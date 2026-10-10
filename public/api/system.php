<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$pdo = Database::connection();

if ($action === 'maintenance' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    PermissionMiddleware::require('system.maintenance.read');
    $row = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key='maintenance_mode'")->fetch();
    ApiController::json(['ok'=>true,'maintenance'=>(bool)($row['setting_value'] ?? 0)]);
}

if ($action === 'maintenance' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    PermissionMiddleware::require('system.maintenance.toggle');
    Auth::verifyCsrf();
    $data = ApiController::input();
    $enabled = !empty($data['enabled']) ? '1' : '0';
    $stmt = $pdo->prepare("INSERT INTO system_settings(setting_key,setting_value,updated_by)
                           VALUES('maintenance_mode',?,?)
                           ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_by=VALUES(updated_by)");
    $stmt->execute([$enabled, Auth::user()['id']]);
    Audit::log('maintenance_toggle', 'system', null, ['enabled'=>(bool)$enabled]);
    ApiController::json(['ok'=>true,'maintenance'=>(bool)$enabled]);
}

if ($action === 'audit' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    PermissionMiddleware::require('audit.read');
    $stmt = $pdo->query(
        "SELECT a.id,a.action,a.entity_type,a.entity_id,a.ip_address,a.created_at,
                u.full_name,se.event_type,se.reason
         FROM audit_logs a
         LEFT JOIN users u ON u.id=a.user_id
         LEFT JOIN security_events se ON se.user_id=a.user_id
         ORDER BY a.id DESC LIMIT 100"
    );
    ApiController::json(['ok'=>true,'items'=>$stmt->fetchAll()]);
}

ApiController::json(['ok'=>false,'error'=>'Unknown action'], 404);
