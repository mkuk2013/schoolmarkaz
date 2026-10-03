<?php
declare(strict_types=1);

/**
 * Login page + one-click demo logins.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$dashboards = [
    'admin'   => 'admin/index.php',
    'teacher' => 'teacher/index.php',
    'student' => 'student/index.php',
    'parent'  => 'parent/index.php',
];

// Already logged in → go to own dashboard.
if (current_user() !== null) {
    redirect($dashboards[user_role()] ?? 'index.php');
}

$error = '';
$username = '';

// One-click demo: ?demo=admin|teacher|student|parent
if (isset($_GET['demo'])) {
    $demo = strtolower(trim((string) $_GET['demo']));
    if (isset($dashboards[$demo])) {
        [$ok, $msg] = attempt_login($demo, 'demo123');
        if ($ok) {
            redirect($dashboards[$demo]);
        }
        $error = $msg !== '' ? $msg : 'Demo login failed.';
    } else {
        $error = 'Unknown demo account.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        [$ok, $msg] = attempt_login($username, $password);
        if ($ok) {
            redirect($dashboards[user_role()] ?? 'index.php');
        }
        $error = $msg;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — <?= e(APP_NAME) ?></title>
<link rel="icon" type="image/png" href="<?= e(app_url('assets/icons/logo.png')) ?>">
<link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>">
<script>try{if(localStorage.getItem('sm-theme')==='dark'){document.documentElement.dataset.theme='dark';}}catch(e){}</script>
<style>
.wrap { max-width: 440px; margin: 0 auto; padding: 48px 18px 40px; }
.brandline { display: flex; align-items: center; justify-content: center; gap: 10px; font-weight: 800; font-size: 20px; margin-bottom: 24px; }
.brandline img { width: 38px; height: 38px; }
.demo-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 12px; }
</style>
</head>
<body>
<div class="wrap">
  <a class="brandline" href="<?= e(app_url('index.php')) ?>">
    <img src="<?= e(app_url('assets/icons/logo.png')) ?>" alt="SM logo"> <?= e(APP_NAME) ?>
  </a>

  <div class="card">
    <div class="card-head"><h2>🔐 Login</h2></div>
    <div class="card-body">
      <?php foreach (get_flashes() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
      <?php endforeach; ?>
      <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= e(app_url('auth/login.php')) ?>">
        <?= csrf_field() ?>
        <div class="field">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" required autocomplete="username" value="<?= e($username) ?>">
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <button class="btn" type="submit" style="width:100%">Log In</button>
      </form>

      <hr style="border:none;border-top:1px solid var(--border);margin:20px 0 14px">
      <p style="font-size:13px;color:var(--muted);text-align:center;margin:0 0 6px">One-click demo accounts</p>
      <div class="demo-grid">
        <a class="btn small" href="<?= e(app_url('auth/login.php?demo=admin')) ?>">🧑‍💼 Demo Admin</a>
        <a class="btn small secondary" href="<?= e(app_url('auth/login.php?demo=teacher')) ?>">👩‍🏫 Demo Teacher</a>
        <a class="btn small secondary" href="<?= e(app_url('auth/login.php?demo=student')) ?>">🎒 Demo Student</a>
        <a class="btn small secondary" href="<?= e(app_url('auth/login.php?demo=parent')) ?>">👪 Demo Parent</a>
      </div>
      <p style="text-align:center;font-size:13px;margin:16px 0 0">
        <a href="<?= e(app_url('index.php')) ?>">← Back to home</a>
      </p>
    </div>
  </div>
</div>
<script src="<?= e(app_url('assets/js/app.js')) ?>"></script>
</body>
</html>
