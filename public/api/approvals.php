<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$pdo = Database::connection();

if ($action === 'queue') {
    PermissionMiddleware::require('approvals.read');
    $stmt = $pdo->query(
        "SELECT r.id,r.request_type,r.title,r.status,r.requested_at,u.full_name requester
         FROM requests r JOIN users u ON u.id=r.requested_by
         WHERE r.status='Pending' ORDER BY r.requested_at ASC"
    );
    ApiController::json(['ok'=>true,'items'=>$stmt->fetchAll()]);
}

if ($action === 'decision' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    PermissionMiddleware::require('approvals.decide');
    Auth::verifyCsrf();
    $d = ApiController::input();
    $id = ApiController::id($d['id'] ?? null);
    $decision = $d['decision'] ?? '';
    if (!in_array($decision,['Approved','Rejected'],true)) ApiController::json(['ok'=>false,'error'=>'Invalid decision'],422);
    $stmt = $pdo->prepare("UPDATE requests SET status=?,reviewed_by=?,reviewed_at=NOW() WHERE id=? AND status='Pending'");
    $stmt->execute([$decision,Auth::user()['id'],$id]);
    if ($stmt->rowCount() !== 1) ApiController::json(['ok'=>false,'error'=>'Request already processed'],409);
    Audit::log('approval_decision','request',$id,['decision'=>$decision]);
    ApiController::json(['ok'=>true]);
}

ApiController::json(['ok'=>false,'error'=>'Unknown action'],404);
