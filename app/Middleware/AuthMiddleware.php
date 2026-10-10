<?php
declare(strict_types=1);

final class AuthMiddleware {
    public static function requireLogin(): void {
        if (!Auth::check()) Auth::jsonError('Authentication required', 401);
    }
}
