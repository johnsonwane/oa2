<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/profile_bootstrap.php';

try {
    $pdo = get_db_connection();
    ensure_student_user_profile_columns($pdo);
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        $stmt = $pdo->query('SELECT id, username, real_name, role, gender, mobile, email, id_no, department, position, hire_date, last_login_at, remark, status, created_at FROM oa_user ORDER BY id ASC');
        json_response(0, 'ok', $stmt->fetchAll());
    }

    if ($m === 'POST') {
        $d = request_body();
        require_fields($d, ['username', 'real_name', 'role', 'password']);
        $stmt = $pdo->prepare('INSERT INTO oa_user(username,password_hash,real_name,role,gender,mobile,email,id_no,department,position,hire_date,remark,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            trim($d['username']),
            password_hash((string)$d['password'], PASSWORD_BCRYPT),
            trim($d['real_name']),
            trim($d['role']),
            trim((string)($d['gender'] ?? '')),
            trim((string)($d['mobile'] ?? '')),
            trim((string)($d['email'] ?? '')),
            trim((string)($d['id_no'] ?? '')),
            trim((string)($d['department'] ?? '')),
            trim((string)($d['position'] ?? '')),
            trim((string)($d['hire_date'] ?? '')),
            trim((string)($d['remark'] ?? '')),
            (int)($d['status'] ?? 1)
        ]);
        json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
    }

    if ($m === 'PUT') {
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        require_fields($d, ['real_name', 'role']);
        $stmt = $pdo->prepare('UPDATE oa_user SET real_name=?, role=?, gender=?, mobile=?, email=?, id_no=?, department=?, position=?, hire_date=?, remark=?, status=? WHERE id=?');
        $stmt->execute([
            trim($d['real_name']),
            trim($d['role']),
            trim((string)($d['gender'] ?? '')),
            trim((string)($d['mobile'] ?? '')),
            trim((string)($d['email'] ?? '')),
            trim((string)($d['id_no'] ?? '')),
            trim((string)($d['department'] ?? '')),
            trim((string)($d['position'] ?? '')),
            trim((string)($d['hire_date'] ?? '')),
            trim((string)($d['remark'] ?? '')),
            (int)($d['status'] ?? 1),
            $id
        ]);
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $stmt = $pdo->prepare('DELETE FROM oa_user WHERE id=?');
        $stmt->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
