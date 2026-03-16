<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/business_bootstrap.php';

function delivery_scope(): array
{
    $role = auth_user_role();
    $uid = auth_user_id();
    $uname = auth_user_name();

    if (auth_is_admin_like() || $role === '班主任') {
        return ['sql' => '1=1', 'params' => []];
    }
    if ($role === '教练') {
        return ['sql' => '(l.coach_user_id = :uid OR s.coach_user_id = :uid OR s.delivery_coach = :uname)', 'params' => [':uid' => $uid, ':uname' => $uname]];
    }
    if ($role === '顾问') {
        return ['sql' => '(s.owner_consultant_user_id = :uid OR s.consultant = :uname)', 'params' => [':uid' => $uid, ':uname' => $uname]];
    }
    return ['sql' => '1=0', 'params' => []];
}

function delivery_log_accessible(PDO $pdo, int $id): bool
{
    $scope = delivery_scope();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM oa_delivery_log l LEFT JOIN oa_student s ON s.id=l.student_id WHERE l.id=:id AND (' . $scope['sql'] . ')');
    $stmt->execute(array_merge([':id' => $id], $scope['params']));
    return (int)$stmt->fetchColumn() > 0;
}

try {
    $pdo = get_db_connection();
    ensure_business_workflow_schema($pdo);
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        $scope = delivery_scope();
        $sql = 'SELECT l.id,l.student_id,l.coach_user_id,l.progress_stage,l.content,l.log_date,l.created_at,s.name student_name,s.delivery_coach,u.real_name coach_name FROM oa_delivery_log l LEFT JOIN oa_student s ON s.id=l.student_id LEFT JOIN oa_user u ON u.id=l.coach_user_id WHERE ' . $scope['sql'] . ' ORDER BY l.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($scope['params']);
        json_response(0, 'ok', $stmt->fetchAll());
    }

    if ($m === 'POST' || $m === 'PUT') {
        auth_require_roles(['班主任', '教练']);

        $d = request_body();
        require_fields($d, ['student_id', 'content', 'log_date']);
        $studentId = (int)$d['student_id'];
        if ($studentId <= 0) json_response(400, 'student_id非法', null, 400);

        $status = trim((string)($d['progress_stage'] ?? ''));
        if ($status !== '' && !in_array($status, ['not_started', 'learning', 'paused', 'completed'], true)) {
            json_response(400, 'progress_stage 非法', null, 400);
        }

        $coachUserId = (int)($d['coach_user_id'] ?? 0) ?: null;
        if (auth_user_role() === '教练') {
            $coachUserId = auth_user_id();
        }

        if ($m === 'POST') {
            $stmt = $pdo->prepare('INSERT INTO oa_delivery_log(student_id,coach_user_id,progress_stage,content,log_date) VALUES(?,?,?,?,?)');
            $stmt->execute([$studentId, $coachUserId, $status, trim((string)$d['content']), normalize_date_or_empty($d['log_date'])]);
            json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
        }

        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        if (!delivery_log_accessible($pdo, $id)) {
            json_response(403, '无权修改该交付日志', null, 403);
        }
        $stmt = $pdo->prepare('UPDATE oa_delivery_log SET student_id=?,coach_user_id=?,progress_stage=?,content=?,log_date=? WHERE id=?');
        $stmt->execute([$studentId, $coachUserId, $status, trim((string)$d['content']), normalize_date_or_empty($d['log_date']), $id]);
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        auth_require_roles(['班主任']);
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        if (!delivery_log_accessible($pdo, $id)) {
            json_response(403, '无权删除该交付日志', null, 403);
        }
        $stmt = $pdo->prepare('DELETE FROM oa_delivery_log WHERE id=?');
        $stmt->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
