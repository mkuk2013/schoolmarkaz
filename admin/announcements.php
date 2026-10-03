<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/bootstrap.php';
require_role('admin');
$pdo = db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $act = $_POST['form_action'] ?? '';
    if ($act === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $audience = $_POST['audience'] ?? 'all';
        if ($title === '') $errors[] = 'Title is required.';
        if ($body === '') $errors[] = 'Body is required.';
        if (!in_array($audience, ['all','teachers','students','parents'], true)) $audience = 'all';
        if (!$errors) {
            if ($id > 0) {
                $pdo->prepare('UPDATE announcements SET title=?, body=?, audience=? WHERE id=?')
                    ->execute([$title, $body, $audience, $id]);
                flash('success', 'Announcement updated.');
            } else {
                $pdo->prepare('INSERT INTO announcements (title, body, audience, created_by) VALUES (?,?,?,?)')
                    ->execute([$title, $body, $audience, current_user()['id'] ?? null]);
                flash('success', 'Announcement published.');
            }
            redirect('admin/announcements.php');
        }
    } elseif ($act === 'delete') {
        $pdo->prepare('DELETE FROM announcements WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
        flash('success', 'Announcement deleted.');
        redirect('admin/announcements.php');
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM announcements WHERE id=?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch();
}

$rows = $pdo->query('SELECT * FROM announcements ORDER BY created_at DESC')->fetchAll();

layout_top('Notices', 'announcements');
?>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>

<div class="card">
  <div class="card-head"><h3><?= $edit ? 'Edit' : 'New' ?> Announcement</h3></div>
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" value="save">
      <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
      <div class="form-row">
        <div class="field"><label>Title *</label>
          <input type="text" name="title" value="<?= e($edit['title'] ?? '') ?>" maxlength="160" required></div>
        <div class="field"><label>Audience</label>
          <select name="audience">
            <?php foreach (['all'=>'Everyone','teachers'=>'Teachers','students'=>'Students','parents'=>'Parents'] as $k=>$label): ?>
            <option value="<?= $k ?>" <?= ($edit['audience'] ?? 'all')===$k?'selected':'' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select></div>
      </div>
      <div class="field"><label>Body *</label>
        <textarea name="body" rows="5" required><?= e($edit['body'] ?? '') ?></textarea></div>
      <button class="btn" type="submit"><?= $edit ? 'Update' : 'Publish' ?></button>
      <?php if ($edit): ?><a class="btn secondary" href="<?= e(app_url('admin/announcements.php')) ?>">Cancel</a><?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-head"><h3>All Announcements</h3></div>
  <div class="card-body">
    <div class="table-wrap"><table class="table">
      <tr><th>Title</th><th>Audience</th><th>Published</th><th>Actions</th></tr>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><strong><?= e($r['title']) ?></strong><br><span class="stat-label"><?= e(mb_strimwidth($r['body'], 0, 100, '…')) ?></span></td>
        <td><span class="badge blue"><?= e(ucfirst($r['audience'])) ?></span></td>
        <td><?= e(fmt_date($r['created_at'], 'd M Y')) ?></td>
        <td style="white-space:nowrap">
          <a class="btn small secondary" href="<?= e(app_url('admin/announcements.php?edit='.$r['id'])) ?>">Edit</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this announcement?')">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn small danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="4" class="stat-label">No announcements yet.</td></tr><?php endif; ?>
    </table></div>
  </div>
</div>
<?php layout_bottom(); ?>
