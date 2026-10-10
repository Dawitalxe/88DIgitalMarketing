<?php
declare(strict_types=1);
    
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Auth::jsonError('Method not allowed', 405);
}

$data = ApiController::input();
$email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
$password = (string)($data['password'] ?? '');

if (!$email || $password === '') {
    Auth::jsonError('Invalid credentials', 422);
}

try {
    $pdo = Database::connection();
    $stmt = $pdo->prepare('SELECT id, password_hash, status FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
} catch (PDOException $exception) {
    error_log('Login database error: ' . $exception->getMessage());
    Auth::jsonError('Unable to connect to the database. Check the database configuration.', 503);
}

if (!$row || $row['status'] !== 'active' || !password_verify($password, $row['password_hash'])) {
    Audit::suspicious('failed_login', ['email' => $email]);
    usleep(250000);
    Auth::jsonError('Invalid credentials', 401);
}

Auth::login((int)$row['id']);
Audit::log('login');
ApiController::json(['ok' => true]);
