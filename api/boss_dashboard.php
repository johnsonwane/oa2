<?php
/**
 * boss_dashboard.php — 老板专用经营看板
 * 权限：老板 / 超管
 * 返回：今日汇总、本月汇总、部门排名、热门课程、引流漏斗、最近动态
 */
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        json_response(405, 'method not allowed', null, 405);
    }

    // 权限校验：仅老板和超管可访问
    if (!auth_is_admin_like() && auth_user_role() !== '老板') {
        json_response(403, '仅老板可查看此看板', null, 403);
    }

    $pdo = get_db_connection();

    // =====================================================
    // 1. 今日汇总
    // =====================================================
    $todayStmt = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM oa_student WHERE DATE(created_at) = CURDATE()) AS new_leads_today,
            (SELECT COUNT(*) FROM oa_student WHERE is_student = 1 AND DATE(converted_at) = CURDATE()) AS new_conversions_today,
            (SELECT COUNT(*) FROM oa_order WHERE DATE(created_at) = CURDATE()) AS new_orders_today,
            (SELECT IFNULL(SUM(paid_amount), 0) FROM oa_order WHERE DATE(created_at) = CURDATE() AND pay_status > 0) AS revenue_today,
            (SELECT COUNT(*) FROM oa_refund_request WHERE DATE(requested_at) = CURDATE()) AS refund_requests_today
    ");
    $today = $todayStmt->fetch(PDO::FETCH_ASSOC);

    // =====================================================
    // 2. 本月汇总
    // =====================================================
    $monthStmt = $pdo->query("
        SELECT
            DATE_FORMAT(NOW(), '%Y-%m') AS stat_month,
            (SELECT COUNT(*) FROM oa_student WHERE DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')) AS new_leads_month,
            (SELECT COUNT(*) FROM oa_student WHERE is_student = 1 AND DATE_FORMAT(converted_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')) AS new_conversions_month,
            (SELECT COUNT(*) FROM oa_order WHERE DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')) AS new_orders_month,
            (SELECT IFNULL(SUM(paid_amount), 0) FROM oa_order WHERE DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m') AND pay_status > 0) AS revenue_month,
            (SELECT IFNULL(SUM(refund_amount), 0) FROM oa_order WHERE DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')) AS refund_amount_month,
            (SELECT COUNT(*) FROM oa_student WHERE is_student = 0) AS total_leads,
            (SELECT COUNT(*) FROM oa_student WHERE is_student = 1) AS total_students,
            (SELECT IFNULL(SUM(paid_amount), 0) FROM oa_order WHERE pay_status > 0) AS total_revenue
    ");
    $month = $monthStmt->fetch(PDO::FETCH_ASSOC);

    // 本月转化率
    $leads = (int)($month['new_leads_month'] ?? 0);
    $converts = (int)($month['new_conversions_month'] ?? 0);
    $month['conversion_rate_month'] = $leads > 0 ? round($converts / $leads * 100, 1) : 0;
    // 本月客单价
    $orders = (int)($month['new_orders_month'] ?? 1);
    $month['avg_order_amount_month'] = $orders > 0 ? round((float)($month['revenue_month'] ?? 0) / $orders, 2) : 0;

    // =====================================================
    // 3. 近6个月趋势
    // =====================================================
    $trendStmt = $pdo->query("
        SELECT
            DATE_FORMAT(o.created_at, '%Y-%m') AS stat_month,
            COUNT(DISTINCT o.id) AS order_count,
            IFNULL(SUM(o.paid_amount), 0) AS revenue,
            COUNT(DISTINCT CASE WHEN s.is_student = 1 THEN s.id END) AS conversions
        FROM oa_order o
        LEFT JOIN oa_student s ON s.id = o.student_id
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY stat_month
        ORDER BY stat_month ASC
    ");
    $trend = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

    // =====================================================
    // 4. 部门业绩排名（本月）
    // =====================================================
    $deptStmt = $pdo->query("
        SELECT
            u.department,
            COUNT(DISTINCT CASE WHEN s.is_student = 0 THEN s.id END) AS leads_count,
            COUNT(DISTINCT CASE WHEN s.is_student = 1 THEN s.id END) AS converted_count,
            IFNULL(SUM(o.paid_amount), 0) AS revenue,
            COUNT(DISTINCT o.id) AS order_count,
            ROUND(
                IFNULL(COUNT(DISTINCT CASE WHEN s.is_student = 1 THEN s.id END) / NULLIF(COUNT(DISTINCT s.id), 0) * 100, 0),
                1
            ) AS conversion_rate
        FROM oa_user u
        LEFT JOIN oa_student s ON (s.owner_consultant_user_id = u.id OR s.headteacher_user_id = u.id OR s.coach_user_id = u.id)
        LEFT JOIN oa_order o ON o.student_id = s.id AND DATE_FORMAT(o.created_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')
        WHERE u.employment_status = '在职' OR u.employment_status IS NULL
        GROUP BY u.department
        HAVING u.department != '' AND u.department IS NOT NULL
        ORDER BY revenue DESC
        LIMIT 10
    ");
    $deptRanking = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

    // =====================================================
    // 5. 热门课程（按收款额）
    // =====================================================
    $courseStmt = $pdo->query("
        SELECT
            c.id,
            c.course_name,
            c.price,
            COUNT(DISTINCT o.id) AS order_count,
            SUM(CASE WHEN o.order_type = 'first' THEN 1 ELSE 0 END) AS first_orders,
            SUM(CASE WHEN o.order_type = 'renewal' THEN 1 ELSE 0 END) AS renewal_orders,
            IFNULL(SUM(o.paid_amount), 0) AS total_revenue,
            ROUND(IFNULL(AVG(NULLIF(o.paid_amount,0)),0), 2) AS avg_paid
        FROM oa_course c
        LEFT JOIN oa_order o ON o.course_id = c.id AND o.pay_status > 0
        WHERE c.status = 1
        GROUP BY c.id, c.course_name, c.price
        ORDER BY total_revenue DESC
        LIMIT 5
    ");
    $topCourses = $courseStmt->fetchAll(PDO::FETCH_ASSOC);

    // =====================================================
    // 6. 引流漏斗（各活动来源转化）
    // =====================================================
    $funnelStmt = $pdo->query("
        SELECT
            mc.channel,
            mc.campaign_name,
            COUNT(DISTINCT s.id) AS total_leads,
            SUM(s.is_student) AS converted_count,
            ROUND(IFNULL(SUM(s.is_student)/NULLIF(COUNT(s.id),0)*100,0), 1) AS conversion_rate,
            IFNULL(SUM(o.paid_amount), 0) AS total_revenue
        FROM oa_material_campaign mc
        LEFT JOIN oa_student s ON s.campaign_id = mc.id
        LEFT JOIN oa_order o ON o.student_id = s.id AND o.pay_status > 0
        GROUP BY mc.id, mc.channel, mc.campaign_name
        ORDER BY total_leads DESC
        LIMIT 10
    ");
    $funnel = $funnelStmt->fetchAll(PDO::FETCH_ASSOC);

    // 无活动来源的自然流量汇总
    $organicStmt = $pdo->query("
        SELECT
            '自然/直接来源' AS campaign_name,
            'organic' AS channel,
            COUNT(*) AS total_leads,
            SUM(is_student) AS converted_count,
            ROUND(IFNULL(SUM(is_student)/NULLIF(COUNT(*),0)*100,0), 1) AS conversion_rate
        FROM oa_student
        WHERE campaign_id IS NULL
    ");
    $organic = $organicStmt->fetch(PDO::FETCH_ASSOC);
    if ($organic && (int)$organic['total_leads'] > 0) {
        $organic['total_revenue'] = 0;
        array_unshift($funnel, $organic);
    }

    // =====================================================
    // 7. 最近10条重要动态
    // =====================================================
    $activityStmt = $pdo->query("
        (SELECT 'new_order' AS event_type, CONCAT('新订单：', s.name, ' 购买 ', c.course_name, ' ¥', o.paid_amount) AS description, o.created_at AS event_time
         FROM oa_order o LEFT JOIN oa_student s ON s.id=o.student_id LEFT JOIN oa_course c ON c.id=o.course_id
         WHERE o.pay_status > 0 ORDER BY o.created_at DESC LIMIT 5)
        UNION ALL
        (SELECT 'new_lead' AS event_type, CONCAT('新线索：', name, '（', IFNULL(source,'未知来源'), '）') AS description, created_at AS event_time
         FROM oa_student ORDER BY created_at DESC LIMIT 5)
        ORDER BY event_time DESC
        LIMIT 10
    ");
    $activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

    // =====================================================
    // 8. 财务快照（本月收支）
    // =====================================================
    $financeStmt = $pdo->query("
        SELECT
            IFNULL(SUM(CASE WHEN record_type='income' THEN amount ELSE 0 END), 0) AS month_income,
            IFNULL(SUM(CASE WHEN record_type='expense' THEN amount ELSE 0 END), 0) AS month_expense
        FROM oa_finance_record
        WHERE DATE_FORMAT(record_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
    ");
    $financeSnap = $financeStmt->fetch(PDO::FETCH_ASSOC);
    $financeSnap['month_balance'] = round(
        (float)($financeSnap['month_income'] ?? 0) - (float)($financeSnap['month_expense'] ?? 0),
        2
    );

    json_response(0, 'ok', [
        'today' => $today,
        'month' => $month,
        'trend' => $trend,
        'dept_ranking' => $deptRanking,
        'top_courses' => $topCourses,
        'funnel' => $funnel,
        'activities' => $activities,
        'finance_snapshot' => $financeSnap,
        'generated_at' => date('Y-m-d H:i:s'),
    ]);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
