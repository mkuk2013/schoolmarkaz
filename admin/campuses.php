<?php
declare(strict_types=1);
/**
 * Campuses — manage school branches. Students, teachers and classes can be
 * assigned to a campus from their own add/edit forms; changing a student's
 * campus there is also how transfers between campuses are done.
 * navkey: 'campuses'
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$pdo = db();
ensure_main_campus();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $act = $_POST['form_action'] ?? '';
    if ($act === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if ($name === '') {
            $errors[] = 'Campus name is required.';
        }
        if (!$errors) {
            if ($id > 0) {
                $pdo->prepare('UPDATE campuses SET name=?, address=?, phone=? WHERE id=?')
                    ->execute([$name, $address, $phone, $id]);
                flash('success', 'Campus updated.');
            } else {
                $pdo->prepare('INSERT INTO campuses (name, address, phone) VALUES (?,?,?)')
                    ->execute([$name, $address, $phone]);
                flash('success', 'Campus added.');
            }
            redirect('admin/campuses.php');
        }
    } elseif ($act === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $used = 0;
        foreach (['students', 'teachers', 'classes'] as $t) {
            $used += (int) $pdo->query("SELECT COUNT(*) FROM $t WHERE campus_id = $id")->fetchColumn();
        }
        $total = (int) $pdo->query('SELECT COUNT(*) FROM campuses')->fetchColumn();
        if ($total <= 1) {
            flash('error', 'Cannot delete the only campus.');
        } elseif ($used > 0) {
            flash('error', "Cannot delete: $used record(s) are assigned to this campus. Reassign them first.");
        } else {
            $pdo->prepare('DELETE FROM campuses WHERE id=?')->execute([$id]);
            flash('success', 'Campus deleted.');
        }
        redirect('admin/campuses.php');
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM campuses WHERE id=?');
    $st->execute([(int) $_GET['edit']]);
    $edit = $st->fetch() ?: null;
}

$rows = $pdo->query(
    'SELECT cp.*,
        (SELECT COUNT(*) FROM students s WHERE s.campus_id = cp.id AND s.status = \'Active\') AS n_students,
        (SELECT COUNT(*) FROM teachers t WHERE t.campus_id = cp.id AND t.status = \'Active\') AS n_teachers,
        (SELECT COUNT(*) FROM classes cl WHERE cl.campus_id = cp.id) AS n_classes
     FROM campuses cp ORDER BY cp.name'
)->fetchAll();
$unassigned = (int) $pdo->query("SELECT COUNT(*) FROM students WHERE campus_id IS NULL AND status='Active'")->fetchColumn();

layout_top('Campuses', 'campuses');
?>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>

<div class="card">
  <div class="card-head"><h3><?= $edit ? 'Edit Campus' : 'Add Campus' ?></h3></div>
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" value="save">
      <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
      <div class="form-row">
        <div class="field"><label>Campus Name *</label>
          <input type="text" name="name" value="<?= e($edit['name'] ?? '') ?>" placeholder="e.g. Boys Campus" required></div>
        <div class="field"><label>Phone</label>
          <input type="tel" name="phone" value="<?= e($edit['phone'] ?? '') ?>"></div>
      </div>
      <div class="field"><label>Address</label>
        <input type="text" name="address" value="<?= e($edit['address'] ?? '') ?>"></div>
      <button class="btn" type="submit"><?= $edit ? 'Update' : 'Add' ?> Campus</button>
      <?php if ($edit): ?><a class="btn secondary" href="<?= e(app_url('admin/campuses.php')) ?>">Cancel</a><?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-head"><h3>All Campuses</h3></div>
  <div class="card-body">
    <div class="table-wrap"><table class="table">
      <tr><th>Campus</th><th>Address</th><th>Phone</th><th>Students</th><th>Teachers</th><th>Classes</th><th>Actions</th></tr>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><strong><?= e($r['name']) ?></strong></td>
        <td><?= e($r['address'] ?: '—') ?></td>
        <td><?= e($r['phone'] ?: '—') ?></td>
        <td><?= (int) $r['n_students'] ?></td>
        <td><?= (int) $r['n_teachers'] ?></td>
        <td><?= (int) $r['n_classes'] ?></td>
        <td style="white-space:nowrap">
          <a class="btn small secondary" href="<?= e(app_url('admin/campuses.php')) ?>?edit=<?= (int) $r['id'] ?>">Edit</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this campus?')">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button class="btn small danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table></div>
    <p class="hint" style="margin-top:10px">
      Assign students / teachers / classes to a campus from their Edit forms.
      Moving a student to another campus there = campus transfer.
      <?php if ($unassigned > 0): ?>Currently <?= $unassigned ?> active student(s) have no campus assigned.<?php endif; ?>
    </p>
  </div>
</div>
<?php layout_bottom(); ?>
