<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$pdo = Database::connection();

if ($action === 'csrf') {
    ApiController::json(['ok'=>true,'csrf'=>Auth::csrf()]);
}

if ($action === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    Audit::log('logout');
    Auth::logout();
    ApiController::json(['ok'=>true]);
}

ApiController::json(['ok'=>false,'error'=>'Unknown action'], 404);
