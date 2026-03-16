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
