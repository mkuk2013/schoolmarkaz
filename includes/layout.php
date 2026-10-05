<?php
declare(strict_types=1);

/**
 * Shared page layout: sidebar + topbar (desktop), bottom nav (mobile),
 * flash messages, dark-mode toggle, notification bell, print styles.
 */

function nav_svg(string $key): string
{
    static $paths = [
        'dashboard' => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>',
        'students' => '<path d="M2.5 9.5 12 5l9.5 4.5L12 14z"/><path d="M6.5 11.5V16c0 1.5 2.5 3 5.5 3s5.5-1.5 5.5-3v-4.5"/><path d="M21.5 9.5V15"/>',
        'teachers' => '<circle cx="12" cy="8" r="3.6"/><path d="M5 20.5c.8-3.6 3.6-5.4 7-5.4s6.2 1.8 7 5.4"/>',
        'parents' => '<circle cx="9" cy="8.5" r="3.5"/><path d="M3.5 20c.6-3.2 2.8-5 5.5-5s4.9 1.8 5.5 5"/><path d="M15.5 5.4a3.5 3.5 0 0 1 0 6.2M17.8 15.3c1.6.7 2.5 2.2 2.8 4.7"/>',
        'classes' => '<path d="M3.5 21h17"/><path d="M5.5 21V8.8L12 4l6.5 4.8V21"/><path d="M10 21v-4.5h4V21"/><path d="M12 8.5h.01"/>',
        'attendance' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.2 2.4 2.4 4.6-5"/>',
        'gate' => '<path d="M4 8V6a2 2 0 0 1 2-2h2M16 4h2a2 2 0 0 1 2 2v2M20 16v2a2 2 0 0 1-2 2h-2M8 20H6a2 2 0 0 1-2-2v-2"/><path d="M4 12h16"/>',
        'exams' => '<rect x="5.5" y="4.5" width="13" height="17" rx="2"/><path d="M9.5 4.5a2.5 2.5 0 0 1 5 0"/><path d="m9.3 15.3 1.9 1.9 3.6-4"/>',
        'marks' => '<path d="M4 20l1.2-4.2L16.6 4.4a2.15 2.15 0 0 1 3 3L8.2 18.8z"/><path d="M14.6 6.4l3 3"/>',
        'quizzes' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.3A2.6 2.6 0 1 1 12 12.2c-.8.35-1 .9-1 1.8"/><path d="M12 17.2h.01"/>',
        'timetable' => '<rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
        'fees' => '<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.6"/><path d="M6.5 9.5h.01M17.5 14.5h.01"/>',
        'salary' => '<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.6"/><path d="M6.5 9.5h.01M17.5 14.5h.01"/>',
        'challans' => '<path d="M6 3h12v18l-2-1.4L14 21l-2-1.4L10 21l-2-1.4L6 21z"/><path d="M9 8h6M9 12h6"/>',
        'finance' => '<path d="M4 4v16h16"/><path d="M8.5 16v-5M12.5 16V8M16.5 16v-3"/>',
        'reports' => '<path d="M4 4v16h16"/><path d="m7 14.5 4-4 3 3 5.5-6"/>',
        'idcards' => '<rect x="2.5" y="5" width="19" height="14" rx="2"/><circle cx="8.5" cy="11" r="2"/><path d="M5.8 16.6c.5-1.5 1.5-2.3 2.7-2.3s2.2.8 2.7 2.3"/><path d="M15 9.5h4M15 13h4"/>',
        'certificates' => '<circle cx="12" cy="9" r="5.5"/><path d="M8.8 13.5 7 21l5-2.6L17 21l-1.8-7.5"/>',
        'campuses' => '<path d="M3.5 21h17"/><rect x="6" y="4" width="12" height="17" rx="1"/><path d="M9.5 8h1.6M13 8h1.6M9.5 12h1.6M13 12h1.6M9.5 16h1.6M13 16h1.6"/>',
        'announcements' => '<path d="M3.5 10.5v3A1.5 1.5 0 0 0 5 15h1.5l8 4.5v-15L6.5 9H5a1.5 1.5 0 0 0-1.5 1.5Z"/><path d="M17.5 9.5a3.5 3.5 0 0 1 0 5"/>',
        'inquiries' => '<rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="m3.5 7 8.5 6 8.5-6"/>',
        'subscriptions' => '<rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M2.5 9.5h19"/><path d="M6 14.5h4"/>',
        'settings' => '<circle cx="12" cy="12" r="3.2"/><path d="M12 2.8v3M12 18.2v3M2.8 12h3M18.2 12h3M5.2 5.2l2.1 2.1M16.7 16.7l2.1 2.1M18.8 5.2l-2.1 2.1M7.3 16.7l-2.1 2.1"/>',
        'results' => '<path d="M8 4h8v5a4 4 0 0 1-8 0z"/><path d="M8 5.8H5.2a2.9 2.9 0 0 0 2.9 3.4M16 5.8h2.8a2.9 2.9 0 0 1-2.9 3.4"/><path d="M12 13v3M8.5 20.5h7M10 16h4"/>',
        'alerts' => '<path d="M18 16H6c.8-1.2 1.2-2.5 1.2-4.2A4.8 4.8 0 0 1 12 6.7a4.8 4.8 0 0 1 4.8 4.8c0 1.7.4 3 1.2 4.2Z"/><path d="M10.3 19.5a1.9 1.9 0 0 0 3.4 0"/><path d="M12 3.8v1"/>',
        'logout' => '<path d="M12 3v8"/><path d="M6.3 6.5a8 8 0 1 0 11.4 0"/>',
        'moon' => '<path d="M20 14.5A8.5 8.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5Z"/>',
        'install' => '<path d="M12 4v10.5M7.5 10.5 12 15l4.5-4.5"/><path d="M4.5 19.5h15"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
    ];
    $d = $paths[$key] ?? $paths['dashboard'];
    return '<svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
}

function nav_items(): array
{
    $role = user_role();
    if ($role === 'admin') {
        return [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'url' => 'admin/index.php', 'icon' => '▦', 'group' => 'Overview'],
            ['key' => 'students', 'label' => 'Students', 'url' => 'admin/students.php', 'icon' => '🎒', 'group' => 'People'],
            ['key' => 'teachers', 'label' => 'Teachers', 'url' => 'admin/teachers.php', 'icon' => '👩‍🏫', 'group' => 'People'],
            ['key' => 'parents', 'label' => 'Parents', 'url' => 'admin/parents.php', 'icon' => '👪', 'group' => 'People'],
            ['key' => 'classes', 'label' => 'Classes', 'url' => 'admin/classes.php', 'icon' => '🏫', 'group' => 'Academics'],
            ['key' => 'attendance', 'label' => 'Attendance', 'url' => 'admin/attendance.php', 'icon' => '✓', 'group' => 'Academics'],
            ['key' => 'gate', 'label' => 'Gate Attendance', 'url' => 'admin/gate.php', 'icon' => '🚪', 'group' => 'Academics'],
            ['key' => 'exams', 'label' => 'Exams & Marks', 'url' => 'admin/exams.php', 'icon' => '📝', 'group' => 'Academics'],
            ['key' => 'quizzes', 'label' => 'Quizzes', 'url' => 'admin/quizzes.php', 'icon' => '❓', 'group' => 'Academics'],
            ['key' => 'timetable', 'label' => 'Timetable', 'url' => 'admin/timetable.php', 'icon' => '🗓', 'group' => 'Academics'],
            ['key' => 'fees', 'label' => 'Fees', 'url' => 'admin/fees.php', 'icon' => '💰', 'group' => 'Finance'],
            ['key' => 'challans', 'label' => 'Bank Challans', 'url' => 'admin/challans.php', 'icon' => '🧾', 'group' => 'Finance'],
            ['key' => 'finance', 'label' => 'Finance', 'url' => 'admin/finance.php', 'icon' => '📊', 'group' => 'Finance'],
            ['key' => 'reports', 'label' => 'Reports', 'url' => 'admin/reports.php', 'icon' => '📈', 'group' => 'Finance'],
            ['key' => 'idcards', 'label' => 'ID Cards', 'url' => 'admin/idcards.php', 'icon' => '🪪', 'group' => 'Documents'],
            ['key' => 'certificates', 'label' => 'Certificates', 'url' => 'admin/certificates.php', 'icon' => '📜', 'group' => 'Documents'],
            ['key' => 'campuses', 'label' => 'Campuses', 'url' => 'admin/campuses.php', 'icon' => '🏢', 'group' => 'School'],
            ['key' => 'announcements', 'label' => 'Notices', 'url' => 'admin/announcements.php', 'icon' => '📢', 'group' => 'School'],
            ['key' => 'inquiries', 'label' => 'Admissions', 'url' => 'admin/inquiries.php', 'icon' => '✉', 'group' => 'School'],
            ['key' => 'subscriptions', 'label' => 'Subscriptions', 'url' => 'admin/subscriptions.php', 'icon' => '💳', 'group' => 'School'],
            ['key' => 'settings', 'label' => 'Settings', 'url' => 'admin/settings.php', 'icon' => '⚙', 'group' => 'School'],
        ];
    }
    if ($role === 'teacher') {
        return [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'url' => 'teacher/index.php', 'icon' => '▦'],
            ['key' => 'attendance', 'label' => 'Mark Attendance', 'url' => 'teacher/attendance.php', 'icon' => '✓'],
            ['key' => 'marks', 'label' => 'Enter Marks', 'url' => 'teacher/marks.php', 'icon' => '📝'],
            ['key' => 'quizzes', 'label' => 'Quizzes', 'url' => 'teacher/quizzes.php', 'icon' => '❓'],
            ['key' => 'timetable', 'label' => 'My Timetable', 'url' => 'teacher/timetable.php', 'icon' => '🗓'],
            ['key' => 'salary', 'label' => 'Salary Slips', 'url' => 'teacher/salary.php', 'icon' => '💰'],
            ['key' => 'announcements', 'label' => 'Notices', 'url' => 'teacher/announcements.php', 'icon' => '📢'],
        ];
    }
    if ($role === 'student') {
        return [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'url' => 'student/index.php', 'icon' => '▦'],
            ['key' => 'attendance', 'label' => 'My Attendance', 'url' => 'student/attendance.php', 'icon' => '✓'],
            ['key' => 'results', 'label' => 'My Results', 'url' => 'student/results.php', 'icon' => '🏆'],
            ['key' => 'quizzes', 'label' => 'Quizzes', 'url' => 'student/quizzes.php', 'icon' => '❓'],
            ['key' => 'fees', 'label' => 'My Fees', 'url' => 'student/fees.php', 'icon' => '💰'],
            ['key' => 'timetable', 'label' => 'Timetable', 'url' => 'student/timetable.php', 'icon' => '🗓'],
            ['key' => 'announcements', 'label' => 'Notices', 'url' => 'student/announcements.php', 'icon' => '📢'],
        ];
    }
    if ($role === 'parent') {
        return [
            ['key' => 'dashboard', 'label' => 'My Children', 'url' => 'parent/index.php', 'icon' => '👪'],
            ['key' => 'alerts', 'label' => 'Gate Alerts', 'url' => 'parent/alerts.php', 'icon' => '🚪'],
            ['key' => 'fees', 'label' => 'Fee Receipts', 'url' => 'parent/fees.php', 'icon' => '💰'],
            ['key' => 'announcements', 'label' => 'Notices', 'url' => 'parent/announcements.php', 'icon' => '📢'],
        ];
    }
    return [];
}

/**
 * Count of announcements in the last 7 days visible to the current role.
 */
function recent_notice_count(): int
{
    $role = user_role();
    if (!$role) {
        return 0;
    }
    $aud = ['all', $role . 's'];
    if ($role === 'parent') {
        $aud[] = 'parents';
    }
    $in = implode(',', array_fill(0, count($aud), '?'));
    $st = db()->prepare("SELECT COUNT(*) FROM announcements
        WHERE audience IN ($in) AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $st->execute($aud);
    return (int) $st->fetchColumn();
}

function layout_top(string $title, string $active = ''): void
{
    $user = current_user();
    $items = nav_items();
    $base = app_url();
    $school = school_profile();
    $brand = $school['name'] ?? APP_NAME;
    $noticeCount = $user ? recent_notice_count() : 0;
    $roleLabel = ucfirst((string) ($user['role'] ?? ''));
    $pendingSubs = 0;
    if ($user && $user['role'] === 'admin') {
        try { $pendingSubs = (int) db()->query("SELECT COUNT(*) FROM package_orders WHERE status IN ('pending','submitted')")->fetchColumn(); }
        catch (Throwable $e) { $pendingSubs = 0; }
    }
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0f172a">
<title><?= e($title) ?> — <?= e($brand) ?></title>
<link rel="icon" href="<?= e($base) ?>/assets/icons/logo.png" type="image/png">
<link rel="manifest" href="<?= e($base) ?>/manifest.webmanifest">
<link rel="apple-touch-icon" href="<?= e($base) ?>/assets/icons/icon-192.png">
<link rel="stylesheet" href="<?= e($base) ?>/assets/css/style.css?v=4">
<style>.nav-icon svg,.bottom-icon svg,.icon-btn svg,.hamburger svg{display:block}.nav-icon,.bottom-icon{display:inline-flex;align-items:center;justify-content:center}</style>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
<script>try{if(localStorage.getItem('sm-theme')==='dark'){document.documentElement.dataset.theme='dark';}}catch(e){}</script>
</head>
<body>
<div class="app">
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <img class="brand-mark" src="<?= e($base) ?>/assets/icons/logo.png" alt="School Markaz logo">
      <div class="brand-text">
        <strong><?= e($brand) ?></strong>
        <span><?= e(APP_TAGLINE) ?></span>
      </div>
    </div>
    <nav class="nav">
      <?php $lastGroup = null; foreach ($items as $it): ?>
        <?php if (!empty($it['group']) && $it['group'] !== $lastGroup): $lastGroup = $it['group']; ?>
        <div class="nav-group"><?= e($it['group']) ?></div>
        <?php endif; ?>
        <a href="<?= e($base . '/' . $it['url']) ?>"
           class="nav-link<?= $active === $it['key'] ? ' active' : '' ?>">
          <span class="nav-icon"><?= nav_svg($it['key']) ?></span><?= e($it['label']) ?>
          <?php if ($it['key'] === 'subscriptions' && $pendingSubs > 0): ?><span class="notif-badge"><?= $pendingSubs ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
      <a href="<?= e($base) ?>/auth/logout.php" class="nav-link"><span class="nav-icon"><?= nav_svg('logout') ?></span>Logout</a>
    </div>
  </aside>

  <div class="main">
    <header class="topbar">
      <button class="hamburger" id="hamburger" aria-label="Menu"><?= nav_svg('menu') ?></button>
      <h1 class="page-title"><?= e($title) ?></h1>
      <div class="topbar-actions">
        <button class="icon-btn" id="installBtn" aria-label="Install app" title="Install app" style="display:none"><?= nav_svg('install') ?></button>
        <button class="icon-btn" id="theme-toggle" aria-label="Toggle dark mode" title="Dark mode"><?= nav_svg('moon') ?></button>
        <?php if ($user): ?>
        <a class="icon-btn" href="<?= e($base . '/' . $user['role'] . '/announcements.php') ?>" aria-label="Notices" title="Notices"><?= nav_svg('alerts') ?><?php if ($noticeCount > 0): ?><span class="notif-badge"><?= $noticeCount ?></span><?php endif; ?></a>
        <div class="user-chip">
          <span class="avatar"><?= e(strtoupper(substr((string) ($user['username'] ?? 'U'), 0, 1))) ?></span>
          <span class="user-name"><?= e($user['username'] ?? '') ?></span>
          <span class="badge"><?= e($roleLabel) ?></span>
        </div>
        <?php endif; ?>
      </div>
    </header>

    <main class="content">
      <?php foreach (get_flashes() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
      <?php endforeach; ?>
    <?php
}

function layout_bottom(): void
{
    $items = nav_items();
    $base = app_url();
    $mobile = array_slice($items, 0, 5);
    ?>
    </main>
  </div>
</div>

<nav class="bottom-nav">
  <?php foreach ($mobile as $it): ?>
    <a href="<?= e($base . '/' . $it['url']) ?>" class="bottom-link">
      <span class="bottom-icon"><?= nav_svg($it['key']) ?></span>
      <span class="bottom-label"><?= e($it['label']) ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<script src="<?= e($base) ?>/assets/js/app.js"></script>
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('<?= e($base) ?>/sw.js').catch(function () {});
}
(function () {
  var btn = document.getElementById('installBtn');
  if (!btn) return;
  var deferred = null;
  var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
  if (!standalone) btn.style.display = '';
  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferred = e;
    btn.style.display = '';
  });
  btn.addEventListener('click', function () {
    if (deferred) {
      deferred.prompt();
      deferred.userChoice.then(function () { deferred = null; });
    } else if (/iphone|ipad|ipod/i.test(navigator.userAgent)) {
      alert('Install karne ke liye: Share button dabayein, phir "Add to Home Screen" select karein.');
    } else {
      alert('Install karne ke liye: browser menu (⋮) kholein, phir "Install app" / "Add to Home screen" select karein.');
    }
  });
  window.addEventListener('appinstalled', function () { btn.style.display = 'none'; });
})();
</script>
</body>
</html>
<?php
}
