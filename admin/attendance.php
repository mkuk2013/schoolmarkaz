<?php
declare(strict_types=1);

/**
 * Admin → Attendance overview: pick date + class → % present/absent/leave
 * stat cards + student rows; quick "mark all present" action; per-student radios.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';
require_role('admin');

$date = $_GET['date'] ?? $_POST['date'] ?? today();
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = today();
}
$class_id = (int) ($_GET['class_id'] ?? $_POST['class_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_all_present') {
        $st = db()->prepare('INSERT INTO student_attendance (student_id, `date`, status, marked_by)
            SELECT id, ?, "P", ? FROM students WHERE class_id = ? AND status = "Active"
            ON DUPLICATE KEY UPDATE status = "P", marked_by = VALUES(marked_by)');
        $st->execute([$date, (int) current_user()['id'], $class_id]);
        flash('success', 'All students marked present for ' . fmt_date($date) . '.');
    } elseif ($action === 'save') {
        $rows = $_POST['status'] ?? [];
        $up = db()->prepare('INSERT INTO student_attendance (student_id, `date`, status, marked_by)
            VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by)');
        $me = (int) current_user()['id'];
        $n = 0;
        foreach ($rows as $sid => $status) {
            if (!in_array($status, ['P', 'A', 'L', 'H'], true)) {
                continue;
            }
            $up->execute([(int) $sid, $date, $status, $me]);
            $n++;
        }
        flash('success', "Attendance saved for $n students (" . fmt_date($date) . ").");
    }
    redirect('admin/attendance.php?date=' . urlencode($date) . '&class_id=' . $class_id);
}

$students = [];
$stats = ['P' => 0, 'A' => 0, 'L' => 0, 'H' => 0, 'marked' => 0];
if ($class_id > 0) {
    $st = db()->prepare('SELECT s.id, s.name, s.admission_no, a.status
        FROM students s LEFT JOIN student_attendance a
          ON a.student_id = s.id AND a.`date` = ?
        WHERE s.class_id = ? AND s.status = "Active"
        ORDER BY s.name');
    $st->execute([$date, $class_id]);
    $students = $st->fetchAll() ?: [];
    foreach ($students as $s) {
        if ($s['status']) {
            $stats[$s['status']]++;
            $stats['marked']++;
        }
    }
}

layout_top('Attendance', 'attendance');
?>
<div class="card">
  <div class="card-body">
    <form method="get" class="form-row" style="grid-template-columns:1fr 1fr auto">
      <div class="field" style="margin:0">
        <label for="date">Date</label>
        <input type="date" id="date" name="date" value="<?= e($date) ?>" max="<?= e(today()) ?>">
      </div>
      <div class="field" style="margin:0">
        <label for="class_id">Class</label>
        <select id="class_id" name="class_id"><?= sm_class_options($class_id) ?></select>
      </div>
      <div class="field" style="margin:0;align-self:end">
        <button class="btn" type="submit">Show</button>
      </div>
    </form>
  </div>
</div>

<?php if ($class_id > 0): ?>
  <?php $total = count($students); ?>
  <div class="grid grid-4" style="margin-bottom:20px">
    <div class="stat green"><div class="stat-num"><?= $total ? round(100 * $stats['P'] / $total, 1) : 0 ?>%</div><div class="stat-label">Present (<?= $stats['P'] ?>/<?= $total ?>)</div></div>
    <div class="stat red"><div class="stat-num"><?= $total ? round(100 * $stats['A'] / $total, 1) : 0 ?>%</div><div class="stat-label">Absent (<?= $stats['A'] ?>)</div></div>
    <div class="stat amber"><div class="stat-num"><?= $total ? round(100 * $stats['L'] / $total, 1) : 0 ?>%</div><div class="stat-label">On Leave (<?= $stats['L'] ?>)</div></div>
    <div class="stat"><div class="stat-num"><?= $stats['marked'] ?>/<?= $total ?></div><div class="stat-label">Marked</div></div>
  </div>

  <div class="card">
    <div class="card-head">
      <h3>Class attendance — <?= e(fmt_date($date)) ?></h3>
      <form method="post" style="display:inline">
        <?= csrf_field() ?>
        <input type="hidden" name="date" value="<?= e($date) ?>">
        <input type="hidden" name="class_id" value="<?= $class_id ?>">
        <input type="hidden" name="action" value="mark_all_present">
        <button class="btn small success" type="submit"
          onclick="return confirm('Mark ALL students present for <?= e(fmt_date($date)) ?>? Existing marks will be overwritten.')">
          ✓ Mark all present
        </button>
      </form>
    </div>
    <div class="card-body">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="date" value="<?= e($date) ?>">
        <input type="hidden" name="class_id" value="<?= $class_id ?>">
        <input type="hidden" name="action" value="save">
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>#</th><th>Admission No</th><th>Student</th><th>Status</th><th>Mark</th></tr></thead>
            <tbody>
              <?php $i = 1;
              foreach ($students as $s): ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td><?= e($s['admission_no']) ?></td>
                  <td><?= e($s['name']) ?></td>
                  <td><?= $s['status'] ? sm_status_badge($s['status']) : '<span class="badge">Not marked</span>' ?></td>
                  <td style="white-space:nowrap">
                    <?php foreach (['P' => 'P', 'A' => 'A', 'L' => 'L', 'H' => 'H'] as $code => $label): ?>
                      <label style="margin-right:8px"><input type="radio" name="status[<?= (int) $s['id'] ?>]"
                        value="<?= $code ?>"<?= ($s['status'] ?? 'P') === $code ? ' checked' : '' ?>> <?= $label ?></label>
                    <?php endforeach; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$students): ?>
                <tr><td colspan="5">No active students in this class.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($students): ?>
          <p style="margin-top:14px"><button class="btn" type="submit">Save attendance</button></p>
        <?php endif; ?>
      </form>
    </div>
  </div>
<?php else: ?>
  <div class="card"><div class="card-body">Select a date and class to view attendance.</div></div>
<?php endif; ?>

<?php layout_bottom(); ?>
