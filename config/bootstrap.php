<?php
declare(strict_types=1);

function env(string $key, ?string $default = null): ?string {
    static $loaded = false;
    static $vars = [];
    if (!$loaded) {
        $file = dirname(__DIR__) . '/.env';
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
                [$k, $v] = explode('=', $line, 2);
                $vars[trim($k)] = trim($v, " \t\n\r\0\x0B\"");
            }
        }
        $loaded = true;
    }
    return $vars[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_name(env('SESSION_NAME', 'digi_mark_sid'));
    session_set_cookie_params([
        'httponly' => true,
        'secure' => filter_var(env('SESSION_SECURE', $https ? 'true' : 'false'), FILTER_VALIDATE_BOOL),
        'samesite' => env('SESSION_SAMESITE', 'Lax'),
        'path' => '/',
    ]);
    session_start();
}

require_once __DIR__ . '/database.php';
require_once dirname(__DIR__) . '/app/Services/Auth.php';
require_once dirname(__DIR__) . '/app/Services/Audit.php';
require_once dirname(__DIR__) . '/app/Middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/app/Middleware/PermissionMiddleware.php';

header_remove('X-Powered-By');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: DENY');
$cspNonce = base64_encode(random_bytes(16));
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net 'nonce-{$cspNonce}'; style-src 'self'; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
