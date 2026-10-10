<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script must be run from the command line.\n");
}

require_once __DIR__ . '/../config/bootstrap.php';

$accounts = [
 ['Super Admin','admin@88digimark.local','DigiMark@88Admin#2026','super_admin'],
 ['Operations Admin','operations@88digimark.local','DigiMark@88Ops#2026','operations_admin'],
 ['HR','hr@88digimark.local','DigiMark@88HR#2026','hr'],
 ['Team Leader','leader@88digimark.local','DigiMark@88Lead#2026','team_leader'],
 ['Employee','employee@88digimark.local','DigiMark@88Emp#2026','employee'],
 ['Reception','reception@88digimark.local','DigiMark@88Reception#2026','reception'],
 ['Client','client@88digimark.local','DigiMark@88Client#2026','client'],
];

$pdo = Database::connection();
$pdo->exec(
    "INSERT INTO companies (name, code, status, created_at, updated_at)
     VALUES ('88 Digi Mark', '88-DIGI-MARK', 'active', NOW(), NOW())
     ON DUPLICATE KEY UPDATE name=VALUES(name), status='active', updated_at=NOW()"
);
$company = $pdo->query("SELECT id FROM companies WHERE code='88-DIGI-MARK' LIMIT 1")->fetchColumn();

$role = $pdo->prepare('SELECT id FROM roles WHERE name=? LIMIT 1');
$user = $pdo->prepare('INSERT INTO users (company_id,full_name,email,password_hash,status,created_at,updated_at) VALUES (?,?,?,?,"active",NOW(),NOW()) ON DUPLICATE KEY UPDATE full_name=VALUES(full_name),password_hash=VALUES(password_hash),status="active",updated_at=NOW()');
$getUser = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
$link = $pdo->prepare('INSERT IGNORE INTO user_roles (user_id,role_id) VALUES (?,?)');
foreach ($accounts as [$name,$email,$password,$slug]) {
  $role->execute([$slug]); $roleId=$role->fetchColumn(); if (!$roleId) throw new RuntimeException("Missing role: $slug");
  $user->execute([$company ?: null,$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
  $getUser->execute([$email]); $userId=$getUser->fetchColumn(); $link->execute([$userId,$roleId]);
  echo "Seeded $email ($slug)\n";
}
echo "Demo accounts ready. Change/remove these passwords before production.\n";
