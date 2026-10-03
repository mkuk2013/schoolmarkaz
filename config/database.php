<?php
declare(strict_types=1);

/**
 * Database configuration. Override with DB_* environment variables.
 * Defaults match a typical cPanel MySQL setup.
 */
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'schoolmarkaz_db');
define('DB_USER', getenv('DB_USER') ?: 'schoolmarkaz_user');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'SchoolMarkaz@123');
define('DB_CHARSET', 'utf8mb4');

/**
 * Shared PDO connection (singleton).
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo '<!doctype html><html><head><meta charset="utf-8"><title>Database Error</title></head>'
                . '<body style="font-family:sans-serif;max-width:640px;margin:60px auto;padding:20px">'
                . '<h1>Database connection failed</h1>'
                . '<p>The application could not connect to MySQL. Create the database and user, import '
                . '<code>database/schema.sql</code>, and verify credentials in <code>config/database.php</code> '
                . '(or the DB_* environment variables).</p>'
                . '</body></html>';
            exit;
        }
    }
    return $pdo;
}
