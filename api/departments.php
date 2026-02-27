<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/hr_bootstrap.php';

try {
    $pdo = get_db_connection();
    ensure_hr_schema($pdo);
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        $stmt = $pdo->query('SELECT id,dept_name,dept_code,parent_name,status,sort_no,remark FROM oa_department ORDER BY sort_no ASC,id ASC');
        json_response(0, 'ok', $stmt->fetchAll());
    }

    if ($m === 'POST' || $m === 'PUT') {
        $d = request_body();
        require_fields($d, ['dept_name', 'dept_code']);

        if ($m === 'POST') {
            $stmt = $pdo->prepare('INSERT INTO oa_department(dept_name,dept_code,parent_name,status,sort_no,remark) VALUES(?,?,?,?,?,?)');
            $stmt->execute([trim((string)$d['dept_name']), trim((string)$d['dept_code']), trim((string)($d['parent_name'] ?? '')), (int)($d['status'] ?? 1), (int)($d['sort_no'] ?? 99), trim((string)($d['remark'] ?? ''))]);
            json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
        }

        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $stmt = $pdo->prepare('UPDATE oa_department SET dept_name=?,dept_code=?,parent_name=?,status=?,sort_no=?,remark=? WHERE id=?');
        $stmt->execute([trim((string)$d['dept_name']), trim((string)$d['dept_code']), trim((string)($d['parent_name'] ?? '')), (int)($d['status'] ?? 1), (int)($d['sort_no'] ?? 99), trim((string)($d['remark'] ?? '')), $id]);
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $stmt = $pdo->prepare('DELETE FROM oa_department WHERE id=?');
        $stmt->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
