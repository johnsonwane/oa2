CREATE DATABASE IF NOT EXISTS oa2 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE oa2;

CREATE TABLE IF NOT EXISTS oa_user (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  real_name VARCHAR(50) NOT NULL,
  role VARCHAR(30) NOT NULL,
  gender VARCHAR(10) DEFAULT '',
  mobile VARCHAR(20) DEFAULT '',
  email VARCHAR(100) DEFAULT '',
  id_no VARCHAR(30) DEFAULT '',
  department VARCHAR(50) DEFAULT '',
  position VARCHAR(50) DEFAULT '',
  hire_date DATE DEFAULT NULL,
  last_login_at DATETIME DEFAULT NULL,
  remark VARCHAR(255) DEFAULT '',
  status TINYINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_username (username)
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
  wechat VARCHAR(50) DEFAULT '',
  id_no VARCHAR(30) DEFAULT '',
  level VARCHAR(30) DEFAULT '',
  intention_level VARCHAR(30) DEFAULT '',
  follow_status VARCHAR(30) DEFAULT '',
  source VARCHAR(50) DEFAULT '',
  enrolled_courses JSON DEFAULT NULL,
  consultant VARCHAR(50) DEFAULT '',
  delivery_coach VARCHAR(50) DEFAULT '',
  guardian_name VARCHAR(50) DEFAULT '',
  guardian_phone VARCHAR(20) DEFAULT '',
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
  phone VARCHAR(20) NOT NULL,
  channel VARCHAR(50) DEFAULT '',
  commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
  remark VARCHAR(255) DEFAULT '',
  status TINYINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_referrer_phone (phone)
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
  referrer_id INT UNSIGNED DEFAULT NULL,
  referrer_commission_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
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

INSERT INTO oa_user (username, password_hash, real_name, role, gender, mobile, email, id_no, department, position, hire_date, remark, status) VALUES
('admin', '$2y$12$rVtPrImk7H.Q6rvIHF4Ql.z8/SgRl3OChjDcGMQG.aXn4Cn7RTxDO', '系统管理员', '超管', '男', '13800000000', 'admin@oa2.local', '310101198801010011', '系统管理部', '平台管理员', '2024-01-01', '系统默认管理员', 1),
('consultant01', '$2y$12$rVtPrImk7H.Q6rvIHF4Ql.z8/SgRl3OChjDcGMQG.aXn4Cn7RTxDO', '顾问A', '顾问', '女', '13800000010', 'consultant01@oa2.local', '310101199001010022', '招生咨询部', '课程顾问', '2024-03-01', '负责A校区咨询', 1),
('finance01', '$2y$12$rVtPrImk7H.Q6rvIHF4Ql.z8/SgRl3OChjDcGMQG.aXn4Cn7RTxDO', '财务A', '财务', '女', '13800000020', 'finance01@oa2.local', '310101199202020033', '财务部', '会计', '2024-02-01', '财务结算负责人', 1)
ON DUPLICATE KEY UPDATE real_name=VALUES(real_name), role=VALUES(role), mobile=VALUES(mobile), email=VALUES(email), department=VALUES(department), position=VALUES(position), status=VALUES(status);

INSERT INTO oa_user_group (group_name, group_code, remark, status) VALUES
('超级管理员组', 'super_admin', '拥有全部权限', 1),
('顾问组', 'consultant_group', '顾问业务权限', 1),
('财务组', 'finance_group', '财务业务权限', 1)
ON DUPLICATE KEY UPDATE group_name=VALUES(group_name), remark=VALUES(remark), status=VALUES(status);

INSERT INTO oa_permission (perm_name, perm_code, module_name, remark, status) VALUES
('用户管理-查看', 'user_view', '系统管理', '查看用户', 1),
('用户管理-新增修改', 'user_edit', '系统管理', '新增与修改用户', 1),
('用户组管理', 'group_manage', '系统管理', '用户组增删改查', 1),
('权限管理', 'perm_manage', '系统管理', '权限增删改查', 1),
('权限分配', 'rbac_assign', '系统管理', '组与权限、用户组分配', 1),
('菜单总览', 'menu_overview', '菜单可见性', '可见数据总览', 1),
('菜单学员', 'menu_students', '菜单可见性', '可见学员管理', 1),
('菜单课程', 'menu_courses', '菜单可见性', '可见课程管理', 1),
('菜单订单', 'menu_orders', '菜单可见性', '可见订单管理', 1),
('菜单财务', 'menu_finance', '菜单可见性', '可见财务管理', 1),
('菜单待办', 'menu_todos', '菜单可见性', '可见待办管理', 1),
('菜单通知', 'menu_notifications', '菜单可见性', '可见通知管理', 1),
('菜单推荐者', 'menu_referrers', '菜单可见性', '可见推荐者管理', 1),
('菜单用户管理', 'menu_users', '菜单可见性', '可见用户管理', 1),
('菜单菜单管理', 'menu_manage', '菜单可见性', '可见菜单管理', 1)
ON DUPLICATE KEY UPDATE perm_name=VALUES(perm_name), module_name=VALUES(module_name), remark=VALUES(remark), status=VALUES(status);

INSERT IGNORE INTO oa_user_group_rel (user_id, group_id)
SELECT u.id, g.id FROM oa_user u, oa_user_group g WHERE u.username='admin' AND g.group_code='super_admin';
INSERT IGNORE INTO oa_user_group_rel (user_id, group_id)
SELECT u.id, g.id FROM oa_user u, oa_user_group g WHERE u.username='consultant01' AND g.group_code='consultant_group';
INSERT IGNORE INTO oa_user_group_rel (user_id, group_id)
SELECT u.id, g.id FROM oa_user u, oa_user_group g WHERE u.username='finance01' AND g.group_code='finance_group';

INSERT IGNORE INTO oa_group_permission_rel (group_id, perm_id)
SELECT g.id, p.id FROM oa_user_group g, oa_permission p WHERE g.group_code='super_admin';
INSERT IGNORE INTO oa_group_permission_rel (group_id, perm_id)
SELECT g.id, p.id FROM oa_user_group g, oa_permission p WHERE g.group_code='consultant_group' AND p.perm_code IN ('menu_overview','menu_students','menu_courses','menu_orders','menu_todos','menu_notifications','menu_referrers');
INSERT IGNORE INTO oa_group_permission_rel (group_id, perm_id)
SELECT g.id, p.id FROM oa_user_group g, oa_permission p WHERE g.group_code='finance_group' AND p.perm_code IN ('menu_overview','menu_orders','menu_finance','menu_notifications');

INSERT INTO oa_menu (parent_name, menu_name, menu_key, path, icon, sort_no, status) VALUES
('总览','数据总览','overview','/overview','🏠',1,1),
('业务管理','学员管理','students','/students','🎓',11,1),
('业务管理','课程管理','courses','/courses','📘',12,1),
('业务管理','订单管理','orders','/orders','🧾',13,1),
('业务管理','财务管理','finance','/finance','💰',14,1),
('业务管理','待办管理','todos','/todos','✅',15,1),
('业务管理','通知管理','notifications','/notifications','🔔',16,1),
('业务管理','推荐者管理','referrers','/referrers','🤝',17,1),
('系统管理','用户管理','users','/users','👥',21,1),
('系统管理','菜单管理','menus','/menus','🧭',22,1),
('系统管理','用户组管理','rbac_groups','/rbac/groups','🧩',23,1),
('系统管理','权限管理','rbac_permissions','/rbac/permissions','🔐',24,1),
('系统管理','RBAC分配','rbac_assign','/rbac/assign','🛡️',25,1)
ON DUPLICATE KEY UPDATE menu_name=VALUES(menu_name), parent_name=VALUES(parent_name), path=VALUES(path), icon=VALUES(icon), sort_no=VALUES(sort_no), status=VALUES(status);

INSERT INTO oa_student (name, gender, birthday, phone, wechat, id_no, level, intention_level, follow_status, source, enrolled_courses, consultant, delivery_coach, guardian_name, guardian_phone, address, remark) VALUES
('张三', '男', '2003-03-12', '13800000001', 'zhangsan001', '310101200303120011', 'A1', '高意向', '已报名', '抖音', JSON_ARRAY('Python全栈训练营','就业辅导课'), '顾问A', '教练甲', '张父', '13900000001', '上海市徐汇区XX路1号', '基础好，目标就业'),
('李四', '女', '2001-08-08', '13800000002', 'lisi002', '310101200108080022', 'B2', '中意向', '跟进中', '小红书', JSON_ARRAY('新媒体运营实战班'), '顾问A', '教练乙', '李母', '13900000002', '上海市浦东新区XX路2号', '对运营课程感兴趣'),
('王五', '男', '1999-12-20', '13800000003', 'wangwu003', '310101199912200033', 'A2', '高意向', '已报名', '转介绍', JSON_ARRAY('AI 应用办公提效课'), '顾问B', '教练丙', '王父', '13900000003', '上海市闵行区XX路3号', '希望转行AI办公');

INSERT INTO oa_course (course_name, coach_name, period_weeks, price, status) VALUES
('Python 全栈训练营', '讲师赵', 12, 12800, 1),
('新媒体运营实战班', '讲师钱', 8, 9800, 1),
('AI 应用办公提效课', '讲师孙', 4, 3999, 1);

INSERT INTO oa_referrer (name, phone, channel, commission_rate, remark, status) VALUES
('老学员张姐', '13700000001', '老带新', 5.00, '历史推荐稳定', 1),
('合作渠道A', '13700000002', '渠道合作', 8.00, '每月导流', 1);

INSERT INTO oa_order (student_id, course_id, amount, total_amount, paid_amount, pay_status, payment_stage, sales_commission_amount, referrer_id, referrer_commission_amount) VALUES
(1, 1, 12800, 12800, 2000, 1, 'deposit', 200, 1, 100),
(2, 2, 9800, 9800, 9800, 1, 'full', 980, 2, 784),
(3, 3, 3999, 3999, 0, 0, 'final', 0, NULL, 0);

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
