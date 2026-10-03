<?php
declare(strict_types=1);

/**
 * School Markaz — demo seed script.
 * Run once: php database/seed.php  (or open database/seed.php in browser)
 * Idempotent: exits early if the demo admin already exists.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$pdo = db();

// Already seeded?
$st = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
$st->execute(['admin']);
if ((int) $st->fetchColumn() > 0) {
    echo "Already seeded — skipping.\n";
    exit;
}

echo "Seeding School Markaz demo data...\n";
$pdo->beginTransaction();

try {
    $hash = password_hash('demo123', PASSWORD_DEFAULT);

    // --- School profile -------------------------------------------------------
    $pdo->exec("INSERT INTO schools (name, address, phone, email, session_year, package_id)
        VALUES ('Markaz Public School', 'Main Road, Umerkot, Sindh', '0300-1234567',
        'info@markazschool.edu.pk', '2026-27', 2)");

    // --- Users ----------------------------------------------------------------
    $mkUser = function (string $role, string $username) use ($pdo, $hash): int {
        $st = $pdo->prepare('INSERT INTO users (role, username, password_hash) VALUES (?,?,?)');
        $st->execute([$role, $username, $hash]);
        return (int) $pdo->lastInsertId();
    };
    $adminId    = $mkUser('admin', 'admin');
    $t1User     = $mkUser('teacher', 'teacher');
    $t2User     = $mkUser('teacher', 'teacher2');
    $s1User     = $mkUser('student', 'student');
    $p1User     = $mkUser('parent', 'parent');

    // --- Classes ---------------------------------------------------------------
    $classIds = [];
    foreach ([['Class 5', 'A'], ['Class 6', 'A'], ['Class 8', 'A']] as [$cname, $sec]) {
        $st = $pdo->prepare('INSERT INTO classes (name, section) VALUES (?,?)');
        $st->execute([$cname, $sec]);
        $classIds[$cname] = (int) $pdo->lastInsertId();
    }

    // --- Subjects ---------------------------------------------------------------
    $subjectsByClass = [
        'Class 5' => ['Urdu', 'English', 'Mathematics', 'Science', 'Islamiat'],
        'Class 6' => ['Urdu', 'English', 'Mathematics', 'Science', 'Islamiat', 'Computer'],
        'Class 8' => ['Urdu', 'English', 'Mathematics', 'Physics', 'Chemistry', 'Islamiat'],
    ];
    $subjectIds = [];
    foreach ($subjectsByClass as $cname => $subs) {
        foreach ($subs as $s) {
            $st = $pdo->prepare('INSERT INTO subjects (name, class_id) VALUES (?,?)');
            $st->execute([$s, $classIds[$cname]]);
            $subjectIds[$cname][] = (int) $pdo->lastInsertId();
        }
    }

    // --- Teachers ----------------------------------------------------------------
    $st = $pdo->prepare("INSERT INTO teachers (staff_no, name, phone, cnic, subject, salary, joining_date, user_id)
        VALUES (?,?,?,?,?,?,?,?)");
    $st->execute(['T-001', 'Ahmed Khan', '0301-1111111', '44101-1111111-1', 'Mathematics', 45000, '2023-04-01', $t1User]);
    $t1Id = (int) $pdo->lastInsertId();
    $st->execute(['T-002', 'Sara Ahmed', '0302-2222222', '44101-2222222-2', 'English', 42000, '2023-04-01', $t2User]);
    $t2Id = (int) $pdo->lastInsertId();

    // --- Parents (10 demo parents; first one is parent/demo123) -------------------
    $parentNames = ['Muhammad Aslam', 'Rashid Mehmood', 'Khalid Hussain', 'Imran Ali',
        'Farooq Sheikh', 'Bilal Raza', 'Nadeem Akhtar', 'Shahid Iqbal', 'Tariq Javed', 'Usman Ghani'];
    $parentIds = [];
    foreach ($parentNames as $i => $pname) {
        $uid = ($i === 0) ? $p1User : $mkUser('parent', 'parent' . ($i + 1));
        $st = $pdo->prepare('INSERT INTO parents (name, phone, cnic, address, user_id) VALUES (?,?,?,?,?)');
        $st->execute([$pname, '0300-10000' . ($i + 10), '44101-000000' . $i . '-1', 'House ' . ($i + 1) . ', Umerkot', $uid]);
        $parentIds[] = (int) $pdo->lastInsertId();
    }

    // --- Students (~30) ------------------------------------------------------------
    $first = ['Ali', 'Usama', 'Hamza', 'Danish', 'Fahad', 'Zain', 'Umar', 'Hassan', 'Ayan', 'Rayyan',
        'Fatima', 'Ayesha', 'Maryam', 'Zainab', 'Hira', 'Sana', 'Iqra', 'Mahnoor', 'Laiba', 'Sadia'];
    $last = ['Khan', 'Ahmed', 'Ali', 'Raza', 'Hussain', 'Sheikh', 'Malik', 'Butt', 'Chaudhry', 'Farooq'];
    $studentIds = [];
    $n = 0;
    foreach (['Class 5', 'Class 6', 'Class 8'] as $cname) {
        for ($i = 0; $i < 10; $i++) {
            $n++;
            $name = $first[$n % count($first)] . ' ' . $last[($n * 3) % count($last)];
            $uid = ($n === 1) ? $s1User : $mkUser('student', 'student' . $n);
            $adm = 'SM-2026-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
            $st = $pdo->prepare("INSERT INTO students
                (admission_no, name, gender, dob, class_id, section, parent_id, admission_date, user_id)
                VALUES (?,?,?,?,?,?,?,?,?)");
            $st->execute([
                $adm, $name, ($n % 2 ? 'Male' : 'Female'), '201' . ($n % 10) . '-0' . (($n % 9) + 1) . '-1' . ($n % 9 + 1),
                $classIds[$cname], 'A', $parentIds[$n % count($parentIds)], '2026-04-01', $uid,
            ]);
            $studentIds[] = (int) $pdo->lastInsertId();
        }
    }

    // --- Attendance: last ~40 weekdays -----------------------------------------------
    $dates = [];
    $d = new DateTime('-60 days');
    while (count($dates) < 40) {
        $d->modify('+1 day');
        if ((int) $d->format('N') <= 5) {
            $dates[] = $d->format('Y-m-d');
        }
    }
    $stA = $pdo->prepare('INSERT IGNORE INTO student_attendance (student_id, `date`, status, marked_by) VALUES (?,?,?,?)');
    foreach ($studentIds as $si => $sid) {
        foreach ($dates as $dt) {
            $r = ($si * 7 + crc32($dt)) % 100;
            $status = $r < 88 ? 'P' : ($r < 95 ? 'A' : 'L');
            $stA->execute([$sid, $dt, $status, $adminId]);
        }
    }

    // --- Fee heads --------------------------------------------------------------------
    $heads = [
        ['Tuition Fee', 2500, null, 'monthly'],
        ['Admission Fee', 3000, null, 'one-time'],
        ['Exam Fee', 800, null, 'one-time'],
        ['Transport', 1200, null, 'monthly'],
    ];
    $headIds = [];
    $st = $pdo->prepare('INSERT INTO fee_heads (name, amount, class_id, frequency) VALUES (?,?,?,?)');
    foreach ($heads as $h) {
        $st->execute($h);
        $headIds[] = (int) $pdo->lastInsertId();
    }

    // --- Invoices for last 2 months ------------------------------------------------------
    $curM = (int) date('n');
    $curY = (int) date('Y');
    $prevM = $curM === 1 ? 12 : $curM - 1;
    $prevY = $curM === 1 ? $curY - 1 : $curY;
    $stI = $pdo->prepare("INSERT INTO fee_invoices
        (student_id, `month`, `year`, due_date, total, discount, fine, paid, status) VALUES (?,?,?,?,?,?,?,?,?)");
    $stIt = $pdo->prepare('INSERT INTO fee_invoice_items (invoice_id, fee_head_id, label, amount) VALUES (?,?,?,?)');
    $stP = $pdo->prepare("INSERT INTO fee_payments
        (invoice_id, amount, method, received_by, receipt_no, note) VALUES (?,?,?,?,?,?)");
    $receiptN = 1;
    foreach ([$prevY . '-' . $prevM, $curY . '-' . $curM] as $idx => $ym) {
        [$yy, $mm] = array_map('intval', explode('-', $ym));
        foreach ($studentIds as $si => $sid) {
            $items = [['Tuition Fee', 2500], ['Transport', 1200]];
            if ($idx === 0) {
                $items[] = ['Admission Fee', 3000];
                $items[] = ['Exam Fee', 800];
            }
            $total = array_sum(array_column($items, 1));
            $due = sprintf('%04d-%02d-10', $yy, $mm);
            $stI->execute([$sid, $mm, $yy, $due, $total, 0, 0, 0, 'unpaid']);
            $invId = (int) $pdo->lastInsertId();
            foreach ($items as [$label, $amt]) {
                $stIt->execute([$invId, null, $label, $amt]);
            }
            // ~65% invoices get payments
            if (($si + $idx) % 3 !== 0) {
                $payFull = ($si + $idx) % 3 === 1;
                $amt = $payFull ? $total : round($total / 2);
                $rcpt = 'R-' . $yy . '-' . str_pad((string) $receiptN++, 6, '0', STR_PAD_LEFT);
                $stP->execute([$invId, $amt, 'Cash', $adminId, $rcpt, 'Seed payment']);
                $paid = $amt;
                $status = $payFull ? 'paid' : 'partial';
                $pdo->prepare('UPDATE fee_invoices SET paid = ?, status = ? WHERE id = ?')
                    ->execute([$paid, $status, $invId]);
                // Fee income into finance
                $pdo->prepare("INSERT INTO finance_transactions (type, category, amount, `date`, note, ref, created_by)
                    VALUES ('income','Fee Collection',?,?,?, ?,?)")
                    ->execute([$amt, $due, 'Seed fee collection', $rcpt, $adminId]);
            }
        }
    }

    // --- Exam + marks ----------------------------------------------------------------------
    $st = $pdo->prepare('INSERT INTO exams (name, term, `year`, class_id) VALUES (?,?,?,?)');
    $st->execute(['First Term Exam', 'First Term', 2026, null]);
    $examId = (int) $pdo->lastInsertId();
    $stM = $pdo->prepare('INSERT IGNORE INTO marks (exam_id, student_id, subject_id, obtained, total) VALUES (?,?,?,?,?)');
    $classOf = [];
    foreach ($pdo->query('SELECT id, class_id FROM students')->fetchAll() as $row) {
        $classOf[(int) $row['id']] = (int) $row['class_id'];
    }
    $classNameById = array_flip($classIds);
    foreach ($studentIds as $si => $sid) {
        $cname = $classNameById[$classOf[$sid]];
        foreach ($subjectIds[$cname] as $subjId) {
            $obt = 45 + (($si * 13 + $subjId * 7) % 51); // 45..95 deterministic
            $stM->execute([$examId, $sid, $subjId, $obt, 100]);
        }
    }

    // --- Timetable (Mon–Fri, 6 periods) --------------------------------------------------------
    $stT = $pdo->prepare("INSERT IGNORE INTO timetable
        (class_id, `day`, period, subject_id, teacher_id, start_time, end_time) VALUES (?,?,?,?,?,?,?)");
    $times = [['08:00', '08:45'], ['08:45', '09:30'], ['09:45', '10:30'], ['10:30', '11:15'], ['11:30', '12:15'], ['12:15', '13:00']];
    foreach ($classIds as $cname => $cid) {
        $subs = $subjectIds[$cname];
        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day) {
            foreach (range(1, 6) as $p) {
                $subj = $subs[($p - 1) % count($subs)];
                $tid = ($p % 2) ? $t1Id : $t2Id;
                $stT->execute([$cid, $day, $p, $subj, $tid, $times[$p - 1][0], $times[$p - 1][1]]);
            }
        }
    }

    // --- Announcements ----------------------------------------------------------------------------
    $st = $pdo->prepare('INSERT INTO announcements (title, body, audience, created_by) VALUES (?,?,?,?)');
    $st->execute(['Welcome to School Markaz', 'Admissions open for session 2026-27. Visit the office for details.', 'all', $adminId]);
    $st->execute(['Parent-Teacher Meeting', 'PTM on Saturday 10 AM. All parents are requested to attend.', 'parents', $adminId]);
    $st->execute(['First Term Result', 'First term results have been announced. Check your report cards.', 'students', $adminId]);

    // --- Finance extras ---------------------------------------------------------------------------------
    $st = $pdo->prepare("INSERT INTO finance_transactions (type, category, amount, `date`, note, created_by)
        VALUES (?,?,?,?,?,?)");
    $st->execute(['expense', 'Salaries', 87000, date('Y-m-05'), 'September staff salaries', $adminId]);
    $st->execute(['expense', 'Utilities', 12000, date('Y-m-12'), 'Electricity bill', $adminId]);
    $st->execute(['income', 'Donation', 25000, date('Y-m-15'), 'Community donation', $adminId]);

    // --- Salary payments --------------------------------------------------------------------------------------
    $st = $pdo->prepare('INSERT IGNORE INTO salary_payments (teacher_id, `month`, `year`, amount, paid_date, note)
        VALUES (?,?,?,?,?,?)');
    $st->execute([$t1Id, $prevM, $prevY, 45000, sprintf('%04d-%02d-05', $prevY, $prevM), 'Monthly salary']);
    $st->execute([$t2Id, $prevM, $prevY, 42000, sprintf('%04d-%02d-05', $prevY, $prevM), 'Monthly salary']);

    $pdo->commit();
    echo "Seed complete: admin/teacher/student/parent (password: demo123), "
        . count($studentIds) . " students, invoices + payments, exam marks, timetable.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    echo 'SEED FAILED: ' . $e->getMessage() . "\n";
    exit(1);
}
