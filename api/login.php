<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';

function login_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
    $stmt->execute([$column]);
    return (bool)$stmt->fetchColumn();
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
    $departmentSelect = login_column_exists($pdo, 'oa_user', 'department') ? 'department' : "'' AS department";
    $positionSelect = login_column_exists($pdo, 'oa_user', 'position') ? 'position' : "'' AS position";
    $stmt = $pdo->prepare("SELECT id, username, password_hash, real_name, role, {$departmentSelect}, {$positionSelect} FROM oa_user WHERE username = ? AND status = 1 LIMIT 1");
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
