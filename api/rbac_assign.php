<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/rbac_bootstrap.php';

try {
    $pdo = get_db_connection();
    ensure_rbac_tables($pdo);
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        $userGroups = $pdo->query('SELECT r.id, r.user_id, u.username, r.group_id, g.group_name FROM oa_user_group_rel r JOIN oa_user u ON u.id=r.user_id JOIN oa_user_group g ON g.id=r.group_id ORDER BY r.id DESC')->fetchAll();
        $groupPerms = $pdo->query('SELECT r.id, r.group_id, g.group_name, r.perm_id, p.perm_name FROM oa_group_permission_rel r JOIN oa_user_group g ON g.id=r.group_id JOIN oa_permission p ON p.id=r.perm_id ORDER BY r.id DESC')->fetchAll();
        json_response(0, 'ok', ['user_groups' => $userGroups, 'group_perms' => $groupPerms]);
    }

    if ($m === 'POST') {
        $d = request_body();
        $type = trim((string)($d['type'] ?? ''));
        if ($type === 'user_group') {
            $stmt = $pdo->prepare('INSERT IGNORE INTO oa_user_group_rel(user_id, group_id) VALUES(?,?)');
            $stmt->execute([(int)($d['user_id'] ?? 0), (int)($d['group_id'] ?? 0)]);
            json_response(0, 'assigned');
        }
        if ($type === 'group_perm') {
            $stmt = $pdo->prepare('INSERT IGNORE INTO oa_group_permission_rel(group_id, perm_id) VALUES(?,?)');
            $stmt->execute([(int)($d['group_id'] ?? 0), (int)($d['perm_id'] ?? 0)]);
            json_response(0, 'assigned');
        }
        json_response(400, 'type非法', null, 400);
    }

    if ($m === 'DELETE') {
        $type = trim((string)($_GET['type'] ?? ''));
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        if ($type === 'user_group') {
            $pdo->prepare('DELETE FROM oa_user_group_rel WHERE id=?')->execute([$id]);
            json_response(0, 'deleted');
        }
        if ($type === 'group_perm') {
            $pdo->prepare('DELETE FROM oa_group_permission_rel WHERE id=?')->execute([$id]);
            json_response(0, 'deleted');
        }
        json_response(400, 'type非法', null, 400);
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
