<?php
require_once __DIR__ . '/db.php';

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

function json_response(int $code, string $message, $data = null, int $httpCode = 200): void
{
    http_response_code($httpCode);
    echo json_encode([
        'code' => $code,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function request_body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function require_fields(array $data, array $fields): void
{
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
            json_response(400, "字段 {$field} 不能为空", null, 400);
        }
    }
}

function crm_sales_definitions(): array
{
    return [
        'customer_files' => [
            'required' => ['customer_name', 'mobile', 'course_name'],
            'numeric' => ['age', 'total_amount'],
            'defaults' => [
                'customer_name' => '',
                'gender' => '女',
                'age' => 25,
                'mobile' => '',
                'wechat' => '',
                'city' => '',
                'occupation' => '',
                'course_name' => '',
                'intention_level' => 'B',
                'source' => '',
                'first_consult_date' => '',
                'last_follow_date' => '',
                'follow_status' => '活跃',
                'total_amount' => 0,
                'consultant' => '',
                'remark' => '',
            ],
        ],
        'followup_management' => [
            'required' => ['customer_name', 'follower', 'follow_method', 'followup_time', 'summary'],
            'numeric' => [],
            'defaults' => [
                'customer_name' => '',
                'follower' => '',
                'follow_method' => '微信',
                'followup_time' => '',
                'summary' => '',
                'next_followup_time' => '',
                'status' => '待跟进',
                'remark' => '',
            ],
        ],
        'customer_transfer' => [
            'required' => ['customer_name', 'from_consultant', 'to_consultant', 'transfer_type', 'transfer_time'],
            'numeric' => [],
            'defaults' => [
                'customer_name' => '',
                'from_consultant' => '',
                'to_consultant' => '',
                'transfer_type' => '转让',
                'reason' => '',
                'transfer_time' => '',
                'operator' => '',
                'remark' => '',
            ],
        ],
        'deal_management' => [
            'required' => ['order_no', 'customer_name', 'course_name', 'order_type', 'order_amount', 'order_time'],
            'numeric' => ['order_amount', 'paid_amount'],
            'defaults' => [
                'order_no' => '',
                'customer_name' => '',
                'course_name' => '',
                'order_type' => '首单',
                'order_amount' => 0,
                'paid_amount' => 0,
                'payment_status' => '待付款',
                'order_time' => '',
                'signing_consultant' => '',
                'sales_owner' => '',
                'related_student' => '',
                'remark' => '',
            ],
        ],
        'deal_status' => [
            'required' => ['order_no', 'customer_name', 'current_status', 'changed_at', 'changed_by'],
            'numeric' => [],
            'defaults' => [
                'order_no' => '',
                'customer_name' => '',
                'current_status' => '待付款',
                'changed_at' => '',
                'changed_by' => '',
                'change_reason' => '',
                'remark' => '',
            ],
        ],
        'deal_stats' => [
            'required' => ['consultant_name', 'department', 'period'],
            'numeric' => ['ranking', 'first_order_count', 'renewal_order_count', 'upgrade_order_count', 'gmv', 'received_amount', 'refund_amount', 'gross_profit', 'efficiency'],
            'defaults' => [
                'ranking' => 0,
                'consultant_name' => '',
                'department' => '',
                'first_order_count' => 0,
                'renewal_order_count' => 0,
                'upgrade_order_count' => 0,
                'gmv' => 0,
                'received_amount' => 0,
                'refund_amount' => 0,
                'gross_profit' => 0,
                'efficiency' => 0,
                'period' => '',
                'remark' => '',
            ],
        ],
    ];
}

function crm_sales_seed_data(): array
{
    return [
        'customer_files' => [
            ['id' => 1, 'customer_name' => '张三', 'gender' => '男', 'age' => 29, 'mobile' => '13800001231', 'wechat' => 'zhangsan_py', 'city' => '北京', 'occupation' => '互联网产品经理', 'course_name' => 'Python全栈', 'intention_level' => 'A', 'source' => '抖音', 'first_consult_date' => '2026-03-02', 'last_follow_date' => '2026-04-04', 'follow_status' => '活跃', 'total_amount' => 15800, 'consultant' => '陈晨', 'remark' => '已签二阶段项目实战'],
            ['id' => 2, 'customer_name' => '李四', 'gender' => '女', 'age' => 26, 'mobile' => '13800001232', 'wechat' => 'lisi_media', 'city' => '上海', 'occupation' => '品牌运营', 'course_name' => '新媒体运营', 'intention_level' => 'A', 'source' => '小红书', 'first_consult_date' => '2026-03-05', 'last_follow_date' => '2026-04-05', 'follow_status' => '活跃', 'total_amount' => 12800, 'consultant' => '何敏', 'remark' => '已续单私域增长模块'],
            ['id' => 3, 'customer_name' => '王五', 'gender' => '男', 'age' => 31, 'mobile' => '13800001233', 'wechat' => 'wangwu_ai', 'city' => '广州', 'occupation' => '行政主管', 'course_name' => 'AI应用办公', 'intention_level' => 'B', 'source' => '百度', 'first_consult_date' => '2026-03-08', 'last_follow_date' => '2026-04-01', 'follow_status' => '沉默', 'total_amount' => 6980, 'consultant' => '刘洋', 'remark' => '企业团报意向需二次沟通'],
            ['id' => 4, 'customer_name' => '赵六', 'gender' => '女', 'age' => 24, 'mobile' => '13800001234', 'wechat' => 'zhaoliu_video', 'city' => '深圳', 'occupation' => '短视频编导', 'course_name' => '短视频制作', 'intention_level' => 'A', 'source' => '转介绍', 'first_consult_date' => '2026-02-28', 'last_follow_date' => '2026-04-06', 'follow_status' => '活跃', 'total_amount' => 9800, 'consultant' => '周倩', 'remark' => '历史订单 2 笔，复购意愿高'],
            ['id' => 5, 'customer_name' => '孙七', 'gender' => '男', 'age' => 33, 'mobile' => '13800001235', 'wechat' => 'sunqi_live', 'city' => '杭州', 'occupation' => '电商店主', 'course_name' => '电商直播', 'intention_level' => 'A', 'source' => '微信朋友圈', 'first_consult_date' => '2026-03-12', 'last_follow_date' => '2026-04-03', 'follow_status' => '活跃', 'total_amount' => 18800, 'consultant' => '陈晨', 'remark' => '本月准备升级陪跑套餐'],
            ['id' => 6, 'customer_name' => '周八', 'gender' => '女', 'age' => 27, 'mobile' => '13800001236', 'wechat' => 'zhouba_growth', 'city' => '成都', 'occupation' => '私域运营', 'course_name' => '新媒体运营', 'intention_level' => 'B', 'source' => '抖音', 'first_consult_date' => '2026-03-16', 'last_follow_date' => '2026-03-30', 'follow_status' => '沉默', 'total_amount' => 8800, 'consultant' => '许凯', 'remark' => '需要等发薪后安排补尾款'],
            ['id' => 7, 'customer_name' => '吴九', 'gender' => '男', 'age' => 30, 'mobile' => '13800001237', 'wechat' => 'wujiu_data', 'city' => '武汉', 'occupation' => '数据分析师', 'course_name' => 'Python全栈', 'intention_level' => 'B', 'source' => '百度', 'first_consult_date' => '2026-03-10', 'last_follow_date' => '2026-04-02', 'follow_status' => '活跃', 'total_amount' => 14200, 'consultant' => '何敏', 'remark' => '已报名就业冲刺班'],
            ['id' => 8, 'customer_name' => '郑十', 'gender' => '女', 'age' => 28, 'mobile' => '13800001238', 'wechat' => 'zhengshi_office', 'city' => '苏州', 'occupation' => '财务专员', 'course_name' => 'AI应用办公', 'intention_level' => 'C', 'source' => '小红书', 'first_consult_date' => '2026-03-20', 'last_follow_date' => '2026-03-25', 'follow_status' => '流失', 'total_amount' => 0, 'consultant' => '刘洋', 'remark' => '预算不足转免费公开课池'],
            ['id' => 9, 'customer_name' => '钱一', 'gender' => '男', 'age' => 35, 'mobile' => '13800001239', 'wechat' => 'qianyi_owner', 'city' => '南京', 'occupation' => '培训机构负责人', 'course_name' => '电商直播', 'intention_level' => 'A', 'source' => '转介绍', 'first_consult_date' => '2026-03-01', 'last_follow_date' => '2026-04-05', 'follow_status' => '活跃', 'total_amount' => 26800, 'consultant' => '周倩', 'remark' => '企业内训签约中'],
            ['id' => 10, 'customer_name' => '冯二', 'gender' => '女', 'age' => 23, 'mobile' => '13800001240', 'wechat' => 'fenger_creator', 'city' => '西安', 'occupation' => '自由职业者', 'course_name' => '短视频制作', 'intention_level' => 'B', 'source' => '微信朋友圈', 'first_consult_date' => '2026-03-18', 'last_follow_date' => '2026-04-04', 'follow_status' => '活跃', 'total_amount' => 7600, 'consultant' => '许凯', 'remark' => '准备加购账号运营服务'],
        ],
        'followup_management' => [
            ['id' => 1, 'customer_name' => '张三', 'follower' => '陈晨', 'follow_method' => '电话', 'followup_time' => '2026-04-04 10:00:00', 'summary' => '确认学习计划与到课时间，客户希望本周末开营。', 'next_followup_time' => '2026-04-08 15:00:00', 'status' => '待跟进', 'remark' => '需发送开课群二维码'],
            ['id' => 2, 'customer_name' => '李四', 'follower' => '何敏', 'follow_method' => '微信', 'followup_time' => '2026-04-05 11:30:00', 'summary' => '已确认续单内容，等待客户走报销流程。', 'next_followup_time' => '2026-04-09 11:00:00', 'status' => '待跟进', 'remark' => '客户公司统一周四审批'],
            ['id' => 3, 'customer_name' => '王五', 'follower' => '刘洋', 'follow_method' => '短信', 'followup_time' => '2026-04-01 18:20:00', 'summary' => '发送AI办公试学资料，客户暂未回复。', 'next_followup_time' => '2026-04-07 20:00:00', 'status' => '待跟进', 'remark' => '建议晚上回访'],
            ['id' => 4, 'customer_name' => '赵六', 'follower' => '周倩', 'follow_method' => '面谈', 'followup_time' => '2026-04-06 14:00:00', 'summary' => '现场沟通增课方案，客户认可直播拆解模块。', 'next_followup_time' => '2026-04-10 16:00:00', 'status' => '已完成', 'remark' => '等待合同盖章'],
            ['id' => 5, 'customer_name' => '孙七', 'follower' => '陈晨', 'follow_method' => '微信', 'followup_time' => '2026-04-03 09:45:00', 'summary' => '确认直播陪跑周期，客户要求本月内排期。', 'next_followup_time' => '2026-04-06 19:30:00', 'status' => '待跟进', 'remark' => '重点推进升级包'],
            ['id' => 6, 'customer_name' => '周八', 'follower' => '许凯', 'follow_method' => '电话', 'followup_time' => '2026-03-30 16:15:00', 'summary' => '客户反馈当前资金紧张，计划下月补尾款。', 'next_followup_time' => '2026-04-12 10:30:00', 'status' => '待跟进', 'remark' => '可提供分期方案'],
            ['id' => 7, 'customer_name' => '吴九', 'follower' => '何敏', 'follow_method' => '微信', 'followup_time' => '2026-04-02 13:20:00', 'summary' => '已完成入学资料收集并同步班主任。', 'next_followup_time' => '2026-04-15 09:00:00', 'status' => '已完成', 'remark' => '转交教务群跟进'],
            ['id' => 8, 'customer_name' => '郑十', 'follower' => '刘洋', 'follow_method' => '电话', 'followup_time' => '2026-03-25 17:10:00', 'summary' => '预算不足，推荐低价体验营方案被婉拒。', 'next_followup_time' => '2026-04-20 18:00:00', 'status' => '已放弃', 'remark' => '保留公开课触达'],
            ['id' => 9, 'customer_name' => '钱一', 'follower' => '周倩', 'follow_method' => '面谈', 'followup_time' => '2026-04-05 15:40:00', 'summary' => '企业内训需求明确，客户要求出正式报价单。', 'next_followup_time' => '2026-04-07 10:00:00', 'status' => '待跟进', 'remark' => '报价需抄送老板'],
            ['id' => 10, 'customer_name' => '冯二', 'follower' => '许凯', 'follow_method' => '微信', 'followup_time' => '2026-04-04 21:00:00', 'summary' => '客户确认短视频账号诊断服务，倾向本周付款。', 'next_followup_time' => '2026-04-06 21:00:00', 'status' => '待跟进', 'remark' => '可顺带推荐剪映模板包'],
        ],
        'customer_transfer' => [
            ['id' => 1, 'customer_name' => '张三', 'from_consultant' => '刘洋', 'to_consultant' => '陈晨', 'transfer_type' => '升级', 'reason' => '客户转入高客单就业班，需要资深顾问承接。', 'transfer_time' => '2026-03-18 10:30:00', 'operator' => '销售主管-王倩', 'remark' => '已同步历史聊天记录'],
            ['id' => 2, 'customer_name' => '李四', 'from_consultant' => '陈晨', 'to_consultant' => '何敏', 'transfer_type' => '转让', 'reason' => '客户偏新媒体方向，由对应专项顾问接手。', 'transfer_time' => '2026-03-20 14:10:00', 'operator' => '销售主管-王倩', 'remark' => '线索热度保持 A 级'],
            ['id' => 3, 'customer_name' => '王五', 'from_consultant' => '周倩', 'to_consultant' => '刘洋', 'transfer_type' => '认领', 'reason' => '原顾问离职后重新分配客户池。', 'transfer_time' => '2026-03-22 09:20:00', 'operator' => '人事-赵琳', 'remark' => '需尽快恢复触达'],
            ['id' => 4, 'customer_name' => '赵六', 'from_consultant' => '许凯', 'to_consultant' => '周倩', 'transfer_type' => '升级', 'reason' => '客户进入直播增课环节，需要大客户经验。', 'transfer_time' => '2026-03-25 16:40:00', 'operator' => '销售经理-李娜', 'remark' => '客户配合度高'],
            ['id' => 5, 'customer_name' => '孙七', 'from_consultant' => '何敏', 'to_consultant' => '陈晨', 'transfer_type' => '转让', 'reason' => '客户需要电商直播专项成交策略支持。', 'transfer_time' => '2026-03-27 11:15:00', 'operator' => '销售经理-李娜', 'remark' => '直播案例库已共享'],
            ['id' => 6, 'customer_name' => '周八', 'from_consultant' => '陈晨', 'to_consultant' => '许凯', 'transfer_type' => '降级', 'reason' => '客户预算收缩，转入标准咨询跟进池。', 'transfer_time' => '2026-03-29 19:00:00', 'operator' => '销售主管-王倩', 'remark' => '保留后续复活机会'],
            ['id' => 7, 'customer_name' => '吴九', 'from_consultant' => '刘洋', 'to_consultant' => '何敏', 'transfer_type' => '转让', 'reason' => '客户要求周末集中沟通，由弹性排班顾问接手。', 'transfer_time' => '2026-04-01 13:00:00', 'operator' => '销售主管-王倩', 'remark' => '已补充意向课程记录'],
            ['id' => 8, 'customer_name' => '郑十', 'from_consultant' => '许凯', 'to_consultant' => '刘洋', 'transfer_type' => '认领', 'reason' => '从沉默客户池重新领回并尝试公开课转化。', 'transfer_time' => '2026-04-02 10:10:00', 'operator' => '销售专员-刘洋', 'remark' => '先推体验营'],
            ['id' => 9, 'customer_name' => '钱一', 'from_consultant' => '何敏', 'to_consultant' => '周倩', 'transfer_type' => '升级', 'reason' => '企业客户预算提升，切换至大单签约顾问。', 'transfer_time' => '2026-04-04 15:25:00', 'operator' => '销售经理-李娜', 'remark' => '预计本周签约'],
            ['id' => 10, 'customer_name' => '冯二', 'from_consultant' => '周倩', 'to_consultant' => '许凯', 'transfer_type' => '转让', 'reason' => '客户更偏内容陪跑，由短视频专项顾问接手。', 'transfer_time' => '2026-04-05 17:35:00', 'operator' => '销售主管-王倩', 'remark' => '同步账号诊断报告'],
        ],
        'deal_management' => [
            ['id' => 1, 'order_no' => 'OA20260401001', 'customer_name' => '张三', 'course_name' => 'Python全栈', 'order_type' => '首单', 'order_amount' => 15800, 'paid_amount' => 15800, 'payment_status' => '已付清', 'order_time' => '2026-04-01 09:15:00', 'signing_consultant' => '陈晨', 'sales_owner' => '陈晨', 'related_student' => '张三', 'remark' => '线下面签'],
            ['id' => 2, 'order_no' => 'OA20260401002', 'customer_name' => '李四', 'course_name' => '新媒体运营', 'order_type' => '续单', 'order_amount' => 6800, 'paid_amount' => 3000, 'payment_status' => '部分付款', 'order_time' => '2026-04-01 11:20:00', 'signing_consultant' => '何敏', 'sales_owner' => '何敏', 'related_student' => '李四', 'remark' => '企业报销中'],
            ['id' => 3, 'order_no' => 'OA20260401003', 'customer_name' => '王五', 'course_name' => 'AI应用办公', 'order_type' => '首单', 'order_amount' => 6980, 'paid_amount' => 0, 'payment_status' => '待付款', 'order_time' => '2026-04-01 14:30:00', 'signing_consultant' => '刘洋', 'sales_owner' => '刘洋', 'related_student' => '王五', 'remark' => '等待客户走个人付款'],
            ['id' => 4, 'order_no' => 'OA20260402001', 'customer_name' => '赵六', 'course_name' => '短视频制作', 'order_type' => '增课', 'order_amount' => 5200, 'paid_amount' => 5200, 'payment_status' => '已付清', 'order_time' => '2026-04-02 10:10:00', 'signing_consultant' => '周倩', 'sales_owner' => '周倩', 'related_student' => '赵六', 'remark' => '直播脚本专项'],
            ['id' => 5, 'order_no' => 'OA20260402002', 'customer_name' => '孙七', 'course_name' => '电商直播', 'order_type' => '首单', 'order_amount' => 18800, 'paid_amount' => 10000, 'payment_status' => '部分付款', 'order_time' => '2026-04-02 13:45:00', 'signing_consultant' => '陈晨', 'sales_owner' => '陈晨', 'related_student' => '孙七', 'remark' => '剩余尾款下周补齐'],
            ['id' => 6, 'order_no' => 'OA20260403001', 'customer_name' => '周八', 'course_name' => '新媒体运营', 'order_type' => '首单', 'order_amount' => 8800, 'paid_amount' => 8800, 'payment_status' => '已付清', 'order_time' => '2026-04-03 09:55:00', 'signing_consultant' => '许凯', 'sales_owner' => '许凯', 'related_student' => '周八', 'remark' => '已安排开营'],
            ['id' => 7, 'order_no' => 'OA20260403002', 'customer_name' => '吴九', 'course_name' => 'Python全栈', 'order_type' => '续单', 'order_amount' => 7200, 'paid_amount' => 7200, 'payment_status' => '已付清', 'order_time' => '2026-04-03 16:40:00', 'signing_consultant' => '何敏', 'sales_owner' => '何敏', 'related_student' => '吴九', 'remark' => '就业冲刺模块'],
            ['id' => 8, 'order_no' => 'OA20260404001', 'customer_name' => '郑十', 'course_name' => 'AI应用办公', 'order_type' => '首单', 'order_amount' => 3980, 'paid_amount' => 3980, 'payment_status' => '已退款', 'order_time' => '2026-04-04 12:05:00', 'signing_consultant' => '刘洋', 'sales_owner' => '刘洋', 'related_student' => '郑十', 'remark' => '因时间冲突已全额退款'],
            ['id' => 9, 'order_no' => 'OA20260405001', 'customer_name' => '钱一', 'course_name' => '电商直播', 'order_type' => '增课', 'order_amount' => 12800, 'paid_amount' => 12800, 'payment_status' => '已付清', 'order_time' => '2026-04-05 10:25:00', 'signing_consultant' => '周倩', 'sales_owner' => '周倩', 'related_student' => '钱一', 'remark' => '企业内训增项'],
            ['id' => 10, 'order_no' => 'OA20260405002', 'customer_name' => '冯二', 'course_name' => '短视频制作', 'order_type' => '首单', 'order_amount' => 7600, 'paid_amount' => 2000, 'payment_status' => '部分付款', 'order_time' => '2026-04-05 18:15:00', 'signing_consultant' => '许凯', 'sales_owner' => '许凯', 'related_student' => '冯二', 'remark' => '已付定金'],
        ],
        'deal_status' => [
            ['id' => 1, 'order_no' => 'OA20260401001', 'customer_name' => '张三', 'current_status' => '已签约', 'changed_at' => '2026-04-01 09:30:00', 'changed_by' => '陈晨', 'change_reason' => '客户完成全款支付并签署电子合同。', 'remark' => '同步开课群'],
            ['id' => 2, 'order_no' => 'OA20260401002', 'customer_name' => '李四', 'current_status' => '部分付款', 'changed_at' => '2026-04-01 11:40:00', 'changed_by' => '何敏', 'change_reason' => '客户先付定金，尾款等待企业报销。', 'remark' => '预计周四补款'],
            ['id' => 3, 'order_no' => 'OA20260401003', 'customer_name' => '王五', 'current_status' => '待付款', 'changed_at' => '2026-04-01 15:00:00', 'changed_by' => '刘洋', 'change_reason' => '订单已创建，客户尚未提交付款。', 'remark' => '需要再次催付'],
            ['id' => 4, 'order_no' => 'OA20260402001', 'customer_name' => '赵六', 'current_status' => '服务中', 'changed_at' => '2026-04-02 17:10:00', 'changed_by' => '周倩', 'change_reason' => '增课订单已付款并加入专项训练营。', 'remark' => '本周开始直播诊断'],
            ['id' => 5, 'order_no' => 'OA20260402002', 'customer_name' => '孙七', 'current_status' => '部分付款', 'changed_at' => '2026-04-02 14:20:00', 'changed_by' => '陈晨', 'change_reason' => '客户先支付 10000 元锁定席位。', 'remark' => '剩余尾款待安排'],
            ['id' => 6, 'order_no' => 'OA20260403001', 'customer_name' => '周八', 'current_status' => '待开课', 'changed_at' => '2026-04-03 10:20:00', 'changed_by' => '许凯', 'change_reason' => '订单已全额到账，等待下周一开课。', 'remark' => '已发送开课须知'],
            ['id' => 7, 'order_no' => 'OA20260403002', 'customer_name' => '吴九', 'current_status' => '已完结', 'changed_at' => '2026-04-03 20:00:00', 'changed_by' => '何敏', 'change_reason' => '续单模块交付完成，确认结项。', 'remark' => '建议转介绍'],
            ['id' => 8, 'order_no' => 'OA20260404001', 'customer_name' => '郑十', 'current_status' => '已退款', 'changed_at' => '2026-04-04 16:45:00', 'changed_by' => '刘洋', 'change_reason' => '客户因时间冲突申请退款并通过审批。', 'remark' => '退款已到账'],
            ['id' => 9, 'order_no' => 'OA20260405001', 'customer_name' => '钱一', 'current_status' => '已签约', 'changed_at' => '2026-04-05 11:00:00', 'changed_by' => '周倩', 'change_reason' => '企业客户增课项目追加完成签约。', 'remark' => '等待项目排期'],
            ['id' => 10, 'order_no' => 'OA20260405002', 'customer_name' => '冯二', 'current_status' => '部分付款', 'changed_at' => '2026-04-05 18:40:00', 'changed_by' => '许凯', 'change_reason' => '客户先付定金进入账号诊断阶段。', 'remark' => '下次回访催尾款'],
        ],
        'deal_stats' => [
            ['id' => 1, 'ranking' => 1, 'consultant_name' => '周倩', 'department' => '顾问一部', 'first_order_count' => 5, 'renewal_order_count' => 3, 'upgrade_order_count' => 2, 'gmv' => 98600, 'received_amount' => 93200, 'refund_amount' => 0, 'gross_profit' => 42100, 'efficiency' => 12.3, 'period' => '2026-04', 'remark' => '企业大单贡献突出'],
            ['id' => 2, 'ranking' => 2, 'consultant_name' => '陈晨', 'department' => '顾问一部', 'first_order_count' => 4, 'renewal_order_count' => 2, 'upgrade_order_count' => 1, 'gmv' => 86400, 'received_amount' => 78200, 'refund_amount' => 0, 'gross_profit' => 36150, 'efficiency' => 10.8, 'period' => '2026-04', 'remark' => '高客单成交稳定'],
            ['id' => 3, 'ranking' => 3, 'consultant_name' => '何敏', 'department' => '顾问二部', 'first_order_count' => 3, 'renewal_order_count' => 3, 'upgrade_order_count' => 1, 'gmv' => 73500, 'received_amount' => 70100, 'refund_amount' => 0, 'gross_profit' => 29800, 'efficiency' => 9.6, 'period' => '2026-04', 'remark' => '续单转化率高'],
            ['id' => 4, 'ranking' => 4, 'consultant_name' => '许凯', 'department' => '顾问二部', 'first_order_count' => 4, 'renewal_order_count' => 1, 'upgrade_order_count' => 1, 'gmv' => 66800, 'received_amount' => 59200, 'refund_amount' => 0, 'gross_profit' => 25100, 'efficiency' => 8.9, 'period' => '2026-04', 'remark' => '短视频线索承接快'],
            ['id' => 5, 'ranking' => 5, 'consultant_name' => '刘洋', 'department' => '顾问三部', 'first_order_count' => 3, 'renewal_order_count' => 1, 'upgrade_order_count' => 0, 'gmv' => 52200, 'received_amount' => 46220, 'refund_amount' => 3980, 'gross_profit' => 18560, 'efficiency' => 7.1, 'period' => '2026-04', 'remark' => '退款影响当期毛利'],
            ['id' => 6, 'ranking' => 6, 'consultant_name' => '林楠', 'department' => '顾问三部', 'first_order_count' => 2, 'renewal_order_count' => 2, 'upgrade_order_count' => 1, 'gmv' => 48600, 'received_amount' => 43800, 'refund_amount' => 0, 'gross_profit' => 17620, 'efficiency' => 6.5, 'period' => '2026-04', 'remark' => '老客复购贡献明显'],
            ['id' => 7, 'ranking' => 7, 'consultant_name' => '王倩', 'department' => '顾问主管组', 'first_order_count' => 2, 'renewal_order_count' => 1, 'upgrade_order_count' => 1, 'gmv' => 43800, 'received_amount' => 42500, 'refund_amount' => 0, 'gross_profit' => 19000, 'efficiency' => 6.2, 'period' => '2026-04', 'remark' => '主管兼顾签单'],
            ['id' => 8, 'ranking' => 8, 'consultant_name' => '李娜', 'department' => '大客户组', 'first_order_count' => 1, 'renewal_order_count' => 1, 'upgrade_order_count' => 2, 'gmv' => 41200, 'received_amount' => 40100, 'refund_amount' => 0, 'gross_profit' => 17300, 'efficiency' => 5.9, 'period' => '2026-04', 'remark' => '增课客单价高'],
            ['id' => 9, 'ranking' => 9, 'consultant_name' => '高远', 'department' => '顾问储备组', 'first_order_count' => 2, 'renewal_order_count' => 0, 'upgrade_order_count' => 0, 'gmv' => 29600, 'received_amount' => 26800, 'refund_amount' => 0, 'gross_profit' => 10200, 'efficiency' => 4.4, 'period' => '2026-04', 'remark' => '新人成交爬坡中'],
            ['id' => 10, 'ranking' => 10, 'consultant_name' => '孙萌', 'department' => '顾问储备组', 'first_order_count' => 1, 'renewal_order_count' => 1, 'upgrade_order_count' => 0, 'gmv' => 25400, 'received_amount' => 23300, 'refund_amount' => 0, 'gross_profit' => 9180, 'efficiency' => 3.8, 'period' => '2026-04', 'remark' => '重点提升首单转化'],
        ],
    ];
}

function crm_sales_storage_dir(): string
{
    return __DIR__ . '/data/crm_sales';
}

function crm_sales_storage_path(string $module): string
{
    return crm_sales_storage_dir() . '/' . $module . '.json';
}

function crm_sales_sort_rows(string $module, array $rows): array
{
    usort($rows, static function (array $a, array $b) use ($module): int {
        if ($module === 'deal_stats') {
            $ra = (int)($a['ranking'] ?? 0);
            $rb = (int)($b['ranking'] ?? 0);
            if ($ra === $rb) {
                return (int)($a['id'] ?? 0) - (int)($b['id'] ?? 0);
            }
            return $ra < $rb ? -1 : ($ra > $rb ? 1 : 0);
        }
        return (int)($b['id'] ?? 0) - (int)($a['id'] ?? 0);
    });
    return $rows;
}

function crm_sales_save(string $module, array $rows): void
{
    $dir = crm_sales_storage_dir();
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $payload = json_encode(crm_sales_sort_rows($module, array_values($rows)), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($payload === false) {
        throw new RuntimeException('序列化数据失败');
    }
    file_put_contents(crm_sales_storage_path($module), $payload, LOCK_EX);
}

function crm_sales_load(string $module): array
{
    $seedAll = crm_sales_seed_data();
    $seed = $seedAll[$module] ?? [];
    $path = crm_sales_storage_path($module);

    if (!is_dir(crm_sales_storage_dir())) {
        mkdir(crm_sales_storage_dir(), 0777, true);
    }
    if (!file_exists($path)) {
        crm_sales_save($module, $seed);
        return crm_sales_sort_rows($module, $seed);
    }

    $raw = file_get_contents($path);
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) {
        crm_sales_save($module, $seed);
        return crm_sales_sort_rows($module, $seed);
    }

    return crm_sales_sort_rows($module, array_values(array_filter($data, 'is_array')));
}

function crm_sales_next_id(array $rows): int
{
    $max = 0;
    foreach ($rows as $row) {
        $max = max($max, (int)($row['id'] ?? 0));
    }
    return $max + 1;
}

function crm_sales_keyword_match(array $row, string $keyword): bool
{
    if ($keyword === '') {
        return true;
    }
    foreach ($row as $value) {
        if (is_scalar($value) && mb_stripos((string)$value, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

function crm_sales_normalize(string $module, array $data, ?array $existing = null): array
{
    $defs = crm_sales_definitions();
    if (!isset($defs[$module])) {
        throw new RuntimeException('未知模块：' . $module);
    }

    $def = $defs[$module];
    $row = $existing ?? [];
    $numericFields = $def['numeric'] ?? [];

    foreach (($def['defaults'] ?? []) as $field => $default) {
        if (array_key_exists($field, $data)) {
            $value = $data[$field];
        } elseif (array_key_exists($field, $row)) {
            $value = $row[$field];
        } else {
            $value = $default;
        }

        if (is_string($value)) {
            $value = trim($value);
        }
        if (in_array($field, $numericFields, true)) {
            $value = is_numeric($value) ? $value + 0 : 0;
        }
        $row[$field] = $value;
    }

    if ($module === 'deal_management' && trim((string)($data['payment_status'] ?? '')) === '') {
        $orderAmount = (float)($row['order_amount'] ?? 0);
        $paidAmount = (float)($row['paid_amount'] ?? 0);
        if ($paidAmount <= 0) {
            $row['payment_status'] = '待付款';
        } elseif ($paidAmount >= $orderAmount && $orderAmount > 0) {
            $row['payment_status'] = '已付清';
        } else {
            $row['payment_status'] = '部分付款';
        }
    }

    if ($module === 'deal_stats') {
        if ((int)($row['ranking'] ?? 0) <= 0) {
            $row['ranking'] = (int)($existing['ranking'] ?? 0);
        }
    }

    $row['updated_at'] = date('Y-m-d H:i:s');
    $row['created_at'] = (string)($existing['created_at'] ?? date('Y-m-d H:i:s'));
    return $row;
}

function crm_sales_handle(string $module): void
{
    $defs = crm_sales_definitions();
    if (!isset($defs[$module])) {
        json_response(404, '模块不存在', null, 404);
    }

    $m = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $rows = crm_sales_load($module);

    if ($m === 'GET') {
        $keyword = trim((string)($_GET['keyword'] ?? ''));
        if ($keyword !== '') {
            $rows = array_values(array_filter($rows, static function (array $row) use ($keyword): bool {
                return crm_sales_keyword_match($row, $keyword);
            }));
        }
        json_response(0, 'ok', crm_sales_sort_rows($module, $rows));
    }

    if ($m === 'POST') {
        $data = request_body();
        require_fields($data, $defs[$module]['required'] ?? []);
        $row = crm_sales_normalize($module, $data, null);
        $row['id'] = crm_sales_next_id($rows);
        if ($module === 'deal_stats' && (int)($row['ranking'] ?? 0) <= 0) {
            $row['ranking'] = count($rows) + 1;
        }
        $rows[] = $row;
        crm_sales_save($module, $rows);
        json_response(0, 'created', ['id' => $row['id']]);
    }

    if ($m === 'PUT') {
        $data = request_body();
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) {
            json_response(400, 'id非法', null, 400);
        }
        require_fields($data, $defs[$module]['required'] ?? []);

        foreach ($rows as $index => $row) {
            if ((int)($row['id'] ?? 0) === $id) {
                $updated = crm_sales_normalize($module, $data, $row);
                $updated['id'] = $id;
                $rows[$index] = $updated;
                crm_sales_save($module, $rows);
                json_response(0, 'updated', $updated);
            }
        }
        json_response(404, '记录不存在', null, 404);
    }

    if ($m === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_response(400, 'id非法', null, 400);
        }
        $filtered = array_values(array_filter($rows, static function (array $row) use ($id): bool {
            return (int)($row['id'] ?? 0) !== $id;
        }));
        if (count($filtered) === count($rows)) {
            json_response(404, '记录不存在', null, 404);
        }
        crm_sales_save($module, $filtered);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
}
