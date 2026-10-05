<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/bootstrap.php';
require_role('admin');

$pdo = db();
$year  = (int)date('Y');
$month = (int)date('n');
$today = today();

$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE status='Active'")->fetchColumn();
$totalTeachers = (int)$pdo->query("SELECT COUNT(*) FROM teachers WHERE status='Active'")->fetchColumn();

$st = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM fee_payments WHERE YEAR(created_at)=? AND MONTH(created_at)=?');
$st->execute([$year, $month]);
$feesThisMonth = (float)$st->fetchColumn();

$st = $pdo->prepare('SELECT COUNT(*), COALESCE(SUM(status="P"),0) FROM student_attendance WHERE `date`=?');
$st->execute([$today]);
[$attTotal, $attPresent] = array_map('intval', $st->fetch(PDO::FETCH_NUM));
$attPct = $attTotal > 0 ? round($attPresent / $attTotal * 100, 1) : 0;

$st = $pdo->prepare("SELECT COUNT(*) FROM fee_invoices WHERE `month`=? AND `year`=? AND status IN ('unpaid','partial')");
$st->execute([$month, $year]);
$defaulters = (int)$st->fetchColumn();

// Fee collection, last 6 months
$feeLabels = []; $feeData = [];
for ($i = 5; $i >= 0; $i--) {
    $d = new DateTime("first day of -$i months");
    $feeLabels[] = $d->format('M Y');
    $st = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM fee_payments WHERE YEAR(created_at)=? AND MONTH(created_at)=?');
    $st->execute([(int)$d->format('Y'), (int)$d->format('n')]);
    $feeData[] = (float)$st->fetchColumn();
}

// Attendance %, last 14 days
$attLabels = []; $attData = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $attLabels[] = date('d M', strtotime($d));
    $st = $pdo->prepare('SELECT COUNT(*), COALESCE(SUM(status="P"),0) FROM student_attendance WHERE `date`=?');
    $st->execute([$d]);
    [$t, $p] = array_map('intval', $st->fetch(PDO::FETCH_NUM));
    $attData[] = $t > 0 ? round($p / $t * 100, 1) : null;
}

$notices = $pdo->query('SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5')->fetchAll();

// Subscription status
$activeSub = $pdo->query("SELECT o.*, p.name AS pkg_name FROM package_orders o
    JOIN packages p ON p.id = o.package_id
    WHERE o.status='approved' AND o.ends_at >= CURDATE() ORDER BY o.ends_at DESC LIMIT 1")->fetch();
$pendingSubs = (int)$pdo->query("SELECT COUNT(*) FROM package_orders WHERE status IN ('pending','submitted')")->fetchColumn();

layout_top('Dashboard', 'dashboard');
?>
<div class="welcome">
  <div class="welcome-text">
    <h2>Welcome back, <?= e(current_user()['username'] ?? 'Admin') ?></h2>
    <p><?= e(date('l, d F Y')) ?> — here is what is happening at your school today.</p>
  </div>
  <div class="welcome-actions">
    <a class="btn white small" href="<?= e(app_url('admin/students.php')) ?>">＋ Add Student</a>
    <a class="btn ghost small" href="<?= e(app_url('admin/fees.php')) ?>"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="vertical-align:-2px"><rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.6"/><path d="M6.5 9.5h.01M17.5 14.5h.01"/></svg> Collect Fee</a>
    <a class="btn ghost small" href="<?= e(app_url('admin/gate.php')) ?>"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><path d="M4 8V6a2 2 0 0 1 2-2h2M16 4h2a2 2 0 0 1 2 2v2M20 16v2a2 2 0 0 1-2 2h-2M8 20H6a2 2 0 0 1-2-2v-2"/><path d="M4 12h16"/></svg> Gate Scan</a>
  </div>
</div>
<?php if ($pendingSubs > 0): ?>
<div class="alert alert-error" style="margin-bottom:14px">💳 <strong><?= $pendingSubs ?> subscription order<?= $pendingSubs>1?'s':'' ?></strong>
  awaiting review — <a href="<?= e(app_url('admin/subscriptions.php')) ?>">review now</a></div>
<?php endif; ?>
<?php if ($activeSub): ?>
<div class="card" style="margin-bottom:14px;border-left:4px solid var(--green,#16a34a)">
  <div class="card-body" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
    <div style="font-size:26px">✅</div>
    <div><strong>Subscription: <?= e($activeSub['pkg_name']) ?></strong>
      <div style="color:var(--muted);font-size:13px">Valid till <?= e(fmt_date($activeSub['ends_at'],'d M Y')) ?>
      (<?= max(0,(int)((strtotime($activeSub['ends_at'])-time())/86400)) ?> days left)</div></div>
    <a class="btn small secondary" style="margin-left:auto" href="<?= e(app_url('admin/subscriptions.php')) ?>">Manage</a>
  </div>
</div>
<?php endif; ?>
<div class="grid grid-4" style="margin-bottom:16px">
  <div class="stat primary"><div class="stat-num"><?= $totalStudents ?></div><div class="stat-label">Active Students</div></div>
  <div class="stat green"><div class="stat-num"><?= $totalTeachers ?></div><div class="stat-label">Active Teachers</div></div>
  <div class="stat amber"><div class="stat-num"><?= e(fmt_money($feesThisMonth)) ?></div><div class="stat-label">Fees Collected — <?= e(month_name($month)) ?></div></div>
  <div class="stat"><div class="stat-num"><?= e((string)$attPct) ?>%</div><div class="stat-label">Attendance Today (<?= $attPresent ?>/<?= $attTotal ?>)</div></div>
  <div class="stat red"><div class="stat-num"><?= $defaulters ?></div><div class="stat-label">Fee Defaulters (this month)</div></div>
</div>

<div class="chart-grid" style="margin-bottom:16px">
  <div class="card"><div class="card-head"><h3>Fee Collection — Last 6 Months</h3></div>
    <div class="card-body"><div class="chart-box"><canvas id="feeChart"></canvas></div></div></div>
  <div class="card"><div class="card-head"><h3>Attendance % — Last 14 Days</h3></div>
    <div class="card-body"><div class="chart-box"><canvas id="attChart"></canvas></div></div></div>
</div>

<div class="card">
  <div class="card-head"><h3>Recent Notices</h3><a class="btn small secondary" href="<?= e(app_url('admin/announcements.php')) ?>">Manage</a></div>
  <div class="card-body">
    <?php if (!$notices): ?><p class="stat-label">No announcements yet.</p>
    <?php else: ?>
    <div class="table-wrap"><table class="table">
      <tr><th>Title</th><th>Audience</th><th>Date</th></tr>
      <?php foreach ($notices as $n): ?>
      <tr>
        <td><strong><?= e($n['title']) ?></strong><br><span class="stat-label"><?= e(mb_strimwidth($n['body'], 0, 80, '…')) ?></span></td>
        <td><span class="badge blue"><?= e(ucfirst($n['audience'])) ?></span></td>
        <td><?= e(fmt_date($n['created_at'], 'd M Y')) ?></td>
      </tr>
      <?php endforeach; ?>
    </table></div>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
  var fee = document.getElementById('feeChart');
  if (fee && window.Chart) new Chart(fee, {
    type: 'bar',
    data: { labels: <?= json_encode($feeLabels) ?>,
      datasets: [{ label: 'Fees (PKR)', data: <?= json_encode($feeData) ?>,
        backgroundColor: '#1d4ed8', borderRadius: 6 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
  });
  var att = document.getElementById('attChart');
  if (att && window.Chart) new Chart(att, {
    type: 'line',
    data: { labels: <?= json_encode($attLabels) ?>,
      datasets: [{ label: 'Present %', data: <?= json_encode($attData) ?>,
        borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.15)', fill: true, tension: .3, spanGaps: true }] },
    options: { responsive: true, maintainAspectRatio: false, scales: { y: { min: 0, max: 100 } } }
  });
})();
</script>
<?php layout_bottom(); ?>
