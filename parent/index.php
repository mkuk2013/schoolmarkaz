<?php
declare(strict_types=1);

/**
 * Parent → My children: cards with attendance %, fee balance, last result %.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';
require_role('parent');

$me = my_profile();
$children = sm_my_children();
$m = (int) date('n');
$y = (int) date('Y');

layout_top('My Children', 'dashboard');
?>

<?php if (!$children): ?>
  <div class="card"><div class="card-body">No children linked to your account yet.</div></div>
<?php endif; ?>

<div class="grid grid-2">
  <?php foreach ($children as $c):
    $att = sm_attendance_month((int) $c['id'], $m, $y);
    $bal = sm_fee_balance((int) $c['id']);
    $pct = sm_latest_result_pct((int) $c['id']);
    $cls = sm_class((int) $c['class_id']);
  ?>
    <div class="card">
      <div class="card-head">
        <h3><?= e($c['name']) ?></h3>
        <span class="badge"><?= e($cls ? $cls['name'] . ' (' . $cls['section'] . ')' : '—') ?></span>
      </div>
      <div class="card-body">
        <div class="grid grid-3">
          <div>
            <div style="font-size:22px;font-weight:800;color:var(--success)">
              <?= $att['pct'] === null ? '—' : $att['pct'] . '%' ?></div>
            <div class="stat-label">Attendance — <?= e(month_name($m)) ?></div>
          </div>
          <div>
            <div style="font-size:22px;font-weight:800;color:<?= $bal > 0 ? 'var(--danger)' : 'var(--success)' ?>">
              <?= e(fmt_money($bal)) ?></div>
            <div class="stat-label">Fee balance</div>
          </div>
          <div>
            <div style="font-size:22px;font-weight:800;color:var(--primary)">
              <?= $pct === null ? '—' : $pct . '%' ?></div>
            <div class="stat-label">Last result</div>
          </div>
        </div>
        <p style="margin:14px 0 0">
          <a class="btn small" href="child.php?id=<?= (int) $c['id'] ?>">View details</a>
        </p>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($me): ?>
<div class="card">
  <div class="card-body">
    <strong><?= e($me['name']) ?></strong> · <?= e($me['phone'] ?: '—') ?>
  </div>
</div>
<?php endif; ?>

<?php layout_bottom(); ?>
