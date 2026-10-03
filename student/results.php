<?php
declare(strict_types=1);

/**
 * Student → My results: exams list with % + grade + link to PDF report card.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';
require_role('student');

$me = my_profile();
$sid = (int) ($me['id'] ?? 0);

$st = db()->prepare('SELECT x.id, x.name, x.term, x.`year`,
        SUM(m.obtained) o, SUM(m.total) t
    FROM marks m JOIN exams x ON x.id = m.exam_id
    WHERE m.student_id = ? GROUP BY x.id ORDER BY x.`year` DESC, x.id DESC');
$st->execute([$sid]);
$results = $st->fetchAll() ?: [];

layout_top('My Results', 'results');
?>
<div class="card">
  <div class="card-head"><h3>Exam results</h3></div>
  <?php if (!$results): ?>
    <div class="card-body">No results published yet.</div>
  <?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Exam</th><th>Term</th><th>Year</th><th>Obtained / Total</th><th>%</th><th>Grade</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($results as $r):
          $pct = (float) $r['t'] > 0 ? round(100 * (float) $r['o'] / (float) $r['t'], 1) : null; ?>
          <tr>
            <td><strong><?= e($r['name']) ?></strong></td>
            <td><?= e($r['term'] ?: '—') ?></td>
            <td><?= (int) $r['year'] ?></td>
            <td><?= e(number_format((float) $r['o'], 1)) ?> / <?= e(number_format((float) $r['t'], 1)) ?></td>
            <td><strong><?= $pct === null ? '—' : $pct . '%' ?></strong></td>
            <td><?= sm_grade($pct) ?></td>
            <td><a class="btn small" target="_blank"
              href="../admin/reportcard.php?exam_id=<?= (int) $r['id'] ?>&student_id=<?= $sid ?>">📄 Report card</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>

<?php layout_bottom(); ?>
