<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-OA-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 模拟数据 - 订单管理
$data = [
    [
        'order_id' => 1,
        'order_no' => 'ORD20250401001',
        'student_name' => '赵六',
        'course_name' => 'Python全栈',
        'order_type' => '新签',
        'order_amount' => '12800.00',
        'paid_amount' => '12800.00',
        'pending_amount' => '0.00',
        'payment_phase' => '全款',
        'payment_status' => '已付清',
        'order_time' => '2025-04-01 10:30:00',
        'consultant' => '张顾问'
    ],
    [
        'order_id' => 2,
        'order_no' => 'ORD20250401002',
        'student_name' => '钱七',
        'course_name' => '新媒体运营',
        'order_type' => '新签',
        'order_amount' => '6800.00',
        'paid_amount' => '2000.00',
        'pending_amount' => '4800.00',
        'payment_phase' => '定金',
        'payment_status' => '部分付款',
        'order_time' => '2025-04-01 14:20:00',
        'consultant' => '李顾问'
    ],
    [
        'order_id' => 3,
        'order_no' => 'ORD20250402001',
        'student_name' => '孙八',
        'course_name' => 'AI应用',
        'order_type' => '新签',
        'order_amount' => '9800.00',
        'paid_amount' => '9800.00',
        'pending_amount' => '0.00',
        'payment_phase' => '全款',
        'payment_status' => '已付清',
        'order_time' => '2025-04-02 09:15:00',
        'consultant' => '王顾问'
    ],
    [
        'order_id' => 4,
        'order_no' => 'ORD20250402002',
        'student_name' => '周九',
        'course_name' => '短视频制作',
        'order_type' => '新签',
        'order_amount' => '5800.00',
        'paid_amount' => '0.00',
        'pending_amount' => '5800.00',
        'payment_phase' => '全款',
        'payment_status' => '下单',
        'order_time' => '2025-04-02 16:45:00',
        'consultant' => '张顾问'
    ],
    [
        'order_id' => 5,
        'order_no' => 'ORD20250403001',
        'student_name' => '吴十',
        'course_name' => '电商直播',
        'order_type' => '新签',
        'order_amount' => '8800.00',
        'paid_amount' => '4400.00',
        'pending_amount' => '4400.00',
        'payment_phase' => '中期',
        'payment_status' => '部分付款',
        'order_time' => '2025-04-03 11:00:00',
        'consultant' => '李顾问'
    ],
    [
        'order_id' => 6,
        'order_no' => 'ORD20250403002',
        'student_name' => '郑十一',
        'course_name' => 'UI设计',
        'order_type' => '新签',
        'order_amount' => '10800.00',
        'paid_amount' => '10800.00',
        'pending_amount' => '0.00',
        'payment_phase' => '全款',
        'payment_status' => '已付清',
        'order_time' => '2025-04-03 15:30:00',
        'consultant' => '王顾问'
    ],
    [
        'order_id' => 7,
        'order_no' => 'ORD20250404001',
        'student_name' => '王十二',
        'course_name' => '数据分析',
        'order_type' => '新签',
        'order_amount' => '11800.00',
        'paid_amount' => '0.00',
        'pending_amount' => '0.00',
        'payment_phase' => '全款',
        'payment_status' => '已取消',
        'order_time' => '2025-04-04 10:00:00',
        'consultant' => '张顾问'
    ],
    [
        'order_id' => 8,
        'order_no' => 'ORD20250404002',
        'student_name' => '冯十三',
        'course_name' => 'Python全栈',
        'order_type' => '新签',
        'order_amount' => '12800.00',
        'paid_amount' => '3000.00',
        'pending_amount' => '9800.00',
        'payment_phase' => '定金',
        'payment_status' => '部分付款',
        'order_time' => '2025-04-04 14:00:00',
        'consultant' => '李顾问'
    ],
    [
        'order_id' => 9,
        'order_no' => 'ORD20250405001',
        'student_name' => '陈十四',
        'course_name' => '新媒体运营',
        'order_type' => '新签',
        'order_amount' => '6800.00',
        'paid_amount' => '6800.00',
        'pending_amount' => '0.00',
        'payment_phase' => '全款',
        'payment_status' => '已退款',
        'order_time' => '2025-04-05 09:30:00',
        'consultant' => '王顾问'
    ],
    [
        'order_id' => 10,
        'order_no' => 'ORD20250405002',
        'student_name' => '褚十五',
        'course_name' => 'AI应用',
        'order_type' => '新签',
        'order_amount' => '9800.00',
        'paid_amount' => '8000.00',
        'pending_amount' => '1800.00',
        'payment_phase' => '尾款',
        'payment_status' => '部分付款',
        'order_time' => '2025-04-05 16:00:00',
        'consultant' => '张顾问'
    ]
];

echo json_encode(['code' => 0, 'message' => 'success', 'data' => $data]);
