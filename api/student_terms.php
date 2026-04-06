<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/sprint123_bootstrap.php';
require_once __DIR__ . '/sprint123_crud.php';

function student_term_status_allowed(?string $old, string $new): bool
{
    $allowed = [
        'learning' => ['learning', 'paused', 'completed', 'dropped'],
        'paused' => ['paused', 'learning', 'dropped'],
        'completed' => ['completed'],
        'dropped' => ['dropped'],
    ];
    if ($old === null) return in_array($new, ['learning', 'paused'], true);
    return in_array($new, $allowed[$old] ?? [], true);
}

try {
    $pdo = get_db_connection();
    ensure_sprint123_tables($pdo);
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        auth_require_roles(['班主任', '教练', '顾问', '财务', '部门经理']);
        $items = crud_list($pdo, 'oa_student_term_rel');
        // 补充分成快照信息
        foreach ($items as &$item) {
            if (!empty($item['commission_info'])) {
                $item['commission_info'] = json_decode($item['commission_info'], true);
            }
        }
        json_response(0, 'ok', $items);
    }

    if ($m === 'POST') {
        auth_require_roles(['班主任']);
        $d = request_body();
        require_fields($d, ['student_id', 'term_id']);

        $studentId = (int)$d['student_id'];
        $termId = (int)$d['term_id'];
        if ($studentId <= 0 || $termId <= 0) json_response(400, 'student_id/term_id 非法', null, 400);

        $s = $pdo->prepare('SELECT COUNT(*) FROM oa_student WHERE id=?');
        $s->execute([$studentId]);
        if ((int)$s->fetchColumn() <= 0) json_response(400, '学员不存在', null, 400);

        $t = $pdo->prepare('SELECT status FROM oa_class_term WHERE id=?');
        $t->execute([$termId]);
        $term = $t->fetch();
        if (!$term) json_response(400, '班期不存在', null, 400);
        if (in_array((string)$term['status'], ['finished', 'cancelled'], true)) {
            json_response(400, '班期已结束或取消，不能入班', null, 400);
        }

        $status = trim((string)($d['status'] ?? 'learning'));
        if (!student_term_status_allowed(null, $status)) json_response(400, '学员班期状态非法', null, 400);

        $payload = $d;
        $payload['status'] = $status;
        if (!isset($payload['joined_at']) || trim((string)$payload['joined_at']) === '') {
            $payload['joined_at'] = date('Y-m-d H:i:s');
        }
        $id = crud_insert($pdo, 'oa_student_term_rel', ['student_id', 'term_id', 'joined_at', 'status'], $payload);
        json_response(0, 'created', ['id' => $id]);
    }

    if ($m === 'PUT') {
        auth_require_roles(['班主任']);
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);

        $oldStmt = $pdo->prepare('SELECT status FROM oa_student_term_rel WHERE id=?');
        $oldStmt->execute([$id]);
        $old = $oldStmt->fetch();
        if (!$old) json_response(404, '记录不存在', null, 404);

        $status = trim((string)($d['status'] ?? $old['status']));
        if (!student_term_status_allowed((string)$old['status'], $status)) json_response(400, '学员班期状态流转非法', null, 400);

        $payload = $d;
        $payload['status'] = $status;
        crud_update($pdo, 'oa_student_term_rel', $id, ['student_id', 'term_id', 'joined_at', 'status'], $payload);

        // 班期销课（completed）时，触发分成计算
        $commissionResult = null;
        if ($status === 'completed') {
            require_once __DIR__ . '/commission_engine.php';
            $commissionResult = calc_commission_for_term_rel($pdo, $id);
        }

        $response = ['id' => $id, 'status' => $status];
        if ($commissionResult !== null) {
            $response['commission'] = $commissionResult;
        }
        json_response(0, 'updated', $response);
    }

    if ($m === 'DELETE') {
        auth_require_roles(['班主任']);
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        crud_delete($pdo, 'oa_student_term_rel', $id);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
