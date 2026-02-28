<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';

function login_get_user_columns(PDO $pdo): array
{
    $columns = [];
    $stmt = $pdo->query('SHOW COLUMNS FROM `oa_user`');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $name = (string)($row['Field'] ?? '');
        if ($name !== '') {
            $columns[$name] = true;
        }
    }
    return $columns;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(405, '仅支持 POST 请求', null, 405);
}

try {
    $data = request_body();
    require_fields($data, ['username', 'password']);

    $username = trim((string)$data['username']);
    $password = (string)$data['password'];

    $pdo = get_db_connection();
    $columns = login_get_user_columns($pdo);

    $departmentSelect = isset($columns['department']) ? 'department' : "'' AS department";
    $positionSelect = isset($columns['position']) ? 'position' : "'' AS position";
    $realNameSelect = isset($columns['real_name']) ? 'real_name' : "'' AS real_name";
    $roleSelect = isset($columns['role']) ? 'role' : "'' AS role";
    $statusWhere = isset($columns['status']) ? ' AND status = 1' : '';

    $stmt = $pdo->prepare("SELECT id, username, password_hash, {$realNameSelect}, {$roleSelect}, {$departmentSelect}, {$positionSelect} FROM oa_user WHERE username = ?{$statusWhere} LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        json_response(401, '用户名或密码错误', null, 401);
    }

    $token = hash('sha256', $user['id'] . '|' . $user['username'] . '|' . microtime(true) . '|' . bin2hex(random_bytes(8)));

    json_response(0, '登录成功', [
        'token' => $token,
        'user' => [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'real_name' => $user['real_name'],
            'role' => $user['role'],
            'department' => $user['department'] ?? '',
            'position' => $user['position'] ?? '',
        ],
    ]);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
