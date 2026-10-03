<?php
declare(strict_types=1);

/**
 * Teacher → Dashboard: my periods today, number of classes, this month's
 * attendance % across my classes.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';
require_role('teacher');

$me = my_profile();
$tid = (int) ($me['id'] ?? 0);
$todayName = date('l'); // Monday..Sunday

/* Today's periods for this teacher */
$st = db()->prepare("SELECT t.period, t.`day`, CONCAT(c.name,' (',c.section,')') AS class_label,
        s.name AS subject, c.id AS class_id
    FROM timetable t
    JOIN classes c ON c.id = t.class_id
    LEFT JOIN subjects s ON s.id = t.subject_id
    WHERE t.teacher_id = ? AND t.`day` = ?
    ORDER BY t.period");
$st->execute([$tid, $todayName]);
$today = $st->fetchAll() ?: [];

$classes = sm_teacher_classes($tid);

/* Month attendance % of my classes */
$m = (int) date('n');
$y = (int) date('Y');
$classIds = array_column($classes, 'id');
$att = null;
if ($classIds) {
    $in = implode(',', array_fill(0, count($classIds), '?'));
    $st = db()->prepare("SELECT a.status, COUNT(*) c
        FROM student_attendance a
        JOIN students s ON s.id = a.student_id
        WHERE s.class_id IN ($in) AND MONTH(a.`date`) = ? AND YEAR(a.`date`) = ?
        GROUP BY a.status");
    $params = array_merge($classIds, [$m, $y]);
    $st->execute($params);
    $p = $a2 = 0;
    foreach ($st->fetchAll() as $r) {
        if ($r['status'] === 'P') $p += (int) $r['c'];
        if ($r['status'] === 'A') $a2 += (int) $r['c'];
    }
    $att = ($p + $a2) > 0 ? round(100 * $p / ($p + $a2), 1) : null;
}

layout_top('Dashboard', 'dashboard');
?>
<div class="grid grid-3" style="margin-bottom:20px">
  <div class="stat primary"><div class="stat-num"><?= count($today) ?></div><div class="stat-label">My periods today (<?= e($todayName) ?>)</div></div>
  <div class="stat"><div class="stat-num"><?= count($classes) ?></div><div class="stat-label">Classes I teach</div></div>
  <div class="stat green"><div class="stat-num"><?= $att === null ? '—' : $att . '%' ?></div><div class="stat-label">Attendance % — <?= e(month_name($m)) ?></div></div>
</div>

<div class="card">
  <div class="card-head"><h3>Today's periods</h3><a class="btn small secondary" href="timetable.php">Full timetable</a></div>
  <?php if ($today): ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Period</th><th>Class</th><th>Subject</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($today as $t): ?>
          <tr>
            <td>Period <?= (int) $t['period'] ?></td>
            <td><?= e($t['class_label']) ?></td>
            <td><?= e($t['subject'] ?? '—') ?></td>
            <td><a class="btn small" href="attendance.php?class_id=<?= (int) $t['class_id'] ?>&date=<?= e(today()) ?>">Mark attendance</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php else: ?>
    <div class="card-body">No periods scheduled for today<?= in_array($todayName, ['Sunday'], true) ? ' (Sunday)' : '' ?>. Enjoy the break!</div>
  <?php endif; ?>
</div>

<?php if ($me): ?>
<div class="card">
  <div class="card-head"><h3>My profile</h3></div>
  <div class="card-body">
    <p><strong><?= e($me['name']) ?></strong> · <?= e($me['staff_no']) ?> · Subject: <?= e($me['subject'] ?: '—') ?><br>
    <span class="badge green"><?= e($me['status']) ?></span></p>
  </div>
</div>
<?php endif; ?>

<?php layout_bottom(); ?>
