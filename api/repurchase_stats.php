<?php
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $allData = [
        [
            'id' => 'RS001',
            'period' => '2026-04',
            'leads' => 156,
            'followCount' => 468,
            'orders' => 47,
            'gmv' => '423000',
            'conversion' => '30.13',
            'avgOutput' => '2706',
            'topConsultant' => '顾问A(¥128000)',
            'topCourse' => 'Python编程入门(¥156000)',
            'change' => '+12.5'
        ],
        [
            'id' => 'RS002',
            'period' => '2026-03',
            'leads' => 139,
            'followCount' => 417,
            'orders' => 42,
            'gmv' => '376000',
            'conversion' => '30.22',
            'avgOutput' => '2705',
            'topConsultant' => '顾问B(¥115000)',
            'topCourse' => '数据分析实战(¥142000)',
            'change' => '+8.3'
        ],
        [
            'id' => 'RS003',
            'period' => '2026-02',
            'leads' => 128,
            'followCount' => 384,
            'orders' => 38,
            'gmv' => '342000',
            'conversion' => '29.69',
            'avgOutput' => '2672',
            'topConsultant' => '顾问A(¥108000)',
            'topCourse' => 'Java高级编程(¥128000)',
            'change' => '-2.1'
        ],
        [
            'id' => 'RS004',
            'period' => '2026-01',
            'leads' => 131,
            'followCount' => 393,
            'orders' => 39,
            'gmv' => '349500',
            'conversion' => '29.77',
            'avgOutput' => '2672',
            'topConsultant' => '顾问C(¥105000)',
            'topCourse' => 'Web前端开发(¥120000)',
            'change' => '+5.4'
        ],
        [
            'id' => 'RS005',
            'period' => '2025-12',
            'leads' => 124,
            'followCount' => 372,
            'orders' => 37,
            'gmv' => '331500',
            'conversion' => '29.84',
            'avgOutput' => '2674',
            'topConsultant' => '顾问A(¥110000)',
            'topCourse' => '人工智能基础(¥118000)',
            'change' => '+3.2'
        ],
        [
            'id' => 'RS006',
            'period' => '2025-11',
            'leads' => 120,
            'followCount' => 360,
            'orders' => 36,
            'gmv' => '324000',
            'conversion' => '30.00',
            'avgOutput' => '2700',
            'topConsultant' => '顾问B(¥102000)',
            'topCourse' => 'Python编程入门(¥115000)',
            'change' => '+1.5'
        ],
    ];

    echo json_encode(['code' => 0, 'message' => 'success', 'data' => $allData]);
} else {
    echo json_encode(['code' => 1, 'message' => '不支持的请求方法', 'data' => []]);
}
