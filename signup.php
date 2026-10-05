<?php
declare(strict_types=1);

/**
 * Public school registration. Only available when no user exists yet.
 */
require_once __DIR__ . '/includes/bootstrap.php';

// First-run setup only: once an account exists, registration is closed.
$count = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($count > 0) {
    flash('info', 'School already registered. Please log in.');
    redirect('auth/login.php');
}

$packages = db()->query('SELECT * FROM packages ORDER BY price_monthly ASC')->fetchAll();

$errors = [];
$old = [
    'school_name' => '', 'address' => '', 'phone' => '', 'email' => '',
    'package_id' => '', 'username' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $old['school_name'] = trim($_POST['school_name'] ?? '');
    $old['address']     = trim($_POST['address'] ?? '');
    $old['phone']       = trim($_POST['phone'] ?? '');
    $old['email']       = trim($_POST['email'] ?? '');
    $old['package_id']  = trim($_POST['package_id'] ?? '');
    $old['username']    = trim($_POST['username'] ?? '');
    $password          = (string) ($_POST['password'] ?? '');
    $passwordConfirm   = (string) ($_POST['password_confirm'] ?? '');

    if ($old['school_name'] === '') $errors[] = 'School name is required.';
    if ($old['username'] === '')    $errors[] = 'Admin username is required.';
    if (strlen($password) < 6)     $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $passwordConfirm) $errors[] = 'Passwords do not match.';
    if ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email address is not valid.';
    }

    $packageId = null;
    if ($old['package_id'] !== '') {
        $packageId = (int) $old['package_id'];
        $pk = db()->prepare('SELECT id FROM packages WHERE id = ? LIMIT 1');
        $pk->execute([$packageId]);
        if (!$pk->fetch()) {
            $errors[] = 'Selected package is invalid.';
            $packageId = null;
        }
    }

    // Username must be unique.
    if ($old['username'] !== '') {
        $u = db()->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $u->execute([$old['username']]);
        if ($u->fetch()) {
            $errors[] = 'This username is already taken.';
        }
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('INSERT INTO schools (name, address, phone, email, package_id)
                                VALUES (?, ?, ?, ?, ?)');
            $st->execute([$old['school_name'], $old['address'], $old['phone'], $old['email'], $packageId]);

            $st = $pdo->prepare("INSERT INTO users (role, username, password_hash, active)
                                VALUES ('admin', ?, ?, 1)");
            $st->execute([$old['username'], password_hash($password, PASSWORD_DEFAULT)]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'Registration failed. Please try again.';
        }
    }

    if (!$errors) {
        [$ok, $msg] = attempt_login($old['username'], $password);
        if ($ok) {
            flash('success', 'Welcome! Your school has been registered.');
            redirect('admin/index.php');
        }
        $errors[] = $msg !== '' ? $msg : 'Could not log you in automatically.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register Your School — <?= e(APP_NAME) ?></title>
<link rel="icon" type="image/png" href="<?= e(app_url('assets/icons/logo.png')) ?>">
<link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>?v=4">
<script>try{if(localStorage.getItem('sm-theme')==='dark'){document.documentElement.dataset.theme='dark';}}catch(e){}</script>
<style>
.wrap { max-width: 640px; margin: 0 auto; padding: 24px 18px 40px; }
.brandline { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 18px; margin-bottom: 20px; }
.brandline img { width: 34px; height: 34px; }
</style>
</head>
<body>
<div class="wrap">
  <a class="brandline" href="<?= e(app_url('index.php')) ?>">
    <img src="<?= e(app_url('assets/icons/logo.png')) ?>" alt="SM logo"> <?= e(APP_NAME) ?>
  </a>

  <div class="card">
    <div class="card-head"><h2>🏫 Register Your School</h2></div>
    <div class="card-body">
      <?php foreach (get_flashes() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
      <?php endforeach; ?>
      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="post" action="<?= e(app_url('signup.php')) ?>">
        <?= csrf_field() ?>

        <h3 style="margin-top:0">School details</h3>
        <div class="field">
          <label for="school_name">School name *</label>
          <input type="text" id="school_name" name="school_name" required value="<?= e($old['school_name']) ?>">
        </div>
        <div class="field">
          <label for="address">Address</label>
          <input type="text" id="address" name="address" value="<?= e($old['address']) ?>">
        </div>
        <div class="form-row">
          <div class="field">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" value="<?= e($old['phone']) ?>">
          </div>
          <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($old['email']) ?>">
          </div>
        </div>
        <div class="field">
          <label for="package_id">Package</label>
          <select id="package_id" name="package_id">
            <option value="">— No package —</option>
            <?php foreach ($packages as $p): ?>
            <option value="<?= (int) $p['id'] ?>"<?= $old['package_id'] === (string) $p['id'] ? ' selected' : '' ?>>
              <?= e($p['name']) ?> — <?= e(fmt_money($p['price_monthly'])) ?>/month (up to <?= e(number_format((int) $p['max_students'])) ?> students)
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <h3>Administrator account</h3>
        <div class="form-row">
          <div class="field">
            <label for="username">Username *</label>
            <input type="text" id="username" name="username" required autocomplete="username" value="<?= e($old['username']) ?>">
          </div>
          <div class="field"></div>
        </div>
        <div class="form-row">
          <div class="field">
            <label for="password">Password * (min 6 characters)</label>
            <input type="password" id="password" name="password" required autocomplete="new-password">
          </div>
          <div class="field">
            <label for="password_confirm">Confirm password *</label>
            <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
          </div>
        </div>

        <button class="btn" type="submit" style="width:100%">Register &amp; Go to Dashboard</button>
        <p style="text-align:center;font-size:13px;color:var(--muted)">
          Already registered? <a href="<?= e(app_url('auth/login.php')) ?>">Log in</a>
        </p>
      </form>
    </div>
  </div>
</div>
<script src="<?= e(app_url('assets/js/app.js')) ?>"></script>
</body>
</html>
