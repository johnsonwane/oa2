<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-OA-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 模拟数据 - 多人参与
$data = [
    [
        'record_id' => 1,
        'order_no' => 'ORD20250401001',
        'student_name' => '赵六',
        'course_name' => 'Python全栈',
        'role' => '顾问',
        'person_name' => '张顾问',
        'contribution_type' => '录单',
        'share_amount' => '800.00',
        'assign_time' => '2025-04-01 10:35:00',
        'status' => '已发放',
        'remark' => '成功签约'
    ],
    [
        'record_id' => 2,
        'order_no' => 'ORD20250401001',
        'student_name' => '赵六',
        'course_name' => 'Python全栈',
        'role' => '班主任',
        'person_name' => '刘老师',
        'contribution_type' => '交付',
        'share_amount' => '400.00',
        'assign_time' => '2025-04-01 10:40:00',
        'status' => '待确认',
        'remark' => '负责班级管理'
    ],
    [
        'record_id' => 3,
        'order_no' => 'ORD20250401001',
        'student_name' => '赵六',
        'course_name' => 'Python全栈',
        'role' => '教练',
        'person_name' => '王教练',
        'contribution_type' => '交付',
        'share_amount' => '600.00',
        'assign_time' => '2025-04-01 10:45:00',
        'status' => '待确认',
        'remark' => '负责技术教学'
    ],
    [
        'record_id' => 4,
        'order_no' => 'ORD20250401002',
        'student_name' => '钱七',
        'course_name' => '新媒体运营',
        'role' => '顾问',
        'person_name' => '李顾问',
        'contribution_type' => '录单',
        'share_amount' => '500.00',
        'assign_time' => '2025-04-01 14:25:00',
        'status' => '已确认',
        'remark' => '部分付款订单'
    ],
    [
        'record_id' => 5,
        'order_no' => 'ORD20250401002',
        'student_name' => '钱七',
        'course_name' => '新媒体运营',
        'role' => '销售',
        'person_name' => '赵销售',
        'contribution_type' => '录单',
        'share_amount' => '300.00',
        'assign_time' => '2025-04-01 14:30:00',
        'status' => '已确认',
        'remark' => '协助签约'
    ],
    [
        'record_id' => 6,
        'order_no' => 'ORD20250402001',
        'student_name' => '孙八',
        'course_name' => 'AI应用',
        'role' => '顾问',
        'person_name' => '王顾问',
        'contribution_type' => '录单',
        'share_amount' => '700.00',
        'assign_time' => '2025-04-02 09:20:00',
        'status' => '已发放',
        'remark' => '全款签约'
    ],
    [
        'record_id' => 7,
        'order_no' => 'ORD20250403001',
        'student_name' => '吴十',
        'course_name' => '电商直播',
        'role' => '顾问',
        'person_name' => '李顾问',
        'contribution_type' => '录单',
        'share_amount' => '600.00',
        'assign_time' => '2025-04-03 11:05:00',
        'status' => '已确认',
        'remark' => '分期付款'
    ],
    [
        'record_id' => 8,
        'order_no' => 'ORD20250403001',
        'student_name' => '吴十',
        'course_name' => '电商直播',
        'role' => '班主任',
        'person_name' => '陈老师',
        'contribution_type' => '交付',
        'share_amount' => '350.00',
        'assign_time' => '2025-04-03 11:10:00',
        'status' => '待确认',
        'remark' => ''
    ],
    [
        'record_id' => 9,
        'order_no' => 'ORD20250405002',
        'student_name' => '褚十五',
        'course_name' => 'AI应用',
        'role' => '顾问',
        'person_name' => '张顾问',
        'contribution_type' => '续费',
        'share_amount' => '550.00',
        'assign_time' => '2025-04-05 16:05:00',
        'status' => '已确认',
        'remark' => '老学员续费'
    ],
    [
        'record_id' => 10,
        'order_no' => 'ORD20250405002',
        'student_name' => '褚十五',
        'course_name' => 'AI应用',
        'role' => '教练',
        'person_name' => '李教练',
        'contribution_type' => '增课',
        'share_amount' => '400.00',
        'assign_time' => '2025-04-05 16:10:00',
        'status' => '待确认',
        'remark' => '推荐增课'
    ]
];

echo json_encode(['code' => 0, 'message' => 'success', 'data' => $data]);
