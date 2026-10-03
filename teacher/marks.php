<?php
declare(strict_types=1);

/**
 * Teacher → Enter marks: pick an exam, then a class → students × subjects
 * grid with obtained/total inputs, saved with INSERT ... ON DUPLICATE KEY UPDATE.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';
require_role('teacher');

$me = my_profile();
$tid = (int) ($me['id'] ?? 0);
$myClasses = sm_teacher_classes($tid);
$myClassIds = array_map('intval', array_column($myClasses, 'id'));

$exams = db()->query('SELECT * FROM exams ORDER BY `year` DESC, id DESC')->fetchAll() ?: [];

$exam_id = (int) ($_GET['exam_id'] ?? $_POST['exam_id'] ?? 0);
$class_id = (int) ($_GET['class_id'] ?? $_POST['class_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $exam_id = (int) ($_POST['exam_id'] ?? 0);
    $class_id = (int) ($_POST['class_id'] ?? 0);
    if ($exam_id <= 0 || !in_array($class_id, $myClassIds, true)) {
        flash('error', 'Invalid exam or class.');
    } else {
        $up = db()->prepare('INSERT INTO marks (exam_id, student_id, subject_id, obtained, total)
            VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
            obtained = VALUES(obtained), total = VALUES(total)');
        $totals = $_POST['total'] ?? [];
        $n = 0;
        foreach ($_POST['obtained'] ?? [] as $sid => $subs) {
            foreach ($subs as $subid => $val) {
                if ($val === '' || $val === null) {
                    continue;
                }
                $t = isset($totals[$subid]) && $totals[$subid] !== '' ? max(1, (float) $totals[$subid]) : 100.0;
                $up->execute([$exam_id, (int) $sid, (int) $subid, max(0, (float) $val), $t]);
                $n++;
            }
        }
        flash('success', "Saved $n marks entries.");
    }
    redirect('teacher/marks.php?exam_id=' . $exam_id . '&class_id=' . $class_id);
}

$students = [];
$subjects = [];
$existing = [];
if ($exam_id > 0 && $class_id > 0 && in_array($class_id, $myClassIds, true)) {
    $st = db()->prepare('SELECT id, name, admission_no FROM students
        WHERE class_id = ? AND status = "Active" ORDER BY name');
    $st->execute([$class_id]);
    $students = $st->fetchAll() ?: [];
    $subjects = sm_subjects($class_id);
    $st = db()->prepare('SELECT student_id, subject_id, obtained, total FROM marks WHERE exam_id = ?');
    $st->execute([$exam_id]);
    foreach ($st->fetchAll() as $r) {
        $existing[(int) $r['student_id']][(int) $r['subject_id']] = $r;
    }
}

layout_top('Enter Marks', 'marks');
?>
<?php if (!$myClasses): ?>
  <div class="card"><div class="card-body">You are not assigned to any class yet.</div></div>
<?php else: ?>
<div class="card">
  <div class="card-body">
    <form method="get" class="form-row" style="grid-template-columns:1fr 1fr auto">
      <div class="field" style="margin:0">
        <label for="exam_id">Exam</label>
        <select id="exam_id" name="exam_id" required>
          <option value="">— Select exam —</option>
          <?php foreach ($exams as $x): ?>
            <option value="<?= (int) $x['id'] ?>"<?= $exam_id === (int) $x['id'] ? ' selected' : '' ?>>
              <?= e($x['name']) ?> (<?= (int) $x['year'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="margin:0">
        <label for="class_id">My class</label>
        <select id="class_id" name="class_id" required>
          <option value="">— Select class —</option>
          <?php foreach ($myClasses as $c): ?>
            <option value="<?= (int) $c['id'] ?>"<?= $class_id == $c['id'] ? ' selected' : '' ?>><?= e($c['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="margin:0;align-self:end"><button class="btn" type="submit">Load grid</button></div>
    </form>
  </div>
</div>

<?php if ($exam_id > 0 && $class_id > 0): ?>
<div class="card">
  <div class="card-head"><h3>Marks entry</h3></div>
  <div class="card-body">
    <?php if (!$students || !$subjects): ?>
      <div class="alert alert-info">No students or subjects in this class yet.</div>
    <?php else: ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
        <input type="hidden" name="class_id" value="<?= $class_id ?>">
        <div class="table-wrap">
          <table class="table">
            <thead><tr>
              <th>Student</th>
              <?php foreach ($subjects as $sub): ?>
                <th><?= e($sub['name']) ?><br>
                  <input type="number" name="total[<?= (int) $sub['id'] ?>]" min="1" step="1"
                    style="width:80px" placeholder="Total"
                    value="<?= e((string) ($existing[$students[0]['id']][$sub['id']]['total'] ?? '')) ?>">
                </th>
              <?php endforeach; ?>
            </tr></thead>
            <tbody>
              <?php foreach ($students as $s): ?>
                <tr>
                  <td><?= e($s['name']) ?><br><small><?= e($s['admission_no']) ?></small></td>
                  <?php foreach ($subjects as $sub):
                    $ex = $existing[(int) $s['id']][(int) $sub['id']] ?? null; ?>
                    <td><input type="number" min="0" step="0.5" style="width:90px"
                      name="obtained[<?= (int) $s['id'] ?>][<?= (int) $sub['id'] ?>]"
                      value="<?= $ex ? e((string) rtrim(rtrim((string) $ex['obtained'], '0'), '.')) : '' ?>"
                      placeholder="—"></td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <p class="hint">Set "Total" once per subject (header row); obtained marks are per student.</p>
        <p><button class="btn" type="submit">Save marks</button></p>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php layout_bottom(); ?>
