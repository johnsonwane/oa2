<?php
header('Content-Type: application/json; charset=utf-8');

// 简单的响应格式
echo json_encode([
    'code' => 0,
    'message' => 'success',
    'data' => [
        [
            'stage' => '曝光',
            'current' => 100000,
            'previous' => 95000,
            'conversion' => '100.00',
            'dropoff' => '0.00',
            'change' => '+5.26'
        ],
        [
            'stage' => '点击',
            'current' => 7500,
            'previous' => 7125,
            'conversion' => '7.50',
            'dropoff' => '92.50',
            'change' => '+5.26'
        ],
        [
            'stage' => '留资',
            'current' => 1875,
            'previous' => 1781,
            'conversion' => '25.00',
            'dropoff' => '75.00',
            'change' => '+5.28'
        ],
        [
            'stage' => '电销接通',
            'current' => 1125,
            'previous' => 1069,
            'conversion' => '60.00',
            'dropoff' => '40.00',
            'change' => '+5.24'
        ],
        [
            'stage' => '邀约到店',
            'current' => 675,
            'previous' => 641,
            'conversion' => '60.00',
            'dropoff' => '40.00',
            'change' => '+5.30'
        ],
        [
            'stage' => '体验课',
            'current' => 473,
            'previous' => 449,
            'conversion' => '70.00',
            'dropoff' => '30.00',
            'change' => '+5.35'
        ],
        [
            'stage' => '报名',
            'current' => 284,
            'previous' => 269,
            'conversion' => '60.00',
            'dropoff' => '40.00',
            'change' => '+5.58'
        ],
        [
            'stage' => '付费',
            'current' => 227,
            'previous' => 215,
            'conversion' => '80.00',
            'dropoff' => '20.00',
            'change' => '+5.58'
        ]
    ]
]);
