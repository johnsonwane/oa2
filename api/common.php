<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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
