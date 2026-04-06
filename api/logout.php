<?php
require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, '仅支持 POST 请求', null, 405);
}

try {
    $token = auth_token();
    if ($token !== '') {
        $hash = hash('sha256', $token);
        $pdo = get_db_connection();
        $pdo->prepare('UPDATE oa_session SET revoked_at = NOW() WHERE token_hash = ? AND revoked_at IS NULL')->execute([$hash]);
    }
    json_response(0, '已退出登录');
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
