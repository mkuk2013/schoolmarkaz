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
    ['icon' => '🚪', 'title' => 'Gate Attendance', 'desc' => 'Barcode ID-card scanning at the gate — entry/exit logged, attendance marked and parents alerted instantly.'],
    ['icon' => '🧾', 'title' => 'Bank Challans & ID Cards', 'desc' => '3-copy PDF bank challans, student ID cards with barcodes, and certificates in one click.'],
    ['icon' => '❓', 'title' => 'Online Quizzes', 'desc' => 'Teachers create MCQ quizzes (AI can draft them), students attempt online and get instant auto-marked results.'],
    ['icon' => '🏢', 'title' => 'Multi-Campus', 'desc' => 'Run all your branches from one system — students, teachers and classes per campus, with easy transfers.'],
];

$portals = [
    ['icon' => '🧑‍💼', 'role' => 'Admin', 'desc' => 'Students, fees, staff, finance, reports — the full command center of your school.', 'demo' => 'admin'],
    ['icon' => '👩‍🏫', 'role' => 'Teacher', 'desc' => 'Mark attendance, enter marks, build quizzes and view your timetable from your phone.', 'demo' => 'teacher'],
    ['icon' => '🎒', 'role' => 'Student', 'desc' => 'See results, fee status, timetable and attempt online quizzes from one simple app.', 'demo' => 'student'],
    ['icon' => '👪', 'role' => 'Parent', 'desc' => 'Gate entry alerts, attendance, results and fee receipts — stay connected to your child\'s school.', 'demo' => 'parent'],
];

$faqs = [
    ['q' => 'Is there really a free plan?', 'a' => 'Yes. The Free plan covers up to 50 students with the core modules, forever — no card required. Paid plans unlock higher limits and advanced modules.'],
    ['q' => 'Does it work on mobile phones?', 'a' => 'School Markaz is a web app that installs like a mobile app (PWA). Admins, teachers, students and parents can all use it from any phone or computer browser.'],
    ['q' => 'Can parents see gate entry and results?', 'a' => 'Yes. When a student\'s ID card is scanned at the gate, the entry is logged and parents can see gate alerts, attendance, results and fee receipts in their own portal.'],
    ['q' => 'How does a school get started?', 'a' => 'Register your school online, choose a package and submit your payment details. Once approved, your school account is ready — our team helps you with the first setup.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($schoolName) ?> — <?= e(APP_TAGLINE) ?></title>
<meta name="description" content="School Markaz — complete school management system: attendance, fees, bank challans, gate scanning, results, parent portal and multi-campus support.">
<link rel="icon" type="image/png" href="<?= e(app_url('assets/icons/logo.png')) ?>">
<link rel="manifest" href="<?= e(app_url('manifest.webmanifest')) ?>">
<script>if('serviceWorker' in navigator){navigator.serviceWorker.register('<?= e(app_url('sw.js')) ?>').catch(function(){});}</script>
<link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>?v=4">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
<script>try{if(localStorage.getItem('sm-theme')==='dark'){document.documentElement.dataset.theme='dark';}}catch(e){}</script>
<style>
.wrap { max-width: 1140px; margin: 0 auto; padding: 0 18px; }
.topnav { position: sticky; top: 0; z-index: 60; background: color-mix(in srgb, var(--surface) 88%, transparent); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-bottom: 1px solid var(--border); }
.topnav-in { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 0; flex-wrap: wrap; }
.brandline { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 19px; letter-spacing: -.3px; }
.brandline img { width: 38px; height: 38px; border-radius: 10px; }
.nav-links { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.nav-links a.plain { color: var(--text); font-weight: 600; font-size: 14px; padding: 8px 10px; }
.nav-links a.plain:hover { color: var(--primary); text-decoration: none; }
.hero { position: relative; overflow: hidden; border-radius: 26px; margin: 26px 0 8px; padding: 64px 34px 54px; text-align: center; color: #fff; background: var(--grad-hero); box-shadow: var(--shadow-lg); }
.hero::before, .hero::after { content: ""; position: absolute; border-radius: 50%; filter: blur(60px); }
.hero::before { width: 340px; height: 340px; background: rgba(14, 165, 164, .55); top: -130px; left: -70px; }
.hero::after { width: 300px; height: 300px; background: rgba(245, 179, 1, .35); bottom: -150px; right: -60px; }
.hero > * { position: relative; z-index: 1; }
.kicker { display: inline-block; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.35); color: #eaf2ff; padding: 6px 14px; border-radius: 999px; font-size: 13px; font-weight: 600; letter-spacing: .4px; text-transform: uppercase; }
.hero h1 { margin: 18px 0 12px; font-size: clamp(32px, 5.4vw, 52px); line-height: 1.12; letter-spacing: -1px; }
.hero h1 .hl { background: linear-gradient(90deg, #fbbf24, #34d399); -webkit-background-clip: text; background-clip: text; color: transparent; }
.hero p.tagline { font-size: clamp(15px, 2.4vw, 19px); opacity: .94; margin: 0 auto 28px; max-width: 680px; }
.hero-cta { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
.hero-cta .btn { padding: 13px 28px; font-size: 15px; border-radius: 12px; }
.btn.white { background: #fff; color: #1d4ed8; box-shadow: 0 12px 26px -10px rgba(0,0,0,.45); }
.btn.white:hover { color: #1d4ed8; }
.btn.ghost { background: rgba(255,255,255,.13); color: #fff; border: 1px solid rgba(255,255,255,.4); box-shadow: none; }
.btn.ghost:hover { background: rgba(255,255,255,.22); color: #fff; }
.hero-points { display: flex; gap: 8px 22px; justify-content: center; flex-wrap: wrap; margin-top: 26px; font-size: 14px; color: #dbeafe; font-weight: 500; }
.hero-points span::before { content: "✓ "; color: #34d399; font-weight: 800; }
.mock { max-width: 760px; margin: 40px auto 6px; background: #fff; color: var(--text); border-radius: 18px; box-shadow: 0 30px 70px -20px rgba(2, 12, 40, .55); text-align: left; overflow: hidden; }
.mock-bar { display: flex; align-items: center; gap: 7px; padding: 11px 14px; border-bottom: 1px solid var(--border); background: var(--surface-muted); }
.mock-bar i { width: 11px; height: 11px; border-radius: 50%; display: inline-block; }
.mock-bar .addr { margin-left: 8px; font-size: 12px; color: var(--muted); background: #fff; border: 1px solid var(--border); border-radius: 7px; padding: 3px 10px; }
.mock-body { display: grid; grid-template-columns: 128px 1fr; min-height: 218px; }
.mock-side { background: linear-gradient(180deg, #0a1730, #0d2149); padding: 14px 10px; display: flex; flex-direction: column; gap: 7px; }
.mock-side b { height: 10px; border-radius: 5px; background: rgba(255,255,255,.22); }
.mock-side b.on { background: var(--grad); }
.mock-main { padding: 16px; background: #f4f7fc; }
.mock-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 12px; }
.mock-stat { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 10px 12px; }
.mock-stat strong { display: block; font-size: 19px; letter-spacing: -.4px; }
.mock-stat span { font-size: 11px; color: var(--muted); font-weight: 600; }
.mock-chart { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 14px; display: flex; align-items: flex-end; gap: 9px; height: 104px; }
.mock-chart i { flex: 1; border-radius: 6px 6px 2px 2px; background: var(--grad); opacity: .9; }
.float-chip { position: absolute; z-index: 2; background: #fff; color: var(--text); border-radius: 12px; box-shadow: var(--shadow-lg); padding: 10px 14px; font-size: 13px; font-weight: 600; display: flex; gap: 8px; align-items: center; }
.chip-a { top: 120px; left: 26px; } .chip-b { bottom: 108px; right: 26px; }
@media (max-width: 900px) { .float-chip { display: none; } .mock-body { grid-template-columns: 1fr; } .mock-side { display: none; } }
.strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin: 30px 0 6px; }
.strip .cell { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); padding: 16px; text-align: center; }
.strip .cell strong { display: block; font-size: 17px; letter-spacing: -.3px; }
.strip .cell span { font-size: 13px; color: var(--muted); }
@media (max-width: 900px) { .strip { grid-template-columns: repeat(2, 1fr); } }
.section-k { text-align: center; color: var(--primary); font-weight: 800; font-size: 13px; letter-spacing: 2px; text-transform: uppercase; margin: 52px 0 6px; }
.section-title { text-align: center; margin: 0 0 8px; font-size: clamp(24px, 3.6vw, 32px); letter-spacing: -.6px; }
.section-sub { text-align: center; color: var(--muted); margin: 0 auto 26px; max-width: 640px; }
.feat { display: flex; gap: 14px; align-items: flex-start; height: 100%; }
.feat-ico { flex-shrink: 0; width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 23px; color: #fff; box-shadow: 0 10px 20px -8px rgba(11,21,38,.4); }
.g1 { background: linear-gradient(135deg, #2563eb, #0ea5a4); } .g2 { background: linear-gradient(135deg, #7c3aed, #db2777); }
.g3 { background: linear-gradient(135deg, #059669, #65a30d); } .g4 { background: linear-gradient(135deg, #d97706, #dc2626); }
.g5 { background: linear-gradient(135deg, #0284c7, #2563eb); } .g6 { background: linear-gradient(135deg, #0f766e, #2563eb); }
.feat h3 { margin: 2px 0 6px; font-size: 16px; }
.feat p { margin: 0; color: var(--muted); font-size: 14px; }
.portal-card { text-align: left; height: 100%; }
.portal-card .pico { font-size: 34px; }
.portal-card h3 { margin: 8px 0 6px; font-size: 17px; }
.portal-card p { margin: 0 0 14px; color: var(--muted); font-size: 14px; }
.price { text-align: center; height: 100%; }
.price .pname { font-size: 18px; font-weight: 800; margin: 0 0 4px; }
.price .pamount { font-size: 31px; font-weight: 800; color: var(--primary); margin: 6px 0; letter-spacing: -.6px; }
.price .pamount small { font-size: 13px; font-weight: 500; color: var(--muted); }
.price ul { list-style: none; margin: 14px 0 18px; padding: 0; text-align: left; }
.price ul li { padding: 7px 0; border-bottom: 1px solid var(--border); font-size: 14px; }
.price ul li::before { content: "✓ "; color: var(--success); font-weight: 800; }
.price.featured { border: 2px solid transparent; background: linear-gradient(var(--surface), var(--surface)) padding-box, var(--grad) border-box; transform: scale(1.03); }
.demo-btns { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin: 6px 0 8px; }
.faq { max-width: 780px; margin: 0 auto; }
.faq details { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 16px 20px; margin-bottom: 10px; box-shadow: var(--shadow); }
.faq summary { font-weight: 700; cursor: pointer; font-size: 15px; }
.faq p { color: var(--muted); margin: 10px 0 2px; font-size: 14px; }
.cta-band { border-radius: 24px; background: var(--grad-hero); color: #fff; text-align: center; padding: 46px 26px; margin: 54px 0 8px; box-shadow: var(--shadow-lg); }
.cta-band h2 { margin: 0 0 8px; font-size: clamp(23px, 3.4vw, 30px); letter-spacing: -.5px; }
.cta-band p { margin: 0 0 22px; opacity: .92; }
.bento { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.bcard { background: var(--surface); border: 1px solid var(--border); border-radius: 20px; box-shadow: var(--shadow); padding: 24px; transition: transform .2s ease, box-shadow .2s ease; }
.bcard:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); }
.bcard h3 { margin: 12px 0 6px; font-size: 17px; letter-spacing: -.3px; }
.bcard p { margin: 0; color: var(--muted); font-size: 14px; }
.b-wide { grid-column: span 2; } .b-wide2 { grid-column: span 2; }
@media (max-width: 900px) { .bento { grid-template-columns: 1fr; } .b-wide, .b-wide2 { grid-column: span 1; } }
.b-ico { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #fff; box-shadow: 0 10px 20px -8px rgba(11,21,38,.4); }
.mini-log { margin-top: 16px; display: flex; flex-direction: column; gap: 8px; }
.mini-log div { display: flex; align-items: center; gap: 9px; background: var(--surface-muted); border: 1px solid var(--border); border-radius: 10px; padding: 8px 12px; font-size: 13px; font-weight: 500; }
.mini-log time { margin-left: auto; color: var(--muted); font-size: 12px; }
.mini-challan { margin-top: 16px; background: var(--surface-muted); border: 1.5px dashed #b6c2d4; border-radius: 12px; padding: 14px; display: flex; flex-direction: column; gap: 5px; }
.mini-challan i { height: 8px; border-radius: 4px; background: #d4dde9; }
.mini-challan i:nth-child(2) { width: 72%; } .mini-challan i:nth-child(3) { width: 54%; }
.mini-challan b { margin-top: 6px; font-size: 14px; } .mini-challan span { font-size: 12.5px; color: var(--muted); }
.mini-score { margin-top: 16px; display: flex; align-items: center; gap: 14px; background: var(--surface-muted); border: 1px solid var(--border); border-radius: 12px; padding: 14px; }
.mini-score strong { font-size: 30px; letter-spacing: -1px; color: var(--success); }
.mini-score span { font-size: 13px; color: var(--muted); font-weight: 500; }
.mini-bars { margin-top: 16px; display: flex; align-items: flex-end; gap: 8px; height: 74px; background: var(--surface-muted); border: 1px solid var(--border); border-radius: 12px; padding: 12px; }
.mini-bars i { flex: 1; border-radius: 5px 5px 2px 2px; background: var(--grad); }
footer.site { text-align: center; color: var(--muted); font-size: 13px; padding: 28px 0 22px; }
</style>
</head>
<body>
<div class="topnav">
  <div class="wrap topnav-in">
    <div class="brandline">
      <img src="<?= e(app_url('assets/icons/logo.png')) ?>" alt="School Markaz logo">
      <?= e($schoolName) ?>
    </div>
    <div class="nav-links">
      <a class="plain" href="#features">Features</a>
      <a class="plain" href="#portals">Portals</a>
      <a class="plain" href="#pricing">Pricing</a>
      <button class="btn secondary small" onclick="smToggleTheme()" type="button" title="Dark mode">🌙</button>
      <a class="btn secondary small" href="<?= e(app_url('admission-form.php')) ?>">Online Admission</a>
      <a class="btn small" href="<?= e(app_url('auth/login.php')) ?>">Login</a>
    </div>
  </div>
</div>

<div class="wrap">
  <div class="hero">
    <div class="float-chip chip-a">🚪 Gate Entry <span class="badge green">Parent Alert Sent</span></div>
    <div class="float-chip chip-b">🧾 Fee Challan <span class="badge blue">PDF Ready</span></div>
    <span class="kicker">🎓 Complete School Management System</span>
    <h1>Run your whole school<br>from <span class="hl">one smart system</span></h1>
    <p class="tagline"><?= e(APP_TAGLINE) ?> — admissions, attendance, fees, results, staff payroll and parents, all in one place. Built for schools in Pakistan.</p>
    <div class="hero-cta">
      <a class="btn white" href="<?= e(app_url('signup.php')) ?>">🏫 Register Your School — Free</a>
      <a class="btn ghost" href="#demo">▶ Try the Live Demo</a>
    </div>
    <div class="hero-points">
      <span>Free plan up to 50 students</span>
      <span>Works on any phone</span>
      <span>Multi-campus ready</span>
      <span>No installation needed</span>
    </div>
    <div class="mock" aria-hidden="true">
      <div class="mock-bar"><i style="background:#f87171"></i><i style="background:#fbbf24"></i><i style="background:#34d399"></i><span class="addr">schoolmarkaz — Admin Dashboard</span></div>
      <div class="mock-body">
        <div class="mock-side"><b class="on"></b><b></b><b></b><b></b><b></b><b></b><b></b></div>
        <div class="mock-main">
          <div class="mock-stats">
            <div class="mock-stat"><strong>1,248</strong><span>Total Students</span></div>
            <div class="mock-stat"><strong>96.4%</strong><span>Attendance Today</span></div>
            <div class="mock-stat"><strong>Rs 8.6L</strong><span>Fees This Month</span></div>
          </div>
          <div class="mock-chart"><i style="height:38%"></i><i style="height:55%"></i><i style="height:44%"></i><i style="height:70%"></i><i style="height:62%"></i><i style="height:84%"></i><i style="height:96%"></i></div>
        </div>
      </div>
    </div>
  </div>

  <div class="strip">
    <div class="cell"><strong>🎁 Free Forever Plan</strong><span>Up to 50 students, core modules included</span></div>
    <div class="cell"><strong>📱 Mobile App (PWA)</strong><span>Installs on any phone — no Play Store needed</span></div>
    <div class="cell"><strong>🏢 Multi-Campus</strong><span>All branches in one account, easy transfers</span></div>
    <div class="cell"><strong>🇵🇰 Made for Pakistan</strong><span>Bank challans, PKR billing, local support</span></div>
  </div>

  <p class="section-k" id="features">Everything You Need</p>
  <h2 class="section-title">One system for the whole school day</h2>
  <p class="section-sub">From the morning gate scan to the monthly fee report — every module works together, so nothing is typed twice.</p>
  <div class="grid grid-3">
    <?php $gi = 0; foreach ($features as $f): $gi++; ?>
    <div class="card" style="margin-bottom:0">
      <div class="card-body feat">
        <div class="feat-ico g<?= (($gi - 1) % 6) + 1 ?>"><?= e($f['icon']) ?></div>
        <div>
          <h3><?= e($f['title']) ?></h3>
          <p><?= e($f['desc']) ?></p>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <p class="section-k">Inside The App</p>
  <h2 class="section-title">Made to feel effortless, every day</h2>
  <p class="section-sub">Real workflows, designed for busy school offices — here is a peek at what your team will use daily.</p>
  <div class="bento">
    <div class="bcard b-wide">
      <div class="b-ico g1">🚪</div>
      <h3>Gate Attendance in seconds</h3>
      <p>Scan the student's ID card at the gate. Entry is logged, attendance is marked, and parents can see the alert in their portal — no registers, no phone calls.</p>
      <div class="mini-log">
        <div><span class="badge green">IN</span> Ahmed Raza — Class 6-A <time>07:42 AM</time></div>
        <div><span class="badge green">IN</span> Fatima Noor — Class 4-B <time>07:43 AM</time></div>
        <div><span class="badge amber">OUT</span> Bilal Ahmed — Class 8-C <time>01:15 PM</time></div>
      </div>
    </div>
    <div class="bcard">
      <div class="b-ico g4">🧾</div>
      <h3>3-copy bank challans</h3>
      <p>Print professional fee challans — Bank, School and Student copies on a single page, straight from the invoice.</p>
      <div class="mini-challan"><i></i><i></i><i></i><b>CH-2026-00124</b><span>Rs 4,500 — Meezan Bank</span></div>
    </div>
    <div class="bcard">
      <div class="b-ico g2">❓</div>
      <h3>Quizzes that mark themselves</h3>
      <p>Teachers create MCQs once — students attempt online and results are calculated instantly, with class-wise analysis.</p>
      <div class="mini-score"><strong>92%</strong><span>Class average — Science Quiz 4</span></div>
    </div>
    <div class="bcard b-wide2">
      <div class="b-ico g3">📊</div>
      <h3>Reports without the month-end panic</h3>
      <p>Fee collection, defaulters, attendance trends and payroll summaries are always one click away — computed live from your data, ready to print or share.</p>
      <div class="mini-bars"><i style="height:34%"></i><i style="height:52%"></i><i style="height:44%"></i><i style="height:66%"></i><i style="height:58%"></i><i style="height:78%"></i><i style="height:92%"></i><i style="height:70%"></i></div>
    </div>
  </div>

  <p class="section-k" id="portals">Four Portals</p>
  <h2 class="section-title">Everyone gets their own app</h2>
  <p class="section-sub">Admin, teachers, students and parents each sign in to a portal made for them.</p>
  <div class="grid grid-4" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
    <?php foreach ($portals as $pt): ?>
    <div class="card portal-card" style="margin-bottom:0">
      <div class="card-body">
        <div class="pico"><?= $pt['icon'] ?></div>
        <h3><?= e($pt['role']) ?> Portal</h3>
        <p><?= e($pt['desc']) ?></p>
        <a class="btn secondary small" href="<?= e(app_url('auth/login.php?demo=' . $pt['demo'])) ?>">Open Demo →</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <p class="section-k" id="pricing">Pricing</p>
  <h2 class="section-title">Simple pricing that grows with you</h2>
  <p class="section-sub">Start free. Upgrade only when your school grows — monthly plans in PKR, no hidden charges.</p>
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
        <?php if (!empty($p['per_student'])): ?>
        <p class="pamount"><?= e(fmt_money($p['price_monthly'])) ?><small>/student/month</small></p>
        <p style="color:var(--muted);font-size:13px;margin:0 0 6px">Pay only for your students</p>
        <?php elseif ((float) $p['price_monthly'] <= 0): ?>
        <p class="pamount">Free</p>
        <p style="color:var(--muted);font-size:13px;margin:0 0 6px">Up to <?= e(number_format((int) $p['max_students'])) ?> students</p>
        <?php else: ?>
        <p class="pamount"><?= e(fmt_money($p['price_monthly'])) ?><small>/month</small></p>
        <p style="color:var(--muted);font-size:13px;margin:0 0 6px">Up to <?= e(number_format((int) $p['max_students'])) ?> students</p>
        <?php endif; ?>
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

  <p class="section-k" id="demo">Live Demo</p>
  <h2 class="section-title">Try it right now</h2>
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

  <p class="section-k">FAQ</p>
  <h2 class="section-title">Questions, answered</h2>
  <p class="section-sub">The things schools ask us most.</p>
  <div class="faq">
    <?php foreach ($faqs as $fq): ?>
    <details>
      <summary><?= e($fq['q']) ?></summary>
      <p><?= e($fq['a']) ?></p>
    </details>
    <?php endforeach; ?>
  </div>

  <div class="cta-band">
    <h2>Ready to modernize your school?</h2>
    <p>Create your school account in minutes — the Free plan needs no card and no installation.</p>
    <a class="btn white" href="<?= e(app_url('signup.php')) ?>">🏫 Register Your School</a>
    <a class="btn ghost" href="<?= e(app_url('admission-form.php')) ?>">🎓 Online Admission Form</a>
  </div>

  <footer class="site">
    &copy; <?= date('Y') ?> <?= e($schoolName) ?> — <?= e(APP_TAGLINE) ?><br>
    <span style="font-size:12px">Powered by Weblitex</span>
  </footer>
</div>
<script src="<?= e(app_url('assets/js/app.js')) ?>"></script>
</body>
</html>
