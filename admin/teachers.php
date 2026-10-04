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

function next_staff_no(): string
{
    $st = db()->prepare("SELECT staff_no FROM teachers WHERE staff_no LIKE 'T-%' ORDER BY id DESC LIMIT 1");
    $st->execute();
    $last = $st->fetchColumn();
    $n = $last ? (int)substr((string)$last, 2) + 1 : 1;
    return 'T-' . str_pad((string)$n, 3, '0', STR_PAD_LEFT);
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $act = $_POST['form_action'] ?? '';
    if ($act === 'save') {
        $id         = (int)($_POST['id'] ?? 0);
        $staff_no   = trim($_POST['staff_no'] ?? '');
        $name       = trim($_POST['name'] ?? '');
        $phone      = trim($_POST['phone'] ?? '');
        $cnic       = trim($_POST['cnic'] ?? '');
        $subject    = trim($_POST['subject'] ?? '');
        $salary     = (float)($_POST['salary'] ?? 0);
        $joining    = $_POST['joining_date'] ?: null;
        $campus_id  = (int)($_POST['campus_id'] ?? 0) ?: null;
        $status     = $_POST['status'] ?? 'Active';

        if ($staff_no === '') $errors[] = 'Staff number is required.';
        if ($name === '') $errors[] = 'Teacher name is required.';
        if (!in_array($status, ['Active','Inactive'], true)) $status = 'Active';

        $st = $pdo->prepare('SELECT id FROM teachers WHERE staff_no=? AND id<>? LIMIT 1');
        $st->execute([$staff_no, $id]);
        if ($st->fetch()) $errors[] = 'Staff number already exists.';

        if (!$errors) {
            if ($id > 0) {
                $pdo->prepare('UPDATE teachers SET staff_no=?, name=?, phone=?, cnic=?, subject=?, salary=?, joining_date=?, campus_id=?, status=? WHERE id=?')
                    ->execute([$staff_no, $name, $phone, $cnic, $subject, $salary, $joining, $campus_id, $status, $id]);
                $st = $pdo->prepare('SELECT user_id FROM teachers WHERE id=?');
                $st->execute([$id]);
                $uid = $st->fetchColumn();
                if ($uid) {
                    $newUser = strtolower($staff_no);
                    $chk = $pdo->prepare('SELECT id FROM users WHERE username=? AND id<>? LIMIT 1');
                    $chk->execute([$newUser, $uid]);
                    if (!$chk->fetch()) {
                        $pdo->prepare('UPDATE users SET username=? WHERE id=?')->execute([$newUser, $uid]);
                    }
                }
                flash('success', 'Teacher updated.');
            } else {
                $uid = create_user_account(strtolower($staff_no), 'teacher', $staff_no);
                $pdo->prepare('INSERT INTO teachers (staff_no, name, phone, cnic, subject, salary, joining_date, campus_id, status, user_id)
                               VALUES (?,?,?,?,?,?,?,?,?,?)')
                    ->execute([$staff_no, $name, $phone, $cnic, $subject, $salary, $joining, $campus_id, $status, $uid]);
                flash('success', "Teacher added. Login: " . strtolower($staff_no) . " / password: staff number.");
            }
            redirect('admin/teachers.php');
        }
    } elseif ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $st = $pdo->prepare('SELECT user_id FROM teachers WHERE id=?');
        $st->execute([$id]);
        $uid = $st->fetchColumn();
        $pdo->prepare('DELETE FROM teachers WHERE id=?')->execute([$id]);
        if ($uid) $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$uid]);
        flash('success', 'Teacher deleted.');
        redirect('admin/teachers.php');
    }
}

$action = $_GET['action'] ?? 'list';
if ($action === 'add' || $action === 'edit') {
    $row = ['id'=>0,'staff_no'=>next_staff_no(),'name'=>'','phone'=>'','cnic'=>'','subject'=>'','salary'=>'','joining_date'=>today(),'campus_id'=>'','status'=>'Active'];
    if ($action === 'edit') {
        $st = $pdo->prepare('SELECT * FROM teachers WHERE id=?');
        $st->execute([(int)($_GET['id'] ?? 0)]);
        $row = $st->fetch() ?: $row;
        if (!$row['id']) { flash('error','Teacher not found.'); redirect('admin/teachers.php'); }
    }
    layout_top(($row['id'] ? 'Edit' : 'Add') . ' Teacher', 'teachers');
    ?>
    <div class="card"><div class="card-body">
      <?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="form_action" value="save">
        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
        <div class="form-row">
          <div class="field"><label>Staff No *</label>
            <input type="text" name="staff_no" value="<?= e($row['staff_no']) ?>" required>
            <div class="hint">Auto-generated; also used as the teacher's login username & password.</div></div>
          <div class="field"><label>Name *</label>
            <input type="text" name="name" value="<?= e($row['name']) ?>" required></div>
        </div>
        <div class="form-row">
          <div class="field"><label>Phone</label><input type="tel" name="phone" value="<?= e($row['phone']) ?>"></div>
          <div class="field"><label>CNIC</label><input type="text" name="cnic" value="<?= e($row['cnic']) ?>"></div>
        </div>
        <div class="form-row">
          <div class="field"><label>Subject</label><input type="text" name="subject" value="<?= e($row['subject']) ?>"></div>
          <div class="field"><label>Salary (PKR)</label><input type="number" name="salary" step="0.01" min="0" value="<?= e((string)$row['salary']) ?>"></div>
        </div>
        <div class="form-row">
          <div class="field"><label>Joining Date</label><input type="date" name="joining_date" value="<?= e($row['joining_date'] ?? '') ?>"></div>
          <div class="field"><label>Status</label>
            <select name="status">
              <option <?= $row['status']==='Active'?'selected':'' ?>>Active</option>
              <option <?= $row['status']==='Inactive'?'selected':'' ?>>Inactive</option>
            </select></div>
        </div>
        <div class="form-row">
          <div class="field"><label>Campus</label>
            <select name="campus_id">
              <option value="">— Main / None —</option>
              <?php foreach (campuses() as $cp): ?>
              <option value="<?= (int)$cp['id'] ?>" <?= (int)($row['campus_id'] ?? 0)===(int)$cp['id']?'selected':'' ?>><?= e($cp['name']) ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="field"></div>
        </div>
        <button class="btn" type="submit">Save Teacher</button>
        <a class="btn secondary" href="<?= e(app_url('admin/teachers.php')) ?>">Cancel</a>
      </form>
    </div></div>
    <?php
    layout_bottom();
    return;
}

$q = trim($_GET['q'] ?? '');
$where = []; $params = [];
if ($q !== '') { $where[] = '(name LIKE ? OR staff_no LIKE ? OR subject LIKE ?)'; $params = ["%$q%","%$q%","%$q%"]; }
$st = $pdo->prepare('SELECT * FROM teachers' . ($where ? ' WHERE '.implode(' AND ',$where) : '') . ' ORDER BY name');
$st->execute($params);
$rows = $st->fetchAll();

layout_top('Teachers', 'teachers');
?>
<div class="card">
  <div class="card-head">
    <form method="get" style="display:flex;gap:8px">
      <input type="text" name="q" placeholder="Search name / staff no / subject" value="<?= e($q) ?>" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px">
      <button class="btn small" type="submit">Search</button>
    </form>
    <a class="btn" href="<?= e(app_url('admin/teachers.php?action=add')) ?>">+ Add Teacher</a>
  </div>
  <div class="card-body">
    <div class="table-wrap"><table class="table">
      <tr><th>Staff No</th><th>Name</th><th>Subject</th><th>Phone</th><th>Salary</th><th>Status</th><th>Actions</th></tr>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= e($r['staff_no']) ?></td>
        <td><strong><?= e($r['name']) ?></strong></td>
        <td><?= e($r['subject'] ?: '—') ?></td>
        <td><?= e($r['phone'] ?: '—') ?></td>
        <td><?= e(fmt_money($r['salary'])) ?></td>
        <td><span class="badge <?= $r['status']==='Active'?'green':'red' ?>"><?= e($r['status']) ?></span></td>
        <td style="white-space:nowrap">
          <a class="btn small secondary" href="<?= e(app_url('admin/teachers.php?action=edit&id='.$r['id'])) ?>">Edit</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this teacher and their login account?')">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn small danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="7" class="stat-label">No teachers found.</td></tr><?php endif; ?>
    </table></div>
  </div>
</div>
<?php layout_bottom(); ?>
