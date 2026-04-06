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
function generateRefundData() {
    $statuses = ['pending', 'approved', 'rejected', 'processing', 'completed'];
    $statusMap = [
        'pending' => ['text' => '待审批', 'class' => 'pending'],
        'approved' => ['text' => '审批通过', 'class' => 'approved'],
        'rejected' => ['text' => '审批拒绝', 'class' => 'rejected'],
        'processing' => ['text' => '退款中', 'class' => 'processing'],
        'completed' => ['text' => '已退款', 'class' => 'completed']
    ];
    $reasons = ['课程不合适', '时间冲突', '经济困难', '教学质量', '其他'];
    $applicants = ['张三', '李四', '王五', '赵六'];
    $approvers = ['经理A', '经理B', '总监', ''];
    
    $data = [];
    for ($i = 1; $i <= 15; $i++) {
        $status = $statuses[array_rand($statuses)];
        $orderAmount = rand(5000, 20000);
        $paidAmount = $orderAmount * (0.5 + rand(0, 4) / 10);
        $refundAmount = $paidAmount * (0.5 + rand(0, 5) / 10);
        
        $applyTime = date('Y-m-d H:i:s', time() - rand(0, 2592000));
        $approveTime = $status !== 'pending' ? date('Y-m-d H:i:s', strtotime($applyTime) + rand(86400, 432000)) : '';
        $completeTime = $status === 'completed' ? date('Y-m-d H:i:s', strtotime($approveTime) + rand(172800, 604800)) : '';
        
        $data[] = [
            'id' => 'RF' . str_pad($i, 5, '0', STR_PAD_LEFT),
            'orderNo' => 'ORD' . date('Ymd') . str_pad($i, 4, '0', STR_PAD_LEFT),
            'studentName' => '学员' . $i,
            'courseName' => ['Python编程入门', '数据分析实战', 'Java企业开发', 'Web全栈开发', '机器学习基础'][rand(0, 4)],
            'orderAmount' => $orderAmount,
            'paidAmount' => round($paidAmount, 2),
            'refundAmount' => round($refundAmount, 2),
            'reason' => $reasons[array_rand($reasons)],
            'status' => $status,
            'statusText' => $statusMap[$status]['text'],
            'statusClass' => $statusMap[$status]['class'],
            'applicant' => $applicants[array_rand($applicants)],
            'approver' => $status === 'pending' ? '' : $approvers[array_rand(array_slice($approvers, 0, 3))],
            'applyTime' => $applyTime,
            'approveTime' => $approveTime,
            'completeTime' => $completeTime
        ];
    }
    return $data;
}

// 处理请求
if ($method === 'GET') {
    echo json_encode(['code' => 0, 'data' => generateRefundData()]);
} elseif ($method === 'POST') {
    $action = $input['action'] ?? '';
    
    if ($action === 'create') {
        // 创建退款申请
        echo json_encode(['code' => 0, 'message' => '退款申请创建成功']);
    } elseif ($action === 'approve') {
        // 审批退款
        echo json_encode(['code' => 0, 'message' => '退款审批成功']);
    } elseif ($action === 'execute') {
        // 执行退款
        echo json_encode(['code' => 0, 'message' => '退款执行成功']);
    } else {
        echo json_encode(['code' => 1, 'message' => '未知操作']);
    }
} else {
    echo json_encode(['code' => 1, 'message' => '不支持的请求方法']);
}
?>
