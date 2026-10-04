<?php
declare(strict_types=1);

/**
 * Code 39 barcode drawing for FPDF (no external dependencies).
 *
 * Encoding table after Gregory Pittman's code39.py (Scribus, GPL), whose
 * patterns were validated against real Code 39 barcodes; cross-checked
 * against the Code 39 spec anchors ('*' element sequence; 'P' = bars
 * NWWNN + wide 4th space; exactly 2 wide bars + 1 wide space per symbol,
 * except $ / + % which use wide spaces only).
 *
 * Pattern notation: a string of elements in draw order — 'n'/'w' are
 * narrow/wide BARS; an 's' directly after a bar makes that bar's following
 * space wide (otherwise spaces are narrow). '*' is the start/stop symbol.
 */
const CODE39_TABLE = [
    '0' => 'nnswwn', '1' => 'wnsnnw', '2' => 'nwsnnw', '3' => 'wwsnnn', '4' => 'nnswnw',
    '5' => 'wnswnn', '6' => 'nwswnn', '7' => 'nnsnww', '8' => 'wnsnwn', '9' => 'nwsnwn',
    'A' => 'wnnsnw', 'B' => 'nwnsnw', 'C' => 'wwnsnn', 'D' => 'nnwsnw', 'E' => 'wnwsnn',
    'F' => 'nwwsnn', 'G' => 'nnnsww', 'H' => 'wnnswn', 'I' => 'nwnswn', 'J' => 'nnwswn',
    'K' => 'wnnnsw', 'L' => 'nwnnsw', 'M' => 'wwnnsn', 'N' => 'nnwnsw', 'O' => 'wnwnsn',
    'P' => 'nwwnsn', 'Q' => 'nnnwsw', 'R' => 'wnnwsn', 'S' => 'nwnwsn', 'T' => 'nnwwsn',
    'U' => 'wsnnnw', 'V' => 'nswnnw', 'W' => 'wswnnn', 'X' => 'nsnwnw', 'Y' => 'wsnwnn',
    'Z' => 'nswwnn', '-' => 'nsnnww', '.' => 'wsnnwn', ' ' => 'nswnwn',
    '$' => 'nsnsnsnn', '%' => 'nnsnsnsn', '/' => 'nsnsnnsn', '+' => 'nsnnsnsn',
    '*' => 'nsnwwn',
];

/** Keep only Code 39-encodable characters (uppercased). */
function code39_clean(string $code): string
{
    $code = strtoupper($code);
    $out = '';
    foreach (str_split($code) as $ch) {
        if (isset(CODE39_TABLE[$ch]) && $ch !== '*') {
            $out .= $ch;
        }
    }
    return $out !== '' ? $out : '0';
}

/** Total width (mm) the barcode will occupy at the given narrow width. */
function code39_width(string $code, float $narrow = 0.35): float
{
    $wide = $narrow * 2.2;
    $full = '*' . code39_clean($code) . '*';
    $w = 0.0;
    foreach (str_split($full) as $ch) {
        $pattern = CODE39_TABLE[$ch];
        $len = strlen($pattern);
        for ($i = 0; $i < $len; $i++) {
            $el = $pattern[$i];
            if ($el === 'n' || $el === 'w') {
                $w += $el === 'w' ? $wide : $narrow;
                $next = $pattern[$i + 1] ?? '';
                $w += $next === 's' ? $wide : $narrow;
                if ($next === 's') {
                    $i++;
                }
            }
        }
    }
    return $w;
}

/**
 * Draw a Code 39 barcode with FPDF at ($x, $y). Returns the end x.
 * Human-readable code is NOT drawn here — print it under the bars yourself.
 */
function draw_code39(FPDF $pdf, float $x, float $y, string $code, float $height = 8.0, float $narrow = 0.35): float
{
    $wide = $narrow * 2.2;
    $full = '*' . code39_clean($code) . '*';
    $pdf->SetFillColor(0, 0, 0);
    foreach (str_split($full) as $ch) {
        $pattern = CODE39_TABLE[$ch];
        $len = strlen($pattern);
        for ($i = 0; $i < $len; $i++) {
            $el = $pattern[$i];
            if ($el === 'n' || $el === 'w') {
                $bw = $el === 'w' ? $wide : $narrow;
                $pdf->Rect($x, $y, $bw, $height, 'F');
                $x += $bw;
                $next = $pattern[$i + 1] ?? '';
                $x += $next === 's' ? $wide : $narrow;
                if ($next === 's') {
                    $i++;
                }
            }
        }
    }
    return $x;
}
