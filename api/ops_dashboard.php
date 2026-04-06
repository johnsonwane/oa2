<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-OA-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 生成测试数据
function generateOpsDashboard() {
    // 仪表盘卡片数据
    $dashboard = [
        'todayLeads' => rand(15, 45),
        'yesterdayLeads' => rand(15, 45),
        'leadsTrend' => rand(-15, 30),
        
        'monthNewStudents' => rand(80, 150),
        'lastMonthStudents' => rand(80, 150),
        'studentsTrend' => rand(-10, 25),
        
        'monthGmv' => rand(500000, 1500000),
        'gmvTrend' => rand(-10, 30),
        
        'monthPayment' => rand(400000, 1200000),
        'paymentTrend' => rand(-15, 25),
        
        'monthRefund' => rand(10000, 50000),
        'refundRate' => round(rand(2, 8) + rand(0, 9) / 10, 1),
        
        'monthProfit' => rand(50000, 300000) * (rand(0, 1) === 0 ? 1 : -1),
        'profitTrend' => rand(-20, 35),
        
        'activeStudents' => rand(300, 600),
        'occupiedSeats' => rand(250, 550),
        
        'renewalStudents' => rand(30, 80),
        'renewalRate' => rand(50, 85)
    ];
    
    // 7天趋势数据
    $trends = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $leads = rand(10, 50);
        $students = rand(5, 20);
        $gmv = rand(10000, 50000);
        $payment = rand(8000, 45000);
        $refund = rand(500, 3000);
        $profit = $gmv * 0.35 - $refund;
        
        $trends[] = [
            'date' => $date,
            'leads' => $leads,
            'leadsTrend' => $i === 6 ? 0 : rand(-20, 30),
            'students' => $students,
            'studentsTrend' => $i === 6 ? 0 : rand(-15, 25),
            'gmv' => $gmv,
            'payment' => $payment,
            'refund' => $refund,
            'profit' => $profit
        ];
    }
    
    return [
        'dashboard' => $dashboard,
        'trends' => $trends
    ];
}

echo json_encode(['code' => 0, 'data' => generateOpsDashboard()]);
?>
