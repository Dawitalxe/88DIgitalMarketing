<?php
declare(strict_types=1);

final class Audit {
    public static function log(string $action, string $entityType = '', ?int $entityId = null, array $meta = []): void {
        $pdo = Database::connection();
        $user = Auth::user();
        $stmt = $pdo->prepare(
            "INSERT INTO audit_logs
             (user_id,company_id,action,entity_type,entity_id,ip_address,user_agent,metadata_json,created_at)
             VALUES (?,?,?,?,?,?,?,?,NOW())"
        );
        $stmt->execute([
            $user['id'] ?? null,
            $user['company_id'] ?? null,
            $action,
            $entityType ?: null,
            $entityId,
            $_SERVER['REMOTE_ADDR'] ?? null,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            json_encode($meta, JSON_UNESCAPED_SLASHES)
        ]);
    }

    public static function suspicious(string $reason, array $meta = []): void {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            "INSERT INTO security_events (user_id,ip_address,event_type,reason,metadata_json,created_at)
             VALUES (?,?,?,?,?,NOW())"
        );
        $u = Auth::user();
        $stmt->execute([
            $u['id'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            'suspicious_activity',
            $reason,
            json_encode($meta, JSON_UNESCAPED_SLASHES)
        ]);
    }
}
