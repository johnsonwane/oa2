CREATE DATABASE IF NOT EXISTS oa2 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE oa2;

CREATE TABLE IF NOT EXISTS oa_user (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  real_name VARCHAR(50) NOT NULL,
  role VARCHAR(30) NOT NULL,
  status TINYINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_username (username)
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
  phone VARCHAR(20) NOT NULL,
  level VARCHAR(30) DEFAULT '',
  consultant VARCHAR(50) DEFAULT '',
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

CREATE TABLE IF NOT EXISTS oa_order (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT UNSIGNED NOT NULL,
  course_id INT UNSIGNED NOT NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  pay_status TINYINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
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

INSERT INTO oa_user (username, password_hash, real_name, role, status) VALUES
('admin', '$2y$12$rVtPrImk7H.Q6rvIHF4Ql.z8/SgRl3OChjDcGMQG.aXn4Cn7RTxDO', '系统管理员', '超管', 1),
('consultant01', '$2y$12$rVtPrImk7H.Q6rvIHF4Ql.z8/SgRl3OChjDcGMQG.aXn4Cn7RTxDO', '顾问A', '顾问', 1),
('finance01', '$2y$12$rVtPrImk7H.Q6rvIHF4Ql.z8/SgRl3OChjDcGMQG.aXn4Cn7RTxDO', '财务A', '财务', 1)
ON DUPLICATE KEY UPDATE real_name=VALUES(real_name), role=VALUES(role), status=VALUES(status);

INSERT INTO oa_menu (parent_name, menu_name, menu_key, path, icon, sort_no, status) VALUES
('0级','首页','home','/home','🏠',1,1),
('0级','我的','my','/my','👤',2,1),
('运营','运营记录-查看','ops_log_view','/ops/log/view','📊',10,1),
('顾问','学员录入','consultant_student_create','/consultant/student/create','🧾',20,1),
('交付','线下课交付记录','delivery_offline_record','/delivery/offline-record','📌',30,1),
('财务','上报收款单','finance_income_report','/finance/income-report','💰',40,1),
('超管','用户管理','super_user_manage','/super/user','⚙️',50,1)
ON DUPLICATE KEY UPDATE menu_name=VALUES(menu_name), path=VALUES(path), icon=VALUES(icon), sort_no=VALUES(sort_no), status=VALUES(status);

INSERT INTO oa_student (name, phone, level, consultant) VALUES
('张三', '13800000001', 'A1', '顾问A'),
('李四', '13800000002', 'B2', '顾问A'),
('王五', '13800000003', 'A2', '顾问B');

INSERT INTO oa_course (course_name, coach_name, period_weeks, price, status) VALUES
('Python 全栈训练营', '讲师赵', 12, 12800, 1),
('新媒体运营实战班', '讲师钱', 8, 9800, 1),
('AI 应用办公提效课', '讲师孙', 4, 3999, 1);

INSERT INTO oa_order (student_id, course_id, amount, pay_status) VALUES
(1, 1, 12800, 1),
(2, 2, 9800, 1),
(3, 3, 3999, 0);

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
