<?php
declare(strict_types=1);

final class Auth {
    public static function user(): ?array {
        return $_SESSION['user'] ?? null;
    }

    public static function login(int $userId): void {
        session_regenerate_id(true);
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            "SELECT u.id,u.company_id,u.full_name,u.email,u.status,u.password_hash,
                    GROUP_CONCAT(DISTINCT r.name) roles,
                    GROUP_CONCAT(DISTINCT p.key_name) permissions
             FROM users u
             LEFT JOIN user_roles ur ON ur.user_id=u.id
             LEFT JOIN roles r ON r.id=ur.role_id
             LEFT JOIN role_permissions rp ON rp.role_id=r.id
             LEFT JOIN permissions p ON p.id=rp.permission_id
             WHERE u.id=? GROUP BY u.id"
        );
        $stmt->execute([$userId]);
        $u = $stmt->fetch();
        if (!$u) throw new RuntimeException('User not found');

        unset($u['password_hash']);
        $_SESSION['user'] = $u;
        $_SESSION['permissions'] = $u['permissions'] ? array_filter(explode(',', $u['permissions'])) : [];
        $_SESSION['roles'] = $u['roles'] ? array_filter(explode(',', $u['roles'])) : [];
    }

    public static function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time()-42000, $p['path'], '', $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool { return isset($_SESSION['user']); }

    public static function hasPermission(string $permission): bool {
        return in_array('*', $_SESSION['permissions'] ?? [], true)
            || in_array($permission, $_SESSION['permissions'] ?? [], true);
    }

    public static function requirePermission(string $permission): void {
        if (!self::check()) self::jsonError('Authentication required', 401);
        if (!self::hasPermission($permission)) self::jsonError('Forbidden', 403);
    }

    public static function jsonError(string $message, int $status): never {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['ok'=>false,'error'=>$message], JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function csrf(): string {
        if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf'];
    }

    public static function verifyCsrf(): void {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? '');
        if (!hash_equals($_SESSION['csrf'] ?? '', $token)) self::jsonError('Invalid CSRF token', 419);
    }
}
