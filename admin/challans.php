<?php
declare(strict_types=1);
/**
 * Fees & Finance — Bank challans list: outstanding invoices with bulk
 * selection that prints 3-copy PDF challans (see challan.php).
 * navkey: 'challans'
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$pdo = db();

$fMonth = isset($_GET['f_month']) && $_GET['f_month'] !== '' ? (int) $_GET['f_month'] : 0;
$fYear  = isset($_GET['f_year']) && $_GET['f_year'] !== '' ? (int) $_GET['f_year'] : 0;
$fClass = isset($_GET['f_class']) && $_GET['f_class'] !== '' ? (int) $_GET['f_class'] : 0;

$where = ['i.status != \'paid\''];
$params = [];
if ($fMonth > 0) { $where[] = 'i.`month` = ?';  $params[] = $fMonth; }
if ($fYear > 0)  { $where[] = 'i.`year` = ?';   $params[] = $fYear; }
if ($fClass > 0) { $where[] = 's.class_id = ?'; $params[] = $fClass; }

$sql = 'SELECT i.*, s.name AS student_name, s.admission_no, c.name AS class_name, c.section
     FROM fee_invoices i
     JOIN students s ON s.id = i.student_id
     LEFT JOIN classes c ON c.id = s.class_id
     WHERE ' . implode(' AND ', $where)
   . ' ORDER BY i.`year` DESC, i.`month` DESC, s.name';
$st = $pdo->prepare($sql);
$st->execute($params);
$invoices = $st->fetchAll();

$classes = $pdo->query('SELECT id, name, section FROM classes ORDER BY name, section')->fetchAll();
$bankSet = setting('pay_bank_name') !== '' || setting('pay_bank_iban') !== '';

layout_top('Bank Challans', 'challans');
?>

<?php if (!$bankSet): ?>
  <div class="alert alert-error">Bank details are not set yet — challans will say “Pay at school office”.
    Add your bank name / account in <a href="<?= e(app_url('admin/settings.php')) ?>">Settings → Payment Methods</a> so parents can pay at the bank.</div>
<?php endif; ?>

<div class="card">
  <div class="card-head"><h2>Outstanding Invoices — Print Challans</h2></div>
  <div class="card-body">
    <form method="get" action="<?= e(app_url('admin/challans.php')) ?>" style="margin-bottom:16px">
      <div class="form-row">
        <div class="field">
          <label>Month</label>
          <select name="f_month">
            <option value="">All</option>
            <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $m ?>"<?= $m === $fMonth ? ' selected' : '' ?>><?= e(month_name($m)) ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="field">
          <label>Year</label>
          <select name="f_year">
            <option value="">All</option>
            <?php for ($y = (int) date('Y') - 3; $y <= (int) date('Y') + 1; $y++): ?>
              <option value="<?= $y ?>"<?= $y === $fYear ? ' selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="field">
          <label>Class</label>
          <select name="f_class">
            <option value="">All</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= (int) $c['id'] ?>"<?= (int) $c['id'] === $fClass ? ' selected' : '' ?>><?= e($c['name'] . ' ' . $c['section']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <button class="btn secondary small" type="submit">Filter</button>
      <a class="btn secondary small" href="<?= e(app_url('admin/challans.php')) ?>">Reset</a>
    </form>

    <form method="post" action="<?= e(app_url('admin/challan.php')) ?>" target="_blank">
      <?= csrf_field() ?>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr><th><input type="checkbox" id="checkAll"></th><th>Student</th><th>Class</th><th>Month</th><th>Balance Due</th><th>Due Date</th><th></th></tr>
          </thead>
          <tbody>
            <?php if (!$invoices): ?>
              <tr><td colspan="7">No outstanding invoices. 🎉</td></tr>
            <?php endif; ?>
            <?php foreach ($invoices as $r):
              $balance = max(0.0, (float) $r['total'] + (float) $r['fine'] - (float) $r['discount'] - (float) $r['paid']);
            ?>
              <tr>
                <td><input type="checkbox" name="ids[]" value="<?= (int) $r['id'] ?>" class="row-check"></td>
                <td><?= e($r['student_name']) ?><br><small style="color:var(--muted)"><?= e($r['admission_no']) ?></small></td>
                <td><?= e(trim(($r['class_name'] ?? '') . ' ' . ($r['section'] ?? ''))) ?></td>
                <td><?= e(month_name((int) $r['month']) . ' ' . $r['year']) ?></td>
                <td><strong><?= e(fmt_money($balance)) ?></strong></td>
                <td><?= e(fmt_date($r['due_date'])) ?></td>
                <td><a class="btn secondary small" target="_blank" href="<?= e(app_url('admin/challan.php')) ?>?id=<?= (int) $r['id'] ?>">Challan</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($invoices): ?>
        <div style="margin-top:14px">
          <button class="btn" type="submit">🧾 Print Selected Challans (3 copies each)</button>
          <span class="hint">Each challan prints Bank / School / Student copies on one A4 page.</span>
        </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<script>
document.getElementById('checkAll')?.addEventListener('change', function () {
  document.querySelectorAll('.row-check').forEach(cb => { cb.checked = this.checked; });
});
</script>

<?php layout_bottom(); ?>
