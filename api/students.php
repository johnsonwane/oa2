<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/profile_bootstrap.php';
require_once __DIR__ . '/business_bootstrap.php';

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
        $userId = (int)($_GET['user_id'] ?? 0);

        if ($keyword !== '') {
            $where[] = '(name LIKE :kw OR phone LIKE :kw OR consultant LIKE :kw OR delivery_coach LIKE :kw)';
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

        if ($userId > 0) {
            $uStmt = $pdo->prepare('SELECT role, real_name FROM oa_user WHERE id=? LIMIT 1');
            $uStmt->execute([$userId]);
            $u = $uStmt->fetch();
            if ($u) {
                $roleName = trim((string)($u['role'] ?? ''));
                $realName = trim((string)($u['real_name'] ?? ''));
                if ($roleName === '顾问') {
                    $where[] = "(follow_status <> '已报名' OR follow_status = '' OR follow_status IS NULL)";
                } elseif ($roleName === '教练') {
                    $where[] = 'delivery_coach = :coach_name';
                    $params[':coach_name'] = $realName;
                }
            }
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        if (paged_mode($_GET)) {
            $p = parse_pagination($_GET);
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM oa_student' . $whereSql);
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $sql = 'SELECT id, name, gender, birthday, phone, wechat, id_no, level, intention_level, follow_status, source, source_channel, miniapp_openid, is_student, student_stage, lead_registered_at, converted_at, owner_consultant_user_id, headteacher_user_id, coach_user_id, enrolled_courses, consultant, delivery_coach, guardian_name, guardian_phone, address, remark, created_at FROM oa_student'
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

        $stmt = $pdo->prepare('SELECT id, name, gender, birthday, phone, wechat, id_no, level, intention_level, follow_status, source, source_channel, miniapp_openid, is_student, student_stage, lead_registered_at, converted_at, owner_consultant_user_id, headteacher_user_id, coach_user_id, enrolled_courses, consultant, delivery_coach, guardian_name, guardian_phone, address, remark, created_at FROM oa_student' . $whereSql . ' ORDER BY id DESC');
        $stmt->execute($params);
        json_response(0, 'ok', $stmt->fetchAll());
    }

    if ($m === 'POST') {
        $d = request_body();
        require_fields($d, ['wechat', 'phone']);

        $phone = trim($d['phone']);
        $existsStmt = $pdo->prepare('SELECT id FROM oa_student WHERE phone = ? LIMIT 1');
        $existsStmt->execute([$phone]);
        if ($existsStmt->fetchColumn()) {
            json_response(409, '手机号已存在', null, 409);
        }

        $stmt = $pdo->prepare('INSERT INTO oa_student(name, gender, birthday, phone, wechat, id_no, level, intention_level, follow_status, source, source_channel, miniapp_openid, is_student, student_stage, lead_registered_at, converted_at, owner_consultant_user_id, headteacher_user_id, coach_user_id, enrolled_courses, consultant, delivery_coach, guardian_name, guardian_phone, address, remark) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            trim((string)($d['name'] ?? '')),
            trim((string)($d['gender'] ?? '')),
            normalize_date_or_empty($d['birthday'] ?? ''),
            $phone,
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
            trim((string)($d['guardian_name'] ?? '')),
            trim((string)($d['guardian_phone'] ?? '')),
            trim((string)($d['address'] ?? '')),
            trim((string)($d['remark'] ?? '')),
        ]);
        json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
    }

    if ($m === 'PUT') {
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        require_fields($d, ['wechat', 'phone']);

        $phone = trim($d['phone']);
        $existsStmt = $pdo->prepare('SELECT id FROM oa_student WHERE phone = ? AND id <> ? LIMIT 1');
        $existsStmt->execute([$phone, $id]);
        if ($existsStmt->fetchColumn()) {
            json_response(409, '手机号已存在', null, 409);
        }

        $stmt = $pdo->prepare('UPDATE oa_student SET name=?, gender=?, birthday=?, phone=?, wechat=?, id_no=?, level=?, intention_level=?, follow_status=?, source=?, source_channel=?, miniapp_openid=?, is_student=?, student_stage=?, lead_registered_at=?, converted_at=?, owner_consultant_user_id=?, headteacher_user_id=?, coach_user_id=?, enrolled_courses=?, consultant=?, delivery_coach=?, guardian_name=?, guardian_phone=?, address=?, remark=? WHERE id=?');
        $stmt->execute([
            trim((string)($d['name'] ?? '')),
            trim((string)($d['gender'] ?? '')),
            normalize_date_or_empty($d['birthday'] ?? ''),
            $phone,
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
            trim((string)($d['guardian_name'] ?? '')),
            trim((string)($d['guardian_phone'] ?? '')),
            trim((string)($d['address'] ?? '')),
            trim((string)($d['remark'] ?? '')),
            $id
        ]);
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $stmt = $pdo->prepare('DELETE FROM oa_student WHERE id=?');
        $stmt->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
