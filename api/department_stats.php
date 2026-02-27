<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/business_bootstrap.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        json_response(405, 'method not allowed', null, 405);
    }

    $pdo = get_db_connection();
    ensure_business_workflow_schema($pdo);

    $userId = (int)($_GET['user_id'] ?? 0);
    if ($userId <= 0) {
        json_response(400, 'user_id 非法', null, 400);
    }

    $uStmt = $pdo->prepare('SELECT id, role, department FROM oa_user WHERE id=? LIMIT 1');
    $uStmt->execute([$userId]);
    $user = $uStmt->fetch();
    if (!$user) json_response(404, '用户不存在', null, 404);

    $role = trim((string)$user['role']);
    if (!in_array($role, ['老板', '部门经理', '超管'], true)) {
        json_response(403, '当前角色无权查看部门统计', null, 403);
    }

    $params = [];
    $where = '';
    if ($role === '部门经理') {
        $where = ' WHERE u.department = :dept';
        $params[':dept'] = trim((string)$user['department']);
    }

    $sql = "SELECT u.department,
                   COUNT(DISTINCT u.id) user_count,
                   COUNT(DISTINCT s.id) student_count,
                   COUNT(DISTINCT o.id) order_count,
                   IFNULL(SUM(o.paid_amount),0) paid_amount,
                   IFNULL(SUM(o.sales_commission_amount),0) sales_commission,
                   IFNULL(SUM(CASE WHEN o.seller_role='教练' THEN o.seller_commission_amount ELSE 0 END),0) coach_sales_commission,
                   IFNULL(SUM(o.referrer_commission_amount),0) referrer_commission
            FROM oa_user u
            LEFT JOIN oa_student s ON s.consultant = u.real_name OR s.delivery_coach = u.real_name
            LEFT JOIN oa_order o ON o.student_id = s.id
            " . $where . "
            GROUP BY u.department
            ORDER BY paid_amount DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    json_response(0, 'ok', $stmt->fetchAll());
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
