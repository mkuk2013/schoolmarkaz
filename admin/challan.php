<?php
declare(strict_types=1);
/**
 * Fees & Finance — 3-copy PDF bank challan (Bank / School / Student copies
 * on one A4 page per invoice). Accepts GET ?id= or POST ids[] (bulk).
 * No HTML output: FPDF writes the document directly.
 */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$pdo = db();

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
    die('No invoice selected.');
}

$in = implode(',', array_fill(0, count($ids), '?'));
$st = $pdo->prepare(
    "SELECT i.*, s.name AS student_name, s.admission_no,
            c.name AS class_name, c.section
     FROM fee_invoices i
     JOIN students s ON s.id = i.student_id
     LEFT JOIN classes c ON c.id = s.class_id
     WHERE i.id IN ($in)
     ORDER BY s.name"
);
$st->execute($ids);
$invoices = $st->fetchAll();
if (!$invoices) {
    http_response_code(404);
    die('Invoice not found.');
}

$itemSt = $pdo->prepare('SELECT label, amount FROM fee_invoice_items WHERE invoice_id = ? ORDER BY id');

$school = school_profile();
$bankName  = setting('pay_bank_name');
$bankTitle = setting('pay_bank_title');
$bankIban  = setting('pay_bank_iban');

/** FPDF uses latin-1: transliterate safely. */
function pdf_text($v): string
{
    $s = iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', (string) $v);
    return $s === false ? '' : $s;
}
function pdf_money($v): string
{
    return pdf_text(CURRENCY . ' ' . number_format((float) $v, 0));
}

require_once __DIR__ . '/../includes/fpdf/fpdf.php';

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->SetMargins(10, 8, 10);
$pdf->SetAutoPageBreak(false);

$copies = ['BANK COPY', 'SCHOOL COPY', 'STUDENT COPY'];
$slotTop = [8, 104, 200];           // three copy slots on the A4 page
$left = 10;
$width = 190;

$dashed = function (float $y) use ($pdf, $left, $width) {
    $pdf->SetDrawColor(150, 150, 150);
    for ($x = $left; $x < $left + $width - 3; $x += 5) {
        $pdf->Line($x, $y, $x + 3, $y);
    }
    $pdf->SetDrawColor(0, 0, 0);
};

foreach ($invoices as $inv) {
    $itemSt->execute([$inv['id']]);
    $items = $itemSt->fetchAll();
    $payable = (float) $inv['total'] + (float) $inv['fine'] - (float) $inv['discount'];
    $balance = max(0.0, $payable - (float) $inv['paid']);
    $challanNo = 'CH-' . $inv['year'] . '-' . str_pad((string) $inv['id'], 5, '0', STR_PAD_LEFT);

    $pdf->AddPage();

    foreach ($copies as $ci => $copyLabel) {
        $pdf->SetY($slotTop[$ci]);

        /* Header: school name + copy label */
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(140, 6.5, pdf_text($school['name'] ?? 'School'), 0, 0);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(230, 235, 245);
        $pdf->Cell(50, 6.5, $copyLabel, 1, 1, 'C', true);
        $pdf->SetFont('Arial', '', 8);
        $sub = trim(implode('  |  ', array_filter([
            $school['address'] ?? '',
            $school['phone'] ?? '',
            $school['email'] ?? '',
        ])));
        if ($sub !== '') {
            $pdf->Cell(0, 4.5, pdf_text($sub), 0, 1);
        }

        /* Challan title + bank line */
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 6, 'FEE CHALLAN', 0, 1);
        $pdf->SetFont('Arial', '', 8.5);
        $bankLine = $bankName !== ''
            ? 'Pay at: ' . $bankName . ($bankTitle !== '' ? '  |  A/C Title: ' . $bankTitle : '') . ($bankIban !== '' ? '  |  IBAN: ' . $bankIban : '')
            : 'Pay at the school office.';
        $pdf->Cell(0, 4.5, pdf_text($bankLine), 0, 1);
        $pdf->Ln(1);

        /* Meta grid: 4 columns */
        $meta = [
            ['Challan No:', $challanNo, 'Issue Date:', fmt_date(today())],
            ['Student:', $inv['student_name'], 'Adm No:', $inv['admission_no']],
            ['Class:', trim(($inv['class_name'] ?? '') . ' ' . ($inv['section'] ?? '')), 'Fee Month:', month_name((int) $inv['month']) . ' ' . $inv['year']],
            ['Due Date:', fmt_date($inv['due_date']), 'Status:', ucfirst($inv['status'])],
        ];
        foreach ($meta as $row) {
            $pdf->SetFont('Arial', 'B', 8.5);
            $pdf->Cell(24, 4.8, pdf_text($row[0]), 0, 0);
            $pdf->SetFont('Arial', '', 8.5);
            $pdf->Cell(66, 4.8, pdf_text($row[1]), 0, 0);
            $pdf->SetFont('Arial', 'B', 8.5);
            $pdf->Cell(24, 4.8, pdf_text($row[2]), 0, 0);
            $pdf->SetFont('Arial', '', 8.5);
            $pdf->Cell(0, 4.8, pdf_text($row[3]), 0, 1);
        }
        $pdf->Ln(1);

        /* Items */
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->SetFillColor(230, 235, 245);
        $pdf->Cell(140, 5.5, 'Description', 1, 0, 'L', true);
        $pdf->Cell(50, 5.5, 'Amount', 1, 1, 'R', true);
        $pdf->SetFont('Arial', '', 8.5);
        foreach ($items as $it) {
            $pdf->Cell(140, 5, pdf_text($it['label']), 1, 0);
            $pdf->Cell(50, 5, pdf_money($it['amount']), 1, 1, 'R');
        }
        $tot = function (string $label, $value, bool $bold = false) use ($pdf) {
            $pdf->SetFont('Arial', $bold ? 'B' : '', 8.5);
            $pdf->Cell(140, 5, pdf_text($label), 1, 0);
            $pdf->Cell(50, 5, pdf_money($value), 1, 1, 'R');
        };
        if ((float) $inv['discount'] > 0) {
            $tot('Discount', -(float) $inv['discount']);
        }
        if ((float) $inv['fine'] > 0) {
            $tot('Fine', (float) $inv['fine']);
        }
        if ((float) $inv['paid'] > 0) {
            $tot('Already paid', -(float) $inv['paid']);
        }
        $pdf->SetFillColor(230, 235, 245);
        $pdf->SetFont('Arial', 'B', 9.5);
        $pdf->Cell(140, 6, 'AMOUNT PAYABLE', 1, 0, 'L', true);
        $pdf->Cell(50, 6, pdf_money($balance), 1, 1, 'R', true);

        /* Copy footer */
        $pdf->Ln(1.5);
        $pdf->SetFont('Arial', '', 8);
        $foot = $copyLabel === 'BANK COPY'
            ? 'Bank stamp & signature: ______________________'
            : ($copyLabel === 'SCHOOL COPY' ? 'Received by (school): ______________________' : 'Please keep this copy safe as proof of payment.');
        $pdf->Cell(120, 4.5, pdf_text($foot), 0, 0);
        $pdf->SetFont('Arial', 'I', 7.5);
        $pdf->Cell(0, 4.5, pdf_text('Generated by ' . APP_NAME), 0, 1, 'R');

        if ($ci < 2) {
            $dashed($slotTop[$ci + 1] - 4);
        }
    }
}

$pdf->Output('I', 'challans.pdf');
exit;
