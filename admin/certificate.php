<?php
declare(strict_types=1);
/**
 * Certificates — landscape A4 PDF with decorative border.
 * POST: student_id, type (character|leaving|merit|bonafide), custom_text,
 * reason, issue_date. No HTML output.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('POST required.');
}
verify_csrf();

$pdo = db();
$studentId = (int) ($_POST['student_id'] ?? 0);
$type = in_array($_POST['type'] ?? '', ['character', 'leaving', 'merit', 'bonafide'], true) ? $_POST['type'] : 'character';
$custom = trim((string) ($_POST['custom_text'] ?? ''));
$reason = trim((string) ($_POST['reason'] ?? ''));
$issueDate = $_POST['issue_date'] ?: today();
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $issueDate)) {
    $issueDate = today();
}

$st = $pdo->prepare(
    'SELECT s.*, c.name AS class_name, p.name AS parent_name
     FROM students s
     LEFT JOIN classes c ON c.id = s.class_id
     LEFT JOIN parents p ON p.id = s.parent_id
     WHERE s.id = ? LIMIT 1'
);
$st->execute([$studentId]);
$s = $st->fetch();
if (!$s) {
    http_response_code(404);
    die('Student not found.');
}

$school = school_profile();
$session = $school['session_year'] ?? setting('session_year');
$classLabel = trim(($s['class_name'] ?? '') . ' ' . ($s['section'] ?? ''));
$female = ($s['gender'] ?? 'Male') === 'Female';
$child = $female ? 'daughter' : 'son';
$subj = $female ? 'She' : 'He';
$poss = $female ? 'Her' : 'His';
$obj = $female ? 'her' : 'him';
$ofParent = $s['parent_name'] ? " {$child} of " . $s['parent_name'] : '';

$titles = [
    'character' => 'CHARACTER CERTIFICATE',
    'leaving' => 'SCHOOL LEAVING CERTIFICATE',
    'merit' => 'CERTIFICATE OF MERIT',
    'bonafide' => 'BONAFIDE CERTIFICATE',
];

switch ($type) {
    case 'leaving':
        $body = 'This is to certify that ' . $s['name'] . $ofParent . ', bearing Admission No. ' . $s['admission_no']
            . ', was a bonafide student of this school, studying in Class ' . $classLabel
            . ' during the session ' . $session . '. ' . $subj . ' has now left the school.'
            . ($reason !== '' ? ' Reason for leaving: ' . $reason . '.' : '')
            . ($s['dob'] ? ' According to the school record, ' . strtolower($poss) . ' date of birth is ' . fmt_date($s['dob']) . '.' : '')
            . ' We wish ' . $obj . ' all the best for the future.';
        break;
    case 'merit':
        $body = 'This certificate is proudly presented to ' . $s['name'] . $ofParent . ' of Class ' . $classLabel
            . ' in recognition of ' . ($custom !== '' ? $custom : 'outstanding performance and dedication')
            . ' during the session ' . $session . '. We congratulate ' . $obj . ' and wish ' . $obj . ' continued success.';
        break;
    case 'bonafide':
        $body = 'This is to certify that ' . $s['name'] . $ofParent . ', bearing Admission No. ' . $s['admission_no']
            . ', is a bonafide student of Class ' . $classLabel . ' at this school for the session ' . $session . '.'
            . ' This certificate is issued at the request of the student / parent for official purposes.';
        break;
    default: /* character */
        $body = 'This is to certify that ' . $s['name'] . $ofParent . ', bearing Admission No. ' . $s['admission_no']
            . ', was a bonafide student of this school in Class ' . $classLabel . ' during the session ' . $session . '. '
            . ($custom !== '' ? $custom : $poss . ' conduct and character remained excellent during ' . strtolower($poss) . ' stay at this school.')
            . ' We wish ' . $obj . ' success in all future endeavours.';
}

function pdf_text($v): string
{
    $s = iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', (string) $v);
    return $s === false ? '' : $s;
}

require_once __DIR__ . '/../includes/fpdf/fpdf.php';

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false);
$pdf->AddPage();

/* Decorative double border */
$pdf->SetDrawColor(15, 23, 42);
$pdf->SetLineWidth(1.1);
$pdf->Rect(8, 8, 281, 194, 'D');
$pdf->SetLineWidth(0.3);
$pdf->Rect(11.5, 11.5, 274, 187, 'D');

/* Header */
$pdf->SetFont('Arial', 'B', 24);
$pdf->SetXY(15, 24);
$pdf->Cell(267, 11, pdf_text($school['name'] ?? 'School'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10.5);
$sub = trim(implode('  |  ', array_filter([$school['address'] ?? '', $school['phone'] ?? '', $school['email'] ?? ''])));
if ($sub !== '') {
    $pdf->SetXY(15, 37);
    $pdf->Cell(267, 6, pdf_text($sub), 0, 1, 'C');
}

/* Title */
$pdf->SetFont('Arial', 'B', 19);
$pdf->SetXY(15, 52);
$pdf->Cell(267, 10, $titles[$type], 0, 1, 'C');
$pdf->SetDrawColor(184, 134, 11);
$pdf->SetLineWidth(0.7);
$pdf->Line(108, 65, 189, 65);
$pdf->SetDrawColor(15, 23, 42);

/* Serial + date */
$pdf->SetFont('Arial', '', 10);
$pdf->SetXY(20, 72);
$pdf->Cell(100, 6, pdf_text('Ref: CERT-' . date('Y', strtotime($issueDate)) . '-' . str_pad((string) $s['id'], 4, '0', STR_PAD_LEFT) . '-' . strtoupper($type)), 0, 0, 'L');
$pdf->SetXY(177, 72);
$pdf->Cell(100, 6, pdf_text('Date: ' . fmt_date($issueDate)), 0, 0, 'R');

/* Body */
$pdf->SetFont('Arial', '', 13.5);
$pdf->SetXY(35, 92);
$pdf->MultiCell(227, 8.5, pdf_text($body), 0, 'C');

/* Signatures */
$pdf->SetFont('Arial', '', 11);
$pdf->Line(35, 168, 95, 168);
$pdf->SetXY(35, 170);
$pdf->Cell(60, 6, 'Class Teacher', 0, 0, 'C');
$pdf->Line(202, 168, 262, 168);
$pdf->SetXY(202, 170);
$pdf->Cell(60, 6, 'Principal', 0, 0, 'C');
$pdf->SetFont('Arial', 'I', 8.5);
$pdf->SetXY(15, 188);
$pdf->Cell(267, 5, pdf_text('Generated by ' . APP_NAME . ' — this certificate is valid with the school stamp and Principal signature.'), 0, 0, 'C');

$pdf->Output('I', 'certificate-' . preg_replace('/[^A-Za-z0-9\-]/', '', $s['admission_no']) . '-' . $type . '.pdf');
exit;
