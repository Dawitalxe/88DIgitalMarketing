<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$pdo = Database::connection();

if ($action === 'analytics') {
    PermissionMiddleware::require('client.analytics.read');
    $companyId = (int)(Auth::user()['company_id'] ?? 0);
    $stmt = $pdo->prepare(
        "SELECT metric_date,platform,engagement,reach,content_count
         FROM social_metrics WHERE company_id=? AND metric_date>=DATE_SUB(CURDATE(),INTERVAL 30 DAY)
         ORDER BY metric_date"
    );
    $stmt->execute([$companyId]);
    ApiController::json(['ok'=>true,'items'=>$stmt->fetchAll()]);
}

if ($action === 'feedback' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    PermissionMiddleware::require('client.feedback.create');
    Auth::verifyCsrf();
    $d=ApiController::input();
    $contentId=ApiController::id($d['content_id']??null);
    $message=trim((string)($d['message']??''));
    if ($message==='') ApiController::json(['ok'=>false,'error'=>'Feedback is required'],422);
    $stmt=$pdo->prepare("INSERT INTO content_feedback(content_id,company_id,user_id,message,created_at) VALUES(?,?,?,?,NOW())");
    $stmt->execute([$contentId,Auth::user()['company_id'],Auth::user()['id'],$message]);
    Audit::log('client_feedback','content',$contentId);
    ApiController::json(['ok'=>true]);
}

ApiController::json(['ok'=>false,'error'=>'Unknown action'],404);
