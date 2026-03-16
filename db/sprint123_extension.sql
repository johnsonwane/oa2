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


-- Sprint123 sample seeds
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

ALTER TABLE oa_receipt ADD COLUMN channel_txn_id VARCHAR(80) DEFAULT NULL AFTER channel;
ALTER TABLE oa_receipt ADD UNIQUE KEY uk_receipt_channel_txn (channel, channel_txn_id);


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
