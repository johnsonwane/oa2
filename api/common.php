<?php

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-OA-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function json_response(int $code, string $message, $data = null, int $httpCode = 200): void
{
    http_response_code($httpCode);
    echo json_encode([
        'code' => $code,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function request_body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function require_fields(array $data, array $fields): void
{
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
            json_response(400, "字段 {$field} 不能为空", null, 400);
        }
    }
}

function parse_pagination(array $query, int $defaultPage = 1, int $defaultPageSize = 20, int $maxPageSize = 100): array
{
    $page = max(1, (int)($query['page'] ?? $defaultPage));
    $pageSize = (int)($query['page_size'] ?? $defaultPageSize);
    if ($pageSize <= 0) {
        $pageSize = $defaultPageSize;
    }
    $pageSize = min($pageSize, $maxPageSize);
    return [
        'page' => $page,
        'page_size' => $pageSize,
        'offset' => ($page - 1) * $pageSize,
    ];
}

function paged_mode(array $query): bool
{
    return isset($query['paged']) && (string)$query['paged'] === '1';
}

function normalize_date_or_empty($value): string
{
    $v = trim((string)$value);
    if ($v === '') {
        return '';
    }
    $dt = DateTime::createFromFormat('Y-m-d', $v);
    if (!$dt || $dt->format('Y-m-d') !== $v) {
        json_response(400, '日期格式必须为 YYYY-MM-DD', null, 400);
    }
    return $v;
}

function auth_user(): ?array
{
    return $GLOBALS['__oa_auth_user'] ?? null;
}

function auth_token(): string
{
    return (string)($GLOBALS['__oa_auth_token'] ?? '');
}

function client_ip(): string
{
    $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($keys as $k) {
        $val = trim((string)($_SERVER[$k] ?? ''));
        if ($val !== '') {
            if ($k === 'HTTP_X_FORWARDED_FOR') {
                return trim(explode(',', $val)[0]);
            }
            return $val;
        }
    }
    return '';
}

function is_public_endpoint(): bool
{
    $name = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $public = [
        'login.php',
        'health.php',
    ];
    return in_array($name, $public, true);
}

function request_bearer_token(): string
{
    $auth = trim((string)($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
    if ($auth !== '' && preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
        return trim($m[1]);
    }
    $xToken = trim((string)($_SERVER['HTTP_X_OA_TOKEN'] ?? ''));
    if ($xToken !== '') {
        return $xToken;
    }
    return '';
}

function require_auth_session(): void
{
    if (is_public_endpoint()) {
        return;
    }

    $token = request_bearer_token();
    if ($token === '') {
        json_response(401, '未登录或登录已过期', null, 401);
    }

    try {
        $pdo = get_db_connection();
    } catch (Throwable $e) {
        json_response(500, '认证服务不可用', null, 500);
    }

    $tokenHash = hash('sha256', $token);
    try {
        $stmt = $pdo->prepare('SELECT s.id AS session_id, s.user_id, s.expires_at, u.username, u.real_name, u.role, u.status
            FROM oa_session s
            JOIN oa_user u ON u.id = s.user_id
            WHERE s.token_hash = ? AND s.revoked_at IS NULL
            LIMIT 1');
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        json_response(500, '认证数据表不存在，请先执行数据库迁移', null, 500);
    }

    if (!$row) {
        json_response(401, '登录状态无效，请重新登录', null, 401);
    }

    $expiresAt = strtotime((string)($row['expires_at'] ?? ''));
    if ($expiresAt <= time()) {
        json_response(401, '登录已过期，请重新登录', null, 401);
    }

    if ((int)($row['status'] ?? 1) !== 1) {
        json_response(403, '账号已禁用', null, 403);
    }

    $pdo->prepare('UPDATE oa_session SET last_seen_at = NOW(), last_ip = ?, user_agent = ? WHERE id = ?')
        ->execute([client_ip(), (string)($_SERVER['HTTP_USER_AGENT'] ?? ''), (int)$row['session_id']]);

    $GLOBALS['__oa_auth_user'] = [
        'id' => (int)$row['user_id'],
        'username' => (string)$row['username'],
        'real_name' => (string)($row['real_name'] ?? ''),
        'role' => (string)($row['role'] ?? ''),
    ];
    $GLOBALS['__oa_auth_token'] = $token;
}


function auth_user_id(): int
{
    $u = auth_user();
    return (int)($u['id'] ?? 0);
}

function auth_user_role(): string
{
    $u = auth_user();
    return trim((string)($u['role'] ?? ''));
}

function auth_user_name(): string
{
    $u = auth_user();
    return trim((string)($u['real_name'] ?? ''));
}

function auth_is_admin_like(): bool
{
    $role = auth_user_role();
    return $role === '超管' || $role === '老板' || stripos($role, 'admin') !== false;
}

function auth_role_in(array $roles): bool
{
    return in_array(auth_user_role(), $roles, true);
}

function auth_require_roles(array $roles): void
{
    if (auth_is_admin_like()) {
        return;
    }
    if (!auth_role_in($roles)) {
        json_response(403, '当前角色无此操作权限', null, 403);
    }
}

require_auth_session();
