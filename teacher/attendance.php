<?php
declare(strict_types=1);

/**
 * Teacher → Mark attendance: pick one of my classes + date → P/A/L/H radio
 * grid, saved with INSERT ... ON DUPLICATE KEY UPDATE.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';
require_role('teacher');

$me = my_profile();
$tid = (int) ($me['id'] ?? 0);
$myClasses = sm_teacher_classes($tid);
$myClassIds = array_map('intval', array_column($myClasses, 'id'));

$date = $_GET['date'] ?? $_POST['date'] ?? today();
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = today();
}
$class_id = (int) ($_GET['class_id'] ?? $_POST['class_id'] ?? 0);
if ($class_id > 0 && !in_array($class_id, $myClassIds, true)) {
    $class_id = 0; // teachers may only mark their own classes
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if ($class_id === 0) {
        flash('error', 'Select one of your classes first.');
    } else {
        $up = db()->prepare('INSERT INTO student_attendance (student_id, `date`, status, marked_by)
            VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by)');
        $n = 0;
        foreach ($_POST['status'] ?? [] as $sid => $status) {
            if (!in_array($status, ['P', 'A', 'L', 'H'], true)) {
                continue;
            }
            $up->execute([(int) $sid, $date, $status, (int) current_user()['id']]);
            $n++;
        }
        flash('success', "Attendance saved for $n students.");
    }
    redirect('teacher/attendance.php?date=' . urlencode($date) . '&class_id=' . $class_id);
}

$students = [];
if ($class_id > 0) {
    $st = db()->prepare('SELECT s.id, s.name, s.admission_no, a.status
        FROM students s LEFT JOIN student_attendance a
          ON a.student_id = s.id AND a.`date` = ?
        WHERE s.class_id = ? AND s.status = "Active" ORDER BY s.name');
    $st->execute([$date, $class_id]);
    $students = $st->fetchAll() ?: [];
}

layout_top('Mark Attendance', 'attendance');
?>
<?php if (!$myClasses): ?>
  <div class="card"><div class="card-body">You are not assigned to any class in the timetable yet.</div></div>
<?php else: ?>
<div class="card">
  <div class="card-body">
    <form method="get" class="form-row" style="grid-template-columns:1fr 1fr auto">
      <div class="field" style="margin:0">
        <label for="class_id">My class</label>
        <select id="class_id" name="class_id" required>
          <option value="">— Select class —</option>
          <?php foreach ($myClasses as $c): ?>
            <option value="<?= (int) $c['id'] ?>"<?= $c['id'] == $class_id ? ' selected' : '' ?>><?= e($c['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="margin:0">
        <label for="date">Date</label>
        <input type="date" id="date" name="date" value="<?= e($date) ?>" max="<?= e(today()) ?>">
      </div>
      <div class="field" style="margin:0;align-self:end"><button class="btn" type="submit">Show</button></div>
    </form>
  </div>
</div>

<?php if ($class_id > 0): ?>
<div class="card">
  <div class="card-head"><h3>Mark attendance — <?= e(fmt_date($date)) ?></h3></div>
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="date" value="<?= e($date) ?>">
      <input type="hidden" name="class_id" value="<?= $class_id ?>">
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>#</th><th>Student</th><th>Current</th><th>Mark</th></tr></thead>
          <tbody>
            <?php $i = 1;
            foreach ($students as $s): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><?= e($s['name']) ?><br><small><?= e($s['admission_no']) ?></small></td>
                <td><?= $s['status'] ? sm_status_badge($s['status']) : '<span class="badge">Not marked</span>' ?></td>
                <td style="white-space:nowrap">
                  <?php foreach (['P' => 'P', 'A' => 'A', 'L' => 'L', 'H' => 'H'] as $code => $label): ?>
                    <label style="margin-right:8px"><input type="radio" name="status[<?= (int) $s['id'] ?>]"
                      value="<?= $code ?>"<?= ($s['status'] ?? 'P') === $code ? ' checked' : '' ?>> <?= $label ?></label>
                  <?php endforeach; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$students): ?><tr><td colspan="4">No active students.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
      <?php if ($students): ?><p style="margin-top:14px"><button class="btn" type="submit">Save attendance</button></p><?php endif; ?>
    </form>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php layout_bottom(); ?>
