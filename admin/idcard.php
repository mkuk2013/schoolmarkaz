<?php
declare(strict_types=1);
/**
 * ID Cards — PDF generator: CR80-size cards (88 × 55 mm), 8 per A4 page,
 * with photo, details and a scannable Code 39 barcode of the admission /
 * staff number (the same code the Gate Attendance page scans).
 * Accepts POST ids[] + kind, or GET ?kind=&id=.
 * No HTML output: FPDF writes the document directly.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$pdo = db();
$kind = ($_POST['kind'] ?? $_GET['kind'] ?? 'student') === 'staff' ? 'staff' : 'student';

$ids = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ((array) ($_POST['ids'] ?? []) as $v) {
        $ids[] = (int) $v;
    }
} elseif (isset($_GET['id'])) {
    $ids[] = (int) $_GET['id'];
}
$ids = array_values(array_filter(array_unique($ids)));
if (!$ids) {
    http_response_code(400);
    die('Nobody selected.');
}

$in = implode(',', array_fill(0, count($ids), '?'));
if ($kind === 'student') {
    $st = $pdo->prepare(
        "SELECT s.id, s.name, s.admission_no AS code, s.photo, s.section,
                c.name AS class_name, p.phone AS contact
         FROM students s
         LEFT JOIN classes c ON c.id = s.class_id
         LEFT JOIN parents p ON p.id = s.parent_id
         WHERE s.id IN ($in) ORDER BY s.name"
    );
} else {
    $st = $pdo->prepare(
        "SELECT id, name, staff_no AS code, subject, phone AS contact
         FROM teachers WHERE id IN ($in) ORDER BY name"
    );
}
$st->execute($ids);
$people = $st->fetchAll();
if (!$people) {
    http_response_code(404);
    die('Nobody found.');
}

$school = school_profile();
$session = $school['session_year'] ?? setting('session_year');

function pdf_text($v): string
{
    $s = iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', (string) $v);
    return $s === false ? '' : $s;
}

require_once __DIR__ . '/../includes/fpdf/fpdf.php';
require_once __DIR__ . '/../includes/barcode.php';

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false);

$cardW = 88.0;
$cardH = 55.0;
$colsX = [13.0, 109.0];
$rowsY = [12.0, 73.0, 134.0, 195.0];

$drawCard = function (array $p) use ($pdf, $school, $session, $kind, $cardW, $cardH) {
    static $slot = 0;
    if ($slot % 8 === 0) {
        $pdf->AddPage();
    }
    $i = $slot % 8;
    $slot++;
    $x = [13.0, 109.0][(int) ($i % 2)];
    $y = [12.0, 73.0, 134.0, 195.0][(int) ($i / 2)];

    /* Border */
    $pdf->SetDrawColor(15, 23, 42);
    $pdf->SetLineWidth(0.5);
    $pdf->Rect($x, $y, $cardW, $cardH, 'D');
    $pdf->SetLineWidth(0.2);

    /* Header band */
    $pdf->SetFillColor(15, 23, 42);
    $pdf->Rect($x, $y, $cardW, 10.5, 'F');
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->SetXY($x + 2, $y + 1.2);
    $pdf->Cell($cardW - 30, 4, pdf_text($school['name'] ?? 'School'), 0, 0, 'L');
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetXY($x + $cardW - 28, $y + 1.2);
    $pdf->Cell(26, 4, $kind === 'staff' ? 'STAFF ID CARD' : 'STUDENT ID CARD', 0, 0, 'R');
    $pdf->SetFont('Arial', '', 6.5);
    $pdf->SetXY($x + 2, $y + 5.6);
    $pdf->Cell($cardW - 4, 3.6, pdf_text(trim(implode('  |  ', array_filter([$school['address'] ?? '', $school['phone'] ?? ''])))), 0, 0, 'L');
    $pdf->SetTextColor(0, 0, 0);

    /* Photo */
    $photoPath = ($kind === 'student' && !empty($p['photo'])) ? __DIR__ . '/../' . $p['photo'] : '';
    $drawn = false;
    if ($photoPath !== '' && is_file($photoPath)) {
        $ext = strtolower(pathinfo($photoPath, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            try {
                $pdf->Image($photoPath, $x + 2.5, $y + 12.5, 19, 24);
                $drawn = true;
            } catch (Throwable $e) {
                $drawn = false;
            }
        }
    }
    if (!$drawn) {
        $pdf->SetDrawColor(150, 150, 150);
        $pdf->Rect($x + 2.5, $y + 12.5, 19, 24, 'D');
        $pdf->SetDrawColor(15, 23, 42);
        $pdf->SetFont('Arial', 'B', 13);
        $initials = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string) $p['name']) ?: 'S', 0, 1));
        $pdf->SetXY($x + 2.5, $y + 21);
        $pdf->Cell(19, 6, $initials, 0, 0, 'C');
    }

    /* Details */
    $fx = $x + 24;
    $pdf->SetFont('Arial', 'B', 10.5);
    $pdf->SetXY($fx, $y + 12.5);
    $pdf->Cell($cardW - 26, 5.5, pdf_text($p['name']), 0, 1);
    $pdf->SetFont('Arial', '', 7.8);
    $lines = [];
    if ($kind === 'student') {
        $lines[] = ['Adm No:', $p['code']];
        $lines[] = ['Class:', trim(($p['class_name'] ?? '') . ' ' . ($p['section'] ?? ''))];
        $lines[] = ['Session:', $session];
        $lines[] = ['Guardian Ph:', $p['contact'] ?: '—'];
    } else {
        $lines[] = ['Staff No:', $p['code']];
        $lines[] = ['Subject:', $p['subject'] ?: '—'];
        $lines[] = ['Phone:', $p['contact'] ?: '—'];
        $lines[] = ['Session:', $session];
    }
    $ly = $y + 19.5;
    foreach ($lines as [$k, $v]) {
        $pdf->SetFont('Arial', 'B', 7.8);
        $pdf->SetXY($fx, $ly);
        $pdf->Cell(21, 4.4, $k, 0, 0);
        $pdf->SetFont('Arial', '', 7.8);
        $pdf->Cell($cardW - 47, 4.4, pdf_text($v), 0, 0);
        $ly += 4.8;
    }

    /* Barcode */
    $code = code39_clean((string) $p['code']);
    $bw = code39_width($code);
    $bx = $x + ($cardW - $bw) / 2;
    draw_code39($pdf, $bx, $y + 41, $code, 8.0);
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetXY($x, $y + 49.6);
    $pdf->Cell($cardW, 4, pdf_text($p['code']), 0, 0, 'C');
};

foreach ($people as $p) {
    $drawCard($p);
}

$pdf->Output('I', ($kind === 'staff' ? 'staff' : 'student') . '-id-cards.pdf');
exit;
