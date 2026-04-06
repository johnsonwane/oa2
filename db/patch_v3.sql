-- =====================================================
-- OA2 数据库补丁 v3：完整功能补全
-- 执行方式：mysql -u root -p oa2 < db/patch_v3.sql
-- =====================================================

USE oa2;

-- =====================================================
-- 1. 新建所有新功能表
-- =====================================================

CREATE TABLE IF NOT EXISTS oa_work (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(150) NOT NULL COMMENT '作品标题',
  work_type ENUM('video','article','live') NOT NULL DEFAULT 'article' COMMENT '作品类型',
  channel VARCHAR(30) NOT NULL DEFAULT '' COMMENT '发布渠道',
  publish_time DATETIME DEFAULT NULL COMMENT '发布时间',
  views INT NOT NULL DEFAULT 0 COMMENT '播放/阅读量',
  likes INT NOT NULL DEFAULT 0 COMMENT '点赞量',
  favorites INT NOT NULL DEFAULT 0 COMMENT '收藏量',
  leads_count INT NOT NULL DEFAULT 0 COMMENT '引流留资人数',
  conversion_rate DECIMAL(6,3) NOT NULL DEFAULT 0 COMMENT '转化率(%)',
  status TINYINT NOT NULL DEFAULT 1 COMMENT '状态：1启用 0停用',
  remark VARCHAR(255) DEFAULT '' COMMENT '备注',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_channel (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  channel_name VARCHAR(50) NOT NULL COMMENT '渠道名称',
  account_name VARCHAR(80) NOT NULL DEFAULT '' COMMENT '账号名称',
  account_id VARCHAR(80) NOT NULL DEFAULT '' COMMENT '账号ID/手机号',
  account_password VARCHAR(255) NOT NULL DEFAULT '' COMMENT '密码（加密存储）',
  operator_user_id INT UNSIGNED DEFAULT NULL COMMENT '运营负责人',
  publish_frequency VARCHAR(30) DEFAULT '' COMMENT '发布频率',
  status TINYINT NOT NULL DEFAULT 1 COMMENT '状态',
  remark VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_traffic_data (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  data_date DATE NOT NULL COMMENT '日期',
  channel VARCHAR(30) NOT NULL DEFAULT '' COMMENT '渠道',
  exposure_count INT NOT NULL DEFAULT 0 COMMENT '曝光量',
  click_count INT NOT NULL DEFAULT 0 COMMENT '点击量',
  click_rate DECIMAL(6,3) NOT NULL DEFAULT 0 COMMENT '点击率(%)',
  leads_count INT NOT NULL DEFAULT 0 COMMENT '留资人数',
  lead_cost DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '留资成本(元)',
  enroll_count INT NOT NULL DEFAULT 0 COMMENT '转化报名数',
  enroll_cost DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '转化成本(元)',
  roi DECIMAL(8,4) NOT NULL DEFAULT 0 COMMENT 'ROI',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_date_channel (data_date, channel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_roi_analysis (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  period_month VARCHAR(7) NOT NULL COMMENT '月份',
  channel VARCHAR(50) NOT NULL DEFAULT '' COMMENT '渠道/活动名称',
  cost DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '投放成本(元)',
  leads_count INT NOT NULL DEFAULT 0 COMMENT '引流人数',
  paid_count INT NOT NULL DEFAULT 0 COMMENT '付费转化数',
  paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '付费金额(元)',
  roi DECIMAL(8,4) NOT NULL DEFAULT 0 COMMENT 'ROI',
  gross_profit DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '毛利(元)',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_lead (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  customer_name VARCHAR(50) NOT NULL DEFAULT '' COMMENT '客户姓名',
  mobile VARCHAR(20) NOT NULL DEFAULT '' COMMENT '手机号',
  source_channel VARCHAR(50) NOT NULL DEFAULT '' COMMENT '来源渠道',
  lead_tag VARCHAR(50) NOT NULL DEFAULT '' COMMENT '线索标签',
  source_campaign VARCHAR(100) NOT NULL DEFAULT '' COMMENT '来源活动',
  register_time DATETIME DEFAULT NULL COMMENT '录入时间',
  assign_status ENUM('unassigned','assigned','claimed') NOT NULL DEFAULT 'unassigned' COMMENT '归属状态',
  assigned_at DATETIME DEFAULT NULL COMMENT '分配时间',
  assigned_to_user_id INT UNSIGNED DEFAULT NULL COMMENT '分配给',
  follow_count INT NOT NULL DEFAULT 0 COMMENT '跟进次数',
  last_follow_time DATETIME DEFAULT NULL COMMENT '最近跟进时间',
  convert_status ENUM('unfollow','following','converted','lost') NOT NULL DEFAULT 'unfollow' COMMENT '转化状态',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_lead_mobile (mobile),
  KEY idx_lead_channel (source_channel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_lead_dispatch_rule (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  rule_name VARCHAR(100) NOT NULL COMMENT '规则名称',
  apply_channel VARCHAR(50) NOT NULL DEFAULT '' COMMENT '适用渠道',
  dispatch_mode ENUM('even','priority','roundrobin') NOT NULL DEFAULT 'even' COMMENT '分配方式',
  batch_size INT NOT NULL DEFAULT 1 COMMENT '每次分配数量',
  assign_to_role VARCHAR(50) NOT NULL DEFAULT '' COMMENT '分配给角色',
  status TINYINT NOT NULL DEFAULT 1 COMMENT '状态',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_customer (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL COMMENT '客户姓名',
  gender VARCHAR(10) DEFAULT '' COMMENT '性别',
  age INT DEFAULT NULL COMMENT '年龄',
  mobile VARCHAR(20) NOT NULL DEFAULT '' COMMENT '手机号',
  wechat VARCHAR(50) DEFAULT '' COMMENT '微信号',
  city VARCHAR(50) DEFAULT '' COMMENT '城市',
  occupation VARCHAR(100) DEFAULT '' COMMENT '职业/公司',
  intention_course VARCHAR(100) DEFAULT '' COMMENT '意向课程',
  intention_level ENUM('A','B','C') DEFAULT '' COMMENT '意向等级',
  source_channel VARCHAR(50) DEFAULT '' COMMENT '来源渠道',
  first_inquiry_date DATE DEFAULT NULL COMMENT '首次咨询日期',
  last_follow_date DATE DEFAULT NULL COMMENT '最近跟进日期',
  follow_status ENUM('active','silent','churned') DEFAULT 'active' COMMENT '跟进状态',
  total_amount DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '累计消费金额',
  owner_user_id INT UNSIGNED DEFAULT NULL COMMENT '归属顾问',
  owner_name VARCHAR(50) DEFAULT '' COMMENT '归属顾问姓名',
  remark VARCHAR(255) DEFAULT '' COMMENT '备注',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_customer_mobile (mobile)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_followup (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  customer_id INT UNSIGNED NOT NULL COMMENT '客户ID',
  customer_name VARCHAR(50) NOT NULL DEFAULT '' COMMENT '客户姓名',
  follow_user VARCHAR(50) NOT NULL DEFAULT '' COMMENT '跟进人',
  follow_method ENUM('call','wechat','meeting','sms') NOT NULL DEFAULT 'call' COMMENT '跟进方式',
  follow_time DATETIME NOT NULL COMMENT '跟进时间',
  content TEXT COMMENT '跟进内容摘要',
  next_follow_date DATE DEFAULT NULL COMMENT '下次跟进时间',
  follow_status ENUM('pending','completed','abandoned') NOT NULL DEFAULT 'pending' COMMENT '跟进状态',
  remark VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_customer_transfer (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  customer_id INT UNSIGNED NOT NULL COMMENT '客户ID',
  customer_name VARCHAR(50) NOT NULL DEFAULT '' COMMENT '客户姓名',
  from_user VARCHAR(50) NOT NULL DEFAULT '' COMMENT '原归属顾问',
  to_user VARCHAR(50) NOT NULL DEFAULT '' COMMENT '新归属顾问',
  transfer_type ENUM('transfer','upgrade','downgrade','claim') NOT NULL DEFAULT 'transfer' COMMENT '流转类型',
  reason VARCHAR(255) DEFAULT '' COMMENT '流转原因',
  transfer_time DATETIME NOT NULL COMMENT '流转时间',
  operator_user VARCHAR(50) DEFAULT '' COMMENT '操作人',
  remark VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_deal (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  deal_no VARCHAR(60) NOT NULL COMMENT '订单编号',
  customer_id INT UNSIGNED DEFAULT NULL COMMENT '客户ID',
  customer_name VARCHAR(50) NOT NULL DEFAULT '' COMMENT '客户姓名',
  course_id INT UNSIGNED DEFAULT NULL COMMENT '课程ID',
  course_name VARCHAR(100) NOT NULL DEFAULT '' COMMENT '课程名称',
  order_type ENUM('first','renewal','upgrade') NOT NULL DEFAULT 'first' COMMENT '订单类型',
  amount DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '订单金额',
  paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '已付金额',
  pay_status ENUM('pending','partial','paid','refunded','cancelled') NOT NULL DEFAULT 'pending' COMMENT '付款状态',
  payment_stage VARCHAR(20) DEFAULT '' COMMENT '付款阶段',
  order_time DATETIME DEFAULT NULL COMMENT '下单时间',
  consultant VARCHAR(50) DEFAULT '' COMMENT '签约顾问',
  seller VARCHAR(50) DEFAULT '' COMMENT '负责销售',
  remark VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_deal_no (deal_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_deal_status (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  deal_id INT UNSIGNED NOT NULL COMMENT '订单ID',
  deal_no VARCHAR(60) NOT NULL DEFAULT '' COMMENT '订单编号',
  customer_name VARCHAR(50) NOT NULL DEFAULT '' COMMENT '客户姓名',
  status VARCHAR(30) NOT NULL DEFAULT '' COMMENT '当前状态',
  prev_status VARCHAR(30) NOT NULL DEFAULT '' COMMENT '前一状态',
  change_time DATETIME NOT NULL COMMENT '变更时间',
  changed_by VARCHAR(50) DEFAULT '' COMMENT '变更人',
  reason VARCHAR(255) DEFAULT '' COMMENT '变更原因',
  remark VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_deal_stats (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  period_month VARCHAR(7) NOT NULL COMMENT '统计月份',
  consultant VARCHAR(50) NOT NULL DEFAULT '' COMMENT '顾问姓名',
  department VARCHAR(50) NOT NULL DEFAULT '' COMMENT '部门',
  first_order_count INT NOT NULL DEFAULT 0 COMMENT '首单数',
  renewal_count INT NOT NULL DEFAULT 0 COMMENT '续单数',
  upgrade_count INT NOT NULL DEFAULT 0 COMMENT '增课数',
  gmv DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'GMV',
  collected DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '回款金额',
  refunded DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '退款金额',
  gross_profit DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '毛利',
  efficiency DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '人效(万/人)',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_student_ext (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL COMMENT '学员ID',
  name VARCHAR(50) NOT NULL DEFAULT '' COMMENT '姓名',
  gender VARCHAR(10) DEFAULT '' COMMENT '性别',
  age INT DEFAULT NULL COMMENT '年龄',
  mobile VARCHAR(20) NOT NULL DEFAULT '' COMMENT '手机号',
  course_name VARCHAR(100) NOT NULL DEFAULT '' COMMENT '所在课程',
  class_name VARCHAR(100) NOT NULL DEFAULT '' COMMENT '班级',
  headteacher VARCHAR(50) DEFAULT '' COMMENT '班主任',
  coach VARCHAR(50) DEFAULT '' COMMENT '教练',
  enroll_date DATE DEFAULT NULL COMMENT '入学日期',
  study_stage ENUM('preview','learning','graduated','suspended','dropped') NOT NULL DEFAULT 'learning' COMMENT '学习阶段',
  status ENUM('normal','exception') NOT NULL DEFAULT 'normal' COMMENT '状态',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_student_progress (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL COMMENT '学员ID',
  student_name VARCHAR(50) NOT NULL DEFAULT '' COMMENT '学员姓名',
  course_name VARCHAR(100) NOT NULL DEFAULT '' COMMENT '课程名称',
  class_name VARCHAR(100) NOT NULL DEFAULT '' COMMENT '班级',
  current_stage VARCHAR(50) NOT NULL DEFAULT '' COMMENT '当前阶段',
  progress_rate DECIMAL(5,2) NOT NULL DEFAULT 0 COMMENT '学习进度(%)',
  headteacher_comment TEXT COMMENT '班主任评价',
  coach_comment TEXT COMMENT '教练意见',
  next_follow_date DATE DEFAULT NULL COMMENT '下次跟进时间',
  record_date DATE NOT NULL COMMENT '记录日期',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_student_exception (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL COMMENT '学员ID',
  student_name VARCHAR(50) NOT NULL DEFAULT '' COMMENT '学员姓名',
  course_name VARCHAR(100) NOT NULL DEFAULT '' COMMENT '课程名称',
  exception_type ENUM('drop_request','refund_complaint','study_interrupt','serious_violation','other') NOT NULL DEFAULT 'other' COMMENT '异常类型',
  severity ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium' COMMENT '严重程度',
  occur_time DATETIME NOT NULL COMMENT '发生时间',
  handler VARCHAR(50) DEFAULT '' COMMENT '负责人',
  handle_status ENUM('pending','processing','resolved','escalated') NOT NULL DEFAULT 'pending' COMMENT '处理状态',
  result VARCHAR(255) DEFAULT '' COMMENT '处理结果',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_order_multi (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL COMMENT '订单ID',
  deal_no VARCHAR(60) NOT NULL DEFAULT '' COMMENT '订单编号',
  customer_name VARCHAR(50) NOT NULL DEFAULT '' COMMENT '学员姓名',
  course_name VARCHAR(100) NOT NULL DEFAULT '' COMMENT '课程名称',
  role_type ENUM('consultant','coach','headteacher','seller') NOT NULL DEFAULT 'consultant' COMMENT '参与角色',
  user_name VARCHAR(50) NOT NULL DEFAULT '' COMMENT '人员姓名',
  contribution_type ENUM('record','deliver','renewal','upgrade') NOT NULL DEFAULT 'record' COMMENT '贡献类型',
  commission_amount DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '分成金额',
  assign_time DATETIME NOT NULL COMMENT '分配时间',
  status ENUM('pending','confirmed','paid') NOT NULL DEFAULT 'pending' COMMENT '状态',
  remark VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_order_flow (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL COMMENT '订单ID',
  deal_no VARCHAR(60) NOT NULL DEFAULT '' COMMENT '订单编号',
  status VARCHAR(30) NOT NULL DEFAULT '' COMMENT '状态名称',
  prev_status VARCHAR(30) NOT NULL DEFAULT '' COMMENT '前一状态',
  curr_status VARCHAR(30) NOT NULL DEFAULT '' COMMENT '当前状态',
  change_time DATETIME NOT NULL COMMENT '变更时间',
  changed_by VARCHAR(50) DEFAULT '' COMMENT '变更人',
  reason VARCHAR(255) DEFAULT '' COMMENT '变更原因',
  remark VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_repurchase (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL COMMENT '老学员ID',
  student_name VARCHAR(50) NOT NULL DEFAULT '' COMMENT '老学员姓名',
  mobile VARCHAR(20) NOT NULL DEFAULT '' COMMENT '手机号',
  original_course VARCHAR(100) NOT NULL DEFAULT '' COMMENT '原购课程',
  original_amount DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '原购金额',
  repurchase_course VARCHAR(100) NOT NULL DEFAULT '' COMMENT '复购课程',
  repurchase_amount DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '复购金额',
  repurchase_type ENUM('renewal','upgrade','transfer') NOT NULL DEFAULT 'renewal' COMMENT '复购类型',
  repurchase_time DATETIME DEFAULT NULL COMMENT '复购时间',
  consultant VARCHAR(50) DEFAULT '' COMMENT '负责顾问',
  repurchase_channel VARCHAR(50) DEFAULT '' COMMENT '复购渠道',
  remark VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_repurchase_followup (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL COMMENT '学员ID',
  student_name VARCHAR(50) NOT NULL DEFAULT '' COMMENT '学员姓名',
  purchased_course VARCHAR(100) NOT NULL DEFAULT '' COMMENT '购买过的课程',
  last_order_time DATETIME DEFAULT NULL COMMENT '最近一单时间',
  repurchase_intention ENUM('high','medium','low','none') NOT NULL DEFAULT 'low' COMMENT '二销意向',
  target_course VARCHAR(100) NOT NULL DEFAULT '' COMMENT '二销目标课程',
  owner VARCHAR(50) NOT NULL DEFAULT '' COMMENT '二销负责人',
  last_follow_time DATETIME DEFAULT NULL COMMENT '最近跟进时间',
  follow_content TEXT COMMENT '跟进内容',
  next_plan VARCHAR(255) DEFAULT '' COMMENT '下次跟进计划',
  estimated_time DATE DEFAULT NULL COMMENT '预计复购时间',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_repurchase_stats (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  period_month VARCHAR(7) NOT NULL COMMENT '月份',
  period_name VARCHAR(20) NOT NULL DEFAULT '' COMMENT '周期名称',
  leads_count INT NOT NULL DEFAULT 0 COMMENT '二销线索数',
  follow_count INT NOT NULL DEFAULT 0 COMMENT '二销跟进次数',
  deal_count INT NOT NULL DEFAULT 0 COMMENT '二销成单数',
  gmv DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '二销GMV',
  conversion_rate DECIMAL(6,3) NOT NULL DEFAULT 0 COMMENT '二销转化率',
  per_capita DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '人均产出',
  top_consultant VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'TOP1顾问',
  top_consultant_gmv DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '顾问GMV',
  top_course VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'TOP1课程',
  top_course_gmv DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '课程GMV',
  mom_change DECIMAL(8,4) NOT NULL DEFAULT 0 COMMENT '环比变化',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_refund_v2 (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  refund_no VARCHAR(60) NOT NULL COMMENT '退款编号',
  order_id INT UNSIGNED NOT NULL COMMENT '订单ID',
  deal_no VARCHAR(60) NOT NULL DEFAULT '' COMMENT '订单编号',
  customer_name VARCHAR(50) NOT NULL DEFAULT '' COMMENT '学员姓名',
  course_name VARCHAR(100) NOT NULL DEFAULT '' COMMENT '课程名称',
  order_amount DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '原订单金额',
  paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '已付金额',
  refund_amount DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '申请退款金额',
  reason VARCHAR(255) NOT NULL DEFAULT '' COMMENT '退款原因',
  status ENUM('pending','approved','rejected','processing','refunded') NOT NULL DEFAULT 'pending' COMMENT '退款状态',
  applicant VARCHAR(50) NOT NULL DEFAULT '' COMMENT '申请人',
  approver VARCHAR(50) DEFAULT '' COMMENT '审批人',
  requested_at DATETIME NOT NULL COMMENT '申请时间',
  approved_at DATETIME DEFAULT NULL COMMENT '审批时间',
  completed_at DATETIME DEFAULT NULL COMMENT '完成时间',
  remark VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_refund_no (refund_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_finance_stats (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  period_month VARCHAR(7) NOT NULL COMMENT '月份',
  period_name VARCHAR(20) NOT NULL DEFAULT '' COMMENT '周期名称',
  income_total DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '收入总计',
  expense_total DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '支出总计',
  net_balance DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '净结余',
  tuition_income DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '学费收入',
  other_income DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '其他收入',
  personnel_cost DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '人员成本',
  ops_cost DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '运营成本',
  refund_amount DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '退费金额',
  commission_paid DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '提成发放',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_period (period_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_course_cost (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_id INT UNSIGNED NOT NULL COMMENT '课程ID',
  course_name VARCHAR(100) NOT NULL DEFAULT '' COMMENT '课程名称',
  class_count INT NOT NULL DEFAULT 0 COMMENT '开班期数',
  total_students INT NOT NULL DEFAULT 0 COMMENT '总招生人数',
  course_income DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '课程收入',
  teacher_fee DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '教师课酬',
  assistant_fee DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '助教费用',
  material_fee DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '教材教具',
  ops_alloc DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '运营分摊',
  other_cost DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '其他成本',
  total_cost DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '总成本',
  net_profit DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '净利润',
  profit_rate DECIMAL(6,3) NOT NULL DEFAULT 0 COMMENT '利润率(%)',
  per_capita_cost DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '人均成本',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_profit_analysis (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  period_month VARCHAR(7) NOT NULL COMMENT '月份',
  dept_channel VARCHAR(50) NOT NULL DEFAULT '' COMMENT '部门/渠道',
  gmv DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'GMV',
  direct_cost DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '直接成本',
  indirect_cost DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '间接成本',
  total_cost DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '总成本',
  gross_profit DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '毛利',
  gross_rate DECIMAL(6,3) NOT NULL DEFAULT 0 COMMENT '毛利率',
  per_capita_gmv DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '人均GMV',
  yoy_change DECIMAL(8,4) NOT NULL DEFAULT 0 COMMENT '同比变化',
  mom_change DECIMAL(8,4) NOT NULL DEFAULT 0 COMMENT '环比变化',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_salary_wages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slip_no VARCHAR(60) NOT NULL COMMENT '工资单编号',
  user_id INT UNSIGNED NOT NULL COMMENT '员工ID',
  user_name VARCHAR(50) NOT NULL DEFAULT '' COMMENT '员工姓名',
  department VARCHAR(50) NOT NULL DEFAULT '' COMMENT '部门',
  position VARCHAR(50) NOT NULL DEFAULT '' COMMENT '职位',
  salary_month VARCHAR(7) NOT NULL COMMENT '工资月份',
  base_salary DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '基本工资',
  position_salary DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '岗位工资',
  performance_salary DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '绩效工资',
  overtime_fee DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '加班费',
  gross_amount DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '应发合计',
  social_deduction DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '社保扣款',
  housing_fund DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '公积金扣款',
  tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '个税',
  other_deduction DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '其他扣款',
  net_amount DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '实发工资',
  status ENUM('draft','confirmed','paid') NOT NULL DEFAULT 'draft' COMMENT '状态',
  paid_date DATE DEFAULT NULL COMMENT '发放日期',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_slip_no (slip_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_salary_bonus (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL COMMENT '员工ID',
  user_name VARCHAR(50) NOT NULL DEFAULT '' COMMENT '员工姓名',
  department VARCHAR(50) NOT NULL DEFAULT '' COMMENT '部门',
  bonus_type ENUM('order_commission','monthly_bonus','quarterly_bonus','annual_bonus','activity_reward','penalty') NOT NULL COMMENT '奖金类型',
  related_period VARCHAR(20) NOT NULL DEFAULT '' COMMENT '对应月份/活动',
  amount DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '金额',
  calc_basis VARCHAR(255) DEFAULT '' COMMENT '计算依据',
  status ENUM('calculating','confirmed','paid') NOT NULL DEFAULT 'calculating' COMMENT '状态',
  paid_time DATETIME DEFAULT NULL COMMENT '发放时间',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_ops_funnel (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  period_month VARCHAR(7) NOT NULL COMMENT '月份',
  funnel_stage VARCHAR(50) NOT NULL COMMENT '漏斗阶段',
  current_count INT NOT NULL DEFAULT 0 COMMENT '当期数量',
  prev_count INT NOT NULL DEFAULT 0 COMMENT '上期数量',
  conversion_rate DECIMAL(6,3) NOT NULL DEFAULT 0 COMMENT '转化率(%)',
  churn_rate DECIMAL(6,3) NOT NULL DEFAULT 0 COMMENT '流失率(%)',
  mom_change DECIMAL(8,4) NOT NULL DEFAULT 0 COMMENT '环比变化',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_ops_rank (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  period_month VARCHAR(7) NOT NULL COMMENT '月份',
  rank_no INT NOT NULL DEFAULT 1 COMMENT '排名',
  name VARCHAR(50) NOT NULL DEFAULT '' COMMENT '名称',
  dimension ENUM('order_count','gmv','leads','conversion','rating') NOT NULL COMMENT '指标维度',
  metric_value DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '数值',
  unit VARCHAR(10) NOT NULL DEFAULT '' COMMENT '单位',
  mom_change DECIMAL(8,4) NOT NULL DEFAULT 0 COMMENT '较上期变化(%)',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_approval (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  approval_no VARCHAR(60) NOT NULL COMMENT '审批编号',
  approval_type ENUM('leave','reimburse','promotion','resignation','contract') NOT NULL COMMENT '审批类型',
  applicant VARCHAR(50) NOT NULL DEFAULT '' COMMENT '申请人',
  department VARCHAR(50) NOT NULL DEFAULT '' COMMENT '部门',
  content_summary TEXT COMMENT '申请内容摘要',
  requested_at DATETIME NOT NULL COMMENT '申请时间',
  status ENUM('draft','pending','approved','rejected') NOT NULL DEFAULT 'draft' COMMENT '审批状态',
  current_approver VARCHAR(50) NOT NULL DEFAULT '' COMMENT '当前审批人',
  remark VARCHAR(255) DEFAULT '' COMMENT '备注',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_approval_no (approval_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_file_manager (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  file_name VARCHAR(150) NOT NULL COMMENT '文件名称',
  file_type ENUM('contract','invoice','certificate','qualification','other') NOT NULL DEFAULT 'other' COMMENT '文件类型',
  related_object VARCHAR(50) NOT NULL DEFAULT '' COMMENT '关联对象',
  related_id INT UNSIGNED DEFAULT NULL COMMENT '关联ID',
  uploader VARCHAR(50) NOT NULL DEFAULT '' COMMENT '上传人',
  file_size INT NOT NULL DEFAULT 0 COMMENT '文件大小(字节)',
  file_url VARCHAR(255) NOT NULL DEFAULT '' COMMENT '文件路径',
  status ENUM('normal','expired','deleted') NOT NULL DEFAULT 'normal' COMMENT '状态',
  remark VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_system_settings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  setting_category ENUM('company','notification','workflow','custom_field') NOT NULL DEFAULT 'company' COMMENT '配置分类',
  setting_key VARCHAR(80) NOT NULL COMMENT '配置项名称',
  setting_value TEXT COMMENT '当前值',
  description VARCHAR(255) DEFAULT '' COMMENT '配置说明',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_setting (setting_category, setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 2. 插入菜单数据（幂等）
-- =====================================================

INSERT INTO oa_menu (parent_name, menu_name, menu_key, path, icon, sort_no, status) VALUES
-- 运营引流中心
('运营引流中心', '作品管理', 'works', '/works', '🎬', 101, 1),
('运营引流中心', '渠道发布', 'channels', '/channels', '📡', 102, 1),
('运营引流中心', '引流数据', 'traffic_data', '/traffic_data', '📊', 103, 1),
('运营引流中心', 'ROI分析', 'roi_analysis', '/roi_analysis', '💹', 104, 1),
-- 线索流转中心
('线索流转中心', '线索池', 'leads_pool', '/leads_pool', '🎯', 201, 1),
('线索流转中心', '分配机制', 'leads_dispatch', '/leads_dispatch', '⚙️', 202, 1),
('线索流转中心', '线索管理', 'leads_management', '/leads_management', '👥', 203, 1),
-- 客户CRM中心
('客户CRM中心', '客户档案', 'customer_files', '/customer_files', '📋', 301, 1),
('客户CRM中心', '跟进管理', 'followup_management', '/followup_management', '📝', 302, 1),
('客户CRM中心', '客户流转', 'customer_transfer', '/customer_transfer', '🔄', 303, 1),
-- 销售成单中心
('销售成单中心', '成单管理', 'deal_management', '/deal_management', '💰', 401, 1),
('销售成单中心', '状态管理', 'deal_status', '/deal_status', '📌', 402, 1),
('销售成单中心', '业绩统计', 'deal_stats', '/deal_stats', '🏆', 403, 1),
-- 订单中心
('订单中心', '订单管理', 'order_management', '/order_management', '🧾', 501, 1),
('订单中心', '多人参与', 'order_multi', '/order_multi', '👥', 502, 1),
('订单中心', '状态流转', 'order_flow', '/order_flow', '🔀', 503, 1),
-- 交付中心
('交付中心', '学员管理', 'student_management', '/student_management', '🎓', 601, 1),
('交付中心', '进度跟踪', 'student_progress', '/student_progress', '📈', 602, 1),
('交付中心', '异常管理', 'student_exception', '/student_exception', '⚠️', 603, 1),
-- 二次销售中心
('二次销售中心', '复购管理', 'repurchase', '/repurchase', '🔁', 701, 1),
('二次销售中心', '二销跟进', 'repurchase_followup', '/repurchase_followup', '📞', 702, 1),
('二次销售中心', '二销统计', 'repurchase_stats', '/repurchase_stats', '📉', 703, 1),
-- 财务中心
('财务中心', '退款管理', 'refund_center', '/refund_center', '↩️', 801, 1),
('财务中心', '财务统计', 'finance_stats', '/finance_stats', '💹', 802, 1),
-- 成本利润中心
('成本利润中心', '单课成本', 'cost_profit', '/cost_profit', '💵', 901, 1),
('成本利润中心', '利润分析', 'profit_analysis', '/profit_analysis', '📈', 902, 1),
-- 经营分析中心
('经营分析中心', '经营看板', 'ops_dashboard', '/ops_dashboard', '📺', 1001, 1),
('经营分析中心', '漏斗分析', 'ops_funnel', '/ops_funnel', '🔻', 1002, 1),
('经营分析中心', '排行分析', 'ops_rank', '/ops_rank', '🏅', 1003, 1),
-- 基础系统
('基础系统', '审批', 'approval', '/approval', '📄', 1101, 1),
('基础系统', '文件', 'file_manager', '/file_manager', '📁', 1102, 1),
('基础系统', '设置', 'system_settings', '/system_settings', '⚙️', 1103, 1)
ON DUPLICATE KEY UPDATE menu_name=VALUES(menu_name), parent_name=VALUES(parent_name), path=VALUES(path), icon=VALUES(icon), sort_no=VALUES(sort_no), status=VALUES(status);

-- =====================================================
-- 3. 插入测试数据
-- =====================================================

-- oa_work（作品管理）
INSERT INTO oa_work (title, work_type, channel, publish_time, views, likes, favorites, leads_count, conversion_rate, status) VALUES
('Python入门9.9特惠，限时抢！', 'video', '抖音', '2026-02-01 10:00:00', 52000, 2100, 850, 312, 1.620, 1),
('新媒体运营速成指南，小红书首发', 'article', '小红书', '2026-02-02 14:30:00', 28000, 1350, 620, 198, 0.940, 1),
('AI办公提效直播课预约中', 'live', '视频号', '2026-02-03 19:00:00', 18000, 720, 310, 145, 0.920, 1),
('0基础学Python，7天入门实战', 'video', '抖音', '2026-02-05 11:00:00', 68000, 3200, 1100, 420, 0.830, 1),
('小红书引流实战：3天涨粉1000+', 'article', '小红书', '2026-02-06 09:00:00', 35000, 1800, 780, 265, 0.960, 1),
('电商直播带货全攻略，免费领', 'video', '快手', '2026-02-08 16:00:00', 42000, 1950, 690, 310, 0.880, 1),
('短视频剪辑入门，限时0元课', 'video', '抖音', '2026-02-10 12:00:00', 75000, 3800, 1400, 510, 0.800, 1),
('数据分析就业班火热招生中', 'article', '知乎', '2026-02-12 10:30:00', 12000, 580, 240, 88, 0.830, 1),
('UI设计零基础到就业，免费试听', 'video', 'B站', '2026-02-14 20:00:00', 38000, 1650, 720, 278, 0.840, 1),
('职场Excel提效课，效率翻3倍', 'video', '抖音', '2026-02-16 09:30:00', 55000, 2450, 980, 390, 0.840, 1),
('小红书变现指南，第3期开启', 'article', '小红书', '2026-02-18 11:00:00', 31000, 1550, 670, 220, 0.820, 1),
('Python副业接单实战班报名中', 'video', '抖音', '2026-02-20 15:00:00', 48000, 2100, 820, 335, 0.820, 1),
('新媒体运营 vs 传统运营，哪个更有钱途？', 'article', '知乎', '2026-02-22 08:00:00', 9800, 460, 195, 72, 0.860, 1),
('AI工具实战课，让ChatGPT帮你赚钱', 'video', '视频号', '2026-02-25 19:30:00', 22000, 980, 420, 168, 0.930, 1),
('抖音直播带货从0到1全流程', 'video', '抖音', '2026-02-28 14:00:00', 62000, 2900, 1150, 445, 0.830, 1)
ON DUPLICATE KEY UPDATE title=VALUES(title), views=VALUES(views), likes=VALUES(likes), leads_count=VALUES(leads_count), conversion_rate=VALUES(conversion_rate);

-- oa_channel（渠道发布）
INSERT INTO oa_channel (channel_name, account_name, account_id, publish_frequency, operator_user_id, status) VALUES
('抖音', '机构官方号', 'douyin_official_2026', '每天1-2条', 8, 1),
('小红书', 'XX教育种草号', 'xhs_edu_2026', '每天2-3条', 8, 1),
('快手', 'XX课堂快手号', 'kuaishou_2026', '每天1条', 8, 1),
('视频号', 'XX在线视频号', 'video_account_2026', '每周3-5条', 8, 1),
('B站', 'XX课堂官方', 'bilibili_2026', '每周2-3条', 8, 1),
('知乎', '职业教育专家', 'zhihu_2026', '每周1-2篇', 8, 1),
('百度', 'SEO引流账号', 'baidu_2026', '每周5-10篇', 8, 1),
('微信公众号', 'XX教育服务号', 'wechat_pub_2026', '每周1篇', 8, 1),
('豆瓣', '在线教育小组', 'douban_2026', '每周3篇', 8, 1),
('头条号', 'XX职场课堂', 'toutiao_2026', '每天1条', 8, 1)
ON DUPLICATE KEY UPDATE account_name=VALUES(account_name), publish_frequency=VALUES(publish_frequency), status=VALUES(status);

-- oa_lead（线索池）
INSERT INTO oa_lead (customer_name, mobile, source_channel, lead_tag, source_campaign, register_time, assign_status, follow_count, convert_status) VALUES
('周明轩', '13812340001', '抖音', '高意向', 'Python引流直播课', '2026-02-01 10:15:00', 'assigned', 3, 'following'),
('林小涵', '13812340002', '小红书', '高意向', '小红书2月种草计划', '2026-02-01 14:30:00', 'assigned', 5, 'converted'),
('张浩然', '13812340003', '百度', '中意向', 'SEO关键词投放', '2026-02-02 09:00:00', 'assigned', 2, 'following'),
('王思琪', '13812340004', '抖音', '高意向', 'AI工具实战课', '2026-02-02 16:00:00', 'claimed', 4, 'converted'),
('陈子墨', '13812340005', '小红书', '中意向', '小红书2月种草计划', '2026-02-03 11:00:00', 'unassigned', 0, 'unfollow'),
('刘佳怡', '13812340006', '转介绍', '高意向', '老带新-张姐推荐', '2026-02-03 15:00:00', 'assigned', 6, 'converted'),
('赵文博', '13812340007', '快手', '低意向', '快手2月投放', '2026-02-04 10:00:00', 'unassigned', 1, 'unfollow'),
('孙晓彤', '13812340008', '知乎', '中意向', '知乎问答引流', '2026-02-04 14:00:00', 'claimed', 2, 'following'),
('吴俊豪', '13812340009', '抖音', '无效', 'Python入门特惠', '2026-02-05 09:30:00', 'assigned', 1, 'lost'),
('郑雅文', '13812340010', 'B站', '高意向', 'B站UI设计课程', '2026-02-05 20:00:00', 'assigned', 4, 'converted'),
('黄志强', '13812340011', '视频号', '中意向', '视频号直播预约', '2026-02-06 12:00:00', 'unassigned', 0, 'unfollow'),
('周雨晴', '13812340012', '小红书', '高意向', '小红书3月运营课', '2026-02-07 10:00:00', 'assigned', 3, 'following'),
('李明远', '13812340013', '抖音', '中意向', '电商直播带货课', '2026-02-08 14:00:00', 'claimed', 2, 'following'),
('张婉婷', '13812340014', '知乎', '低意向', '数据分析入门指南', '2026-02-09 08:00:00', 'unassigned', 0, 'unfollow'),
('刘浩然', '13812340015', '转介绍', '高意向', '老带新-李总推荐', '2026-02-10 16:00:00', 'assigned', 5, 'converted'),
('王若曦', '13812340016', '抖音', '中意向', 'AI办公提效直播课', '2026-02-11 11:00:00', 'assigned', 2, 'following'),
('陈俊杰', '13812340017', '快手', '无效', '短视频剪辑入门', '2026-02-12 09:00:00', 'unassigned', 1, 'lost'),
('林诗涵', '13812340018', '小红书', '高意向', '小红书3月课程', '2026-02-13 14:00:00', 'assigned', 4, 'converted'),
('杨浩宇', '13812340019', 'B站', '中意向', 'B站数据分析课', '2026-02-14 18:00:00', 'claimed', 3, 'following'),
('赵雪晴', '13812340020', '抖音', '高意向', 'Python副业接单班', '2026-02-15 10:00:00', 'assigned', 5, 'converted')
ON DUPLICATE KEY UPDATE customer_name=VALUES(customer_name), source_channel=VALUES(source_channel), lead_tag=VALUES(lead_tag), follow_count=VALUES(follow_count), convert_status=VALUES(convert_status);

-- oa_lead_dispatch_rule（分配机制）
INSERT INTO oa_lead_dispatch_rule (rule_name, apply_channel, dispatch_mode, batch_size, assign_to_role, status) VALUES
('抖音线索均分规则', '抖音', 'even', 5, '顾问', 1),
('小红书线索轮询规则', '小红书', 'roundrobin', 3, '顾问', 1),
('高意向线索优先分配', '', 'priority', 1, '高级顾问', 1),
('知乎线索指定分配', '知乎', 'even', 2, '顾问', 1),
('转介绍线索优先认领', '转介绍', 'priority', 1, '顾问', 1)
ON DUPLICATE KEY UPDATE rule_name=VALUES(rule_name), dispatch_mode=VALUES(dispatch_mode), status=VALUES(status);

-- oa_customer（客户档案）
INSERT INTO oa_customer (name, gender, age, mobile, wechat, city, occupation, intention_course, intention_level, source_channel, first_inquiry_date, follow_status, total_amount, owner_name) VALUES
('林小涵', '女', 25, '13812340002', 'linxiaohan2026', '上海市', '互联网产品', 'Python全栈', 'A', '小红书', '2026-01-15', 'active', 12800, '顾问A'),
('王思琪', '女', 28, '13812340004', 'wangsiqui2026', '北京市', '市场运营', '新媒体运营', 'A', '抖音', '2026-01-18', 'active', 9800, '顾问A'),
('刘佳怡', '女', 24, '13812340006', 'liujiayi2026', '广州市', '电商运营', '电商直播', 'A', '转介绍', '2026-01-20', 'active', 15800, '顾问B'),
('郑雅文', '女', 26, '13812340010', 'zhengyawen2026', '深圳市', '设计师', 'UI设计', 'A', 'B站', '2026-01-22', 'active', 13800, '顾问A'),
('刘浩然', '男', 30, '13812340015', 'liuhra2026', '成都市', '项目经理', 'AI应用', 'A', '转介绍', '2026-01-25', 'active', 3999, '顾问B'),
('林诗涵', '女', 23, '13812340018', 'linshihan2026', '杭州市', '新媒体', '新媒体运营', 'A', '小红书', '2026-02-01', 'active', 9800, '顾问A'),
('杨浩宇', '男', 27, '13812340019', 'yanghaoyu2026', '南京市', '数据分析', '数据分析就业', 'B', 'B站', '2026-02-05', 'active', 0, '顾问B'),
('周明轩', '男', 29, '13812340001', 'zhoumingxuan', '上海市', '后端开发', 'Python全栈', 'B', '抖音', '2026-02-01', 'active', 0, '顾问A'),
('张浩然', '男', 31, '13812340003', 'zhanghr2026', '北京市', '产品经理', 'AI应用', 'B', '百度', '2026-02-02', 'silent', 0, '顾问A'),
('孙晓彤', '女', 25, '13812340008', 'sunxiaotong2026', '武汉市', '内容运营', '短视频制作', 'B', '知乎', '2026-02-04', 'active', 0, '顾问B'),
('周雨晴', '女', 27, '13812340012', 'zhouyuqing2026', '西安市', '销售', 'Python全栈', 'A', '小红书', '2026-02-07', 'active', 0, '顾问A'),
('李明远', '男', 33, '13812340013', 'limingyuan2026', '重庆市', '创业', '电商直播', 'C', '抖音', '2026-02-08', 'silent', 0, '顾问B'),
('陈子墨', '男', 26, '13812340005', 'chenzimo2026', '天津市', '行政', '办公软件', 'C', '小红书', '2026-02-03', 'churned', 0, '顾问A'),
('赵文博', '男', 24, '13812340007', 'zhaowenbo2026', '苏州市', '市场推广', '短视频制作', 'C', '快手', '2026-02-04', 'churned', 0, '顾问B'),
('王若曦', '女', 29, '13812340016', 'wangruoxi2026', '青岛市', 'HR', 'AI应用办公', 'B', '抖音', '2026-02-11', 'active', 0, '顾问A')
ON DUPLICATE KEY UPDATE name=VALUES(name), follow_status=VALUES(follow_status), total_amount=VALUES(total_amount), owner_name=VALUES(owner_name);

-- oa_followup（跟进管理）
INSERT INTO oa_followup (customer_id, customer_name, follow_user, follow_method, follow_time, content, next_follow_date, follow_status) VALUES
(2, '林小涵', '顾问A', 'call', '2026-02-10 10:00:00', '客户表示对Python全栈非常感兴趣，预算1.2-1.5万，希望系统学习后转行成功就业。已发送课程大纲和学员案例。', '2026-02-17', 'completed'),
(2, '林小涵', '顾问A', 'wechat', '2026-02-17 14:00:00', '客户确认报名意向，讨论上课时间安排。推荐春季班，周末上课。客户表示满意。', '2026-02-24', 'completed'),
(3, '王思琪', '顾问A', 'call', '2026-02-12 11:00:00', '客户做市场运营，想提升数据分析能力。推荐新媒体+数据分析组合课。客户希望先试听。', '2026-02-19', 'completed'),
(4, '刘佳怡', '顾问B', 'meeting', '2026-02-15 15:00:00', '面谈沟通，客户对电商直播非常感兴趣，现场体验了直播设备。成交！', '2026-03-01', 'completed'),
(6, '郑雅文', '顾问A', 'wechat', '2026-02-18 10:00:00', '客户确认报名UI设计班，已付款定金2000元。等待正式开课。', '2026-02-25', 'completed'),
(7, '刘浩然', '顾问B', 'call', '2026-02-20 09:00:00', '客户是项目经理，对AI应用很感兴趣。推荐AI应用办公提效课，预算有限，先报名体验版。', '2026-02-27', 'completed'),
(10, '林诗涵', '顾问A', 'wechat', '2026-02-22 16:00:00', '客户意向高，已试听课程。客户决定报名新媒体运营班全款。成交！', '2026-03-01', 'completed'),
(13, '周明轩', '顾问A', 'call', '2026-02-25 11:00:00', '客户对Python全栈还在考虑中，主要担心学习时间和效果。继续跟进。', '2026-03-04', 'pending'),
(14, '张浩然', '顾问A', 'call', '2026-02-28 10:00:00', '客户表示工作忙，暂无学习计划。已加入沉默池，后续定期跟进。', '2026-03-14', 'abandoned'),
(15, '孙晓彤', '顾问B', 'wechat', '2026-03-01 14:00:00', '客户对短视频制作有兴趣，已发送课程介绍和学员作品集。', '2026-03-08', 'pending'),
(16, '周雨晴', '顾问A', 'call', '2026-03-03 10:30:00', '客户表示身边有朋友学过我们的课程，反馈不错。有报名意向。', '2026-03-10', 'pending')
ON DUPLICATE KEY UPDATE follow_user=VALUES(follow_user), follow_status=VALUES(follow_status);

-- oa_deal（成单管理）
INSERT INTO oa_deal (deal_no, customer_id, customer_name, course_name, order_type, amount, paid_amount, pay_status, payment_stage, order_time, consultant) VALUES
('DEAL20260001', 2, '林小涵', 'Python全栈训练营', 'first', 12800, 12800, 'paid', '全款', '2026-02-18 15:00:00', '顾问A'),
('DEAL20260002', 3, '王思琪', '新媒体运营实战班', 'first', 9800, 9800, 'paid', '全款', '2026-02-19 14:00:00', '顾问A'),
('DEAL20260003', 4, '刘佳怡', '电商直播带货班', 'first', 15800, 5000, 'partial', '定金', '2026-02-20 16:00:00', '顾问B'),
('DEAL20260004', 5, '郑雅文', 'UI设计就业班', 'first', 13800, 13800, 'paid', '全款', '2026-02-25 11:00:00', '顾问A'),
('DEAL20260005', 6, '刘浩然', 'AI应用办公提效课', 'first', 3999, 3999, 'paid', '全款', '2026-02-27 10:00:00', '顾问B'),
('DEAL20260006', 7, '林诗涵', '新媒体运营实战班', 'first', 9800, 9800, 'paid', '全款', '2026-03-01 14:00:00', '顾问A'),
('DEAL20260007', 9, '杨浩宇', '数据分析就业班', 'first', 15800, 0, 'pending', '定金', '2026-03-05 09:00:00', '顾问B'),
('DEAL20260008', 10, '周明轩', 'Python全栈训练营', 'first', 12800, 2000, 'partial', '定金', '2026-03-02 11:00:00', '顾问A'),
('DEAL20260009', 11, '孙晓彤', '短视频制作实战班', 'first', 8800, 0, 'pending', '定金', '2026-03-03 10:00:00', '顾问B'),
('DEAL20260010', 12, '周雨晴', 'Python全栈训练营', 'first', 12800, 5000, 'partial', '定金', '2026-03-04 15:00:00', '顾问A'),
('DEAL20260011', 13, '李明远', '电商直播带货班', 'first', 15800, 0, 'pending', '定金', '2026-03-05 11:00:00', '顾问B')
ON DUPLICATE KEY UPDATE customer_name=VALUES(customer_name), amount=VALUES(amount), paid_amount=VALUES(paid_amount), pay_status=VALUES(pay_status);

-- oa_deal_status（状态管理）
INSERT INTO oa_deal_status (deal_id, deal_no, customer_name, prev_status, status, change_time, changed_by) VALUES
(1, 'DEAL20260001', '林小涵', '', '已下单', '2026-02-18 15:00:00', '顾问A'),
(1, 'DEAL20260001', '林小涵', '已下单', '定金已付', '2026-02-18 15:30:00', '财务A'),
(1, 'DEAL20260001', '林小涵', '定金已付', '已付清', '2026-02-18 18:00:00', '财务A'),
(2, 'DEAL20260002', '王思琪', '', '已下单', '2026-02-19 14:00:00', '顾问A'),
(2, 'DEAL20260002', '王思琪', '已下单', '已付清', '2026-02-19 17:00:00', '财务A'),
(3, 'DEAL20260003', '刘佳怡', '', '已下单', '2026-02-20 16:00:00', '顾问B'),
(3, 'DEAL20260003', '刘佳怡', '已下单', '定金已付', '2026-02-20 16:30:00', '财务A'),
(4, 'DEAL20260004', '郑雅文', '', '已下单', '2026-02-25 11:00:00', '顾问A'),
(4, 'DEAL20260004', '郑雅文', '已下单', '已付清', '2026-02-25 14:00:00', '财务A')
ON DUPLICATE KEY UPDATE status=VALUES(status);

-- oa_deal_stats（业绩统计）
INSERT INTO oa_deal_stats (period_month, consultant, department, first_order_count, renewal_count, upgrade_count, gmv, collected, refunded, gross_profit, efficiency) VALUES
('2026-01', '顾问A', '招生咨询部', 8, 1, 2, 85600, 82000, 0, 42800, 4.28),
('2026-01', '顾问B', '招生咨询部', 6, 2, 1, 67800, 63000, 5000, 33900, 3.39),
('2026-02', '顾问A', '招生咨询部', 12, 2, 3, 138600, 125000, 0, 69300, 6.93),
('2026-02', '顾问B', '招生咨询部', 9, 3, 1, 98900, 88000, 8000, 49450, 4.95),
('2026-03', '顾问A', '招生咨询部', 5, 1, 0, 58600, 32000, 0, 29300, 2.93),
('2026-03', '顾问B', '招生咨询部', 4, 0, 1, 42300, 21000, 0, 21150, 2.12)
ON DUPLICATE KEY UPDATE gmv=VALUES(gmv), collected=VALUES(collected), refunded=VALUES(refunded), gross_profit=VALUES(gross_profit), efficiency=VALUES(efficiency);

-- oa_student_ext（学员管理扩展）
INSERT INTO oa_student_ext (student_id, name, gender, age, mobile, course_name, class_name, headteacher, coach, enroll_date, study_stage) VALUES
(1, '张三', '男', 23, '13800000001', 'Python全栈训练营', 'Python全栈-2026春季1期', '班主任A', '教练甲', '2026-02-03', 'learning'),
(2, '李四', '女', 25, '13800000002', '新媒体运营实战班', '新媒体-2026春季班', '班主任A', '教练乙', '2026-02-10', 'learning'),
(3, '王五', '男', 27, '13800000003', 'AI应用办公提效课', 'AI应用-周末班', '班主任A', '教练丙', '2026-02-15', 'preview')
ON DUPLICATE KEY UPDATE course_name=VALUES(course_name), study_stage=VALUES(study_stage);

-- oa_student_progress（进度跟踪）
INSERT INTO oa_student_progress (student_id, student_name, course_name, class_name, current_stage, progress_rate, headteacher_comment, coach_comment, next_follow_date, record_date) VALUES
(1, '张三', 'Python全栈训练营', 'Python全栈-2026春季1期', '第1周-Python基础语法', 8.33, '学习态度认真，作业完成度95%。', '基础不错，可以加快进度。', '2026-03-01', '2026-02-10'),
(1, '张三', 'Python全栈训练营', 'Python全栈-2026春季1期', '第2周-Web开发入门', 16.67, 'Web开发进度正常。', '能独立完成简单页面了。', '2026-03-08', '2026-02-17'),
(2, '李四', '新媒体运营实战班', '新媒体-2026春季班', '第1周-平台规则与定位', 12.50, '小红书定位已确定，开始产出内容。', '内容创意不错，继续保持。', '2026-02-28', '2026-02-14'),
(3, '王五', 'AI应用办公提效课', 'AI应用-周末班', '预习阶段', 5.00, '资料已发放，正在预习。', '预计下周开始正式课程。', '2026-03-07', '2026-02-21')
ON DUPLICATE KEY UPDATE current_stage=VALUES(current_stage), progress_rate=VALUES(progress_rate);

-- oa_student_exception（异常管理）
INSERT INTO oa_student_exception (student_id, student_name, course_name, exception_type, severity, occur_time, handler, handle_status, result) VALUES
(2, '李四', '新媒体运营实战班', 'study_interrupt', 'medium', '2026-02-20 14:00:00', '班主任A', 'resolved', '因工作出差2周，已安排补课方案，学员满意。'),
(3, '王五', 'AI应用办公提效课', 'drop_request', 'high', '2026-02-25 10:00:00', '顾问A', 'processing', '学员觉得课程节奏快，希望退款。顾问正在沟通挽留。'),
(1, '张三', 'Python全栈训练营', 'refund_complaint', 'urgent', '2026-02-28 16:00:00', '班主任A', 'resolved', '学员反映视频卡顿，已升级服务器并赠送下期课程优惠券。')
ON DUPLICATE KEY UPDATE exception_type=VALUES(exception_type), severity=VALUES(severity), handle_status=VALUES(handle_status);

-- oa_order_multi（多人参与）
INSERT INTO oa_order_multi (order_id, deal_no, customer_name, course_name, role_type, user_name, contribution_type, commission_amount, assign_time, status) VALUES
(1, 'DEAL20260001', '林小涵', 'Python全栈训练营', 'consultant', '顾问A', 'record', 1024, '2026-02-18 15:00:00', 'confirmed'),
(1, 'DEAL20260001', '林小涵', 'Python全栈训练营', 'headteacher', '班主任A', 'deliver', 768, '2026-02-18 16:00:00', 'pending'),
(2, 'DEAL20260002', '王思琪', '新媒体运营实战班', 'consultant', '顾问A', 'record', 784, '2026-02-19 14:00:00', 'confirmed'),
(2, 'DEAL20260002', '王思琪', '新媒体运营实战班', 'coach', '教练乙', 'deliver', 588, '2026-02-19 15:00:00', 'pending'),
(3, 'DEAL20260003', '刘佳怡', '电商直播带货班', 'consultant', '顾问B', 'record', 1264, '2026-02-20 16:00:00', 'confirmed'),
(3, 'DEAL20260003', '刘佳怡', '电商直播带货班', 'seller', '顾问B', 'record', 948, '2026-02-20 16:00:00', 'confirmed')
ON DUPLICATE KEY UPDATE commission_amount=VALUES(commission_amount), status=VALUES(status);

-- oa_order_flow（状态流转）
INSERT INTO oa_order_flow (order_id, deal_no, status, prev_status, curr_status, change_time, changed_by) VALUES
(1, 'DEAL20260001', '下单成功', '', '下单成功', '2026-02-18 15:00:00', '顾问A'),
(1, 'DEAL20260001', '定金已付', '下单成功', '定金已付', '2026-02-18 15:30:00', '财务A'),
(1, 'DEAL20260001', '已付清', '定金已付', '已付清', '2026-02-18 18:00:00', '财务A'),
(2, 'DEAL20260002', '下单成功', '', '下单成功', '2026-02-19 14:00:00', '顾问A'),
(2, 'DEAL20260002', '已付清', '下单成功', '已付清', '2026-02-19 17:00:00', '财务A'),
(3, 'DEAL20260003', '下单成功', '', '下单成功', '2026-02-20 16:00:00', '顾问B'),
(3, 'DEAL20260003', '定金已付', '下单成功', '定金已付', '2026-02-20 16:30:00', '财务A')
ON DUPLICATE KEY UPDATE curr_status=VALUES(curr_status);

-- oa_repurchase（复购管理）
INSERT INTO oa_repurchase (student_id, student_name, mobile, original_course, original_amount, repurchase_course, repurchase_amount, repurchase_type, repurchase_time, consultant) VALUES
(1, '张三', '13800000001', 'Python入门课', 3999, 'Python全栈训练营', 12800, 'upgrade', '2026-02-18 15:00:00', '顾问A'),
(4, '老学员A', '13900000001', '新媒体运营实战班', 9800, 'AI应用办公提效课', 3999, 'renewal', '2026-02-20 10:00:00', '顾问B'),
(5, '老学员B', '13900000002', '短视频制作课', 6800, '电商直播带货班', 8800, 'upgrade', '2026-03-01 14:00:00', '顾问A'),
(6, '老学员C', '13900000003', 'Python全栈', 12800, '数据分析就业班', 15800, 'upgrade', '2026-03-05 09:00:00', '顾问B')
ON DUPLICATE KEY UPDATE repurchase_course=VALUES(repurchase_course), repurchase_amount=VALUES(repurchase_amount);

-- oa_repurchase_followup（二销跟进）
INSERT INTO oa_repurchase_followup (student_id, student_name, purchased_course, last_order_time, repurchase_intention, target_course, owner, follow_content, next_plan, estimated_time) VALUES
(2, '李四', '新媒体运营实战班', '2026-02-10 14:00:00', 'high', 'AI应用办公', '顾问A', '客户对AI工具非常感兴趣，已发AI课程介绍。', '下周二再跟进，催单。', '2026-04-01'),
(3, '王五', 'AI应用办公提效课', '2026-02-27 10:00:00', 'medium', 'Python全栈', '顾问B', '客户想系统学Python，建议先上入门再进阶。', '发Python学习路线图，继续跟进。', '2026-05-01'),
(7, '老学员D', '数据分析就业班', '2026-03-01 10:00:00', 'high', 'BI可视化进阶课', '顾问A', '学员已完成课程，对BI方向感兴趣。推荐进阶课。', '预约一对一咨询。', '2026-04-15'),
(8, '老学员E', '电商直播带货班', '2026-02-20 16:00:00', 'low', '短视频制作', '顾问B', '学员刚学完，时间精力有限，暂时不考虑。', '3个月后再联系。', '2026-06-01')
ON DUPLICATE KEY UPDATE repurchase_intention=VALUES(repurchase_intention), follow_content=VALUES(follow_content);

-- oa_repurchase_stats（二销统计）
INSERT INTO oa_repurchase_stats (period_month, period_name, leads_count, follow_count, deal_count, gmv, conversion_rate, per_capita, top_consultant, top_consultant_gmv, top_course, top_course_gmv, mom_change) VALUES
('2026-01', '2026年1月', 35, 62, 8, 89600, 22.86, 11200, '顾问A', 39800, 'Python全栈', 42800, 0),
('2026-02', '2026年2月', 48, 85, 12, 142600, 25.00, 11883, '顾问A', 68600, 'Python全栈', 55600, 0.1412),
('2026-03', '2026年3月', 22, 38, 4, 41399, 18.18, 10350, '顾问B', 24800, 'AI应用', 16599, -0.7096)
ON DUPLICATE KEY UPDATE leads_count=VALUES(leads_count), deal_count=VALUES(deal_count), gmv=VALUES(gmv), conversion_rate=VALUES(conversion_rate);

-- oa_refund_v2（退款管理）
INSERT INTO oa_refund_v2 (refund_no, order_id, deal_no, customer_name, course_name, order_amount, paid_amount, refund_amount, reason, status, applicant, approver, requested_at, approved_at, completed_at) VALUES
('REF20260001', 1, 'DEAL20260001', '学员甲', 'Python入门课', 3999, 3999, 3999, '课程内容与宣传不符，教学质量差', 'refunded', '学员甲', '财务A', '2026-02-05 10:00:00', '2026-02-06 14:00:00', '2026-02-07 11:00:00'),
('REF20260002', 2, 'DEAL20260002', '学员乙', '新媒体体验课', 1999, 1999, 1999, '时间冲突，无法继续参加课程', 'approved', '学员乙', '财务A', '2026-02-10 09:00:00', '2026-02-11 10:00:00', NULL),
('REF20260003', 3, 'DEAL20260003', '学员丙', 'UI设计课', 8800, 8800, 6000, '经济困难，需退部分费用', 'processing', '学员丙', '', '2026-02-25 14:00:00', NULL, NULL),
('REF20260004', 4, 'DEAL20260004', '学员丁', '数据分析入门', 5999, 2999, 2999, '已学完部分，申请退未学部分', 'pending', '学员丁', '', '2026-03-01 16:00:00', NULL, NULL)
ON DUPLICATE KEY UPDATE refund_amount=VALUES(refund_amount), status=VALUES(status);

-- oa_finance_stats（财务统计）
INSERT INTO oa_finance_stats (period_month, period_name, income_total, expense_total, net_balance, tuition_income, other_income, personnel_cost, ops_cost, refund_amount, commission_paid) VALUES
('2025-10', '2025年10月', 186000, 142000, 44000, 168000, 18000, 68000, 42000, 12000, 20000),
('2025-11', '2025年11月', 215000, 158000, 57000, 196000, 19000, 72000, 48000, 18000, 20000),
('2025-12', '2025年12月', 268000, 182000, 86000, 248000, 20000, 78000, 52000, 22000, 30000),
('2026-01', '2026年01月', 242000, 175000, 67000, 220000, 22000, 80000, 51000, 24000, 20000),
('2026-02', '2026年02月', 318000, 198000, 120000, 296000, 22000, 85000, 56000, 27000, 30000),
('2026-03', '2026年03月', 142000, 126000, 16000, 128000, 14000, 82000, 28000, 8000, 8000)
ON DUPLICATE KEY UPDATE income_total=VALUES(income_total), expense_total=VALUES(expense_total), net_balance=VALUES(net_balance);

-- oa_course_cost（单课成本）
INSERT INTO oa_course_cost (course_id, course_name, class_count, total_students, course_income, teacher_fee, assistant_fee, material_fee, ops_alloc, other_cost, total_cost, net_profit, profit_rate, per_capita_cost) VALUES
(1, 'Python全栈训练营', 3, 28, 358400, 112000, 24000, 12000, 28000, 8000, 184000, 174400, 48.66, 6571),
(2, '新媒体运营实战班', 4, 32, 313600, 96000, 18000, 8000, 22000, 6000, 150000, 163600, 52.17, 4688),
(3, 'AI应用办公提效课', 2, 18, 71982, 24000, 8000, 4000, 12000, 4000, 52000, 19982, 27.76, 2889)
ON DUPLICATE KEY UPDATE course_income=VALUES(course_income), net_profit=VALUES(net_profit), profit_rate=VALUES(profit_rate);

-- oa_profit_analysis（利润分析）
INSERT INTO oa_profit_analysis (period_month, dept_channel, gmv, direct_cost, indirect_cost, total_cost, gross_profit, gross_rate, per_capita_gmv) VALUES
('2026-01', '招生咨询部', 153400, 58000, 12000, 70000, 83400, 54.37, 25667),
('2026-01', '交付部', 153400, 72000, 8000, 80000, 73400, 47.85, 12272),
('2026-02', '招生咨询部', 237500, 88000, 14000, 102000, 135500, 57.05, 39583),
('2026-02', '交付部', 237500, 96000, 9000, 105000, 132500, 55.79, 18417),
('2026-03', '招生咨询部', 100900, 42000, 8000, 50000, 50900, 50.45, 16817),
('2026-03', '交付部', 100900, 48000, 6000, 54000, 46900, 46.48, 10400)
ON DUPLICATE KEY UPDATE gmv=VALUES(gmv), gross_profit=VALUES(gross_profit), gross_rate=VALUES(gross_rate);

-- oa_salary_wages（工资）
INSERT INTO oa_salary_wages (slip_no, user_id, user_name, department, position, salary_month, base_salary, position_salary, performance_salary, gross_amount, social_deduction, housing_fund, tax_amount, net_amount, status, paid_date) VALUES
('WAGE20260301', 2, '顾问A', '招生咨询部', '课程顾问', '2026-03', 6000, 2000, 3000, 11000, 800, 600, 580, 9020, 'paid', '2026-03-28'),
('WAGE20260302', 3, '班主任A', '教务部', '班主任', '2026-03', 5500, 1500, 2000, 9000, 720, 540, 424, 7316, 'paid', '2026-03-28'),
('WAGE20260303', 4, '教练甲', '教学部', '教练', '2026-03', 7000, 2000, 2500, 11500, 920, 680, 690, 9210, 'paid', '2026-03-28'),
('WAGE20260304', 2, '顾问A', '招生咨询部', '课程顾问', '2026-02', 6000, 2000, 5000, 13000, 800, 600, 830, 10770, 'paid', '2026-02-28'),
('WAGE20260305', 3, '班主任A', '教务部', '班主任', '2026-02', 5500, 1500, 1800, 8800, 720, 540, 384, 7156, 'paid', '2026-02-28')
ON DUPLICATE KEY UPDATE net_amount=VALUES(net_amount), status=VALUES(status);

-- oa_salary_bonus（奖金）
INSERT INTO oa_salary_bonus (user_id, user_name, department, bonus_type, related_period, amount, calc_basis, status, paid_time) VALUES
(2, '顾问A', '招生咨询部', 'order_commission', '2026-02月订单', 5248, '首单10%+续单5%阶梯提成', 'paid', '2026-02-28 18:00:00'),
(4, '教练甲', '教学部', 'order_commission', '2026-02月交付', 3160, '交付提成5%+续费2%', 'paid', '2026-02-28 18:00:00'),
(3, '班主任A', '教务部', 'monthly_bonus', '2026-02月', 1000, '月度绩效考核优秀奖', 'paid', '2026-02-28 18:00:00'),
(2, '顾问A', '招生咨询部', 'activity_reward', '2026-02月活动', 500, '2月开门红活动参与奖励', 'paid', '2026-02-28 18:00:00'),
(8, '运营小王', '运营部', 'monthly_bonus', '2026-02月', 800, '2月引流数据优秀奖', 'paid', '2026-02-28 18:00:00'),
(5, '财务A', '财务部', 'quarterly_bonus', '2026-Q1', 2000, 'Q1财务结算优秀奖', 'confirmed', NULL)
ON DUPLICATE KEY UPDATE amount=VALUES(amount), status=VALUES(status);

-- oa_ops_funnel（漏斗分析）
INSERT INTO oa_ops_funnel (period_month, funnel_stage, current_count, prev_count, conversion_rate, churn_rate, mom_change) VALUES
('2026-02', '曝光', 185000, 162000, 100.00, 0, 0.1419),
('2026-02', '点击', 14800, 12960, 8.00, 92.00, 0.1419),
('2026-02', '留资', 3552, 3240, 24.00, 76.00, 0.0963),
('2026-02', '电销接通', 2131, 1944, 60.00, 40.00, 0.0962),
('2026-02', '邀约到店', 639, 583, 30.00, 70.00, 0.0960),
('2026-02', '体验课', 256, 233, 40.00, 60.00, 0.0987),
('2026-02', '报名', 103, 94, 40.23, 59.77, 0.0957),
('2026-02', '付费', 82, 75, 79.61, 20.39, 0.0933),
('2026-03', '曝光', 128000, 185000, 100.00, 0, -0.3081),
('2026-03', '点击', 10240, 14800, 8.00, 92.00, -0.3081),
('2026-03', '留资', 2458, 3552, 24.00, 76.00, -0.3081),
('2026-03', '电销接通', 1475, 2131, 60.00, 40.00, -0.3078),
('2026-03', '邀约到店', 443, 639, 30.00, 70.00, -0.3067),
('2026-03', '体验课', 177, 256, 40.00, 60.00, -0.3086),
('2026-03', '报名', 71, 103, 40.11, 59.89, -0.3107),
('2026-03', '付费', 57, 82, 80.28, 19.72, -0.3050)
ON DUPLICATE KEY UPDATE current_count=VALUES(current_count), conversion_rate=VALUES(conversion_rate), mom_change=VALUES(mom_change);

-- oa_ops_rank（排行分析）
INSERT INTO oa_ops_rank (period_month, rank_no, name, dimension, metric_value, unit, mom_change) VALUES
('2026-02', 1, '顾问A', 'gmv', 138600, '元', 0.5981),
('2026-02', 2, '顾问B', 'gmv', 98900, '元', 0.4588),
('2026-02', 1, '顾问A', 'order_count', 12, '单', 0.5000),
('2026-02', 2, '顾问B', 'order_count', 9, '单', 0.5000),
('2026-02', 1, '抖音', 'leads', 420, '人', 0.2000),
('2026-02', 2, '小红书', 'leads', 310, '人', 0.1500),
('2026-02', 1, 'Python全栈', 'conversion', 12.50, '%', 0.0500),
('2026-02', 2, '新媒体运营', 'conversion', 10.80, '%', 0.0200),
('2026-03', 1, '顾问A', 'gmv', 58600, '元', -0.5771),
('2026-03', 2, '顾问B', 'gmv', 42300, '元', -0.5723),
('2026-03', 1, '抖音', 'leads', 285, '人', -0.3214),
('2026-03', 2, '小红书', 'leads', 198, '人', -0.3613)
ON DUPLICATE KEY UPDATE metric_value=VALUES(metric_value), mom_change=VALUES(mom_change);

-- oa_approval（审批）
INSERT INTO oa_approval (approval_no, approval_type, applicant, department, content_summary, requested_at, status, current_approver) VALUES
('APPR20260001', 'leave', '教练甲', '教学部', '事假申请：3月5日-3月7日，因家中有事需回老家处理，共计3天。已安排教练乙代课。', '2026-03-03 09:00:00', 'pending', '教学部主管'),
('APPR20260002', 'reimburse', '顾问A', '招生咨询部', '差旅报销：2月出差拜访客户，报销交通费480元、餐费220元，共计700元。票据附后。', '2026-03-01 14:00:00', 'approved', '财务A'),
('APPR20260003', 'promotion', '班主任A', '教务部', '转正申请：试用期3个月表现优秀，学员满意度98%，建议予以转正，转正后薪资调整至9000元/月。', '2026-03-02 10:00:00', 'pending', '管理部主管'),
('APPR20260004', 'resignation', '员工戊', '教学部', '离职申请：因个人发展原因申请离职，最后工作日为2026年3月31日。已交接工作清单附后。', '2026-03-05 16:00:00', 'pending', '管理部主管'),
('APPR20260005', 'leave', '运营小王', '运营部', '病假申请：3月10日因发烧需就医，半天病假，已安排工作交接。', '2026-03-10 07:30:00', 'pending', '运营部主管'),
('APPR20260006', 'reimburse', '班主任A', '教务部', '学员活动物料采购报销：迎新活动购买礼品物料共计860元。', '2026-02-28 11:00:00', 'approved', '财务A')
ON DUPLICATE KEY UPDATE status=VALUES(status), current_approver=VALUES(current_approver);

-- oa_file_manager（文件管理）
INSERT INTO oa_file_manager (file_name, file_type, related_object, uploader, file_size, file_url, status) VALUES
('DEAL20260001-林小涵.pdf', 'contract', '订单/1', '顾问A', 524288, 'https://storage.example.com/contracts/2026/DEAL20260001.pdf', 'normal'),
('DEAL20260002-王思琪.pdf', 'contract', '订单/2', '顾问A', 498560, 'https://storage.example.com/contracts/2026/DEAL20260002.pdf', 'normal'),
('FP-2026-0001-张三.pdf', 'invoice', '发票/1', '财务A', 128000, 'https://storage.example.com/invoices/2026/FP-2026-0001.pdf', 'normal'),
('FP-2026-0002-林小涵.pdf', 'invoice', '发票/2', '财务A', 136000, 'https://storage.example.com/invoices/2026/FP-2026-0002.pdf', 'normal'),
('CERT-2026-0001-张三.pdf', 'certificate', '证书/1', '班主任A', 256000, 'https://storage.example.com/certs/2026/CERT-2026-0001.pdf', 'normal'),
('营业执照-2026.pdf', 'qualification', '公司资质', '管理员', 1024000, 'https://storage.example.com/qualification/business-license.pdf', 'normal'),
('DEAL20260003-刘佳怡.pdf', 'contract', '订单/3', '顾问B', 512000, 'https://storage.example.com/contracts/2026/DEAL20260003.pdf', 'normal'),
('DEAL20260004-郑雅文.pdf', 'contract', '订单/4', '顾问A', 487424, 'https://storage.example.com/contracts/2026/DEAL20260004.pdf', 'normal')
ON DUPLICATE KEY UPDATE file_name=VALUES(file_name), status=VALUES(status);

-- oa_system_settings（系统设置）
INSERT INTO oa_system_settings (setting_category, setting_key, setting_value, description) VALUES
('company', 'company_name', '上海XX在线教育科技有限公司', '公司全称'),
('company', 'company_short', 'XX教育', '公司简称'),
('company', 'contact_mobile', '400-888-8888', '客服联系电话'),
('company', 'contact_email', 'service@example.com', '客服邮箱'),
('notification', 'email_notifications', '1', '启用邮件通知：1启用 0停用'),
('notification', 'sms_notifications', '1', '启用短信通知：1启用 0停用'),
('notification', 'wechat_notifications', '1', '启用微信通知：1启用 0停用'),
('workflow', 'refund_approval_required', '1', '退款必须审批：1必须 0可选'),
('workflow', 'commission_auto_calc', '1', '提成自动计算：1自动 0手动'),
('workflow', 'lead_auto_dispatch', '1', '线索自动分配：1自动 0手动'),
('custom_field', 'student_level_options', 'A,B,C,D', '学员等级自定义选项')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), description=VALUES(description);

-- =====================================================
-- 完成提示
-- =====================================================
SELECT 'patch_v3.sql 执行完成！共新增28个表 + 菜单数据 + 测试数据' AS result;
