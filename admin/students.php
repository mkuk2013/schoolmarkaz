<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/bootstrap.php';
require_role('admin');
$pdo = db();

function create_user_account(string $username, string $role, string $plain): int
{
    $pdo = db();
    $base = $username; $n = 1;
    $st = $pdo->prepare('SELECT id FROM users WHERE username=?');
    while (true) {
        $st->execute([$username]);
        if (!$st->fetch()) break;
        $username = $base . '-' . (++$n);
    }
    $pdo->prepare('INSERT INTO users (role, username, password_hash) VALUES (?,?,?)')
        ->execute([$role, $username, password_hash($plain, PASSWORD_DEFAULT)]);
    return (int)$pdo->lastInsertId();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $act = $_POST['form_action'] ?? '';
    if ($act === 'save') {
        $id           = (int)($_POST['id'] ?? 0);
        $admission_no = trim($_POST['admission_no'] ?? '');
        $name         = trim($_POST['name'] ?? '');
        $gender       = $_POST['gender'] ?? 'Male';
        $dob          = $_POST['dob'] ?: null;
        $class_id     = (int)($_POST['class_id'] ?? 0);
        $section      = trim($_POST['section'] ?? 'A');
        $parent_id    = (int)($_POST['parent_id'] ?? 0) ?: null;
        $campus_id    = (int)($_POST['campus_id'] ?? 0) ?: null;
        $admission_date = $_POST['admission_date'] ?: null;
        $status       = $_POST['status'] ?? 'Active';

        if ($admission_no === '') $errors[] = 'Admission number is required.';
        if ($name === '') $errors[] = 'Student name is required.';
        if ($class_id <= 0) $errors[] = 'Please select a class.';
        if (!in_array($gender, ['Male','Female','Other'], true)) $gender = 'Male';
        if (!in_array($status, ['Active','Inactive'], true)) $status = 'Active';

        // duplicate admission_no?
        $st = $pdo->prepare('SELECT id FROM students WHERE admission_no=? AND id<>? LIMIT 1');
        $st->execute([$admission_no, $id]);
        if ($st->fetch()) $errors[] = 'Admission number already exists.';

        if (!$errors) {
            if ($id > 0) {
                $pdo->prepare('UPDATE students SET admission_no=?, name=?, gender=?, dob=?, class_id=?, section=?, parent_id=?, campus_id=?, admission_date=?, status=? WHERE id=?')
                    ->execute([$admission_no, $name, $gender, $dob, $class_id, $section, $parent_id, $campus_id, $admission_date, $status, $id]);
                // keep login username in sync with admission_no
                $st = $pdo->prepare('SELECT user_id FROM students WHERE id=?');
                $st->execute([$id]);
                $uid = $st->fetchColumn();
                if ($uid) {
                    $newUser = strtolower($admission_no);
                    $chk = $pdo->prepare('SELECT id FROM users WHERE username=? AND id<>? LIMIT 1');
                    $chk->execute([$newUser, $uid]);
                    if (!$chk->fetch()) {
                        $pdo->prepare('UPDATE users SET username=? WHERE id=?')->execute([$newUser, $uid]);
                    }
                }
                flash('success', 'Student updated.');
            } else {
                $uid = create_user_account(strtolower($admission_no), 'student', $admission_no);
                $pdo->prepare('INSERT INTO students (admission_no, name, gender, dob, class_id, section, parent_id, campus_id, admission_date, status, user_id)
                               VALUES (?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute([$admission_no, $name, $gender, $dob, $class_id, $section, $parent_id, $campus_id, $admission_date, $status, $uid]);
                flash('success', "Student added. Login: " . strtolower($admission_no) . " / password: admission number.");
            }
            redirect('admin/students.php');
        }
    } elseif ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $st = $pdo->prepare('SELECT user_id FROM students WHERE id=?');
        $st->execute([$id]);
        $uid = $st->fetchColumn();
        $pdo->prepare('DELETE FROM students WHERE id=?')->execute([$id]);
        if ($uid) $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$uid]);
        flash('success', 'Student deleted.');
        redirect('admin/students.php');
    }
}

$action = $_GET['action'] ?? 'list';
$classes = $pdo->query('SELECT id, name, section FROM classes ORDER BY name, section')->fetchAll();
$parents = $pdo->query('SELECT id, name, phone FROM parents ORDER BY name')->fetchAll();

if ($action === 'add' || $action === 'edit') {
    $row = ['id'=>0,'admission_no'=>next_admission_no(),'name'=>'','gender'=>'Male','dob'=>'','class_id'=>'','section'=>'A','parent_id'=>'','campus_id'=>'','admission_date'=>today(),'status'=>'Active'];
    if ($action === 'edit') {
        $st = $pdo->prepare('SELECT * FROM students WHERE id=?');
        $st->execute([(int)($_GET['id'] ?? 0)]);
        $row = $st->fetch() ?: $row;
        if (!$row['id']) { flash('error','Student not found.'); redirect('admin/students.php'); }
    }
    layout_top(($row['id'] ? 'Edit' : 'Add') . ' Student', 'students');
    ?>
    <div class="card"><div class="card-body">
      <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="form_action" value="save">
        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
        <div class="form-row">
          <div class="field"><label>Admission No *</label>
            <input type="text" name="admission_no" value="<?= e($row['admission_no']) ?>" required>
            <div class="hint">Auto-generated; also used as the student's login username & password.</div></div>
          <div class="field"><label>Name *</label>
            <input type="text" name="name" value="<?= e($row['name']) ?>" required></div>
        </div>
        <div class="form-row">
          <div class="field"><label>Gender</label>
            <select name="gender">
              <?php foreach (['Male','Female','Other'] as $g): ?>
              <option value="<?= $g ?>" <?= $row['gender']===$g?'selected':'' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="field"><label>Date of Birth</label>
            <input type="date" name="dob" value="<?= e($row['dob'] ?? '') ?>"></div>
        </div>
        <div class="form-row">
          <div class="field"><label>Class *</label>
            <select name="class_id" required>
              <option value="">— Select —</option>
              <?php foreach ($classes as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= (int)$row['class_id']===(int)$c['id']?'selected':'' ?>>
                <?= e($c['name'] . ' (' . $c['section'] . ')') ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="field"><label>Section</label>
            <input type="text" name="section" value="<?= e($row['section']) ?>" maxlength="10"></div>
        </div>
        <div class="form-row">
          <div class="field"><label>Parent</label>
            <select name="parent_id">
              <option value="">— None —</option>
              <?php foreach ($parents as $p): ?>
              <option value="<?= (int)$p['id'] ?>" <?= (int)$row['parent_id']===(int)$p['id']?'selected':'' ?>>
                <?= e($p['name'] . ($p['phone'] ? ' ('.$p['phone'].')' : '')) ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="field"><label>Status</label>
            <select name="status">
              <option <?= $row['status']==='Active'?'selected':'' ?>>Active</option>
              <option <?= $row['status']==='Inactive'?'selected':'' ?>>Inactive</option>
            </select></div>
        </div>
        <div class="form-row">
          <div class="field"><label>Admission Date</label>
            <input type="date" name="admission_date" value="<?= e($row['admission_date'] ?? '') ?>"></div>
          <div class="field"><label>Campus</label>
            <select name="campus_id">
              <option value="">— Main / None —</option>
              <?php foreach (campuses() as $cp): ?>
              <option value="<?= (int)$cp['id'] ?>" <?= (int)($row['campus_id'] ?? 0)===(int)$cp['id']?'selected':'' ?>><?= e($cp['name']) ?></option>
              <?php endforeach; ?>
            </select></div>
        </div>
        <button class="btn" type="submit">Save Student</button>
        <a class="btn secondary" href="<?= e(app_url('admin/students.php')) ?>">Cancel</a>
      </form>
    </div></div>
    <?php
    layout_bottom();
    return;
}

// ---- list ----
$q = trim($_GET['q'] ?? '');
$fclass = (int)($_GET['class_id'] ?? 0);
$where = []; $params = [];
if ($q !== '') { $where[] = '(s.name LIKE ? OR s.admission_no LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($fclass > 0) { $where[] = 's.class_id=?'; $params[] = $fclass; }
$sql = 'SELECT s.*, c.name AS class_name, p.name AS parent_name FROM students s
        LEFT JOIN classes c ON c.id=s.class_id LEFT JOIN parents p ON p.id=s.parent_id'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY s.name';
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

layout_top('Students', 'students');
?>
<div class="card">
  <div class="card-head">
    <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <input type="text" name="q" placeholder="Search name / admission no" value="<?= e($q) ?>" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px">
      <select name="class_id" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px">
        <option value="0">All classes</option>
        <?php foreach ($classes as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $fclass===(int)$c['id']?'selected':'' ?>><?= e($c['name'].' ('.$c['section'].')') ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn small" type="submit">Filter</button>
    </form>
    <a class="btn" href="<?= e(app_url('admin/students.php?action=add')) ?>">+ Add Student</a>
  </div>
  <div class="card-body">
    <div class="table-wrap"><table class="table">
      <tr><th>Admission No</th><th>Name</th><th>Class</th><th>Parent</th><th>Status</th><th>Actions</th></tr>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= e($r['admission_no']) ?></td>
        <td><strong><?= e($r['name']) ?></strong></td>
        <td><?= e(($r['class_name'] ?? '—') . ' (' . $r['section'] . ')') ?></td>
        <td><?= e($r['parent_name'] ?? '—') ?></td>
        <td><span class="badge <?= $r['status']==='Active'?'green':'red' ?>"><?= e($r['status']) ?></span></td>
        <td style="white-space:nowrap">
          <a class="btn small secondary" href="<?= e(app_url('admin/students.php?action=edit&id='.$r['id'])) ?>">Edit</a>
          <a class="btn small secondary" href="<?= e(app_url('admin/documents.php?student_id='.$r['id'])) ?>">Docs</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this student and their login account?')">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn small danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6" class="stat-label">No students found.</td></tr><?php endif; ?>
    </table></div>
  </div>
</div>
<?php layout_bottom(); ?>
