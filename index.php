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

$svgs = [
    '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.2 2.4 2.4 4.6-5"/>',
    '<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.6"/><path d="M6.5 9.5h.01M17.5 14.5h.01"/>',
    '<circle cx="12" cy="9" r="5.5"/><path d="M8.8 13.5 7 21l5-2.6L17 21l-1.8-7.5"/>',
    '<circle cx="9" cy="8.5" r="3.5"/><path d="M3.5 20c.6-3.2 2.8-5 5.5-5s4.9 1.8 5.5 5"/><path d="M15.5 5.4a3.5 3.5 0 0 1 0 6.2M17.8 15.3c1.6.7 2.5 2.2 2.8 4.7"/>',
    '<path d="M4 4v16h16"/><path d="M8.5 16v-5M12.5 16V8M16.5 16v-3"/>',
    '<rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
    '<path d="M4 8V6a2 2 0 0 1 2-2h2M16 4h2a2 2 0 0 1 2 2v2M20 16v2a2 2 0 0 1-2 2h-2M8 20H6a2 2 0 0 1-2-2v-2"/><path d="M4 12h16"/>',
    '<path d="M6 3h12v18l-2-1.4L14 21l-2-1.4L10 21l-2-1.4L6 21z"/><path d="M9 8h6M9 12h6"/>',
    '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.3A2.6 2.6 0 1 1 12 12.2c-.8.35-1 .9-1 1.8"/><path d="M12 17.2h.01"/>',
    '<path d="M3.5 21h17"/><rect x="6" y="4" width="12" height="17" rx="1"/><path d="M9.5 8h1.6M13 8h1.6M9.5 12h1.6M13 12h1.6M9.5 16h1.6M13 16h1.6"/>',
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
.hero-grid { display: grid; grid-template-columns: 1.02fr .98fr; gap: 38px; align-items: center; text-align: left; }
.hero-copy .hero-cta { justify-content: flex-start; }
.hero-copy .hero-points { justify-content: flex-start; }
.hero-copy p.tagline { margin-left: 0; }
.hero-photo { position: relative; }
.hero-photo > img { width: 100%; height: auto; display: block; border-radius: 20px; border: 1px solid rgba(255,255,255,.35); box-shadow: 0 34px 70px -22px rgba(2,12,40,.65); }
.glass-chip { position: absolute; background: rgba(255,255,255,.94); backdrop-filter: blur(6px); color: var(--text); border-radius: 12px; box-shadow: var(--shadow-lg); padding: 9px 13px; font-size: 13px; font-weight: 600; display: flex; gap: 8px; align-items: center; }
.glass-chip.chip1 { top: 16px; left: -14px; } .glass-chip.chip2 { bottom: 16px; right: -10px; }
@media (max-width: 900px) { .hero-grid { grid-template-columns: 1fr; text-align: center; } .hero-copy .hero-cta, .hero-copy .hero-points { justify-content: center; } .glass-chip.chip1 { left: 8px; } .glass-chip.chip2 { right: 8px; } }
.band { display: grid; grid-template-columns: 1fr 1fr; gap: 0; background: var(--surface); border: 1px solid var(--border); border-radius: 24px; overflow: hidden; box-shadow: var(--shadow); margin: 34px 0 4px; }
.band-photo img { width: 100%; height: 100%; object-fit: cover; display: block; min-height: 320px; }
.band-text { padding: 38px 36px; display: flex; flex-direction: column; justify-content: center; }
.band-k { color: var(--primary); font-weight: 800; font-size: 12.5px; letter-spacing: 2px; text-transform: uppercase; margin: 0 0 6px; }
.band-text h2 { margin: 0 0 10px; font-size: clamp(22px, 3vw, 29px); letter-spacing: -.6px; }
.band-text > p { color: var(--muted); margin: 0 0 14px; font-size: 15px; }
.ticks { list-style: none; margin: 0 0 20px; padding: 0; }
.ticks li { padding: 6px 0 6px 26px; position: relative; font-size: 14.5px; font-weight: 500; }
.ticks li::before { content: "✓"; position: absolute; left: 0; top: 6px; width: 19px; height: 19px; border-radius: 50%; background: var(--grad); color: #fff; font-size: 11.5px; font-weight: 800; display: flex; align-items: center; justify-content: center; }
.band-text .btn { align-self: flex-start; }
@media (max-width: 900px) { .band { grid-template-columns: 1fr; } .band-photo img { min-height: 220px; max-height: 300px; } }
.feat-ico svg { display: block; }
.foot-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 26px; text-align: left; max-width: 1140px; margin: 0 auto; padding: 6px 18px 20px; }
.foot-grid h4 { margin: 4px 0 12px; font-size: 14px; letter-spacing: .3px; }
.foot-grid a { display: block; color: var(--muted); font-size: 13.5px; padding: 3.5px 0; }
.foot-grid a:hover { color: var(--primary); text-decoration: none; }
.foot-brand p { color: var(--muted); font-size: 13.5px; margin: 12px 0 0; max-width: 320px; }
.foot-bottom { border-top: 1px solid var(--border); padding-top: 16px; }
@media (max-width: 900px) { .foot-grid { grid-template-columns: 1fr 1fr; } }
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
    <div class="hero-grid">
      <div class="hero-copy">
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
      </div>
      <div class="hero-photo">
        <img src="<?= e(app_url('assets/img/principal.jpg')) ?>" alt="A principal running his school with School Markaz">
        <div class="glass-chip chip1">🚪 Gate Entry <span class="badge green">Parent Alert Sent</span></div>
        <div class="glass-chip chip2">🧾 Fee Challan <span class="badge blue">PDF Ready</span></div>
      </div>
    </div>
  </div>

  <div class="strip">
    <div class="cell"><strong>🎁 Free Forever Plan</strong><span>Up to 50 students, core modules included</span></div>
    <div class="cell"><strong>📱 Mobile App (PWA)</strong><span>Installs on any phone — no Play Store needed</span></div>
    <div class="cell"><strong>🏢 Multi-Campus</strong><span>All branches in one account, easy transfers</span></div>
    <div class="cell"><strong>🇵🇰 Made for Pakistan</strong><span>Bank challans, PKR billing, local support</span></div>
  </div>

  <div class="band">
    <div class="band-photo"><img src="<?= e(app_url('assets/img/classroom.jpg')) ?>" alt="A teacher using digital tools in a classroom"></div>
    <div class="band-text">
      <p class="band-k">Built For Real Schools</p>
      <h2>Less register work. More teaching.</h2>
      <p>School Markaz was designed with Pakistani schools in mind — simple screens your staff already understands, and everything reachable from the phone in your pocket.</p>
      <ul class="ticks">
        <li>Works on any phone or computer — nothing to install for staff</li>
        <li>Parents stay informed with gate alerts, results and fee receipts</li>
        <li>Your data stays yours — reports and records export anytime</li>
        <li>Local support from the Weblitex team, in your language</li>
      </ul>
      <a class="btn" href="<?= e(app_url('auth/login.php?demo=teacher')) ?>">See the Teacher Portal →</a>
    </div>
  </div>

  <p class="section-k" id="features">Everything You Need</p>
  <h2 class="section-title">One system for the whole school day</h2>
  <p class="section-sub">From the morning gate scan to the monthly fee report — every module works together, so nothing is typed twice.</p>
  <div class="grid grid-3">
    <?php $gi = 0; foreach ($features as $f): $gi++; ?>
    <div class="card" style="margin-bottom:0">
      <div class="card-body feat">
        <div class="feat-ico g<?= (($gi - 1) % 6) + 1 ?>"><svg viewBox="0 0 24 24" width="25" height="25" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $svgs[$gi - 1] ?? '' ?></svg></div>
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

  <div class="cta-band" style="background:linear-gradient(rgba(6,20,50,.84),rgba(5,32,38,.86)),url('<?= e(app_url('assets/img/students.jpg')) ?>') center/cover">
    <h2>Ready to modernize your school?</h2>
    <p>Create your school account in minutes — the Free plan needs no card and no installation.</p>
    <a class="btn white" href="<?= e(app_url('signup.php')) ?>">🏫 Register Your School</a>
    <a class="btn ghost" href="<?= e(app_url('admission-form.php')) ?>">🎓 Online Admission Form</a>
  </div>

  <footer class="site">
    <div class="foot-grid">
      <div class="foot-brand">
        <div class="brandline"><img src="<?= e(app_url('assets/icons/logo.png')) ?>" alt="School Markaz logo"> <?= e($schoolName) ?></div>
        <p><?= e(APP_TAGLINE) ?> — admissions, attendance, fees, results and parents in one simple system, built for schools in Pakistan.</p>
      </div>
      <div>
        <h4>Product</h4>
        <a href="#features">Features</a>
        <a href="#pricing">Pricing</a>
        <a href="#demo">Live Demo</a>
        <a href="<?= e(app_url('signup.php')) ?>">Register a School</a>
      </div>
      <div>
        <h4>Portals</h4>
        <a href="<?= e(app_url('auth/login.php?demo=admin')) ?>">Admin</a>
        <a href="<?= e(app_url('auth/login.php?demo=teacher')) ?>">Teacher</a>
        <a href="<?= e(app_url('auth/login.php?demo=student')) ?>">Student</a>
        <a href="<?= e(app_url('auth/login.php?demo=parent')) ?>">Parent</a>
      </div>
      <div>
        <h4>Get Started</h4>
        <a href="<?= e(app_url('admission-form.php')) ?>">Online Admission</a>
        <a href="<?= e(app_url('auth/login.php')) ?>">Login</a>
        <a href="#demo">Try the Demo</a>
      </div>
    </div>
    <div class="foot-bottom">&copy; <?= date('Y') ?> <?= e($schoolName) ?> — <?= e(APP_TAGLINE) ?> · Powered by Weblitex</div>
  </footer>
</div>
<script src="<?= e(app_url('assets/js/app.js')) ?>"></script>
</body>
</html>
