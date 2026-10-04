<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/bootstrap.php';
require_role('admin');
$pdo = db();
$school = school_profile();
$school_id = (int)($school['id'] ?? 0);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $act = $_POST['form_action'] ?? '';
    if ($act === 'profile') {
        $name    = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $session = trim($_POST['session_year'] ?? '');
        $pkg     = (int)($_POST['package_id'] ?? 0) ?: null;
        if ($name === '') $errors[] = 'School name is required.';
        if (!$errors) {
            $pdo->prepare('UPDATE schools SET name=?, address=?, phone=?, email=?, session_year=?, package_id=? WHERE id=?')
                ->execute([$name, $address, $phone, $email, $session, $pkg, $school_id]);
            flash('success', 'School profile updated.');
            redirect('admin/settings.php');
        }
    } elseif ($act === 'logo') {
        $f = $_FILES['logo'] ?? null;
        if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Please choose a logo file.';
        } elseif ($f['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload failed (error code ' . (int)$f['error'] . ').';
        } elseif ($f['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Logo must be 2 MB or smaller.';
        } else {
            $info = @getimagesize($f['tmp_name']);
            $mime = is_array($info) ? ($info['mime'] ?? '') : '';
            $map = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
            if (!isset($map[$mime])) {
                $errors[] = 'Only JPG and PNG images are allowed.';
            } else {
                $dir = __DIR__ . '/../uploads/logo';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $file = 'logo_' . bin2hex(random_bytes(8)) . '.' . $map[$mime];
                if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $file)) {
                    $errors[] = 'Could not save the uploaded file.';
                } else {
                    $pdo->prepare('UPDATE schools SET logo=? WHERE id=?')
                        ->execute(['uploads/logo/' . $file, $school_id]);
                    if (($school['logo'] ?? '') && str_starts_with($school['logo'], 'uploads/logo/')) {
                        $oldPath = __DIR__ . '/../' . $school['logo'];
                        if (is_file($oldPath)) @unlink($oldPath);
                    }
                    flash('success', 'Logo uploaded.');
                    redirect('admin/settings.php');
                }
            }
        }
    } elseif ($act === 'payments') {
        $keys = ['pay_jazzcash_no','pay_jazzcash_title','pay_easypaisa_no','pay_easypaisa_title',
                 'pay_bank_name','pay_bank_title','pay_bank_iban'];
        foreach ($keys as $k) {
            save_setting($k, trim($_POST[$k] ?? ''));
        }
        flash('success', 'Payment methods updated.');
        redirect('admin/settings.php');
    } elseif ($act === 'integrations') {
        save_setting('sms_enabled', isset($_POST['sms_enabled']) ? '1' : '0');
        save_setting('sms_api_url', trim($_POST['sms_api_url'] ?? ''));
        save_setting('sms_sender', trim($_POST['sms_sender'] ?? ''));
        if (trim($_POST['sms_api_key'] ?? '') !== '') {
            save_setting('sms_api_key', trim($_POST['sms_api_key']));
        }
        save_setting('ai_api_url', trim($_POST['ai_api_url'] ?? '') ?: 'https://api.openai.com/v1/chat/completions');
        save_setting('ai_model', trim($_POST['ai_model'] ?? '') ?: 'gpt-4o-mini');
        if (trim($_POST['ai_api_key'] ?? '') !== '') {
            save_setting('ai_api_key', trim($_POST['ai_api_key']));
        }
        flash('success', 'SMS & AI settings updated.');
        redirect('admin/settings.php');
    }
}

$packages = $pdo->query('SELECT id, name FROM packages ORDER BY price_monthly')->fetchAll();
$logoUrl = ($school['logo'] ?? '') ? app_url('/') . '/' . ltrim($school['logo'], '/') : '';

layout_top('Settings', 'settings');
?>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>

<div class="grid grid-2">
  <div class="card">
    <div class="card-head"><h3>School Profile</h3></div>
    <div class="card-body">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="form_action" value="profile">
        <div class="field"><label>School Name *</label>
          <input type="text" name="name" value="<?= e($school['name'] ?? '') ?>" required></div>
        <div class="field"><label>Address</label>
          <input type="text" name="address" value="<?= e($school['address'] ?? '') ?>"></div>
        <div class="form-row">
          <div class="field"><label>Phone</label>
            <input type="tel" name="phone" value="<?= e($school['phone'] ?? '') ?>"></div>
          <div class="field"><label>Email</label>
            <input type="email" name="email" value="<?= e($school['email'] ?? '') ?>"></div>
        </div>
        <div class="form-row">
          <div class="field"><label>Session Year</label>
            <input type="text" name="session_year" value="<?= e($school['session_year'] ?? '') ?>" placeholder="e.g. 2026-27"></div>
          <div class="field"><label>Package</label>
            <select name="package_id">
              <option value="">— None —</option>
              <?php foreach ($packages as $p): ?>
              <option value="<?= (int)$p['id'] ?>" <?= (int)($school['package_id'] ?? 0)===(int)$p['id']?'selected':'' ?>><?= e($p['name']) ?></option>
              <?php endforeach; ?>
            </select></div>
        </div>
        <button class="btn" type="submit">Save Profile</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>School Logo</h3></div>
    <div class="card-body">
      <?php if ($logoUrl): ?>
        <p><img src="<?= e($logoUrl) ?>" alt="School logo" style="max-width:180px;max-height:180px;border:1px solid var(--border);border-radius:8px"></p>
      <?php else: ?>
        <p class="stat-label">No logo uploaded yet.</p>
      <?php endif; ?>
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="form_action" value="logo">
        <div class="field"><label>Upload Logo (JPG/PNG, max 2 MB)</label>
          <input type="file" name="logo" accept="image/jpeg,image/png" required></div>
        <button class="btn" type="submit">Upload Logo</button>
      </form>
    </div>
  </div>

  <div class="card" style="grid-column:1/-1">
    <div class="card-head"><h3>💳 Payment Methods <span class="stat-label">— shown to buyers on the payment page</span></h3></div>
    <div class="card-body">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="form_action" value="payments">
        <div class="grid grid-2">
          <div>
            <h4 style="margin:0 0 8px">📱 JazzCash</h4>
            <div class="field"><label>Account Number</label>
              <input type="text" name="pay_jazzcash_no" value="<?= e(setting('pay_jazzcash_no')) ?>" placeholder="03xx-xxxxxxx"></div>
            <div class="field"><label>Account Title</label>
              <input type="text" name="pay_jazzcash_title" value="<?= e(setting('pay_jazzcash_title')) ?>"></div>
          </div>
          <div>
            <h4 style="margin:0 0 8px">📱 Easypaisa</h4>
            <div class="field"><label>Account Number</label>
              <input type="text" name="pay_easypaisa_no" value="<?= e(setting('pay_easypaisa_no')) ?>" placeholder="03xx-xxxxxxx"></div>
            <div class="field"><label>Account Title</label>
              <input type="text" name="pay_easypaisa_title" value="<?= e(setting('pay_easypaisa_title')) ?>"></div>
          </div>
        </div>
        <h4 style="margin:14px 0 8px">🏦 Bank Transfer</h4>
        <div class="form-row">
          <div class="field"><label>Bank Name</label>
            <input type="text" name="pay_bank_name" value="<?= e(setting('pay_bank_name')) ?>" placeholder="e.g. Meezan Bank"></div>
          <div class="field"><label>Account Title</label>
            <input type="text" name="pay_bank_title" value="<?= e(setting('pay_bank_title')) ?>"></div>
          <div class="field"><label>IBAN</label>
            <input type="text" name="pay_bank_iban" value="<?= e(setting('pay_bank_iban')) ?>" placeholder="PKxx..."></div>
        </div>
        <button class="btn" type="submit">Save Payment Methods</button>
      </form>
    </div>
  </div>

  <div class="card" style="grid-column:1/-1">
    <div class="card-head"><h3>📩 SMS &amp; 🤖 AI <span class="stat-label">— gate alerts by SMS + AI quiz questions (optional)</span></h3></div>
    <div class="card-body">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="form_action" value="integrations">
        <h4 style="margin:0 0 8px">📩 SMS Gateway (parent gate alerts)</h4>
        <div class="form-row">
          <div class="field"><label>SMS bhejna on karein</label>
            <select name="sms_enabled">
              <option value="0" <?= setting('sms_enabled') === '1' ? '' : 'selected' ?>>Off — alerts sirf portal mein</option>
              <option value="1" <?= setting('sms_enabled') === '1' ? 'selected' : '' ?>>On — portal + SMS</option>
            </select></div>
          <div class="field"><label>Sender Name</label>
            <input type="text" name="sms_sender" value="<?= e(setting('sms_sender', 'School')) ?>" placeholder="School"></div>
        </div>
        <div class="form-row">
          <div class="field"><label>SMS API URL</label>
            <input type="text" name="sms_api_url" value="<?= e(setting('sms_api_url')) ?>" placeholder="https://your-sms-provider/api/send"></div>
          <div class="field"><label>SMS API Key <?= setting('sms_api_key') !== '' ? '<span class="badge green">saved</span>' : '' ?></label>
            <input type="password" name="sms_api_key" value="" placeholder="<?= setting('sms_api_key') !== '' ? 'Saved — change karna ho to naya likhein' : 'API key' ?>" autocomplete="new-password"></div>
        </div>
        <h4 style="margin:16px 0 8px">🤖 AI (quiz questions generator)</h4>
        <div class="form-row">
          <div class="field"><label>AI API URL</label>
            <input type="text" name="ai_api_url" value="<?= e(setting('ai_api_url', 'https://api.openai.com/v1/chat/completions')) ?>"></div>
          <div class="field"><label>Model</label>
            <input type="text" name="ai_model" value="<?= e(setting('ai_model', 'gpt-4o-mini')) ?>"></div>
          <div class="field"><label>AI API Key <?= setting('ai_api_key') !== '' ? '<span class="badge green">saved</span>' : '' ?></label>
            <input type="password" name="ai_api_key" value="" placeholder="<?= setting('ai_api_key') !== '' ? 'Saved — change karna ho to naya likhein' : 'sk-...' ?>" autocomplete="new-password"></div>
        </div>
        <p class="hint">Keys sirf tab save hoti hain jab field mein kuch likha ho — khaali chhorne se purani key mehfooz rehti hai. Koi bhi OpenAI-compatible API chalegi.</p>
        <button class="btn" type="submit">Save SMS & AI Settings</button>
      </form>
    </div>
  </div>
</div>
<?php layout_bottom(); ?>
