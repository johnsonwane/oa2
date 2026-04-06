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
function generateFinanceStats() {
    $data = [];
    for ($i = 0; $i < 12; $i++) {
        $month = date('Y-m', strtotime("-$i months"));
        $tuitionIncome = rand(150000, 300000);
        $otherIncome = rand(10000, 50000);
        $totalIncome = $tuitionIncome + $otherIncome;
        
        $staffCost = round($totalIncome * (0.25 + rand(0, 10) / 100));
        $opsCost = round($totalIncome * (0.10 + rand(0, 5) / 100));
        $refundAmount = round($totalIncome * (0.03 + rand(0, 5) / 100));
        $bonusPaid = round($totalIncome * (0.08 + rand(0, 5) / 100));
        
        $totalExpense = $staffCost + $opsCost + $refundAmount + $bonusPaid;
        $netBalance = $totalIncome - $totalExpense;
        
        $data[] = [
            'id' => 'FS' . str_pad(12 - $i, 4, '0', STR_PAD_LEFT),
            'period' => $month,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'netBalance' => $netBalance,
            'tuitionIncome' => $tuitionIncome,
            'otherIncome' => $otherIncome,
            'staffCost' => $staffCost,
            'opsCost' => $opsCost,
            'refundAmount' => $refundAmount,
            'bonusPaid' => $bonusPaid,
            'statTime' => $month . '-01 00:00:00'
        ];
    }
    return $data;
}

echo json_encode(['code' => 0, 'data' => generateFinanceStats()]);
?>
