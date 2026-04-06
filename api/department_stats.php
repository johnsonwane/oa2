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

    $uid = auth_user_id();
    $uStmt = $pdo->prepare('SELECT id, role, department FROM oa_user WHERE id=? LIMIT 1');
    $uStmt->execute([$uid]);
    $user = $uStmt->fetch();
    if (!$user) json_response(404, '用户不存在', null, 404);

    $role = trim((string)$user['role']);
    if (!auth_is_admin_like() && !in_array($role, ['部门经理', '财务'], true)) {
        json_response(403, '当前角色无权查看部门统计', null, 403);
    }

    $params = [];
    $where = '';
    if ($role === '部门经理' && !auth_is_admin_like()) {
        $where = ' WHERE u.department = :dept';
        $params[':dept'] = trim((string)$user['department']);
    }

    // 月份筛选
    $statMonth = trim((string)($_GET['month'] ?? ''));
    $monthConditionOrder = '1=1';
    $monthParams = [];
    if ($statMonth !== '' && preg_match('/^\d{4}-\d{2}$/', $statMonth)) {
        $monthConditionOrder = "DATE_FORMAT(o.created_at,'%Y-%m') = :stat_month";
        $monthParams[':stat_month'] = $statMonth;
    }

    // 主统计：部门维度
    $sql = "SELECT
                u.department,
                COUNT(DISTINCT u.id) AS user_count,
                -- 线索相关指标
                COUNT(DISTINCT s.id) AS student_count,
                SUM(CASE WHEN s.is_student = 0 THEN 1 ELSE 0 END) AS leads_count,
                SUM(CASE WHEN s.is_student = 1 THEN 1 ELSE 0 END) AS converted_count,
                ROUND(
                    IFNULL(
                        SUM(CASE WHEN s.is_student = 1 THEN 1.0 ELSE 0.0 END) / NULLIF(COUNT(DISTINCT s.id), 0) * 100,
                        0
                    ), 1
                ) AS conversion_rate,
                -- 订单相关指标
                COUNT(DISTINCT o.id) AS order_count,
                COUNT(DISTINCT CASE WHEN o.order_type='first' THEN o.id END) AS first_order_count,
                COUNT(DISTINCT CASE WHEN o.order_type='renewal' THEN o.id END) AS renewal_order_count,
                COUNT(DISTINCT CASE WHEN o.order_type='upgrade' THEN o.id END) AS upgrade_order_count,
                -- 收款指标
                IFNULL(SUM(o.paid_amount), 0) AS paid_amount,
                IFNULL(AVG(NULLIF(o.paid_amount, 0)), 0) AS avg_order_amount,
                IFNULL(SUM(o.total_amount), 0) AS total_amount,
                IFNULL(SUM(o.refund_amount), 0) AS refund_amount,
                ROUND(
                    IFNULL(
                        SUM(o.refund_amount) / NULLIF(SUM(o.paid_amount), 0) * 100,
                        0
                    ), 1
                ) AS refund_rate,
                -- 分成指标
                IFNULL(SUM(o.sales_commission_amount), 0) AS sales_commission,
                IFNULL(SUM(CASE WHEN o.seller_role='教练' THEN o.seller_commission_amount ELSE 0 END), 0) AS coach_sales_commission,
                IFNULL(SUM(o.referrer_commission_amount), 0) AS referrer_commission
            FROM oa_user u
            LEFT JOIN oa_student s ON (s.owner_consultant_user_id = u.id OR s.headteacher_user_id = u.id OR s.coach_user_id = u.id)
            LEFT JOIN oa_order o ON o.student_id = s.id AND ({$monthConditionOrder})
            " . $where . "
            GROUP BY u.department
            ORDER BY paid_amount DESC";

    // 合并参数
    $allParams = array_merge($params, $monthParams);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($allParams);
    $rows = $stmt->fetchAll();

    // 添加汇总行
    $totalSummary = [
        'department' => '【全公司汇总】',
        'user_count' => array_sum(array_column($rows, 'user_count')),
        'student_count' => array_sum(array_column($rows, 'student_count')),
        'leads_count' => array_sum(array_column($rows, 'leads_count')),
        'converted_count' => array_sum(array_column($rows, 'converted_count')),
        'conversion_rate' => round(
            array_sum(array_column($rows, 'converted_count'))
            / max(1, array_sum(array_column($rows, 'student_count'))) * 100,
            1
        ),
        'order_count' => array_sum(array_column($rows, 'order_count')),
        'first_order_count' => array_sum(array_column($rows, 'first_order_count')),
        'renewal_order_count' => array_sum(array_column($rows, 'renewal_order_count')),
        'upgrade_order_count' => array_sum(array_column($rows, 'upgrade_order_count')),
        'paid_amount' => round(array_sum(array_column($rows, 'paid_amount')), 2),
        'avg_order_amount' => round(
            array_sum(array_column($rows, 'paid_amount'))
            / max(1, array_sum(array_column($rows, 'order_count'))),
            2
        ),
        'total_amount' => round(array_sum(array_column($rows, 'total_amount')), 2),
        'refund_amount' => round(array_sum(array_column($rows, 'refund_amount')), 2),
        'refund_rate' => round(
            array_sum(array_column($rows, 'refund_amount'))
            / max(0.01, array_sum(array_column($rows, 'paid_amount'))) * 100,
            1
        ),
        'sales_commission' => round(array_sum(array_column($rows, 'sales_commission')), 2),
        'coach_sales_commission' => round(array_sum(array_column($rows, 'coach_sales_commission')), 2),
        'referrer_commission' => round(array_sum(array_column($rows, 'referrer_commission')), 2),
    ];

    json_response(0, 'ok', [
        'list' => $rows,
        'summary' => $totalSummary,
        'stat_month' => $statMonth ?: date('Y-m'),
    ]);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
