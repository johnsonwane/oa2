-- =====================================================
-- OA2 数据库补丁 v2：业务逻辑优化
-- 执行方式：mysql -u root -p oa2 < db/patch_v2.sql
-- 兼容：MySQL 5.7+ / MySQL 8.0+
-- 注意：phpMyAdmin 执行时请分段执行以下各块，或在命令行执行
-- =====================================================

USE oa2;

-- =====================================================
-- 工具宏：用 PREPARE 方式安全添加列（避免 IF NOT EXISTS 语法）
-- =====================================================
-- 1. oa_student: birthday
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_student' AND COLUMN_NAME='birthday'),
  'SELECT 1',
  'ALTER TABLE oa_student ADD COLUMN birthday DATE DEFAULT NULL COMMENT "出生日期"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. oa_student: campaign_id
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_student' AND COLUMN_NAME='campaign_id'),
  'SELECT 1',
  'ALTER TABLE oa_student ADD COLUMN campaign_id INT UNSIGNED DEFAULT NULL COMMENT "引流活动ID，关联 oa_material_campaign.id"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. oa_student: lead_source_type
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_student' AND COLUMN_NAME='lead_source_type'),
  'SELECT 1',
  'ALTER TABLE oa_student ADD COLUMN lead_source_type VARCHAR(30) DEFAULT "" COMMENT "线索来源类型：organic/campaign/referral/direct"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. oa_student: owner_consultant_user_id
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_student' AND COLUMN_NAME='owner_consultant_user_id'),
  'SELECT 1',
  'ALTER TABLE oa_student ADD COLUMN owner_consultant_user_id INT UNSIGNED DEFAULT NULL COMMENT "归属顾问用户ID"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. oa_student: headteacher_user_id
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_student' AND COLUMN_NAME='headteacher_user_id'),
  'SELECT 1',
  'ALTER TABLE oa_student ADD COLUMN headteacher_user_id INT UNSIGNED DEFAULT NULL COMMENT "归属班主任用户ID"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 6. oa_student: coach_user_id
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_student' AND COLUMN_NAME='coach_user_id'),
  'SELECT 1',
  'ALTER TABLE oa_student ADD COLUMN coach_user_id INT UNSIGNED DEFAULT NULL COMMENT "归属教练用户ID"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7. oa_student: lead_registered_at
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_student' AND COLUMN_NAME='lead_registered_at'),
  'SELECT 1',
  'ALTER TABLE oa_student ADD COLUMN lead_registered_at DATETIME DEFAULT NULL COMMENT "线索登记时间"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 8. oa_student: converted_at
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_student' AND COLUMN_NAME='converted_at'),
  'SELECT 1',
  'ALTER TABLE oa_student ADD COLUMN converted_at DATETIME DEFAULT NULL COMMENT "转化时间（付款）"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 9. oa_student: enrolled_courses
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_student' AND COLUMN_NAME='enrolled_courses'),
  'SELECT 1',
  'ALTER TABLE oa_student ADD COLUMN enrolled_courses JSON DEFAULT NULL COMMENT "已购课程JSON列表"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 10. oa_student 索引: campaign_id
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_student' AND INDEX_NAME='idx_student_campaign'),
  'SELECT 1',
  'ALTER TABLE oa_student ADD INDEX idx_student_campaign (campaign_id)'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 11. oa_order: order_type
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_order' AND COLUMN_NAME='order_type'),
  'SELECT 1',
  "ALTER TABLE oa_order ADD COLUMN order_type ENUM('first','renewal','upgrade') NOT NULL DEFAULT 'first' COMMENT '订单类型：first首单/renewal续单/upgrade增课'"
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 12. oa_order 索引: order_type
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_order' AND INDEX_NAME='idx_order_type'),
  'SELECT 1',
  'ALTER TABLE oa_order ADD INDEX idx_order_type (order_type)'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 13. oa_finance_record: source_type
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_finance_record' AND COLUMN_NAME='source_type'),
  'SELECT 1',
  'ALTER TABLE oa_finance_record ADD COLUMN source_type VARCHAR(30) DEFAULT "manual" COMMENT "来源类型：manual手动/order_payment订单收款/order_refund退款"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 14. oa_finance_record: source_id
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_finance_record' AND COLUMN_NAME='source_id'),
  'SELECT 1',
  'ALTER TABLE oa_finance_record ADD COLUMN source_id INT UNSIGNED DEFAULT NULL COMMENT "来源ID（订单ID或收款单ID）"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 15. oa_finance_record: operator_user_id
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_finance_record' AND COLUMN_NAME='operator_user_id'),
  'SELECT 1',
  'ALTER TABLE oa_finance_record ADD COLUMN operator_user_id INT UNSIGNED DEFAULT NULL COMMENT "操作人用户ID"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 16. oa_finance_record: created_at
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_finance_record' AND COLUMN_NAME='created_at'),
  'SELECT 1',
  'ALTER TABLE oa_finance_record ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT "创建时间"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 17. oa_finance_record 索引: source_type, source_id
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_finance_record' AND INDEX_NAME='idx_finance_source'),
  'SELECT 1',
  'ALTER TABLE oa_finance_record ADD INDEX idx_finance_source (source_type, source_id)'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 18. oa_commission_calc: term_rel_id
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_commission_calc' AND COLUMN_NAME='term_rel_id'),
  'SELECT 1',
  'ALTER TABLE oa_commission_calc ADD COLUMN term_rel_id INT UNSIGNED DEFAULT NULL COMMENT "学员-班期关系ID"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 19. oa_commission_calc: calc_status
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_commission_calc' AND COLUMN_NAME='calc_status'),
  'SELECT 1',
  "ALTER TABLE oa_commission_calc ADD COLUMN calc_status VARCHAR(20) DEFAULT 'pending' COMMENT 'pending待计算/calculated已算/pre_final预提/final已终算'"
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 20. oa_commission_calc: pay_status
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_commission_calc' AND COLUMN_NAME='pay_status'),
  'SELECT 1',
  "ALTER TABLE oa_commission_calc ADD COLUMN pay_status VARCHAR(20) DEFAULT 'pending' COMMENT 'pending待发/released已发/withheld暂缓'"
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 21. oa_commission_calc: tier_id
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_commission_calc' AND COLUMN_NAME='tier_id'),
  'SELECT 1',
  'ALTER TABLE oa_commission_calc ADD COLUMN tier_id INT UNSIGNED DEFAULT NULL COMMENT "命中阶梯ID"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 22. oa_commission_calc: final_amount
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_commission_calc' AND COLUMN_NAME='final_amount'),
  'SELECT 1',
  'ALTER TABLE oa_commission_calc ADD COLUMN final_amount DECIMAL(10,2) DEFAULT NULL COMMENT "终算后最终金额"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 23. oa_commission_calc: settled_at
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_commission_calc' AND COLUMN_NAME='settled_at'),
  'SELECT 1',
  'ALTER TABLE oa_commission_calc ADD COLUMN settled_at DATETIME DEFAULT NULL COMMENT "实际发放时间"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 24. oa_commission_calc 索引: term_rel_id
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_commission_calc' AND INDEX_NAME='idx_calc_term_rel'),
  'SELECT 1',
  'ALTER TABLE oa_commission_calc ADD INDEX idx_calc_term_rel (term_rel_id)'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 25. oa_commission_calc 索引: calc_status, pay_status
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_commission_calc' AND INDEX_NAME='idx_calc_status'),
  'SELECT 1',
  'ALTER TABLE oa_commission_calc ADD INDEX idx_calc_status (calc_status, pay_status)'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 26. oa_student_term_rel: commission_info
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_student_term_rel' AND COLUMN_NAME='commission_info'),
  'SELECT 1',
  'ALTER TABLE oa_student_term_rel ADD COLUMN commission_info JSON DEFAULT NULL COMMENT "分成快照JSON（终算后写入）"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 27. oa_student_term_rel: commission_calc_ids
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_student_term_rel' AND COLUMN_NAME='commission_calc_ids'),
  'SELECT 1',
  'ALTER TABLE oa_student_term_rel ADD COLUMN commission_calc_ids VARCHAR(255) DEFAULT "" COMMENT "分成记录ID列表，逗号分隔"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 28. oa_student_term_rel: commission_calc_at
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_student_term_rel' AND COLUMN_NAME='commission_calc_at'),
  'SELECT 1',
  'ALTER TABLE oa_student_term_rel ADD COLUMN commission_calc_at DATETIME DEFAULT NULL COMMENT "分成计算时间"'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 29. oa_order: commission_status
SET @sql = (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='oa_order' AND COLUMN_NAME='commission_status'),
  'SELECT 1',
  "ALTER TABLE oa_order ADD COLUMN commission_status VARCHAR(20) DEFAULT 'pending' COMMENT 'pending待触发/calculated已算'"
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- =====================================================
-- 4. 新增运营角色到 oa_user_group
-- =====================================================
INSERT INTO oa_user_group (group_name, group_code, remark, status) VALUES
('运营', 'ops_role', '负责公域引流、资料投放和推广活动', 1)
ON DUPLICATE KEY UPDATE group_name=VALUES(group_name), remark=VALUES(remark), status=VALUES(status);

-- =====================================================
-- 5. 新增运营人员测试账号
-- =====================================================
INSERT INTO oa_user (username, password_hash, real_name, role, gender, mobile, email, department, position, hire_date, remark, status) VALUES
('ops01', '$2y$12$rVtPrImk7H.Q6rvIHF4Ql.z8/SgRl3OChjDcGMQG.aXn4Cn7RTxDO', '运营小王', '运营', '女', '13800000060', 'ops01@oa2.local', '运营部', '运营专员', '2024-04-01', '负责抖音/小红书引流投放', 1)
ON DUPLICATE KEY UPDATE real_name=VALUES(real_name), role=VALUES(role), mobile=VALUES(mobile), department=VALUES(department), position=VALUES(position), status=VALUES(status);

-- 绑定运营用户到运营角色组
INSERT IGNORE INTO oa_user_group_rel (user_id, group_id)
SELECT u.id, g.id FROM oa_user u, oa_user_group g WHERE u.username='ops01' AND g.group_code='ops_role';

-- =====================================================
-- 6. 新增运营相关权限
-- =====================================================
INSERT INTO oa_permission (perm_name, perm_code, module_name, remark, status) VALUES
('菜单运营管理', 'menu_ops', '菜单可见性', '可见运营管理模块', 1),
('菜单资料库', 'menu_materials', '菜单可见性', '可见资料库', 1),
('菜单资料投放', 'menu_material_campaigns', '菜单可见性', '可见资料投放', 1),
('菜单资料领取', 'menu_material_claims', '菜单可见性', '可见资料领取', 1),
('菜单引流漏斗', 'menu_leads_funnel', '菜单可见性', '可见引流漏斗统计', 1),
('菜单老板看板', 'menu_boss_dashboard', '菜单可见性', '可见老板专用看板', 1)
ON DUPLICATE KEY UPDATE perm_name=VALUES(perm_name), module_name=VALUES(module_name), remark=VALUES(remark), status=VALUES(status);

-- 运营角色权限：资料库、引流活动、资料领取、引流漏斗
INSERT IGNORE INTO oa_group_permission_rel (group_id, perm_id)
SELECT g.id, p.id FROM oa_user_group g, oa_permission p
WHERE g.group_code='ops_role'
AND p.perm_code IN ('menu_overview','menu_todos','menu_materials','menu_material_campaigns','menu_material_claims','menu_leads_funnel','menu_ops');

-- 老板增加看板权限
INSERT IGNORE INTO oa_group_permission_rel (group_id, perm_id)
SELECT g.id, p.id FROM oa_user_group g, oa_permission p
WHERE g.group_code='boss_role'
AND p.perm_code IN ('menu_boss_dashboard','menu_leads_funnel','menu_department_stats');

-- 顾问增加订单权限（首次需要录单）
INSERT IGNORE INTO oa_group_permission_rel (group_id, perm_id)
SELECT g.id, p.id FROM oa_user_group g, oa_permission p
WHERE g.group_code='consultant_role'
AND p.perm_code IN ('menu_orders','menu_courses','menu_referrers');

-- =====================================================
-- 7. 新增菜单：引流漏斗、老板看板
-- =====================================================
INSERT INTO oa_menu (parent_name, menu_name, menu_key, path, icon, sort_no, status) VALUES
('经营分析', '引流漏斗', 'leads_funnel', '/leads_funnel', '&#x1F4C8;', 31, 1),
('经营分析', '老板看板', 'boss_dashboard', '/boss_dashboard', '&#x1F451;', 32, 1),
('运营管理', '资料库', 'materials_ops', '/materials', '&#x1F4E6;', 41, 1),
('运营管理', '资料投放', 'material_campaigns_ops', '/material_campaigns', '&#x1F4F1;', 42, 1),
('运营管理', '资料领取', 'material_claims_ops', '/material_claims', '&#x1F4E5;', 43, 1)
ON DUPLICATE KEY UPDATE menu_name=VALUES(menu_name), parent_name=VALUES(parent_name), path=VALUES(path), icon=VALUES(icon), sort_no=VALUES(sort_no), status=VALUES(status);

-- =====================================================
-- 8. 顾问/教练月业绩阶梯表（各自独立）
-- =====================================================
CREATE TABLE IF NOT EXISTS oa_commission_tier (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_type    VARCHAR(30)  NOT NULL,
  tier_name    VARCHAR(50)  NOT NULL,
  min_amount   DECIMAL(12,2) NOT NULL DEFAULT 0,
  max_amount   DECIMAL(12,2) DEFAULT NULL,
  rate         DECIMAL(8,4)  NOT NULL,
  start_date   DATE DEFAULT NULL,
  end_date     DATE DEFAULT NULL,
  status       TINYINT NOT NULL DEFAULT 1,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_tier_role (role_type),
  KEY idx_tier_range (role_type, min_amount, max_amount)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 顾问阶梯（示例数据，按实际业务调整）
INSERT INTO oa_commission_tier (role_type, tier_name, min_amount, max_amount, rate, start_date, status) VALUES
('consultant', '顾问 6万以下档',       0.00,    60000.00, 0.0350, '2026-01-01', 1),
('consultant', '顾问 6-8万档',       60000.01, 80000.00, 0.0400, '2026-01-01', 1),
('consultant', '顾问 8-10万档',      80000.01, 100000.00, 0.0450, '2026-01-01', 1),
('consultant', '顾问 10万以上档',   100000.01, NULL,      0.0500, '2026-01-01', 1)
ON DUPLICATE KEY UPDATE tier_name=VALUES(tier_name), rate=VALUES(rate), max_amount=VALUES(max_amount);

-- 教练阶梯（示例数据）
INSERT INTO oa_commission_tier (role_type, tier_name, min_amount, max_amount, rate, start_date, status) VALUES
('coach', '教练 6万以下档',      0.00,    60000.00, 0.0300, '2026-01-01', 1),
('coach', '教练 6-8万档',       60000.01, 80000.00, 0.0350, '2026-01-01', 1),
('coach', '教练 8-10万档',      80000.01, 100000.00, 0.0400, '2026-01-01', 1),
('coach', '教练 10万以上档',   100000.01, NULL,      0.0450, '2026-01-01', 1)
ON DUPLICATE KEY UPDATE tier_name=VALUES(tier_name), rate=VALUES(rate), max_amount=VALUES(max_amount);

-- =====================================================
-- 11. 扩展 oa_order（标记是否已触发过分成计算）
-- （commission_status 列已在上面 #29 处添加）

-- =====================================================
-- 12. 替换分成基础规则为兜底规则（阶梯无法命中时使用）
-- =====================================================
INSERT INTO oa_commission_rule (rule_name, role_type, calc_base, commission_type, rate, fixed_amount, priority_no, start_date, end_date, status) VALUES
-- 顾问兜底：订单类型区分（最低档）
('顾问首单兜底',    'consultant',          'order', 'rate', 0.0350, 0, 10, '2026-01-01', NULL, 1),
('顾问续单兜底',    'consultant_renewal', 'order', 'rate', 0.0350, 0, 11, '2026-01-01', NULL, 1),
('顾问增课兜底',    'consultant_upgrade', 'order', 'rate', 0.0350, 0, 12, '2026-01-01', NULL, 1),
-- 教练兜底
('教练首单兜底',    'coach',              'order', 'rate', 0.0300, 0, 20, '2026-01-01', NULL, 1),
('教练续单兜底',    'coach_renewal',      'order', 'rate', 0.0300, 0, 21, '2026-01-01', NULL, 1),
('教练增课兜底',    'coach_upgrade',      'order', 'rate', 0.0300, 0, 22, '2026-01-01', NULL, 1)
ON DUPLICATE KEY UPDATE rate=VALUES(rate), priority_no=VALUES(priority_no), start_date=VALUES(start_date), status=VALUES(status);

-- =====================================================
-- 完成提示
-- =====================================================
SELECT 'patch_v2.sql 执行完成！' AS result;
