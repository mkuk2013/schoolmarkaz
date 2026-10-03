<?php
declare(strict_types=1);

/**
 * Session authentication + Role-Based Access Control.
 * Roles: admin, teacher, student, parent.
 */

const ROLES = ['admin', 'teacher', 'student', 'parent'];

function attempt_login(string $username, string $password): array
{
    $username = trim($username);
    $stmt = db()->prepare('SELECT * FROM users WHERE username = ? AND active = 1 LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || empty($user['password_hash']) || !password_verify($password, (string) $user['password_hash'])) {
        return [false, 'Invalid username or password.'];
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    return [true, ''];
}

function logout(): void
{
    unset($_SESSION['user_id'], $_SESSION['csrf_token'], $_SESSION['flash']);
    session_regenerate_id(true);
}

/** @return array<string,mixed>|null */
function current_user(): ?array
{
    static $cached = null;
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    if ($cached === null) {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ? AND active = 1 LIMIT 1');
        $stmt->execute([(int) $_SESSION['user_id']]);
        $cached = $stmt->fetch() ?: null;
        if ($cached === null) {
            unset($_SESSION['user_id']);
        }
    }
    return $cached ?: null;
}

function user_role(): ?string
{
    $u = current_user();
    return $u['role'] ?? null;
}

function is_role(string ...$roles): bool
{
    return in_array(user_role(), $roles, true);
}

function require_login(): void
{
    if (current_user() === null) {
        redirect('auth/login.php');
    }
}

/**
 * Usage: require_role('admin'); or require_role('admin','teacher');
 */
function require_role(string ...$roles): void
{
    require_login();
    if (!is_role(...$roles)) {
        http_response_code(403);
        die('Access denied: insufficient permissions.');
    }
}

/**
 * Linked profile row for the current user.
 * teacher → teachers row, student → students row, parent → parents row.
 * @return array<string,mixed>|null
 */
function my_profile(): ?array
{
    $u = current_user();
    if (!$u) {
        return null;
    }
    $map = ['teacher' => 'teachers', 'student' => 'students', 'parent' => 'parents'];
    $table = $map[$u['role']] ?? null;
    if (!$table) {
        return null;
    }
    $st = db()->prepare("SELECT * FROM `$table` WHERE user_id = ? LIMIT 1");
    $st->execute([(int) $u['id']]);
    return $st->fetch() ?: null;
}
