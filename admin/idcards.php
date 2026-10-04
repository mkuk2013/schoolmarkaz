<?php
declare(strict_types=1);
/**
 * ID Cards — choose students / staff and print PDF ID cards (idcard.php).
 * Student photos for the cards are uploaded here (stored in students.photo).
 * Note: print forms and per-row photo forms are siblings (never nested);
 * print checkboxes/buttons join their form via the HTML form attribute.
 * navkey: 'idcards'
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$pdo = db();
$errors = [];

/* ---------- Photo upload ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'photo') {
    verify_csrf();
    $sid = (int) ($_POST['student_id'] ?? 0);
    $f = $_FILES['photo'] ?? null;
    if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Please choose a photo.';
    } elseif ($f['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Upload failed (error code ' . (int) $f['error'] . ').';
    } elseif ($f['size'] > 2 * 1024 * 1024) {
        $errors[] = 'Photo must be 2 MB or smaller.';
    } else {
        $info = @getimagesize($f['tmp_name']);
        $mime = is_array($info) ? ($info['mime'] ?? '') : '';
        $map = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($map[$mime])) {
            $errors[] = 'Only JPG and PNG photos are allowed.';
        } else {
            $dir = __DIR__ . '/../uploads/photos';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $file = 'photo_' . $sid . '_' . bin2hex(random_bytes(6)) . '.' . $map[$mime];
            if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $file)) {
                $errors[] = 'Could not save the photo.';
            } else {
                $old = $pdo->prepare('SELECT photo FROM students WHERE id = ?');
                $old->execute([$sid]);
                $oldPath = (string) ($old->fetchColumn() ?: '');
                $pdo->prepare('UPDATE students SET photo = ? WHERE id = ?')
                    ->execute(['uploads/photos/' . $file, $sid]);
                if ($oldPath !== '' && str_starts_with($oldPath, 'uploads/photos/') && is_file(__DIR__ . '/../' . $oldPath)) {
                    @unlink(__DIR__ . '/../' . $oldPath);
                }
                flash('success', 'Photo saved.');
                redirect('admin/idcards.php?class_id=' . (int) ($_POST['class_id'] ?? 0));
            }
        }
    }
}

$fClass = isset($_GET['class_id']) && $_GET['class_id'] !== '' ? (int) $_GET['class_id'] : 0;
$classes = $pdo->query('SELECT id, name, section FROM classes ORDER BY name, section')->fetchAll();

$sql = 'SELECT s.id, s.name, s.admission_no, s.photo, s.section, c.name AS class_name
        FROM students s LEFT JOIN classes c ON c.id = s.class_id
        WHERE s.status = \'Active\'';
$params = [];
if ($fClass > 0) {
    $sql .= ' AND s.class_id = ?';
    $params[] = $fClass;
}
$sql .= ' ORDER BY s.name LIMIT 500';
$st = $pdo->prepare($sql);
$st->execute($params);
$students = $st->fetchAll();

$teachers = $pdo->query("SELECT id, name, staff_no, subject FROM teachers WHERE status = 'Active' ORDER BY name")->fetchAll();

layout_top('ID Cards', 'idcards');
?>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>

<form id="printStudents" method="post" action="<?= e(app_url('admin/idcard.php')) ?>" target="_blank">
  <?= csrf_field() ?><input type="hidden" name="kind" value="student">
</form>
<form id="printStaff" method="post" action="<?= e(app_url('admin/idcard.php')) ?>" target="_blank">
  <?= csrf_field() ?><input type="hidden" name="kind" value="staff">
</form>

<div class="card">
  <div class="card-head"><h2>🪪 Student ID Cards</h2></div>
  <div class="card-body">
    <form method="get" style="margin-bottom:14px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <label>Class</label>
      <select name="class_id" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px">
        <option value="">All classes</option>
        <?php foreach ($classes as $c): ?>
          <option value="<?= (int) $c['id'] ?>"<?= (int) $c['id'] === $fClass ? ' selected' : '' ?>><?= e($c['name'] . ' ' . $c['section']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn small secondary" type="submit">Filter</button>
    </form>

    <div class="table-wrap"><table class="table">
      <tr><th><input type="checkbox" id="checkAllStudents"></th><th>Photo</th><th>Student</th><th>Class</th><th>Upload Photo</th></tr>
      <?php foreach ($students as $s): ?>
      <tr>
        <td><input type="checkbox" form="printStudents" name="ids[]" value="<?= (int) $s['id'] ?>" class="stu-check"></td>
        <td>
          <?php if (!empty($s['photo'])): ?>
            <img src="<?= e(app_url('/')) ?>/<?= e($s['photo']) ?>" alt="" style="width:34px;height:42px;object-fit:cover;border-radius:4px;border:1px solid var(--border)">
          <?php else: ?>
            <span class="badge red">No photo</span>
          <?php endif; ?>
        </td>
        <td><strong><?= e($s['name']) ?></strong><br><small style="color:var(--muted)"><?= e($s['admission_no']) ?></small></td>
        <td><?= e(trim(($s['class_name'] ?? '') . ' ' . $s['section'])) ?></td>
        <td>
          <form method="post" enctype="multipart/form-data" style="display:flex;gap:6px;align-items:center">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="photo">
            <input type="hidden" name="student_id" value="<?= (int) $s['id'] ?>">
            <input type="hidden" name="class_id" value="<?= $fClass ?>">
            <input type="file" name="photo" accept="image/jpeg,image/png" required style="max-width:170px">
            <button class="btn small secondary" type="submit">Save</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$students): ?><tr><td colspan="5" class="stat-label">No students found.</td></tr><?php endif; ?>
    </table></div>
    <?php if ($students): ?>
      <div style="margin-top:14px">
        <button class="btn" type="submit" form="printStudents">🖨 Print Selected ID Cards</button>
        <span class="hint">8 cards per A4 page — cut along the borders. The barcode carries the admission number for gate scanning.</span>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-head"><h2>👩‍🏫 Staff ID Cards</h2></div>
  <div class="card-body">
    <div class="table-wrap"><table class="table">
      <tr><th><input type="checkbox" id="checkAllStaff"></th><th>Teacher</th><th>Staff No</th><th>Subject</th></tr>
      <?php foreach ($teachers as $t): ?>
      <tr>
        <td><input type="checkbox" form="printStaff" name="ids[]" value="<?= (int) $t['id'] ?>" class="staff-check"></td>
        <td><strong><?= e($t['name']) ?></strong></td>
        <td><?= e($t['staff_no']) ?></td>
        <td><?= e($t['subject'] ?: '—') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$teachers): ?><tr><td colspan="4" class="stat-label">No teachers found.</td></tr><?php endif; ?>
    </table></div>
    <?php if ($teachers): ?>
      <div style="margin-top:14px"><button class="btn" type="submit" form="printStaff">🖨 Print Selected Staff Cards</button></div>
    <?php endif; ?>
  </div>
</div>

<script>
document.getElementById('checkAllStudents')?.addEventListener('change', function () {
  document.querySelectorAll('.stu-check').forEach(cb => { cb.checked = this.checked; });
});
document.getElementById('checkAllStaff')?.addEventListener('change', function () {
  document.querySelectorAll('.staff-check').forEach(cb => { cb.checked = this.checked; });
});
</script>
<?php layout_bottom(); ?>
