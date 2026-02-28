<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/profile_bootstrap.php';
require_once __DIR__ . '/hr_bootstrap.php';

try {
    $pdo = get_db_connection();
    ensure_student_user_profile_columns($pdo);
    ensure_hr_schema($pdo);
    $m = $_SERVER['REQUEST_METHOD'];

    $select = 'SELECT id, username, employee_no, company_name, real_name, role, gender, mobile, email, id_no, department, position, birthday, birth_date, age, social_city, hukou_place, hukou_type, native_place, ethnicity, marital_status, home_address, emergency_contact, education, graduation_school, major, hire_date, contract_years, working_days, contract_end_date, is_probation, probation_salary, regular_date, regular_salary, bank_name, bank_card_no, salary_adjust_records, employment_status, leave_date, last_login_at, remark, status, created_at FROM oa_user';

    if ($m === 'GET') {
        $where = [];
        $params = [];
        $keyword = trim((string)($_GET['keyword'] ?? ''));
        $role = trim((string)($_GET['role'] ?? ''));
        $status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';

        if ($keyword !== '') {
            $where[] = '(employee_no LIKE :kw OR username LIKE :kw OR real_name LIKE :kw OR mobile LIKE :kw OR email LIKE :kw OR department LIKE :kw)';
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
        if (paged_mode($_GET)) {
            $p = parse_pagination($_GET);
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM oa_user' . $whereSql);
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();
            $stmt = $pdo->prepare($select . $whereSql . ' ORDER BY id ASC LIMIT :limit OFFSET :offset');
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->bindValue(':limit', $p['page_size'], PDO::PARAM_INT);
            $stmt->bindValue(':offset', $p['offset'], PDO::PARAM_INT);
            $stmt->execute();
            json_response(0, 'ok', ['items' => $stmt->fetchAll(), 'pagination' => ['page' => $p['page'], 'page_size' => $p['page_size'], 'total' => $total]]);
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
        if ($dupStmt->fetchColumn()) json_response(409, '用户名已存在', null, 409);

        $stmt = $pdo->prepare('INSERT INTO oa_user(username,password_hash,employee_no,company_name,real_name,role,gender,mobile,email,id_no,department,position,birthday,birth_date,age,social_city,hukou_place,hukou_type,native_place,ethnicity,marital_status,home_address,emergency_contact,education,graduation_school,major,hire_date,contract_years,working_days,contract_end_date,is_probation,probation_salary,regular_date,regular_salary,bank_name,bank_card_no,salary_adjust_records,employment_status,leave_date,remark,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $username,
            password_hash((string)$d['password'], PASSWORD_BCRYPT),
            trim((string)($d['employee_no'] ?? '')),
            trim((string)($d['company_name'] ?? '')),
            trim((string)$d['real_name']),
            trim((string)$d['role']),
            trim((string)($d['gender'] ?? '')),
            trim((string)($d['mobile'] ?? '')),
            trim((string)($d['email'] ?? '')),
            trim((string)($d['id_no'] ?? '')),
            trim((string)($d['department'] ?? '')),
            trim((string)($d['position'] ?? '')),
            normalize_date_or_empty($d['birthday'] ?? ''),
            normalize_date_or_empty($d['birth_date'] ?? ''),
            (int)($d['age'] ?? 0) ?: null,
            trim((string)($d['social_city'] ?? '')),
            trim((string)($d['hukou_place'] ?? '')),
            trim((string)($d['hukou_type'] ?? '')),
            trim((string)($d['native_place'] ?? '')),
            trim((string)($d['ethnicity'] ?? '')),
            trim((string)($d['marital_status'] ?? '')),
            trim((string)($d['home_address'] ?? '')),
            trim((string)($d['emergency_contact'] ?? '')),
            trim((string)($d['education'] ?? '')),
            trim((string)($d['graduation_school'] ?? '')),
            trim((string)($d['major'] ?? '')),
            normalize_date_or_empty($d['hire_date'] ?? ''),
            trim((string)($d['contract_years'] ?? '')),
            (int)($d['working_days'] ?? 0),
            normalize_date_or_empty($d['contract_end_date'] ?? ''),
            (int)($d['is_probation'] ?? 1),
            (float)($d['probation_salary'] ?? 0),
            normalize_date_or_empty($d['regular_date'] ?? ''),
            (float)($d['regular_salary'] ?? 0),
            trim((string)($d['bank_name'] ?? '')),
            trim((string)($d['bank_card_no'] ?? '')),
            trim((string)($d['salary_adjust_records'] ?? '')),
            trim((string)($d['employment_status'] ?? '在职')),
            normalize_date_or_empty($d['leave_date'] ?? ''),
            trim((string)($d['remark'] ?? '')),
            (int)($d['status'] ?? 1),
        ]);
        json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
    }

    if ($m === 'PUT') {
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        require_fields($d, ['username', 'real_name', 'role']);

        $username = trim((string)$d['username']);
        $dupStmt = $pdo->prepare('SELECT id FROM oa_user WHERE username=? AND id<>? LIMIT 1');
        $dupStmt->execute([$username, $id]);
        if ($dupStmt->fetchColumn()) json_response(409, '用户名已存在', null, 409);

        $stmt = $pdo->prepare('UPDATE oa_user SET username=?,employee_no=?,company_name=?,real_name=?,role=?,gender=?,mobile=?,email=?,id_no=?,department=?,position=?,birthday=?,birth_date=?,age=?,social_city=?,hukou_place=?,hukou_type=?,native_place=?,ethnicity=?,marital_status=?,home_address=?,emergency_contact=?,education=?,graduation_school=?,major=?,hire_date=?,contract_years=?,working_days=?,contract_end_date=?,is_probation=?,probation_salary=?,regular_date=?,regular_salary=?,bank_name=?,bank_card_no=?,salary_adjust_records=?,employment_status=?,leave_date=?,remark=?,status=? WHERE id=?');
        $stmt->execute([
            $username,
            trim((string)($d['employee_no'] ?? '')),
            trim((string)($d['company_name'] ?? '')),
            trim((string)$d['real_name']),
            trim((string)$d['role']),
            trim((string)($d['gender'] ?? '')),
            trim((string)($d['mobile'] ?? '')),
            trim((string)($d['email'] ?? '')),
            trim((string)($d['id_no'] ?? '')),
            trim((string)($d['department'] ?? '')),
            trim((string)($d['position'] ?? '')),
            normalize_date_or_empty($d['birthday'] ?? ''),
            normalize_date_or_empty($d['birth_date'] ?? ''),
            (int)($d['age'] ?? 0) ?: null,
            trim((string)($d['social_city'] ?? '')),
            trim((string)($d['hukou_place'] ?? '')),
            trim((string)($d['hukou_type'] ?? '')),
            trim((string)($d['native_place'] ?? '')),
            trim((string)($d['ethnicity'] ?? '')),
            trim((string)($d['marital_status'] ?? '')),
            trim((string)($d['home_address'] ?? '')),
            trim((string)($d['emergency_contact'] ?? '')),
            trim((string)($d['education'] ?? '')),
            trim((string)($d['graduation_school'] ?? '')),
            trim((string)($d['major'] ?? '')),
            normalize_date_or_empty($d['hire_date'] ?? ''),
            trim((string)($d['contract_years'] ?? '')),
            (int)($d['working_days'] ?? 0),
            normalize_date_or_empty($d['contract_end_date'] ?? ''),
            (int)($d['is_probation'] ?? 1),
            (float)($d['probation_salary'] ?? 0),
            normalize_date_or_empty($d['regular_date'] ?? ''),
            (float)($d['regular_salary'] ?? 0),
            trim((string)($d['bank_name'] ?? '')),
            trim((string)($d['bank_card_no'] ?? '')),
            trim((string)($d['salary_adjust_records'] ?? '')),
            trim((string)($d['employment_status'] ?? '在职')),
            normalize_date_or_empty($d['leave_date'] ?? ''),
            trim((string)($d['remark'] ?? '')),
            (int)($d['status'] ?? 1),
            $id
        ]);

        $newPassword = trim((string)($d['password'] ?? ''));
        if ($newPassword !== '') {
            $pwdStmt = $pdo->prepare('UPDATE oa_user SET password_hash=? WHERE id=?');
            $pwdStmt->execute([password_hash($newPassword, PASSWORD_BCRYPT), $id]);
        }

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
