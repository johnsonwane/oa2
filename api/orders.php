<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/order_referrer_bootstrap.php';
require_once __DIR__ . '/business_bootstrap.php';

try {
    $pdo = get_db_connection();
    ensure_order_referrer_schema($pdo);
    ensure_business_workflow_schema($pdo);
    ensure_table_columns($pdo, 'oa_user', [
        'real_name' => "`real_name` VARCHAR(50) DEFAULT ''",
        'role' => "`role` VARCHAR(30) DEFAULT ''",
    ]);
    ensure_table_columns($pdo, 'oa_student', [
        'name' => "`name` VARCHAR(50) DEFAULT ''",
        'delivery_coach' => "`delivery_coach` VARCHAR(50) DEFAULT ''",
    ]);
    ensure_table_columns($pdo, 'oa_order', [
        'amount' => "`amount` DECIMAL(10,2) NOT NULL DEFAULT 0",
        'pay_status' => "`pay_status` TINYINT NOT NULL DEFAULT 0",
        'created_at' => "`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
        'student_wechat_name' => "`student_wechat_name` VARCHAR(80) DEFAULT ''",
        'student_mobile' => "`student_mobile` VARCHAR(20) DEFAULT ''",
        'student_address' => "`student_address` VARCHAR(255) DEFAULT ''",
        'payment_time' => "`payment_time` DATETIME DEFAULT NULL",
        'receipt_time' => "`receipt_time` DATETIME DEFAULT NULL",
        'refund_time' => "`refund_time` DATETIME DEFAULT NULL",
        'refund_amount' => "`refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0",
        'remark' => "`remark` VARCHAR(255) DEFAULT ''",
    ]);
    $m = $_SERVER['REQUEST_METHOD'];

    $hasCol = function (string $table, string $column) use ($pdo): bool {
        try {
            return function_exists('column_exists') ? column_exists($pdo, $table, $column) : true;
        } catch (Throwable $e) {
            return false;
        }
    };
    $hasTable = function (string $table) use ($pdo): bool {
        try {
            return function_exists('table_exists') ? table_exists($pdo, $table) : true;
        } catch (Throwable $e) {
            return false;
        }
    };

    if ($m === 'GET') {
        $where = [];
        $params = [];
        $payStatus = isset($_GET['pay_status']) ? trim((string)$_GET['pay_status']) : '';
        $keyword = trim((string)($_GET['keyword'] ?? ''));
        $paymentStage = trim((string)($_GET['payment_stage'] ?? ''));
        $userId = (int)($_GET['user_id'] ?? 0);

        if ($payStatus !== '' && $hasCol('oa_order', 'pay_status')) {
            $where[] = 'o.pay_status = :pay_status';
            $params[':pay_status'] = (int)$payStatus;
        }
        if ($paymentStage !== '' && $hasCol('oa_order', 'payment_stage')) {
            $where[] = 'o.payment_stage = :payment_stage';
            $params[':payment_stage'] = $paymentStage;
        }

        if ($userId > 0) {
            $uStmt = $pdo->prepare('SELECT role, real_name FROM oa_user WHERE id=? LIMIT 1');
            $uStmt->execute([$userId]);
            $u = $uStmt->fetch();
            if ($u) {
                $roleName = trim((string)($u['role'] ?? ''));
                $realName = trim((string)($u['real_name'] ?? ''));
                if ($roleName === '顾问') {
                    $where[] = '1=0';
                } elseif ($roleName === '教练' && $hasCol('oa_student', 'delivery_coach')) {
                    $where[] = 's.delivery_coach = :coach_name';
                    $params[':coach_name'] = $realName;
                }
            }
        }

        $joinReferrer = $hasTable('oa_referrer') && $hasCol('oa_order', 'referrer_id');
        $joinSeller = $hasTable('oa_user') && $hasCol('oa_order', 'seller_user_id');
        if ($keyword !== '') {
            $keywordWhere = '(s.name LIKE :kw OR c.course_name LIKE :kw';
            if ($joinReferrer) {
                $keywordWhere .= ' OR r.name LIKE :kw';
            }
            $keywordWhere .= ')';
            $where[] = $keywordWhere;
            $params[':kw'] = "%{$keyword}%";
        }
        $baseSql = ' FROM oa_order o LEFT JOIN oa_student s ON s.id=o.student_id LEFT JOIN oa_course c ON c.id=o.course_id';
        if ($joinReferrer) {
            $baseSql .= ' LEFT JOIN oa_referrer r ON r.id=o.referrer_id';
        }
        if ($joinSeller) {
            $baseSql .= ' LEFT JOIN oa_user su ON su.id=o.seller_user_id';
        }
        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';
        $selectSql = 'SELECT '
            . 'o.id,o.student_id,' . ($hasCol('oa_student', 'name') ? 's.name' : "''") . ' student_name,o.course_id,c.course_name,'
            . ($hasCol('oa_order', 'amount') ? 'o.amount' : '0') . ' amount,'
            . ($hasCol('oa_order', 'total_amount') ? 'o.total_amount' : '0') . ' total_amount,'
            . ($hasCol('oa_order', 'paid_amount') ? 'o.paid_amount' : '0') . ' paid_amount,'
            . ($hasCol('oa_order', 'pay_status') ? 'o.pay_status' : '0') . ' pay_status,'
            . ($hasCol('oa_order', 'payment_stage') ? 'o.payment_stage' : "'full'") . ' payment_stage,'
            . ($hasCol('oa_order', 'sales_commission_amount') ? 'o.sales_commission_amount' : '0') . ' sales_commission_amount,'
            . ($hasCol('oa_order', 'seller_user_id') ? 'o.seller_user_id' : 'NULL') . ' seller_user_id,'
            . ($hasCol('oa_order', 'seller_role') ? 'o.seller_role' : "''") . ' seller_role,'
            . ($hasCol('oa_order', 'seller_commission_amount') ? 'o.seller_commission_amount' : '0') . ' seller_commission_amount,'
            . (($joinSeller && $hasCol('oa_user', 'real_name')) ? 'su.real_name' : "''") . ' seller_name,'
            . ($hasCol('oa_order', 'referrer_id') ? 'o.referrer_id' : 'NULL') . ' referrer_id,'
            . ($hasCol('oa_order', 'referrer_commission_amount') ? 'o.referrer_commission_amount' : '0') . ' referrer_commission_amount,'
            . (($joinReferrer && $hasCol('oa_referrer', 'name')) ? 'r.name' : "''") . ' referrer_name,'
            . ($hasCol('oa_order', 'student_wechat_name') ? 'o.student_wechat_name' : "''") . ' student_wechat_name,'
            . ($hasCol('oa_order', 'student_mobile') ? 'o.student_mobile' : "''") . ' student_mobile,'
            . ($hasCol('oa_order', 'student_address') ? 'o.student_address' : "''") . ' student_address,'
            . ($hasCol('oa_order', 'payment_time') ? 'o.payment_time' : 'NULL') . ' payment_time,'
            . ($hasCol('oa_order', 'receipt_time') ? 'o.receipt_time' : 'NULL') . ' receipt_time,'
            . ($hasCol('oa_order', 'refund_time') ? 'o.refund_time' : 'NULL') . ' refund_time,'
            . ($hasCol('oa_order', 'refund_amount') ? 'o.refund_amount' : '0') . ' refund_amount,'
            . ($hasCol('oa_order', 'remark') ? 'o.remark' : "''") . ' remark,'
            . ($hasCol('oa_order', 'created_at') ? 'o.created_at' : 'NULL') . ' created_at';

        if (paged_mode($_GET)) {
            $p = parse_pagination($_GET);
            $countStmt = $pdo->prepare('SELECT COUNT(*)' . $baseSql . $whereSql);
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $sql = $selectSql . $baseSql . $whereSql . ' ORDER BY o.id DESC LIMIT :limit OFFSET :offset';
            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', $p['page_size'], PDO::PARAM_INT);
            $stmt->bindValue(':offset', $p['offset'], PDO::PARAM_INT);
            $stmt->execute();
            json_response(0, 'ok', [
                'items' => $stmt->fetchAll(),
                'pagination' => ['page' => $p['page'], 'page_size' => $p['page_size'], 'total' => $total],
            ]);
        }

        $stmt = $pdo->prepare($selectSql . $baseSql . $whereSql . ' ORDER BY o.id DESC');
        $stmt->execute($params);
        json_response(0, 'ok', $stmt->fetchAll());
    }

    if ($m === 'POST' || $m === 'PUT') {
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($m === 'PUT' && $id <= 0) {
            json_response(400, 'id非法', null, 400);
        }

        require_fields($d, ['student_id', 'course_id']);
        $studentId = (int)$d['student_id'];
        $courseId = (int)$d['course_id'];
        if ($studentId <= 0 || $courseId <= 0) {
            json_response(400, 'student_id 或 course_id 非法', null, 400);
        }

        $payStatus = (int)($d['pay_status'] ?? 0);
        if (!in_array($payStatus, [0, 1], true)) {
            json_response(400, 'pay_status 只能为 0 或 1', null, 400);
        }

        $paymentStage = trim((string)($d['payment_stage'] ?? 'full'));
        $allowedStages = ['deposit', 'middle', 'final', 'full'];
        if (!in_array($paymentStage, $allowedStages, true)) {
            json_response(400, 'payment_stage 非法', null, 400);
        }

        $studentCheck = $pdo->prepare('SELECT id FROM oa_student WHERE id=? LIMIT 1');
        $studentCheck->execute([$studentId]);
        if (!$studentCheck->fetchColumn()) {
            json_response(404, '学员不存在', null, 404);
        }

        $courseStmt = $pdo->prepare('SELECT id, price FROM oa_course WHERE id=? LIMIT 1');
        $courseStmt->execute([$courseId]);
        $course = $courseStmt->fetch();
        if (!$course) {
            json_response(404, '课程不存在', null, 404);
        }

        $totalAmount = isset($d['total_amount']) && $d['total_amount'] !== '' ? (float)$d['total_amount'] : (float)$course['price'];
        if ($totalAmount < 0) {
            json_response(400, 'total_amount 不能为负数', null, 400);
        }

        $paidAmount = isset($d['paid_amount']) && $d['paid_amount'] !== '' ? (float)$d['paid_amount'] : $totalAmount;
        if ($paidAmount < 0) {
            json_response(400, 'paid_amount 不能为负数', null, 400);
        }

        $sellerUserId = (int)($d['seller_user_id'] ?? 0);
        $sellerUserId = $sellerUserId > 0 ? $sellerUserId : null;
        $sellerRole = trim((string)($d['seller_role'] ?? ''));
        if ($sellerUserId !== null) {
            $suStmt = $pdo->prepare('SELECT id, role FROM oa_user WHERE id=? LIMIT 1');
            $suStmt->execute([$sellerUserId]);
            $su = $suStmt->fetch();
            if (!$su) {
                json_response(404, '销售归属用户不存在', null, 404);
            }
            if ($sellerRole === '') {
                $sellerRole = trim((string)($su['role'] ?? ''));
            }
        }

        $defaultRate = 0.10;
        if ($sellerRole === '教练') {
            $defaultRate = 0.08;
        } elseif ($sellerRole === '顾问') {
            $defaultRate = 0.10;
        } elseif ($sellerRole === '班主任') {
            $defaultRate = 0.06;
        }

        $salesCommission = isset($d['sales_commission_amount']) && $d['sales_commission_amount'] !== ''
            ? (float)$d['sales_commission_amount']
            : round($paidAmount * $defaultRate, 2);
        if ($salesCommission < 0) {
            json_response(400, 'sales_commission_amount 不能为负数', null, 400);
        }
        $sellerCommission = isset($d['seller_commission_amount']) && $d['seller_commission_amount'] !== ''
            ? (float)$d['seller_commission_amount']
            : $salesCommission;
        if ($sellerCommission < 0) {
            json_response(400, 'seller_commission_amount 不能为负数', null, 400);
        }

        $referrerId = (int)($d['referrer_id'] ?? 0);
        $referrerId = $referrerId > 0 ? $referrerId : null;
        $referrerRate = 0.0;
        if ($referrerId !== null) {
            $refStmt = $pdo->prepare('SELECT id, commission_type, commission_rate, fixed_amount FROM oa_referrer WHERE id=? AND status=1 LIMIT 1');
            $refStmt->execute([$referrerId]);
            $ref = $refStmt->fetch();
            if (!$ref) {
                json_response(404, '推荐者不存在或已禁用', null, 404);
            }
            $referrerRate = (float)$ref['commission_rate'];
        }

        $referrerCommission = isset($d['referrer_commission_amount']) && $d['referrer_commission_amount'] !== ''
            ? (float)$d['referrer_commission_amount']
            : (($referrerId !== null && (($ref['commission_type'] ?? 'rate') === 'fixed')) ? (float)($ref['fixed_amount'] ?? 0) : round($paidAmount * $referrerRate / 100, 2));
        if ($referrerCommission < 0) {
            json_response(400, 'referrer_commission_amount 不能为负数', null, 400);
        }
        if ($referrerId === null) {
            $referrerCommission = 0.0;
        }


        $studentWechatName = trim((string)($d['student_wechat_name'] ?? ''));
        $studentMobile = trim((string)($d['student_mobile'] ?? ''));
        $studentAddress = trim((string)($d['student_address'] ?? ''));
        $paymentTime = trim((string)($d['payment_time'] ?? '')) ?: null;
        $receiptTime = trim((string)($d['receipt_time'] ?? '')) ?: null;
        $refundTime = trim((string)($d['refund_time'] ?? '')) ?: null;
        $refundAmount = (float)($d['refund_amount'] ?? 0);
        if ($refundAmount < 0) {
            json_response(400, 'refund_amount 不能为负数', null, 400);
        }
        $orderRemark = trim((string)($d['remark'] ?? ''));

        if ($m === 'POST') {
            $stmt = $pdo->prepare('INSERT INTO oa_order(student_id,course_id,amount,total_amount,paid_amount,pay_status,payment_stage,sales_commission_amount,seller_user_id,seller_role,seller_commission_amount,referrer_id,referrer_commission_amount,student_wechat_name,student_mobile,student_address,payment_time,receipt_time,refund_time,refund_amount,remark) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$studentId, $courseId, $totalAmount, $totalAmount, $paidAmount, $payStatus, $paymentStage, $salesCommission, $sellerUserId, $sellerRole, $sellerCommission, $referrerId, $referrerCommission, $studentWechatName, $studentMobile, $studentAddress, $paymentTime, $receiptTime, $refundTime, $refundAmount, $orderRemark]);
            if ($payStatus === 1) {
                $stu = $pdo->prepare("UPDATE oa_student SET is_student=1, student_stage='active', follow_status='已报名', converted_at=NOW() WHERE id=?");
                $stu->execute([$studentId]);
            }
            json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
        }

        $stmt = $pdo->prepare('UPDATE oa_order SET student_id=?,course_id=?,amount=?,total_amount=?,paid_amount=?,pay_status=?,payment_stage=?,sales_commission_amount=?,seller_user_id=?,seller_role=?,seller_commission_amount=?,referrer_id=?,referrer_commission_amount=?,student_wechat_name=?,student_mobile=?,student_address=?,payment_time=?,receipt_time=?,refund_time=?,refund_amount=?,remark=? WHERE id=?');
        $stmt->execute([$studentId, $courseId, $totalAmount, $totalAmount, $paidAmount, $payStatus, $paymentStage, $salesCommission, $sellerUserId, $sellerRole, $sellerCommission, $referrerId, $referrerCommission, $studentWechatName, $studentMobile, $studentAddress, $paymentTime, $receiptTime, $refundTime, $refundAmount, $orderRemark, $id]);
        if ($payStatus === 1) {
            $stu = $pdo->prepare("UPDATE oa_student SET is_student=1, student_stage='active', follow_status='已报名', converted_at=COALESCE(converted_at,NOW()) WHERE id=?");
            $stu->execute([$studentId]);
        }
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $stmt = $pdo->prepare('DELETE FROM oa_order WHERE id=?');
        $stmt->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
