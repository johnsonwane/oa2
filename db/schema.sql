CREATE DATABASE IF NOT EXISTS oa2 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE oa2;

CREATE TABLE IF NOT EXISTS oa_user (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  employee_no VARCHAR(50) DEFAULT '',
  company_name VARCHAR(100) DEFAULT '',
  real_name VARCHAR(50) NOT NULL,
  role VARCHAR(30) NOT NULL,
  gender VARCHAR(10) DEFAULT '',
  mobile VARCHAR(20) DEFAULT '',
  email VARCHAR(100) DEFAULT '',
  id_no VARCHAR(30) DEFAULT '',
  department VARCHAR(50) DEFAULT '',
  position VARCHAR(50) DEFAULT '',
  social_city VARCHAR(50) DEFAULT '',
  hukou_place VARCHAR(100) DEFAULT '',
  hukou_type VARCHAR(20) DEFAULT '',
  birth_date DATE DEFAULT NULL,
  age INT DEFAULT NULL,
  native_place VARCHAR(100) DEFAULT '',
  ethnicity VARCHAR(30) DEFAULT '',
  marital_status VARCHAR(20) DEFAULT '',
  home_address VARCHAR(255) DEFAULT '',
  emergency_contact VARCHAR(100) DEFAULT '',
  education VARCHAR(50) DEFAULT '',
  graduation_school VARCHAR(100) DEFAULT '',
  major VARCHAR(100) DEFAULT '',
  contract_years VARCHAR(20) DEFAULT '',
  working_days INT DEFAULT 0,
  contract_end_date DATE DEFAULT NULL,
  is_probation TINYINT NOT NULL DEFAULT 1,
  probation_salary DECIMAL(10,2) DEFAULT 0,
  regular_date DATE DEFAULT NULL,
  regular_salary DECIMAL(10,2) DEFAULT 0,
  bank_name VARCHAR(100) DEFAULT '',
  bank_card_no VARCHAR(50) DEFAULT '',
  salary_adjust_records TEXT,
  employment_status VARCHAR(20) DEFAULT '在职',
  leave_date DATE DEFAULT NULL,
  hire_date DATE DEFAULT NULL,
  last_login_at DATETIME DEFAULT NULL,
  remark VARCHAR(255) DEFAULT '',
  status TINYINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



CREATE TABLE IF NOT EXISTS oa_session (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  issued_at DATETIME NOT NULL,
  expires_at DATETIME NOT NULL,
  revoked_at DATETIME DEFAULT NULL,
  last_seen_at DATETIME DEFAULT NULL,
  last_ip VARCHAR(64) DEFAULT '',
  user_agent VARCHAR(255) DEFAULT '',
  PRIMARY KEY (id),
  UNIQUE KEY uk_token_hash (token_hash),
  KEY idx_session_user (user_id),
  KEY idx_session_expire (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_department (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  dept_name VARCHAR(50) NOT NULL,
  dept_code VARCHAR(50) NOT NULL,
  parent_name VARCHAR(50) DEFAULT '',
  status TINYINT NOT NULL DEFAULT 1,
  sort_no INT NOT NULL DEFAULT 99,
  remark VARCHAR(255) DEFAULT '',
  PRIMARY KEY (id),
  UNIQUE KEY uk_dept_code (dept_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_user_group (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  group_name VARCHAR(50) NOT NULL,
  group_code VARCHAR(50) NOT NULL,
  remark VARCHAR(255) DEFAULT '',
  status TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uk_group_code (group_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_permission (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  perm_name VARCHAR(80) NOT NULL,
  perm_code VARCHAR(80) NOT NULL,
  module_name VARCHAR(50) DEFAULT '系统管理',
  remark VARCHAR(255) DEFAULT '',
  status TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uk_perm_code (perm_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_user_group_rel (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  group_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_user_group (user_id, group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_group_permission_rel (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  group_id INT UNSIGNED NOT NULL,
  perm_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_group_perm (group_id, perm_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_menu (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  parent_name VARCHAR(50) DEFAULT '',
  menu_name VARCHAR(100) NOT NULL,
  menu_key VARCHAR(100) NOT NULL,
  path VARCHAR(255) NOT NULL,
  icon VARCHAR(100) DEFAULT '',
  sort_no INT NOT NULL DEFAULT 0,
  status TINYINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_menu_key (menu_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_student (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL,
  gender VARCHAR(10) DEFAULT '',
  birthday DATE DEFAULT NULL,
  phone VARCHAR(20) NOT NULL,
  wechat_name VARCHAR(80) DEFAULT '',
  wechat VARCHAR(50) DEFAULT '',
  id_no VARCHAR(30) DEFAULT '',
  level VARCHAR(30) DEFAULT '',
  intention_level VARCHAR(30) DEFAULT '',
  follow_status VARCHAR(30) DEFAULT '',
  source VARCHAR(50) DEFAULT '',
  source_channel VARCHAR(50) DEFAULT '',
  miniapp_openid VARCHAR(80) DEFAULT '',
  is_student TINYINT NOT NULL DEFAULT 0,
  lead_registered_at DATETIME DEFAULT NULL,
  converted_at DATETIME DEFAULT NULL,
  owner_consultant_user_id INT UNSIGNED DEFAULT NULL,
  headteacher_user_id INT UNSIGNED DEFAULT NULL,
  coach_user_id INT UNSIGNED DEFAULT NULL,
  student_stage VARCHAR(30) NOT NULL DEFAULT 'lead',
  enrolled_courses JSON DEFAULT NULL,
  consultant VARCHAR(50) DEFAULT '',
  delivery_coach VARCHAR(50) DEFAULT '',
  address VARCHAR(255) DEFAULT '',
  remark VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_course (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_name VARCHAR(100) NOT NULL,
  coach_name VARCHAR(50) NOT NULL,
  period_weeks INT NOT NULL DEFAULT 0,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  status TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_referrer (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL,
  wechat_name VARCHAR(80) DEFAULT '',
  phone VARCHAR(20) NOT NULL,
  channel VARCHAR(50) DEFAULT '',
  commission_type VARCHAR(20) NOT NULL DEFAULT 'rate',
  commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
  fixed_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  payout_detail VARCHAR(255) DEFAULT '',
  remark VARCHAR(255) DEFAULT '',
  status TINYINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_referrer_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_external_contacts_local (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  customer_name VARCHAR(120) NOT NULL DEFAULT '',
  description_text VARCHAR(255) NOT NULL DEFAULT '',
  follower_name VARCHAR(80) NOT NULL DEFAULT '',
  follower_account VARCHAR(80) NOT NULL DEFAULT '',
  follower_department VARCHAR(120) NOT NULL DEFAULT '',
  follow_time DATETIME DEFAULT NULL,
  source VARCHAR(80) NOT NULL DEFAULT '',
  mobile VARCHAR(40) NOT NULL DEFAULT '',
  enterprise VARCHAR(120) NOT NULL DEFAULT '',
  email VARCHAR(120) NOT NULL DEFAULT '',
  address VARCHAR(255) NOT NULL DEFAULT '',
  job_title VARCHAR(120) NOT NULL DEFAULT '',
  phone VARCHAR(40) NOT NULL DEFAULT '',
  tag_group1_student_level VARCHAR(80) NOT NULL DEFAULT '',
  tag_group2_source VARCHAR(80) NOT NULL DEFAULT '',
  row_hash CHAR(64) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_external_contacts_local_row_hash (row_hash),
  KEY idx_external_contacts_local_follow_time (follow_time),
  KEY idx_external_contacts_local_follower_account (follower_account)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_external_contacts_local_account_note (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  follower_account VARCHAR(80) NOT NULL,
  account_note VARCHAR(120) NOT NULL DEFAULT '',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_external_contacts_local_account_note (follower_account)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_order (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL,
  course_id INT UNSIGNED NOT NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  pay_status TINYINT NOT NULL DEFAULT 0,
  payment_stage VARCHAR(20) NOT NULL DEFAULT 'full',
  sales_commission_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  seller_user_id INT UNSIGNED DEFAULT NULL,
  seller_role VARCHAR(20) DEFAULT '',
  seller_commission_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  referrer_id INT UNSIGNED DEFAULT NULL,
  referrer_commission_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  student_wechat_name VARCHAR(80) DEFAULT '',
  student_mobile VARCHAR(20) DEFAULT '',
  student_address VARCHAR(255) DEFAULT '',
  payment_time DATETIME DEFAULT NULL,
  receipt_time DATETIME DEFAULT NULL,
  refund_time DATETIME DEFAULT NULL,
  refund_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  remark VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_delivery_log (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL,
  coach_user_id INT UNSIGNED DEFAULT NULL,
  progress_stage VARCHAR(30) DEFAULT '',
  content TEXT NOT NULL,
  log_date DATE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_receipt (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  receipt_no VARCHAR(60) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  channel VARCHAR(50) DEFAULT '',
  channel_txn_id VARCHAR(80) DEFAULT NULL,
  receiver_user_id INT UNSIGNED DEFAULT NULL,
  pay_method VARCHAR(30) DEFAULT '',
  pay_time DATETIME DEFAULT NULL,
  refund_time DATETIME DEFAULT NULL,
  refund_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  verified_status TINYINT NOT NULL DEFAULT 0,
  remark VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_receipt_no (receipt_no),
  UNIQUE KEY uk_receipt_channel_txn (channel, channel_txn_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS oa_refund_request (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  reason VARCHAR(255) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  requested_by_user_id INT UNSIGNED DEFAULT NULL,
  approved_by_user_id INT UNSIGNED DEFAULT NULL,
  paid_by_user_id INT UNSIGNED DEFAULT NULL,
  requested_at DATETIME NOT NULL,
  approved_at DATETIME DEFAULT NULL,
  paid_at DATETIME DEFAULT NULL,
  remark VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_refund_order (order_id),
  KEY idx_refund_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_refund_reversal_task (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  refund_request_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED DEFAULT NULL,
  reversal_type VARCHAR(20) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  remark VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_rrt_refund (refund_request_id),
  KEY idx_rrt_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_finance_record (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  record_type ENUM('income','expense') NOT NULL,
  item_name VARCHAR(100) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  record_date DATE NOT NULL,
  remark VARCHAR(255) DEFAULT '',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_todo (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(150) NOT NULL,
  priority VARCHAR(10) DEFAULT '中',
  status VARCHAR(20) DEFAULT 'todo',
  due_date DATE DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_notification (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(120) NOT NULL,
  content VARCHAR(255) NOT NULL,
  is_read TINYINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO oa_department (dept_name, dept_code, parent_name, status, sort_no, remark) VALUES
('老板','boss_dept','',1,1,'老板办公室'),
('管理部','management_dept','',1,10,'综合管理'),
('顾问部','consulting_dept','',1,20,'课程销售与咨询'),
('交付部','delivery_dept','',1,30,'班主任/教练交付'),
('财务部','finance_dept','',1,40,'收款与核算'),
('运营部','ops_dept','',1,50,'增长运营'),
('外协','outsource_dept','',1,60,'外部协作')
ON DUPLICATE KEY UPDATE dept_name=VALUES(dept_name), parent_name=VALUES(parent_name), status=VALUES(status), sort_no=VALUES(sort_no), remark=VALUES(remark);


-- Sprint123 merged tables (previously in db/sprint123_extension.sql)

-- Sprint 1/2/3 extension tables

CREATE TABLE IF NOT EXISTS oa_material (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(150) NOT NULL,
  material_type VARCHAR(30) DEFAULT 'article',
  category VARCHAR(50) DEFAULT '',
  tags VARCHAR(255) DEFAULT '',
  cover_url VARCHAR(255) DEFAULT '',
  content_url VARCHAR(255) DEFAULT '',
  description VARCHAR(255) DEFAULT '',
  owner_user_id INT UNSIGNED DEFAULT NULL,
  status TINYINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_material_campaign (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  material_id INT UNSIGNED NOT NULL,
  channel VARCHAR(30) NOT NULL,
  campaign_name VARCHAR(120) NOT NULL,
  consultant_user_id INT UNSIGNED DEFAULT NULL,
  landing_url VARCHAR(255) DEFAULT '',
  qr_code_url VARCHAR(255) DEFAULT '',
  status TINYINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_material_claim (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  campaign_id INT UNSIGNED NOT NULL,
  material_id INT UNSIGNED NOT NULL,
  consultant_user_id INT UNSIGNED DEFAULT NULL,
  wechat_name VARCHAR(80) DEFAULT '',
  avatar_url VARCHAR(255) DEFAULT '',
  mobile VARCHAR(20) DEFAULT '',
  miniapp_openid VARCHAR(80) DEFAULT '',
  source_channel VARCHAR(50) DEFAULT '',
  claim_time DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_contract (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  contract_no VARCHAR(80) NOT NULL,
  order_id INT UNSIGNED NOT NULL,
  student_id INT UNSIGNED NOT NULL,
  seller_user_id INT UNSIGNED DEFAULT NULL,
  sign_time DATETIME DEFAULT NULL,
  contract_url VARCHAR(255) DEFAULT '',
  status VARCHAR(30) DEFAULT 'draft',
  remark VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_contract_no (contract_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_invoice_profile (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED DEFAULT NULL,
  company_name VARCHAR(120) NOT NULL,
  tax_no VARCHAR(50) DEFAULT '',
  address VARCHAR(255) DEFAULT '',
  bank_name VARCHAR(120) DEFAULT '',
  bank_account VARCHAR(80) DEFAULT '',
  contact_name VARCHAR(50) DEFAULT '',
  contact_mobile VARCHAR(20) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_invoice (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  receipt_id INT UNSIGNED DEFAULT NULL,
  invoice_profile_id INT UNSIGNED DEFAULT NULL,
  invoice_no VARCHAR(80) DEFAULT '',
  amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  invoice_type VARCHAR(30) DEFAULT 'normal',
  status VARCHAR(30) DEFAULT 'pending',
  issued_at DATETIME DEFAULT NULL,
  remark VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_payment_callback_log (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  channel VARCHAR(30) NOT NULL,
  biz_type VARCHAR(30) DEFAULT 'receipt',
  biz_id INT UNSIGNED DEFAULT NULL,
  callback_payload LONGTEXT,
  verify_status TINYINT NOT NULL DEFAULT 0,
  callback_time DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_class_term (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_id INT UNSIGNED NOT NULL,
  term_name VARCHAR(100) NOT NULL,
  start_date DATE DEFAULT NULL,
  end_date DATE DEFAULT NULL,
  headteacher_user_id INT UNSIGNED DEFAULT NULL,
  status VARCHAR(20) DEFAULT 'planning',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_student_term_rel (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL,
  term_id INT UNSIGNED NOT NULL,
  joined_at DATETIME NOT NULL,
  status VARCHAR(20) DEFAULT 'learning',
  PRIMARY KEY (id),
  UNIQUE KEY uk_student_term (student_id, term_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_shipment (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED DEFAULT NULL,
  receiver_name VARCHAR(50) NOT NULL,
  receiver_mobile VARCHAR(20) DEFAULT '',
  receiver_address VARCHAR(255) NOT NULL,
  courier_company VARCHAR(50) DEFAULT '',
  tracking_no VARCHAR(80) DEFAULT '',
  shipped_at DATETIME DEFAULT NULL,
  delivered_at DATETIME DEFAULT NULL,
  status VARCHAR(30) DEFAULT 'pending',
  remark VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_certificate_template (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  template_name VARCHAR(100) NOT NULL,
  cert_type VARCHAR(20) NOT NULL,
  template_url VARCHAR(255) DEFAULT '',
  status TINYINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_certificate_issue (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL,
  course_id INT UNSIGNED DEFAULT NULL,
  template_id INT UNSIGNED DEFAULT NULL,
  cert_no VARCHAR(80) NOT NULL,
  cert_type VARCHAR(20) NOT NULL,
  issued_by_user_id INT UNSIGNED DEFAULT NULL,
  issued_at DATETIME NOT NULL,
  cert_url VARCHAR(255) DEFAULT '',
  remark VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_cert_no (cert_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_commission_rule (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  rule_name VARCHAR(120) NOT NULL,
  role_type VARCHAR(30) NOT NULL,
  calc_base VARCHAR(20) NOT NULL DEFAULT 'order',
  commission_type VARCHAR(20) NOT NULL DEFAULT 'rate',
  rate DECIMAL(8,4) NOT NULL DEFAULT 0,
  fixed_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  priority_no INT NOT NULL DEFAULT 100,
  start_date DATE DEFAULT NULL,
  end_date DATE DEFAULT NULL,
  status TINYINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_commission_scope (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  rule_id INT UNSIGNED NOT NULL,
  scope_type VARCHAR(20) NOT NULL,
  scope_value VARCHAR(80) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_commission_calc (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  rule_id INT UNSIGNED DEFAULT NULL,
  user_id INT UNSIGNED DEFAULT NULL,
  role_type VARCHAR(30) DEFAULT '',
  base_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  commission_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  calc_time DATETIME NOT NULL,
  status VARCHAR(20) DEFAULT 'auto',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_commission_adjustment (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  calc_id INT UNSIGNED DEFAULT NULL,
  user_id INT UNSIGNED DEFAULT NULL,
  adjust_amount DECIMAL(10,2) NOT NULL,
  reason VARCHAR(255) NOT NULL,
  operator_user_id INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_payroll_period (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  period_name VARCHAR(20) NOT NULL,
  period_month VARCHAR(7) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  status VARCHAR(20) DEFAULT 'draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_period_month (period_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_payroll_slip (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  period_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  gross_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  social_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  housing_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  special_deduction DECIMAL(10,2) NOT NULL DEFAULT 0,
  net_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  status VARCHAR(20) DEFAULT 'draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_period_user (period_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_payroll_item (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slip_id INT UNSIGNED NOT NULL,
  item_type VARCHAR(30) NOT NULL,
  item_name VARCHAR(80) NOT NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  remark VARCHAR(255) DEFAULT '',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_salary_payment_log (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  period_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  paid_amount DECIMAL(10,2) NOT NULL,
  paid_at DATETIME NOT NULL,
  channel VARCHAR(30) DEFAULT '',
  voucher_no VARCHAR(80) DEFAULT '',
  remark VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oa_expense_voucher (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  expense_no VARCHAR(80) NOT NULL,
  item_name VARCHAR(120) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  dept_name VARCHAR(80) DEFAULT '',
  expense_date DATE NOT NULL,
  payer_user_id INT UNSIGNED DEFAULT NULL,
  pay_channel VARCHAR(30) DEFAULT '',
  invoice_no VARCHAR(80) DEFAULT '',
  remark VARCHAR(255) DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_expense_no (expense_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO oa_user (username, password_hash, real_name, role, gender, mobile, email, id_no, department, position, hire_date, remark, status) VALUES
('admin', '$2y$12$rVtPrImk7H.Q6rvIHF4Ql.z8/SgRl3OChjDcGMQG.aXn4Cn7RTxDO', '系统管理员', '超管', '男', '13800000000', 'admin@oa2.local', '310101198801010011', '系统管理部', '平台管理员', '2024-01-01', '系统默认管理员', 1),
('consultant01', '$2y$12$rVtPrImk7H.Q6rvIHF4Ql.z8/SgRl3OChjDcGMQG.aXn4Cn7RTxDO', '顾问A', '顾问', '女', '13800000010', 'consultant01@oa2.local', '310101199001010022', '招生咨询部', '课程顾问', '2024-03-01', '负责A校区咨询', 1),
('headteacher01', '$2y$12$rVtPrImk7H.Q6rvIHF4Ql.z8/SgRl3OChjDcGMQG.aXn4Cn7RTxDO', '班主任A', '班主任', '女', '13800000020', 'headteacher01@oa2.local', '310101199202020033', '教务部', '班主任', '2024-02-01', '负责在读学员管理', 1),
('finance01', '$2y$12$rVtPrImk7H.Q6rvIHF4Ql.z8/SgRl3OChjDcGMQG.aXn4Cn7RTxDO', '财务A', '财务', '女', '13800000021', 'finance01@oa2.local', '310101199202020034', '财务部', '财务', '2024-02-02', '负责收款数据上报与统计', 1),
('coach01', '$2y$12$rVtPrImk7H.Q6rvIHF4Ql.z8/SgRl3OChjDcGMQG.aXn4Cn7RTxDO', '教练甲', '教练', '男', '13800000030', 'coach01@oa2.local', '310101199303030044', '教学部', '教练', '2024-02-10', '负责部分学员销课跟进', 1),
('manager01', '$2y$12$rVtPrImk7H.Q6rvIHF4Ql.z8/SgRl3OChjDcGMQG.aXn4Cn7RTxDO', '招生经理', '部门经理', '男', '13800000040', 'manager01@oa2.local', '310101198704040055', '招生咨询部', '经理', '2024-01-15', '查看本部门统计', 1),
('boss01', '$2y$12$rVtPrImk7H.Q6rvIHF4Ql.z8/SgRl3OChjDcGMQG.aXn4Cn7RTxDO', '老板', '老板', '男', '13800000050', 'boss01@oa2.local', '310101198001010066', '管理层', 'CEO', '2023-01-01', '查看全局经营统计', 1)
ON DUPLICATE KEY UPDATE real_name=VALUES(real_name), role=VALUES(role), mobile=VALUES(mobile), email=VALUES(email), department=VALUES(department), position=VALUES(position), status=VALUES(status);

INSERT INTO oa_user_group (group_name, group_code, remark, status) VALUES
('超级管理员', 'super_admin', '拥有全部权限', 1),
('顾问', 'consultant_role', '仅管理未成交准学员', 1),
('班主任', 'headteacher_role', '管理所有学员及销课信息', 1),
('教练', 'coach_role', '管理自己跟进学员销课记录', 1),
('财务', 'finance_role', '负责收款数据上报和统计', 1),
('部门经理', 'manager_role', '查看本部门经营统计', 1),
('老板', 'boss_role', '查看全局经营统计', 1)
ON DUPLICATE KEY UPDATE group_name=VALUES(group_name), remark=VALUES(remark), status=VALUES(status);

INSERT INTO oa_permission (perm_name, perm_code, module_name, remark, status) VALUES
('用户管理-查看', 'user_view', '系统管理', '查看用户', 1),
('用户管理-新增修改', 'user_edit', '系统管理', '新增与修改用户', 1),
('角色管理', 'group_manage', '系统管理', '角色增删改查', 1),
('权限管理', 'perm_manage', '系统管理', '权限增删改查', 1),
('权限分配', 'rbac_assign', '系统管理', '角色与权限、用户角色分配', 1),
('菜单总览', 'menu_overview', '菜单可见性', '可见数据总览', 1),
('菜单学员', 'menu_students', '菜单可见性', '可见学员管理', 1),
('菜单课程', 'menu_courses', '菜单可见性', '可见课程管理', 1),
('菜单订单', 'menu_orders', '菜单可见性', '可见订单管理', 1),
('菜单财务', 'menu_finance', '菜单可见性', '可见财务管理', 1),
('菜单待办', 'menu_todos', '菜单可见性', '可见待办', 1),
('菜单通知', 'menu_notifications', '菜单可见性', '可见通知管理', 1),
('菜单推荐者', 'menu_referrers', '菜单可见性', '可见推荐者管理', 1),
('菜单外部联系人', 'menu_external_contacts', '菜单可见性', '可见外部联系人列表', 1),
('菜单员工管理', 'menu_users', '菜单可见性', '可见员工管理', 1),
('菜单菜单管理', 'menu_manage', '菜单可见性', '可见菜单管理', 1),
('菜单部门管理', 'menu_departments', '菜单可见性', '可见部门管理', 1),
('菜单收款单', 'menu_receipts', '菜单可见性', '可见收款单管理', 1),
('菜单交付记录', 'menu_delivery_logs', '菜单可见性', '可见交付记录', 1),
('菜单部门统计', 'menu_department_stats', '菜单可见性', '可见部门统计', 1)
ON DUPLICATE KEY UPDATE perm_name=VALUES(perm_name), module_name=VALUES(module_name), remark=VALUES(remark), status=VALUES(status);

INSERT IGNORE INTO oa_user_group_rel (user_id, group_id)
SELECT u.id, g.id FROM oa_user u, oa_user_group g WHERE u.username='admin' AND g.group_code='super_admin';
INSERT IGNORE INTO oa_user_group_rel (user_id, group_id)
SELECT u.id, g.id FROM oa_user u, oa_user_group g WHERE u.username='consultant01' AND g.group_code='consultant_role';
INSERT IGNORE INTO oa_user_group_rel (user_id, group_id)
SELECT u.id, g.id FROM oa_user u, oa_user_group g WHERE u.username='headteacher01' AND g.group_code='headteacher_role';
INSERT IGNORE INTO oa_user_group_rel (user_id, group_id)
SELECT u.id, g.id FROM oa_user u, oa_user_group g WHERE u.username='coach01' AND g.group_code='coach_role';
INSERT IGNORE INTO oa_user_group_rel (user_id, group_id)
SELECT u.id, g.id FROM oa_user u, oa_user_group g WHERE u.username='finance01' AND g.group_code='finance_role';
INSERT IGNORE INTO oa_user_group_rel (user_id, group_id)
SELECT u.id, g.id FROM oa_user u, oa_user_group g WHERE u.username='manager01' AND g.group_code='manager_role';
INSERT IGNORE INTO oa_user_group_rel (user_id, group_id)
SELECT u.id, g.id FROM oa_user u, oa_user_group g WHERE u.username='boss01' AND g.group_code='boss_role';

INSERT IGNORE INTO oa_group_permission_rel (group_id, perm_id)
SELECT g.id, p.id FROM oa_user_group g, oa_permission p WHERE g.group_code='super_admin';
INSERT IGNORE INTO oa_group_permission_rel (group_id, perm_id)
SELECT g.id, p.id FROM oa_user_group g, oa_permission p WHERE g.group_code='consultant_role' AND p.perm_code IN ('menu_overview','menu_todos','menu_students','menu_notifications');
INSERT IGNORE INTO oa_group_permission_rel (group_id, perm_id)
SELECT g.id, p.id FROM oa_user_group g, oa_permission p WHERE g.group_code='headteacher_role' AND p.perm_code IN ('menu_overview','menu_todos','menu_students','menu_orders','menu_finance','menu_notifications','menu_referrers');
INSERT IGNORE INTO oa_group_permission_rel (group_id, perm_id)
SELECT g.id, p.id FROM oa_user_group g, oa_permission p WHERE g.group_code='coach_role' AND p.perm_code IN ('menu_overview','menu_todos','menu_students','menu_orders','menu_delivery_logs','menu_notifications');
INSERT IGNORE INTO oa_group_permission_rel (group_id, perm_id)
SELECT g.id, p.id FROM oa_user_group g, oa_permission p WHERE g.group_code='manager_role' AND p.perm_code IN ('menu_overview','menu_todos','menu_department_stats');
INSERT IGNORE INTO oa_group_permission_rel (group_id, perm_id)
SELECT g.id, p.id FROM oa_user_group g, oa_permission p WHERE g.group_code='boss_role' AND p.perm_code IN ('menu_overview','menu_todos','menu_department_stats');

INSERT INTO oa_menu (parent_name, menu_name, menu_key, path, icon, sort_no, status) VALUES
('总览','数据总览','overview','/overview','🏠',1,1),
('业务管理','学员管理','students','/students','🎓',11,1),
('业务管理','课程管理','courses','/courses','📘',12,1),
('业务管理','订单管理','orders','/orders','🧾',13,1),
('业务管理','财务管理','finance','/finance','💰',14,1),
('总览','待办','todos','/todos','✅',2,1),
('业务管理','通知管理','notifications','/notifications','🔔',16,1),
('业务管理','推荐者管理','referrers','/referrers','🤝',17,1),
('业务管理','外部联系人列表','external_contacts','/external_contacts','📇',17,1),
('业务管理','收款单管理','receipts','/receipts','🧾',18,1),
('业务管理','企微收款','payment_callback_logs','/payment_callback_logs','💳',18,1),
('业务管理','交付记录','delivery_logs','/delivery_logs','📒',19,1),
('系统管理','员工管理','users','/users','👥',21,1),
('系统管理','菜单管理','menus','/menus','🧭',22,1),
('系统管理','角色管理','rbac_groups','/rbac/groups','🧩',23,1),
('系统管理','权限管理','rbac_permissions','/rbac/permissions','🔐',24,1),
('系统管理','RBAC分配','rbac_assign','/rbac/assign','🛡️',25,1),
('系统管理','部门管理','departments','/departments','🏢',26,1),
('经营分析','部门统计','department_stats','/department_stats','📊',30,1)
ON DUPLICATE KEY UPDATE menu_name=VALUES(menu_name), parent_name=VALUES(parent_name), path=VALUES(path), icon=VALUES(icon), sort_no=VALUES(sort_no), status=VALUES(status);

INSERT INTO oa_student (name, gender, birthday, phone, wechat_name, wechat, id_no, level, intention_level, follow_status, source, enrolled_courses, consultant, delivery_coach, address, remark) VALUES
('张三', '男', '2003-03-12', '13800000001', '张三同学', 'zhangsan001', '310101200303120011', 'A1', '高意向', '已报名', '抖音', JSON_ARRAY('Python全栈训练营','就业辅导课'), '顾问A', '教练甲', '上海市徐汇区XX路1号', '基础好，目标就业'),
('李四', '女', '2001-08-08', '13800000002', '李四同学', 'lisi002', '310101200108080022', 'B2', '中意向', '跟进中', '小红书', JSON_ARRAY('新媒体运营实战班'), '顾问A', '教练乙', '上海市浦东新区XX路2号', '对运营课程感兴趣'),
('王五', '男', '1999-12-20', '13800000003', '王五同学', 'wangwu003', '310101199912200033', 'A2', '高意向', '已报名', '转介绍', JSON_ARRAY('AI 应用办公提效课'), '顾问B', '教练丙', '上海市闵行区XX路3号', '希望转行AI办公');

INSERT INTO oa_course (course_name, coach_name, period_weeks, price, status) VALUES
('Python 全栈训练营', '讲师赵', 12, 12800, 1),
('新媒体运营实战班', '讲师钱', 8, 9800, 1),
('AI 应用办公提效课', '讲师孙', 4, 3999, 1);

INSERT INTO oa_referrer (name, phone, channel, commission_rate, remark, status) VALUES
('老学员张姐', '13700000001', '老带新', 5.00, '历史推荐稳定', 1),
('合作渠道A', '13700000002', '渠道合作', 8.00, '每月导流', 1);

INSERT INTO oa_order (student_id, course_id, amount, total_amount, paid_amount, pay_status, payment_stage, sales_commission_amount, seller_user_id, seller_role, seller_commission_amount, referrer_id, referrer_commission_amount) VALUES
(1, 1, 12800, 12800, 2000, 1, 'deposit', 200, 2, '顾问', 200, 1, 100),
(2, 2, 9800, 9800, 9800, 1, 'full', 784, 4, '教练', 784, 2, 784),
(3, 3, 3999, 3999, 0, 0, 'final', 0, NULL, '', 0, NULL, 0);

INSERT INTO oa_receipt (order_id, receipt_no, amount, pay_method, pay_time, verified_status, remark) VALUES
(1, 'SKD2026001', 2000, '微信支付', '2026-02-01 10:00:00', 1, '定金收款'),
(2, 'SKD2026002', 9800, '银行转账', '2026-02-02 11:00:00', 1, '全款收款');

INSERT INTO oa_delivery_log (student_id, coach_user_id, progress_stage, content, log_date) VALUES
(1, 4, '开班', '已建群并发放课前资料', '2026-02-03'),
(2, 4, '中期', '完成阶段测评并反馈学习建议', '2026-02-15');

INSERT INTO oa_finance_record (record_type, item_name, amount, record_date, remark) VALUES
('income', '学费到账-张三', 12800, '2026-02-01', '支付宝'),
('income', '学费到账-李四', 9800, '2026-02-02', '对公转账'),
('expense', '讲师课酬-赵老师', 5000, '2026-02-05', '2月课酬');

INSERT INTO oa_todo (title, priority, status, due_date) VALUES
('审批张三退费申请', '高', 'todo', '2026-02-28'),
('确认下周排课计划', '中', 'doing', '2026-02-27'),
('发布本月招生海报', '低', 'todo', '2026-03-01');

INSERT INTO oa_notification (title, content, is_read) VALUES
('系统公告', '本周六晚上进行系统维护', 0),
('审批提醒', '你有 2 条待审批单据', 0),
('课程提醒', 'Python 班级明日开课', 1);

-- Sprint123 merged sample seeds

INSERT INTO oa_material (title, material_type, category, tags, cover_url, content_url, description, owner_user_id, status) VALUES
('抖音引流学习资料包', 'pdf', '引流资料', '抖音,入门', '', 'https://example.com/materials/douyin-pack.pdf', '用于顾问引导领取', 2, 1),
('小红书学习路线图', 'article', '引流资料', '小红书,路线图', '', 'https://example.com/materials/xhs-roadmap', '用于初次咨询转化', 2, 1)
ON DUPLICATE KEY UPDATE title=VALUES(title), category=VALUES(category), tags=VALUES(tags), content_url=VALUES(content_url), description=VALUES(description), status=VALUES(status);

INSERT INTO oa_material_campaign (material_id, channel, campaign_name, consultant_user_id, landing_url, qr_code_url, status) VALUES
(1, 'douyin', '抖音2月投放-资料领取', 2, 'https://example.com/campaign/dy2026', '', 1),
(2, 'xiaohongshu', '小红书3月投放-路线图', 2, 'https://example.com/campaign/xhs2026', '', 1)
ON DUPLICATE KEY UPDATE campaign_name=VALUES(campaign_name), consultant_user_id=VALUES(consultant_user_id), landing_url=VALUES(landing_url), status=VALUES(status);

INSERT INTO oa_material_claim (campaign_id, material_id, consultant_user_id, wechat_name, avatar_url, mobile, miniapp_openid, source_channel, claim_time) VALUES
(1, 1, 2, '学习用户A', '', '13911110001', 'openid_demo_a', 'douyin', NOW()),
(2, 2, 2, '学习用户B', '', '13911110002', 'openid_demo_b', 'xiaohongshu', NOW())
ON DUPLICATE KEY UPDATE wechat_name=VALUES(wechat_name), mobile=VALUES(mobile), source_channel=VALUES(source_channel), claim_time=VALUES(claim_time);

INSERT INTO oa_class_term (course_id, term_name, start_date, end_date, headteacher_user_id, status) VALUES
(1, 'Python全栈-2026春季1期', '2026-03-10', '2026-06-10', 3, 'running')
ON DUPLICATE KEY UPDATE start_date=VALUES(start_date), end_date=VALUES(end_date), headteacher_user_id=VALUES(headteacher_user_id), status=VALUES(status);

INSERT INTO oa_student_term_rel (student_id, term_id, joined_at, status) VALUES
(1, 1, NOW(), 'learning')
ON DUPLICATE KEY UPDATE joined_at=VALUES(joined_at), status=VALUES(status);

INSERT INTO oa_shipment (student_id, order_id, receiver_name, receiver_mobile, receiver_address, courier_company, tracking_no, shipped_at, status, remark) VALUES
(1, 1, '张三', '13800000001', '上海市徐汇区XX路1号', '顺丰', 'SF1234567890', NOW(), 'shipped', '已发放实体资料')
ON DUPLICATE KEY UPDATE courier_company=VALUES(courier_company), tracking_no=VALUES(tracking_no), shipped_at=VALUES(shipped_at), status=VALUES(status), remark=VALUES(remark);

INSERT INTO oa_contract (contract_no, order_id, student_id, seller_user_id, sign_time, contract_url, status, remark) VALUES
('HT-2026-0001', 1, 1, 2, NOW(), 'https://example.com/contracts/HT-2026-0001.pdf', 'signed', '首单合同')
ON DUPLICATE KEY UPDATE sign_time=VALUES(sign_time), contract_url=VALUES(contract_url), status=VALUES(status), remark=VALUES(remark);

INSERT INTO oa_invoice_profile (student_id, company_name, tax_no, address, bank_name, bank_account, contact_name, contact_mobile) VALUES
(1, '上海示例科技有限公司', '91310000MA1K000001', '上海市浦东新区XX路88号', '中国银行上海分行', '6222000000000001', '张三', '13800000001')
ON DUPLICATE KEY UPDATE tax_no=VALUES(tax_no), address=VALUES(address), bank_name=VALUES(bank_name), bank_account=VALUES(bank_account), contact_name=VALUES(contact_name), contact_mobile=VALUES(contact_mobile);

INSERT INTO oa_invoice (order_id, receipt_id, invoice_profile_id, invoice_no, amount, invoice_type, status, issued_at, remark) VALUES
(1, 1, 1, 'FP-2026-0001', 5000.00, 'normal', 'issued', NOW(), '课程费发票')
ON DUPLICATE KEY UPDATE amount=VALUES(amount), status=VALUES(status), issued_at=VALUES(issued_at), remark=VALUES(remark);

INSERT INTO oa_payment_callback_log (channel, biz_type, biz_id, callback_payload, verify_status, callback_time) VALUES
('wechat_work', 'receipt', 1, '{"trade_no":"wx_demo_001","status":"SUCCESS"}', 1, NOW()),
('alipay_enterprise', 'receipt', 1, '{"trade_no":"ali_demo_001","status":"SUCCESS"}', 1, NOW());

INSERT INTO oa_commission_rule (rule_name, role_type, calc_base, commission_type, rate, fixed_amount, priority_no, start_date, end_date, status) VALUES
('顾问标准分成', 'consultant', 'order', 'rate', 0.0800, 0, 10, '2026-01-01', NULL, 1),
('教练续费分成', 'coach', 'order', 'rate', 0.0500, 0, 20, '2026-01-01', NULL, 1)
ON DUPLICATE KEY UPDATE rate=VALUES(rate), priority_no=VALUES(priority_no), start_date=VALUES(start_date), end_date=VALUES(end_date), status=VALUES(status);

INSERT INTO oa_commission_scope (rule_id, scope_type, scope_value) VALUES
(1, 'department', 'consulting_dept'),
(2, 'department', 'delivery_dept');

INSERT INTO oa_commission_calc (order_id, rule_id, user_id, role_type, base_amount, commission_amount, calc_time, status) VALUES
(1, 1, 2, 'consultant', 5000.00, 400.00, NOW(), 'auto');

INSERT INTO oa_commission_adjustment (calc_id, user_id, adjust_amount, reason, operator_user_id) VALUES
(1, 2, 50.00, '活动奖励加成', 1);

INSERT INTO oa_certificate_template (template_name, cert_type, template_url, status) VALUES
('线上结业证模板A', 'online', 'https://example.com/cert/template-online-a', 1),
('线下结业证模板B', 'offline', 'https://example.com/cert/template-offline-b', 1)
ON DUPLICATE KEY UPDATE template_url=VALUES(template_url), status=VALUES(status);

INSERT INTO oa_certificate_issue (student_id, course_id, template_id, cert_no, cert_type, issued_by_user_id, issued_at, cert_url, remark) VALUES
(1, 1, 1, 'CERT-2026-0001', 'online', 3, NOW(), 'https://example.com/cert/CERT-2026-0001', '首期结业证');

INSERT INTO oa_payroll_period (period_name, period_month, start_date, end_date, status) VALUES
('2026年3月薪资', '2026-03', '2026-03-01', '2026-03-31', 'confirmed')
ON DUPLICATE KEY UPDATE start_date=VALUES(start_date), end_date=VALUES(end_date), status=VALUES(status);

INSERT INTO oa_payroll_slip (period_id, user_id, gross_amount, tax_amount, social_amount, housing_amount, special_deduction, net_amount, status) VALUES
(1, 2, 12000.00, 600.00, 800.00, 600.00, 500.00, 9500.00, 'paid')
ON DUPLICATE KEY UPDATE gross_amount=VALUES(gross_amount), tax_amount=VALUES(tax_amount), social_amount=VALUES(social_amount), housing_amount=VALUES(housing_amount), special_deduction=VALUES(special_deduction), net_amount=VALUES(net_amount), status=VALUES(status);

INSERT INTO oa_payroll_item (slip_id, item_type, item_name, amount, remark) VALUES
(1, 'base', '基本工资', 8000.00, ''),
(1, 'commission', '订单提成', 1500.00, '含手工调节'),
(1, 'deduction', '个税', -600.00, '按月扣缴');

INSERT INTO oa_salary_payment_log (period_id, user_id, paid_amount, paid_at, channel, voucher_no, remark) VALUES
(1, 2, 9500.00, NOW(), 'bank_transfer', 'PAY-2026-03-0001', '3月工资发放');

INSERT INTO oa_expense_voucher (expense_no, item_name, amount, dept_name, expense_date, payer_user_id, pay_channel, invoice_no, remark) VALUES
('EXP-2026-0001', '投流成本-抖音', 3000.00, '运营部', CURDATE(), 4, 'corporate_alipay', 'INV-EXP-0001', '3月首周投流');

-- =====================================================
-- v2 优化：新增字段与数据
-- 注意：schema.sql 是全量建库脚本，用于初始化新库，无需 IF NOT EXISTS。
-- 若需在已有库上执行增量变更，请使用 db/patch_v2.sql（已做幂等处理）。
-- =====================================================

-- oa_student 增加引流追踪字段
ALTER TABLE oa_student
  ADD COLUMN campaign_id INT UNSIGNED DEFAULT NULL COMMENT '引流活动ID',
  ADD COLUMN lead_source_type VARCHAR(30) DEFAULT '' COMMENT '线索来源类型';

-- oa_order 增加订单类型
ALTER TABLE oa_order
  ADD COLUMN order_type ENUM('first','renewal','upgrade') NOT NULL DEFAULT 'first' COMMENT '订单类型';

-- oa_finance_record 增加来源追踪
ALTER TABLE oa_finance_record
  ADD COLUMN source_type VARCHAR(30) DEFAULT 'manual' COMMENT '来源类型',
  ADD COLUMN source_id INT UNSIGNED DEFAULT NULL COMMENT '来源ID',
  ADD COLUMN operator_user_id INT UNSIGNED DEFAULT NULL COMMENT '操作人ID',
  ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- 新增运营角色组
INSERT INTO oa_user_group (group_name, group_code, remark, status) VALUES
('运营', 'ops_role', '负责公域引流、资料投放和推广活动', 1)
ON DUPLICATE KEY UPDATE group_name=VALUES(group_name), remark=VALUES(remark), status=VALUES(status);

-- 新增运营测试账号
INSERT INTO oa_user (username, password_hash, real_name, role, gender, mobile, email, department, position, hire_date, remark, status) VALUES
('ops01', '$2y$12$rVtPrImk7H.Q6rvIHF4Ql.z8/SgRl3OChjDcGMQG.aXn4Cn7RTxDO', '运营小王', '运营', '女', '13800000060', 'ops01@oa2.local', '运营部', '运营专员', '2024-04-01', '负责抖音/小红书引流投放', 1)
ON DUPLICATE KEY UPDATE real_name=VALUES(real_name), role=VALUES(role), mobile=VALUES(mobile), department=VALUES(department), position=VALUES(position), status=VALUES(status);

INSERT IGNORE INTO oa_user_group_rel (user_id, group_id)
SELECT u.id, g.id FROM oa_user u, oa_user_group g WHERE u.username='ops01' AND g.group_code='ops_role';

-- 新增运营/老板/顾问扩展权限
INSERT INTO oa_permission (perm_name, perm_code, module_name, remark, status) VALUES
('菜单运营管理', 'menu_ops', '菜单可见性', '可见运营管理模块', 1),
('菜单资料库', 'menu_materials', '菜单可见性', '可见资料库', 1),
('菜单资料投放', 'menu_material_campaigns', '菜单可见性', '可见资料投放', 1),
('菜单资料领取', 'menu_material_claims', '菜单可见性', '可见资料领取', 1),
('菜单引流漏斗', 'menu_leads_funnel', '菜单可见性', '可见引流漏斗统计', 1),
('菜单老板看板', 'menu_boss_dashboard', '菜单可见性', '可见老板专用看板', 1)
ON DUPLICATE KEY UPDATE perm_name=VALUES(perm_name), module_name=VALUES(module_name), remark=VALUES(remark), status=VALUES(status);

INSERT IGNORE INTO oa_group_permission_rel (group_id, perm_id)
SELECT g.id, p.id FROM oa_user_group g, oa_permission p
WHERE g.group_code='ops_role'
AND p.perm_code IN ('menu_overview','menu_todos','menu_materials','menu_material_campaigns','menu_material_claims','menu_leads_funnel','menu_ops');

INSERT IGNORE INTO oa_group_permission_rel (group_id, perm_id)
SELECT g.id, p.id FROM oa_user_group g, oa_permission p
WHERE g.group_code='boss_role'
AND p.perm_code IN ('menu_boss_dashboard','menu_leads_funnel','menu_department_stats');

INSERT IGNORE INTO oa_group_permission_rel (group_id, perm_id)
SELECT g.id, p.id FROM oa_user_group g, oa_permission p
WHERE g.group_code='consultant_role'
AND p.perm_code IN ('menu_orders','menu_courses','menu_referrers');

-- 新增菜单
INSERT INTO oa_menu (parent_name, menu_name, menu_key, path, icon, sort_no, status) VALUES
('经营分析', '引流漏斗', 'leads_funnel', '/leads_funnel', '📈', 31, 1),
('经营分析', '老板看板', 'boss_dashboard', '/boss_dashboard', '👑', 32, 1),
('运营管理', '资料库', 'materials_ops', '/materials', '📦', 41, 1),
('运营管理', '资料投放', 'material_campaigns_ops', '/material_campaigns', '📡', 42, 1),
('运营管理', '资料领取', 'material_claims_ops', '/material_claims', '📥', 43, 1)
ON DUPLICATE KEY UPDATE menu_name=VALUES(menu_name), parent_name=VALUES(parent_name), path=VALUES(path), icon=VALUES(icon), sort_no=VALUES(sort_no), status=VALUES(status);

-- 更新分成规则
INSERT INTO oa_commission_rule (rule_name, role_type, calc_base, commission_type, rate, fixed_amount, priority_no, start_date, end_date, status) VALUES
('顾问首单分成', 'consultant', 'order', 'rate', 0.1000, 0, 10, '2026-01-01', NULL, 1),
('顾问续单分成', 'consultant_renewal', 'order', 'rate', 0.0500, 0, 11, '2026-01-01', NULL, 1),
('顾问增课分成', 'consultant_upgrade', 'order', 'rate', 0.0800, 0, 12, '2026-01-01', NULL, 1),
('教练首单分成', 'coach_first', 'order', 'rate', 0.0800, 0, 20, '2026-01-01', NULL, 1),
('教练续单分成', 'coach_renewal', 'order', 'rate', 0.0400, 0, 21, '2026-01-01', NULL, 1),
('教练增课分成', 'coach_upgrade', 'order', 'rate', 0.0600, 0, 22, '2026-01-01', NULL, 1),
('班主任分成', 'headteacher', 'order', 'rate', 0.0600, 0, 30, '2026-01-01', NULL, 1)
ON DUPLICATE KEY UPDATE rate=VALUES(rate), priority_no=VALUES(priority_no), start_date=VALUES(start_date), status=VALUES(status);
