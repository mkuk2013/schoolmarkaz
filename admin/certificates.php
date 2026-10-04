<?php
declare(strict_types=1);
/**
 * Certificates — issue Character / Leaving / Merit / Bonafide certificates
 * as print-ready landscape PDFs (certificate.php).
 * navkey: 'certificates'
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$pdo = db();
$students = $pdo->query(
    "SELECT s.id, s.name, s.admission_no, c.name AS class_name, s.section
     FROM students s LEFT JOIN classes c ON c.id = s.class_id
     WHERE s.status = 'Active' ORDER BY s.name LIMIT 500"
)->fetchAll();

layout_top('Certificates', 'certificates');
?>
<div class="card">
  <div class="card-head"><h2>📜 Issue a Certificate</h2></div>
  <div class="card-body">
    <form method="post" action="<?= e(app_url('admin/certificate.php')) ?>" target="_blank">
      <?= csrf_field() ?>
      <div class="form-row">
        <div class="field"><label>Student *</label>
          <select name="student_id" required>
            <option value="">— Select student —</option>
            <?php foreach ($students as $s): ?>
              <option value="<?= (int) $s['id'] ?>"><?= e($s['name'] . ' (' . $s['admission_no'] . ') — ' . trim(($s['class_name'] ?? '') . ' ' . $s['section'])) ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="field"><label>Certificate Type *</label>
          <select name="type" id="certType" required>
            <option value="character">Character Certificate</option>
            <option value="leaving">School Leaving Certificate</option>
            <option value="merit">Merit / Achievement Certificate</option>
            <option value="bonafide">Bonafide Certificate</option>
          </select></div>
      </div>
      <div class="form-row">
        <div class="field"><label>Custom Line (optional)</label>
          <input type="text" name="custom_text" placeholder="e.g. achievement for Merit, conduct note for Character">
          <div class="hint">For Merit certificates this is the achievement text. Left empty = standard wording.</div></div>
        <div class="field" id="reasonField" style="display:none"><label>Reason for Leaving</label>
          <input type="text" name="reason" placeholder="e.g. Parent's request / Family shifted"></div>
      </div>
      <div class="form-row">
        <div class="field"><label>Issue Date</label>
          <input type="date" name="issue_date" value="<?= e(today()) ?>"></div>
        <div class="field"></div>
      </div>
      <button class="btn" type="submit">📜 Generate Certificate (PDF)</button>
    </form>
  </div>
</div>

<script>
const t = document.getElementById('certType');
const r = document.getElementById('reasonField');
function syncReason() { r.style.display = t.value === 'leaving' ? '' : 'none'; }
t.addEventListener('change', syncReason); syncReason();
</script>
<?php layout_bottom(); ?>
