<?php
declare(strict_types=1);

final class PermissionMiddleware {
    public static function require(string $permission): void {
        Auth::requirePermission($permission);
    }
}
