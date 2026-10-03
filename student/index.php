<?php
declare(strict_types=1);

/**
 * Student → Dashboard: my attendance % this month, pending fee, latest result %.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';
require_role('student');

$me = my_profile();
$sid = (int) ($me['id'] ?? 0);

$att = sm_attendance_month($sid, (int) date('n'), (int) date('Y'));
$balance = sm_fee_balance($sid);
$latestPct = sm_latest_result_pct($sid);

/* Latest exam name for context */
$st = db()->prepare('SELECT x.name, x.`year` FROM marks m JOIN exams x ON x.id = m.exam_id
    WHERE m.student_id = ? ORDER BY x.`year` DESC, x.id DESC LIMIT 1');
$st->execute([$sid]);
$latestExam = $st->fetch();

layout_top('Dashboard', 'dashboard');
?>
<div class="grid grid-3" style="margin-bottom:20px">
  <div class="stat green">
    <div class="stat-num"><?= $att['pct'] === null ? '—' : $att['pct'] . '%' ?></div>
    <div class="stat-label">My attendance — <?= e(month_name((int) date('n'))) ?>
      <br><small><?= $att['present'] ?> present · <?= $att['absent'] ?> absent · <?= $att['leave'] ?> leave</small></div>
  </div>
  <div class="stat <?= $balance > 0 ? 'red' : '' ?>">
    <div class="stat-num"><?= e(fmt_money($balance)) ?></div>
    <div class="stat-label">Pending fee<?= $balance > 0 ? ' <a href="fees.php">Pay</a>' : '' ?></div>
  </div>
  <div class="stat primary">
    <div class="stat-num"><?= $latestPct === null ? '—' : $latestPct . '%' ?></div>
    <div class="stat-label">Latest result<?= $latestExam ? ' — ' . e($latestExam['name']) : '' ?></div>
  </div>
</div>

<div class="grid grid-2">
  <div class="card">
    <div class="card-head"><h3>My quick links</h3></div>
    <div class="card-body">
      <p><a class="btn small" href="attendance.php">My attendance</a>
         <a class="btn small secondary" href="results.php">My results</a>
         <a class="btn small secondary" href="fees.php">My fees</a>
         <a class="btn small secondary" href="timetable.php">Timetable</a></p>
    </div>
  </div>
  <?php if ($me): ?>
  <div class="card">
    <div class="card-head"><h3>My profile</h3></div>
    <div class="card-body">
      <p><strong><?= e($me['name']) ?></strong> · <?= e($me['admission_no']) ?><br>
      <?php $c = sm_class((int) $me['class_id']); ?>
      Class: <?= e($c ? $c['name'] . ' (' . $c['section'] . ')' : '—') ?> ·
      <span class="badge green"><?= e($me['status']) ?></span></p>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php layout_bottom(); ?>
