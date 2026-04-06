<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-OA-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 模拟数据 - 异常管理
$data = [
    [
        'exception_id' => 1,
        'student_name' => '王十二',
        'course_name' => '数据分析',
        'exception_type' => '退学申请',
        'severity' => '高',
        'occurred_time' => '2025-04-04 11:00:00',
        'handler' => '刘老师',
        'handle_status' => '已解决',
        'handle_result' => '已办理退学手续，退款处理中'
    ],
    [
        'exception_id' => 2,
        'student_name' => '陈十四',
        'course_name' => '新媒体运营',
        'exception_type' => '退款投诉',
        'severity' => '紧急',
        'occurred_time' => '2025-04-05 10:30:00',
        'handler' => '张主管',
        'handle_status' => '已解决',
        'handle_result' => '已全额退款，客户满意'
    ],
    [
        'exception_id' => 3,
        'student_name' => '赵六',
        'course_name' => 'Python全栈',
        'exception_type' => '学习中断',
        'severity' => '中',
        'occurred_time' => '2025-04-06 09:00:00',
        'handler' => '刘老师',
        'handle_status' => '处理中',
        'handle_result' => '因出差请假一周，已安排补课'
    ],
    [
        'exception_id' => 4,
        'student_name' => '孙八',
        'course_name' => 'AI应用',
        'exception_type' => '其他',
        'severity' => '低',
        'occurred_time' => '2025-04-06 14:00:00',
        'handler' => '李老师',
        'handle_status' => '待处理',
        'handle_result' => ''
    ],
    [
        'exception_id' => 5,
        'student_name' => '周九',
        'course_name' => '短视频制作',
        'exception_type' => '严重违纪',
        'severity' => '高',
        'occurred_time' => '2025-04-05 16:30:00',
        'handler' => '王主管',
        'handle_status' => '已升级',
        'handle_result' => '课堂纪律问题，已上报教学主管'
    ],
    [
        'exception_id' => 6,
        'student_name' => '吴十',
        'course_name' => '电商直播',
        'exception_type' => '学习中断',
        'severity' => '中',
        'occurred_time' => '2025-04-07 10:00:00',
        'handler' => '赵老师',
        'handle_status' => '处理中',
        'handle_result' => '个人原因请假，正在沟通'
    ],
    [
        'exception_id' => 7,
        'student_name' => '钱七',
        'course_name' => '新媒体运营',
        'exception_type' => '其他',
        'severity' => '低',
        'occurred_time' => '2025-04-07 11:30:00',
        'handler' => '陈老师',
        'handle_status' => '待处理',
        'handle_result' => ''
    ],
    [
        'exception_id' => 8,
        'student_name' => '郑十一',
        'course_name' => 'UI设计',
        'exception_type' => '退学申请',
        'severity' => '高',
        'occurred_time' => '2025-04-06 15:00:00',
        'handler' => '孙老师',
        'handle_status' => '处理中',
        'handle_result' => '经济困难，正在协商分期方案'
    ],
    [
        'exception_id' => 9,
        'student_name' => '冯十三',
        'course_name' => 'Python全栈',
        'exception_type' => '学习中断',
        'severity' => '中',
        'occurred_time' => '2025-04-07 09:30:00',
        'handler' => '刘老师',
        'handle_status' => '待处理',
        'handle_result' => ''
    ],
    [
        'exception_id' => 10,
        'student_name' => '褚十五',
        'course_name' => 'AI应用',
        'exception_type' => '其他',
        'severity' => '低',
        'occurred_time' => '2025-04-07 14:00:00',
        'handler' => '李老师',
        'handle_status' => '已解决',
        'handle_result' => '课程时间冲突，已调整班级'
    ]
];

echo json_encode(['code' => 0, 'message' => 'success', 'data' => $data]);
