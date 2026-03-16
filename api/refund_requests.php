<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settlement.php';

function refund_accessible(PDO $pdo, int $id): bool
{
    $role = auth_user_role();
    if (auth_is_admin_like() || $role === '财务') {
        return true;
    }
    $uid = auth_user_id();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM oa_refund_request rr JOIN oa_order o ON o.id=rr.order_id WHERE rr.id=? AND o.seller_user_id=?');
    $stmt->execute([$id, $uid]);
    return (int)$stmt->fetchColumn() > 0;
}

function refund_status_allowed(string $old, string $new): bool
{
    $map = [
        'pending' => ['approved', 'rejected'],
        'approved' => ['paid', 'rejected'],
        'rejected' => ['rejected'],
        'paid' => ['paid'],
    ];
    return in_array($new, $map[$old] ?? [], true);
}

function refund_apply_reversal(PDO $pdo, array $refund): void
{
    $orderId = (int)$refund['order_id'];
    $refundAmount = (float)$refund['amount'];

    $oStmt = $pdo->prepare('SELECT id, seller_user_id, seller_commission_amount, paid_amount FROM oa_order WHERE id=? LIMIT 1');
    $oStmt->execute([$orderId]);
    $order = $oStmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        throw new RuntimeException('订单不存在');
    }

    $sellerId = (int)($order['seller_user_id'] ?? 0);
    if ($sellerId > 0) {
        $paid = max((float)$order['paid_amount'], 0.01);
        $ratio = min(1, $refundAmount / $paid);
        $commissionReverse = round(((float)$order['seller_commission_amount']) * $ratio * -1, 2);

        if ($commissionReverse < 0) {
            $adj = $pdo->prepare('INSERT INTO oa_commission_adjustment(calc_id, user_id, adjust_amount, reason, operator_user_id) VALUES(NULL, ?, ?, ?, ?)');
            $adj->execute([$sellerId, $commissionReverse, '退款自动反结算', auth_user_id()]);

            $task = $pdo->prepare("INSERT INTO oa_refund_reversal_task(refund_request_id, order_id, user_id, reversal_type, amount, status, remark) VALUES(?, ?, ?, 'payroll', ?, 'pending', ?)");
            $task->execute([(int)$refund['id'], $orderId, $sellerId, $commissionReverse, '退款触发工资反结算任务']);
        }
    }

    settlement_reconcile_order($pdo, $orderId);
}

try {
    $pdo = get_db_connection();
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        auth_require_roles(['财务', '顾问', '班主任', '教练', '部门经理']);
        if (auth_is_admin_like() || auth_role_in(['财务', '部门经理'])) {
            $rows = $pdo->query('SELECT rr.*, o.student_id, o.course_id, o.seller_user_id, s.name student_name, c.course_name FROM oa_refund_request rr LEFT JOIN oa_order o ON o.id=rr.order_id LEFT JOIN oa_student s ON s.id=o.student_id LEFT JOIN oa_course c ON c.id=o.course_id ORDER BY rr.id DESC')->fetchAll(PDO::FETCH_ASSOC);
            json_response(0, 'ok', $rows);
        }
        $uid = auth_user_id();
        $stmt = $pdo->prepare('SELECT rr.*, o.student_id, o.course_id, o.seller_user_id, s.name student_name, c.course_name FROM oa_refund_request rr LEFT JOIN oa_order o ON o.id=rr.order_id LEFT JOIN oa_student s ON s.id=o.student_id LEFT JOIN oa_course c ON c.id=o.course_id WHERE o.seller_user_id=? ORDER BY rr.id DESC');
        $stmt->execute([$uid]);
        json_response(0, 'ok', $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    if ($m === 'POST') {
        auth_require_roles(['财务', '顾问', '班主任']);
        $d = request_body();
        require_fields($d, ['order_id', 'amount', 'reason']);

        $orderId = (int)$d['order_id'];
        $amount = (float)$d['amount'];
        if ($orderId <= 0 || $amount <= 0) {
            json_response(400, 'order_id/amount 非法', null, 400);
        }

        $snap = settlement_order_finance_snapshot($pdo, $orderId);
        if ($amount > $snap['net_paid'] + 0.00001) {
            json_response(400, '退款金额不能超过订单净实收金额', $snap, 400);
        }

        $stmt = $pdo->prepare("INSERT INTO oa_refund_request(order_id, amount, reason, status, requested_by_user_id, requested_at, remark) VALUES(?, ?, ?, 'pending', ?, NOW(), ?)");
        $stmt->execute([$orderId, $amount, trim((string)$d['reason']), auth_user_id(), trim((string)($d['remark'] ?? ''))]);
        json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
    }

    if ($m === 'PUT') {
        auth_require_roles(['财务']);
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        if (!refund_accessible($pdo, $id)) {
            json_response(403, '无权操作该退款单', null, 403);
        }

        $rowStmt = $pdo->prepare('SELECT * FROM oa_refund_request WHERE id=? LIMIT 1');
        $rowStmt->execute([$id]);
        $row = $rowStmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) json_response(404, '退款单不存在', null, 404);

        $newStatus = trim((string)($d['status'] ?? ''));
        if ($newStatus === '') json_response(400, 'status不能为空', null, 400);
        if (!refund_status_allowed((string)$row['status'], $newStatus)) {
            json_response(400, '退款状态流转非法', null, 400);
        }

        $pdo->beginTransaction();
        try {
            if ($newStatus === 'approved') {
                $u = $pdo->prepare('UPDATE oa_refund_request SET status=?, approved_by_user_id=?, approved_at=NOW(), remark=? WHERE id=?');
                $u->execute([$newStatus, auth_user_id(), trim((string)($d['remark'] ?? '')), $id]);
            } elseif ($newStatus === 'rejected') {
                $u = $pdo->prepare('UPDATE oa_refund_request SET status=?, approved_by_user_id=?, approved_at=NOW(), remark=? WHERE id=?');
                $u->execute([$newStatus, auth_user_id(), trim((string)($d['remark'] ?? '')), $id]);
            } elseif ($newStatus === 'paid') {
                $snap = settlement_order_finance_snapshot($pdo, (int)$row['order_id']);
                if ((float)$row['amount'] > $snap['net_paid'] + 0.00001) {
                    json_response(400, '退款金额超出当前净实收，无法执行', $snap, 400);
                }

                $u = $pdo->prepare('UPDATE oa_refund_request SET status=?, approved_by_user_id=COALESCE(approved_by_user_id,?), approved_at=COALESCE(approved_at,NOW()), paid_by_user_id=?, paid_at=NOW(), remark=? WHERE id=?');
                $u->execute([$newStatus, auth_user_id(), auth_user_id(), trim((string)($d['remark'] ?? '')), $id]);

                $row['id'] = $id;
                refund_apply_reversal($pdo, $row);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        auth_require_roles(['财务']);
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $rowStmt = $pdo->prepare('SELECT status FROM oa_refund_request WHERE id=? LIMIT 1');
        $rowStmt->execute([$id]);
        $status = (string)$rowStmt->fetchColumn();
        if ($status === '') json_response(404, '退款单不存在', null, 404);
        if ($status === 'paid') json_response(400, '已执行退款单禁止删除', null, 400);
        $pdo->prepare('DELETE FROM oa_refund_request WHERE id=?')->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
