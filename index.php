<?php
declare(strict_types=1);

/**
 * School Markaz — public landing page.
 * Standalone HTML (no login required); reuses the design system + dark mode.
 */
require_once __DIR__ . '/includes/bootstrap.php';

$profile = school_profile();
$schoolName = ($profile['name'] ?? '') !== '' ? (string) $profile['name'] : APP_NAME;

$pkgStmt = db()->query('SELECT * FROM packages ORDER BY price_monthly ASC');
$packages = $pkgStmt ? $pkgStmt->fetchAll() : [];

$features = [
    ['icon' => '✓', 'title' => 'Attendance',   'desc' => 'Daily student & staff attendance with registers, defaulter lists and SMS-ready summaries.'],
    ['icon' => '💰', 'title' => 'Fee Management', 'desc' => 'Fee heads, invoices, receipts, dues tracking and fine calculation — fully automated.'],
    ['icon' => '📝', 'title' => 'Results & Exams', 'desc' => 'Exams, marks entry, report cards and merit lists computed in seconds.'],
    ['icon' => '👪', 'title' => 'Parent Portal',  'desc' => 'Parents check attendance, results, fees and school notices from their own login.'],
    ['icon' => '📊', 'title' => 'Finance & Payroll', 'desc' => 'Income/expense ledger, salary payments with slips, and month-wise reports.'],
    ['icon' => '🗓', 'title' => 'Timetable',     'desc' => 'Class-wise weekly timetables for teachers and students, printable anytime.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($schoolName) ?> — <?= e(APP_TAGLINE) ?></title>
<link rel="icon" type="image/png" href="<?= e(app_url('assets/icons/logo.png')) ?>">
<link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>">
<script>try{if(localStorage.getItem('sm-theme')==='dark'){document.documentElement.dataset.theme='dark';}}catch(e){}</script>
<style>
.hero { background: linear-gradient(135deg, #1d4ed8, #7c3aed); color: #fff; border-radius: var(--radius); padding: 56px 32px; margin-bottom: 28px; text-align: center; box-shadow: var(--shadow); }
.hero h1 { margin: 0 0 10px; font-size: clamp(28px, 5vw, 44px); line-height: 1.15; }
.hero p.tagline { font-size: clamp(15px, 2.5vw, 19px); opacity: .92; margin: 0 0 26px; }
.hero-cta { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
.hero-cta .btn { padding: 12px 26px; font-size: 15px; }
.hero .btn.ghost { background: rgba(255,255,255,.14); color: #fff; border-color: rgba(255,255,255,.35); }
.hero .btn.ghost:hover { background: rgba(255,255,255,.25); }
.topnav { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 0; flex-wrap: wrap; }
.brandline { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 18px; }
.brandline img { width: 34px; height: 34px; }
.nav-links { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.section-title { text-align: center; margin: 34px 0 6px; font-size: 26px; }
.section-sub { text-align: center; color: var(--muted); margin: 0 0 22px; }
.feat { display: flex; gap: 12px; align-items: flex-start; }
.feat-ico { font-size: 26px; line-height: 1; }
.feat h3 { margin: 0 0 6px; font-size: 16px; }
.feat p { margin: 0; color: var(--muted); font-size: 14px; }
.price { text-align: center; }
.price .pname { font-size: 18px; font-weight: 800; margin: 0 0 4px; }
.price .pamount { font-size: 30px; font-weight: 800; color: var(--primary); margin: 6px 0; }
.price .pamount small { font-size: 13px; font-weight: 400; color: var(--muted); }
.price ul { list-style: none; margin: 14px 0 18px; padding: 0; text-align: left; }
.price ul li { padding: 7px 0; border-bottom: 1px solid var(--border); font-size: 14px; }
.price ul li::before { content: "✓ "; color: var(--success); font-weight: 700; }
.price.featured { border: 2px solid var(--primary); }
.demo-btns { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin: 20px 0 8px; }
footer.site { text-align: center; color: var(--muted); font-size: 13px; padding: 30px 0 20px; }
.wrap { max-width: 1080px; margin: 0 auto; padding: 0 18px 20px; }
</style>
</head>
<body>
<div class="wrap">

  <div class="topnav">
    <div class="brandline">
      <img src="<?= e(app_url('assets/icons/logo.png')) ?>" alt="SM logo">
      <?= e($schoolName) ?>
    </div>
    <div class="nav-links">
      <button class="btn secondary small" onclick="smToggleTheme()" type="button">🌙</button>
      <a class="btn secondary small" href="<?= e(app_url('admission-form.php')) ?>">Online Admission</a>
      <a class="btn secondary small" href="<?= e(app_url('signup.php')) ?>">Register Your School</a>
      <a class="btn small" href="<?= e(app_url('auth/login.php')) ?>">Login</a>
    </div>
  </div>

  <div class="hero">
    <h1><?= e($schoolName) ?></h1>
    <p class="tagline"><?= e(APP_TAGLINE) ?> — attendance, fees, results, staff &amp; parents, all in one place.</p>
    <div class="hero-cta">
      <a class="btn ghost" href="<?= e(app_url('admission-form.php')) ?>">🎓 Online Admission</a>
      <a class="btn" style="background:#fff;color:#1d4ed8" href="<?= e(app_url('signup.php')) ?>">🏫 Register Your School</a>
    </div>
  </div>

  <h2 class="section-title">Everything your school needs</h2>
  <p class="section-sub">Six core modules covering daily operations end-to-end.</p>
  <div class="grid grid-3">
    <?php foreach ($features as $f): ?>
    <div class="card" style="margin-bottom:0">
      <div class="card-body feat">
        <div class="feat-ico"><?= e($f['icon']) ?></div>
        <div>
          <h3><?= e($f['title']) ?></h3>
          <p><?= e($f['desc']) ?></p>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <h2 class="section-title">Packages &amp; Pricing</h2>
  <p class="section-sub">Simple monthly pricing that grows with your school.</p>
  <div class="grid grid-3">
    <?php
    $i = 0;
    foreach ($packages as $p):
        $i++;
        $featLines = array_filter(array_map('trim', explode("\n", (string) ($p['features'] ?? ''))));
    ?>
    <div class="card price<?= $i === 2 ? ' featured' : '' ?>" style="margin-bottom:0">
      <div class="card-body">
        <p class="pname"><?= e($p['name']) ?></p>
        <?php if ($i === 2): ?><span class="badge blue">Most Popular</span><?php endif; ?>
        <p class="pamount"><?= e(fmt_money($p['price_monthly'])) ?><small>/month</small></p>
        <p style="color:var(--muted);font-size:13px;margin:0 0 6px">Up to <?= e(number_format((int) $p['max_students'])) ?> students</p>
        <ul>
          <?php foreach ($featLines as $line): ?>
          <li><?= e($line) ?></li>
          <?php endforeach; ?>
        </ul>
        <a class="btn secondary" style="width:100%;text-align:center" href="<?= e(app_url('subscribe.php?package_id=' . (int)$p['id'])) ?>">Choose <?= e($p['name']) ?></a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <h2 class="section-title">Try the live demo</h2>
  <p class="section-sub">One click to explore each role — username &amp; password filled automatically.</p>
  <div class="card">
    <div class="card-body">
      <div class="demo-btns">
        <a class="btn" href="<?= e(app_url('auth/login.php?demo=admin')) ?>">🧑‍💼 Demo Admin</a>
        <a class="btn secondary" href="<?= e(app_url('auth/login.php?demo=teacher')) ?>">👩‍🏫 Demo Teacher</a>
        <a class="btn secondary" href="<?= e(app_url('auth/login.php?demo=student')) ?>">🎒 Demo Student</a>
        <a class="btn secondary" href="<?= e(app_url('auth/login.php?demo=parent')) ?>">👪 Demo Parent</a>
      </div>
      <p style="text-align:center;color:var(--muted);font-size:13px;margin:6px 0 0">Demo accounts are for evaluation only.</p>
    </div>
  </div>

  <footer class="site">
    &copy; <?= date('Y') ?> <?= e($schoolName) ?> — <?= e(APP_TAGLINE) ?>
  </footer>
</div>
<script src="<?= e(app_url('assets/js/app.js')) ?>"></script>
</body>
</html>
