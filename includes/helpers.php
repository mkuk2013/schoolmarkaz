<?php
declare(strict_types=1);

/**
 * Generic helpers: escaping, redirects, CSRF, flash messages, formatting.
 */

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . app_url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', (string) $token)) {
        http_response_code(419);
        die('Invalid CSRF token. Please go back and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** @return array<int,array{type:string,message:string}> */
function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function today(): string
{
    return date('Y-m-d');
}

function fmt_date(?string $date, string $format = 'd M Y'): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : e($date);
}

function fmt_money($amount): string
{
    return CURRENCY . ' ' . number_format((float) $amount, 0);
}

function month_name(int $m): string
{
    return date('F', mktime(0, 0, 0, $m, 1));
}

/**
 * Fetch the single-row school profile.
 * @return array<string,mixed>
 */
function school_profile(): array
{
    static $profile = null;
    if ($profile === null) {
        $profile = db()->query('SELECT * FROM schools ORDER BY id ASC LIMIT 1')->fetch() ?: [];
    }
    return $profile;
}

/**
 * Get a setting value (settings table, key/value).
 */
function setting(string $key, string $default = ''): string
{
    static $cache = [];
    if (!array_key_exists($key, $cache)) {
        $st = db()->prepare('SELECT `value` FROM settings WHERE `key` = ? LIMIT 1');
        $st->execute([$key]);
        $cache[$key] = $st->fetchColumn() ?: $default;
    }
    return $cache[$key];
}

/**
 * Save a setting value (settings table, key/value — upsert).
 */
function save_setting(string $key, string $value): void
{
    db()->prepare('INSERT INTO settings (`key`, `value`) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)')->execute([$key, $value]);
}

/**
 * Generate the next package order number like PO-2026-0001.
 */
function next_order_no(): string
{
    $prefix = 'PO-' . date('Y') . '-';
    $st = db()->prepare('SELECT order_no FROM package_orders WHERE order_no LIKE ? ORDER BY id DESC LIMIT 1');
    $st->execute([$prefix . '%']);
    $last = $st->fetchColumn();
    $n = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;
    return $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
}

/**
 * Package price for a billing cycle. Yearly = 10x monthly (2 months free).
 * @return array{amount: float, label: string, months: int}
 */
function package_price(float $monthly, string $cycle): array
{
    if ($cycle === 'yearly') {
        return ['amount' => round($monthly * 10, 2), 'label' => 'Yearly (2 months free)', 'months' => 12];
    }
    return ['amount' => round($monthly, 2), 'label' => 'Monthly', 'months' => 1];
}

/**
 * Generate the next receipt number like R-2026-000123.
 */
function next_receipt_no(): string
{
    $prefix = 'R-' . date('Y') . '-';
    $st = db()->prepare('SELECT receipt_no FROM fee_payments WHERE receipt_no LIKE ? ORDER BY id DESC LIMIT 1');
    $st->execute([$prefix . '%']);
    $last = $st->fetchColumn();
    $n = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;
    return $prefix . str_pad((string) $n, 6, '0', STR_PAD_LEFT);
}

/**
 * Generate next admission number like SM-2026-0001.
 */
function next_admission_no(): string
{
    $prefix = 'SM-' . date('Y') . '-';
    $st = db()->prepare('SELECT admission_no FROM students WHERE admission_no LIKE ? ORDER BY id DESC LIMIT 1');
    $st->execute([$prefix . '%']);
    $last = $st->fetchColumn();
    $n = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;
    return $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
}

/**
 * Weekday names for timetable.
 * @return string[]
 */
function week_days(): array
{
    return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
}
