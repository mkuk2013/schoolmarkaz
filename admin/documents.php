<?php
declare(strict_types=1);
/**
 * Students — Documents: upload / list / delete a student's documents
 * (B-Form, certificates, photos…). Linked from the Students list ("Docs").
 * navkey: 'students'
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$pdo = db();
$studentId = (int) ($_GET['student_id'] ?? $_POST['student_id'] ?? 0);

$student = null;
if ($studentId > 0) {
    $st = $pdo->prepare(
        'SELECT s.*, c.name AS class_name FROM students s
         LEFT JOIN classes c ON c.id = s.class_id WHERE s.id = ? LIMIT 1'
    );
    $st->execute([$studentId]);
    $student = $st->fetch() ?: null;
}

$docTypes = ['B-Form', 'Birth Certificate', 'Student Photo', 'Previous Result Card', 'Parent CNIC', 'Leaving Certificate', 'Other'];
$errors = [];

/* ---------- Upload ---------- */
if ($student && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'upload') {
    verify_csrf();
    $type = in_array($_POST['doc_type'] ?? '', $docTypes, true) ? $_POST['doc_type'] : 'Other';
    $f = $_FILES['document'] ?? null;
    if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Please choose a file.';
    } elseif ($f['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Upload failed (error code ' . (int) $f['error'] . ').';
    } elseif ($f['size'] > 5 * 1024 * 1024) {
        $errors[] = 'File must be 5 MB or smaller.';
    } else {
        $ext = strtolower(pathinfo((string) $f['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($ext, $allowed, true)) {
            $errors[] = 'Only JPG, PNG or PDF files are allowed.';
        } else {
            $dir = __DIR__ . '/../uploads/documents';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $file = 'doc_' . $studentId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $file)) {
                $errors[] = 'Could not save the uploaded file.';
            } else {
                $pdo->prepare(
                    'INSERT INTO student_documents (student_id, doc_type, file_path, original_name, uploaded_by)
                     VALUES (?, ?, ?, ?, ?)'
                )->execute([
                    $studentId, $type, 'uploads/documents/' . $file,
                    (string) $f['name'], (int) (current_user()['id'] ?? 0) ?: null,
                ]);
                flash('success', 'Document uploaded.');
                redirect('admin/documents.php?student_id=' . $studentId);
            }
        }
    }
}

/* ---------- Delete ---------- */
if ($student && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'delete') {
    verify_csrf();
    $docId = (int) ($_POST['doc_id'] ?? 0);
    $st = $pdo->prepare('SELECT file_path FROM student_documents WHERE id = ? AND student_id = ? LIMIT 1');
    $st->execute([$docId, $studentId]);
    $path = $st->fetchColumn();
    if ($path) {
        $pdo->prepare('DELETE FROM student_documents WHERE id = ?')->execute([$docId]);
        $full = __DIR__ . '/../' . $path;
        if (is_file($full)) {
            @unlink($full);
        }
        flash('success', 'Document deleted.');
    }
    redirect('admin/documents.php?student_id=' . $studentId);
}

$docs = [];
if ($student) {
    $st = $pdo->prepare('SELECT * FROM student_documents WHERE student_id = ? ORDER BY created_at DESC');
    $st->execute([$studentId]);
    $docs = $st->fetchAll();
} else {
    $students = $pdo->query(
        "SELECT s.id, s.name, s.admission_no, c.name AS class_name, s.section
         FROM students s LEFT JOIN classes c ON c.id = s.class_id
         WHERE s.status = 'Active' ORDER BY s.name LIMIT 500"
    )->fetchAll();
}

layout_top('Student Documents', 'students');
?>
<?php foreach ($errors as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>

<?php if (!$student): ?>
<div class="card">
  <div class="card-head"><h2>Choose a Student</h2></div>
  <div class="card-body">
    <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <select name="student_id" required style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;min-width:260px">
        <option value="">— Select student —</option>
        <?php foreach ($students as $s): ?>
          <option value="<?= (int) $s['id'] ?>"><?= e($s['name'] . ' (' . $s['admission_no'] . ') — ' . trim(($s['class_name'] ?? '') . ' ' . $s['section'])) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn small" type="submit">Open Documents</button>
    </form>
    <p class="hint" style="margin-top:10px">Tip: you can also open a student's documents from the <a href="<?= e(app_url('admin/students.php')) ?>">Students list</a> → “Docs”.</p>
  </div>
</div>
<?php else: ?>
<div class="card">
  <div class="card-head">
    <h2><?= e($student['name']) ?> <small style="color:var(--muted)"><?= e($student['admission_no']) ?> · <?= e(trim(($student['class_name'] ?? '') . ' ' . $student['section'])) ?></small></h2>
    <a class="btn secondary small" href="<?= e(app_url('admin/students.php')) ?>">← All Students</a>
  </div>
  <div class="card-body">
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" value="upload">
      <input type="hidden" name="student_id" value="<?= $studentId ?>">
      <div class="form-row">
        <div class="field">
          <label>Document Type</label>
          <select name="doc_type">
            <?php foreach ($docTypes as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>File (JPG / PNG / PDF, max 5 MB)</label>
          <input type="file" name="document" accept=".jpg,.jpeg,.png,.pdf" required>
        </div>
      </div>
      <button class="btn" type="submit">Upload Document</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-head"><h2>Documents (<?= count($docs) ?>)</h2></div>
  <div class="card-body">
    <div class="table-wrap"><table class="table">
      <tr><th>Type</th><th>File</th><th>Uploaded</th><th>Actions</th></tr>
      <?php foreach ($docs as $d): ?>
      <tr>
        <td><span class="badge"><?= e($d['doc_type']) ?></span></td>
        <td><?= e($d['original_name'] ?: basename($d['file_path'])) ?></td>
        <td><?= e(fmt_date(substr((string) $d['created_at'], 0, 10))) ?></td>
        <td style="white-space:nowrap">
          <a class="btn small secondary" target="_blank" href="<?= e(app_url('/')) ?>/<?= e($d['file_path']) ?>">View</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this document?')">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="delete">
            <input type="hidden" name="student_id" value="<?= $studentId ?>">
            <input type="hidden" name="doc_id" value="<?= (int) $d['id'] ?>">
            <button class="btn small danger" type="submit">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$docs): ?><tr><td colspan="4" class="stat-label">No documents uploaded yet.</td></tr><?php endif; ?>
    </table></div>
  </div>
</div>
<?php endif; ?>
<?php layout_bottom(); ?>
