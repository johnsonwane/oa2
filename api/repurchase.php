<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-OA-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 模拟数据 - 复购管理
$data = [
    [
        'repurchase_id' => 1,
        'student_name' => '赵六',
        'phone' => '13800138001',
        'original_course' => 'Python全栈',
        'original_amount' => '12800.00',
        'repurchase_course' => '数据分析',
        'repurchase_amount' => '11800.00',
        'repurchase_type' => '增课',
        'repurchase_time' => '2025-04-06 10:00:00',
        'consultant' => '张顾问',
        'channel' => '顾问回访',
        'remark' => '老学员增购数据分析课程'
    ],
    [
        'repurchase_id' => 2,
        'student_name' => '钱七',
        'phone' => '13800138002',
        'original_course' => '新媒体运营',
        'original_amount' => '6800.00',
        'repurchase_course' => '短视频制作',
        'repurchase_amount' => '5800.00',
        'repurchase_type' => '增课',
        'repurchase_time' => '2025-04-06 14:30:00',
        'consultant' => '李顾问',
        'channel' => '学员主动',
        'remark' => '学员主动咨询增课'
    ],
    [
        'repurchase_id' => 3,
        'student_name' => '孙八',
        'phone' => '13800138003',
        'original_course' => 'AI应用',
        'original_amount' => '9800.00',
        'repurchase_course' => 'Python全栈',
        'repurchase_amount' => '12800.00',
        'repurchase_type' => '增课',
        'repurchase_time' => '2025-04-07 09:00:00',
        'consultant' => '王顾问',
        'channel' => '活动推广',
        'remark' => '周年庆活动增课'
    ],
    [
        'repurchase_id' => 4,
        'student_name' => '褚十五',
        'phone' => '13800138010',
        'original_course' => 'AI应用基础班',
        'original_amount' => '5800.00',
        'repurchase_course' => 'AI应用进阶班',
        'repurchase_amount' => '9800.00',
        'repurchase_type' => '续费',
        'repurchase_time' => '2025-04-05 16:00:00',
        'consultant' => '张顾问',
        'channel' => '顾问回访',
        'remark' => '基础班学完续费进阶班'
    ],
    [
        'repurchase_id' => 5,
        'student_name' => '周九',
        'phone' => '13800138004',
        'original_course' => '短视频制作',
        'original_amount' => '5800.00',
        'repurchase_course' => '电商直播',
        'repurchase_amount' => '8800.00',
        'repurchase_type' => '转课',
        'repurchase_time' => '2025-04-07 11:00:00',
        'consultant' => '李顾问',
        'channel' => '转介绍',
        'remark' => '朋友推荐转报电商直播'
    ],
    [
        'repurchase_id' => 6,
        'student_name' => '吴十',
        'phone' => '13800138005',
        'original_course' => '电商直播',
        'original_amount' => '8800.00',
        'repurchase_course' => '新媒体运营',
        'repurchase_amount' => '6800.00',
        'repurchase_type' => '增课',
        'repurchase_time' => '2025-04-07 15:30:00',
        'consultant' => '王顾问',
        'channel' => '学员主动',
        'remark' => '想系统学习运营知识'
    ],
    [
        'repurchase_id' => 7,
        'student_name' => '郑十一',
        'phone' => '13800138006',
        'original_course' => 'UI设计',
        'original_amount' => '10800.00',
        'repurchase_course' => 'AI应用',
        'repurchase_amount' => '9800.00',
        'repurchase_type' => '增课',
        'repurchase_time' => '2025-04-08 10:00:00',
        'consultant' => '张顾问',
        'channel' => '活动推广',
        'remark' => 'AI绘画兴趣增课'
    ],
    [
        'repurchase_id' => 8,
        'student_name' => '冯十三',
        'phone' => '13800138008',
        'original_course' => 'Python全栈',
        'original_amount' => '12800.00',
        'repurchase_course' => '数据分析',
        'repurchase_amount' => '11800.00',
        'repurchase_type' => '增课',
        'repurchase_time' => '2025-04-08 14:00:00',
        'consultant' => '李顾问',
        'channel' => '顾问回访',
        'remark' => '职业规划需要数据分析技能'
    ],
    [
        'repurchase_id' => 9,
        'student_name' => '赵六',
        'phone' => '13800138001',
        'original_course' => 'Python全栈',
        'original_amount' => '12800.00',
        'repurchase_course' => 'AI应用',
        'repurchase_amount' => '9800.00',
        'repurchase_type' => '增课',
        'repurchase_time' => '2025-04-08 16:00:00',
        'consultant' => '王顾问',
        'channel' => '学员主动',
        'remark' => '对AI方向感兴趣'
    ],
    [
        'repurchase_id' => 10,
        'student_name' => '钱七',
        'phone' => '13800138002',
        'original_course' => '新媒体运营',
        'original_amount' => '6800.00',
        'repurchase_course' => 'UI设计',
        'repurchase_amount' => '10800.00',
        'repurchase_type' => '转课',
        'repurchase_time' => '2025-04-09 09:30:00',
        'consultant' => '张顾问',
        'channel' => '转介绍',
        'remark' => '发现对设计更感兴趣'
    ]
];

echo json_encode(['code' => 0, 'message' => 'success', 'data' => $data]);
