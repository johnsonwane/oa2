<?php
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $tag = $_GET['tag'] ?? '';
    $status = $_GET['status'] ?? '';

    $allData = [
        ['id' => 'L001', 'name' => '张三', 'phone' => '138****1234', 'wechat' => 'zhangsan_wx', 'source' => '抖音广告', 'tag' => '高意向', 'tagClass' => 'high', 'course' => 'Python编程入门', 'consultant' => '顾问A', 'lastFollowTime' => '2026-04-05 15:30', 'followCount' => 3, 'status' => '跟进中', 'statusClass' => 'following', 'createTime' => '2026-04-02 10:20'],
        ['id' => 'L002', 'name' => '李四', 'phone' => '139****5678', 'wechat' => 'lisi_wx', 'source' => '微信朋友圈', 'tag' => '中意向', 'tagClass' => 'medium', 'course' => '数据分析实战', 'consultant' => '顾问B', 'lastFollowTime' => '2026-04-04 14:20', 'followCount' => 2, 'status' => '跟进中', 'statusClass' => 'following', 'createTime' => '2026-04-01 09:15'],
        ['id' => 'L003', 'name' => '王五', 'phone' => '137****9012', 'wechat' => 'wangwu_wx', 'source' => '百度搜索', 'tag' => '高意向', 'tagClass' => 'high', 'course' => 'Web前端开发', 'consultant' => '顾问C', 'lastFollowTime' => '2026-04-05 11:00', 'followCount' => 5, 'status' => '已成交', 'statusClass' => 'deal', 'createTime' => '2026-03-30 16:45'],
        ['id' => 'L004', 'name' => '赵六', 'phone' => '136****3456', 'wechat' => 'zhaoliu_wx', 'source' => '小红书', 'tag' => '低意向', 'tagClass' => 'low', 'course' => 'Java高级编程', 'consultant' => '顾问A', 'lastFollowTime' => '2026-04-03 13:20', 'followCount' => 1, 'status' => '未跟进', 'statusClass' => 'unfollowed', 'createTime' => '2026-04-03 10:00'],
        ['id' => 'L005', 'name' => '孙七', 'phone' => '135****7890', 'wechat' => 'sunqi_wx', 'source' => '线下活动', 'tag' => '中意向', 'tagClass' => 'medium', 'course' => '人工智能基础', 'consultant' => '顾问D', 'lastFollowTime' => '2026-04-02 15:30', 'followCount' => 2, 'status' => '跟进中', 'statusClass' => 'following', 'createTime' => '2026-03-31 11:15'],
        ['id' => 'L006', 'name' => '周八', 'phone' => '134****2345', 'wechat' => 'zhouba_wx', 'source' => '转介绍', 'tag' => '高意向', 'tagClass' => 'high', 'course' => 'Python编程入门', 'consultant' => '顾问B', 'lastFollowTime' => '2026-04-05 09:45', 'followCount' => 4, 'status' => '已成交', 'statusClass' => 'deal', 'createTime' => '2026-03-29 14:20'],
        ['id' => 'L007', 'name' => '吴九', 'phone' => '133****6789', 'wechat' => 'wujiu_wx', 'source' => '抖音广告', 'tag' => '低意向', 'tagClass' => 'low', 'course' => '数据分析实战', 'consultant' => '顾问C', 'lastFollowTime' => '2026-04-01 10:30', 'followCount' => 1, 'status' => '已流失', 'statusClass' => 'lost', 'createTime' => '2026-03-28 08:45'],
        ['id' => 'L008', 'name' => '郑十', 'phone' => '132****0123', 'wechat' => 'zhengshi_wx', 'source' => '微信朋友圈', 'tag' => '中意向', 'tagClass' => 'medium', 'course' => 'Web前端开发', 'consultant' => '顾问A', 'lastFollowTime' => '2026-04-04 16:00', 'followCount' => 2, 'status' => '跟进中', 'statusClass' => 'following', 'createTime' => '2026-03-27 13:15'],
        ['id' => 'L009', 'name' => '钱一', 'phone' => '131****4567', 'wechat' => 'qianyi_wx', 'source' => '百度搜索', 'tag' => '无效', 'tagClass' => 'invalid', 'course' => '', 'consultant' => '顾问D', 'lastFollowTime' => '-', 'followCount' => 0, 'status' => '已流失', 'statusClass' => 'lost', 'createTime' => '2026-03-26 09:30'],
        ['id' => 'L010', 'name' => '陈二', 'phone' => '130****8901', 'wechat' => 'chener_wx', 'source' => '小红书', 'tag' => '高意向', 'tagClass' => 'high', 'course' => 'Java高级编程', 'consultant' => '顾问B', 'lastFollowTime' => '2026-04-05 14:15', 'followCount' => 3, 'status' => '跟进中', 'statusClass' => 'following', 'createTime' => '2026-03-25 11:45'],
    ];

    $filteredData = $allData;

    if ($tag) {
        $filteredData = array_filter($filteredData, function($item) use ($tag) {
            return $item['tag'] === $tag;
        });
    }

    if ($status) {
        $filteredData = array_filter($filteredData, function($item) use ($status) {
            return $item['status'] === $status;
        });
    }

    echo json_encode(['code' => 0, 'message' => 'success', 'data' => array_values($filteredData)]);
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'add') {
        echo json_encode(['code' => 0, 'message' => '线索已录入', 'data' => ['id' => 'L' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT)]]);
    } elseif ($action === 'follow') {
        echo json_encode(['code' => 0, 'message' => '跟进记录已保存', 'data' => []]);
    } else {
        echo json_encode(['code' => 1, 'message' => '无效操作', 'data' => []]);
    }
} else {
    echo json_encode(['code' => 1, 'message' => '不支持的请求方法', 'data' => []]);
}
