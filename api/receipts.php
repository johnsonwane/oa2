<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/business_bootstrap.php';

try {
    $pdo = get_db_connection();
    ensure_business_workflow_schema($pdo);
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        $sql = 'SELECT r.id,r.order_id,r.receipt_no,r.amount,r.pay_method,r.pay_time,r.verified_status,r.remark,r.created_at,o.student_id,s.name student_name,o.course_id,c.course_name,o.paid_amount FROM oa_receipt r LEFT JOIN oa_order o ON o.id=r.order_id LEFT JOIN oa_student s ON s.id=o.student_id LEFT JOIN oa_course c ON c.id=o.course_id ORDER BY r.id DESC';
        $rows = $pdo->query($sql)->fetchAll();
        json_response(0, 'ok', $rows);
    }

    if ($m === 'POST' || $m === 'PUT') {
        $d = request_body();
        require_fields($d, ['order_id', 'receipt_no', 'amount']);
        $orderId = (int)$d['order_id'];
        if ($orderId <= 0) json_response(400, 'order_id非法', null, 400);
        $amount = (float)$d['amount'];
        if ($amount < 0) json_response(400, 'amount不能为负数', null, 400);

        $orderStmt = $pdo->prepare('SELECT id, paid_amount FROM oa_order WHERE id=? LIMIT 1');
        $orderStmt->execute([$orderId]);
        $order = $orderStmt->fetch();
        if (!$order) json_response(404, '订单不存在', null, 404);

        $verified = (int)($d['verified_status'] ?? 0);
        if ($verified === 1 && $amount > (float)$order['paid_amount']) {
            json_response(400, '核销金额不能大于订单实收金额', null, 400);
        }

        if ($m === 'POST') {
            $stmt = $pdo->prepare('INSERT INTO oa_receipt(order_id,receipt_no,amount,pay_method,pay_time,verified_status,remark) VALUES(?,?,?,?,?,?,?)');
            $stmt->execute([$orderId, trim((string)$d['receipt_no']), $amount, trim((string)($d['pay_method'] ?? '')), trim((string)($d['pay_time'] ?? '')) ?: null, $verified, trim((string)($d['remark'] ?? ''))]);
            json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
        }

        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $stmt = $pdo->prepare('UPDATE oa_receipt SET order_id=?,receipt_no=?,amount=?,pay_method=?,pay_time=?,verified_status=?,remark=? WHERE id=?');
        $stmt->execute([$orderId, trim((string)$d['receipt_no']), $amount, trim((string)($d['pay_method'] ?? '')), trim((string)($d['pay_time'] ?? '')) ?: null, $verified, trim((string)($d['remark'] ?? '')), $id]);
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $stmt = $pdo->prepare('DELETE FROM oa_receipt WHERE id=?');
        $stmt->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
