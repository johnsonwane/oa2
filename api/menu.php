<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';

try {
    $pdo = get_db_connection();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $stmt = $pdo->query('SELECT id, menu_name, menu_key, path, icon, sort_no, status, parent_name FROM oa_menu ORDER BY sort_no ASC, id ASC');
        json_response(0, '查询成功', $stmt->fetchAll());
    }

    if ($method === 'POST') {
        $data = request_body();
        require_fields($data, ['menu_name', 'menu_key', 'path']);
        $stmt = $pdo->prepare('INSERT INTO oa_menu (menu_name, menu_key, path, icon, sort_no, status, parent_name) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            trim((string)$data['menu_name']),
            trim((string)$data['menu_key']),
            trim((string)$data['path']),
            trim((string)($data['icon'] ?? '')),
            (int)($data['sort_no'] ?? 0),
            (int)($data['status'] ?? 1),
            trim((string)($data['parent_name'] ?? '')),
        ]);
        json_response(0, '新增成功', ['id' => (int)$pdo->lastInsertId()]);
    }

    if ($method === 'PUT') {
        $data = request_body();
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id 非法', null, 400);
        require_fields($data, ['menu_name', 'menu_key', 'path']);
        $stmt = $pdo->prepare('UPDATE oa_menu SET menu_name=?, menu_key=?, path=?, icon=?, sort_no=?, status=?, parent_name=? WHERE id=?');
        $stmt->execute([
            trim((string)$data['menu_name']),
            trim((string)$data['menu_key']),
            trim((string)$data['path']),
            trim((string)($data['icon'] ?? '')),
            (int)($data['sort_no'] ?? 0),
            (int)($data['status'] ?? 1),
            trim((string)($data['parent_name'] ?? '')),
            $id,
        ]);
        json_response(0, '更新成功');
    }

    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id 非法', null, 400);
        $stmt = $pdo->prepare('DELETE FROM oa_menu WHERE id = ?');
        $stmt->execute([$id]);
        json_response(0, '删除成功');
    }

    json_response(405, '不支持的请求方法', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
