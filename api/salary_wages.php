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
function generateSalaryData() {
    $names = ['张明', '李华', '王芳', '赵强', '刘洋', '陈静', '杨伟', '黄敏', '周杰', '吴婷'];
    $departments = ['销售部', '教务部', '运营部', '财务部'];
    $positions = ['销售经理', '销售顾问', '课程顾问', '助教', '讲师', '运营专员', '财务主管'];
    
    $data = [];
    for ($i = 0; $i < 20; $i++) {
        $baseSalary = rand(6000, 15000);
        $positionSalary = rand(1000, 5000);
        $performanceSalary = rand(0, 8000);
        $overtimePay = rand(0, 2000);
        
        $totalGross = $baseSalary + $positionSalary + $performanceSalary + $overtimePay;
        
        // 社保扣款约10-12%
        $socialSecurity = round($totalGross * (0.10 + rand(0, 2) / 100));
        // 公积金扣款约5-7%
        $housingFund = round($totalGross * (0.05 + rand(0, 2) / 100));
        // 个税
        $taxableIncome = max(0, $totalGross - 5000 - $socialSecurity - $housingFund);
        if ($taxableIncome <= 3000) $tax = round($taxableIncome * 0.03);
        elseif ($taxableIncome <= 12000) $tax = round($taxableIncome * 0.1 - 210);
        elseif ($taxableIncome <= 25000) $tax = round($taxableIncome * 0.2 - 1410);
        else $tax = round($taxableIncome * 0.25 - 2660);
        
        $otherDeduction = rand(0, 500);
        $netSalary = $totalGross - $socialSecurity - $housingFund - $tax - $otherDeduction;
        
        $status = rand(0, 1) === 0 ? 'pending' : 'paid';
        $paidDate = $status === 'paid' ? date('Y-m-d', strtotime('-' . rand(0, 30) . ' days')) : '';
        
        $data[] = [
            'id' => 'SW' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
            'name' => $names[$i % count($names)],
            'department' => $departments[$i % count($departments)],
            'position' => $positions[$i % count($positions)],
            'salaryMonth' => date('Y-m', strtotime('-' . rand(0, 5) . ' months')),
            'baseSalary' => $baseSalary,
            'positionSalary' => $positionSalary,
            'performanceSalary' => $performanceSalary,
            'overtimePay' => $overtimePay,
            'totalGross' => $totalGross,
            'socialSecurity' => $socialSecurity,
            'housingFund' => $housingFund,
            'tax' => $tax,
            'otherDeduction' => $otherDeduction,
            'netSalary' => $netSalary,
            'status' => $status,
            'statusText' => $status === 'pending' ? '待确认' : '已发放',
            'statusClass' => $status === 'pending' ? 'pending' : 'paid',
            'paidDate' => $paidDate
        ];
    }
    return $data;
}

// 处理请求
if ($method === 'GET') {
    echo json_encode(['code' => 0, 'data' => generateSalaryData()]);
} elseif ($method === 'POST') {
    $action = $input['action'] ?? '';
    
    if ($action === 'create') {
        echo json_encode(['code' => 0, 'message' => '工资单创建成功']);
    } elseif ($action === 'confirm') {
        echo json_encode(['code' => 0, 'message' => '工资发放确认成功']);
    } else {
        echo json_encode(['code' => 1, 'message' => '未知操作']);
    }
} else {
    echo json_encode(['code' => 1, 'message' => '不支持的请求方法']);
}
?>
