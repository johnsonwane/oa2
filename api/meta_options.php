<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/hr_bootstrap.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        json_response(405, 'method not allowed', null, 405);
    }

    $pdo = get_db_connection();
    ensure_hr_schema($pdo);
    $departments = $pdo->query("SELECT dept_name FROM oa_department WHERE status=1 ORDER BY sort_no ASC,id ASC")->fetchAll(PDO::FETCH_COLUMN);

    $positions = [
        '老板',
        '运营兼财务总监','高级音疗师','人事总监','行政会务专员',
        '财务经理',
        '运营主管','剪辑专员','运营专员',
        '交付部负责人','班主任','成交教练',
        '顾问部负责人','课程顾问','培训导师',
        '外部运营','外部顾问','外部教练','其他'
    ];

    $roles = ['超管','老板','部门经理','顾问','班主任','教练','财务'];

    $receiptChannels = ['老板个人收款','哈广企微','乐悟企微','哈北企微','哈厦企微','哈支付宝','哈平安','乐悟平安','厦门招行','北京工行'];

    json_response(0, 'ok', [
        'departments' => $departments,
        'positions' => $positions,
        'roles' => $roles,
        'receipt_channels' => $receiptChannels,
    ]);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
