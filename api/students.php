<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/profile_bootstrap.php';

try {
    $pdo = get_db_connection();
    ensure_student_user_profile_columns($pdo);
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        $stmt = $pdo->query('SELECT id, name, gender, birthday, phone, wechat, id_no, level, intention_level, follow_status, source, enrolled_courses, consultant, delivery_coach, guardian_name, guardian_phone, address, remark, created_at FROM oa_student ORDER BY id DESC');
        json_response(0, 'ok', $stmt->fetchAll());
    }

    if ($m === 'POST') {
        $d = request_body();
        require_fields($d, ['name', 'phone']);
        $stmt = $pdo->prepare('INSERT INTO oa_student(name, gender, birthday, phone, wechat, id_no, level, intention_level, follow_status, source, enrolled_courses, consultant, delivery_coach, guardian_name, guardian_phone, address, remark) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            trim($d['name']),
            trim((string)($d['gender'] ?? '')),
            trim((string)($d['birthday'] ?? '')),
            trim($d['phone']),
            trim((string)($d['wechat'] ?? '')),
            trim((string)($d['id_no'] ?? '')),
            trim((string)($d['level'] ?? '')),
            trim((string)($d['intention_level'] ?? '')),
            trim((string)($d['follow_status'] ?? '')),
            trim((string)($d['source'] ?? '')),
            json_encode($d['enrolled_courses'] ?? [], JSON_UNESCAPED_UNICODE),
            trim((string)($d['consultant'] ?? '')),
            trim((string)($d['delivery_coach'] ?? '')),
            trim((string)($d['guardian_name'] ?? '')),
            trim((string)($d['guardian_phone'] ?? '')),
            trim((string)($d['address'] ?? '')),
            trim((string)($d['remark'] ?? '')),
        ]);
        json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
    }

    if ($m === 'PUT') {
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        require_fields($d, ['name', 'phone']);
        $stmt = $pdo->prepare('UPDATE oa_student SET name=?, gender=?, birthday=?, phone=?, wechat=?, id_no=?, level=?, intention_level=?, follow_status=?, source=?, enrolled_courses=?, consultant=?, delivery_coach=?, guardian_name=?, guardian_phone=?, address=?, remark=? WHERE id=?');
        $stmt->execute([
            trim($d['name']),
            trim((string)($d['gender'] ?? '')),
            trim((string)($d['birthday'] ?? '')),
            trim($d['phone']),
            trim((string)($d['wechat'] ?? '')),
            trim((string)($d['id_no'] ?? '')),
            trim((string)($d['level'] ?? '')),
            trim((string)($d['intention_level'] ?? '')),
            trim((string)($d['follow_status'] ?? '')),
            trim((string)($d['source'] ?? '')),
            json_encode($d['enrolled_courses'] ?? [], JSON_UNESCAPED_UNICODE),
            trim((string)($d['consultant'] ?? '')),
            trim((string)($d['delivery_coach'] ?? '')),
            trim((string)($d['guardian_name'] ?? '')),
            trim((string)($d['guardian_phone'] ?? '')),
            trim((string)($d['address'] ?? '')),
            trim((string)($d['remark'] ?? '')),
            $id
        ]);
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $stmt = $pdo->prepare('DELETE FROM oa_student WHERE id=?');
        $stmt->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
