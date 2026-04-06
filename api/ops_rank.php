<?php
header('Content-Type: application/json; charset=utf-8');

$type = $_GET['type'] ?? 'consultant';

$consultantData = [
    ['id' => 1, 'name' => '顾问A', 'metric' => '成单数', 'value' => 45, 'unit' => '单', 'change' => '+12.5'],
    ['id' => 2, 'name' => '顾问B', 'metric' => '成单数', 'value' => 38, 'unit' => '单', 'change' => '+8.6'],
    ['id' => 3, 'name' => '顾问C', 'metric' => '成单数', 'value' => 32, 'unit' => '单', 'change' => '-3.0'],
    ['id' => 4, 'name' => '顾问D', 'metric' => '成单数', 'value' => 28, 'unit' => '单', 'change' => '+5.2'],
    ['id' => 5, 'name' => '顾问E', 'metric' => '成单数', 'value' => 25, 'unit' => '单', 'change' => '+10.0'],
];

$channelData = [
    ['id' => 1, 'name' => '抖音广告', 'metric' => 'GMV', 'value' => 1250000, 'unit' => '元', 'change' => '+15.3'],
    ['id' => 2, 'name' => '微信朋友圈', 'metric' => 'GMV', 'value' => 980000, 'unit' => '元', 'change' => '+8.7'],
    ['id' => 3, 'name' => '百度搜索', 'metric' => 'GMV', 'value' => 750000, 'unit' => '元', 'change' => '-2.5'],
    ['id' => 4, 'name' => '小红书', 'metric' => 'GMV', 'value' => 560000, 'unit' => '元', 'change' => '+20.1'],
    ['id' => 5, 'name' => '线下活动', 'metric' => 'GMV', 'value' => 420000, 'unit' => '元', 'change' => '+5.8'],
];

$courseData = [
    ['id' => 1, 'name' => 'Python编程入门', 'metric' => '好评率', 'value' => 98.5, 'unit' => '%', 'change' => '+1.2'],
    ['id' => 2, 'name' => '数据分析实战', 'metric' => '好评率', 'value' => 97.2, 'unit' => '%', 'change' => '+0.8'],
    ['id' => 3, 'name' => 'Web前端开发', 'metric' => '好评率', 'value' => 96.8, 'unit' => '%', 'change' => '-0.5'],
    ['id' => 4, 'name' => 'Java高级编程', 'metric' => '好评率', 'value' => 95.5, 'unit' => '%', 'change' => '+2.1'],
    ['id' => 5, 'name' => '人工智能基础', 'metric' => '好评率', 'value' => 94.8, 'unit' => '%', 'change' => '+3.5'],
];

switch($type) {
    case 'consultant':
        $data = $consultantData;
        break;
    case 'channel':
        $data = $channelData;
        break;
    case 'course':
        $data = $courseData;
        break;
    default:
        $data = $consultantData;
}

echo json_encode([
    'code' => 0,
    'message' => 'success',
    'data' => $data
]);
