<?php
/**
 * leads_dispatch.php — 分配机制 API
 * 设置线索自动分配规则（按渠道/区域/数量均分等）
 */
require_once __DIR__ . '/db.php';

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = get_db_connection();

    $pdo->exec("CREATE TABLE IF NOT EXISTS oa_leads_dispatch (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        rule_name VARCHAR(100) NOT NULL COMMENT '规则名称',
        apply_channel VARCHAR(50) NOT NULL DEFAULT '全部渠道' COMMENT '适用渠道',
        assign_method ENUM('均分','优先','轮询') NOT NULL DEFAULT '均分' COMMENT '分配方式',
        assign_count INT UNSIGNED NOT NULL DEFAULT 5 COMMENT '每次分配数量',
        assign_to VARCHAR(500) NOT NULL COMMENT '分配给(多人逗号分隔)',
        status ENUM('启用','停用') NOT NULL DEFAULT '启用' COMMENT '状态',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $countStmt = $pdo->query("SELECT COUNT(*) FROM oa_leads_dispatch");
    if ((int)$countStmt->fetchColumn() === 0) {
        $testData = [
            ['抖音渠道均分规则', '抖音', '均分', 10, '李明辉,周子涵', '启用'],
            ['小红书渠道轮询分配', '小红书', '轮询', 8, '张婉清,韩文博', '启用'],
            ['视频号线索优先分配', '视频号', '优先', 5, '陈思远', '启用'],
            ['B站线索均分规则', 'B站', '均分', 5, '赵宇航,陈思远', '启用'],
            ['转介绍线索直分配', '转介绍', '优先', 3, '李明辉', '启用'],
            ['百度SEM线索轮询', '百度', '轮询', 6, '张婉清,周子涵,陈思远', '启用'],
            ['快手渠道均分', '快手', '均分', 5, '王志强,赵宇航', '启用'],
            ['知乎线索分配规则', '知乎', '轮询', 4, '林小雅,周子涵', '停用'],
            ['全渠道兜底规则', '全部渠道', '轮询', 20, '李明辉,张婉清,陈思远,周子涵,赵宇航', '启用'],
            ['高意向线索优先分配', '全部渠道', '优先', 3, '李明辉,陈思远', '启用'],
            ['试听报名线索分配', '全部渠道', '均分', 5, '周子涵,张婉清', '停用'],
            ['价格敏感线索分配', '全部渠道', '轮询', 10, '张婉清,林小雅', '启用'],
        ];
        $stmt = $pdo->prepare("INSERT INTO oa_leads_dispatch (rule_name, apply_channel, assign_method, assign_count, assign_to, status) VALUES (?,?,?,?,?,?)");
        foreach ($testData as $row) {
            $stmt->execute($row);
        }
    }

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $channel = trim((string)($_GET['channel'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));
        $where = '1=1';
        $params = [];
        if ($channel !== '') { $where .= ' AND apply_channel = ?'; $params[] = $channel; }
        if ($status !== '') { $where .= ' AND status = ?'; $params[] = $status; }
        $stmt = $pdo->prepare("SELECT * FROM oa_leads_dispatch WHERE {$where} ORDER BY status DESC, id");
        $stmt->execute($params);
        json_response(0, 'ok', $stmt->fetchAll());
    }

    if ($method === 'POST') {
        $d = request_body();
        require_fields($d, ['rule_name', 'assign_method', 'assign_count', 'assign_to']);
        $stmt = $pdo->prepare("INSERT INTO oa_leads_dispatch (rule_name, apply_channel, assign_method, assign_count, assign_to, status) VALUES (?,?,?,?,?,?)");
        $stmt->execute([
            $d['rule_name'], $d['apply_channel'] ?? '全部渠道',
            $d['assign_method'], (int)$d['assign_count'],
            $d['assign_to'], $d['status'] ?? '启用'
        ]);
        json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
    }

    if ($method === 'PUT') {
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $fields = ['rule_name', 'apply_channel', 'assign_method', 'assign_count', 'assign_to', 'status'];
        $sets = []; $vals = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $d)) { $sets[] = "$f=?"; $vals[] = $d[$f]; }
        }
        if (empty($sets)) json_response(400, '无更新字段', null, 400);
        $vals[] = $id;
        $pdo->prepare("UPDATE oa_leads_dispatch SET " . implode(',', $sets) . " WHERE id=?")->execute($vals);
        json_response(0, 'updated');
    }

    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $pdo->prepare("DELETE FROM oa_leads_dispatch WHERE id=?")->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
