<?php
declare(strict_types=1);

/**
 * Shared page layout: sidebar + topbar (desktop), bottom nav (mobile),
 * flash messages, dark-mode toggle, notification bell, print styles.
 */

function nav_items(): array
{
    $role = user_role();
    if ($role === 'admin') {
        return [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'url' => 'admin/index.php', 'icon' => '▦'],
            ['key' => 'students', 'label' => 'Students', 'url' => 'admin/students.php', 'icon' => '🎒'],
            ['key' => 'teachers', 'label' => 'Teachers', 'url' => 'admin/teachers.php', 'icon' => '👩‍🏫'],
            ['key' => 'parents', 'label' => 'Parents', 'url' => 'admin/parents.php', 'icon' => '👪'],
            ['key' => 'classes', 'label' => 'Classes', 'url' => 'admin/classes.php', 'icon' => '🏫'],
            ['key' => 'campuses', 'label' => 'Campuses', 'url' => 'admin/campuses.php', 'icon' => '🏢'],
            ['key' => 'attendance', 'label' => 'Attendance', 'url' => 'admin/attendance.php', 'icon' => '✓'],
            ['key' => 'gate', 'label' => 'Gate Attendance', 'url' => 'admin/gate.php', 'icon' => '🚪'],
            ['key' => 'fees', 'label' => 'Fees', 'url' => 'admin/fees.php', 'icon' => '💰'],
            ['key' => 'challans', 'label' => 'Bank Challans', 'url' => 'admin/challans.php', 'icon' => '🧾'],
            ['key' => 'exams', 'label' => 'Exams & Marks', 'url' => 'admin/exams.php', 'icon' => '📝'],
            ['key' => 'quizzes', 'label' => 'Quizzes', 'url' => 'admin/quizzes.php', 'icon' => '❓'],
            ['key' => 'idcards', 'label' => 'ID Cards', 'url' => 'admin/idcards.php', 'icon' => '🪪'],
            ['key' => 'certificates', 'label' => 'Certificates', 'url' => 'admin/certificates.php', 'icon' => '📜'],
            ['key' => 'timetable', 'label' => 'Timetable', 'url' => 'admin/timetable.php', 'icon' => '🗓'],
            ['key' => 'finance', 'label' => 'Finance', 'url' => 'admin/finance.php', 'icon' => '📊'],
            ['key' => 'announcements', 'label' => 'Notices', 'url' => 'admin/announcements.php', 'icon' => '📢'],
            ['key' => 'inquiries', 'label' => 'Admissions', 'url' => 'admin/inquiries.php', 'icon' => '✉'],
            ['key' => 'subscriptions', 'label' => 'Subscriptions', 'url' => 'admin/subscriptions.php', 'icon' => '💳'],
            ['key' => 'reports', 'label' => 'Reports', 'url' => 'admin/reports.php', 'icon' => '📈'],
            ['key' => 'settings', 'label' => 'Settings', 'url' => 'admin/settings.php', 'icon' => '⚙'],
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
<link rel="stylesheet" href="<?= e($base) ?>/assets/css/style.css?v=3">
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
      <?php foreach ($items as $it): ?>
        <a href="<?= e($base . '/' . $it['url']) ?>"
           class="nav-link<?= $active === $it['key'] ? ' active' : '' ?>">
          <span class="nav-icon"><?= $it['icon'] ?></span><?= e($it['label']) ?>
          <?php if ($it['key'] === 'subscriptions' && $pendingSubs > 0): ?><span class="notif-badge"><?= $pendingSubs ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-foot">
      <a href="<?= e($base) ?>/auth/logout.php" class="nav-link">⏻ Logout</a>
    </div>
  </aside>

  <div class="main">
    <header class="topbar">
      <button class="hamburger" id="hamburger" aria-label="Menu">☰</button>
      <h1 class="page-title"><?= e($title) ?></h1>
      <div class="topbar-actions">
        <button class="icon-btn" id="installBtn" aria-label="Install app" title="Install app" style="display:none">📲</button>
        <button class="icon-btn" id="theme-toggle" aria-label="Toggle dark mode" title="Dark mode">🌙</button>
        <?php if ($user): ?>
        <a class="icon-btn" href="<?= e($base . '/' . $user['role'] . '/announcements.php') ?>" aria-label="Notices" title="Notices">🔔<?php if ($noticeCount > 0): ?><span class="notif-badge"><?= $noticeCount ?></span><?php endif; ?></a>
        <div class="user-chip">
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
      <span class="bottom-icon"><?= $it['icon'] ?></span>
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
