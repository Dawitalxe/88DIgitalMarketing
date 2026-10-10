<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$pdo = Database::connection();

if ($action === 'list') {
    PermissionMiddleware::require('tasks.read');
    $user = Auth::user();
    $stmt = $pdo->prepare(
        "SELECT t.id,t.title,t.description,t.status,t.due_date,t.assigned_to,
                u.full_name assignee,c.name company
         FROM tasks t
         LEFT JOIN users u ON u.id=t.assigned_to
         LEFT JOIN companies c ON c.id=t.company_id
         WHERE t.assigned_to=? OR ? IN (SELECT user_id FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE r.name IN ('super_admin','operations_admin'))
         ORDER BY t.due_date IS NULL,t.due_date,t.id DESC"
    );
    $stmt->execute([$user['id'], $user['id']]);
    ApiController::json(['ok'=>true,'items'=>$stmt->fetchAll()]);
}

if ($action === 'status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    PermissionMiddleware::require('tasks.update');
    Auth::verifyCsrf();
    $data = ApiController::input();
    $id = ApiController::id($data['id'] ?? null);
    $status = $data['status'] ?? '';
    $allowed = ['Pending','In Progress','Done'];
    if (!in_array($status, $allowed, true)) ApiController::json(['ok'=>false,'error'=>'Invalid status'],422);

    $stmt = $pdo->prepare("UPDATE tasks SET status=?,updated_at=NOW() WHERE id=?");
    $stmt->execute([$status,$id]);
    Audit::log('task_status_update','task',$id,['status'=>$status]);
    ApiController::json(['ok'=>true]);
}

if ($action === 'assign' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    PermissionMiddleware::require('tasks.assign');
    Auth::verifyCsrf();
    $data = ApiController::input();
    $taskId = ApiController::id($data['task_id'] ?? null);
    $assignee = ApiController::id($data['assigned_to'] ?? null);
    $stmt = $pdo->prepare("UPDATE tasks SET assigned_to=?,updated_at=NOW() WHERE id=?");
    $stmt->execute([$assignee,$taskId]);
    Audit::log('task_assign','task',$taskId,['assigned_to'=>$assignee]);
    ApiController::json(['ok'=>true]);
}

ApiController::json(['ok'=>false,'error'=>'Unknown action'],404);
