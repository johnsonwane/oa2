<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/sprint123_bootstrap.php';
require_once __DIR__ . '/sprint123_crud.php';

function term_status_allowed(?string $old, string $new): bool
{
    $allowed = [
        'planning' => ['planning', 'enrolling', 'running', 'cancelled'],
        'enrolling' => ['enrolling', 'running', 'cancelled'],
        'running' => ['running', 'finished', 'cancelled'],
        'finished' => ['finished'],
        'cancelled' => ['cancelled'],
    ];
    if ($old === null) return in_array($new, ['planning', 'enrolling', 'running'], true);
    return in_array($new, $allowed[$old] ?? [], true);
}

try {
    $pdo = get_db_connection();
    ensure_sprint123_tables($pdo);
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        auth_require_roles(['班主任', '教练', '顾问', '财务', '部门经理']);
        json_response(0, 'ok', crud_list($pdo, 'oa_class_term'));
    }

    if ($m === 'POST') {
        auth_require_roles(['班主任']);
        $d = request_body();
        require_fields($d, ['course_id', 'term_name']);

        $courseId = (int)$d['course_id'];
        if ($courseId <= 0) json_response(400, 'course_id非法', null, 400);
        $exists = $pdo->prepare('SELECT COUNT(*) FROM oa_course WHERE id=?');
        $exists->execute([$courseId]);
        if ((int)$exists->fetchColumn() <= 0) json_response(400, '课程不存在', null, 400);

        $status = trim((string)($d['status'] ?? 'planning'));
        if (!term_status_allowed(null, $status)) json_response(400, '班期状态非法', null, 400);

        $id = crud_insert($pdo, 'oa_class_term', ['course_id', 'term_name', 'start_date', 'end_date', 'headteacher_user_id', 'status'], array_merge($d, ['status' => $status]));
        json_response(0, 'created', ['id' => $id]);
    }

    if ($m === 'PUT') {
        auth_require_roles(['班主任']);
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);

        $oldStmt = $pdo->prepare('SELECT status FROM oa_class_term WHERE id=? LIMIT 1');
        $oldStmt->execute([$id]);
        $old = $oldStmt->fetch();
        if (!$old) json_response(404, '班期不存在', null, 404);

        $status = trim((string)($d['status'] ?? $old['status']));
        if (!term_status_allowed((string)$old['status'], $status)) json_response(400, '班期状态流转非法', null, 400);

        $payload = $d;
        $payload['status'] = $status;
        crud_update($pdo, 'oa_class_term', $id, ['course_id', 'term_name', 'start_date', 'end_date', 'headteacher_user_id', 'status'], $payload);
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        auth_require_roles(['班主任']);
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);

        $cnt = $pdo->prepare('SELECT COUNT(*) FROM oa_student_term_rel WHERE term_id=?');
        $cnt->execute([$id]);
        if ((int)$cnt->fetchColumn() > 0) {
            json_response(400, '班期已绑定学员，禁止删除', null, 400);
        }
        crud_delete($pdo, 'oa_class_term', $id);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
