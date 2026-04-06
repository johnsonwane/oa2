<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-OA-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// 生成测试数据
function generateBonusData() {
    $names = ['张明', '李华', '王芳', '赵强', '刘洋', '陈静', '杨伟', '黄敏', '周杰', '吴婷', '郑伟', '孙丽'];
    $departments = ['销售部', '教务部', '运营部'];
    $bonusTypes = ['订单提成', '月度奖金', '季度奖金', '年度奖金', '活动奖励', '惩罚'];
    $typeClassMap = [
        '订单提成' => 'commission',
        '月度奖金' => 'monthly',
        '季度奖金' => 'quarterly',
        '年度奖金' => 'yearly',
        '活动奖励' => 'activity',
        '惩罚' => 'penalty'
    ];
    $statuses = ['calculating', 'confirmed', 'paid'];
    $statusMap = [
        'calculating' => '计算中',
        'confirmed' => '已确认',
        'paid' => '已发放'
    ];
    $statusClassMap = [
        'calculating' => 'calculating',
        'confirmed' => 'confirmed',
        'paid' => 'paid'
    ];
    
    $data = [];
    for ($i = 0; $i < 25; $i++) {
        $bonusType = $bonusTypes[array_rand($bonusTypes)];
        $amount = $bonusType === '惩罚' ? -rand(100, 1000) : rand(500, 15000);
        
        $period = '';
        $basis = '';
        if ($bonusType === '订单提成') {
            $period = date('Y-m', strtotime('-' . rand(0, 5) . ' months'));
            $basis = '本月成交订单' . rand(5, 30) . '单，提成比例' . (rand(5, 15) / 100) . '%';
        } elseif ($bonusType === '月度奖金') {
            $period = date('Y-m', strtotime('-' . rand(0, 5) . ' months'));
            $basis = '月度绩效考核' . ['A', 'B', 'C'][rand(0, 2)] . '级';
        } elseif ($bonusType === '季度奖金') {
            $q = ceil((date('n') - rand(0, 5)) / 3);
            $period = date('Y', strtotime('-' . rand(0, 5) . ' months')) . '第' . $q . '季度';
            $basis = '季度目标达成率' . rand(80, 120) . '%';
        } elseif ($bonusType === '年度奖金') {
            $period = date('Y', strtotime('-' . rand(0, 5) . ' months')) . '年度';
            $basis = '年度综合评估优秀';
        } elseif ($bonusType === '活动奖励') {
            $period = '春季招生活动';
            $basis = '活动期间表现突出';
        } elseif ($bonusType === '惩罚') {
            $period = date('Y-m', strtotime('-' . rand(0, 5) . ' months'));
            $basis = '迟到' . rand(1, 5) . '次/违规操作';
        }
        
        $status = $statuses[array_rand($statuses)];
        $paidTime = $status === 'paid' ? date('Y-m-d H:i:s', strtotime('-' . rand(0, 30) . ' days')) : '';
        
        $data[] = [
            'id' => 'SB' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
            'name' => $names[$i % count($names)],
            'department' => $departments[$i % count($departments)],
            'bonusType' => $bonusType,
            'typeClass' => $typeClassMap[$bonusType],
            'period' => $period,
            'amount' => $amount,
            'basis' => $basis,
            'status' => $status,
            'statusText' => $statusMap[$status],
            'statusClass' => $statusClassMap[$status],
            'paidTime' => $paidTime
        ];
    }
    return $data;
}

// 处理请求
if ($method === 'GET') {
    echo json_encode(['code' => 0, 'data' => generateBonusData()]);
} elseif ($method === 'POST') {
    $action = $input['action'] ?? '';
    
    if ($action === 'create') {
        echo json_encode(['code' => 0, 'message' => '奖金记录创建成功']);
    } elseif ($action === 'confirm') {
        echo json_encode(['code' => 0, 'message' => '奖金确认成功']);
    } elseif ($action === 'pay') {
        echo json_encode(['code' => 0, 'message' => '奖金发放成功']);
    } else {
        echo json_encode(['code' => 1, 'message' => '未知操作']);
    }
} else {
    echo json_encode(['code' => 1, 'message' => '不支持的请求方法']);
}
?>
