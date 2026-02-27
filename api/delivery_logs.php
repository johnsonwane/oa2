<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/business_bootstrap.php';

try {
    $pdo = get_db_connection();
    ensure_business_workflow_schema($pdo);
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        $where = [];
        $params = [];
        $userId = (int)($_GET['user_id'] ?? 0);

        if ($userId > 0) {
            $uStmt = $pdo->prepare('SELECT role, real_name FROM oa_user WHERE id=? LIMIT 1');
            $uStmt->execute([$userId]);
            $u = $uStmt->fetch();
            if ($u && trim((string)$u['role']) === '教练') {
                $where[] = 's.delivery_coach = :coach_name';
                $params[':coach_name'] = trim((string)$u['real_name']);
            }
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';
        $sql = 'SELECT l.id,l.student_id,l.coach_user_id,l.progress_stage,l.content,l.log_date,l.created_at,s.name student_name,s.delivery_coach,u.real_name coach_name FROM oa_delivery_log l LEFT JOIN oa_student s ON s.id=l.student_id LEFT JOIN oa_user u ON u.id=l.coach_user_id' . $whereSql . ' ORDER BY l.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response(0, 'ok', $stmt->fetchAll());
    }

    if ($m === 'POST' || $m === 'PUT') {
        $d = request_body();
        require_fields($d, ['student_id', 'content', 'log_date']);
        $studentId = (int)$d['student_id'];
        if ($studentId <= 0) json_response(400, 'student_id非法', null, 400);

        if ($m === 'POST') {
            $stmt = $pdo->prepare('INSERT INTO oa_delivery_log(student_id,coach_user_id,progress_stage,content,log_date) VALUES(?,?,?,?,?)');
            $stmt->execute([$studentId, (int)($d['coach_user_id'] ?? 0) ?: null, trim((string)($d['progress_stage'] ?? '')), trim((string)$d['content']), normalize_date_or_empty($d['log_date'])]);
            json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
        }

        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $stmt = $pdo->prepare('UPDATE oa_delivery_log SET student_id=?,coach_user_id=?,progress_stage=?,content=?,log_date=? WHERE id=?');
        $stmt->execute([$studentId, (int)($d['coach_user_id'] ?? 0) ?: null, trim((string)($d['progress_stage'] ?? '')), trim((string)$d['content']), normalize_date_or_empty($d['log_date']), $id]);
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $stmt = $pdo->prepare('DELETE FROM oa_delivery_log WHERE id=?');
        $stmt->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
