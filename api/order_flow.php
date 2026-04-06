<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-OA-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 模拟数据 - 状态流转
$data = [
    [
        'node_id' => 1,
        'order_no' => 'ORD20250401001',
        'status_name' => '订单创建',
        'prev_status' => '-',
        'current_status' => '已创建',
        'change_time' => '2025-04-01 10:30:00',
        'change_by' => '张顾问',
        'change_reason' => '客户确定报名',
        'remark' => 'Python全栈课程'
    ],
    [
        'node_id' => 2,
        'order_no' => 'ORD20250401001',
        'status_name' => '付款完成',
        'prev_status' => '已创建',
        'current_status' => '已付清',
        'change_time' => '2025-04-01 10:35:00',
        'change_by' => '系统',
        'change_reason' => '全款支付成功',
        'remark' => '支付宝支付'
    ],
    [
        'node_id' => 3,
        'order_no' => 'ORD20250401001',
        'status_name' => '订单确认',
        'prev_status' => '已付清',
        'current_status' => '已完成',
        'change_time' => '2025-04-01 10:40:00',
        'change_by' => '财务',
        'change_reason' => '财务确认收款',
        'remark' => '已开具发票'
    ],
    [
        'node_id' => 4,
        'order_no' => 'ORD20250401002',
        'status_name' => '订单创建',
        'prev_status' => '-',
        'current_status' => '已创建',
        'change_time' => '2025-04-01 14:20:00',
        'change_by' => '李顾问',
        'change_reason' => '客户确定报名',
        'remark' => '新媒体运营课程'
    ],
    [
        'node_id' => 5,
        'order_no' => 'ORD20250401002',
        'status_name' => '定金支付',
        'prev_status' => '已创建',
        'current_status' => '部分付款',
        'change_time' => '2025-04-01 14:25:00',
        'change_by' => '系统',
        'change_reason' => '定金支付成功',
        'remark' => '微信支付2000元'
    ],
    [
        'node_id' => 6,
        'order_no' => 'ORD20250402001',
        'status_name' => '订单创建',
        'prev_status' => '-',
        'current_status' => '已创建',
        'change_time' => '2025-04-02 09:15:00',
        'change_by' => '王顾问',
        'change_reason' => '客户确定报名',
        'remark' => 'AI应用课程'
    ],
    [
        'node_id' => 7,
        'order_no' => 'ORD20250402001',
        'status_name' => '付款完成',
        'prev_status' => '已创建',
        'current_status' => '已付清',
        'change_time' => '2025-04-02 09:20:00',
        'change_by' => '系统',
        'change_reason' => '全款支付成功',
        'remark' => '银行转账'
    ],
    [
        'node_id' => 8,
        'order_no' => 'ORD20250402001',
        'status_name' => '开课处理',
        'prev_status' => '已付清',
        'current_status' => '处理中',
        'change_time' => '2025-04-02 09:30:00',
        'change_by' => '班主任',
        'change_reason' => '安排班级',
        'remark' => '分配至春季1期'
    ],
    [
        'node_id' => 9,
        'order_no' => 'ORD20250404001',
        'status_name' => '订单创建',
        'prev_status' => '-',
        'current_status' => '已创建',
        'change_time' => '2025-04-04 10:00:00',
        'change_by' => '张顾问',
        'change_reason' => '客户确定报名',
        'remark' => '数据分析课程'
    ],
    [
        'node_id' => 10,
        'order_no' => 'ORD20250404001',
        'status_name' => '订单取消',
        'prev_status' => '已创建',
        'current_status' => '已取消',
        'change_time' => '2025-04-04 11:30:00',
        'change_by' => '张顾问',
        'change_reason' => '客户个人原因',
        'remark' => '客户放弃报名'
    ],
    [
        'node_id' => 11,
        'order_no' => 'ORD20250405001',
        'status_name' => '订单创建',
        'prev_status' => '-',
        'current_status' => '已创建',
        'change_time' => '2025-04-05 09:30:00',
        'change_by' => '王顾问',
        'change_reason' => '客户确定报名',
        'remark' => '新媒体运营课程'
    ],
    [
        'node_id' => 12,
        'order_no' => 'ORD20250405001',
        'status_name' => '申请退款',
        'prev_status' => '已创建',
        'current_status' => '已退款',
        'change_time' => '2025-04-05 16:00:00',
        'change_by' => '财务',
        'change_reason' => '7天无理由退款',
        'remark' => '全额退款已处理'
    ]
];

echo json_encode(['code' => 0, 'message' => 'success', 'data' => $data]);
