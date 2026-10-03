<?php
declare(strict_types=1);

/**
 * Student → Timetable: my class weekly grid (Mon–Sat × periods).
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';
require_role('student');

$me = my_profile();
$class_id = (int) ($me['class_id'] ?? 0);

$st = db()->prepare("SELECT t.`day`, t.period, s.name AS subject, te.name AS teacher
    FROM timetable t
    LEFT JOIN subjects s ON s.id = t.subject_id
    LEFT JOIN teachers te ON te.id = t.teacher_id
    WHERE t.class_id = ? ORDER BY t.period");
$st->execute([$class_id]);
$grid = [];
$maxPeriod = 0;
foreach ($st->fetchAll() as $r) {
    $grid[$r['day']][(int) $r['period']] = $r;
    $maxPeriod = max($maxPeriod, (int) $r['period']);
}
$class = sm_class($class_id);

layout_top('Timetable', 'timetable');
?>
<div class="card">
  <div class="card-head"><h3>Class timetable<?= $class ? ' — ' . e($class['name'] . ' (' . $class['section'] . ')') : '' ?></h3></div>
  <?php if (!$grid): ?>
    <div class="card-body">Timetable not set yet.</div>
  <?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Day</th><?php for ($p = 1; $p <= $maxPeriod; $p++): ?><th>P<?= $p ?></th><?php endfor; ?></tr></thead>
      <tbody>
        <?php foreach (week_days() as $day): ?>
          <tr>
            <td><strong><?= e($day) ?></strong></td>
            <?php for ($p = 1; $p <= $maxPeriod; $p++):
              $cell = $grid[$day][$p] ?? null; ?>
              <td>
                <?php if ($cell && ($cell['subject'] || $cell['teacher'])): ?>
                  <strong><?= e($cell['subject'] ?? '—') ?></strong><br>
                  <small><?= e($cell['teacher'] ?? '') ?></small>
                <?php else: ?><span style="color:#94a3b8">—</span><?php endif; ?>
              </td>
            <?php endfor; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>

<?php layout_bottom(); ?>
