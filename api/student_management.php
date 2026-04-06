<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-OA-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 模拟数据 - 学员管理
$data = [
    [
        'student_id' => 1,
        'name' => '赵六',
        'gender' => '男',
        'age' => 24,
        'phone' => '13800138001',
        'course_name' => 'Python全栈',
        'class_name' => 'Python全栈-春季1期',
        'teacher' => '刘老师',
        'coach' => '王教练',
        'enroll_date' => '2025-04-01',
        'stage' => '在读',
        'status' => '正常'
    ],
    [
        'student_id' => 2,
        'name' => '钱七',
        'gender' => '女',
        'age' => 22,
        'phone' => '13800138002',
        'course_name' => '新媒体运营',
        'class_name' => '新媒体-周末班',
        'teacher' => '陈老师',
        'coach' => '张教练',
        'enroll_date' => '2025-04-01',
        'stage' => '在读',
        'status' => '正常'
    ],
    [
        'student_id' => 3,
        'name' => '孙八',
        'gender' => '男',
        'age' => 26,
        'phone' => '13800138003',
        'course_name' => 'AI应用',
        'class_name' => 'AI应用-春季1期',
        'teacher' => '李老师',
        'coach' => '赵教练',
        'enroll_date' => '2025-04-02',
        'stage' => '预习',
        'status' => '正常'
    ],
    [
        'student_id' => 4,
        'name' => '周九',
        'gender' => '女',
        'age' => 23,
        'phone' => '13800138004',
        'course_name' => '短视频制作',
        'class_name' => '短视频-晚班',
        'teacher' => '王老师',
        'coach' => '刘教练',
        'enroll_date' => '2025-04-02',
        'stage' => '预习',
        'status' => '正常'
    ],
    [
        'student_id' => 5,
        'name' => '吴十',
        'gender' => '男',
        'age' => 25,
        'phone' => '13800138005',
        'course_name' => '电商直播',
        'class_name' => '电商直播-春季1期',
        'teacher' => '赵老师',
        'coach' => '陈教练',
        'enroll_date' => '2025-04-03',
        'stage' => '在读',
        'status' => '正常'
    ],
    [
        'student_id' => 6,
        'name' => '郑十一',
        'gender' => '女',
        'age' => 21,
        'phone' => '13800138006',
        'course_name' => 'UI设计',
        'class_name' => 'UI设计-周末班',
        'teacher' => '孙老师',
        'coach' => '周教练',
        'enroll_date' => '2025-04-03',
        'stage' => '在读',
        'status' => '正常'
    ],
    [
        'student_id' => 7,
        'name' => '王十二',
        'gender' => '男',
        'age' => 27,
        'phone' => '13800138007',
        'course_name' => '-',
        'class_name' => '-',
        'teacher' => '-',
        'coach' => '-',
        'enroll_date' => '-',
        'stage' => '退学',
        'status' => '异常'
    ],
    [
        'student_id' => 8,
        'name' => '冯十三',
        'gender' => '女',
        'age' => 24,
        'phone' => '13800138008',
        'course_name' => 'Python全栈',
        'class_name' => 'Python全栈-春季1期',
        'teacher' => '刘老师',
        'coach' => '王教练',
        'enroll_date' => '2025-04-04',
        'stage' => '在读',
        'status' => '正常'
    ],
    [
        'student_id' => 9,
        'name' => '陈十四',
        'gender' => '男',
        'age' => 28,
        'phone' => '13800138009',
        'course_name' => '-',
        'class_name' => '-',
        'teacher' => '-',
        'coach' => '-',
        'enroll_date' => '2025-04-05',
        'stage' => '退学',
        'status' => '异常'
    ],
    [
        'student_id' => 10,
        'name' => '褚十五',
        'gender' => '女',
        'age' => 25,
        'phone' => '13800138010',
        'course_name' => 'AI应用',
        'class_name' => 'AI应用-春季2期',
        'teacher' => '李老师',
        'coach' => '赵教练',
        'enroll_date' => '2025-04-05',
        'stage' => '在读',
        'status' => '正常'
    ]
];

echo json_encode(['code' => 0, 'message' => 'success', 'data' => $data]);
