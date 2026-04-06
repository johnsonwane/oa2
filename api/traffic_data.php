<?php
/**
 * traffic_data.php — 引流数据 API
 * 每日/每周渠道引流数据统计
 */

// 确保输出缓冲开启，捕获所有意外输出
while (ob_get_level()) ob_end_clean();
ob_start();

ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/db.php';

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
}

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

    $pdo->exec("CREATE TABLE IF NOT EXISTS oa_traffic_data (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        stat_date DATE NOT NULL COMMENT '统计日期',
        channel VARCHAR(50) NOT NULL COMMENT '渠道',
        exposure INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '曝光量',
        clicks INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '点击量',
        click_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT '点击率(%)',
        lead_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '留资人数',
        lead_cost DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT '留资成本(元)',
        conversion_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '转化报名数',
        conversion_cost DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT '转化成本(元)',
        roi DECIMAL(6,2) NOT NULL DEFAULT 0.00 COMMENT 'ROI',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_date_channel (stat_date, channel)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 列迁移：补全所有旧表可能缺失的列
    $cols = $pdo->query("SHOW COLUMNS FROM oa_traffic_data")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('channel', $cols)) {
        $pdo->exec("ALTER TABLE oa_traffic_data ADD COLUMN channel VARCHAR(50) NOT NULL DEFAULT '' COMMENT '渠道'");
    }
    if (!in_array('exposure', $cols)) {
        $pdo->exec("ALTER TABLE oa_traffic_data ADD COLUMN exposure INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '曝光量'");
    }
    if (!in_array('clicks', $cols)) {
        $pdo->exec("ALTER TABLE oa_traffic_data ADD COLUMN clicks INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '点击量'");
    }
    if (!in_array('click_rate', $cols)) {
        $pdo->exec("ALTER TABLE oa_traffic_data ADD COLUMN click_rate DECIMAL(5,2) DEFAULT 0.00 COMMENT '点击率(%)'");
    }
    if (!in_array('lead_count', $cols)) {
        $pdo->exec("ALTER TABLE oa_traffic_data ADD COLUMN lead_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '留资人数'");
    }
    if (!in_array('lead_cost', $cols)) {
        $pdo->exec("ALTER TABLE oa_traffic_data ADD COLUMN lead_cost DECIMAL(8,2) DEFAULT 0.00 COMMENT '留资成本(元)'");
    }
    if (!in_array('conversion_count', $cols)) {
        $pdo->exec("ALTER TABLE oa_traffic_data ADD COLUMN conversion_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '转化报名数'");
    }
    if (!in_array('conversion_cost', $cols)) {
        $pdo->exec("ALTER TABLE oa_traffic_data ADD COLUMN conversion_cost DECIMAL(8,2) DEFAULT 0.00 COMMENT '转化成本(元)'");
    }
    if (!in_array('roi', $cols)) {
        $pdo->exec("ALTER TABLE oa_traffic_data ADD COLUMN roi DECIMAL(6,2) DEFAULT 0.00 COMMENT 'ROI'");
    }

    $countStmt = $pdo->query("SELECT COUNT(*) FROM oa_traffic_data");
    if ((int)$countStmt->fetchColumn() === 0) {
        $testData = [];
        $channels = ['抖音', '小红书', '视频号', 'B站', '快手', '知乎'];
        $baseDate = '2026-03-07';
        for ($day = 0; $day < 30; $day++) {
            $date = date('Y-m-d', strtotime($baseDate . " +{$day} days"));
            foreach ($channels as $ch) {
                // 根据渠道设置曝光基准（兼容PHP 7）
            $exposureMap = [
                '抖音' => rand(80000, 250000),
                '小红书' => rand(30000, 120000),
                '视频号' => rand(20000, 80000),
                'B站' => rand(15000, 60000),
                '快手' => rand(10000, 50000),
                '知乎' => rand(5000, 25000),
            ];
            $exposure = isset($exposureMap[$ch]) ? $exposureMap[$ch] : rand(10000, 100000);
                $clicks = (int)($exposure * (rand(3, 12) / 100));
                $clickRate = $exposure > 0 ? round($clicks / $exposure * 100, 2) : 0;
                $leadCount = (int)($clicks * (rand(2, 8) / 100));
                $leadCost = $leadCount > 0 ? round(rand(15, 45) + rand(0, 100) / 100, 2) : 0;
                $conversionCount = (int)($leadCount * (rand(5, 25) / 100));
                $conversionCost = $conversionCount > 0 ? round(rand(80, 250) + rand(0, 100) / 100, 2) : 0;
                $totalRevenue = $conversionCount * rand(1980, 5980);
                $totalCost = $leadCount * $leadCost;
                $roi = $totalCost > 0 ? round($totalRevenue / $totalCost, 2) : 0;
                $testData[] = [$date, $ch, $exposure, $clicks, $clickRate, $leadCount, $leadCost, $conversionCount, $conversionCost, $roi];
            }
        }
        $stmt = $pdo->prepare("INSERT INTO oa_traffic_data (stat_date, channel, exposure, clicks, click_rate, lead_count, lead_cost, conversion_count, conversion_cost, roi) VALUES (?,?,?,?,?,?,?,?,?,?)");
        foreach ($testData as $row) {
            $stmt->execute($row);
        }
    }

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $where = '1=1';
        $params = [];
        $date = trim((string)($_GET['date'] ?? ''));
        $channel = trim((string)($_GET['channel'] ?? ''));
        if ($date !== '') { $where .= ' AND stat_date = ?'; $params[] = $date; }
        if ($channel !== '') { $where .= ' AND channel = ?'; $params[] = $channel; }

        $sql = "SELECT * FROM oa_traffic_data WHERE {$where} ORDER BY stat_date DESC, channel";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response(0, 'ok', $stmt->fetchAll());
    }

    if ($method === 'POST') {
        $d = request_body();
        require_fields($d, ['stat_date', 'channel']);
        $stmt = $pdo->prepare("INSERT INTO oa_traffic_data (stat_date, channel, exposure, clicks, click_rate, lead_count, lead_cost, conversion_count, conversion_cost, roi) VALUES (?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE exposure=VALUES(exposure), clicks=VALUES(clicks), click_rate=VALUES(click_rate), lead_count=VALUES(lead_count), lead_cost=VALUES(lead_cost), conversion_count=VALUES(conversion_count), conversion_cost=VALUES(conversion_cost), roi=VALUES(roi)");
        $stmt->execute([
            $d['stat_date'], $d['channel'],
            (int)($d['exposure'] ?? 0), (int)($d['clicks'] ?? 0),
            (float)($d['click_rate'] ?? 0), (int)($d['lead_count'] ?? 0),
            (float)($d['lead_cost'] ?? 0), (int)($d['conversion_count'] ?? 0),
            (float)($d['conversion_cost'] ?? 0), (float)($d['roi'] ?? 0)
        ]);
        json_response(0, 'upserted');
    }

    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $pdo->prepare("DELETE FROM oa_traffic_data WHERE id=?")->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
