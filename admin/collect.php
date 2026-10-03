<?php
declare(strict_types=1);
/**
 * Fees & Finance — Collect a payment against an invoice.
 * GET ?invoice_id — shows detail; POST pays or adjusts fine/discount.
 * navkey: 'fees'
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$pdo = db();

$id = (int) ($_GET['invoice_id'] ?? 0);
$st = $pdo->prepare(
    'SELECT i.*, s.name AS student_name, s.admission_no, s.class_id,
            c.name AS class_name, c.section
     FROM fee_invoices i
     JOIN students s ON s.id = i.student_id
     LEFT JOIN classes c ON c.id = s.class_id
     WHERE i.id = ? LIMIT 1'
);
$st->execute([$id]);
$inv = $st->fetch();
if (!$inv) {
    http_response_code(404);
    die('Invoice not found.');
}

/** @return string */
function inv_status_of(float $paid, float $payable): string
{
    $balance = $payable - $paid;
    if ($balance <= 0.001) {
        return 'paid';
    }
    return $paid > 0 ? 'partial' : 'unpaid';
}

$payable = (float) $inv['total'] + (float) $inv['fine'] - (float) $inv['discount'];
$balance = max(0.0, $payable - (float) $inv['paid']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'adjust') {
        $fine     = max(0.0, (float) ($_POST['fine'] ?? 0));
        $discount = max(0.0, (float) ($_POST['discount'] ?? 0));
        $newPayable = (float) $inv['total'] + $fine - $discount;
        $status = inv_status_of((float) $inv['paid'], $newPayable);
        $pdo->prepare('UPDATE fee_invoices SET fine = ?, discount = ?, status = ? WHERE id = ?')
            ->execute([$fine, $discount, $status, $id]);
        flash('success', 'Fine/discount updated.');
        redirect('admin/collect.php?invoice_id=' . $id);
    }

    if ($action === 'pay') {
        $amount = (float) ($_POST['amount'] ?? 0);
        $method = $_POST['method'] ?? 'Cash';
        $method = in_array($method, ['Cash', 'Bank', 'Online'], true) ? $method : 'Cash';
        $note   = trim($_POST['note'] ?? '');

        if ($amount <= 0) {
            flash('error', 'Amount must be greater than zero.');
            redirect('admin/collect.php?invoice_id=' . $id);
        }
        if ($amount > $balance + 0.001) {
            flash('error', 'Amount cannot exceed the outstanding balance of ' . fmt_money($balance) . '.');
            redirect('admin/collect.php?invoice_id=' . $id);
        }

        $receiptNo = next_receipt_no();
        $newPaid   = (float) $inv['paid'] + $amount;
        $status    = inv_status_of($newPaid, $payable);

        $pdo->beginTransaction();
        try {
            $paySt = $pdo->prepare(
                'INSERT INTO fee_payments (invoice_id, amount, method, received_by, receipt_no, note)
                 VALUES (?,?,?,?,?,?)'
            );
            $paySt->execute([$id, $amount, $method, current_user()['id'] ?? null, $receiptNo, $note]);
            $paymentId = (int) $pdo->lastInsertId();

            $pdo->prepare('UPDATE fee_invoices SET paid = ?, status = ? WHERE id = ?')
                ->execute([$newPaid, $status, $id]);

            $pdo->prepare(
                "INSERT INTO finance_transactions (type, category, amount, `date`, note, ref, created_by)
                 VALUES ('income','Fee Collection',?,?,?, ?,?)"
            )->execute([
                $amount,
                today(),
                'Fee received: ' . $inv['student_name'] . ' (' . month_name((int) $inv['month']) . ' ' . $inv['year'] . ')',
                $receiptNo,
                current_user()['id'] ?? null,
            ]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('error', 'Payment failed: ' . $e->getMessage());
            redirect('admin/collect.php?invoice_id=' . $id);
        }

        redirect('admin/receipt.php?id=' . $paymentId);
    }
}

$items = $pdo->prepare('SELECT label, amount FROM fee_invoice_items WHERE invoice_id = ? ORDER BY id');
$items->execute([$id]);
$items = $items->fetchAll();

$payments = $pdo->prepare(
    'SELECT p.*, u.username AS received_name
     FROM fee_payments p
     LEFT JOIN users u ON u.id = p.received_by
     WHERE p.invoice_id = ? ORDER BY p.id'
);
$payments->execute([$id]);
$payments = $payments->fetchAll();

layout_top('Collect Fee', 'fees');
?>

<div class="card">
  <div class="card-head">
    <h2>Invoice #<?= (int) $inv['id'] ?> — <?= e(month_name((int) $inv['month']) . ' ' . $inv['year']) ?></h2>
    <a class="btn secondary small" href="<?= e(app_url('admin/fees.php')) ?>">Back to Fees</a>
  </div>
  <div class="card-body">
    <div class="grid grid-4">
      <div class="stat"><div class="stat-num" style="font-size:20px"><?= e($inv['student_name']) ?></div><div class="stat-label">Student (<?= e($inv['admission_no']) ?>)</div></div>
      <div class="stat"><div class="stat-num" style="font-size:20px"><?= e(trim(($inv['class_name'] ?? '') . ' ' . ($inv['section'] ?? ''))) ?></div><div class="stat-label">Class</div></div>
      <div class="stat primary"><div class="stat-num"><?= e(fmt_money($payable)) ?></div><div class="stat-label">Payable</div></div>
      <div class="stat amber"><div class="stat-num"><?= e(fmt_money($balance)) ?></div><div class="stat-label">Balance</div></div>
    </div>

    <h3>Invoice Items</h3>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Item</th><th style="text-align:right">Amount</th></tr></thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr><td><?= e($it['label']) ?></td><td style="text-align:right"><?= e(fmt_money($it['amount'])) ?></td></tr>
          <?php endforeach; ?>
          <tr><td><strong>Total</strong></td><td style="text-align:right"><strong><?= e(fmt_money($inv['total'])) ?></strong></td></tr>
          <tr><td>Discount</td><td style="text-align:right"><?= e(fmt_money($inv['discount'])) ?></td></tr>
          <tr><td>Fine</td><td style="text-align:right"><?= e(fmt_money($inv['fine'])) ?></td></tr>
          <tr><td><strong>Paid to date</strong></td><td style="text-align:right"><strong><?= e(fmt_money($inv['paid'])) ?></strong></td></tr>
        </tbody>
      </table>
    </div>

    <h3>Fine / Discount</h3>
    <form method="post" style="margin-bottom:16px">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="adjust">
      <div class="form-row">
        <div class="field"><label>Fine (<?= e(CURRENCY) ?>)</label>
          <input type="number" name="fine" min="0" step="0.01" value="<?= e((string) $inv['fine']) ?>">
        </div>
        <div class="field"><label>Discount (<?= e(CURRENCY) ?>)</label>
          <input type="number" name="discount" min="0" step="0.01" value="<?= e((string) $inv['discount']) ?>">
        </div>
      </div>
      <button class="btn secondary small" type="submit">Update Fine / Discount</button>
    </form>

    <h3>Previous Payments</h3>
    <?php if ($payments): ?>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Receipt</th><th>Date</th><th>Method</th><th style="text-align:right">Amount</th><th>Received by</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($payments as $p): ?>
              <tr>
                <td><?= e($p['receipt_no']) ?></td>
                <td><?= e(fmt_date(substr($p['created_at'], 0, 10))) ?></td>
                <td><?= e($p['method']) ?></td>
                <td style="text-align:right"><?= e(fmt_money($p['amount'])) ?></td>
                <td><?= e($p['received_name'] ?? '—') ?></td>
                <td><a class="btn secondary small" target="_blank" href="<?= e(app_url('admin/receipt.php')) ?>?id=<?= (int) $p['id'] ?>">Receipt</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p>No payments recorded yet.</p>
    <?php endif; ?>

    <?php if ($balance > 0): ?>
      <h3>Collect Payment</h3>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="pay">
        <div class="form-row">
          <div class="field">
            <label>Amount (<?= e(CURRENCY) ?>) — max <?= e(fmt_money($balance)) ?></label>
            <input type="number" name="amount" required min="0.01" step="0.01" max="<?= e((string) $balance) ?>" value="<?= e((string) $balance) ?>">
          </div>
          <div class="field">
            <label>Method</label>
            <select name="method">
              <option>Cash</option>
              <option>Bank</option>
              <option>Online</option>
            </select>
          </div>
        </div>
        <div class="field">
          <label>Note (optional)</label>
          <input type="text" name="note" maxlength="255">
        </div>
        <button class="btn success" type="submit">Collect Payment</button>
      </form>
    <?php else: ?>
      <div class="alert alert-success">This invoice is fully paid.</div>
    <?php endif; ?>
  </div>
</div>

<?php layout_bottom(); ?>
