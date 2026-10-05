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
<link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>?v=4">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
<script>try{if(localStorage.getItem('sm-theme')==='dark'){document.documentElement.dataset.theme='dark';}}catch(e){}</script>
<style>
.lg-shell { display: grid; grid-template-columns: 1.05fr .95fr; min-height: 100vh; }
.lg-brand { position: relative; color: #fff; padding: 44px 48px; display: flex; flex-direction: column; background: linear-gradient(155deg, rgba(7,28,74,.90), rgba(4,58,48,.88)), url('<?= e(app_url('assets/img/classroom.jpg')) ?>') center/cover; }
.lg-brand .lg-logo { display: flex; align-items: center; gap: 12px; font-family: "Plus Jakarta Sans", Inter, sans-serif; font-weight: 800; font-size: 22px; color: #fff; }
.lg-brand .lg-logo img { width: 46px; height: 46px; background: #fff; border-radius: 12px; padding: 3px; }
.lg-brand .lg-mid { margin: auto 0; max-width: 480px; }
.lg-brand h1 { font-size: clamp(30px, 3.6vw, 44px); line-height: 1.12; letter-spacing: -1px; margin: 0 0 14px; }
.lg-brand h1 .hl { background: linear-gradient(90deg, #7dd3fc, #6ee7b7); -webkit-background-clip: text; background-clip: text; color: transparent; }
.lg-brand p { color: rgba(255,255,255,.82); font-size: 16px; margin: 0 0 24px; }
.lg-ticks { list-style: none; margin: 0 0 28px; padding: 0; }
.lg-ticks li { padding: 7px 0 7px 30px; position: relative; font-weight: 500; font-size: 15px; }
.lg-ticks li::before { content: "✓"; position: absolute; left: 0; top: 7px; width: 21px; height: 21px; border-radius: 50%; background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.35); font-size: 12px; font-weight: 800; display: flex; align-items: center; justify-content: center; }
.lg-chips { display: flex; gap: 10px; flex-wrap: wrap; }
.lg-chips span { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.22); backdrop-filter: blur(6px); border-radius: 999px; padding: 7px 14px; font-size: 13px; font-weight: 600; }
.lg-foot { color: rgba(255,255,255,.6); font-size: 12.5px; }
.lg-form-side { display: flex; align-items: center; justify-content: center; padding: 40px 22px; background: var(--bg); }
.lg-form { width: 100%; max-width: 400px; }
.lg-mobile-brand { display: none; align-items: center; gap: 10px; font-weight: 800; font-size: 19px; margin-bottom: 22px; }
.lg-mobile-brand img { width: 38px; height: 38px; }
.lg-form h2 { font-size: 27px; letter-spacing: -.6px; margin: 0 0 6px; }
.lg-form .lg-sub { color: var(--muted); margin: 0 0 24px; font-size: 14.5px; }
.lg-field { margin-bottom: 16px; }
.lg-field label { display: block; font-weight: 600; font-size: 13.5px; margin-bottom: 7px; }
.lg-input { position: relative; }
.lg-input svg { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--muted); pointer-events: none; }
.lg-input input { width: 100%; box-sizing: border-box; padding: 12px 44px 12px 42px; border: 1.5px solid var(--border); border-radius: 12px; background: var(--surface); color: var(--text); font: inherit; font-size: 15px; transition: border-color .15s, box-shadow .15s; }
.lg-input input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(37,99,235,.14); }
.lg-eye { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--muted); padding: 7px; border-radius: 8px; display: flex; }
.lg-eye:hover { color: var(--text); }
.lg-submit { width: 100%; padding: 13px; font-size: 15.5px; border-radius: 12px; margin-top: 4px; }
.lg-divider { display: flex; align-items: center; gap: 12px; color: var(--muted); font-size: 12.5px; font-weight: 600; letter-spacing: .4px; margin: 24px 0 14px; text-transform: uppercase; }
.lg-divider::before, .lg-divider::after { content: ""; flex: 1; border-top: 1px solid var(--border); }
.lg-demos { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.lg-demo { display: flex; align-items: center; gap: 11px; padding: 12px 13px; border: 1.5px solid var(--border); border-radius: 14px; background: var(--surface); transition: transform .15s, border-color .15s, box-shadow .15s; }
.lg-demo:hover { transform: translateY(-2px); border-color: var(--primary); box-shadow: var(--shadow); text-decoration: none; }
.lg-demo .lg-ico { width: 38px; height: 38px; flex: none; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #fff; }
.lg-demo b { display: block; font-size: 14px; color: var(--text); line-height: 1.25; }
.lg-demo small { color: var(--muted); font-size: 12px; }
.g-a { background: linear-gradient(135deg, #2563eb, #1d4ed8); } .g-t { background: linear-gradient(135deg, #059669, #047857); }
.g-s { background: linear-gradient(135deg, #d97706, #b45309); } .g-p { background: linear-gradient(135deg, #7c3aed, #6d28d9); }
.lg-back { text-align: center; font-size: 13.5px; margin: 22px 0 0; }
@media (max-width: 900px) {
  .lg-shell { grid-template-columns: 1fr; }
  .lg-brand { display: none; }
  .lg-mobile-brand { display: flex; }
  .lg-form-side { align-items: flex-start; padding-top: 30px; }
}
</style>
</head>
<body>
<div class="lg-shell">
  <aside class="lg-brand">
    <a class="lg-logo" href="<?= e(app_url('index.php')) ?>">
      <img src="<?= e(app_url('assets/icons/logo.png')) ?>" alt="School Markaz logo"> <?= e(APP_NAME) ?>
    </a>
    <div class="lg-mid">
      <h1>Your whole school,<br><span class="hl">one smart system.</span></h1>
      <p><?= e(APP_TAGLINE) ?> — admissions, attendance, fees, results and parents, all in one place.</p>
      <ul class="lg-ticks">
        <li>Gate attendance with instant parent alerts</li>
        <li>Fee challans &amp; receipts in one click</li>
        <li>Separate portals for admin, teachers, students &amp; parents</li>
      </ul>
      <div class="lg-chips"><span>🎓 4 Portals</span><span>🏫 Multi-Campus</span><span>📱 Works on any phone</span></div>
    </div>
    <div class="lg-foot">Powered by Weblitex · Umerkot, Sindh</div>
  </aside>

  <main class="lg-form-side">
    <div class="lg-form">
      <a class="lg-mobile-brand" href="<?= e(app_url('index.php')) ?>">
        <img src="<?= e(app_url('assets/icons/logo.png')) ?>" alt="School Markaz logo"> <?= e(APP_NAME) ?>
      </a>
      <h2>Welcome back 👋</h2>
      <p class="lg-sub">Sign in to your portal to continue.</p>

      <?php foreach (get_flashes() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
      <?php endforeach; ?>
      <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= e(app_url('auth/login.php')) ?>">
        <?= csrf_field() ?>
        <div class="lg-field">
          <label for="username">Username</label>
          <div class="lg-input">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c.8-3.8 4-5.5 8-5.5s7.2 1.7 8 5.5"/></svg>
            <input type="text" id="username" name="username" required autocomplete="username" placeholder="Enter your username" value="<?= e($username) ?>">
          </div>
        </div>
        <div class="lg-field">
          <label for="password">Password</label>
          <div class="lg-input">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/></svg>
            <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
            <button class="lg-eye" type="button" id="pwToggle" aria-label="Show or hide password">
              <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>
        <button class="btn lg-submit" type="submit">Sign In →</button>
      </form>

      <div class="lg-divider">Or explore the demo</div>
      <div class="lg-demos">
        <a class="lg-demo" href="<?= e(app_url('auth/login.php?demo=admin')) ?>"><span class="lg-ico g-a">🧑‍💼</span><span><b>Admin</b><small>Full school control</small></span></a>
        <a class="lg-demo" href="<?= e(app_url('auth/login.php?demo=teacher')) ?>"><span class="lg-ico g-t">👩‍🏫</span><span><b>Teacher</b><small>Classes &amp; results</small></span></a>
        <a class="lg-demo" href="<?= e(app_url('auth/login.php?demo=student')) ?>"><span class="lg-ico g-s">🎒</span><span><b>Student</b><small>Results &amp; fees</small></span></a>
        <a class="lg-demo" href="<?= e(app_url('auth/login.php?demo=parent')) ?>"><span class="lg-ico g-p">👪</span><span><b>Parent</b><small>Child's progress</small></span></a>
      </div>
      <p class="lg-back"><a href="<?= e(app_url('index.php')) ?>">← Back to home</a></p>
    </div>
  </main>
</div>
<script>
document.getElementById('pwToggle').addEventListener('click', function () {
  var p = document.getElementById('password');
  p.type = p.type === 'password' ? 'text' : 'password';
  p.focus();
});
</script>
<script src="<?= e(app_url('assets/js/app.js')) ?>"></script>
</body>
</html>
