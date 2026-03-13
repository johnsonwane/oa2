<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/sprint123_bootstrap.php';
require_once __DIR__ . '/sprint123_crud.php';

function shipment_status_allowed(?string $old, string $new): bool
{
    $allowed = [
        'pending' => ['pending', 'shipped', 'cancelled'],
        'shipped' => ['shipped', 'delivered', 'returned'],
        'delivered' => ['delivered'],
        'returned' => ['returned'],
        'cancelled' => ['cancelled'],
    ];
    if ($old === null) return in_array($new, ['pending', 'shipped'], true);
    return in_array($new, $allowed[$old] ?? [], true);
}

try {
    $pdo = get_db_connection();
    ensure_sprint123_tables($pdo);
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        auth_require_roles(['班主任', '教练', '顾问', '财务', '部门经理']);
        json_response(0, 'ok', crud_list($pdo, 'oa_shipment'));
    }

    if ($m === 'POST') {
        auth_require_roles(['班主任']);
        $d = request_body();
        require_fields($d, ['student_id', 'receiver_name', 'receiver_address']);

        $studentId = (int)$d['student_id'];
        if ($studentId <= 0) json_response(400, 'student_id非法', null, 400);
        $s = $pdo->prepare('SELECT COUNT(*) FROM oa_student WHERE id=?');
        $s->execute([$studentId]);
        if ((int)$s->fetchColumn() <= 0) json_response(400, '学员不存在', null, 400);

        $status = trim((string)($d['status'] ?? 'pending'));
        if (!shipment_status_allowed(null, $status)) json_response(400, '寄送状态非法', null, 400);

        $payload = $d;
        $payload['status'] = $status;
        $id = crud_insert($pdo, 'oa_shipment', ['student_id', 'order_id', 'receiver_name', 'receiver_mobile', 'receiver_address', 'courier_company', 'tracking_no', 'shipped_at', 'delivered_at', 'status', 'remark'], $payload);
        json_response(0, 'created', ['id' => $id]);
    }

    if ($m === 'PUT') {
        auth_require_roles(['班主任']);
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);

        $oldStmt = $pdo->prepare('SELECT status FROM oa_shipment WHERE id=?');
        $oldStmt->execute([$id]);
        $old = $oldStmt->fetch();
        if (!$old) json_response(404, '寄送记录不存在', null, 404);

        $status = trim((string)($d['status'] ?? $old['status']));
        if (!shipment_status_allowed((string)$old['status'], $status)) json_response(400, '寄送状态流转非法', null, 400);

        $payload = $d;
        $payload['status'] = $status;
        crud_update($pdo, 'oa_shipment', $id, ['student_id', 'order_id', 'receiver_name', 'receiver_mobile', 'receiver_address', 'courier_company', 'tracking_no', 'shipped_at', 'delivered_at', 'status', 'remark'], $payload);
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        auth_require_roles(['班主任']);
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        crud_delete($pdo, 'oa_shipment', $id);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
