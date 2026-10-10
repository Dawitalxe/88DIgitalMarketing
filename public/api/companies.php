<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$pdo = Database::connection();

if ($action === 'list') {
    PermissionMiddleware::require('companies.read');
    $rows = $pdo->query("SELECT id,name,code,status,created_at FROM companies ORDER BY name")->fetchAll();
    ApiController::json(['ok'=>true,'items'=>$rows]);
}

if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    PermissionMiddleware::require('companies.write');
    Auth::verifyCsrf();
    $d = ApiController::input();
    $name = trim((string)($d['name'] ?? ''));
    $code = strtoupper(trim((string)($d['code'] ?? '')));
    $status = ($d['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    if ($name === '' || !preg_match('/^[A-Z0-9_-]{2,30}$/', $code)) ApiController::json(['ok'=>false,'error'=>'Invalid company data'],422);

    $id = !empty($d['id']) ? ApiController::id($d['id']) : null;
    if ($id) {
        $stmt = $pdo->prepare("UPDATE companies SET name=?,code=?,status=?,updated_at=NOW() WHERE id=?");
        $stmt->execute([$name,$code,$status,$id]);
        Audit::log('company_update','company',$id);
    } else {
        $stmt = $pdo->prepare("INSERT INTO companies(name,code,status,created_at,updated_at) VALUES(?,?,?,NOW(),NOW())");
        $stmt->execute([$name,$code,$status]);
        $id = (int)$pdo->lastInsertId();
        Audit::log('company_create','company',$id);
    }
    ApiController::json(['ok'=>true,'id'=>$id]);
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    PermissionMiddleware::require('companies.delete');
    Auth::verifyCsrf();
    $id = ApiController::id(ApiController::input()['id'] ?? null);
    $stmt = $pdo->prepare("UPDATE companies SET status='inactive',updated_at=NOW() WHERE id=?");
    $stmt->execute([$id]);
    Audit::log('company_archive','company',$id);
    ApiController::json(['ok'=>true]);
}

ApiController::json(['ok'=>false,'error'=>'Unknown action'],404);
