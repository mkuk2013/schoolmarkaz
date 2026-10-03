<?php
declare(strict_types=1);

/**
 * Teacher → My timetable: weekly Mon–Sat × periods grid for the teacher's slots.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';
require_role('teacher');

$me = my_profile();
$tid = (int) ($me['id'] ?? 0);

$st = db()->prepare("SELECT t.`day`, t.period, CONCAT(c.name,' (',c.section,')') AS class_label,
        s.name AS subject
    FROM timetable t
    JOIN classes c ON c.id = t.class_id
    LEFT JOIN subjects s ON s.id = t.subject_id
    WHERE t.teacher_id = ? ORDER BY t.period");
$st->execute([$tid]);
$grid = [];
$maxPeriod = 0;
foreach ($st->fetchAll() as $r) {
    $grid[$r['day']][(int) $r['period']] = $r;
    $maxPeriod = max($maxPeriod, (int) $r['period']);
}

layout_top('My Timetable', 'timetable');
?>
<div class="card">
  <div class="card-head"><h3>Weekly periods — <?= e($me['name'] ?? '') ?></h3></div>
  <?php if (!$grid): ?>
    <div class="card-body">No periods assigned to you yet.</div>
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
                <?php if ($cell): ?>
                  <span class="badge blue"><?= e($cell['class_label']) ?></span><br>
                  <small><?= e($cell['subject'] ?? '—') ?></small>
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
