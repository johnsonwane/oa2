<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/order_referrer_bootstrap.php';
require_once __DIR__ . '/business_bootstrap.php';

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
        $userId = (int)($_GET['user_id'] ?? 0);

        if ($payStatus !== '') {
            $where[] = 'o.pay_status = :pay_status';
            $params[':pay_status'] = (int)$payStatus;
        }
        if ($keyword !== '') {
            $where[] = '(s.name LIKE :kw OR c.course_name LIKE :kw OR r.name LIKE :kw)';
            $params[':kw'] = "%{$keyword}%";
        }
        if ($paymentStage !== '') {
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
                } elseif ($roleName === '教练') {
                    $where[] = 's.delivery_coach = :coach_name';
                    $params[':coach_name'] = $realName;
                }
            }
        }

        $baseSql = ' FROM oa_order o LEFT JOIN oa_student s ON s.id=o.student_id LEFT JOIN oa_course c ON c.id=o.course_id LEFT JOIN oa_referrer r ON r.id=o.referrer_id';
        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';
        $selectSql = 'SELECT o.id,o.student_id,s.name student_name,o.course_id,c.course_name,o.amount,o.total_amount,o.paid_amount,o.pay_status,o.payment_stage,o.sales_commission_amount,o.referrer_id,o.referrer_commission_amount,r.name referrer_name,o.created_at';

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

        $salesCommission = isset($d['sales_commission_amount']) && $d['sales_commission_amount'] !== ''
            ? (float)$d['sales_commission_amount']
            : round($paidAmount * 0.10, 2);
        if ($salesCommission < 0) {
            json_response(400, 'sales_commission_amount 不能为负数', null, 400);
        }

        $referrerId = (int)($d['referrer_id'] ?? 0);
        $referrerId = $referrerId > 0 ? $referrerId : null;
        $referrerRate = 0.0;
        if ($referrerId !== null) {
            $refStmt = $pdo->prepare('SELECT id, commission_rate FROM oa_referrer WHERE id=? AND status=1 LIMIT 1');
            $refStmt->execute([$referrerId]);
            $ref = $refStmt->fetch();
            if (!$ref) {
                json_response(404, '推荐者不存在或已禁用', null, 404);
            }
            $referrerRate = (float)$ref['commission_rate'];
        }

        $referrerCommission = isset($d['referrer_commission_amount']) && $d['referrer_commission_amount'] !== ''
            ? (float)$d['referrer_commission_amount']
            : round($paidAmount * $referrerRate / 100, 2);
        if ($referrerCommission < 0) {
            json_response(400, 'referrer_commission_amount 不能为负数', null, 400);
        }
        if ($referrerId === null) {
            $referrerCommission = 0.0;
        }

        if ($m === 'POST') {
            $stmt = $pdo->prepare('INSERT INTO oa_order(student_id,course_id,amount,total_amount,paid_amount,pay_status,payment_stage,sales_commission_amount,referrer_id,referrer_commission_amount) VALUES(?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$studentId, $courseId, $totalAmount, $totalAmount, $paidAmount, $payStatus, $paymentStage, $salesCommission, $referrerId, $referrerCommission]);
            if ($payStatus === 1) {
                $stu = $pdo->prepare("UPDATE oa_student SET is_student=1, follow_status='已报名', converted_at=NOW() WHERE id=?");
                $stu->execute([$studentId]);
            }
            json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
        }

        $stmt = $pdo->prepare('UPDATE oa_order SET student_id=?,course_id=?,amount=?,total_amount=?,paid_amount=?,pay_status=?,payment_stage=?,sales_commission_amount=?,referrer_id=?,referrer_commission_amount=? WHERE id=?');
        $stmt->execute([$studentId, $courseId, $totalAmount, $totalAmount, $paidAmount, $payStatus, $paymentStage, $salesCommission, $referrerId, $referrerCommission, $id]);
        if ($payStatus === 1) {
            $stu = $pdo->prepare("UPDATE oa_student SET is_student=1, follow_status='已报名', converted_at=COALESCE(converted_at,NOW()) WHERE id=?");
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
