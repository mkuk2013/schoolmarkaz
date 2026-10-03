<?php
declare(strict_types=1);

/**
 * Teacher → Notices: announcements with audience all/teachers.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';
require_role('teacher');

$notices = db()->query("SELECT title, body, audience, created_at FROM announcements
    WHERE audience IN ('all','teachers') ORDER BY created_at DESC")->fetchAll() ?: [];

layout_top('Notices', 'announcements');
?>
<?php foreach ($notices as $n): ?>
  <div class="card">
    <div class="card-head">
      <h3><?= e($n['title']) ?></h3>
      <span class="badge blue"><?= e(ucfirst($n['audience'])) ?></span>
    </div>
    <div class="card-body">
      <p style="white-space:pre-line"><?= e($n['body']) ?></p>
      <small><?= e(fmt_date($n['created_at'], 'd M Y, h:i A')) ?></small>
    </div>
  </div>
<?php endforeach; ?>
<?php if (!$notices): ?>
  <div class="card"><div class="card-body">No notices yet.</div></div>
<?php endif; ?>

<?php layout_bottom(); ?>
