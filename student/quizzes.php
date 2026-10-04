<?php
declare(strict_types=1);
/**
 * Student → Quizzes: attempt published quizzes for my class; auto-marked,
 * one attempt each, with a full answer review afterwards.
 * navkey: 'quizzes'
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/portal.php';
require_role('student');

$pdo = db();
$me = my_profile();
$sid = (int) ($me['id'] ?? 0);
$classId = (int) ($me['class_id'] ?? 0);

function student_quiz(int $quizId, int $classId): ?array
{
    $st = db()->prepare(
        'SELECT q.*, s.name AS subject_name FROM quizzes q
         LEFT JOIN subjects s ON s.id = q.subject_id
         WHERE q.id = ? AND q.is_published = 1 AND (q.class_id = ? OR q.class_id IS NULL) LIMIT 1'
    );
    $st->execute([$quizId, $classId]);
    return $st->fetch() ?: null;
}

function my_attempt(int $quizId, int $sid): ?array
{
    $st = db()->prepare('SELECT * FROM quiz_attempts WHERE quiz_id = ? AND student_id = ? LIMIT 1');
    $st->execute([$quizId, $sid]);
    return $st->fetch() ?: null;
}

/* ---------- Submit attempt ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'submit') {
    verify_csrf();
    $quizId = (int) ($_POST['quiz_id'] ?? 0);
    $quiz = student_quiz($quizId, $classId);
    if ($quiz && !my_attempt($quizId, $sid)) {
        $qs = $pdo->prepare('SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id');
        $qs->execute([$quizId]);
        $score = 0.0;
        $total = 0.0;
        $answers = [];
        foreach ($qs->fetchAll() as $q) {
            $total += (float) $q['marks'];
            $given = strtoupper((string) ($_POST['ans'][$q['id']] ?? ''));
            $answers[$q['id']] = $given;
            if ($given === $q['correct']) {
                $score += (float) $q['marks'];
            }
        }
        try {
            $pdo->prepare('INSERT INTO quiz_attempts (quiz_id, student_id, score, total, answers) VALUES (?,?,?,?,?)')
                ->execute([$quizId, $sid, $score, $total, json_encode($answers)]);
        } catch (Throwable $e) {
            /* duplicate attempt — fall through to result */
        }
        flash('success', 'Quiz submitted!');
        redirect('student/quizzes.php?result=' . $quizId);
    }
    redirect('student/quizzes.php');
}

/* ---------- Take view ---------- */
$takeId = (int) ($_GET['take'] ?? 0);
if ($takeId > 0) {
    $quiz = student_quiz($takeId, $classId);
    if (!$quiz) {
        flash('error', 'Quiz available nahi hai.');
        redirect('student/quizzes.php');
    }
    if (my_attempt($takeId, $sid)) {
        redirect('student/quizzes.php?result=' . $takeId);
    }
    $qs = $pdo->prepare('SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id');
    $qs->execute([$takeId]);
    $questions = $qs->fetchAll();

    layout_top('Quiz: ' . $quiz['title'], 'quizzes');
    ?>
    <div class="card">
      <div class="card-head"><h2>❓ <?= e($quiz['title']) ?></h2></div>
      <div class="card-body">
        <?php if (!$questions): ?>
          <p class="stat-label">Is quiz mein abhi sawal nahi hain.</p>
          <a class="btn secondary" href="<?= e(app_url('student/quizzes.php')) ?>">← Back</a>
        <?php else: ?>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="form_action" value="submit">
          <input type="hidden" name="quiz_id" value="<?= $takeId ?>">
          <?php foreach ($questions as $i => $q): ?>
            <div style="padding:12px 0;border-bottom:1px solid var(--border)">
              <div><strong>Q<?= $i + 1 ?>.</strong> <?= e($q['question']) ?> <small style="color:var(--muted)">(<?= (float) $q['marks'] ?>)</small></div>
              <div style="margin:8px 0 0 16px">
                <?php foreach (['A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d'] as $L => $col): ?>
                  <?php if (($q[$col] ?? '') !== ''): ?>
                    <label style="display:block;padding:6px 0;cursor:pointer">
                      <input type="radio" name="ans[<?= (int) $q['id'] ?>]" value="<?= $L ?>" required> <?= $L ?>. <?= e($q[$col]) ?>
                    </label>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
          <div style="margin-top:16px">
            <button class="btn" type="submit" onclick="return confirm('Submit kar dein? Dobara attempt nahi milega.')">Submit Quiz</button>
            <a class="btn secondary" href="<?= e(app_url('student/quizzes.php')) ?>">Cancel</a>
          </div>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php
    layout_bottom();
    return;
}

/* ---------- Result view ---------- */
$resultId = (int) ($_GET['result'] ?? 0);
if ($resultId > 0) {
    $attempt = my_attempt($resultId, $sid);
    $quiz = student_quiz($resultId, $classId);
    if (!$attempt || !$quiz) {
        redirect('student/quizzes.php');
    }
    $answers = json_decode((string) ($attempt['answers'] ?? '{}'), true) ?: [];
    $qs = $pdo->prepare('SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id');
    $qs->execute([$resultId]);
    $questions = $qs->fetchAll();
    $pct = (float) $attempt['total'] > 0 ? round((float) $attempt['score'] / (float) $attempt['total'] * 100, 1) : 0;

    layout_top('Quiz Result', 'quizzes');
    ?>
    <div class="grid grid-3">
      <div class="stat primary"><div class="stat-num"><?= (float) $attempt['score'] ?> / <?= (float) $attempt['total'] ?></div><div class="stat-label">Score</div></div>
      <div class="stat <?= $pct >= 50 ? 'green' : 'amber' ?>"><div class="stat-num"><?= $pct ?>%</div><div class="stat-label"><?= $pct >= 50 ? 'Pass 🎉' : 'Keep practicing' ?></div></div>
      <div class="stat"><div class="stat-num"><?= count($questions) ?></div><div class="stat-label">Questions</div></div>
    </div>
    <div class="card">
      <div class="card-head"><h2><?= e($quiz['title']) ?> — Answer Review</h2>
        <a class="btn secondary small" href="<?= e(app_url('student/quizzes.php')) ?>">← All Quizzes</a></div>
      <div class="card-body">
        <?php foreach ($questions as $i => $q):
          $given = (string) ($answers[$q['id']] ?? '');
          $right = $given === $q['correct'];
        ?>
          <div style="padding:10px 0;border-bottom:1px solid var(--border)">
            <div><strong>Q<?= $i + 1 ?>.</strong> <?= e($q['question']) ?>
              <?= $right ? '<span class="badge green">✓ Correct</span>' : '<span class="badge red">✗ Wrong</span>' ?></div>
            <div style="margin:4px 0 0 18px;font-size:.92rem">
              <?php foreach (['A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d'] as $L => $col): ?>
                <?php if (($q[$col] ?? '') !== ''): ?>
                  <div><?= $L ?>. <?= e($q[$col]) ?>
                    <?php if ($L === $q['correct']): ?> <span class="badge green">Correct answer</span><?php endif; ?>
                    <?php if ($L === $given && !$right): ?> <span class="badge amber">Your answer</span><?php endif; ?>
                  </div>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
    layout_bottom();
    return;
}

/* ---------- List ---------- */
$st = $pdo->prepare(
    'SELECT q.*, s.name AS subject_name,
        (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.id) AS n_q,
        (SELECT COALESCE(SUM(marks),0) FROM quiz_questions WHERE quiz_id = q.id) AS total_marks
     FROM quizzes q LEFT JOIN subjects s ON s.id = q.subject_id
     WHERE q.is_published = 1 AND (q.class_id = ? OR q.class_id IS NULL)
     ORDER BY q.id DESC'
);
$st->execute([$classId]);
$quizzes = $st->fetchAll();

layout_top('Quizzes', 'quizzes');
?>
<div class="card">
  <div class="card-head"><h2>❓ Quizzes for My Class</h2></div>
  <div class="card-body">
    <div class="table-wrap"><table class="table">
      <tr><th>Quiz</th><th>Subject</th><th>Questions</th><th>Marks</th><th>My Result</th><th></th></tr>
      <?php foreach ($quizzes as $q):
        $att = my_attempt((int) $q['id'], $sid);
      ?>
      <tr>
        <td><strong><?= e($q['title']) ?></strong></td>
        <td><?= e($q['subject_name'] ?? '—') ?></td>
        <td><?= (int) $q['n_q'] ?></td>
        <td><?= (float) $q['total_marks'] ?></td>
        <td>
          <?php if ($att): ?>
            <span class="badge <?= (float) $att['total'] > 0 && (float) $att['score'] / (float) $att['total'] >= 0.5 ? 'green' : 'amber' ?>"><?= (float) $att['score'] ?> / <?= (float) $att['total'] ?></span>
          <?php else: ?>
            <span class="badge">Not attempted</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($att): ?>
            <a class="btn small secondary" href="<?= e(app_url('student/quizzes.php')) ?>?result=<?= (int) $q['id'] ?>">View Result</a>
          <?php elseif ((int) $q['n_q'] > 0): ?>
            <a class="btn small" href="<?= e(app_url('student/quizzes.php')) ?>?take=<?= (int) $q['id'] ?>">Start Quiz</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$quizzes): ?><tr><td colspan="6" class="stat-label">Abhi koi quiz available nahi hai.</td></tr><?php endif; ?>
    </table></div>
  </div>
</div>
<?php layout_bottom(); ?>
