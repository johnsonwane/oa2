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
function generateCostProfit() {
    $courses = [
        'Python编程入门', '数据分析实战', 'Java企业开发', 'Web全栈开发', 
        '机器学习基础', '数据结构与算法', 'Spring Boot实战', 
        'Vue3全家桶', 'Docker容器化', 'Kubernetes入门'
    ];
    $periods = ['2026春季班', '2026夏季班', '2026秋季班', '2026冬季班'];
    
    $data = [];
    $id = 1;
    foreach ($courses as $course) {
        foreach ($periods as $period) {
            $studentCount = rand(15, 40);
            $income = $studentCount * rand(3000, 8000);
            
            // 教师课酬占比约30-40%
            $teacherCost = round($income * (0.30 + rand(0, 10) / 100));
            $assistantCost = round($income * (0.05 + rand(0, 5) / 100));
            $materialCost = round($income * (0.03 + rand(0, 3) / 100));
            $opsCost = round($income * (0.15 + rand(0, 10) / 100));
            $otherCost = round($income * (0.02 + rand(0, 3) / 100));
            
            $totalCost = $teacherCost + $assistantCost + $materialCost + $opsCost + $otherCost;
            $netProfit = $income - $totalCost;
            $profitRate = $income > 0 ? round($netProfit / $income * 100, 2) : 0;
            $perHeadCost = $studentCount > 0 ? round($totalCost / $studentCount, 2) : 0;
            
            // 利润率等级
            if ($profitRate >= 30) $rateLevel = 'good';
            elseif ($profitRate >= 15) $rateLevel = 'normal';
            else $rateLevel = 'low';
            
            $data[] = [
                'id' => 'CP' . str_pad($id, 4, '0', STR_PAD_LEFT),
                'courseName' => $course,
                'period' => $period,
                'studentCount' => $studentCount,
                'income' => $income,
                'teacherCost' => $teacherCost,
                'assistantCost' => $assistantCost,
                'materialCost' => $materialCost,
                'opsCost' => $opsCost,
                'otherCost' => $otherCost,
                'totalCost' => $totalCost,
                'netProfit' => $netProfit,
                'profitRate' => $profitRate,
                'rateLevel' => $rateLevel,
                'perHeadCost' => $perHeadCost
            ];
            $id++;
        }
    }
    return $data;
}

echo json_encode(['code' => 0, 'data' => generateCostProfit()]);
?>
