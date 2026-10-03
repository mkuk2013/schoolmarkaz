<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/bootstrap.php';
require_role('admin');
$pdo = db();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $act = $_POST['form_action'] ?? '';
    if ($act === 'save_class') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $section = trim($_POST['section'] ?? 'A') ?: 'A';
        $ctid = (int)($_POST['class_teacher_id'] ?? 0) ?: null;
        if ($name === '') $errors[] = 'Class name is required.';
        $st = $pdo->prepare('SELECT id FROM classes WHERE name=? AND section=? AND id<>? LIMIT 1');
        $st->execute([$name, $section, $id]);
        if ($st->fetch()) $errors[] = 'A class with this name + section already exists.';
        if (!$errors) {
            if ($id > 0) {
                $pdo->prepare('UPDATE classes SET name=?, section=?, class_teacher_id=? WHERE id=?')
                    ->execute([$name, $section, $ctid, $id]);
                flash('success', 'Class updated.');
            } else {
                $pdo->prepare('INSERT INTO classes (name, section, class_teacher_id) VALUES (?,?,?)')
                    ->execute([$name, $section, $ctid]);
                flash('success', 'Class added.');
            }
            redirect('admin/classes.php');
        }
    } elseif ($act === 'delete_class') {
        $id = (int)($_POST['id'] ?? 0);
        $n = (int)$pdo->query('SELECT COUNT(*) FROM students WHERE class_id=' . $id)->fetchColumn();
        if ($n > 0) {
            flash('error', "Cannot delete: $n student(s) are enrolled in this class.");
        } else {
            $pdo->prepare('DELETE FROM subjects WHERE class_id=?')->execute([$id]);
            $pdo->prepare('DELETE FROM classes WHERE id=?')->execute([$id]);
            flash('success', 'Class deleted.');
        }
        redirect('admin/classes.php');
    } elseif ($act === 'save_subject') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $class_id = (int)($_POST['class_id'] ?? 0) ?: null;
        if ($name === '') $errors[] = 'Subject name is required.';
        if (!$errors) {
            if ($id > 0) {
                $pdo->prepare('UPDATE subjects SET name=?, code=?, class_id=? WHERE id=?')
                    ->execute([$name, $code, $class_id, $id]);
                flash('success', 'Subject updated.');
            } else {
                $pdo->prepare('INSERT INTO subjects (name, code, class_id) VALUES (?,?,?)')
                    ->execute([$name, $code, $class_id]);
                flash('success', 'Subject added.');
            }
            redirect('admin/classes.php');
        }
    } elseif ($act === 'delete_subject') {
        $pdo->prepare('DELETE FROM subjects WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
        flash('success', 'Subject deleted.');
        redirect('admin/classes.php');
    }
}

$teachers = $pdo->query("SELECT id, name FROM teachers WHERE status='Active' ORDER BY name")->fetchAll();
$classes = $pdo->query(
    'SELECT c.*, t.name AS teacher_name FROM classes c LEFT JOIN teachers t ON t.id=c.class_teacher_id ORDER BY c.name, c.section'
)->fetchAll();
$subjects = $pdo->query(
    'SELECT s.*, c.name AS class_name, c.section FROM subjects s LEFT JOIN classes c ON c.id=s.class_id ORDER BY s.name'
)->fetchAll();

$editClass = null; $editSubject = null;
if (isset($_GET['edit_class'])) {
    $st = $pdo->prepare('SELECT * FROM classes WHERE id=?');
    $st->execute([(int)$_GET['edit_class']]);
    $editClass = $st->fetch();
}
if (isset($_GET['edit_subject'])) {
    $st = $pdo->prepare('SELECT * FROM subjects WHERE id=?');
    $st->execute([(int)$_GET['edit_subject']]);
    $editSubject = $st->fetch();
}

layout_top('Classes & Subjects', 'classes');
?>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>

<div class="card">
  <div class="card-head"><h3><?= $editClass ? 'Edit Class' : 'Add Class' ?></h3></div>
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" value="save_class">
      <input type="hidden" name="id" value="<?= (int)($editClass['id'] ?? 0) ?>">
      <div class="form-row">
        <div class="field"><label>Class Name *</label>
          <input type="text" name="name" value="<?= e($editClass['name'] ?? '') ?>" placeholder="e.g. Class 5" required></div>
        <div class="field"><label>Section</label>
          <input type="text" name="section" value="<?= e($editClass['section'] ?? 'A') ?>" maxlength="10"></div>
      </div>
      <div class="field"><label>Class Teacher</label>
        <select name="class_teacher_id">
          <option value="">— None —</option>
          <?php foreach ($teachers as $t): ?>
          <option value="<?= (int)$t['id'] ?>" <?= (int)($editClass['class_teacher_id'] ?? 0)===(int)$t['id']?'selected':'' ?>><?= e($t['name']) ?></option>
          <?php endforeach; ?>
        </select></div>
      <button class="btn" type="submit"><?= $editClass ? 'Update' : 'Add' ?> Class</button>
      <?php if ($editClass): ?><a class="btn secondary" href="<?= e(app_url('admin/classes.php')) ?>">Cancel</a><?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-head"><h3>Classes</h3></div>
  <div class="card-body">
    <div class="table-wrap"><table class="table">
      <tr><th>Class</th><th>Section</th><th>Class Teacher</th><th>Students</th><th>Actions</th></tr>
      <?php foreach ($classes as $c): ?>
      <?php $cnt = (int)$pdo->query('SELECT COUNT(*) FROM students WHERE class_id='.(int)$c['id'])->fetchColumn(); ?>
      <tr>
        <td><strong><?= e($c['name']) ?></strong></td>
        <td><?= e($c['section']) ?></td>
        <td><?= e($c['teacher_name'] ?? '—') ?></td>
        <td><span class="badge blue"><?= $cnt ?></span></td>
        <td style="white-space:nowrap">
          <a class="btn small secondary" href="<?= e(app_url('admin/classes.php?edit_class='.$c['id'])) ?>">Edit</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this class and its subjects?')">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="delete_class">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button class="btn small danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$classes): ?><tr><td colspan="5" class="stat-label">No classes yet.</td></tr><?php endif; ?>
    </table></div>
  </div>
</div>

<div class="card">
  <div class="card-head"><h3><?= $editSubject ? 'Edit Subject' : 'Add Subject' ?></h3></div>
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" value="save_subject">
      <input type="hidden" name="id" value="<?= (int)($editSubject['id'] ?? 0) ?>">
      <div class="form-row">
        <div class="field"><label>Subject Name *</label>
          <input type="text" name="name" value="<?= e($editSubject['name'] ?? '') ?>" required></div>
        <div class="field"><label>Code</label>
          <input type="text" name="code" value="<?= e($editSubject['code'] ?? '') ?>" placeholder="e.g. MATH-5"></div>
      </div>
      <div class="field"><label>Class</label>
        <select name="class_id">
          <option value="">— All classes —</option>
          <?php foreach ($classes as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= (int)($editSubject['class_id'] ?? 0)===(int)$c['id']?'selected':'' ?>>
            <?= e($c['name'] . ' (' . $c['section'] . ')') ?></option>
          <?php endforeach; ?>
        </select></div>
      <button class="btn" type="submit"><?= $editSubject ? 'Update' : 'Add' ?> Subject</button>
      <?php if ($editSubject): ?><a class="btn secondary" href="<?= e(app_url('admin/classes.php')) ?>">Cancel</a><?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-head"><h3>Subjects</h3></div>
  <div class="card-body">
    <div class="table-wrap"><table class="table">
      <tr><th>Subject</th><th>Code</th><th>Class</th><th>Actions</th></tr>
      <?php foreach ($subjects as $s): ?>
      <tr>
        <td><strong><?= e($s['name']) ?></strong></td>
        <td><?= e($s['code'] ?: '—') ?></td>
        <td><?= $s['class_name'] ? e($s['class_name'] . ' (' . $s['section'] . ')') : 'All classes' ?></td>
        <td style="white-space:nowrap">
          <a class="btn small secondary" href="<?= e(app_url('admin/classes.php?edit_subject='.$s['id'])) ?>">Edit</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this subject?')">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="delete_subject">
            <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
            <button class="btn small danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$subjects): ?><tr><td colspan="4" class="stat-label">No subjects yet.</td></tr><?php endif; ?>
    </table></div>
  </div>
</div>
<?php layout_bottom(); ?>
