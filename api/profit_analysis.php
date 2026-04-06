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
function generateProfitAnalysis() {
    $departments = ['销售部', '教务部', '运营部', '线上渠道', '线下渠道', '合作渠道'];
    
    $data = [];
    $id = 1;
    
    // 生成近12个月数据
    for ($i = 0; $i < 12; $i++) {
        $month = date('Y-m', strtotime("-$i months"));
        
        foreach ($departments as $dept) {
            $gmv = rand(50000, 200000);
            $directCost = round($gmv * (0.30 + rand(0, 15) / 100));
            $indirectCost = round($gmv * (0.15 + rand(0, 10) / 100));
            $totalCost = $directCost + $indirectCost;
            $grossProfit = $gmv - $totalCost;
            $grossMargin = $gmv > 0 ? round($grossProfit / $gmv * 100, 2) : 0;
            
            $staffCount = rand(5, 20);
            $perHeadGmv = $staffCount > 0 ? round($gmv / $staffCount) : 0;
            
            $yoyChange = rand(-15, 25);
            $momChange = $i === 0 ? 0 : rand(-10, 20);
            
            $data[] = [
                'id' => 'PA' . str_pad($id, 5, '0', STR_PAD_LEFT),
                'month' => $month,
                'department' => $dept,
                'gmv' => $gmv,
                'directCost' => $directCost,
                'indirectCost' => $indirectCost,
                'totalCost' => $totalCost,
                'grossProfit' => $grossProfit,
                'grossMargin' => $grossMargin,
                'perHeadGmv' => $perHeadGmv,
                'yoyChange' => $yoyChange,
                'momChange' => $momChange
            ];
            $id++;
        }
    }
    
    return array_slice($data, 0, 50);
}

echo json_encode(['code' => 0, 'data' => generateProfitAnalysis()]);
?>
