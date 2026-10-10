<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$pdo = Database::connection();

if ($action === 'gate-pass' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    PermissionMiddleware::require('gate_pass.create');
    Auth::verifyCsrf();
    $d = ApiController::input();
    $purpose = trim((string)($d['purpose'] ?? ''));
    if ($purpose === '') ApiController::json(['ok'=>false,'error'=>'Purpose is required'],422);
    $stmt = $pdo->prepare("INSERT INTO requests(request_type,title,description,requested_by,status,requested_at) VALUES('Gate Pass',?,?,?,'Pending',NOW())");
    $stmt->execute(['Gate pass request',$purpose,Auth::user()['id']]);
    $id = (int)$pdo->lastInsertId();
    Audit::log('gate_pass_create','request',$id);
    ApiController::json(['ok'=>true,'id'=>$id]);
}

if ($action === 'inventory' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    PermissionMiddleware::require('inventory.read');
    $rows = $pdo->query("SELECT id,name,category,quantity,unit,status,updated_at FROM inventory_items ORDER BY name")->fetchAll();
    ApiController::json(['ok'=>true,'items'=>$rows]);
}

if ($action === 'inventory-save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    PermissionMiddleware::require('inventory.write');
    Auth::verifyCsrf();
    $d = ApiController::input();
    $name = trim((string)($d['name'] ?? ''));
    $qty = filter_var($d['quantity'] ?? null,FILTER_VALIDATE_INT);
    if ($name === '' || $qty === false || $qty < 0) ApiController::json(['ok'=>false,'error'=>'Invalid inventory data'],422);
    if (!empty($d['id'])) {
        $id=ApiController::id($d['id']);
        $stmt=$pdo->prepare("UPDATE inventory_items SET name=?,category=?,quantity=?,unit=?,status=?,updated_at=NOW() WHERE id=?");
        $stmt->execute([$name,$d['category']??null,$qty,$d['unit']??'pcs',$d['status']??'active',$id]);
    } else {
        $stmt=$pdo->prepare("INSERT INTO inventory_items(name,category,quantity,unit,status,created_at,updated_at) VALUES(?,?,?,?,?,NOW(),NOW())");
        $stmt->execute([$name,$d['category']??null,$qty,$d['unit']??'pcs',$d['status']??'active']);
        $id=(int)$pdo->lastInsertId();
    }
    Audit::log('inventory_save','inventory_item',$id);
    ApiController::json(['ok'=>true,'id'=>$id]);
}

ApiController::json(['ok'=>false,'error'=>'Unknown action'],404);
