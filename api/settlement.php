<?php

function settlement_order_finance_snapshot(PDO $pdo, int $orderId): array
{
    $orderStmt = $pdo->prepare('SELECT id, total_amount, paid_amount, pay_status, seller_user_id, seller_commission_amount FROM oa_order WHERE id=? LIMIT 1');
    $orderStmt->execute([$orderId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        throw new RuntimeException('订单不存在');
    }

    $receiptInStmt = $pdo->prepare('SELECT IFNULL(SUM(amount),0), MAX(pay_time) FROM oa_receipt WHERE order_id=? AND verified_status=1');
    $receiptInStmt->execute([$orderId]);
    [$receiptIn, $maxPayTime] = $receiptInStmt->fetch(PDO::FETCH_NUM);

    $refundStmt = $pdo->prepare("SELECT IFNULL(SUM(amount),0), MAX(paid_at) FROM oa_refund_request WHERE order_id=? AND status='paid'");
    $refundStmt->execute([$orderId]);
    [$refundAmount, $maxRefundTime] = $refundStmt->fetch(PDO::FETCH_NUM);

    $invoiceStmt = $pdo->prepare("SELECT IFNULL(SUM(amount),0) FROM oa_invoice WHERE order_id=? AND status NOT IN ('void','cancelled')");
    $invoiceStmt->execute([$orderId]);
    $invoiceAmount = (float)$invoiceStmt->fetchColumn();

    $receiptIn = (float)$receiptIn;
    $refundAmount = (float)$refundAmount;
    $netPaid = max(0, $receiptIn - $refundAmount);

    $totalAmount = (float)$order['total_amount'];
    $payStatus = 0;
    if ($refundAmount > 0 && $refundAmount >= $receiptIn) {
        $payStatus = 3; // refunded
    } elseif ($netPaid <= 0.00001) {
        $payStatus = 0;
    } elseif ($netPaid + 0.00001 < $totalAmount) {
        $payStatus = 1;
    } else {
        $payStatus = 2;
    }

    return [
        'order' => $order,
        'receipt_in' => round($receiptIn, 2),
        'refund_amount' => round($refundAmount, 2),
        'net_paid' => round($netPaid, 2),
        'invoice_amount' => round($invoiceAmount, 2),
        'invoice_exceeds_net_paid' => $invoiceAmount > ($netPaid + 0.00001),
        'pay_status' => $payStatus,
        'receipt_time' => $maxPayTime ?: null,
        'refund_time' => $maxRefundTime ?: null,
    ];
}

function settlement_reconcile_order(PDO $pdo, int $orderId): array
{
    $snap = settlement_order_finance_snapshot($pdo, $orderId);

    $stmt = $pdo->prepare('UPDATE oa_order SET paid_amount=?, pay_status=?, receipt_time=?, refund_amount=?, refund_time=? WHERE id=?');
    $stmt->execute([
        $snap['net_paid'],
        $snap['pay_status'],
        $snap['receipt_time'],
        $snap['refund_amount'],
        $snap['refund_time'],
        $orderId,
    ]);

    return $snap;
}

function settlement_assert_invoice_not_exceed_net_paid(PDO $pdo, int $orderId, float $extraInvoiceAmount = 0.0): void
{
    $snap = settlement_order_finance_snapshot($pdo, $orderId);
    if (($snap['invoice_amount'] + $extraInvoiceAmount) > ($snap['net_paid'] + 0.00001)) {
        json_response(400, '开票金额合计不能超过订单净实收金额（实收-退款）', [
            'net_paid' => $snap['net_paid'],
            'existing_invoice_amount' => $snap['invoice_amount'],
            'request_invoice_amount' => round($extraInvoiceAmount, 2),
        ], 400);
    }
}

/**
 * 收款成功后自动写入财务记录（防重复）
 * 由 receipts.php 或 orders.php 收款确认时调用
 */
function settlement_auto_finance_income(PDO $pdo, int $orderId, float $amount, int $operatorUserId = 0): void
{
    if ($amount <= 0) return;

    // 防重复：同一订单同类型只写一条
    $checkStmt = $pdo->prepare("SELECT id FROM oa_finance_record WHERE source_type='order_payment' AND source_id=? LIMIT 1");
    $checkStmt->execute([$orderId]);
    if ($checkStmt->fetchColumn()) return;

    // 获取订单、学员、课程信息
    $infoStmt = $pdo->prepare("
        SELECT o.id, s.name student_name, c.course_name, o.order_type
        FROM oa_order o
        LEFT JOIN oa_student s ON s.id = o.student_id
        LEFT JOIN oa_course c ON c.id = o.course_id
        WHERE o.id = ? LIMIT 1
    ");
    $infoStmt->execute([$orderId]);
    $info = $infoStmt->fetch(PDO::FETCH_ASSOC);
    if (!$info) return;

    $orderTypeLabel = ['first' => '首单', 'renewal' => '续单', 'upgrade' => '增课'][$info['order_type'] ?? 'first'] ?? '首单';
    $itemName = "学费收款({$orderTypeLabel})-" . ($info['student_name'] ?? '') . '-' . ($info['course_name'] ?? '');

    $pdo->prepare("INSERT INTO oa_finance_record(record_type, item_name, amount, record_date, remark, source_type, source_id, operator_user_id)
        VALUES('income', ?, ?, CURDATE(), ?, 'order_payment', ?, ?)")
        ->execute([$itemName, $amount, "订单#{$orderId}收款自动入账", $orderId, $operatorUserId]);
}

/**
 * 退款确认后自动写入财务记录（防重复）
 */
function settlement_auto_finance_refund(PDO $pdo, int $orderId, float $refundAmount, int $operatorUserId = 0): void
{
    if ($refundAmount <= 0) return;

    $checkStmt = $pdo->prepare("SELECT id FROM oa_finance_record WHERE source_type='order_refund' AND source_id=? LIMIT 1");
    $checkStmt->execute([$orderId]);
    if ($checkStmt->fetchColumn()) return;

    $infoStmt = $pdo->prepare("SELECT s.name student_name FROM oa_order o LEFT JOIN oa_student s ON s.id=o.student_id WHERE o.id=? LIMIT 1");
    $infoStmt->execute([$orderId]);
    $info = $infoStmt->fetch(PDO::FETCH_ASSOC);

    $itemName = '退款-' . ($info['student_name'] ?? '') . "-订单#{$orderId}";

    $pdo->prepare("INSERT INTO oa_finance_record(record_type, item_name, amount, record_date, remark, source_type, source_id, operator_user_id)
        VALUES('expense', ?, ?, CURDATE(), ?, 'order_refund', ?, ?)")
        ->execute([$itemName, $refundAmount, "订单#{$orderId}退款自动入账", $orderId, $operatorUserId]);
}

/**
 * 全量对账：扫描所有已付款订单，补全缺失的财务记录
 * 可由定时任务或管理员手动触发
 */
function settlement_batch_reconcile_finance(PDO $pdo): int
{
    $stmt = $pdo->query("SELECT id, paid_amount, refund_amount, pay_status FROM oa_order WHERE pay_status > 0 AND paid_amount > 0");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = 0;
    foreach ($orders as $order) {
        settlement_auto_finance_income($pdo, (int)$order['id'], (float)$order['paid_amount']);
        if ((float)$order['refund_amount'] > 0 && (int)$order['pay_status'] === 3) {
            settlement_auto_finance_refund($pdo, (int)$order['id'], (float)$order['refund_amount']);
        }
        $count++;
    }
    return $count;
}
