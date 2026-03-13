<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/profile_bootstrap.php';
require_once __DIR__ . '/business_bootstrap.php';

function students_scope(PDO $pdo, string $alias = 'oa_student'): array
{
    $role = auth_user_role();
    $uid = auth_user_id();
    $name = auth_user_name();

    if (auth_is_admin_like() || $role === '财务') {
        return ['sql' => '1=1', 'params' => []];
    }

    if ($role === '顾问') {
        return ['sql' => "({$alias}.owner_consultant_user_id = :uid OR {$alias}.consultant = :uname)", 'params' => [':uid' => $uid, ':uname' => $name]];
    }

    if ($role === '教练') {
        return ['sql' => "({$alias}.coach_user_id = :uid OR {$alias}.delivery_coach = :uname)", 'params' => [':uid' => $uid, ':uname' => $name]];
    }

    if ($role === '班主任') {
        return ['sql' => "{$alias}.headteacher_user_id = :uid", 'params' => [':uid' => $uid]];
    }

    if ($role === '部门经理') {
        $stmt = $pdo->prepare('SELECT department FROM oa_user WHERE id=? LIMIT 1');
        $stmt->execute([$uid]);
        $dept = trim((string)$stmt->fetchColumn());
        if ($dept === '') {
            return ['sql' => '1=0', 'params' => []];
        }
        return [
            'sql' => "EXISTS (SELECT 1 FROM oa_user u1 WHERE u1.id = {$alias}.owner_consultant_user_id AND u1.department = :dept)
                   OR EXISTS (SELECT 1 FROM oa_user u2 WHERE u2.id = {$alias}.headteacher_user_id AND u2.department = :dept)
                   OR EXISTS (SELECT 1 FROM oa_user u3 WHERE u3.id = {$alias}.coach_user_id AND u3.department = :dept)",
            'params' => [':dept' => $dept],
        ];
    }

    return ['sql' => '1=0', 'params' => []];
}

function student_accessible(PDO $pdo, int $id): bool
{
    $scope = students_scope($pdo, 's');
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM oa_student s WHERE s.id = :id AND (' . $scope['sql'] . ')');
    $params = array_merge([':id' => $id], $scope['params']);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn() > 0;
}

try {
    $pdo = get_db_connection();
    ensure_student_user_profile_columns($pdo);
    ensure_business_workflow_schema($pdo);
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        $where = [];
        $params = [];
        $keyword = trim((string)($_GET['keyword'] ?? ''));
        $status = trim((string)($_GET['follow_status'] ?? ''));
        $category = trim((string)($_GET['student_category'] ?? ''));

        $scope = students_scope($pdo, 'oa_student');
        $where[] = '(' . $scope['sql'] . ')';
        $params = array_merge($params, $scope['params']);

        if ($keyword !== '') {
            $where[] = '(name LIKE :kw OR wechat_name LIKE :kw OR wechat LIKE :kw OR phone LIKE :kw OR consultant LIKE :kw OR delivery_coach LIKE :kw)';
            $params[':kw'] = "%{$keyword}%";
        }
        if ($status !== '') {
            $where[] = 'follow_status = :follow_status';
            $params[':follow_status'] = $status;
        }

        if ($category !== '') {
            if ($category === 'lead') {
                $where[] = "(is_student = 0 OR student_stage = 'lead')";
            } elseif ($category === 'pending_payment') {
                $where[] = "EXISTS (SELECT 1 FROM oa_order oo WHERE oo.student_id = oa_student.id AND oo.pay_status = 0)";
            } elseif ($category === 'active') {
                $where[] = "(is_student = 1 OR student_stage = 'active')";
            }
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        if (paged_mode($_GET)) {
            $p = parse_pagination($_GET);
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM oa_student' . $whereSql);
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $sql = 'SELECT id, name, gender, birthday, phone, wechat_name, wechat, id_no, level, intention_level, follow_status, source, source_channel, miniapp_openid, is_student, student_stage, lead_registered_at, converted_at, owner_consultant_user_id, headteacher_user_id, coach_user_id, enrolled_courses, consultant, delivery_coach, address, remark, created_at FROM oa_student'
                . $whereSql . ' ORDER BY id DESC LIMIT :limit OFFSET :offset';
            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', $p['page_size'], PDO::PARAM_INT);
            $stmt->bindValue(':offset', $p['offset'], PDO::PARAM_INT);
            $stmt->execute();
            json_response(0, 'ok', [
                'items' => $stmt->fetchAll(),
                'pagination' => [
                    'page' => $p['page'],
                    'page_size' => $p['page_size'],
                    'total' => $total,
                ],
            ]);
        }

        $stmt = $pdo->prepare('SELECT id, name, gender, birthday, phone, wechat_name, wechat, id_no, level, intention_level, follow_status, source, source_channel, miniapp_openid, is_student, student_stage, lead_registered_at, converted_at, owner_consultant_user_id, headteacher_user_id, coach_user_id, enrolled_courses, consultant, delivery_coach, address, remark, created_at FROM oa_student' . $whereSql . ' ORDER BY id DESC');
        $stmt->execute($params);
        json_response(0, 'ok', $stmt->fetchAll());
    }

    if ($m === 'POST') {
        auth_require_roles(['顾问', '班主任', '教练']);

        $d = request_body();
        require_fields($d, ['wechat_name', 'phone']);

        $phone = trim((string)$d['phone']);
        $existsStmt = $pdo->prepare('SELECT id FROM oa_student WHERE phone = ? LIMIT 1');
        $existsStmt->execute([$phone]);
        if ($existsStmt->fetchColumn()) {
            json_response(409, '手机号已存在', null, 409);
        }

        $role = auth_user_role();
        $uid = auth_user_id();
        $uname = auth_user_name();

        $ownerConsultantId = (int)($d['owner_consultant_user_id'] ?? 0) ?: null;
        $headteacherUserId = (int)($d['headteacher_user_id'] ?? 0) ?: null;
        $coachUserId = (int)($d['coach_user_id'] ?? 0) ?: null;
        $consultant = trim((string)($d['consultant'] ?? ''));
        $deliveryCoach = trim((string)($d['delivery_coach'] ?? ''));

        if ($role === '顾问') {
            $ownerConsultantId = $uid;
            if ($consultant === '') $consultant = $uname;
        } elseif ($role === '班主任') {
            $headteacherUserId = $uid;
        } elseif ($role === '教练') {
            $coachUserId = $uid;
            if ($deliveryCoach === '') $deliveryCoach = $uname;
        }

        $stmt = $pdo->prepare('INSERT INTO oa_student(name, gender, birthday, phone, wechat_name, wechat, id_no, level, intention_level, follow_status, source, source_channel, miniapp_openid, is_student, student_stage, lead_registered_at, converted_at, owner_consultant_user_id, headteacher_user_id, coach_user_id, enrolled_courses, consultant, delivery_coach, address, remark) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            trim((string)($d['name'] ?? '')),
            trim((string)($d['gender'] ?? '')),
            normalize_date_or_empty($d['birthday'] ?? ''),
            $phone,
            trim((string)($d['wechat_name'] ?? '')),
            trim((string)($d['wechat'] ?? '')),
            trim((string)($d['id_no'] ?? '')),
            trim((string)($d['level'] ?? '')),
            trim((string)($d['intention_level'] ?? '')),
            trim((string)($d['follow_status'] ?? '')),
            trim((string)($d['source'] ?? '')),
            trim((string)($d['source_channel'] ?? '')),
            trim((string)($d['miniapp_openid'] ?? '')),
            (int)($d['is_student'] ?? 0),
            trim((string)($d['student_stage'] ?? ((int)($d['is_student'] ?? 0) === 1 ? 'active' : 'lead'))),
            trim((string)($d['lead_registered_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            trim((string)($d['converted_at'] ?? '')) ?: null,
            $ownerConsultantId,
            $headteacherUserId,
            $coachUserId,
            json_encode($d['enrolled_courses'] ?? [], JSON_UNESCAPED_UNICODE),
            $consultant,
            $deliveryCoach,
            trim((string)($d['address'] ?? '')),
            trim((string)($d['remark'] ?? '')),
        ]);
        json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
    }

    if ($m === 'PUT') {
        auth_require_roles(['顾问', '班主任', '教练']);

        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        if (!student_accessible($pdo, $id)) {
            json_response(403, '无权修改该学员', null, 403);
        }
        require_fields($d, ['wechat_name', 'phone']);

        $phone = trim((string)$d['phone']);
        $existsStmt = $pdo->prepare('SELECT id FROM oa_student WHERE phone = ? AND id <> ? LIMIT 1');
        $existsStmt->execute([$phone, $id]);
        if ($existsStmt->fetchColumn()) {
            json_response(409, '手机号已存在', null, 409);
        }

        $stmt = $pdo->prepare('UPDATE oa_student SET name=?, gender=?, birthday=?, phone=?, wechat_name=?, wechat=?, id_no=?, level=?, intention_level=?, follow_status=?, source=?, source_channel=?, miniapp_openid=?, is_student=?, student_stage=?, lead_registered_at=?, converted_at=?, owner_consultant_user_id=?, headteacher_user_id=?, coach_user_id=?, enrolled_courses=?, consultant=?, delivery_coach=?, address=?, remark=? WHERE id=?');
        $stmt->execute([
            trim((string)($d['name'] ?? '')),
            trim((string)($d['gender'] ?? '')),
            normalize_date_or_empty($d['birthday'] ?? ''),
            $phone,
            trim((string)($d['wechat_name'] ?? '')),
            trim((string)($d['wechat'] ?? '')),
            trim((string)($d['id_no'] ?? '')),
            trim((string)($d['level'] ?? '')),
            trim((string)($d['intention_level'] ?? '')),
            trim((string)($d['follow_status'] ?? '')),
            trim((string)($d['source'] ?? '')),
            trim((string)($d['source_channel'] ?? '')),
            trim((string)($d['miniapp_openid'] ?? '')),
            (int)($d['is_student'] ?? 0),
            trim((string)($d['student_stage'] ?? ((int)($d['is_student'] ?? 0) === 1 ? 'active' : 'lead'))),
            trim((string)($d['lead_registered_at'] ?? '')) ?: date('Y-m-d H:i:s'),
            trim((string)($d['converted_at'] ?? '')) ?: null,
            (int)($d['owner_consultant_user_id'] ?? 0) ?: null,
            (int)($d['headteacher_user_id'] ?? 0) ?: null,
            (int)($d['coach_user_id'] ?? 0) ?: null,
            json_encode($d['enrolled_courses'] ?? [], JSON_UNESCAPED_UNICODE),
            trim((string)($d['consultant'] ?? '')),
            trim((string)($d['delivery_coach'] ?? '')),
            trim((string)($d['address'] ?? '')),
            trim((string)($d['remark'] ?? '')),
            $id
        ]);
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        auth_require_roles(['班主任']);

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        if (!student_accessible($pdo, $id)) {
            json_response(403, '无权删除该学员', null, 403);
        }
        $stmt = $pdo->prepare('DELETE FROM oa_student WHERE id=?');
        $stmt->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
