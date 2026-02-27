<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/profile_bootstrap.php';

try {
    $pdo = get_db_connection();
    ensure_student_user_profile_columns($pdo);
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        $where = [];
        $params = [];
        $keyword = trim((string)($_GET['keyword'] ?? ''));
        $role = trim((string)($_GET['role'] ?? ''));
        $status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';

        if ($keyword !== '') {
            $where[] = '(username LIKE :kw OR real_name LIKE :kw OR mobile LIKE :kw OR email LIKE :kw OR department LIKE :kw)';
            $params[':kw'] = "%{$keyword}%";
        }
        if ($role !== '') {
            $where[] = 'role = :role';
            $params[':role'] = $role;
        }
        if ($status !== '') {
            $where[] = 'status = :status';
            $params[':status'] = (int)$status;
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';
        $select = 'SELECT id, username, real_name, role, gender, mobile, email, id_no, department, position, hire_date, last_login_at, remark, status, created_at FROM oa_user';

        if (paged_mode($_GET)) {
            $p = parse_pagination($_GET);
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM oa_user' . $whereSql);
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $stmt = $pdo->prepare($select . $whereSql . ' ORDER BY id ASC LIMIT :limit OFFSET :offset');
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

        $stmt = $pdo->prepare($select . $whereSql . ' ORDER BY id ASC');
        $stmt->execute($params);
        json_response(0, 'ok', $stmt->fetchAll());
    }

    if ($m === 'POST') {
        $d = request_body();
        require_fields($d, ['username', 'real_name', 'role', 'password']);

        $username = trim((string)$d['username']);
        $dupStmt = $pdo->prepare('SELECT id FROM oa_user WHERE username=? LIMIT 1');
        $dupStmt->execute([$username]);
        if ($dupStmt->fetchColumn()) {
            json_response(409, '用户名已存在', null, 409);
        }

        $stmt = $pdo->prepare('INSERT INTO oa_user(username,password_hash,real_name,role,gender,mobile,email,id_no,department,position,hire_date,remark,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $username,
            password_hash((string)$d['password'], PASSWORD_BCRYPT),
            trim($d['real_name']),
            trim($d['role']),
            trim((string)($d['gender'] ?? '')),
            trim((string)($d['mobile'] ?? '')),
            trim((string)($d['email'] ?? '')),
            trim((string)($d['id_no'] ?? '')),
            trim((string)($d['department'] ?? '')),
            trim((string)($d['position'] ?? '')),
            normalize_date_or_empty($d['hire_date'] ?? ''),
            trim((string)($d['remark'] ?? '')),
            (int)($d['status'] ?? 1)
        ]);
        json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
    }

    if ($m === 'PUT') {
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        require_fields($d, ['real_name', 'role']);
        $stmt = $pdo->prepare('UPDATE oa_user SET real_name=?, role=?, gender=?, mobile=?, email=?, id_no=?, department=?, position=?, hire_date=?, remark=?, status=? WHERE id=?');
        $stmt->execute([
            trim($d['real_name']),
            trim($d['role']),
            trim((string)($d['gender'] ?? '')),
            trim((string)($d['mobile'] ?? '')),
            trim((string)($d['email'] ?? '')),
            trim((string)($d['id_no'] ?? '')),
            trim((string)($d['department'] ?? '')),
            trim((string)($d['position'] ?? '')),
            normalize_date_or_empty($d['hire_date'] ?? ''),
            trim((string)($d['remark'] ?? '')),
            (int)($d['status'] ?? 1),
            $id
        ]);
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $stmt = $pdo->prepare('DELETE FROM oa_user WHERE id=?');
        $stmt->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
