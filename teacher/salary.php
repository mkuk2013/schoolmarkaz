<?php
declare(strict_types=1);

/**
 * Teacher → Salary slips: list of my salary_payments; each slip printable.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';
require_role('teacher');

$me = my_profile();
$tid = (int) ($me['id'] ?? 0);
$slip_id = (int) ($_GET['slip'] ?? 0);

$st = db()->prepare('SELECT * FROM salary_payments WHERE teacher_id = ? ORDER BY `year` DESC, `month` DESC');
$st->execute([$tid]);
$slips = $st->fetchAll() ?: [];

$slip = null;
if ($slip_id > 0) {
    $st = db()->prepare('SELECT * FROM salary_payments WHERE id = ? AND teacher_id = ? LIMIT 1');
    $st->execute([$slip_id, $tid]);
    $slip = $st->fetch() ?: null;
}

layout_top('Salary Slips', 'salary');
?>

<?php if ($slip): ?>
  <div class="card payslip">
    <div class="card-head no-print"><h3>Salary slip</h3><a class="btn small secondary" href="salary.php">← Back</a></div>
    <div class="card-body">
      <div class="slip">
        <h2><?= e((school_profile()['name'] ?? APP_NAME)) ?></h2>
        <div class="slip-sub">Salary Slip — <?= e(month_name((int) $slip['month'])) ?> <?= (int) $slip['year'] ?></div>
        <table>
          <tr><th>Staff</th><td><?= e($me['name'] ?? '') ?> (<?= e($me['staff_no'] ?? '') ?>)</td></tr>
          <tr><th>Period</th><td><?= e(month_name((int) $slip['month'])) ?> <?= (int) $slip['year'] ?></td></tr>
          <tr><th>Paid on</th><td><?= e(fmt_date($slip['paid_date'])) ?></td></tr>
          <tr class="total-row"><td>Net Pay</td><td><?= e(fmt_money($slip['amount'])) ?></td></tr>
          <?php if ($slip['note']): ?><tr><th>Note</th><td><?= e($slip['note']) ?></td></tr><?php endif; ?>
        </table>
      </div>
      <p class="no-print" style="text-align:center;margin-top:16px">
        <button class="btn" onclick="window.print()">🖨 Print slip</button>
      </p>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <div class="card-head"><h3>My salary payments</h3></div>
    <?php if (!$slips): ?>
      <div class="card-body">No salary payments recorded yet.</div>
    <?php else: ?>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Month</th><th>Year</th><th>Amount</th><th>Paid date</th><th>Note</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($slips as $s): ?>
            <tr>
              <td><?= e(month_name((int) $s['month'])) ?></td>
              <td><?= (int) $s['year'] ?></td>
              <td><strong><?= e(fmt_money($s['amount'])) ?></strong></td>
              <td><?= e(fmt_date($s['paid_date'])) ?></td>
              <td><?= e($s['note'] ?: '—') ?></td>
              <td><a class="btn small" href="salary.php?slip=<?= (int) $s['id'] ?>">View slip</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php layout_bottom(); ?>
