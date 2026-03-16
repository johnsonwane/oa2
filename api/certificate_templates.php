<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/sprint123_bootstrap.php';
require_once __DIR__ . '/sprint123_crud.php';

try {
    $pdo = get_db_connection();
    ensure_sprint123_tables($pdo);
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        json_response(0, 'ok', crud_list($pdo, 'oa_certificate_template'));
    }

    if ($m === 'POST') {
        $d = request_body();
        require_fields($d, ['template_name', 'cert_type']);
        $id = crud_insert($pdo, 'oa_certificate_template', ['template_name', 'cert_type', 'template_url', 'status'], $d);
        json_response(0, 'created', ['id' => $id]);
    }

    if ($m === 'PUT') {
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        crud_update($pdo, 'oa_certificate_template', $id, ['template_name', 'cert_type', 'template_url', 'status'], $d);
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        crud_delete($pdo, 'oa_certificate_template', $id);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
