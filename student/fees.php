<?php
declare(strict_types=1);

/**
 * Student → My fees: my invoices + payments + receipt links.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';
require_role('student');

$me = my_profile();
$sid = (int) ($me['id'] ?? 0);

$invoices = db()->prepare('SELECT id, `month`, `year`, due_date, total, discount, fine, paid, status
    FROM fee_invoices WHERE student_id = ? ORDER BY `year` DESC, `month` DESC');
$invoices->execute([$sid]);
$invoices = $invoices->fetchAll() ?: [];

$invIds = array_column($invoices, 'id');
$payments = [];
if ($invIds) {
    $in = implode(',', array_fill(0, count($invIds), '?'));
    $st = db()->prepare("SELECT id, invoice_id, amount, method, receipt_no, note, created_at
        FROM fee_payments WHERE invoice_id IN ($in) ORDER BY created_at DESC");
    $st->execute($invIds);
    foreach ($st->fetchAll() as $p) {
        $payments[(int) $p['invoice_id']][] = $p;
    }
}

$statusBadge = ['unpaid' => 'red', 'partial' => 'amber', 'paid' => 'green'];

layout_top('My Fees', 'fees');
?>
<div class="card">
  <div class="card-head"><h3>My invoices</h3></div>
  <?php if (!$invoices): ?>
    <div class="card-body">No invoices yet.</div>
  <?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Month</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($invoices as $inv):
          $bal = (float) $inv['total'] - (float) $inv['paid']; ?>
          <tr>
            <td><strong><?= e(month_name((int) $inv['month'])) ?> <?= (int) $inv['year'] ?></strong><br>
              <small>Due <?= e(fmt_date($inv['due_date'])) ?></small></td>
            <td><?= e(fmt_money($inv['total'])) ?></td>
            <td><?= e(fmt_money($inv['paid'])) ?></td>
            <td><strong><?= e(fmt_money($bal)) ?></strong></td>
            <td><span class="badge <?= $statusBadge[$inv['status']] ?? '' ?>"><?= e(ucfirst($inv['status'])) ?></span></td>
            <td>
              <?php foreach ($payments[(int) $inv['id']] ?? [] as $p): ?>
                <a class="btn small secondary" target="_blank"
                  href="../admin/receipt.php?id=<?= (int) $p['id'] ?>">🧾 <?= e($p['receipt_no']) ?></a>
              <?php endforeach; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>

<?php layout_bottom(); ?>
