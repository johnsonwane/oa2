<?php
/**
 * channels.php — 渠道发布 API
 * 管理各渠道账号、密码、发布规则、负责人
 */
require_once __DIR__ . '/db.php';

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = get_db_connection();

    $pdo->exec("CREATE TABLE IF NOT EXISTS oa_channels (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        channel_name VARCHAR(50) NOT NULL COMMENT '渠道名称',
        account_name VARCHAR(100) NOT NULL COMMENT '账号名称',
        account_id VARCHAR(100) NOT NULL COMMENT '账号ID/手机号',
        password VARCHAR(200) DEFAULT '' COMMENT '密码',
        owner VARCHAR(50) NOT NULL COMMENT '运营负责人',
        publish_freq VARCHAR(50) NOT NULL DEFAULT '每日' COMMENT '发布频率',
        status ENUM('启用','停用') NOT NULL DEFAULT '启用' COMMENT '状态',
        remark VARCHAR(500) DEFAULT '' COMMENT '备注',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $countStmt = $pdo->query("SELECT COUNT(*) FROM oa_channels");
    if ((int)$countStmt->fetchColumn() === 0) {
        $testData = [
            ['抖音', '星辰教育官方号', 'DY20260301', 'dy#starEdu2026!', '李明辉', '每日', '启用', '企业蓝V认证，日更2条'],
            ['抖音', '编程小课堂', 'DY20260302', 'dy#code2026!', '李明辉', '每周5次', '启用', '技术干货号，粉丝12万'],
            ['小红书', '星辰留学规划', 'XHS_EDU001', 'xhs#plan2026!', '张婉清', '每周3次', '启用', '留学规划垂直号'],
            ['小红书', '职场成长笔记', 'XHS_CAREER02', 'xhs#career26!', '张婉清', '每周5次', '启用', '职场干货，粉丝8.6万'],
            ['快手', '星辰职教直播', 'KS_LIVE001', 'ks#live2026!', '王志强', '每日', '启用', '主做直播引流'],
            ['快手', 'IT技能分享', 'KS_ITSKILL02', 'ks#itskl26!', '王志强', '每周3次', '停用', '测试阶段暂停更新'],
            ['视频号', '星辰教育学院', 'SPH_MAIN001', 'sph#main2026!', '陈思远', '每日', '启用', '视频号主号'],
            ['视频号', '编程每日一练', 'SPH_CODE002', 'sph#code26!', '陈思远', '不定期', '启用', '技术副号'],
            ['B站', '星辰IT课堂', 'BLI_IT001', 'bli#it2026!', '赵宇航', '每周3次', '启用', 'B站技术教程，粉丝3.2万'],
            ['B站', '设计师成长之路', 'BLI_DESIGN02', 'bli#design26!', '赵宇航', '每周5次', '启用', '设计类教程'],
            ['知乎', '星辰教育官方', 'ZHI_MAIN001', 'zhi#main2026!', '林小雅', '不定期', '启用', '知乎机构号'],
            ['知乎', 'IT转行经验谈', 'ZHI_ITCAREER02', 'zhi#career26!', '林小雅', '每周3次', '停用', '内容调整中'],
            ['抖音', 'Python进阶之路', 'DY_PYTHON03', 'dy#py2026!', '周子涵', '每周5次', '启用', 'Python垂直号，粉丝6.5万'],
            ['小红书', '数据分析师日记', 'XHS_DA003', 'xhs#data26!', '周子涵', '每周3次', '启用', '数据分析赛道'],
        ];
        $stmt = $pdo->prepare("INSERT INTO oa_channels (channel_name, account_name, account_id, password, owner, publish_freq, status, remark) VALUES (?,?,?,?,?,?,?,?)");
        foreach ($testData as $row) {
            $stmt->execute($row);
        }
    }

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT * FROM oa_channels ORDER BY channel_name, id");
        json_response(0, 'ok', $stmt->fetchAll());
    }

    if ($method === 'POST') {
        $d = request_body();
        require_fields($d, ['channel_name', 'account_name', 'account_id', 'owner']);
        $stmt = $pdo->prepare("INSERT INTO oa_channels (channel_name, account_name, account_id, password, owner, publish_freq, status, remark) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $d['channel_name'], $d['account_name'], $d['account_id'],
            $d['password'] ?? '', $d['owner'], $d['publish_freq'] ?? '每日',
            $d['status'] ?? '启用', $d['remark'] ?? ''
        ]);
        json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
    }

    if ($method === 'PUT') {
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $fields = ['channel_name', 'account_name', 'account_id', 'password', 'owner', 'publish_freq', 'status', 'remark'];
        $sets = []; $vals = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $d)) { $sets[] = "$f=?"; $vals[] = $d[$f]; }
        }
        if (empty($sets)) json_response(400, '无更新字段', null, 400);
        $vals[] = $id;
        $pdo->prepare("UPDATE oa_channels SET " . implode(',', $sets) . " WHERE id=?")->execute($vals);
        json_response(0, 'updated');
    }

    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $pdo->prepare("DELETE FROM oa_channels WHERE id=?")->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
