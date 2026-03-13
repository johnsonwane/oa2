<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/order_referrer_bootstrap.php';
require_once __DIR__ . '/business_bootstrap.php';

function orders_scope(PDO $pdo, string $orderAlias = 'o', string $studentAlias = 's'): array
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
    if ($role === '部门经理') {
        $stmt = $pdo->prepare('SELECT department FROM oa_user WHERE id=? LIMIT 1');
        $stmt->execute([$uid]);
        $dept = trim((string)$stmt->fetchColumn());
        if ($dept === '') {
            return ['sql' => '1=0', 'params' => []];
        }
        return [
            'sql' => "EXISTS (SELECT 1 FROM oa_user du WHERE du.id = {$orderAlias}.seller_user_id AND du.department = :dept)",
            'params' => [':dept' => $dept],
        ];
    }
    return ['sql' => '1=0', 'params' => []];
}

function orders_allowed_pay_status_transition(?int $old, int $new): bool
{
    if (!in_array($new, [0, 1, 2, 3], true)) {
        return false;
    }
    if ($old === null) {
        return true;
    }
    $allowed = [
        0 => [0, 1, 2],
        1 => [1, 2, 3],
        2 => [2, 3],
        3 => [3],
    ];
    return in_array($new, $allowed[$old] ?? [], true);
}

function orders_validate_payment_stage(string $stage): void
{
    if (!in_array($stage, ['deposit', 'middle', 'final', 'full'], true)) {
        json_response(400, 'payment_stage 非法', null, 400);
    }
}

function order_accessible(PDO $pdo, int $id): bool
{
    $scope = orders_scope($pdo, 'o', 's');
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM oa_order o LEFT JOIN oa_student s ON s.id=o.student_id WHERE o.id = :id AND (' . $scope['sql'] . ')');
    $params = array_merge([':id' => $id], $scope['params']);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn() > 0;
}

try {
    $pdo = get_db_connection();
    ensure_order_referrer_schema($pdo);
    ensure_business_workflow_schema($pdo);
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        $where = [];
        $params = [];
        $payStatus = isset($_GET['pay_status']) ? trim((string)$_GET['pay_status']) : '';
        $keyword = trim((string)($_GET['keyword'] ?? ''));
        $paymentStage = trim((string)($_GET['payment_stage'] ?? ''));

        $scope = orders_scope($pdo, 'o', 's');
        $where[] = '(' . $scope['sql'] . ')';
        $params = array_merge($params, $scope['params']);

        if ($payStatus !== '') {
            $where[] = 'o.pay_status = :pay_status';
            $params[':pay_status'] = (int)$payStatus;
        }
        if ($paymentStage !== '') {
            orders_validate_payment_stage($paymentStage);
            $where[] = 'o.payment_stage = :payment_stage';
            $params[':payment_stage'] = $paymentStage;
        }
        if ($keyword !== '') {
            $where[] = '(s.name LIKE :kw OR c.course_name LIKE :kw OR IFNULL(r.name,\'\') LIKE :kw)';
            $params[':kw'] = "%{$keyword}%";
        }

        $baseSql = ' FROM oa_order o
            LEFT JOIN oa_student s ON s.id=o.student_id
            LEFT JOIN oa_course c ON c.id=o.course_id
            LEFT JOIN oa_referrer r ON r.id=o.referrer_id
            LEFT JOIN oa_user su ON su.id=o.seller_user_id';

        $whereSql = ' WHERE ' . implode(' AND ', $where);

        if (paged_mode($_GET)) {
            $p = parse_pagination($_GET);
            $countStmt = $pdo->prepare('SELECT COUNT(*)' . $baseSql . $whereSql);
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $sql = 'SELECT o.id,o.student_id,s.name student_name,o.course_id,c.course_name,o.amount,o.total_amount,o.paid_amount,o.pay_status,o.payment_stage,o.sales_commission_amount,o.seller_user_id,o.seller_role,o.seller_commission_amount,o.referrer_id,r.name referrer_name,o.referrer_commission_amount,o.student_wechat_name,o.student_mobile,o.student_address,o.payment_time,o.receipt_time,o.refund_time,o.refund_amount,o.remark,o.created_at, su.real_name seller_name'
                . $baseSql . $whereSql . ' ORDER BY o.id DESC LIMIT :limit OFFSET :offset';
            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', $p['page_size'], PDO::PARAM_INT);
            $stmt->bindValue(':offset', $p['offset'], PDO::PARAM_INT);
            $stmt->execute();

            json_response(0, 'ok', ['items' => $stmt->fetchAll(), 'pagination' => ['page' => $p['page'], 'page_size' => $p['page_size'], 'total' => $total]]);
        }

        $sql = 'SELECT o.id,o.student_id,s.name student_name,o.course_id,c.course_name,o.amount,o.total_amount,o.paid_amount,o.pay_status,o.payment_stage,o.sales_commission_amount,o.seller_user_id,o.seller_role,o.seller_commission_amount,o.referrer_id,r.name referrer_name,o.referrer_commission_amount,o.student_wechat_name,o.student_mobile,o.student_address,o.payment_time,o.receipt_time,o.refund_time,o.refund_amount,o.remark,o.created_at, su.real_name seller_name'
            . $baseSql . $whereSql . ' ORDER BY o.id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response(0, 'ok', $stmt->fetchAll());
    }

    if ($m === 'POST' || $m === 'PUT') {
        auth_require_roles(['顾问', '班主任', '教练', '财务']);

        $d = request_body();
        $id = (int)($d['id'] ?? 0);

        if ($m === 'PUT') {
            if ($id <= 0) json_response(400, 'id非法', null, 400);
            if (!order_accessible($pdo, $id)) {
                json_response(403, '无权修改该订单', null, 403);
            }
        }

        require_fields($d, ['student_id', 'course_id']);
        $studentId = (int)$d['student_id'];
        $courseId = (int)$d['course_id'];
        if ($studentId <= 0 || $courseId <= 0) {
            json_response(400, 'student_id/course_id 非法', null, 400);
        }

        $stuStmt = $pdo->prepare('SELECT id FROM oa_student WHERE id=? LIMIT 1');
        $stuStmt->execute([$studentId]);
        if (!$stuStmt->fetch()) json_response(404, '学员不存在', null, 404);

        $courseStmt = $pdo->prepare('SELECT id, price FROM oa_course WHERE id=? LIMIT 1');
        $courseStmt->execute([$courseId]);
        $course = $courseStmt->fetch();
        if (!$course) json_response(404, '课程不存在', null, 404);

        $totalAmount = isset($d['total_amount']) && $d['total_amount'] !== '' ? (float)$d['total_amount'] : (float)$course['price'];
        if ($totalAmount < 0) json_response(400, 'total_amount 不能为负数', null, 400);

        $paidAmount = isset($d['paid_amount']) && $d['paid_amount'] !== '' ? (float)$d['paid_amount'] : $totalAmount;
        if ($paidAmount < 0) json_response(400, 'paid_amount 不能为负数', null, 400);

        $payStatus = isset($d['pay_status']) ? (int)$d['pay_status'] : ($paidAmount >= $totalAmount ? 2 : ($paidAmount > 0 ? 1 : 0));
        $oldStatus = null;
        if ($m === 'PUT') {
            $oldStmt = $pdo->prepare('SELECT pay_status FROM oa_order WHERE id=? LIMIT 1');
            $oldStmt->execute([$id]);
            $oldStatus = ($row = $oldStmt->fetch()) ? (int)$row['pay_status'] : null;
        }
        if (!orders_allowed_pay_status_transition($oldStatus, $payStatus)) {
            json_response(400, 'pay_status 状态流转非法', null, 400);
        }

        $paymentStage = trim((string)($d['payment_stage'] ?? 'full'));
        orders_validate_payment_stage($paymentStage);

        $sellerUserId = (int)($d['seller_user_id'] ?? 0);
        if ($sellerUserId <= 0) $sellerUserId = auth_user_id();
        $sellerRole = trim((string)($d['seller_role'] ?? auth_user_role()));

        $defaultRate = 0.10;
        if ($sellerRole === '教练') $defaultRate = 0.08;
        if ($sellerRole === '班主任') $defaultRate = 0.06;
        $salesCommission = isset($d['sales_commission_amount']) && $d['sales_commission_amount'] !== '' ? (float)$d['sales_commission_amount'] : round($paidAmount * $defaultRate, 2);
        $sellerCommission = isset($d['seller_commission_amount']) && $d['seller_commission_amount'] !== '' ? (float)$d['seller_commission_amount'] : $salesCommission;
        if ($salesCommission < 0 || $sellerCommission < 0) json_response(400, '分成金额不能为负数', null, 400);

        $referrerId = (int)($d['referrer_id'] ?? 0);
        $referrerId = $referrerId > 0 ? $referrerId : null;
        $ref = null;
        if ($referrerId !== null) {
            $refStmt = $pdo->prepare('SELECT id, commission_type, commission_rate, fixed_amount FROM oa_referrer WHERE id=? AND status=1 LIMIT 1');
            $refStmt->execute([$referrerId]);
            $ref = $refStmt->fetch();
            if (!$ref) json_response(404, '推荐者不存在或已禁用', null, 404);
        }
        $referrerCommission = isset($d['referrer_commission_amount']) && $d['referrer_commission_amount'] !== ''
            ? (float)$d['referrer_commission_amount']
            : (($referrerId !== null && (($ref['commission_type'] ?? 'rate') === 'fixed')) ? (float)($ref['fixed_amount'] ?? 0) : round($paidAmount * (float)($ref['commission_rate'] ?? 0) / 100, 2));
        if ($referrerId === null) $referrerCommission = 0.0;
        if ($referrerCommission < 0) json_response(400, 'referrer_commission_amount 不能为负数', null, 400);

        $studentWechatName = trim((string)($d['student_wechat_name'] ?? ''));
        $studentMobile = trim((string)($d['student_mobile'] ?? ''));
        $studentAddress = trim((string)($d['student_address'] ?? ''));
        $paymentTime = trim((string)($d['payment_time'] ?? '')) ?: null;
        $receiptTime = trim((string)($d['receipt_time'] ?? '')) ?: null;
        $refundTime = trim((string)($d['refund_time'] ?? '')) ?: null;
        $refundAmount = (float)($d['refund_amount'] ?? 0);
        if ($refundAmount < 0) json_response(400, 'refund_amount 不能为负数', null, 400);
        $orderRemark = trim((string)($d['remark'] ?? ''));

        if ($m === 'POST') {
            $stmt = $pdo->prepare('INSERT INTO oa_order(student_id,course_id,amount,total_amount,paid_amount,pay_status,payment_stage,sales_commission_amount,seller_user_id,seller_role,seller_commission_amount,referrer_id,referrer_commission_amount,student_wechat_name,student_mobile,student_address,payment_time,receipt_time,refund_time,refund_amount,remark) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$studentId, $courseId, $totalAmount, $totalAmount, $paidAmount, $payStatus, $paymentStage, $salesCommission, $sellerUserId, $sellerRole, $sellerCommission, $referrerId, $referrerCommission, $studentWechatName, $studentMobile, $studentAddress, $paymentTime, $receiptTime, $refundTime, $refundAmount, $orderRemark]);
            if (in_array($payStatus, [1, 2], true)) {
                $pdo->prepare("UPDATE oa_student SET is_student=1, student_stage='active', follow_status='已报名', converted_at=COALESCE(converted_at,NOW()) WHERE id=?")->execute([$studentId]);
            }
            json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
        }

        $stmt = $pdo->prepare('UPDATE oa_order SET student_id=?,course_id=?,amount=?,total_amount=?,paid_amount=?,pay_status=?,payment_stage=?,sales_commission_amount=?,seller_user_id=?,seller_role=?,seller_commission_amount=?,referrer_id=?,referrer_commission_amount=?,student_wechat_name=?,student_mobile=?,student_address=?,payment_time=?,receipt_time=?,refund_time=?,refund_amount=?,remark=? WHERE id=?');
        $stmt->execute([$studentId, $courseId, $totalAmount, $totalAmount, $paidAmount, $payStatus, $paymentStage, $salesCommission, $sellerUserId, $sellerRole, $sellerCommission, $referrerId, $referrerCommission, $studentWechatName, $studentMobile, $studentAddress, $paymentTime, $receiptTime, $refundTime, $refundAmount, $orderRemark, $id]);
        if (in_array($payStatus, [1, 2], true)) {
            $pdo->prepare("UPDATE oa_student SET is_student=1, student_stage='active', follow_status='已报名', converted_at=COALESCE(converted_at,NOW()) WHERE id=?")->execute([$studentId]);
        }
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        auth_require_roles(['班主任', '财务']);
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        if (!order_accessible($pdo, $id)) {
            json_response(403, '无权删除该订单', null, 403);
        }
        $stmt = $pdo->prepare('DELETE FROM oa_order WHERE id=?');
        $stmt->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
