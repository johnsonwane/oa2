<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';

try {
    $pdo = get_db_connection();
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        $where = [];
        $params = [];
        $payStatus = isset($_GET['pay_status']) ? trim((string)$_GET['pay_status']) : '';
        $keyword = trim((string)($_GET['keyword'] ?? ''));

        if ($payStatus !== '') {
            $where[] = 'o.pay_status = :pay_status';
            $params[':pay_status'] = (int)$payStatus;
        }
        if ($keyword !== '') {
            $where[] = '(s.name LIKE :kw OR c.course_name LIKE :kw)';
            $params[':kw'] = "%{$keyword}%";
        }

        $baseSql = ' FROM oa_order o LEFT JOIN oa_student s ON s.id=o.student_id LEFT JOIN oa_course c ON c.id=o.course_id';
        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        if (paged_mode($_GET)) {
            $p = parse_pagination($_GET);
            $countStmt = $pdo->prepare('SELECT COUNT(*)' . $baseSql . $whereSql);
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $sql = 'SELECT o.id,o.student_id,s.name student_name,o.course_id,c.course_name,o.amount,o.pay_status,o.created_at'
                . $baseSql . $whereSql . ' ORDER BY o.id DESC LIMIT :limit OFFSET :offset';
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

        $sql = 'SELECT o.id,o.student_id,s.name student_name,o.course_id,c.course_name,o.amount,o.pay_status,o.created_at'
            . $baseSql . $whereSql . ' ORDER BY o.id DESC';
        $stmt = $pdo->prepare($sql);
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

        $amount = isset($d['amount']) && $d['amount'] !== '' ? (float)$d['amount'] : (float)$course['price'];
        if ($amount < 0) {
            json_response(400, 'amount 不能为负数', null, 400);
        }

        if ($m === 'POST') {
            $stmt = $pdo->prepare('INSERT INTO oa_order(student_id,course_id,amount,pay_status) VALUES(?,?,?,?)');
            $stmt->execute([$studentId, $courseId, $amount, $payStatus]);
            json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
        }

        $stmt = $pdo->prepare('UPDATE oa_order SET student_id=?,course_id=?,amount=?,pay_status=? WHERE id=?');
        $stmt->execute([$studentId, $courseId, $amount, $payStatus, $id]);
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
