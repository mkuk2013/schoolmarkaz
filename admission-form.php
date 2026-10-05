<?php
declare(strict_types=1);

/**
 * Public online admission inquiry form.
 */
require_once __DIR__ . '/includes/bootstrap.php';

$success = false;
$errors = [];
$old = [
    'student_name' => '', 'gender' => 'Male', 'dob' => '', 'class_applied' => '',
    'parent_name' => '', 'phone' => '', 'address' => '', 'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    foreach ($old as $k => $v) {
        $old[$k] = trim($_POST[$k] ?? '');
    }

    if ($old['student_name'] === '') $errors[] = 'Student name is required.';
    if (!in_array($old['gender'], ['Male', 'Female', 'Other'], true)) $old['gender'] = 'Male';
    if ($old['phone'] === '')        $errors[] = 'Phone number is required.';
    if ($old['dob'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $old['dob'])) {
        $errors[] = 'Date of birth is not a valid date.';
        $old['dob'] = '';
    }

    if (!$errors) {
        $st = db()->prepare("INSERT INTO admissions_inquiries
            (student_name, gender, dob, class_applied, parent_name, phone, address, message, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new')");
        $st->execute([
            $old['student_name'],
            $old['gender'],
            $old['dob'] !== '' ? $old['dob'] : null,
            $old['class_applied'],
            $old['parent_name'],
            $old['phone'],
            $old['address'],
            $old['message'],
        ]);
        flash('success', 'Your admission inquiry has been received. The school office will contact you soon.');
        redirect('admission-form.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Online Admission — <?= e(APP_NAME) ?></title>
<link rel="icon" type="image/png" href="<?= e(app_url('assets/icons/logo.png')) ?>">
<link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>?v=4">
<script>try{if(localStorage.getItem('sm-theme')==='dark'){document.documentElement.dataset.theme='dark';}}catch(e){}</script>
<style>
.wrap { max-width: 640px; margin: 0 auto; padding: 24px 18px 40px; }
.brandline { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 18px; margin-bottom: 20px; }
.brandline img { width: 34px; height: 34px; }
</style>
</head>
<body>
<div class="wrap">
  <a class="brandline" href="<?= e(app_url('index.php')) ?>">
    <img src="<?= e(app_url('assets/icons/logo.png')) ?>" alt="SM logo"> <?= e(APP_NAME) ?>
  </a>

  <div class="card">
    <div class="card-head"><h2>🎓 Online Admission Inquiry</h2></div>
    <div class="card-body">
      <?php foreach (get_flashes() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
      <?php endforeach; ?>
      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="post" action="<?= e(app_url('admission-form.php')) ?>">
        <?= csrf_field() ?>

        <div class="form-row">
          <div class="field">
            <label for="student_name">Student name *</label>
            <input type="text" id="student_name" name="student_name" required value="<?= e($old['student_name']) ?>">
          </div>
          <div class="field">
            <label for="gender">Gender *</label>
            <select id="gender" name="gender">
              <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
              <option<?= $old['gender'] === $g ? ' selected' : '' ?>><?= e($g) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="field">
            <label for="dob">Date of birth</label>
            <input type="date" id="dob" name="dob" value="<?= e($old['dob']) ?>">
          </div>
          <div class="field">
            <label for="class_applied">Class applied for</label>
            <input type="text" id="class_applied" name="class_applied" placeholder="e.g. Class 5" value="<?= e($old['class_applied']) ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="field">
            <label for="parent_name">Parent / guardian name</label>
            <input type="text" id="parent_name" name="parent_name" value="<?= e($old['parent_name']) ?>">
          </div>
          <div class="field">
            <label for="phone">Phone *</label>
            <input type="tel" id="phone" name="phone" required value="<?= e($old['phone']) ?>">
          </div>
        </div>
        <div class="field">
          <label for="address">Address</label>
          <input type="text" id="address" name="address" value="<?= e($old['address']) ?>">
        </div>
        <div class="field">
          <label for="message">Message</label>
          <textarea id="message" name="message" rows="4" placeholder="Anything you want the school to know..."><?= e($old['message']) ?></textarea>
        </div>

        <button class="btn" type="submit" style="width:100%">Submit Admission Inquiry</button>
        <p style="text-align:center;font-size:13px;color:var(--muted)">
          <a href="<?= e(app_url('index.php')) ?>">← Back to home</a>
        </p>
      </form>
    </div>
  </div>
</div>
<script src="<?= e(app_url('assets/js/app.js')) ?>"></script>
</body>
</html>
