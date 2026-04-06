<?php
/**
 * leads_pool.php — 线索池 API
 * 所有未分配线索统一入池，销售可主动领取或系统自动分配
 */
while (ob_get_level()) ob_end_clean();
ob_start();

ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/db.php';

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

function request_body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function require_fields(array $data, array $fields): void
{
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
            json_response(400, "字段 {$field} 不能为空", null, 400);
        }
    }
}

try {
    $pdo = get_db_connection();

    $pdo->exec("CREATE TABLE IF NOT EXISTS oa_leads_pool (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(50) NOT NULL COMMENT '客户姓名',
        phone VARCHAR(20) NOT NULL COMMENT '手机号',
        source_channel VARCHAR(50) NOT NULL COMMENT '来源渠道',
        tag VARCHAR(50) NOT NULL DEFAULT '中意向' COMMENT '线索标签',
        campaign VARCHAR(200) DEFAULT '' COMMENT '来源活动',
        assign_status ENUM('未分配','已分配','已领取') NOT NULL DEFAULT '未分配' COMMENT '归属状态',
        assign_time DATETIME DEFAULT NULL COMMENT '分配时间',
        assign_to VARCHAR(50) DEFAULT '' COMMENT '分配给',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_phone (phone)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $countStmt = $pdo->query("SELECT COUNT(*) FROM oa_leads_pool");
    if ((int)$countStmt->fetchColumn() === 0) {
        $testData = [
            ['王思琪', '13812345601', '抖音', '高意向', 'Python零基础课程推广', '已领取', '2026-03-10 09:30:00', '李明辉'],
            ['张浩然', '13912345602', '小红书', '课程咨询', '新媒体运营工具推荐', '已分配', '2026-03-11 14:00:00', '张婉清'],
            ['刘雨萱', '15012345603', '抖音', '高意向', '编程语言选择指南', '已领取', '2026-03-12 10:15:00', '周子涵'],
            ['陈嘉豪', '13712345604', '转介绍', '高意向', '老学员推荐', '已领取', '2026-03-08 16:00:00', '李明辉'],
            ['赵敏', '15212345605', '百度', '中意向', '百度SEM投放', '未分配', NULL, ''],
            ['孙磊', '18812345606', '抖音', '价格敏感', 'ChatGPT编程教程', '未分配', NULL, ''],
            ['吴思涵', '13512345607', '小红书', '课程咨询', '设计师转前端', '已分配', '2026-03-14 11:00:00', '张婉清'],
            ['周俊杰', '18612345608', '视频号', '试听报名', '数据分析入门直播', '已领取', '2026-03-15 20:30:00', '陈思远'],
            ['郑雨桐', '15812345609', '知乎', '中意向', 'IT转行经验帖', '未分配', NULL, ''],
            ['黄雅琪', '13612345610', '抖音', '高意向', '短视频剪辑课推广', '已领取', '2026-03-16 15:00:00', '李明辉'],
            ['林浩宇', '18912345611', 'B站', '中意向', 'React Native教程', '未分配', NULL, ''],
            ['杨梦瑶', '15312345612', '小红书', '价格敏感', 'Excel技巧合集', '未分配', NULL, ''],
            ['马天翔', '18712345613', '快手', '课程咨询', '职教直播引流', '已分配', '2026-03-18 09:00:00', '王志强'],
            ['徐晓峰', '15512345614', '抖音', '试听报名', '算法面试精讲', '已领取', '2026-03-19 21:00:00', '周子涵'],
            ['何佳欣', '13812345615', '视频号', '高意向', 'Vue3项目实战推广', '未分配', NULL, ''],
            ['宋志远', '15112345616', '转介绍', '高意向', '老学员推荐-前端课', '已领取', '2026-03-09 13:00:00', '陈思远'],
            ['唐思琪', '18212345617', '百度', '中意向', 'SEM数据分析关键词', '未分配', NULL, ''],
            ['韩文博', '15712345618', '抖音', '课程咨询', 'Figma设计系统课', '已分配', '2026-03-20 10:30:00', '赵宇航'],
        ];
        $stmt = $pdo->prepare("INSERT INTO oa_leads_pool (customer_name, phone, source_channel, tag, campaign, assign_status, assign_time, assign_to) VALUES (?,?,?,?,?,?,?,?)");
        foreach ($testData as $row) {
            $stmt->execute($row);
        }
    }

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $keyword = trim((string)($_GET['keyword'] ?? ''));
        $source = trim((string)($_GET['source'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));

        $where = '1=1';
        $params = [];
        if ($keyword !== '') { $where .= ' AND (customer_name LIKE ? OR phone LIKE ?)'; $params[] = "%{$keyword}%"; $params[] = "%{$keyword}%"; }
        if ($source !== '') { $where .= ' AND source_channel = ?'; $params[] = $source; }
        if ($status !== '') { $where .= ' AND assign_status = ?'; $params[] = $status; }

        $stmt = $pdo->prepare("SELECT * FROM oa_leads_pool WHERE {$where} ORDER BY created_at DESC");
        $stmt->execute($params);
        json_response(0, 'ok', $stmt->fetchAll());
    }

    if ($method === 'POST') {
        $d = request_body();
        require_fields($d, ['customer_name', 'phone']);
        $stmt = $pdo->prepare("INSERT INTO oa_leads_pool (customer_name, phone, source_channel, tag, campaign, assign_status) VALUES (?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE source_channel=VALUES(source_channel), tag=VALUES(tag), campaign=VALUES(campaign)");
        $stmt->execute([
            $d['customer_name'], $d['phone'],
            $d['source_channel'] ?? '抖音', $d['tag'] ?? '中意向',
            $d['campaign'] ?? '', '未分配'
        ]);
        json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
    }

    if ($method === 'PUT') {
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);

        $fields = ['customer_name', 'phone', 'source_channel', 'tag', 'campaign', 'assign_status', 'assign_time', 'assign_to'];
        $sets = []; $vals = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $d) && $d[$f] !== '') { $sets[] = "$f=?"; $vals[] = $d[$f]; }
        }
        if (empty($sets)) json_response(400, '无更新字段', null, 400);
        $vals[] = $id;
        $pdo->prepare("UPDATE oa_leads_pool SET " . implode(',', $sets) . " WHERE id=?")->execute($vals);
        json_response(0, 'updated');
    }

    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $pdo->prepare("DELETE FROM oa_leads_pool WHERE id=?")->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
