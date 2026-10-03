<?php
declare(strict_types=1);

/**
 * Academics + Portals shared helpers (used by admin/teacher/student/parent pages).
 * Included explicitly by those pages after bootstrap.
 */

/**
 * All classes ordered by name/section.
 * @return array<int,array{id:int,name:string,section:string,label:string}>
 */
function sm_classes(): array
{
    $rows = db()->query("SELECT id, name, section, CONCAT(name,' (',section,')') AS label
        FROM classes ORDER BY name, section")->fetchAll();
    return $rows ?: [];
}

/**
 * Render <option> tags for a class select.
 */
function sm_class_options(?int $selected = null): string
{
    $out = '<option value="">— Select class —</option>';
    foreach (sm_classes() as $c) {
        $out .= sprintf('<option value="%d"%s>%s</option>',
            (int) $c['id'], ((int) $c['id'] === (int) $selected ? ' selected' : ''), e($c['label']));
    }
    return $out;
}

/**
 * Subjects for a class (class_id NULL rows are treated as school-wide).
 * @return array<int,array{id:int,name:string,code:string}>
 */
function sm_subjects(int $class_id): array
{
    $st = db()->prepare('SELECT id, name, code FROM subjects
        WHERE class_id = ? OR class_id IS NULL ORDER BY name');
    $st->execute([$class_id]);
    return $st->fetchAll() ?: [];
}

/** @return array{id:int,name:string,section:string}|null */
function sm_class(int $class_id): ?array
{
    $st = db()->prepare('SELECT id, name, section FROM classes WHERE id = ? LIMIT 1');
    $st->execute([$class_id]);
    return $st->fetch() ?: null;
}

/** @return array{id:int,name:string,term:string,year:int,class_id:?int}|null */
function sm_exam(int $exam_id): ?array
{
    $st = db()->prepare('SELECT id, name, term, `year`, class_id FROM exams WHERE id = ? LIMIT 1');
    $st->execute([$exam_id]);
    $row = $st->fetch() ?: null;
    if ($row) {
        $row['class_id'] = $row['class_id'] === null ? null : (int) $row['class_id'];
    }
    return $row;
}

/**
 * Attendance summary for a student in a month.
 * @return array{pct:?float,present:int,absent:int,leave:int,holiday:int,total:int}
 */
function sm_attendance_month(int $student_id, int $month, int $year): array
{
    $st = db()->prepare("SELECT status, COUNT(*) c FROM student_attendance
        WHERE student_id = ? AND MONTH(`date`) = ? AND YEAR(`date`) = ? GROUP BY status");
    $st->execute([$student_id, $month, $year]);
    $counts = ['P' => 0, 'A' => 0, 'L' => 0, 'H' => 0];
    foreach ($st->fetchAll() as $r) {
        $counts[$r['status']] = (int) $r['c'];
    }
    $denom = $counts['P'] + $counts['A'];
    return [
        'pct'     => $denom > 0 ? round(100 * $counts['P'] / $denom, 1) : null,
        'present' => $counts['P'],
        'absent'  => $counts['A'],
        'leave'   => $counts['L'],
        'holiday' => $counts['H'],
        'total'   => array_sum($counts),
    ];
}

/**
 * Outstanding fee balance (total − paid) across unpaid/partial invoices.
 */
function sm_fee_balance(int $student_id): float
{
    $st = db()->prepare("SELECT COALESCE(SUM(total - paid), 0) FROM fee_invoices
        WHERE student_id = ? AND status <> 'paid'");
    $st->execute([$student_id]);
    return (float) $st->fetchColumn();
}

/**
 * Result of a student in an exam: per-subject rows + grand totals + %.
 * @return array{subjects:array,obtained:float,total:float,pct:?float}
 */
function sm_exam_result(int $exam_id, int $student_id): array
{
    $st = db()->prepare('SELECT s.name AS subject, m.obtained, m.total
        FROM marks m JOIN subjects s ON s.id = m.subject_id
        WHERE m.exam_id = ? AND m.student_id = ? ORDER BY s.name');
    $st->execute([$exam_id, $student_id]);
    $rows = $st->fetchAll() ?: [];
    $obtained = 0.0;
    $total = 0.0;
    foreach ($rows as $r) {
        $obtained += (float) $r['obtained'];
        $total += (float) $r['total'];
    }
    return [
        'subjects' => $rows,
        'obtained' => $obtained,
        'total'    => $total,
        'pct'      => $total > 0 ? round(100 * $obtained / $total, 1) : null,
    ];
}

/**
 * Latest exam result % for a student (across all exams they have marks in).
 */
function sm_latest_result_pct(int $student_id): ?float
{
    $st = db()->prepare('SELECT m.exam_id, SUM(m.obtained) o, SUM(m.total) t
        FROM marks m JOIN exams x ON x.id = m.exam_id
        WHERE m.student_id = ? GROUP BY m.exam_id
        ORDER BY x.`year` DESC, x.id DESC LIMIT 1');
    $st->execute([$student_id]);
    $r = $st->fetch();
    if (!$r || (float) $r['t'] <= 0) {
        return null;
    }
    return round(100 * (float) $r['o'] / (float) $r['t'], 1);
}

/**
 * Grade from percentage: A+ ≥90, A ≥80, B ≥70, C ≥60, D ≥50, F <50.
 */
function sm_grade(?float $pct): string
{
    if ($pct === null) {
        return '—';
    }
    if ($pct >= 90) return 'A+';
    if ($pct >= 80) return 'A';
    if ($pct >= 70) return 'B';
    if ($pct >= 60) return 'C';
    if ($pct >= 50) return 'D';
    return 'F';
}

/**
 * Class position of a student in an exam (1-based rank by total obtained,
 * only among students of the same class who have marks in this exam).
 */
function sm_class_position(int $exam_id, int $student_id): ?int
{
    $st = db()->prepare('SELECT class_id FROM students WHERE id = ? LIMIT 1');
    $st->execute([$student_id]);
    $class_id = $st->fetchColumn();
    if (!$class_id) {
        return null;
    }
    $st = db()->prepare('SELECT m.student_id, SUM(m.obtained) total_obt
        FROM marks m JOIN students s ON s.id = m.student_id
        WHERE m.exam_id = ? AND s.class_id = ?
        GROUP BY m.student_id ORDER BY total_obt DESC, m.student_id ASC');
    $st->execute([$exam_id, (int) $class_id]);
    $rank = 1;
    foreach ($st->fetchAll() as $r) {
        if ((int) $r['student_id'] === $student_id) {
            return $rank;
        }
        $rank++;
    }
    return null;
}

/**
 * Attendance status badge HTML.
 */
function sm_status_badge(string $status): string
{
    $map = ['P' => ['green', 'Present'], 'A' => ['red', 'Absent'],
            'L' => ['amber', 'Leave'], 'H' => ['blue', 'Holiday']];
    [$cls, $label] = $map[$status] ?? ['badge', $status];
    return '<span class="badge ' . $cls . '">' . $label . '</span>';
}

/**
 * Children of the current parent user. Returns [] for non-parents.
 * @return array<int,array{id:int,name:string,class_id:int,admission_no:string}>
 */
function sm_my_children(): array
{
    $p = my_profile();
    if (!$p || user_role() !== 'parent') {
        return [];
    }
    $st = db()->prepare('SELECT id, name, class_id, admission_no FROM students
        WHERE parent_id = ? AND status = ? ORDER BY name');
    $st->execute([(int) $p['id'], 'Active']);
    return $st->fetchAll() ?: [];
}

/**
 * Verify a student belongs to the given parent. Returns the student row or null.
 * @return array<string,mixed>|null
 */
function sm_verify_child(int $student_id, int $parent_id): ?array
{
    $st = db()->prepare('SELECT * FROM students WHERE id = ? AND parent_id = ? LIMIT 1');
    $st->execute([$student_id, $parent_id]);
    return $st->fetch() ?: null;
}

/**
 * Teacher's classes: distinct classes with timetable slots, or where they are class teacher.
 * @return array<int,array{id:int,label:string}>
 */
function sm_teacher_classes(int $teacher_id): array
{
    $st = db()->prepare("SELECT DISTINCT c.id, CONCAT(c.name,' (',c.section,')') AS label
        FROM classes c
        LEFT JOIN timetable t ON t.class_id = c.id AND t.teacher_id = ?
        WHERE t.id IS NOT NULL OR c.class_teacher_id = ?
        ORDER BY c.name, c.section");
    $st->execute([$teacher_id, $teacher_id]);
    return $st->fetchAll() ?: [];
}
