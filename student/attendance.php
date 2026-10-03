<?php
declare(strict_types=1);

/**
 * Student → My attendance: month picker → day grid with P/A/L colored.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';
require_role('student');

$me = my_profile();
$sid = (int) ($me['id'] ?? 0);

$month = (int) ($_GET['month'] ?? date('n'));
$year = (int) ($_GET['year'] ?? date('Y'));
$month = max(1, min(12, $month));
$year = max(2000, min(2100, $year));

$st = db()->prepare('SELECT `date`, status FROM student_attendance
    WHERE student_id = ? AND MONTH(`date`) = ? AND YEAR(`date`) = ?');
$st->execute([$sid, $month, $year]);
$byDay = [];
foreach ($st->fetchAll() as $r) {
    $byDay[(int) date('j', strtotime($r['date']))] = $r['status'];
}

$summary = sm_attendance_month($sid, $month, $year);
$daysInMonth = (int) date('t', mktime(0, 0, 0, $month, 1, $year));

$colors = ['P' => '#16a34a', 'A' => '#dc2626', 'L' => '#d97706', 'H' => '#64748b'];

layout_top('My Attendance', 'attendance');
?>
<div class="card">
  <div class="card-body">
    <form method="get" class="form-row" style="grid-template-columns:1fr 1fr auto">
      <div class="field" style="margin:0">
        <label for="month">Month</label>
        <select id="month" name="month">
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>"<?= $m === $month ? ' selected' : '' ?>><?= e(month_name($m)) ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="field" style="margin:0">
        <label for="year">Year</label>
        <input type="number" id="year" name="year" min="2000" max="2100" value="<?= $year ?>">
      </div>
      <div class="field" style="margin:0;align-self:end"><button class="btn" type="submit">Show</button></div>
    </form>
  </div>
</div>

<div class="grid grid-4" style="margin-bottom:20px">
  <div class="stat green"><div class="stat-num"><?= $summary['present'] ?></div><div class="stat-label">Present</div></div>
  <div class="stat red"><div class="stat-num"><?= $summary['absent'] ?></div><div class="stat-label">Absent</div></div>
  <div class="stat amber"><div class="stat-num"><?= $summary['leave'] ?></div><div class="stat-label">Leave</div></div>
  <div class="stat primary"><div class="stat-num"><?= $summary['pct'] === null ? '—' : $summary['pct'] . '%' ?></div><div class="stat-label">Attendance %</div></div>
</div>

<div class="card">
  <div class="card-head"><h3><?= e(month_name($month)) ?> <?= $year ?></h3></div>
  <div class="card-body">
    <div class="grid" style="grid-template-columns:repeat(7,1fr);gap:8px">
      <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?>
        <div style="text-align:center;font-size:12px;color:var(--muted);font-weight:600"><?= $d ?></div>
      <?php endforeach; ?>
      <?php
      $firstDow = (int) date('N', mktime(0, 0, 0, $month, 1, $year)); // 1=Mon
      for ($i = 1; $i < $firstDow; $i++) echo '<div></div>';
      for ($d = 1; $d <= $daysInMonth; $d++):
        $st2 = $byDay[$d] ?? null;
      ?>
        <div title="<?= e(fmt_date(sprintf('%04d-%02d-%02d', $year, $month, $d))) ?><?= $st2 ? ' — ' . e($st2) : '' ?>"
          style="text-align:center;padding:10px 4px;border-radius:8px;border:1px solid var(--border);
                 <?= $st2 ? 'background:' . $colors[$st2] . ';color:#fff;font-weight:700;border-color:transparent' : 'color:var(--muted)' ?>">
          <?= $d ?>
        </div>
      <?php endfor; ?>
    </div>
    <p style="margin-top:14px">
      <?php foreach ($colors as $code => $col): ?>
        <span class="badge" style="background:<?= $col ?>;color:#fff;margin-right:6px"><?= $code ?></span>
      <?php endforeach; ?>
    </p>
  </div>
</div>

<?php layout_bottom(); ?>
