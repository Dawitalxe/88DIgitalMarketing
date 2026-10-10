<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$pdo = Database::connection();

if ($action === 'salary') {
    PermissionMiddleware::require('payroll.read');
    $month = preg_match('/^\d{4}-\d{2}$/', $_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');
    $stmt = $pdo->prepare(
        "SELECT u.id,u.full_name,
                COALESCE(s.base_salary,0) base_salary,
                COALESCE(s.bonus,0) bonus,
                COALESCE(s.deductions,0) deductions,
                (COALESCE(s.base_salary,0)+COALESCE(s.bonus,0)-COALESCE(s.deductions,0)) net_salary
         FROM users u
         LEFT JOIN salaries s ON s.user_id=u.id AND s.pay_month=?
         WHERE u.status='active' ORDER BY u.full_name"
    );
    $stmt->execute([$month]);
    ApiController::json(['ok'=>true,'month'=>$month,'items'=>$stmt->fetchAll()]);
}

if ($action === 'attendance') {
    PermissionMiddleware::require('attendance.read');
    $from = $_GET['from'] ?? date('Y-m-01');
    $to = $_GET['to'] ?? date('Y-m-d');
    $stmt = $pdo->prepare(
        "SELECT a.id,a.user_id,u.full_name,a.work_date,a.check_in,a.check_out,
                TIMESTAMPDIFF(MINUTE,a.check_in,a.check_out)/60 AS work_hours
         FROM attendance a JOIN users u ON u.id=a.user_id
         WHERE a.work_date BETWEEN ? AND ? ORDER BY a.work_date DESC,u.full_name"
    );
    $stmt->execute([$from,$to]);
    ApiController::json(['ok'=>true,'items'=>$stmt->fetchAll()]);
}

ApiController::json(['ok'=>false,'error'=>'Unknown action'],404);
