<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/sprint123_bootstrap.php';
require_once __DIR__ . '/sprint123_crud.php';

function payroll_period_exists(PDO $pdo, int $periodId): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(1) FROM oa_payroll_period WHERE id=?');
    $stmt->execute([$periodId]);
    return (int)$stmt->fetchColumn() > 0;
}

function payroll_slip_prepare(array $data): array
{
    $gross = (float)($data['gross_amount'] ?? 0);
    $tax = (float)($data['tax_amount'] ?? 0);
    $social = (float)($data['social_amount'] ?? 0);
    $housing = (float)($data['housing_amount'] ?? 0);
    $deduction = (float)($data['special_deduction'] ?? 0);

    $computed = round($gross - $tax - $social - $housing - $deduction, 2);
    $data['net_amount'] = $computed;

    return $data;
}

function payroll_slips_validate(PDO $pdo, array $data): void
{
    $periodId = (int)($data['period_id'] ?? 0);
    $userId = (int)($data['user_id'] ?? 0);

    if ($periodId <= 0 || $userId <= 0) {
        json_response(400, '工资期ID和员工ID必须为正整数', null, 400);
    }

    if (!payroll_period_exists($pdo, $periodId)) {
        json_response(400, '业务校验失败：工资期不存在', null, 400);
    }

    if ((float)($data['net_amount'] ?? 0) < 0) {
        json_response(400, '业务校验失败：实发金额不能为负数，请检查扣减项', null, 400);
    }
}

try {
    $pdo = get_db_connection();
    ensure_sprint123_tables($pdo);
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        json_response(0, 'ok', crud_list($pdo, 'oa_payroll_slip'));
    }

    if ($m === 'POST') {
        $d = request_body();
        require_fields($d, ['period_id', 'user_id']);
        $d = payroll_slip_prepare($d);
        payroll_slips_validate($pdo, $d);
        $id = crud_insert($pdo, 'oa_payroll_slip', ['period_id', 'user_id', 'gross_amount', 'tax_amount', 'social_amount', 'housing_amount', 'special_deduction', 'net_amount', 'status'], $d);
        json_response(0, 'created', ['id' => $id]);
    }

    if ($m === 'PUT') {
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $d = payroll_slip_prepare($d);
        payroll_slips_validate($pdo, $d);
        crud_update($pdo, 'oa_payroll_slip', $id, ['period_id', 'user_id', 'gross_amount', 'tax_amount', 'social_amount', 'housing_amount', 'special_deduction', 'net_amount', 'status'], $d);
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        crud_delete($pdo, 'oa_payroll_slip', $id);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
