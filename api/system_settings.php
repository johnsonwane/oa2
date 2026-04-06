<?php
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $category = $_GET['category'] ?? '';

    $allData = [
        ['id' => 'CFG001', 'category' => '公司信息', 'categoryClass' => 'company', 'name' => '公司名称', 'value' => 'XX教育培训有限公司', 'description' => '公司注册名称'],
        ['id' => 'CFG002', 'category' => '公司信息', 'categoryClass' => 'company', 'name' => '联系电话', 'value' => '400-888-8888', 'description' => '公司客服电话'],
        ['id' => 'CFG003', 'category' => '公司信息', 'categoryClass' => 'company', 'name' => '公司地址', 'value' => '北京市朝阳区XXX大厦', 'description' => '公司办公地址'],
        ['id' => 'CFG004', 'category' => '通知设置', 'categoryClass' => 'notification', 'name' => '邮件通知', 'value' => '开启', 'description' => '是否启用邮件通知'],
        ['id' => 'CFG005', 'category' => '通知设置', 'categoryClass' => 'notification', 'name' => '短信通知', 'value' => '开启', 'description' => '是否启用短信通知'],
        ['id' => 'CFG006', 'category' => '通知设置', 'categoryClass' => 'notification', 'name' => '微信公众号通知', 'value' => '关闭', 'description' => '是否启用微信通知'],
        ['id' => 'CFG007', 'category' => '流程配置', 'categoryClass' => 'workflow', 'name' => '请假审批流程', 'value' => '主管→经理→总监', 'description' => '请假申请审批流程'],
        ['id' => 'CFG008', 'category' => '流程配置', 'categoryClass' => 'workflow', 'name' => '报销审批流程', 'value' => '部门经理→财务经理→总经理', 'description' => '费用报销审批流程'],
        ['id' => 'CFG009', 'category' => '流程配置', 'categoryClass' => 'workflow', 'name' => '转正审批流程', 'value' => '部门主管→HR→总经理', 'description' => '员工转正审批流程'],
        ['id' => 'CFG010', 'category' => '自定义字段', 'categoryClass' => 'custom', 'name' => '线索来源选项', 'value' => '抖音,微信,百度,线下,转介绍', 'description' => '线索来源渠道选项'],
        ['id' => 'CFG011', 'category' => '自定义字段', 'categoryClass' => 'custom', 'name' => '意向等级分类', 'value' => '高意向,中意向,低意向,无效', 'description' => '线索意向等级定义'],
        ['id' => 'CFG012', 'category' => '公司信息', 'categoryClass' => 'company', 'name' => '工作日设置', 'value' => '周一至周五', 'description' => '公司工作日'],
    ];

    if ($category) {
        $filteredData = array_filter($allData, function($item) use ($category) {
            return $item['category'] === $category;
        });
        echo json_encode(['code' => 0, 'message' => 'success', 'data' => array_values($filteredData)]);
    } else {
        echo json_encode(['code' => 0, 'message' => 'success', 'data' => $allData]);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'add') {
        echo json_encode(['code' => 0, 'message' => '配置已添加', 'data' => ['id' => 'CFG' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT)]]);
    } elseif ($action === 'update') {
        echo json_encode(['code' => 0, 'message' => '配置已更新', 'data' => []]);
    } else {
        echo json_encode(['code' => 1, 'message' => '无效操作', 'data' => []]);
    }
} else {
    echo json_encode(['code' => 1, 'message' => '不支持的请求方法', 'data' => []]);
}
