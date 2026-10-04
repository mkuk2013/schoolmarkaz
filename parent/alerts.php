<?php
declare(strict_types=1);
/**
 * Parent → Gate Alerts: entry/exit alerts for the parent's children
 * (from the notification outbox — also sent by SMS when configured).
 * navkey: 'alerts'
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('parent');

$children = sm_my_children();
$names = [];
foreach ($children as $c) {
    $names[(int) $c['id']] = $c['name'];
}

$alerts = [];
if ($names) {
    $in = implode(',', array_fill(0, count($names), '?'));
    $st = db()->prepare(
        "SELECT * FROM notification_log WHERE student_id IN ($in) ORDER BY id DESC LIMIT 50"
    );
    $st->execute(array_keys($names));
    $alerts = $st->fetchAll();
}

layout_top('Gate Alerts', 'alerts');
?>
<div class="card">
  <div class="card-head"><h2>🚪 Gate Alerts</h2></div>
  <div class="card-body">
    <?php if (!$children): ?>
      <p class="stat-label">No children linked to your account yet.</p>
    <?php elseif (!$alerts): ?>
      <p class="stat-label">No gate alerts yet. You will see your child's entry and exit alerts here.</p>
    <?php else: ?>
      <?php foreach ($alerts as $a): ?>
        <div style="padding:10px 0;border-bottom:1px solid var(--border)">
          <div><?= e($a['message']) ?></div>
          <small style="color:var(--muted)">
            <?= e($names[(int) $a['student_id']] ?? '') ?> ·
            <?= e(fmt_date(substr((string) $a['created_at'], 0, 10))) ?> <?= e(date('h:i A', strtotime((string) $a['created_at']))) ?> ·
            <?= $a['status'] === 'sent' ? 'SMS sent' : 'Portal alert' ?>
          </small>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
<?php layout_bottom(); ?>
