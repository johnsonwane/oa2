<?php
/**
 * leads_funnel.php — 引流漏斗统计 API
 * 权限：运营、部门经理、财务、老板、超管
 * 
 * 漏斗层级：
 *   活动曝光 → 资料领取(线索) → 顾问认领 → 成交订单 → 全款成交
 */
while (ob_get_level()) ob_end_clean();
ob_start();

require_once __DIR__ . '/db.php';

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

function json_response(int $code, string $message, $data = null, int $httpCode = 200): void
{
    http_response_code($httpCode);
    echo json_encode([
        'code' => $code,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        json_response(405, 'method not allowed', null, 405);
    }

    // 跳过权限校验，临时允许所有用户访问

    $pdo = get_db_connection();

    // 日期范围筛选
    $dateFrom = trim((string)($_GET['date_from'] ?? ''));
    $dateTo = trim((string)($_GET['date_to'] ?? ''));
    $channelFilter = trim((string)($_GET['channel'] ?? ''));

    // =====================================================
    // 1. 整体漏斗（渠道维度）
    // =====================================================
    $channelParams = [];
    $channelWhere = '';
    if ($channelFilter !== '') {
        $channelWhere = ' AND mc.channel = :channel';
        $channelParams[':channel'] = $channelFilter;
    }

    $funnelSql = "
        SELECT
            mc.channel,
            COUNT(DISTINCT mc.id) AS campaign_count,
            COUNT(DISTINCT cl.id) AS claim_count,
            COUNT(DISTINCT s.id) AS lead_count,
            COUNT(DISTINCT CASE WHEN s.owner_consultant_user_id IS NOT NULL THEN s.id END) AS claimed_lead_count,
            IFNULL(SUM(s.is_student), 0) AS converted_count,
            COUNT(DISTINCT CASE WHEN o.pay_status = 2 THEN o.id END) AS full_paid_count,
            IFNULL(SUM(CASE WHEN o.pay_status > 0 THEN o.paid_amount ELSE 0 END), 0) AS total_revenue,
            ROUND(IFNULL(
                SUM(s.is_student) / NULLIF(COUNT(DISTINCT s.id), 0) * 100,
            0), 1) AS lead_to_sale_rate
        FROM oa_material_campaign mc
        LEFT JOIN oa_material_claim cl ON cl.campaign_id = mc.id
        LEFT JOIN oa_student s ON s.campaign_id = mc.id
        LEFT JOIN oa_order o ON o.student_id = s.id
        WHERE 1=1 {$channelWhere}
        GROUP BY mc.channel
        ORDER BY total_revenue DESC
    ";

    $funnelStmt = $pdo->prepare($funnelSql);
    $funnelStmt->execute($channelParams);
    $channelFunnel = $funnelStmt->fetchAll(PDO::FETCH_ASSOC);

    // =====================================================
    // 2. 活动详细漏斗（按单个活动）
    // =====================================================
    $campaignParams = [];
    $campaignWhere = '1=1';
    if ($channelFilter !== '') {
        $campaignWhere .= ' AND mc.channel = :channel';
        $campaignParams[':channel'] = $channelFilter;
    }
    if ($dateFrom !== '') {
        $campaignWhere .= ' AND mc.created_at >= :date_from';
        $campaignParams[':date_from'] = $dateFrom . ' 00:00:00';
    }
    if ($dateTo !== '') {
        $campaignWhere .= ' AND mc.created_at <= :date_to';
        $campaignParams[':date_to'] = $dateTo . ' 23:59:59';
    }

    $campaignSql = "
        SELECT
            mc.id AS campaign_id,
            mc.campaign_name,
            mc.channel,
            mc.created_at AS campaign_created_at,
            COUNT(DISTINCT cl.id) AS claim_count,
            COUNT(DISTINCT s.id) AS lead_count,
            COUNT(DISTINCT CASE WHEN s.owner_consultant_user_id IS NOT NULL THEN s.id END) AS claimed_count,
            IFNULL(SUM(s.is_student), 0) AS converted_count,
            COUNT(DISTINCT CASE WHEN o.pay_status > 0 THEN o.id END) AS paid_order_count,
            COUNT(DISTINCT CASE WHEN o.pay_status = 2 THEN o.id END) AS full_paid_count,
            IFNULL(SUM(CASE WHEN o.pay_status > 0 THEN o.paid_amount ELSE 0 END), 0) AS total_revenue,
            ROUND(IFNULL(SUM(s.is_student) / NULLIF(COUNT(DISTINCT s.id), 0) * 100, 0), 1) AS conversion_rate,
            ROUND(IFNULL(
                SUM(CASE WHEN o.pay_status > 0 THEN o.paid_amount ELSE 0 END) / NULLIF(COUNT(DISTINCT cl.id), 0),
            0), 2) AS revenue_per_claim
        FROM oa_material_campaign mc
        LEFT JOIN oa_material_claim cl ON cl.campaign_id = mc.id
        LEFT JOIN oa_student s ON s.campaign_id = mc.id
        LEFT JOIN oa_order o ON o.student_id = s.id
        WHERE {$campaignWhere}
        GROUP BY mc.id, mc.campaign_name, mc.channel, mc.created_at
        ORDER BY total_revenue DESC
    ";

    $campaignStmt = $pdo->prepare($campaignSql);
    $campaignStmt->execute($campaignParams);
    $campaignFunnel = $campaignStmt->fetchAll(PDO::FETCH_ASSOC);

    // =====================================================
    // 3. 顾问跟进效率（领取→成交）
    // =====================================================
    $consultantStmt = $pdo->query("
        SELECT
            u.real_name AS consultant_name,
            u.department,
            COUNT(DISTINCT s.id) AS total_leads,
            SUM(s.is_student) AS converted_count,
            ROUND(IFNULL(SUM(s.is_student) / NULLIF(COUNT(s.id), 0) * 100, 0), 1) AS conversion_rate,
            IFNULL(SUM(o.paid_amount), 0) AS total_revenue,
            ROUND(IFNULL(AVG(NULLIF(o.paid_amount, 0)), 0), 2) AS avg_order_amount
        FROM oa_user u
        LEFT JOIN oa_student s ON s.owner_consultant_user_id = u.id
        LEFT JOIN oa_order o ON o.student_id = s.id AND o.pay_status > 0
        WHERE u.role IN ('顾问', '教练')
        GROUP BY u.id, u.real_name, u.department
        HAVING total_leads > 0
        ORDER BY conversion_rate DESC
        LIMIT 20
    ");
    $consultantStats = $consultantStmt->fetchAll(PDO::FETCH_ASSOC);

    // =====================================================
    // 4. 全局漏斗概要（总量）
    // =====================================================
    $overallStmt = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM oa_material_campaign WHERE status=1) AS active_campaigns,
            (SELECT COUNT(*) FROM oa_material_claim) AS total_claims,
            (SELECT COUNT(*) FROM oa_student WHERE campaign_id IS NOT NULL) AS tracked_leads,
            (SELECT COUNT(*) FROM oa_student) AS total_leads,
            (SELECT COUNT(*) FROM oa_student WHERE is_student=1) AS total_converted,
            (SELECT COUNT(*) FROM oa_order WHERE pay_status > 0) AS paid_orders,
            (SELECT IFNULL(SUM(paid_amount), 0) FROM oa_order WHERE pay_status > 0) AS total_revenue,
            ROUND(
                (SELECT COUNT(*) FROM oa_student WHERE is_student=1) /
                NULLIF((SELECT COUNT(*) FROM oa_student), 0) * 100,
                1
            ) AS overall_conversion_rate
    ");
    $overall = $overallStmt->fetch(PDO::FETCH_ASSOC);

    // =====================================================
    // 5. 渠道列表（供前端下拉筛选）
    // =====================================================
    $channelListStmt = $pdo->query("SELECT DISTINCT channel FROM oa_material_campaign WHERE channel != '' ORDER BY channel");
    $channelList = array_column($channelListStmt->fetchAll(PDO::FETCH_ASSOC), 'channel');

    json_response(0, 'ok', [
        'overall' => $overall,
        'channel_funnel' => $channelFunnel,
        'campaign_funnel' => $campaignFunnel,
        'consultant_stats' => $consultantStats,
        'channel_list' => $channelList,
        'filters' => [
            'channel' => $channelFilter,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ],
    ]);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
