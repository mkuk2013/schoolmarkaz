<?php
declare(strict_types=1);
/**
 * Gate Attendance — barcode/ID scan at the school gate.
 * A USB barcode scanner (keyboard type) or the phone camera keyboard types
 * the admission no / staff no and presses Enter. Entry scans mark today's
 * attendance Present and record a parent alert (SMS when configured).
 * Also supports manual entry and biometric-machine CSV import.
 * navkey: 'gate'
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$pdo = db();
$me = (int) (current_user()['id'] ?? 0) ?: null;
$schoolName = school_profile()['name'] ?? APP_NAME;

/** Find a person by scanned code: student admission_no first, then teacher staff_no. */
function gate_find(string $code): ?array
{
    $pdo = db();
    $st = $pdo->prepare('SELECT s.id, s.name, s.admission_no AS code, s.photo,
                                c.name AS class_name, s.section
                         FROM students s LEFT JOIN classes c ON c.id = s.class_id
                         WHERE LOWER(s.admission_no) = LOWER(?) AND s.status = \'Active\' LIMIT 1');
    $st->execute([$code]);
    $r = $st->fetch();
    if ($r) {
        $r['type'] = 'student';
        $r['sub'] = trim(($r['class_name'] ?? '') . ' ' . ($r['section'] ?? ''));
        return $r;
    }
    $st = $pdo->prepare('SELECT id, name, staff_no AS code, subject AS sub
                         FROM teachers WHERE LOWER(staff_no) = LOWER(?) AND status = \'Active\' LIMIT 1');
    $st->execute([$code]);
    $r = $st->fetch();
    if ($r) {
        $r['type'] = 'teacher';
        $r['photo'] = '';
        $r['sub'] = $r['sub'] ?: 'Teacher';
        return $r;
    }
    return null;
}

/** Record one gate event: log + attendance on entry + parent alert for students. */
function gate_record(array $person, string $dir, string $source, ?string $when = null): void
{
    $pdo = db();
    $when = $when ?: date('Y-m-d H:i:s');
    $date = substr($when, 0, 10);

    if ($when !== null && $source === 'import') {
        $pdo->prepare('INSERT INTO gate_logs (person_type, person_id, direction, source, created_at) VALUES (?,?,?,?,?)')
            ->execute([$person['type'], $person['id'], $dir, $source, $when]);
    } else {
        $pdo->prepare('INSERT INTO gate_logs (person_type, person_id, direction, source) VALUES (?,?,?,?)')
            ->execute([$person['type'], $person['id'], $dir, $source]);
    }

    if ($dir === 'in') {
        if ($person['type'] === 'student') {
            $pdo->prepare("INSERT INTO student_attendance (student_id, `date`, status, marked_by) VALUES (?, ?, 'P', ?)
                           ON DUPLICATE KEY UPDATE status = 'P', marked_by = VALUES(marked_by)")
                ->execute([$person['id'], $date, $GLOBALS['gate_me'] ?? null]);
            if ($source !== 'import') {
                notify_parent((int) $person['id'],
                    'Assalam-o-Alaikum! Your child ' . $person['name'] . ' (' . $person['code'] . ') entered '
                    . $GLOBALS['gate_school'] . ' at ' . date('h:i A', strtotime($when)) . '.');
            }
        } else {
            $pdo->prepare("INSERT INTO staff_attendance (teacher_id, `date`, status, marked_by) VALUES (?, ?, 'P', ?)
                           ON DUPLICATE KEY UPDATE status = 'P', marked_by = VALUES(marked_by)")
                ->execute([$person['id'], $date, $GLOBALS['gate_me'] ?? null]);
        }
    } elseif ($dir === 'out' && $person['type'] === 'student' && $source !== 'import') {
        notify_parent((int) $person['id'],
            'Assalam-o-Alaikum! Your child ' . $person['name'] . ' (' . $person['code'] . ') left '
            . $GLOBALS['gate_school'] . ' at ' . date('h:i A', strtotime($when)) . '.');
    }
}
$GLOBALS['gate_me'] = $me;
$GLOBALS['gate_school'] = $schoolName;

$lastScan = null;
$scanError = '';

/* ---------- Scan ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'scan') {
    verify_csrf();
    $code = trim($_POST['code'] ?? '');
    $mode = in_array($_POST['mode'] ?? 'auto', ['auto', 'in', 'out'], true) ? $_POST['mode'] : 'auto';
    $person = $code !== '' ? gate_find($code) : null;
    if (!$person) {
        $scanError = 'Code not recognized: ' . $code;
    } else {
        $dir = $mode;
        if ($mode === 'auto') {
            $st = $pdo->prepare('SELECT direction FROM gate_logs
                                 WHERE person_type = ? AND person_id = ? AND DATE(created_at) = ?
                                 ORDER BY id DESC LIMIT 1');
            $st->execute([$person['type'], $person['id'], today()]);
            $last = $st->fetchColumn();
            $dir = $last === 'in' ? 'out' : 'in';
        }
        gate_record($person, $dir, 'scan');
        $lastScan = $person + ['dir' => $dir, 'time' => date('h:i A')];
    }
}

/* ---------- Manual entry ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'manual') {
    verify_csrf();
    $sid = (int) ($_POST['student_id'] ?? 0);
    $dir = ($_POST['direction'] ?? 'in') === 'out' ? 'out' : 'in';
    $st = $pdo->prepare('SELECT s.id, s.name, s.admission_no AS code, s.photo, c.name AS class_name, s.section
                         FROM students s LEFT JOIN classes c ON c.id = s.class_id WHERE s.id = ? LIMIT 1');
    $st->execute([$sid]);
    $person = $st->fetch();
    if ($person) {
        $person['type'] = 'student';
        $person['sub'] = trim(($person['class_name'] ?? '') . ' ' . ($person['section'] ?? ''));
        gate_record($person, $dir, 'manual');
        $lastScan = $person + ['dir' => $dir, 'time' => date('h:i A')];
    } else {
        $scanError = 'Please select a student.';
    }
}

/* ---------- Biometric CSV import ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'import') {
    verify_csrf();
    $f = $_FILES['csv'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK) {
        flash('error', 'Please choose a CSV file.');
    } else {
        $ok = 0; $skip = 0;
        $fh = fopen($f['tmp_name'], 'r');
        if ($fh) {
            while (($cols = fgetcsv($fh)) !== false) {
                $code = trim((string) ($cols[0] ?? ''));
                $dtRaw = trim((string) ($cols[1] ?? ''));
                $ts = $dtRaw !== '' ? strtotime($dtRaw) : false;
                if ($code === '' || $ts === false) { $skip++; continue; }
                $person = gate_find($code);
                if (!$person) { $skip++; continue; }
                $d = strtolower(trim((string) ($cols[2] ?? 'in')));
                $dir = in_array($d, ['out', 'exit', 'o'], true) ? 'out' : 'in';
                gate_record($person, $dir, 'import', date('Y-m-d H:i:s', $ts));
                $ok++;
            }
            fclose($fh);
        }
        flash($ok ? 'success' : 'error', "Biometric import: $ok record(s) added, $skip skipped. (No SMS sent for imported records.)");
    }
    redirect('admin/gate.php');
}

/* ---------- Today's log + stats ---------- */
$logs = $pdo->query(
    'SELECT * FROM gate_logs WHERE DATE(created_at) = CURDATE() ORDER BY id DESC LIMIT 30'
)->fetchAll();
$sIds = array_unique(array_column(array_filter($logs, fn($l) => $l['person_type'] === 'student'), 'person_id'));
$tIds = array_unique(array_column(array_filter($logs, fn($l) => $l['person_type'] === 'teacher'), 'person_id'));
$sNames = $tNames = [];
if ($sIds) {
    $st = $pdo->query('SELECT id, name FROM students WHERE id IN (' . implode(',', array_map('intval', $sIds)) . ')');
    foreach ($st->fetchAll() as $r) $sNames[(int) $r['id']] = $r['name'];
}
if ($tIds) {
    $st = $pdo->query('SELECT id, name FROM teachers WHERE id IN (' . implode(',', array_map('intval', $tIds)) . ')');
    foreach ($st->fetchAll() as $r) $tNames[(int) $r['id']] = $r['name'];
}
$statIn = (int) $pdo->query("SELECT COUNT(*) FROM gate_logs WHERE DATE(created_at) = CURDATE() AND direction = 'in'")->fetchColumn();
$statOut = (int) $pdo->query("SELECT COUNT(*) FROM gate_logs WHERE DATE(created_at) = CURDATE() AND direction = 'out'")->fetchColumn();
$statPresent = (int) $pdo->query('SELECT COUNT(*) FROM student_attendance WHERE `date` = CURDATE() AND status = \'P\'')->fetchColumn();
$students = $pdo->query("SELECT id, name, admission_no FROM students WHERE status = 'Active' ORDER BY name LIMIT 500")->fetchAll();

layout_top('Gate Attendance', 'gate');
?>

<div class="grid grid-3">
  <div class="stat primary"><div class="stat-num"><?= $statIn ?></div><div class="stat-label">Entries today</div></div>
  <div class="stat amber"><div class="stat-num"><?= $statOut ?></div><div class="stat-label">Exits today</div></div>
  <div class="stat green"><div class="stat-num"><?= $statPresent ?></div><div class="stat-label">Students present today</div></div>
</div>

<?php if ($lastScan): ?>
  <div class="alert alert-success" style="font-size:1.05rem">
    <?= $lastScan['dir'] === 'in' ? '✅ ENTRY' : '🚪 EXIT' ?> —
    <strong><?= e($lastScan['name']) ?></strong> (<?= e($lastScan['code']) ?>) · <?= e($lastScan['sub']) ?> · <?= e($lastScan['time']) ?>
  </div>
<?php endif; ?>
<?php if ($scanError): ?><div class="alert alert-error"><?= e($scanError) ?></div><?php endif; ?>

<div class="grid grid-2">
  <div class="card">
    <div class="card-head"><h3>📷 Scan ID Card</h3></div>
    <div class="card-body">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="form_action" value="scan">
        <div class="field"><label>Admission No / Staff No</label>
          <input type="text" name="code" id="scanCode" autocomplete="off" autofocus
                 placeholder="Scan barcode or type the number & press Enter"
                 style="font-size:1.25rem;padding:14px"></div>
        <div class="field"><label>Mode</label>
          <select name="mode">
            <option value="auto" selected>Auto (first scan = entry, next = exit)</option>
            <option value="in">Entry only</option>
            <option value="out">Exit only</option>
          </select></div>
        <button class="btn" type="submit" style="width:100%">Record Scan</button>
      </form>
      <p class="hint" style="margin-top:10px">Works with any USB barcode scanner and with the ID cards from the ID Cards page. Entry also marks attendance and alerts the parent.</p>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-head"><h3>✍ Manual Entry (Student)</h3></div>
      <div class="card-body">
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="form_action" value="manual">
          <div class="field"><label>Student</label>
            <select name="student_id" required>
              <option value="">— Select student —</option>
              <?php foreach ($students as $s): ?>
                <option value="<?= (int) $s['id'] ?>"><?= e($s['name'] . ' (' . $s['admission_no'] . ')') ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="field"><label>Direction</label>
            <select name="direction"><option value="in">Entry</option><option value="out">Exit</option></select></div>
          <button class="btn secondary" type="submit">Record</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-head"><h3>🖐 Biometric Log Import (CSV)</h3></div>
      <div class="card-body">
        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="form_action" value="import">
          <div class="field"><label>CSV file — columns: code, date-time, in/out</label>
            <input type="file" name="csv" accept=".csv,text/csv" required></div>
          <button class="btn secondary" type="submit">Import</button>
        </form>
        <p class="hint" style="margin-top:8px">Export from your biometric machine as CSV in that order. Imported entries update attendance but do not send SMS.</p>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head"><h3>Today's Gate Log</h3></div>
  <div class="card-body">
    <div class="table-wrap"><table class="table">
      <tr><th>Time</th><th>Name</th><th>Type</th><th>Direction</th><th>Source</th></tr>
      <?php foreach ($logs as $l):
        $nm = $l['person_type'] === 'student' ? ($sNames[(int) $l['person_id']] ?? '#'.$l['person_id']) : ($tNames[(int) $l['person_id']] ?? '#'.$l['person_id']);
      ?>
      <tr>
        <td><?= e(date('h:i A', strtotime((string) $l['created_at']))) ?></td>
        <td><strong><?= e($nm) ?></strong></td>
        <td><?= e(ucfirst($l['person_type'])) ?></td>
        <td><?= $l['direction'] === 'in' ? '<span class="badge green">IN</span>' : '<span class="badge amber">OUT</span>' ?></td>
        <td><?= e(ucfirst($l['source'])) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$logs): ?><tr><td colspan="5" class="stat-label">No gate records yet today.</td></tr><?php endif; ?>
    </table></div>
  </div>
</div>

<script>document.getElementById('scanCode')?.focus();</script>
<?php layout_bottom(); ?>
