<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/db.php';

function json_response(int $code, string $message, $data = null): void
{
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

try {
    $pdo = get_db_connection();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $stmt = $pdo->query('SELECT id, menu_name, menu_key, path, icon, sort_no, status, created_at, updated_at FROM oa_menu ORDER BY sort_no ASC, id ASC');
        json_response(0, '查询成功', $stmt->fetchAll());
    }

    if ($method === 'POST') {
        $data = request_body();
        $menuName = trim((string)($data['menu_name'] ?? ''));
        $menuKey = trim((string)($data['menu_key'] ?? ''));
        $path = trim((string)($data['path'] ?? ''));
        $icon = trim((string)($data['icon'] ?? ''));
        $sortNo = (int)($data['sort_no'] ?? 0);
        $status = (int)($data['status'] ?? 1);

        if ($menuName === '' || $menuKey === '' || $path === '') {
            http_response_code(400);
            json_response(400, 'menu_name、menu_key、path 不能为空');
        }

        $stmt = $pdo->prepare('INSERT INTO oa_menu (menu_name, menu_key, path, icon, sort_no, status) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$menuName, $menuKey, $path, $icon, $sortNo, $status]);

        json_response(0, '新增成功', ['id' => (int)$pdo->lastInsertId()]);
    }

    if ($method === 'PUT') {
        $data = request_body();
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            json_response(400, 'id 非法');
        }

        $menuName = trim((string)($data['menu_name'] ?? ''));
        $menuKey = trim((string)($data['menu_key'] ?? ''));
        $path = trim((string)($data['path'] ?? ''));
        $icon = trim((string)($data['icon'] ?? ''));
        $sortNo = (int)($data['sort_no'] ?? 0);
        $status = (int)($data['status'] ?? 1);

        if ($menuName === '' || $menuKey === '' || $path === '') {
            http_response_code(400);
            json_response(400, 'menu_name、menu_key、path 不能为空');
        }

        $stmt = $pdo->prepare('UPDATE oa_menu SET menu_name=?, menu_key=?, path=?, icon=?, sort_no=?, status=? WHERE id=?');
        $stmt->execute([$menuName, $menuKey, $path, $icon, $sortNo, $status, $id]);

        json_response(0, '更新成功');
    }

    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            json_response(400, 'id 非法');
        }

        $stmt = $pdo->prepare('DELETE FROM oa_menu WHERE id = ?');
        $stmt->execute([$id]);

        json_response(0, '删除成功');
    }

    http_response_code(405);
    json_response(405, '不支持的请求方法');
} catch (Throwable $e) {
    http_response_code(500);
    json_response(500, '服务异常：' . $e->getMessage());
}
