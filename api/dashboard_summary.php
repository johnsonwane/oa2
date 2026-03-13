<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/business_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(405, '仅支持 GET', null, 405);
}

try {
    $pdo = get_db_connection();
    ensure_business_workflow_schema($pdo);

    $role = auth_user_role();
    $uid = auth_user_id();
    $uname = auth_user_name();

    $studentWhere = '1=1';
    $studentParams = [];
    $orderWhere = '1=1';
    $orderParams = [];

    if (!auth_is_admin_like() && $role !== '财务') {
        if ($role === '顾问') {
            $studentWhere = '(owner_consultant_user_id = :uid OR consultant = :uname)';
            $studentParams = [':uid' => $uid, ':uname' => $uname];
            $orderWhere = 'seller_user_id = :uid';
            $orderParams = [':uid' => $uid];
        } elseif ($role === '教练') {
            $studentWhere = '(coach_user_id = :uid OR delivery_coach = :uname)';
            $studentParams = [':uid' => $uid, ':uname' => $uname];
            $orderWhere = 'student_id IN (SELECT id FROM oa_student WHERE coach_user_id = :uid OR delivery_coach = :uname)';
            $orderParams = [':uid' => $uid, ':uname' => $uname];
        } elseif ($role === '班主任') {
            $studentWhere = 'headteacher_user_id = :uid';
            $studentParams = [':uid' => $uid];
            $orderWhere = 'student_id IN (SELECT id FROM oa_student WHERE headteacher_user_id = :uid)';
            $orderParams = [':uid' => $uid];
        }
    }

    $studentStmt = $pdo->prepare('SELECT COUNT(*) FROM oa_student WHERE ' . $studentWhere);
    $studentStmt->execute($studentParams);
    $students = (int)$studentStmt->fetchColumn();

    $leadStmt = $pdo->prepare('SELECT COUNT(*) FROM oa_student WHERE is_student=0 AND ' . $studentWhere);
    $leadStmt->execute($studentParams);

    $orderStmt = $pdo->prepare('SELECT COUNT(*) FROM oa_order WHERE ' . $orderWhere);
    $orderStmt->execute($orderParams);

    $receiptStmt = $pdo->prepare('SELECT COUNT(*) FROM oa_receipt WHERE order_id IN (SELECT id FROM oa_order WHERE ' . $orderWhere . ')');
    $receiptStmt->execute($orderParams);

    $summary = [
        'students' => $students,
        'leads' => (int)$leadStmt->fetchColumn(),
        'courses' => (int)$pdo->query('SELECT COUNT(*) FROM oa_course')->fetchColumn(),
        'orders' => (int)$orderStmt->fetchColumn(),
        'receipts' => (int)$receiptStmt->fetchColumn(),
        'todos_open' => (int)$pdo->query("SELECT COUNT(*) FROM oa_todo WHERE status <> 'done'")->fetchColumn(),
        'notifications_unread' => (int)$pdo->query('SELECT COUNT(*) FROM oa_notification WHERE is_read = 0')->fetchColumn(),
    ];
    json_response(0, 'ok', $summary);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
