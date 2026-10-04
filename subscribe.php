<?php
declare(strict_types=1);

/**
 * Public package subscription: pick a package + billing cycle, enter details,
 * creates a pending package_orders row, then redirects to pay.php.
 */
require_once __DIR__ . '/includes/bootstrap.php';

$packages = db()->query('SELECT * FROM packages ORDER BY price_monthly ASC')->fetchAll();

$sel_pkg = null;
$pid = (int)($_GET['package_id'] ?? $_POST['package_id'] ?? 0);
if ($pid > 0) {
    foreach ($packages as $p) {
        if ((int)$p['id'] === $pid) { $sel_pkg = $p; break; }
    }
}

$errors = [];
$old = [
    'package_id' => $sel_pkg ? (string)$sel_pkg['id'] : '',
    'cycle' => 'monthly',
    'school_name' => '', 'contact_name' => '', 'phone' => '', 'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($old as $k => $v) { $old[$k] = trim($_POST[$k] ?? ''); }
    if (!in_array($old['cycle'], ['monthly', 'yearly'], true)) $old['cycle'] = 'monthly';

    $sel_pkg = null;
    foreach ($packages as $p) {
        if ((string)$p['id'] === $old['package_id']) { $sel_pkg = $p; break; }
    }
    if (!$sel_pkg) $errors[] = 'Please choose a package.';
    if ($old['school_name'] === '') $errors[] = 'School name is required.';
    if ($old['contact_name'] === '') $errors[] = 'Contact person name is required.';
    if ($old['phone'] === '') $errors[] = 'Phone number is required.';
    if ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email address is not valid.';
    }

    if (!$errors && $sel_pkg) {
        $monthly = (float)$sel_pkg['price_monthly'];
        $note = null;
        if (!empty($sel_pkg['per_student'])) {
            $count = max(1, min(100000, (int)($_POST['student_count'] ?? 100)));
            $monthly = $monthly * $count;
            $note = $sel_pkg['name'] . ' plan: ' . $count . ' students x ' . fmt_money((float)$sel_pkg['price_monthly']) . ' per student';
        }
        $price = package_price($monthly, $old['cycle']);
        $order_no = next_order_no();
        db()->prepare("INSERT INTO package_orders
            (order_no, package_id, billing_cycle, amount, school_name, contact_name, phone, email, status, note)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)")
            ->execute([
                $order_no, (int)$sel_pkg['id'], $old['cycle'], $price['amount'],
                $old['school_name'], $old['contact_name'], $old['phone'], $old['email'], $note,
            ]);
        redirect('pay.php?o=' . urlencode($order_no));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Subscribe — <?= e(APP_NAME) ?></title>
<link rel="icon" type="image/png" href="<?= e(app_url('assets/icons/logo.png')) ?>">
<link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>">
<script>try{if(localStorage.getItem('sm-theme')==='dark'){document.documentElement.dataset.theme='dark';}}catch(e){}</script>
<style>
.wrap { max-width: 720px; margin: 0 auto; padding: 24px 18px 40px; }
.brandline { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 18px; margin-bottom: 20px; }
.brandline img { width: 34px; height: 34px; }
.pkgpick { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 14px; }
.pkg { border: 2px solid var(--border); border-radius: 12px; padding: 14px; cursor: pointer; }
.pkg.sel { border-color: var(--primary); background: color-mix(in srgb, var(--primary) 8%, transparent); }
.pkg .pname { font-weight: 800; }
.pkg .pamount { font-size: 20px; font-weight: 800; margin: 4px 0; }
.cycles { display: flex; gap: 10px; margin-bottom: 14px; }
.cycle { flex: 1; border: 2px solid var(--border); border-radius: 12px; padding: 12px; cursor: pointer; text-align: center; }
.cycle.sel { border-color: var(--primary); background: color-mix(in srgb, var(--primary) 8%, transparent); }
.total { font-size: 22px; font-weight: 800; margin: 12px 0; }
</style>
</head>
<body>
<div class="wrap">
  <a class="brandline" href="<?= e(app_url('index.php')) ?>">
    <img src="<?= e(app_url('assets/icons/logo.png')) ?>" alt="SM logo"> <?= e(APP_NAME) ?>
  </a>

  <h2 style="margin:0 0 6px">Subscribe to a Package</h2>
  <p style="color:var(--muted);margin:0 0 18px">Choose your plan, then pay via JazzCash, Easypaisa or bank transfer. Your subscription activates after payment verification.</p>

  <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>

  <form method="post" id="subform">
    <?= csrf_field() ?>
    <div class="card" style="margin-bottom:14px"><div class="card-body">
      <h3 style="margin:0 0 10px">1. Choose package</h3>
      <div class="pkgpick">
        <?php foreach ($packages as $p): ?>
        <div class="pkg<?= $sel_pkg && (int)$sel_pkg['id'] === (int)$p['id'] ? ' sel' : '' ?>" data-id="<?= (int)$p['id'] ?>">
          <div class="pname"><?= e($p['name']) ?></div>
          <?php if (!empty($p['per_student'])): ?>
            <div class="pamount"><?= e(fmt_money($p['price_monthly'])) ?><small style="font-size:12px;font-weight:400">/student/mo</small></div>
            <div style="font-size:12px;color:var(--muted)">Pay only for your students</div>
          <?php elseif ((float)$p['price_monthly'] <= 0): ?>
            <div class="pamount">Free</div>
            <div style="font-size:12px;color:var(--muted)">Up to <?= e(number_format((int)$p['max_students'])) ?> students</div>
          <?php else: ?>
            <div class="pamount"><?= e(fmt_money($p['price_monthly'])) ?><small style="font-size:12px;font-weight:400">/mo</small></div>
            <div style="font-size:12px;color:var(--muted)">Up to <?= e(number_format((int)$p['max_students'])) ?> students</div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <input type="hidden" name="package_id" id="package_id" value="<?= e($old['package_id']) ?>">

      <div id="countRow" style="display:none;margin:14px 0 4px">
        <div class="field"><label>Number of students</label>
          <input type="number" name="student_count" id="student_count" value="100" min="1" max="100000" style="max-width:220px">
          <div style="font-size:12px;color:var(--muted);margin-top:4px">Pro plan: per-student rate × your students = monthly price.</div></div>
      </div>

      <h3 style="margin:14px 0 10px">2. Billing cycle</h3>
      <div class="cycles">
        <div class="cycle<?= $old['cycle'] === 'monthly' ? ' sel' : '' ?>" data-cycle="monthly">
          <strong>Monthly</strong><div style="font-size:12px;color:var(--muted)" id="mprice"></div>
        </div>
        <div class="cycle<?= $old['cycle'] === 'yearly' ? ' sel' : '' ?>" data-cycle="yearly">
          <strong>Yearly</strong><div style="font-size:12px;color:var(--muted)">2 months free</div>
          <div style="font-size:12px;color:var(--muted)" id="yprice"></div>
        </div>
      </div>
      <input type="hidden" name="cycle" id="cycle" value="<?= e($old['cycle']) ?>">
      <div class="total">Total payable: <span id="total">—</span></div>
    </div></div>

    <div class="card"><div class="card-body">
      <h3 style="margin:0 0 10px">3. Your details</h3>
      <div class="field"><label>School Name *</label>
        <input type="text" name="school_name" value="<?= e($old['school_name']) ?>" required></div>
      <div class="form-row">
        <div class="field"><label>Contact Person *</label>
          <input type="text" name="contact_name" value="<?= e($old['contact_name']) ?>" required></div>
        <div class="field"><label>Phone *</label>
          <input type="text" name="phone" value="<?= e($old['phone']) ?>" required placeholder="03xx-xxxxxxx"></div>
      </div>
      <div class="field"><label>Email (optional)</label>
        <input type="email" name="email" value="<?= e($old['email']) ?>"></div>
      <button class="btn" type="submit" style="width:100%">Continue to Payment →</button>
    </div></div>
  </form>
</div>
<script>
const prices = <?= json_encode(array_column($packages, 'price_monthly', 'id')) ?>;
<?php $perMap = []; foreach ($packages as $p) { $perMap[(int)$p['id']] = !empty($p['per_student']) ? 1 : 0; } ?>
const pers = <?= json_encode($perMap) ?>;
const pkgInput = document.getElementById('package_id');
const cycInput = document.getElementById('cycle');
const countInput = document.getElementById('student_count');
function fmt(n){ return 'PKR ' + Number(n).toLocaleString('en-PK'); }
function refresh(){
  document.querySelectorAll('.pkg').forEach(el=>el.classList.toggle('sel', el.dataset.id === pkgInput.value));
  document.querySelectorAll('.cycle').forEach(el=>el.classList.toggle('sel', el.dataset.cycle === cycInput.value));
  const base = parseFloat(prices[pkgInput.value] || 0);
  const isPer = (pers[pkgInput.value] || 0) === 1;
  document.getElementById('countRow').style.display = isPer ? '' : 'none';
  const cnt = isPer ? Math.max(1, parseInt(countInput.value || '0', 10) || 0) : 1;
  const m = base * cnt;
  document.getElementById('mprice').textContent = m ? fmt(m) + '/month' : (pkgInput.value ? 'Free' : '');
  document.getElementById('yprice').textContent = m ? fmt(m*10) + '/year' : '';
  const total = cycInput.value === 'yearly' ? m*10 : m;
  document.getElementById('total').textContent = m ? fmt(total) : (pkgInput.value ? 'Free' : '—');
}
document.querySelectorAll('.pkg').forEach(el=>el.addEventListener('click',()=>{pkgInput.value=el.dataset.id;refresh();}));
document.querySelectorAll('.cycle').forEach(el=>el.addEventListener('click',()=>{cycInput.value=el.dataset.cycle;refresh();}));
countInput.addEventListener('input', refresh);
refresh();
</script>
</body>
</html>
