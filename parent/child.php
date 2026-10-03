<?php
declare(strict_types=1);

/**
 * Parent → Child detail (?id=): verifies the child belongs to this parent.
 * Tabs: attendance month grid, invoices + receipts, results.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';
require_role('parent');

$me = my_profile();
$pid = (int) ($me['id'] ?? 0);
$child_id = (int) ($_GET['id'] ?? 0);
$child = $child_id > 0 ? sm_verify_child($child_id, $pid) : null;

if (!$child) {
    http_response_code(403);
    layout_top('Child', 'dashboard');
    echo '<div class="card"><div class="card-body"><div class="alert alert-error">Access denied: this student is not linked to your account.</div><a class="btn secondary" href="index.php">← Back to children</a></div></div>';
    layout_bottom();
    exit;
}

$tab = $_GET['tab'] ?? 'attendance';
if (!in_array($tab, ['attendance', 'fees', 'results'], true)) {
    $tab = 'attendance';
}
$cls = sm_class((int) $child['class_id']);

layout_top($child['name'], 'dashboard');
?>
<p><a href="index.php">← My children</a></p>

<div class="card">
  <div class="card-head">
    <h3><?= e($child['name']) ?> <span class="badge"><?= e($cls ? $cls['name'] . ' (' . $cls['section'] . ')' : '—') ?></span></h3>
  </div>
  <div class="card-body">
    <p style="margin-top:0">
      <a class="btn small <?= $tab === 'attendance' ? '' : 'secondary' ?>" href="child.php?id=<?= $child_id ?>&tab=attendance">Attendance</a>
      <a class="btn small <?= $tab === 'fees' ? '' : 'secondary' ?>" href="child.php?id=<?= $child_id ?>&tab=fees">Fees</a>
      <a class="btn small <?= $tab === 'results' ? '' : 'secondary' ?>" href="child.php?id=<?= $child_id ?>&tab=results">Results</a>
    </p>

    <?php if ($tab === 'attendance'): ?>
      <?php
      $month = (int) ($_GET['month'] ?? date('n'));
      $year = (int) ($_GET['year'] ?? date('Y'));
      $month = max(1, min(12, $month));
      $year = max(2000, min(2100, $year));
      $st = db()->prepare('SELECT `date`, status FROM student_attendance
          WHERE student_id = ? AND MONTH(`date`) = ? AND YEAR(`date`) = ?');
      $st->execute([$child_id, $month, $year]);
      $byDay = [];
      foreach ($st->fetchAll() as $r) {
          $byDay[(int) date('j', strtotime($r['date']))] = $r['status'];
      }
      $summary = sm_attendance_month($child_id, $month, $year);
      $daysInMonth = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
      $colors = ['P' => '#16a34a', 'A' => '#dc2626', 'L' => '#d97706', 'H' => '#64748b'];
      ?>
      <form method="get" class="form-row" style="grid-template-columns:1fr 1fr auto;margin-bottom:14px">
        <input type="hidden" name="id" value="<?= $child_id ?>">
        <input type="hidden" name="tab" value="attendance">
        <div class="field" style="margin:0">
          <label>Month</label>
          <select name="month">
            <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $m ?>"<?= $m === $month ? ' selected' : '' ?>><?= e(month_name($m)) ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="field" style="margin:0"><label>Year</label>
          <input type="number" name="year" min="2000" max="2100" value="<?= $year ?>"></div>
        <div class="field" style="margin:0;align-self:end"><button class="btn small" type="submit">Show</button></div>
      </form>
      <p>Present <strong><?= $summary['present'] ?></strong> · Absent <strong><?= $summary['absent'] ?></strong> ·
         Leave <strong><?= $summary['leave'] ?></strong> ·
         Month % <strong><?= $summary['pct'] === null ? '—' : $summary['pct'] . '%' ?></strong></p>
      <div class="grid" style="grid-template-columns:repeat(7,1fr);gap:8px">
        <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?>
          <div style="text-align:center;font-size:12px;color:var(--muted);font-weight:600"><?= $d ?></div>
        <?php endforeach; ?>
        <?php
        $firstDow = (int) date('N', mktime(0, 0, 0, $month, 1, $year));
        for ($i = 1; $i < $firstDow; $i++) echo '<div></div>';
        for ($d = 1; $d <= $daysInMonth; $d++):
          $s2 = $byDay[$d] ?? null; ?>
          <div style="text-align:center;padding:10px 4px;border-radius:8px;border:1px solid var(--border);
               <?= $s2 ? 'background:' . $colors[$s2] . ';color:#fff;font-weight:700;border-color:transparent' : 'color:var(--muted)' ?>"><?= $d ?></div>
        <?php endfor; ?>
      </div>

    <?php elseif ($tab === 'fees'): ?>
      <?php
      $inv = db()->prepare('SELECT id, `month`, `year`, total, paid, status FROM fee_invoices
          WHERE student_id = ? ORDER BY `year` DESC, `month` DESC');
      $inv->execute([$child_id]);
      $invoices = $inv->fetchAll() ?: [];
      ?>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Month</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th>Receipts</th></tr></thead>
        <tbody>
          <?php foreach ($invoices as $i):
            $pay = db()->prepare('SELECT id, receipt_no FROM fee_payments WHERE invoice_id = ? ORDER BY id DESC');
            $pay->execute([(int) $i['id']]);
            $receipts = $pay->fetchAll() ?: [];
          ?>
            <tr>
              <td><?= e(month_name((int) $i['month'])) ?> <?= (int) $i['year'] ?></td>
              <td><?= e(fmt_money($i['total'])) ?></td>
              <td><?= e(fmt_money($i['paid'])) ?></td>
              <td><strong><?= e(fmt_money((float) $i['total'] - (float) $i['paid'])) ?></strong></td>
              <td><span class="badge <?= ['unpaid' => 'red', 'partial' => 'amber', 'paid' => 'green'][$i['status']] ?? '' ?>"><?= e(ucfirst($i['status'])) ?></span></td>
              <td><?php foreach ($receipts as $rc): ?>
                <a class="btn small secondary" target="_blank" href="../admin/receipt.php?id=<?= (int) $rc['id'] ?>">🧾 <?= e($rc['receipt_no']) ?></a>
              <?php endforeach; ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$invoices): ?><tr><td colspan="6">No invoices.</td></tr><?php endif; ?>
        </tbody>
      </table></div>

    <?php else: /* results */ ?>
      <?php
      $st = db()->prepare('SELECT x.id, x.name, x.term, x.`year`, SUM(m.obtained) o, SUM(m.total) t
          FROM marks m JOIN exams x ON x.id = m.exam_id
          WHERE m.student_id = ? GROUP BY x.id ORDER BY x.`year` DESC, x.id DESC');
      $st->execute([$child_id]);
      $results = $st->fetchAll() ?: [];
      ?>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Exam</th><th>Term</th><th>Year</th><th>Obtained / Total</th><th>%</th><th>Grade</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($results as $r):
            $pct = (float) $r['t'] > 0 ? round(100 * (float) $r['o'] / (float) $r['t'], 1) : null; ?>
            <tr>
              <td><strong><?= e($r['name']) ?></strong></td>
              <td><?= e($r['term'] ?: '—') ?></td>
              <td><?= (int) $r['year'] ?></td>
              <td><?= e(number_format((float) $r['o'], 1)) ?> / <?= e(number_format((float) $r['t'], 1)) ?></td>
              <td><strong><?= $pct === null ? '—' : $pct . '%' ?></strong></td>
              <td><?= sm_grade($pct) ?></td>
              <td><a class="btn small" target="_blank"
                href="../admin/reportcard.php?exam_id=<?= (int) $r['id'] ?>&student_id=<?= $child_id ?>">📄 Report card</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$results): ?><tr><td colspan="7">No results yet.</td></tr><?php endif; ?>
        </tbody>
      </table></div>
    <?php endif; ?>
  </div>
</div>

<?php layout_bottom(); ?>
