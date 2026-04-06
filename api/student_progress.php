<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-OA-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 模拟数据 - 进度跟踪
$data = [
    [
        'record_id' => 1,
        'student_name' => '赵六',
        'course_name' => 'Python全栈',
        'class_name' => 'Python全栈-春季1期',
        'current_stage' => '第2周',
        'progress' => 35,
        'teacher_comment' => '学习态度认真，作业完成及时',
        'coach_comment' => '代码能力进步明显',
        'next_followup' => '2025-04-15'
    ],
    [
        'record_id' => 2,
        'student_name' => '钱七',
        'course_name' => '新媒体运营',
        'class_name' => '新媒体-周末班',
        'current_stage' => '第1周',
        'progress' => 20,
        'teacher_comment' => '积极参与课堂讨论',
        'coach_comment' => '创意能力不错',
        'next_followup' => '2025-04-14'
    ],
    [
        'record_id' => 3,
        'student_name' => '孙八',
        'course_name' => 'AI应用',
        'class_name' => 'AI应用-春季1期',
        'current_stage' => '预习',
        'progress' => 5,
        'teacher_comment' => '预习材料已发送',
        'coach_comment' => '等待开课',
        'next_followup' => '2025-04-10'
    ],
    [
        'record_id' => 4,
        'student_name' => '周九',
        'course_name' => '短视频制作',
        'class_name' => '短视频-晚班',
        'current_stage' => '预习',
        'progress' => 0,
        'teacher_comment' => '预习材料已发送',
        'coach_comment' => '等待开课',
        'next_followup' => '2025-04-12'
    ],
    [
        'record_id' => 5,
        'student_name' => '吴十',
        'course_name' => '电商直播',
        'class_name' => '电商直播-春季1期',
        'current_stage' => '第3周',
        'progress' => 50,
        'teacher_comment' => '直播实操表现优秀',
        'coach_comment' => '带货能力突出',
        'next_followup' => '2025-04-13'
    ],
    [
        'record_id' => 6,
        'student_name' => '郑十一',
        'course_name' => 'UI设计',
        'class_name' => 'UI设计-周末班',
        'current_stage' => '第2周',
        'progress' => 40,
        'teacher_comment' => '设计作品质量高',
        'coach_comment' => '审美能力很好',
        'next_followup' => '2025-04-16'
    ],
    [
        'record_id' => 7,
        'student_name' => '冯十三',
        'course_name' => 'Python全栈',
        'class_name' => 'Python全栈-春季1期',
        'current_stage' => '第1周',
        'progress' => 15,
        'teacher_comment' => '刚入学，适应中',
        'coach_comment' => '基础较好',
        'next_followup' => '2025-04-11'
    ],
    [
        'record_id' => 8,
        'student_name' => '褚十五',
        'course_name' => 'AI应用',
        'class_name' => 'AI应用-春季2期',
        'current_stage' => '第1周',
        'progress' => 10,
        'teacher_comment' => '老学员，学习积极',
        'coach_comment' => '续费学员，基础扎实',
        'next_followup' => '2025-04-15'
    ],
    [
        'record_id' => 9,
        'student_name' => '赵六',
        'course_name' => '数据分析',
        'class_name' => '数据分析-春季1期',
        'current_stage' => '预习',
        'progress' => 0,
        'teacher_comment' => '增课学员',
        'coach_comment' => 'Python基础好，数据分析上手快',
        'next_followup' => '2025-04-20'
    ],
    [
        'record_id' => 10,
        'student_name' => '钱七',
        'course_name' => '短视频制作',
        'class_name' => '短视频-周末班',
        'current_stage' => '预习',
        'progress' => 0,
        'teacher_comment' => '增课学员',
        'coach_comment' => '新媒体基础，短视频应该容易上手',
        'next_followup' => '2025-04-18'
    ]
];

echo json_encode(['code' => 0, 'message' => 'success', 'data' => $data]);
