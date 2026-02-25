<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';

try {
    $pdo = get_db_connection();
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        $stmt = $pdo->query('SELECT id, perm_name, perm_code, module_name, remark, status FROM oa_permission ORDER BY id ASC');
        json_response(0, 'ok', $stmt->fetchAll());
    }

    if ($m === 'POST') {
        $d = request_body();
        require_fields($d, ['perm_name', 'perm_code']);
        $stmt = $pdo->prepare('INSERT INTO oa_permission(perm_name, perm_code, module_name, remark, status) VALUES(?,?,?,?,?)');
        $stmt->execute([trim($d['perm_name']), trim($d['perm_code']), trim((string)($d['module_name'] ?? '系统管理')), trim((string)($d['remark'] ?? '')), (int)($d['status'] ?? 1)]);
        json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
    }

    if ($m === 'PUT') {
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        require_fields($d, ['perm_name', 'perm_code']);
        $stmt = $pdo->prepare('UPDATE oa_permission SET perm_name=?, perm_code=?, module_name=?, remark=?, status=? WHERE id=?');
        $stmt->execute([trim($d['perm_name']), trim($d['perm_code']), trim((string)($d['module_name'] ?? '系统管理')), trim((string)($d['remark'] ?? '')), (int)($d['status'] ?? 1), $id]);
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $pdo->prepare('DELETE FROM oa_group_permission_rel WHERE perm_id=?')->execute([$id]);
        $pdo->prepare('DELETE FROM oa_permission WHERE id=?')->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
