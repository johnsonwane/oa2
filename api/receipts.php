<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/business_bootstrap.php';
require_once __DIR__ . '/hr_bootstrap.php';
require_once __DIR__ . '/settlement.php';

function receipts_scope(PDO $pdo, string $orderAlias = 'o', string $studentAlias = 's'): array
{
    $role = auth_user_role();
    $uid = auth_user_id();
    $uname = auth_user_name();

    if (auth_is_admin_like() || $role === '财务') {
        return ['sql' => '1=1', 'params' => []];
    }
    if ($role === '顾问') {
        return ['sql' => "({$orderAlias}.seller_user_id = :uid OR {$studentAlias}.owner_consultant_user_id = :uid OR {$studentAlias}.consultant = :uname)", 'params' => [':uid' => $uid, ':uname' => $uname]];
    }
    if ($role === '班主任') {
        return ['sql' => "{$studentAlias}.headteacher_user_id = :uid", 'params' => [':uid' => $uid]];
    }
    if ($role === '教练') {
        return ['sql' => "({$studentAlias}.coach_user_id = :uid OR {$studentAlias}.delivery_coach = :uname)", 'params' => [':uid' => $uid, ':uname' => $uname]];
    }
    return ['sql' => '1=0', 'params' => []];
}

function receipt_accessible(PDO $pdo, int $id): bool
{
    $scope = receipts_scope($pdo, 'o', 's');
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM oa_receipt r LEFT JOIN oa_order o ON o.id=r.order_id LEFT JOIN oa_student s ON s.id=o.student_id WHERE r.id=:id AND (' . $scope['sql'] . ')');
    $stmt->execute(array_merge([':id' => $id], $scope['params']));
    return (int)$stmt->fetchColumn() > 0;
}

function receipt_assert_idempotent(PDO $pdo, string $channel, ?string $txnId, int $ignoreId = 0): void
{
    if ($txnId === '') {
        return;
    }
    $sql = 'SELECT id FROM oa_receipt WHERE channel=? AND channel_txn_id=?';
    $params = [$channel, $txnId];
    if ($ignoreId > 0) {
        $sql .= ' AND id<>?';
        $params[] = $ignoreId;
    }
    $sql .= ' LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    if ($stmt->fetchColumn()) {
        json_response(409, '重复回单：该渠道流水号已入账', null, 409);
    }
}

try {
    $pdo = get_db_connection();
    ensure_business_workflow_schema($pdo);
    ensure_hr_schema($pdo);
    ensure_table_columns($pdo, 'oa_receipt', [
        'channel' => "`channel` VARCHAR(50) DEFAULT ''",
        'receiver_user_id' => "`receiver_user_id` INT UNSIGNED DEFAULT NULL",
        'refund_time' => "`refund_time` DATETIME DEFAULT NULL",
        'refund_amount' => "`refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0",
        'channel_txn_id' => "`channel_txn_id` VARCHAR(80) DEFAULT NULL",
    ]);

    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        $scope = receipts_scope($pdo, 'o', 's');
        $sql = 'SELECT r.id,r.order_id,r.receipt_no,r.amount,r.channel,r.channel_txn_id,r.receiver_user_id,ru.real_name receiver_name,r.pay_method,r.pay_time,r.refund_time,r.refund_amount,r.verified_status,r.remark,r.created_at,o.student_id,s.name student_name,o.course_id,c.course_name,o.paid_amount FROM oa_receipt r LEFT JOIN oa_order o ON o.id=r.order_id LEFT JOIN oa_student s ON s.id=o.student_id LEFT JOIN oa_course c ON c.id=o.course_id LEFT JOIN oa_user ru ON ru.id=r.receiver_user_id WHERE ' . $scope['sql'] . ' ORDER BY r.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($scope['params']);
        json_response(0, 'ok', $stmt->fetchAll());
    }

    if ($m === 'POST' || $m === 'PUT') {
        auth_require_roles(['财务', '班主任']);

        $d = request_body();
        require_fields($d, ['order_id', 'receipt_no', 'amount']);
        $orderId = (int)$d['order_id'];
        if ($orderId <= 0) json_response(400, 'order_id非法', null, 400);
        $amount = (float)$d['amount'];
        if ($amount < 0) json_response(400, 'amount不能为负数', null, 400);

        $channel = trim((string)($d['channel'] ?? ''));
        $channelTxnId = trim((string)($d['channel_txn_id'] ?? ''));
        $channelTxnId = $channelTxnId !== '' ? $channelTxnId : null;

        $orderStmt = $pdo->prepare('SELECT id FROM oa_order WHERE id=? LIMIT 1');
        $orderStmt->execute([$orderId]);
        if (!$orderStmt->fetch()) json_response(404, '订单不存在', null, 404);

        $verified = (int)($d['verified_status'] ?? 0);
        if (!in_array($verified, [0, 1], true)) {
            json_response(400, 'verified_status 非法', null, 400);
        }

        $receiver = (int)($d['receiver_user_id'] ?? 0);
        $receiver = $receiver > 0 ? $receiver : null;
        $refundAmount = (float)($d['refund_amount'] ?? 0);
        if ($refundAmount < 0) json_response(400, 'refund_amount不能为负数', null, 400);

        if ($m === 'POST') {
            receipt_assert_idempotent($pdo, $channel, $channelTxnId, 0);

            $stmt = $pdo->prepare('INSERT INTO oa_receipt(order_id,receipt_no,amount,channel,channel_txn_id,receiver_user_id,pay_method,pay_time,refund_time,refund_amount,verified_status,remark) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$orderId, trim((string)$d['receipt_no']), $amount, $channel, $channelTxnId, $receiver, trim((string)($d['pay_method'] ?? '')), trim((string)($d['pay_time'] ?? '')) ?: null, trim((string)($d['refund_time'] ?? '')) ?: null, $refundAmount, $verified, trim((string)($d['remark'] ?? ''))]);
            settlement_reconcile_order($pdo, $orderId);
            json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
        }

        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        if (!receipt_accessible($pdo, $id)) {
            json_response(403, '无权修改该收款单', null, 403);
        }

        receipt_assert_idempotent($pdo, $channel, $channelTxnId, $id);

        $stmt = $pdo->prepare('UPDATE oa_receipt SET order_id=?,receipt_no=?,amount=?,channel=?,channel_txn_id=?,receiver_user_id=?,pay_method=?,pay_time=?,refund_time=?,refund_amount=?,verified_status=?,remark=? WHERE id=?');
        $stmt->execute([$orderId, trim((string)$d['receipt_no']), $amount, $channel, $channelTxnId, $receiver, trim((string)($d['pay_method'] ?? '')), trim((string)($d['pay_time'] ?? '')) ?: null, trim((string)($d['refund_time'] ?? '')) ?: null, $refundAmount, $verified, trim((string)($d['remark'] ?? '')), $id]);
        settlement_reconcile_order($pdo, $orderId);
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        auth_require_roles(['财务']);
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        if (!receipt_accessible($pdo, $id)) {
            json_response(403, '无权删除该收款单', null, 403);
        }

        $orderStmt = $pdo->prepare('SELECT order_id FROM oa_receipt WHERE id=? LIMIT 1');
        $orderStmt->execute([$id]);
        $oid = (int)$orderStmt->fetchColumn();

        $stmt = $pdo->prepare('DELETE FROM oa_receipt WHERE id=?');
        $stmt->execute([$id]);
        if ($oid > 0) {
            settlement_reconcile_order($pdo, $oid);
        }
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
