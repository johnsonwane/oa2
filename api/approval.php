<?php
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $status = $_GET['status'] ?? '';

    $allData = [
        ['id' => 'APP001', 'type' => '请假申请', 'applicant' => '张三', 'department' => '销售部', 'summary' => '事假2天，家中急事', 'applyTime' => '2026-04-05 10:30', 'status' => '审批中', 'statusClass' => 'pending', 'currentApprover' => '李经理'],
        ['id' => 'APP002', 'type' => '报销申请', 'applicant' => '李四', 'department' => '市场部', 'summary' => '差旅费报销3500元', 'applyTime' => '2026-04-04 14:20', 'status' => '已通过', 'statusClass' => 'approved', 'currentApprover' => '-'],
        ['id' => 'APP003', 'type' => '转正申请', 'applicant' => '王五', 'department' => '技术部', 'summary' => '试用期已满，申请转正', 'applyTime' => '2026-04-03 09:15', 'status' => '已通过', 'statusClass' => 'approved', 'currentApprover' => '-'],
        ['id' => 'APP004', 'type' => '病假', 'applicant' => '赵六', 'department' => '客服部', 'summary' => '病假3天，医院证明', 'applyTime' => '2026-04-02 16:45', 'status' => '已通过', 'statusClass' => 'approved', 'currentApprover' => '-'],
        ['id' => 'APP005', 'type' => '报销申请', 'applicant' => '孙七', 'department' => '运营部', 'summary' => '办公用品采购报销1200元', 'applyTime' => '2026-04-01 11:00', 'status' => '已拒绝', 'statusClass' => 'rejected', 'currentApprover' => '-'],
        ['id' => 'APP006', 'type' => '离职申请', 'applicant' => '周八', 'department' => '销售部', 'summary' => '个人原因申请离职', 'applyTime' => '2026-03-31 15:30', 'status' => '审批中', 'statusClass' => 'pending', 'currentApprover' => '王总监'],
        ['id' => 'APP007', 'type' => '年假', 'applicant' => '吴九', 'department' => '财务部', 'summary' => '年假5天，探亲', 'applyTime' => '2026-03-30 08:45', 'status' => '已通过', 'statusClass' => 'approved', 'currentApprover' => '-'],
        ['id' => 'APP008', 'type' => '合同审批', 'applicant' => '郑十', 'department' => '法务部', 'summary' => '供应商合同审批', 'applyTime' => '2026-03-29 13:20', 'status' => '草稿', 'statusClass' => 'draft', 'currentApprover' => '-'],
        ['id' => 'APP009', 'type' => '请假申请', 'applicant' => '钱一', 'department' => '技术部', 'summary' => '年假7天，旅游', 'applyTime' => '2026-03-28 10:00', 'status' => '已通过', 'statusClass' => 'approved', 'currentApprover' => '-'],
        ['id' => 'APP010', 'type' => '报销申请', 'applicant' => '陈二', 'department' => '市场部', 'summary' => '团建费用报销2800元', 'applyTime' => '2026-03-27 14:15', 'status' => '审批中', 'statusClass' => 'pending', 'currentApprover' => '张经理'],
    ];

    if ($status) {
        $filteredData = array_filter($allData, function($item) use ($status) {
            return $item['status'] === $status;
        });
        echo json_encode(['code' => 0, 'message' => 'success', 'data' => array_values($filteredData)]);
    } else {
        echo json_encode(['code' => 0, 'message' => 'success', 'data' => $allData]);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'create') {
        echo json_encode(['code' => 0, 'message' => '审批申请已提交', 'data' => ['id' => 'APP' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT)]]);
    } elseif ($action === 'approve') {
        echo json_encode(['code' => 0, 'message' => '审批已通过', 'data' => []]);
    } elseif ($action === 'reject') {
        echo json_encode(['code' => 0, 'message' => '审批已拒绝', 'data' => []]);
    } else {
        echo json_encode(['code' => 1, 'message' => '无效操作', 'data' => []]);
    }
} else {
    echo json_encode(['code' => 1, 'message' => '不支持的请求方法', 'data' => []]);
}
