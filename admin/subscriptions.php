<?php
declare(strict_types=1);

/**
 * Admin: review package orders — approve (activates subscription) or reject.
 */
require_once __DIR__.'/../includes/bootstrap.php';
require_role('admin');
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $act = $_POST['form_action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($act === 'approve' && $id > 0) {
        $o = $pdo->prepare('SELECT * FROM package_orders WHERE id=? LIMIT 1');
        $o->execute([$id]);
        $order = $o->fetch();
        if ($order && in_array($order['status'], ['pending','submitted'], true)) {
            $months = $order['billing_cycle'] === 'yearly' ? 12 : 1;
            $starts = date('Y-m-d');
            $ends = date('Y-m-d', strtotime("+{$months} months"));
            $pdo->prepare("UPDATE package_orders SET status='approved', reviewed_at=NOW(), starts_at=?, ends_at=? WHERE id=?")
                ->execute([$starts, $ends, $id]);
            // Mark this as the school's current package.
            $pdo->prepare('UPDATE schools SET package_id=? ORDER BY id ASC LIMIT 1')
                ->execute([(int)$order['package_id']]);
            flash('success', 'Order ' . $order['order_no'] . ' approved — subscription active until ' . fmt_date($ends, 'd M Y') . '.');
        }
    } elseif ($act === 'reject' && $id > 0) {
        $note = trim($_POST['note'] ?? '');
        $pdo->prepare("UPDATE package_orders SET status='rejected', reviewed_at=NOW(), note=? WHERE id=? AND status IN ('pending','submitted')")
            ->execute([$note, $id]);
        flash('success', 'Order rejected.');
    } elseif ($act === 'delete' && $id > 0) {
        $pdo->prepare('DELETE FROM package_orders WHERE id=?')->execute([$id]);
        flash('success', 'Order deleted.');
    }
    redirect('admin/subscriptions.php' . (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
}

$fstatus = $_GET['status'] ?? '';
$where = []; $params = [];
if (in_array($fstatus, ['pending','submitted','approved','rejected'], true)) { $where[] = 'o.status=?'; $params[] = $fstatus; }
$st = $pdo->prepare("SELECT o.*, p.name AS pkg_name FROM package_orders o
    JOIN packages p ON p.id = o.package_id"
    . ($where ? ' WHERE '.implode(' AND ',$where) : '') . ' ORDER BY o.created_at DESC');
$st->execute($params);
$rows = $st->fetchAll();

$counts = $pdo->query("SELECT status, COUNT(*) c FROM package_orders GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$badge = ['pending'=>'amber','submitted'=>'blue','approved'=>'green','rejected'=>'red'];

// Current active subscription
$active = $pdo->query("SELECT o.*, p.name AS pkg_name FROM package_orders o
    JOIN packages p ON p.id = o.package_id
    WHERE o.status='approved' AND o.ends_at >= CURDATE() ORDER BY o.ends_at DESC LIMIT 1")->fetch();

layout_top('Subscriptions', 'subscriptions');
?>
<?php if ($active): ?>
<div class="card" style="margin-bottom:14px;border-left:4px solid var(--green,#16a34a)">
  <div class="card-body" style="display:flex;gap:14px;align-items:center;flex-wrap:wrap">
    <div style="font-size:28px">✅</div>
    <div>
      <strong>Active subscription: <?= e($active['pkg_name']) ?></strong>
      <div style="color:var(--muted);font-size:13px">
        <?= e(fmt_date($active['starts_at'],'d M Y')) ?> → <?= e(fmt_date($active['ends_at'],'d M Y')) ?>
        (<?= max(0, (int)((strtotime($active['ends_at']) - time()) / 86400)) ?> days left)
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-head">
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <?php foreach ([''=>'All','pending'=>'Pending','submitted'=>'Proof Submitted','approved'=>'Approved','rejected'=>'Rejected'] as $k=>$label): ?>
      <a class="btn small <?= $fstatus===$k?'':'secondary' ?>"
         href="<?= e(app_url('admin/subscriptions.php' . ($k ? '?status='.$k : ''))) ?>"><?= $label ?>
         <?= isset($counts[$k]) ? '(' . (int)$counts[$k] . ')' : '' ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card-body">
    <div class="table-wrap"><table class="table">
      <tr><th>Order</th><th>Package</th><th>School / Contact</th><th>Payment</th><th>Proof</th><th>Status</th><th>Actions</th></tr>
      <?php if (!$rows): ?>
      <tr><td colspan="7" style="text-align:center;color:var(--muted)">No orders yet. Share your <a href="<?= e(app_url('subscribe.php')) ?>">subscribe.php</a> link with schools.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><strong><?= e($r['order_no']) ?></strong><br>
          <span class="stat-label"><?= e(fmt_money($r['amount'])) ?> · <?= $r['billing_cycle']==='yearly'?'Yearly':'Monthly' ?><br>
          <?= e(fmt_date($r['created_at'],'d M Y')) ?></span></td>
        <td><?= e($r['pkg_name']) ?></td>
        <td><?= e($r['school_name']) ?><br>
          <span class="stat-label"><?= e($r['contact_name']) ?> · <?= e($r['phone']) ?></span></td>
        <td><?= e($r['payment_method'] ?: '—') ?><br>
          <span class="stat-label"><?= e($r['txn_ref'] ?: '') ?></span></td>
        <td><?php if ($r['proof_path']): ?>
          <a href="<?= e(app_url($r['proof_path'])) ?>" target="_blank">🖼 View</a>
          <?php else: ?>—<?php endif; ?></td>
        <td><span class="badge <?= $badge[$r['status']] ?>"><?= e(ucfirst($r['status'])) ?></span>
          <?php if ($r['status']==='approved'): ?><br><span class="stat-label">till <?= e(fmt_date($r['ends_at'],'d M Y')) ?></span><?php endif; ?></td>
        <td style="white-space:nowrap">
          <?php if (in_array($r['status'], ['pending','submitted'], true)): ?>
          <form method="post" style="display:inline" onsubmit="return confirm('Approve this order and activate the subscription?')">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="approve">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn small" type="submit">✓ Approve</button>
          </form>
          <form method="post" style="display:inline" onsubmit="return confirm('Reject this order?')">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="reject">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn small secondary" type="submit">✕ Reject</button>
          </form>
          <?php endif; ?>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this order?')">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn small secondary" type="submit">🗑</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table></div>
  </div>
</div>
<?php layout_bottom(); ?>
