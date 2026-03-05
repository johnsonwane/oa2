<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/sprint123_bootstrap.php';
require_once __DIR__ . '/sprint123_crud.php';

function invoices_order_exists(PDO $pdo, int $orderId): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(1) FROM oa_order WHERE id=?');
    $stmt->execute([$orderId]);
    return (int)$stmt->fetchColumn() > 0;
}

function invoices_validate(PDO $pdo, array $data): void
{
    $orderId = (int)($data['order_id'] ?? 0);
    $amount = (float)($data['amount'] ?? 0);

    if ($orderId <= 0) {
        json_response(400, '订单ID必须为正整数', null, 400);
    }
    if ($amount <= 0) {
        json_response(400, '开票金额必须大于0', null, 400);
    }
    if (!invoices_order_exists($pdo, $orderId)) {
        json_response(400, '业务校验失败：订单不存在，不能创建发票', null, 400);
    }
}

try {
    $pdo = get_db_connection();
    ensure_sprint123_tables($pdo);
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        json_response(0, 'ok', crud_list($pdo, 'oa_invoice'));
    }

    if ($m === 'POST') {
        $d = request_body();
        require_fields($d, ['order_id', 'amount']);
        invoices_validate($pdo, $d);
        $id = crud_insert($pdo, 'oa_invoice', ['order_id', 'receipt_id', 'invoice_profile_id', 'invoice_no', 'amount', 'invoice_type', 'status', 'issued_at', 'remark'], $d);
        json_response(0, 'created', ['id' => $id]);
    }

    if ($m === 'PUT') {
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        invoices_validate($pdo, $d);
        crud_update($pdo, 'oa_invoice', $id, ['order_id', 'receipt_id', 'invoice_profile_id', 'invoice_no', 'amount', 'invoice_type', 'status', 'issued_at', 'remark'], $d);
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        crud_delete($pdo, 'oa_invoice', $id);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
