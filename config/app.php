<?php
declare(strict_types=1);

/**
 * School Markaz — application configuration.
 */

const APP_NAME    = 'School Markaz';
const APP_TAGLINE = 'Complete School Management System';
const APP_TIMEZONE = 'Asia/Karachi';
const SESSION_NAME = 'schoolmarkaz_sid';
const CURRENCY     = 'PKR';

/**
 * Base URL of the app, e.g. https://example.com/school-markaz
 * Auto-detected; override with APP_URL env var on cPanel if needed.
 */
function app_url(string $path = ''): string
{
    static $base = null;
    if ($base === null) {
        $env = getenv('APP_URL');
        if ($env) {
            $base = rtrim($env, '/');
        } else {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
            // Pages live in sub-dirs (admin/, student/ ...); base is the app root.
            $parts = explode('/', trim($dir, '/'));
            if (in_array(end($parts), ['admin', 'teacher', 'student', 'parent', 'auth', 'api'], true)) {
                array_pop($parts);
            }
            $base = $scheme . '://' . $host . ($parts ? '/' . implode('/', $parts) : '');
        }
    }
    return $base . ($path ? '/' . ltrim($path, '/') : '');
}
