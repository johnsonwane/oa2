<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';

try {
    $pdo = get_db_connection();
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        // 所有已登录用户都可查看课程列表
        $stmt = $pdo->query('SELECT id, course_name, coach_name, period_weeks, price, status FROM oa_course ORDER BY id DESC');
        json_response(0, 'ok', $stmt->fetchAll());
    }

    if ($m === 'POST') {
        // 新增课程：班主任、财务、超管可操作（顾问和教练只读）
        auth_require_roles(['班主任', '财务']);
        $d = request_body();
        require_fields($d, ['course_name', 'coach_name']);
        $stmt = $pdo->prepare('INSERT INTO oa_course(course_name, coach_name, period_weeks, price, status) VALUES(?,?,?,?,?)');
        $stmt->execute([
            trim($d['course_name']),
            trim($d['coach_name']),
            (int)($d['period_weeks'] ?? 0),
            (float)($d['price'] ?? 0),
            (int)($d['status'] ?? 1)
        ]);
        json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
    }

    if ($m === 'PUT') {
        // 修改课程：班主任改内容，财务改价格，超管全改
        auth_require_roles(['班主任', '财务']);
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        require_fields($d, ['course_name', 'coach_name']);

        $role = auth_user_role();

        if ($role === '财务' && !auth_is_admin_like()) {
            // 财务只能修改价格
            $stmt = $pdo->prepare('UPDATE oa_course SET price=?, status=? WHERE id=?');
            $stmt->execute([(float)($d['price'] ?? 0), (int)($d['status'] ?? 1), $id]);
        } else {
            // 班主任及以上可修改全部
            $stmt = $pdo->prepare('UPDATE oa_course SET course_name=?, coach_name=?, period_weeks=?, price=?, status=? WHERE id=?');
            $stmt->execute([
                trim($d['course_name']),
                trim($d['coach_name']),
                (int)($d['period_weeks'] ?? 0),
                (float)($d['price'] ?? 0),
                (int)($d['status'] ?? 1),
                $id
            ]);
        }
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        // 删除课程：仅超管可操作
        if (!auth_is_admin_like()) {
            json_response(403, '仅超级管理员可删除课程', null, 403);
        }
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);

        // 检查是否有关联订单
        $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM oa_order WHERE course_id=? LIMIT 1');
        $checkStmt->execute([$id]);
        if ((int)$checkStmt->fetchColumn() > 0) {
            json_response(409, '该课程已有关联订单，无法删除', null, 409);
        }

        $stmt = $pdo->prepare('DELETE FROM oa_course WHERE id=?');
        $stmt->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
