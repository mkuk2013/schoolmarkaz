<?php
declare(strict_types=1);

/**
 * Parent → Fee receipts: all unpaid invoices across children + receipt history.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';
require_role('parent');

$children = sm_my_children();
$childIds = array_column($children, 'id');
$byChild = [];
foreach ($children as $c) {
    $byChild[(int) $c['id']] = $c;
}

$unpaid = [];
$history = [];
if ($childIds) {
    $in = implode(',', array_fill(0, count($childIds), '?'));
    $st = db()->prepare("SELECT id, student_id, `month`, `year`, total, paid, status
        FROM fee_invoices WHERE student_id IN ($in) AND status <> 'paid'
        ORDER BY `year` DESC, `month` DESC");
    $st->execute($childIds);
    $unpaid = $st->fetchAll() ?: [];

    $st = db()->prepare("SELECT p.id, p.receipt_no, p.amount, p.method, p.created_at, i.student_id
        FROM fee_payments p JOIN fee_invoices i ON i.id = p.invoice_id
        WHERE i.student_id IN ($in) ORDER BY p.created_at DESC LIMIT 100");
    $st->execute($childIds);
    $history = $st->fetchAll() ?: [];
}

$statusBadge = ['unpaid' => 'red', 'partial' => 'amber', 'paid' => 'green'];

layout_top('Fee Receipts', 'fees');
?>
<div class="card">
  <div class="card-head"><h3>Unpaid invoices — all children</h3></div>
  <?php if (!$unpaid): ?>
    <div class="card-body">All fees are paid. 🎉</div>
  <?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Child</th><th>Month</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($unpaid as $u): ?>
          <tr>
            <td><a href="child.php?id=<?= (int) $u['student_id'] ?>"><?= e($byChild[(int) $u['student_id']]['name'] ?? '') ?></a></td>
            <td><?= e(month_name((int) $u['month'])) ?> <?= (int) $u['year'] ?></td>
            <td><?= e(fmt_money($u['total'])) ?></td>
            <td><?= e(fmt_money($u['paid'])) ?></td>
            <td><strong><?= e(fmt_money((float) $u['total'] - (float) $u['paid'])) ?></strong></td>
            <td><span class="badge <?= $statusBadge[$u['status']] ?? '' ?>"><?= e(ucfirst($u['status'])) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-head"><h3>Receipt history</h3></div>
  <?php if (!$history): ?>
    <div class="card-body">No receipts yet.</div>
  <?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Receipt</th><th>Child</th><th>Amount</th><th>Method</th><th>Date</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($history as $h): ?>
          <tr>
            <td><?= e($h['receipt_no']) ?></td>
            <td><?= e($byChild[(int) $h['student_id']]['name'] ?? '') ?></td>
            <td><?= e(fmt_money($h['amount'])) ?></td>
            <td><?= e($h['method']) ?></td>
            <td><?= e(fmt_date($h['created_at'], 'd M Y')) ?></td>
            <td><a class="btn small secondary" target="_blank"
              href="../admin/receipt.php?id=<?= (int) $h['id'] ?>">🧾 View</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>

<?php layout_bottom(); ?>
