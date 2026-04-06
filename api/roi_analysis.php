<?php
/**
 * roi_analysis.php — ROI分析 API
 * 各渠道、各活动的投入产出比分析
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

    // 建表（首次）
    $pdo->exec("CREATE TABLE IF NOT EXISTS oa_roi_analysis (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        month VARCHAR(7) NOT NULL COMMENT '月份',
        channel_name VARCHAR(100) NOT NULL COMMENT '渠道/活动名称',
        ad_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '投放成本(元)',
        lead_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '引流人数',
        paid_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '付费转化数',
        paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '付费金额(元)',
        roi DECIMAL(6,2) DEFAULT 0.00 COMMENT 'ROI',
        profit DECIMAL(10,2) DEFAULT 0.00 COMMENT '毛利(元)',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 列迁移：补全所有旧表可能缺失的列
    $cols = $pdo->query("SHOW COLUMNS FROM oa_roi_analysis")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('month', $cols)) {
        $pdo->exec("ALTER TABLE oa_roi_analysis ADD COLUMN month VARCHAR(7) NOT NULL DEFAULT '2026-01' COMMENT '月份' AFTER id");
    }
    if (!in_array('channel_name', $cols)) {
        $pdo->exec("ALTER TABLE oa_roi_analysis ADD COLUMN channel_name VARCHAR(100) NOT NULL DEFAULT '' COMMENT '渠道/活动名称'");
    }
    if (!in_array('ad_cost', $cols)) {
        $pdo->exec("ALTER TABLE oa_roi_analysis ADD COLUMN ad_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '投放成本(元)'");
    }
    if (!in_array('lead_count', $cols)) {
        $pdo->exec("ALTER TABLE oa_roi_analysis ADD COLUMN lead_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '引流人数'");
    }
    if (!in_array('paid_count', $cols)) {
        $pdo->exec("ALTER TABLE oa_roi_analysis ADD COLUMN paid_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '付费转化数'");
    }
    if (!in_array('paid_amount', $cols)) {
        $pdo->exec("ALTER TABLE oa_roi_analysis ADD COLUMN paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '付费金额(元)'");
    }
    if (!in_array('roi', $cols)) {
        $pdo->exec("ALTER TABLE oa_roi_analysis ADD COLUMN roi DECIMAL(6,2) DEFAULT 0.00 COMMENT 'ROI'");
    }
    if (!in_array('profit', $cols)) {
        $pdo->exec("ALTER TABLE oa_roi_analysis ADD COLUMN profit DECIMAL(10,2) DEFAULT 0.00 COMMENT '毛利(元)'");
    }
    if (!in_array('updated_at', $cols)) {
        $pdo->exec("ALTER TABLE oa_roi_analysis ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }

    // 更新 roi 和 profit 字段（如果有数据但字段为空）
    $pdo->exec("UPDATE oa_roi_analysis SET roi = ROUND(IF(ad_cost > 0, paid_amount / ad_cost, 0), 2), profit = ROUND(paid_amount - ad_cost, 2) WHERE roi IS NULL OR roi = 0");

    $countStmt = $pdo->query("SELECT COUNT(*) FROM oa_roi_analysis");
    if ((int)$countStmt->fetchColumn() === 0) {
        $testData = [
            ['2026-01', '抖音信息流', 28500.00, 892, 67, 132660.00],
            ['2026-01', '小红书薯条', 15800.00, 456, 32, 63360.00],
            ['2026-01', '快手磁力引擎', 8600.00, 234, 15, 29700.00],
            ['2026-01', '视频号广告', 5200.00, 178, 11, 21780.00],
            ['2026-01', 'B站起飞', 3800.00, 112, 8, 15840.00],
            ['2026-01', '知乎知+', 2400.00, 67, 4, 7920.00],
            ['2026-02', '抖音信息流', 32000.00, 1023, 78, 156780.00],
            ['2026-02', '小红书薯条', 18600.00, 534, 41, 81600.00],
            ['2026-02', '快手磁力引擎', 9800.00, 289, 19, 38040.00],
            ['2026-02', '视频号广告', 6700.00, 223, 14, 27720.00],
            ['2026-02', 'B站起飞', 4500.00, 145, 10, 19800.00],
            ['2026-02', '知乎知+', 2800.00, 78, 5, 9900.00],
            ['2026-03', '抖音信息流', 35800.00, 1156, 92, 183300.00],
            ['2026-03', '小红书薯条', 21000.00, 623, 48, 95040.00],
            ['2026-03', '快手磁力引擎', 11200.00, 312, 23, 45780.00],
            ['2026-03', '视频号广告', 7500.00, 267, 18, 35640.00],
            ['2026-03', 'B站起飞', 5200.00, 168, 12, 23760.00],
            ['2026-03', '知乎知+', 3200.00, 89, 6, 11880.00],
        ];
        $stmt = $pdo->prepare("INSERT INTO oa_roi_analysis (month, channel_name, ad_cost, lead_count, paid_count, paid_amount, roi, profit) VALUES (?,?,?,?,?,?, ROUND(IF(? > 0, ? / ?, 0), 2), ROUND(? - ?, 2))");
        foreach ($testData as $row) {
            $stmt->execute([$row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[2], $row[5], $row[2], $row[5], $row[2]]);
        }
    }

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT * FROM oa_roi_analysis ORDER BY month DESC, channel_name");
        json_response(0, 'ok', $stmt->fetchAll());
    }

    if ($method === 'POST') {
        $d = request_body();
        require_fields($d, ['month', 'channel_name', 'ad_cost', 'lead_count', 'paid_count', 'paid_amount']);
        $adCost = (float)$d['ad_cost'];
        $paidAmount = (float)$d['paid_amount'];
        $roi = $adCost > 0 ? round($paidAmount / $adCost, 2) : 0;
        $profit = round($paidAmount - $adCost, 2);
        $stmt = $pdo->prepare("INSERT INTO oa_roi_analysis (month, channel_name, ad_cost, lead_count, paid_count, paid_amount, roi, profit) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $d['month'], $d['channel_name'],
            $adCost, (int)$d['lead_count'],
            (int)$d['paid_count'], $paidAmount,
            $roi, $profit
        ]);
        json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
    }

    if ($method === 'PUT') {
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $fields = ['month', 'channel_name', 'ad_cost', 'lead_count', 'paid_count', 'paid_amount'];
        $sets = []; $vals = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $d)) { $sets[] = "$f=?"; $vals[] = $d[$f]; }
        }
        // 更新 roi 和 profit
        if (array_key_exists('ad_cost', $d) || array_key_exists('paid_amount', $d)) {
            $sets[] = "roi=?"; $vals[] = $roi ?? 0;
            $sets[] = "profit=?"; $vals[] = $profit ?? 0;
        }
        if (empty($sets)) json_response(400, '无更新字段', null, 400);
        $vals[] = $id;
        $pdo->prepare("UPDATE oa_roi_analysis SET " . implode(',', $sets) . " WHERE id=?")->execute($vals);
        json_response(0, 'updated');
    }

    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $pdo->prepare("DELETE FROM oa_roi_analysis WHERE id=?")->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
