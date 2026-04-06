<?php
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $intent = $_GET['intent'] ?? '';

    $allData = [
        ['id' => 'RF001', 'studentName' => '张三', 'purchasedCourses' => 'Python编程入门', 'lastOrderTime' => '2026-01-15', 'intent' => '高', 'intentClass' => 'high', 'targetCourse' => '数据分析实战', 'responsible' => '顾问A', 'lastFollowTime' => '2026-04-05 15:30', 'followContent' => '介绍了数据分析课程优势，学员表示感兴趣', 'nextPlan' => '发送课程试听链接', 'expectedRepurchaseTime' => '2026-04-20'],
        ['id' => 'RF002', 'studentName' => '李四', 'purchasedCourses' => 'Web前端开发', 'lastOrderTime' => '2026-02-20', 'intent' => '中', 'intentClass' => 'medium', 'targetCourse' => 'Java高级编程', 'responsible' => '顾问B', 'lastFollowTime' => '2026-04-04 14:20', 'followContent' => '推荐Java课程，学员需要考虑', 'nextPlan' => '下周再次跟进', 'expectedRepurchaseTime' => '2026-05-10'],
        ['id' => 'RF003', 'studentName' => '王五', 'purchasedCourses' => '数据分析实战', 'lastOrderTime' => '2026-03-10', 'intent' => '低', 'intentClass' => 'low', 'targetCourse' => '人工智能基础', 'responsible' => '顾问C', 'lastFollowTime' => '2026-04-03 11:00', 'followContent' => '学员当前工作较忙，暂无学习计划', 'nextPlan' => '一个月后跟进', 'expectedRepurchaseTime' => '2026-06-01'],
        ['id' => 'RF004', 'studentName' => '赵六', 'purchasedCourses' => 'Java高级编程', 'lastOrderTime' => '2026-01-28', 'intent' => '高', 'intentClass' => 'high', 'targetCourse' => 'Python编程入门', 'responsible' => '顾问A', 'lastFollowTime' => '2026-04-05 10:15', 'followContent' => '学员对Python很有兴趣，正在对比课程', 'nextPlan' => '发送课程大纲和试听链接', 'expectedRepurchaseTime' => '2026-04-15'],
        ['id' => 'RF005', 'studentName' => '孙七', 'purchasedCourses' => '人工智能基础', 'lastOrderTime' => '2026-02-15', 'intent' => '无', 'intentClass' => 'none', 'targetCourse' => '数据分析实战', 'responsible' => '顾问D', 'lastFollowTime' => '2026-04-01 09:30', 'followContent' => '学员表示暂无二销需求', 'nextPlan' => '3个月后跟进', 'expectedRepurchaseTime' => '-'],
        ['id' => 'RF006', 'studentName' => '周八', 'purchasedCourses' => 'Python编程入门', 'lastOrderTime' => '2026-03-05', 'intent' => '中', 'intentClass' => 'medium', 'targetCourse' => 'Web前端开发', 'responsible' => '顾问B', 'lastFollowTime' => '2026-04-04 16:45', 'followContent' => '推荐Web前端课程，学员需要时间考虑', 'nextPlan' => '本周五再跟进', 'expectedRepurchaseTime' => '2026-05-05'],
        ['id' => 'RF007', 'studentName' => '吴九', 'purchasedCourses' => '数据分析实战', 'lastOrderTime' => '2026-01-20', 'intent' => '高', 'intentClass' => 'high', 'targetCourse' => 'Java高级编程', 'responsible' => '顾问C', 'lastFollowTime' => '2026-04-05 13:20', 'followContent' => '学员已试听Java课程，反馈良好', 'nextPlan' => '促成报名', 'expectedRepurchaseTime' => '2026-04-12'],
        ['id' => 'RF008', 'studentName' => '郑十', 'purchasedCourses' => 'Web前端开发', 'lastOrderTime' => '2026-02-28', 'intent' => '低', 'intentClass' => 'low', 'targetCourse' => '人工智能基础', 'responsible' => '顾问A', 'lastFollowTime' => '2026-04-02 14:00', 'followContent' => '学员正在学习前端课程，暂无其他学习计划', 'nextPlan' => '待前端课程完成后跟进', 'expectedRepurchaseTime' => '2026-07-01'],
    ];

    if ($intent) {
        $filteredData = array_filter($allData, function($item) use ($intent) {
            return $item['intent'] === $intent;
        });
        echo json_encode(['code' => 0, 'message' => 'success', 'data' => array_values($filteredData)]);
    } else {
        echo json_encode(['code' => 0, 'message' => 'success', 'data' => $allData]);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'follow') {
        echo json_encode(['code' => 0, 'message' => '跟进记录已保存', 'data' => []]);
    } else {
        echo json_encode(['code' => 1, 'message' => '无效操作', 'data' => []]);
    }
} else {
    echo json_encode(['code' => 1, 'message' => '不支持的请求方法', 'data' => []]);
}
