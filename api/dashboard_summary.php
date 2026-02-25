<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(405, '仅支持 GET', null, 405);
}

try {
    $pdo = get_db_connection();
    $summary = [
        'students' => (int)$pdo->query('SELECT COUNT(*) FROM oa_student')->fetchColumn(),
        'courses' => (int)$pdo->query('SELECT COUNT(*) FROM oa_course')->fetchColumn(),
        'orders' => (int)$pdo->query('SELECT COUNT(*) FROM oa_order')->fetchColumn(),
        'todos_open' => (int)$pdo->query("SELECT COUNT(*) FROM oa_todo WHERE status <> 'done'")->fetchColumn(),
        'notifications_unread' => (int)$pdo->query('SELECT COUNT(*) FROM oa_notification WHERE is_read = 0')->fetchColumn(),
    ];
    json_response(0, 'ok', $summary);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
