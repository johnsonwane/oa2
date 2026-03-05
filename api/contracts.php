<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/sprint123_bootstrap.php';
require_once __DIR__ . '/sprint123_crud.php';

function contracts_order_student_match(PDO $pdo, int $orderId, int $studentId): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(1) FROM oa_order WHERE id=? AND student_id=?');
    $stmt->execute([$orderId, $studentId]);
    return (int)$stmt->fetchColumn() > 0;
}

function contracts_validate(PDO $pdo, array $data): void
{
    $orderId = (int)($data['order_id'] ?? 0);
    $studentId = (int)($data['student_id'] ?? 0);
    if ($orderId <= 0 || $studentId <= 0) {
        json_response(400, '订单ID和学员ID必须为正整数', null, 400);
    }

    if (!contracts_order_student_match($pdo, $orderId, $studentId)) {
        json_response(400, '业务校验失败：订单与学员不匹配，请检查来源数据', null, 400);
    }
}

try {
    $pdo = get_db_connection();
    ensure_sprint123_tables($pdo);
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        json_response(0, 'ok', crud_list($pdo, 'oa_contract'));
    }

    if ($m === 'POST') {
        $d = request_body();
        require_fields($d, ['contract_no', 'order_id', 'student_id']);
        contracts_validate($pdo, $d);
        $id = crud_insert($pdo, 'oa_contract', ['contract_no', 'order_id', 'student_id', 'seller_user_id', 'sign_time', 'contract_url', 'status', 'remark'], $d);
        json_response(0, 'created', ['id' => $id]);
    }

    if ($m === 'PUT') {
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        contracts_validate($pdo, $d);
        crud_update($pdo, 'oa_contract', $id, ['contract_no', 'order_id', 'student_id', 'seller_user_id', 'sign_time', 'contract_url', 'status', 'remark'], $d);
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        crud_delete($pdo, 'oa_contract', $id);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
