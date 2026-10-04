<?php
declare(strict_types=1);

/**
 * Quiz module — shared implementation for Admin and Teacher shells.
 * Teachers manage their own quizzes; admins manage all. Students attempt
 * published quizzes for their class and are auto-marked.
 * AI generation is optional: it only runs when an AI API key is saved in
 * Settings (see ai_json() in helpers.php); manual entry always works.
 */

function quiz_current_uid(): int
{
    return (int) (current_user()['id'] ?? 0);
}

/** Fetch a quiz the current user may manage (admin: any; teacher: own). */
function quiz_get_manageable(int $id, bool $admin): ?array
{
    $st = db()->prepare(
        'SELECT q.*, c.name AS class_name, c.section, s.name AS subject_name
         FROM quizzes q
         LEFT JOIN classes c ON c.id = q.class_id
         LEFT JOIN subjects s ON s.id = q.subject_id
         WHERE q.id = ?' . ($admin ? '' : ' AND q.created_by = ?') . ' LIMIT 1'
    );
    $st->execute($admin ? [$id] : [$id, quiz_current_uid()]);
    return $st->fetch() ?: null;
}

function quiz_class_options(?int $selected): string
{
    $out = '<option value="">— All classes (general) —</option>';
    foreach (db()->query('SELECT id, name, section FROM classes ORDER BY name, section')->fetchAll() as $c) {
        $sel = $selected !== null && (int) $c['id'] === $selected ? ' selected' : '';
        $out .= '<option value="' . (int) $c['id'] . '"' . $sel . '>' . e($c['name'] . ' ' . $c['section']) . '</option>';
    }
    return $out;
}

function quiz_subject_options(?int $selected): string
{
    $out = '<option value="">— No subject —</option>';
    foreach (db()->query('SELECT id, name FROM subjects ORDER BY name')->fetchAll() as $s) {
        $sel = $selected !== null && (int) $s['id'] === $selected ? ' selected' : '';
        $out .= '<option value="' . (int) $s['id'] . '"' . $sel . '>' . e($s['name']) . '</option>';
    }
    return $out;
}

/* ---------------- List page (create / publish / delete + table) -------- */

function quiz_handle_list_post(string $base, bool $admin): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    verify_csrf();
    $act = $_POST['form_action'] ?? '';
    $uid = quiz_current_uid();

    if ($act === 'create') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $classId = (int) ($_POST['class_id'] ?? 0) ?: null;
        $subjectId = (int) ($_POST['subject_id'] ?? 0) ?: null;
        if ($title === '') {
            flash('error', 'Quiz title is required.');
        } else {
            db()->prepare('INSERT INTO quizzes (title, class_id, subject_id, created_by) VALUES (?,?,?,?)')
                ->execute([$title, $classId, $subjectId, $uid]);
            flash('success', 'Quiz created. Ab is mein sawal add karein.');
        }
        redirect($base);
    }

    if ($act === 'toggle' || $act === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $quiz = quiz_get_manageable($id, $admin);
        if ($quiz) {
            if ($act === 'toggle') {
                db()->prepare('UPDATE quizzes SET is_published = 1 - is_published WHERE id = ?')->execute([$id]);
                flash('success', 'Quiz ' . ($quiz['is_published'] ? 'unpublished' : 'published') . '.');
            } else {
                db()->prepare('DELETE FROM quiz_attempts WHERE quiz_id = ?')->execute([$id]);
                db()->prepare('DELETE FROM quiz_questions WHERE quiz_id = ?')->execute([$id]);
                db()->prepare('DELETE FROM quizzes WHERE id = ?')->execute([$id]);
                flash('success', 'Quiz deleted.');
            }
        }
        redirect($base);
    }
}

function quiz_render_list(string $base, string $editBase, string $resultsBase, bool $admin): void
{
    $uid = quiz_current_uid();
    $sql = 'SELECT q.*, c.name AS class_name, c.section, s.name AS subject_name,
              (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.id) AS n_q,
              (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id) AS n_att,
              (SELECT COALESCE(SUM(marks),0) FROM quiz_questions WHERE quiz_id = q.id) AS total_marks
            FROM quizzes q
            LEFT JOIN classes c ON c.id = q.class_id
            LEFT JOIN subjects s ON s.id = q.subject_id'
        . ($admin ? '' : ' WHERE q.created_by = ' . $uid)
        . ' ORDER BY q.id DESC';
    $rows = db()->query($sql)->fetchAll();
    ?>
    <div class="card">
      <div class="card-head"><h2>Create Quiz</h2></div>
      <div class="card-body">
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="form_action" value="create">
          <div class="form-row">
            <div class="field"><label>Title *</label>
              <input type="text" name="title" required placeholder="e.g. Science Chapter 4 Quiz"></div>
            <div class="field"><label>Class</label>
              <select name="class_id"><?= quiz_class_options(null) ?></select></div>
            <div class="field"><label>Subject</label>
              <select name="subject_id"><?= quiz_subject_options(null) ?></select></div>
          </div>
          <button class="btn" type="submit">Create Quiz</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-head"><h2><?= $admin ? 'All Quizzes' : 'My Quizzes' ?></h2></div>
      <div class="card-body">
        <div class="table-wrap"><table class="table">
          <tr><th>Quiz</th><th>Class</th><th>Subject</th><th>Questions</th><th>Marks</th><th>Attempts</th><th>Status</th><th>Actions</th></tr>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td><strong><?= e($r['title']) ?></strong></td>
            <td><?= e(trim(($r['class_name'] ?? 'All') . ' ' . ($r['section'] ?? ''))) ?></td>
            <td><?= e($r['subject_name'] ?? '—') ?></td>
            <td><?= (int) $r['n_q'] ?></td>
            <td><?= (float) $r['total_marks'] ?></td>
            <td><?= (int) $r['n_att'] ?></td>
            <td><?= $r['is_published'] ? '<span class="badge green">Published</span>' : '<span class="badge amber">Draft</span>' ?></td>
            <td style="white-space:nowrap">
              <a class="btn small secondary" href="<?= e(app_url($editBase)) ?>?id=<?= (int) $r['id'] ?>">Questions</a>
              <a class="btn small secondary" href="<?= e(app_url($resultsBase)) ?>?id=<?= (int) $r['id'] ?>">Results</a>
              <form method="post" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="toggle">
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button class="btn small" type="submit"><?= $r['is_published'] ? 'Unpublish' : 'Publish' ?></button>
              </form>
              <form method="post" style="display:inline" onsubmit="return confirm('Delete this quiz, its questions and all attempts?')">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button class="btn small danger" type="submit">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?><tr><td colspan="8" class="stat-label">No quizzes yet. Create one above.</td></tr><?php endif; ?>
        </table></div>
        <p class="hint" style="margin-top:10px">Students sirf <strong>Published</strong> quizzes attempt kar sakte hain, aur wo bhi sirf apni class ke (ya general) quizzes.</p>
      </div>
    </div>
    <?php
}

/* ---------------- Edit page (questions + AI generation) ---------------- */

function quiz_handle_edit_post(int $quizId, string $editBase, bool $admin): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    verify_csrf();
    $quiz = quiz_get_manageable($quizId, $admin);
    if (!$quiz) {
        return;
    }
    $act = $_POST['form_action'] ?? '';

    if ($act === 'add_question') {
        $q = trim((string) ($_POST['question'] ?? ''));
        $opts = [
            'A' => trim((string) ($_POST['option_a'] ?? '')),
            'B' => trim((string) ($_POST['option_b'] ?? '')),
            'C' => trim((string) ($_POST['option_c'] ?? '')),
            'D' => trim((string) ($_POST['option_d'] ?? '')),
        ];
        $correct = in_array($_POST['correct'] ?? 'A', ['A', 'B', 'C', 'D'], true) ? $_POST['correct'] : 'A';
        $marks = max(0.25, (float) ($_POST['marks'] ?? 1));
        if ($q === '' || $opts['A'] === '' || $opts['B'] === '') {
            flash('error', 'Sawal aur kam az kam 2 options (A, B) zaroor likhein.');
        } else {
            db()->prepare('INSERT INTO quiz_questions (quiz_id, question, option_a, option_b, option_c, option_d, correct, marks)
                           VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$quizId, $q, $opts['A'], $opts['B'], $opts['C'], $opts['D'], $correct, $marks]);
            flash('success', 'Question added.');
        }
        redirect($editBase . '?id=' . $quizId);
    }

    if ($act === 'delete_question') {
        db()->prepare('DELETE FROM quiz_questions WHERE id = ? AND quiz_id = ?')
            ->execute([(int) ($_POST['question_id'] ?? 0), $quizId]);
        flash('success', 'Question deleted.');
        redirect($editBase . '?id=' . $quizId);
    }

    if ($act === 'ai_generate') {
        $topic = trim((string) ($_POST['topic'] ?? ''));
        $count = max(1, min(20, (int) ($_POST['count'] ?? 5)));
        if ($topic === '') {
            flash('error', 'Topic likhein jis par sawal chahiye.');
            redirect($editBase . '?id=' . $quizId);
        }
        if (setting('ai_api_key') === '') {
            flash('error', 'AI abhi set nahi hai. Admin Settings mein AI API key add karein — ya sawal manually add karein, wo hamesha chalta hai.');
            redirect($editBase . '?id=' . $quizId);
        }
        $system = 'You create multiple-choice questions for school students. Reply with ONLY a JSON array. '
            . 'Each item: {"question": string, "options": [string, string, string, string], "correct": "A"|"B"|"C"|"D"}. No other text.';
        $user = 'Create ' . $count . ' MCQs on the topic: ' . $topic
            . ($quiz['class_name'] ? ' for class ' . $quiz['class_name'] : '')
            . ($quiz['subject_name'] ? ' (' . $quiz['subject_name'] . ')' : '') . '.';
        $res = ai_json($system, $user);
        $items = null;
        if (is_array($res)) {
            $items = isset($res['questions']) && is_array($res['questions']) ? $res['questions'] : $res;
        }
        $added = 0;
        if (is_array($items)) {
            $ins = db()->prepare('INSERT INTO quiz_questions (quiz_id, question, option_a, option_b, option_c, option_d, correct, marks)
                                  VALUES (?,?,?,?,?,?,?,1)');
            foreach ($items as $it) {
                if (!is_array($it) || empty($it['question']) || !is_array($it['options'] ?? null) || count($it['options']) < 2) {
                    continue;
                }
                $o = array_values($it['options']) + [2 => '', 3 => ''];
                $corr = strtoupper((string) ($it['correct'] ?? 'A'));
                if (!in_array($corr, ['A', 'B', 'C', 'D'], true)) {
                    $corr = 'A';
                }
                $ins->execute([$quizId, (string) $it['question'], (string) $o[0], (string) $o[1], (string) ($o[2] ?? ''), (string) ($o[3] ?? ''), $corr]);
                $added++;
            }
        }
        flash($added ? 'success' : 'error', $added
            ? "AI ne $added sawal bana diye. Publish karne se pehle ek nazar check kar lein."
            : 'AI se sawal nahi ban sake. Dobara try karein ya manually add karein.');
        redirect($editBase . '?id=' . $quizId);
    }
}

function quiz_render_edit(int $quizId, string $listBase, bool $admin): void
{
    $quiz = quiz_get_manageable($quizId, $admin);
    if (!$quiz) {
        echo '<div class="alert alert-error">Quiz not found.</div>';
        return;
    }
    $st = db()->prepare('SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id');
    $st->execute([$quizId]);
    $questions = $st->fetchAll();
    $aiReady = setting('ai_api_key') !== '';
    ?>
    <p><a class="btn secondary small" href="<?= e(app_url($listBase)) ?>">← All Quizzes</a></p>
    <div class="card">
      <div class="card-head"><h2><?= e($quiz['title']) ?></h2>
        <span><?= $quiz['is_published'] ? '<span class="badge green">Published</span>' : '<span class="badge amber">Draft</span>' ?></span></div>
      <div class="card-body">
        <p class="hint">Class: <?= e(trim(($quiz['class_name'] ?? 'All classes') . ' ' . ($quiz['section'] ?? ''))) ?> · Subject: <?= e($quiz['subject_name'] ?? '—') ?> · Questions: <?= count($questions) ?></p>
      </div>
    </div>

    <div class="grid grid-2">
      <div class="card">
        <div class="card-head"><h3>➕ Add Question</h3></div>
        <div class="card-body">
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="add_question">
            <div class="field"><label>Question *</label>
              <textarea name="question" rows="2" required style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px"></textarea></div>
            <?php foreach (['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'] as $k => $L): ?>
            <div class="field"><label>Option <?= $L ?><?= $k <= 'b' ? ' *' : '' ?></label>
              <input type="text" name="option_<?= $k ?>" <?= $k <= 'b' ? 'required' : '' ?>></div>
            <?php endforeach; ?>
            <div class="form-row">
              <div class="field"><label>Correct Option</label>
                <select name="correct">
                  <option value="A">A</option><option value="B">B</option>
                  <option value="C">C</option><option value="D">D</option>
                </select></div>
              <div class="field"><label>Marks</label>
                <input type="number" name="marks" value="1" step="0.25" min="0.25"></div>
            </div>
            <button class="btn" type="submit">Add Question</button>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-head"><h3>🤖 AI se Sawal Banayein</h3></div>
        <div class="card-body">
          <?php if (!$aiReady): ?>
            <p class="stat-label">AI abhi configured nahi hai. Admin pehle <strong>Settings → SMS &amp; AI</strong> mein apni AI API key add kare — phir ye button kaam karega. Manual sawal add karna hamesha available hai.</p>
          <?php endif; ?>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="form_action" value="ai_generate">
            <div class="field"><label>Topic</label>
              <input type="text" name="topic" placeholder="e.g. Photosynthesis, Fractions, Past tense" <?= $aiReady ? 'required' : 'disabled' ?>></div>
            <div class="field"><label>Kitne sawal</label>
              <input type="number" name="count" value="5" min="1" max="20" <?= $aiReady ? '' : 'disabled' ?>></div>
            <button class="btn secondary" type="submit" <?= $aiReady ? '' : 'disabled' ?>>Generate Questions</button>
          </form>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-head"><h3>Questions (<?= count($questions) ?>)</h3></div>
      <div class="card-body">
        <?php foreach ($questions as $i => $q): ?>
          <div style="padding:10px 0;border-bottom:1px solid var(--border)">
            <div><strong>Q<?= $i + 1 ?>.</strong> <?= e($q['question']) ?> <small style="color:var(--muted)">(<?= (float) $q['marks'] ?> marks)</small></div>
            <div style="margin:4px 0 0 18px;font-size:.92rem">
              <?php foreach (['A' => 'option_a', 'B' => 'option_b', 'C' => 'option_c', 'D' => 'option_d'] as $L => $col): ?>
                <?php if (($q[$col] ?? '') !== ''): ?>
                  <div><?= $L ?>. <?= e($q[$col]) ?> <?= $q['correct'] === $L ? ' <span class="badge green">✓ Correct</span>' : '' ?></div>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
            <form method="post" style="margin-top:6px" onsubmit="return confirm('Delete this question?')">
              <?= csrf_field() ?>
              <input type="hidden" name="form_action" value="delete_question">
              <input type="hidden" name="question_id" value="<?= (int) $q['id'] ?>">
              <button class="btn small danger" type="submit">Delete</button>
            </form>
          </div>
        <?php endforeach; ?>
        <?php if (!$questions): ?><p class="stat-label">Abhi koi sawal nahi. Upar se add karein.</p><?php endif; ?>
      </div>
    </div>
    <?php
}

/* ---------------- Results page ---------------- */

function quiz_render_results(int $quizId, string $listBase, bool $admin): void
{
    $quiz = quiz_get_manageable($quizId, $admin);
    if (!$quiz) {
        echo '<div class="alert alert-error">Quiz not found.</div>';
        return;
    }
    $st = db()->prepare(
        'SELECT a.*, s.name AS student_name, s.admission_no, c.name AS class_name, s.section
         FROM quiz_attempts a
         JOIN students s ON s.id = a.student_id
         LEFT JOIN classes c ON c.id = s.class_id
         WHERE a.quiz_id = ? ORDER BY a.score DESC, a.submitted_at'
    );
    $st->execute([$quizId]);
    $rows = $st->fetchAll();
    ?>
    <p><a class="btn secondary small" href="<?= e(app_url($listBase)) ?>">← All Quizzes</a></p>
    <div class="card">
      <div class="card-head"><h2>Results — <?= e($quiz['title']) ?></h2></div>
      <div class="card-body">
        <div class="table-wrap"><table class="table">
          <tr><th>#</th><th>Student</th><th>Class</th><th>Score</th><th>%</th><th>Submitted</th></tr>
          <?php foreach ($rows as $i => $r):
            $pct = (float) $r['total'] > 0 ? round((float) $r['score'] / (float) $r['total'] * 100, 1) : 0;
          ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><strong><?= e($r['student_name']) ?></strong><br><small style="color:var(--muted)"><?= e($r['admission_no']) ?></small></td>
            <td><?= e(trim(($r['class_name'] ?? '') . ' ' . $r['section'])) ?></td>
            <td><?= (float) $r['score'] ?> / <?= (float) $r['total'] ?></td>
            <td><strong><?= $pct ?>%</strong></td>
            <td><?= e(fmt_date(substr((string) $r['submitted_at'], 0, 10))) ?> <?= e(date('h:i A', strtotime((string) $r['submitted_at']))) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?><tr><td colspan="6" class="stat-label">Abhi kisi student ne attempt nahi kiya.</td></tr><?php endif; ?>
        </table></div>
      </div>
    </div>
    <?php
}
