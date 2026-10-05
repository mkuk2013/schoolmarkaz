<?php
declare(strict_types=1);

/**
 * Application bootstrap: config, secure session, helpers, self-healing schema.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

date_default_timezone_set(APP_TIMEZONE);

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $isHttps,
    ]);
    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';

/**
 * All tables the application needs. Used by the self-healing installer.
 */
const REQUIRED_TABLES = [
    'users', 'schools', 'settings', 'students', 'parents', 'teachers',
    'classes', 'subjects', 'student_attendance', 'staff_attendance',
    'fee_heads', 'fee_invoices', 'fee_invoice_items', 'fee_payments',
    'exams', 'marks', 'timetable', 'finance_transactions',
    'salary_payments', 'announcements', 'packages', 'package_orders', 'admissions_inquiries',
    'campuses', 'student_documents', 'gate_logs', 'notification_log',
    'quizzes', 'quiz_questions', 'quiz_attempts',
];

/**
 * Self-healing database: if any required table is missing, the full schema
 * (database/schema.sql) is executed automatically. Cheap single query when healthy.
 */
function ensure_tables_exist(): void
{
    $pdo = db();
    $placeholders = implode(',', array_fill(0, count(REQUIRED_TABLES), '?'));
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name IN (' . $placeholders . ')');
    $st->execute(REQUIRED_TABLES);
    if ((int) $st->fetchColumn() === count(REQUIRED_TABLES)) {
        return;
    }
    $file = __DIR__ . '/../database/schema.sql';
    if (!is_readable($file)) {
        throw new RuntimeException('Database schema file not found: database/schema.sql');
    }
    $sql = file_get_contents($file);
    $statements = preg_split('/;\s*\n/', $sql);
    foreach ($statements as $stmt) {
        $lines = explode("\n", $stmt);
        $code = [];
        foreach ($lines as $line) {
            if (!preg_match('/^\s*--/', $line)) {
                $code[] = $line;
            }
        }
        $stmt = trim(implode("\n", $code));
        if ($stmt === '') {
            continue;
        }
        try {
            $pdo->exec($stmt);
        } catch (PDOException $e) {
            $ignorable = in_array($e->errorInfo[1] ?? 0, [1050, 1060], true)
                || in_array($e->getCode(), ['42S01', '42S21'], true);
            if (!$ignorable) {
                throw $e;
            }
        }
    }
}

ensure_tables_exist();
