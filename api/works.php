<?php
/**
 * works.php — 作品管理 API
 * 管理引流用的短视频/图文/直播作品
 */
require_once __DIR__ . '/db.php';

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = get_db_connection();

    // 建表（首次访问自动创建）
    $pdo->exec("CREATE TABLE IF NOT EXISTS oa_works (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL COMMENT '作品标题',
        work_type ENUM('短视频','图文','直播') NOT NULL DEFAULT '短视频' COMMENT '作品类型',
        channel ENUM('抖音','小红书','视频号') NOT NULL DEFAULT '抖音' COMMENT '发布渠道',
        publish_time DATETIME DEFAULT NULL COMMENT '发布时间',
        play_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '播放量',
        like_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞量',
        collect_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '收藏量',
        lead_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '引流人数(留资数)',
        remark VARCHAR(500) DEFAULT '' COMMENT '备注',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 插入测试数据
    $countStmt = $pdo->query("SELECT COUNT(*) FROM oa_works");
    if ((int)$countStmt->fetchColumn() === 0) {
        $testData = [
            ['Python零基础入门到实战完整课程攻略', '短视频', '抖音', '2026-03-08 10:00:00', 128500, 8920, 2340, 156, ''],
            ['新媒体运营必备的10个免费工具推荐', '图文', '小红书', '2026-03-09 14:30:00', 45200, 5630, 1890, 89, '小红书爆款笔记'],
            ['2026年最值得学的3门编程语言', '短视频', '视频号', '2026-03-10 09:00:00', 67300, 4210, 1120, 67, ''],
            ['直播答疑：转行学IT到底靠不靠谱', '直播', '抖音', '2026-03-11 20:00:00', 234000, 15600, 4560, 312, '当晚同时在线峰值1.2万人'],
            ['设计师转前端开发的真实经历分享', '图文', '小红书', '2026-03-12 11:00:00', 32800, 3450, 980, 45, '用户反馈积极'],
            ['ChatGPT辅助编程实战教程', '短视频', '抖音', '2026-03-13 16:00:00', 189000, 12300, 3780, 245, '播放量创新高'],
            ['数据分析Python库Pandas入门教程', '短视频', '视频号', '2026-03-14 10:30:00', 51600, 3890, 1020, 53, ''],
            ['Excel高级技巧20招合集', '图文', '小红书', '2026-03-15 13:00:00', 28900, 2670, 760, 38, '职场干货类'],
            ['Vue3+TypeScript项目实战从零搭建', '短视频', '抖音', '2026-03-16 18:00:00', 95400, 6780, 2100, 134, '技术向内容'],
            ['零基础转行数据分析月薪过万经验谈', '直播', '视频号', '2026-03-17 21:00:00', 156000, 9870, 2890, 198, '同时在线8000人'],
            ['UI设计接单全流程详解', '图文', '小红书', '2026-03-18 09:30:00', 21300, 1980, 540, 28, '设计师群体反响好'],
            ['React Native跨平台开发入门到进阶', '短视频', '抖音', '2026-03-19 15:00:00', 78600, 5430, 1560, 98, ''],
            ['Figma设计系统搭建实战指南', '短视频', '视频号', '2026-03-20 11:00:00', 43200, 3120, 890, 51, ''],
            ['短视频剪辑Premiere Pro速成课', '短视频', '抖音', '2026-03-21 14:00:00', 167000, 11200, 3240, 223, '剪辑教程类爆款'],
            ['互联网大厂面试算法题精讲', '直播', '抖音', '2026-03-22 20:30:00', 298000, 18700, 5100, 387, '求职季内容效果好'],
        ];
        $stmt = $pdo->prepare("INSERT INTO oa_works (title, work_type, channel, publish_time, play_count, like_count, collect_count, lead_count, remark) VALUES (?,?,?,?,?,?,?,?,?)");
        foreach ($testData as $row) {
            $stmt->execute($row);
        }
    }

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $sql = "SELECT * FROM oa_works ORDER BY publish_time DESC";
        $stmt = $pdo->query($sql);
        json_response(0, 'ok', $stmt->fetchAll());
    }

    if ($method === 'POST') {
        $d = request_body();
        require_fields($d, ['title']);
        $stmt = $pdo->prepare("INSERT INTO oa_works (title, work_type, channel, publish_time, play_count, like_count, collect_count, lead_count, remark) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $d['title'],
            $d['work_type'] ?? '短视频',
            $d['channel'] ?? '抖音',
            !empty($d['publish_time']) ? $d['publish_time'] : null,
            (int)($d['play_count'] ?? 0),
            (int)($d['like_count'] ?? 0),
            (int)($d['collect_count'] ?? 0),
            (int)($d['lead_count'] ?? 0),
            $d['remark'] ?? ''
        ]);
        json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
    }

    if ($method === 'PUT') {
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $fields = ['title', 'work_type', 'channel', 'publish_time', 'play_count', 'like_count', 'collect_count', 'lead_count', 'remark'];
        $sets = []; $vals = [];
        foreach ($fields as $f) {
            if (array_key_exists($f, $d)) { $sets[] = "$f=?"; $vals[] = $d[$f]; }
        }
        if (empty($sets)) json_response(400, '无更新字段', null, 400);
        $vals[] = $id;
        $pdo->prepare("UPDATE oa_works SET " . implode(',', $sets) . " WHERE id=?")->execute($vals);
        json_response(0, 'updated');
    }

    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $pdo->prepare("DELETE FROM oa_works WHERE id=?")->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
