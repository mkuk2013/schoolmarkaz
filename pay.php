<?php
declare(strict_types=1);

/**
 * Public payment page for a package order: shows JazzCash / Easypaisa / bank
 * instructions (from settings), accepts transaction ref + screenshot proof.
 */
require_once __DIR__ . '/includes/bootstrap.php';

$order_no = trim($_GET['o'] ?? '');
$st = db()->prepare("SELECT o.*, p.name AS pkg_name FROM package_orders o
    JOIN packages p ON p.id = o.package_id WHERE o.order_no = ? LIMIT 1");
$st->execute([$order_no]);
$order = $st->fetch();
if (!$order) {
    http_response_code(404);
    die('Order not found.');
}

$errors = [];
$done = $order['status'] === 'submitted' || $order['status'] === 'approved';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$done) {
    verify_csrf();
    $method = trim($_POST['payment_method'] ?? '');
    $txn = trim($_POST['txn_ref'] ?? '');
    if (!in_array($method, ['JazzCash', 'Easypaisa', 'Bank Transfer'], true)) {
        $errors[] = 'Please select how you paid.';
    }
    if ($txn === '') $errors[] = 'Transaction reference / TID is required.';

    $proof = '';
    $f = $_FILES['proof'] ?? null;
    if ($f && $f['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($f['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Proof upload failed.';
        } elseif ($f['size'] > 3 * 1024 * 1024) {
            $errors[] = 'Screenshot must be 3 MB or smaller.';
        } else {
            $info = @getimagesize($f['tmp_name']);
            $mime = is_array($info) ? ($info['mime'] ?? '') : '';
            $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($map[$mime])) {
                $errors[] = 'Proof must be a JPG, PNG or WebP image.';
            } else {
                $dir = __DIR__ . '/uploads/proofs';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $proof = 'proof_' . bin2hex(random_bytes(8)) . '.' . $map[$mime];
                if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $proof)) {
                    $errors[] = 'Could not save the screenshot.';
                    $proof = '';
                } else {
                    $proof = 'uploads/proofs/' . $proof;
                }
            }
        }
    }

    if (!$errors) {
        db()->prepare("UPDATE package_orders SET status='submitted', payment_method=?, txn_ref=?, proof_path=? WHERE id=?")
            ->execute([$method, $txn, $proof, (int)$order['id']]);
        flash('success', 'Payment proof submitted. We will verify and activate your subscription shortly.');
        redirect('pay.php?o=' . urlencode($order_no));
    }
}

$pay = [
    'JazzCash' => ['no' => setting('pay_jazzcash_no'), 'title' => setting('pay_jazzcash_title')],
    'Easypaisa' => ['no' => setting('pay_easypaisa_no'), 'title' => setting('pay_easypaisa_title')],
    'Bank' => [
        'bank' => setting('pay_bank_name'),
        'title' => setting('pay_bank_title'),
        'iban' => setting('pay_bank_iban'),
    ],
];
$hasAny = $pay['JazzCash']['no'] || $pay['Easypaisa']['no'] || $pay['Bank']['iban'];
$cycleLabel = $order['billing_cycle'] === 'yearly' ? 'Yearly' : 'Monthly';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment — <?= e(APP_NAME) ?></title>
<link rel="icon" type="image/png" href="<?= e(app_url('assets/icons/logo.png')) ?>">
<link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>?v=5">
<script>try{if(localStorage.getItem('sm-theme')==='dark'){document.documentElement.dataset.theme='dark';}}catch(e){}</script>
<style>
.wrap { max-width: 680px; margin: 0 auto; padding: 24px 18px 40px; }
.brandline { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 18px; margin-bottom: 20px; }
.brandline img { width: 34px; height: 34px; }
.paybox { border: 1px solid var(--border); border-radius: 12px; padding: 14px; margin-bottom: 10px; }
.paybox h4 { margin: 0 0 8px; }
.kv { display: flex; justify-content: space-between; font-size: 14px; padding: 3px 0; }
.kv b { font-weight: 700; }
.status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 700; }
.st-pending { background: #fef3c7; color: #92400e; }
.st-submitted { background: #dbeafe; color: #1e40af; }
.st-approved { background: #d1fae5; color: #065f46; }
.st-rejected { background: #fee2e2; color: #991b1b; }
</style>
</head>
<body>
<div class="wrap">
  <a class="brandline" href="<?= e(app_url('index.php')) ?>">
    <img src="<?= e(app_url('assets/icons/logo.png')) ?>" alt="SM logo"> <?= e(APP_NAME) ?>
  </a>

  <h2 style="margin:0 0 6px">Complete Your Payment</h2>
  <p style="color:var(--muted);margin:0 0 16px">Order <strong><?= e($order['order_no']) ?></strong>
    <span class="status st-<?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span></p>

  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
  <?php foreach (get_flashes() as $fl): ?><div class="alert alert-<?= e($fl['type']) ?>"><?= e($fl['message']) ?></div><?php endforeach; ?>

  <div class="card" style="margin-bottom:14px"><div class="card-body">
    <div class="kv"><span>Package</span><b><?= e($order['pkg_name']) ?> (<?= e($cycleLabel) ?>)</b></div>
    <div class="kv"><span>School</span><b><?= e($order['school_name']) ?></b></div>
    <div class="kv"><span>Amount payable</span><b style="font-size:18px"><?= e(fmt_money($order['amount'])) ?></b></div>
  </div></div>

  <?php if ($order['status'] === 'approved'): ?>
    <div class="alert alert-success">🎉 Your subscription is <strong>active</strong>
      (<?= e($order['starts_at']) ?> → <?= e($order['ends_at']) ?>). Welcome aboard!</div>
  <?php elseif ($order['status'] === 'rejected'): ?>
    <div class="alert alert-error">This order was not approved.<?= $order['note'] ? ' Reason: ' . e($order['note']) : '' ?></div>
  <?php elseif ($order['status'] === 'submitted'): ?>
    <div class="alert alert-success">✅ Payment proof received. Our team will verify it and activate your subscription soon.</div>
  <?php else: ?>
    <h3>Step 1 — Send <?= e(fmt_money($order['amount'])) ?> to any of these:</h3>
    <?php if (!$hasAny): ?>
      <div class="alert alert-error">Payment details are not configured yet. Please contact us on <?= e(setting('contact_phone', school_profile()['phone'] ?? '')) ?>.</div>
    <?php endif; ?>
    <?php if ($pay['JazzCash']['no']): ?>
    <div class="paybox"><h4>📱 JazzCash</h4>
      <div class="kv"><span>Account No.</span><b><?= e($pay['JazzCash']['no']) ?></b></div>
      <?php if ($pay['JazzCash']['title']): ?><div class="kv"><span>Title</span><b><?= e($pay['JazzCash']['title']) ?></b></div><?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if ($pay['Easypaisa']['no']): ?>
    <div class="paybox"><h4>📱 Easypaisa</h4>
      <div class="kv"><span>Account No.</span><b><?= e($pay['Easypaisa']['no']) ?></b></div>
      <?php if ($pay['Easypaisa']['title']): ?><div class="kv"><span>Title</span><b><?= e($pay['Easypaisa']['title']) ?></b></div><?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if ($pay['Bank']['iban']): ?>
    <div class="paybox"><h4>🏦 Bank Transfer</h4>
      <?php if ($pay['Bank']['bank']): ?><div class="kv"><span>Bank</span><b><?= e($pay['Bank']['bank']) ?></b></div><?php endif; ?>
      <?php if ($pay['Bank']['title']): ?><div class="kv"><span>Title</span><b><?= e($pay['Bank']['title']) ?></b></div><?php endif; ?>
      <div class="kv"><span>IBAN</span><b><?= e($pay['Bank']['iban']) ?></b></div>
    </div>
    <?php endif; ?>

    <h3>Step 2 — Submit your payment proof:</h3>
    <div class="card"><div class="card-body">
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="field"><label>How did you pay? *</label>
          <select name="payment_method" required>
            <option value="">— Select —</option>
            <option>JazzCash</option>
            <option>Easypaisa</option>
            <option>Bank Transfer</option>
          </select></div>
        <div class="field"><label>Transaction Ref / TID *</label>
          <input type="text" name="txn_ref" required placeholder="e.g. 30981234567"></div>
        <div class="field"><label>Screenshot (JPG/PNG, max 3 MB)</label>
          <input type="file" name="proof" accept="image/jpeg,image/png,image/webp"></div>
        <button class="btn" type="submit" style="width:100%">Submit Payment Proof</button>
      </form>
    </div></div>
  <?php endif; ?>
</div>
</body>
</html>
