<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://oac.hahahaxinli.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'code' => 405,
        'message' => '仅支持 POST 请求'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$username = isset($data['username']) ? trim($data['username']) : '';
$password = isset($data['password']) ? (string)$data['password'] : '';

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode([
        'code' => 400,
        'message' => '用户名和密码不能为空'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 样例账号（生产环境请改为数据库校验）
$validUser = 'admin';
$validPass = '123456';

if ($username !== $validUser || $password !== $validPass) {
    http_response_code(401);
    echo json_encode([
        'code' => 401,
        'message' => '用户名或密码错误'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$token = hash('sha256', $username . '|' . time() . '|' . bin2hex(random_bytes(8)));

echo json_encode([
    'code' => 0,
    'message' => '登录成功',
    'data' => [
        'token' => $token,
        'username' => $username
    ]
], JSON_UNESCAPED_UNICODE);
