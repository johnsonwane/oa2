<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/rbac_bootstrap.php';

try {
    $pdo = get_db_connection();
    ensure_rbac_tables($pdo);
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        $stmt = $pdo->query('SELECT id, group_name, group_code, remark, status FROM oa_user_group ORDER BY id ASC');
        json_response(0, 'ok', $stmt->fetchAll());
    }

    if ($m === 'POST') {
        $d = request_body();
        require_fields($d, ['group_name', 'group_code']);
        $stmt = $pdo->prepare('INSERT INTO oa_user_group(group_name, group_code, remark, status) VALUES(?,?,?,?)');
        $stmt->execute([trim($d['group_name']), trim($d['group_code']), trim((string)($d['remark'] ?? '')), (int)($d['status'] ?? 1)]);
        json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
    }

    if ($m === 'PUT') {
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        require_fields($d, ['group_name', 'group_code']);
        $stmt = $pdo->prepare('UPDATE oa_user_group SET group_name=?, group_code=?, remark=?, status=? WHERE id=?');
        $stmt->execute([trim($d['group_name']), trim($d['group_code']), trim((string)($d['remark'] ?? '')), (int)($d['status'] ?? 1), $id]);
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $pdo->prepare('DELETE FROM oa_user_group_rel WHERE group_id=?')->execute([$id]);
        $pdo->prepare('DELETE FROM oa_group_permission_rel WHERE group_id=?')->execute([$id]);
        $pdo->prepare('DELETE FROM oa_user_group WHERE id=?')->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
