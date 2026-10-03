<?php
declare(strict_types=1);

/**
 * Admin → Timetable: pick a class → grid Mon–Sat × 6 periods;
 * each cell: subject + teacher selects; save whole grid at once.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';
require_role('admin');

$PERIODS = 6;
$class_id = (int) ($_GET['class_id'] ?? $_POST['class_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $class_id = (int) ($_POST['class_id'] ?? 0);
    $slots = $_POST['slot'] ?? []; // [day][period] => ['subject'=>id,'teacher'=>id]
    $up = db()->prepare('INSERT INTO timetable (class_id, `day`, period, subject_id, teacher_id)
        VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
        subject_id = VALUES(subject_id), teacher_id = VALUES(teacher_id)');
    $del = db()->prepare('DELETE FROM timetable WHERE class_id = ? AND `day` = ? AND period = ?');
    foreach (week_days() as $day) {
        for ($p = 1; $p <= $PERIODS; $p++) {
            $cell = $slots[$day][$p] ?? [];
            $sub = (int) ($cell['subject'] ?? 0);
            $tea = (int) ($cell['teacher'] ?? 0);
            if ($sub === 0 && $tea === 0) {
                $del->execute([$class_id, $day, $p]);
            } else {
                $up->execute([$class_id, $day, $p, $sub ?: null, $tea ?: null]);
            }
        }
    }
    flash('success', 'Timetable saved.');
    redirect('admin/timetable.php?class_id=' . $class_id);
}

$subjects = $class_id > 0 ? sm_subjects($class_id) : [];
$teachers = db()->query('SELECT id, name FROM teachers WHERE status = "Active" ORDER BY name')->fetchAll() ?: [];
$existing = [];
if ($class_id > 0) {
    $st = db()->prepare('SELECT `day`, period, subject_id, teacher_id FROM timetable WHERE class_id = ?');
    $st->execute([$class_id]);
    foreach ($st->fetchAll() as $r) {
        $existing[$r['day']][(int) $r['period']] = $r;
    }
}

layout_top('Timetable', 'timetable');
?>
<div class="card">
  <div class="card-body">
    <form method="get" class="form-row" style="grid-template-columns:1fr auto">
      <div class="field" style="margin:0">
        <label for="class_id">Class</label>
        <select id="class_id" name="class_id"><?= sm_class_options($class_id) ?></select>
      </div>
      <div class="field" style="margin:0;align-self:end"><button class="btn" type="submit">Load</button></div>
    </form>
  </div>
</div>

<?php if ($class_id > 0): ?>
  <div class="card">
    <div class="card-head"><h3>Weekly timetable</h3></div>
    <div class="card-body">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="class_id" value="<?= $class_id ?>">
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr><th>Day</th><?php for ($p = 1; $p <= $PERIODS; $p++): ?><th>Period <?= $p ?></th><?php endfor; ?></tr>
            </thead>
            <tbody>
              <?php foreach (week_days() as $day): ?>
                <tr>
                  <td><strong><?= e($day) ?></strong></td>
                  <?php for ($p = 1; $p <= $PERIODS; $p++):
                    $ex = $existing[$day][$p] ?? null; ?>
                    <td style="min-width:170px">
                      <select name="slot[<?= e($day) ?>][<?= $p ?>][subject]" style="width:100%;margin-bottom:4px">
                        <option value="0">— Subject —</option>
                        <?php foreach ($subjects as $s): ?>
                          <option value="<?= (int) $s['id'] ?>"<?= ($ex && (int) $ex['subject_id'] === (int) $s['id']) ? ' selected' : '' ?>><?= e($s['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <select name="slot[<?= e($day) ?>][<?= $p ?>][teacher]" style="width:100%">
                        <option value="0">— Teacher —</option>
                        <?php foreach ($teachers as $t): ?>
                          <option value="<?= (int) $t['id'] ?>"<?= ($ex && (int) $ex['teacher_id'] === (int) $t['id']) ? ' selected' : '' ?>><?= e($t['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </td>
                  <?php endfor; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <p style="margin-top:14px"><button class="btn" type="submit">Save timetable</button></p>
      </form>
    </div>
  </div>
<?php else: ?>
  <div class="card"><div class="card-body">Select a class to edit its weekly timetable.</div></div>
<?php endif; ?>

<?php layout_bottom(); ?>
