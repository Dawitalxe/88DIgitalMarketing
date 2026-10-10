<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Auth::jsonError('Method not allowed', 405);
}

Auth::verifyCsrf();

$data = ApiController::input();
$fullName = trim((string)($data['full_name'] ?? ''));
$email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
$password = (string)($data['password'] ?? '');
$passwordConfirmation = (string)($data['password_confirmation'] ?? '');

if ($fullName === '' || mb_strlen($fullName) > 160 || !$email) {
    Auth::jsonError('Enter a valid name and email address.', 422);
}

if (strlen($password) < 8 || $password !== $passwordConfirmation) {
    Auth::jsonError('Passwords must match and contain at least 8 characters.', 422);
}

/** @var PDO|null $pdo */
$pdo = null;

try {
    $pdo = Database::connection();
    $pdo->beginTransaction();

    $roleStatement = $pdo->prepare("SELECT id FROM roles WHERE name = 'client' LIMIT 1");
    $roleStatement->execute();
    $roleId = $roleStatement->fetchColumn();

    if (!$roleId) {
        $pdo->rollBack();
        Auth::jsonError('Registration is not configured. Contact an administrator.', 503);
    }

    $userStatement = $pdo->prepare(
        'INSERT INTO users (full_name, email, password_hash, status, created_at, updated_at)
         VALUES (?, ?, ?, \'active\', NOW(), NOW())'
    );
    $userStatement->execute([
        $fullName,
        $email,
        password_hash($password, PASSWORD_DEFAULT),
    ]);

    $userId = (int)$pdo->lastInsertId();
    $linkStatement = $pdo->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)');
    $linkStatement->execute([$userId, $roleId]);
    $pdo->commit();
} catch (PDOException $exception) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ((int)$exception->errorInfo[1] === 1062) {
        Auth::jsonError('An account with this email already exists.', 409);
    }

    error_log('Registration database error: ' . $exception->getMessage());
    Auth::jsonError('Unable to create the account. Check the database configuration.', 503);
}

ApiController::json(['ok' => true, 'message' => 'Registration successful.']);
