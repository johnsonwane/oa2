<?php
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $type = $_GET['type'] ?? '';

    $allData = [
        ['id' => 'F001', 'name' => '学员张三合同.pdf', 'type' => '合同', 'typeClass' => 'contract', 'related' => '学员张三', 'uploader' => '顾问A', 'uploadTime' => '2026-04-05 10:30', 'size' => '1.2MB', 'status' => '正常', 'statusClass' => 'normal', 'remark' => 'Python课程合同'],
        ['id' => 'F002', 'name' => '订单12345发票.pdf', 'type' => '发票', 'typeClass' => 'invoice', 'related' => '订单12345', 'uploader' => '财务-李四', 'uploadTime' => '2026-04-04 14:20', 'size' => '0.5MB', 'status' => '正常', 'statusClass' => 'normal', 'remark' => '普通发票'],
        ['id' => 'F003', 'name' => '结业证书-王五.jpg', 'type' => '证书', 'typeClass' => 'certificate', 'related' => '学员王五', 'uploader' => '教务-赵六', 'uploadTime' => '2026-04-03 09:15', 'size' => '2.3MB', 'status' => '正常', 'statusClass' => 'normal', 'remark' => 'Web前端课程'],
        ['id' => 'F004', 'name' => '机构营业执照.pdf', 'type' => '资质', 'typeClass' => 'qualification', 'related' => '机构', 'uploader' => '行政-孙七', 'uploadTime' => '2026-04-02 16:45', 'size' => '0.8MB', 'status' => '正常', 'statusClass' => 'normal', 'remark' => '2025年更新'],
        ['id' => 'F005', 'name' => '课程大纲.docx', 'type' => '其他', 'typeClass' => 'other', 'related' => '数据分析课程', 'uploader' => '课程-周八', 'uploadTime' => '2026-04-01 11:00', 'size' => '0.3MB', 'status' => '正常', 'statusClass' => 'normal', 'remark' => ''],
        ['id' => 'F006', 'name' => '学员李四合同.pdf', 'type' => '合同', 'typeClass' => 'contract', 'related' => '学员李四', 'uploader' => '顾问B', 'uploadTime' => '2026-03-31 15:30', 'size' => '1.1MB', 'status' => '正常', 'statusClass' => 'normal', 'remark' => 'Java课程合同'],
        ['id' => 'F007', 'name' => '订单67890发票.pdf', 'type' => '发票', 'typeClass' => 'invoice', 'related' => '订单67890', 'uploader' => '财务-吴九', 'uploadTime' => '2026-03-30 08:45', 'size' => '0.6MB', 'status' => '已过期', 'statusClass' => 'expired', 'remark' => '发票已换开'],
        ['id' => 'F008', 'name' => '结业证书-郑十.jpg', 'type' => '证书', 'typeClass' => 'certificate', 'related' => '学员郑十', 'uploader' => '教务-钱一', 'uploadTime' => '2026-03-29 13:20', 'size' => '2.1MB', 'status' => '正常', 'statusClass' => 'normal', 'remark' => 'AI课程'],
        ['id' => 'F009', 'name' => '办学许可证.pdf', 'type' => '资质', 'typeClass' => 'qualification', 'related' => '机构', 'uploader' => '行政-陈二', 'uploadTime' => '2026-03-28 10:00', 'size' => '1.5MB', 'status' => '正常', 'statusClass' => 'normal', 'remark' => '长期有效'],
        ['id' => 'F010', 'name' => '旧合同-已删除.pdf', 'type' => '合同', 'typeClass' => 'contract', 'related' => '学员林一', 'uploader' => '顾问A', 'uploadTime' => '2026-03-27 14:15', 'size' => '1.0MB', 'status' => '已删除', 'statusClass' => 'deleted', 'remark' => '学员取消报名'],
    ];

    if ($type) {
        $filteredData = array_filter($allData, function($item) use ($type) {
            return $item['type'] === $type;
        });
        echo json_encode(['code' => 0, 'message' => 'success', 'data' => array_values($filteredData)]);
    } else {
        echo json_encode(['code' => 0, 'message' => 'success', 'data' => $allData]);
    }
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'upload') {
        echo json_encode(['code' => 0, 'message' => '文件上传成功', 'data' => ['id' => 'F' . str_pad(rand(10, 99), 3, '0', STR_PAD_LEFT)]]);
    } elseif ($action === 'delete') {
        echo json_encode(['code' => 0, 'message' => '文件已删除', 'data' => []]);
    } else {
        echo json_encode(['code' => 1, 'message' => '无效操作', 'data' => []]);
    }
} else {
    echo json_encode(['code' => 1, 'message' => '不支持的请求方法', 'data' => []]);
}
