-- =====================================================
-- OA2 数据库补丁 v2：业务逻辑优化
-- 执行方式：mysql -u root -p oa2 < db/patch_v2.sql
-- 兼容：MySQL 5.7+ / MySQL 8.0+（不依赖 MariaDB 专属语法）
-- =====================================================

USE oa2;

-- =====================================================
-- 工具存储过程：安全 ADD COLUMN（字段不存在才加）
-- =====================================================
DROP PROCEDURE IF EXISTS _safe_add_column;
DELIMITER $$
CREATE PROCEDURE _safe_add_column(
  IN p_table  VARCHAR(64),
  IN p_column VARCHAR(64),
  IN p_ddl    TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = p_table
      AND COLUMN_NAME  = p_column
  ) THEN
    SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_ddl);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

-- 工具存储过程：安全 ADD INDEX（索引不存在才加）
DROP PROCEDURE IF EXISTS _safe_add_index;
DELIMITER $$
CREATE PROCEDURE _safe_add_index(
  IN p_table VARCHAR(64),
  IN p_index VARCHAR(64),
  IN p_ddl   TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = p_table
      AND INDEX_NAME   = p_index
  ) THEN
    SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX ', p_ddl);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

-- =====================================================
-- 1. oa_student 表：新增所有缺失字段（兼容旧库 + v2 扩展）
-- =====================================================
-- 基础字段：旧库可能缺失
CALL _safe_add_column('oa_student', 'birthday',
  'birthday DATE DEFAULT NULL COMMENT \'出生日期\'');

-- v2 扩展字段：引流追踪
CALL _safe_add_column('oa_student', 'campaign_id',
  'campaign_id INT UNSIGNED DEFAULT NULL COMMENT \'引流活动ID，关联 oa_material_campaign.id\'');

CALL _safe_add_column('oa_student', 'lead_source_type',
  'lead_source_type VARCHAR(30) DEFAULT \'\' COMMENT \'线索来源类型：organic/campaign/referral/direct\'');

-- v2 扩展字段：人员归属（顾问/班主任/教练）
CALL _safe_add_column('oa_student', 'owner_consultant_user_id',
  'owner_consultant_user_id INT UNSIGNED DEFAULT NULL COMMENT \'归属顾问用户ID\'');

CALL _safe_add_column('oa_student', 'headteacher_user_id',
  'headteacher_user_id INT UNSIGNED DEFAULT NULL COMMENT \'归属班主任用户ID\'');

CALL _safe_add_column('oa_student', 'coach_user_id',
  'coach_user_id INT UNSIGNED DEFAULT NULL COMMENT \'归属教练用户ID\'');

-- v2 扩展字段：时间节点
CALL _safe_add_column('oa_student', 'lead_registered_at',
  'lead_registered_at DATETIME DEFAULT NULL COMMENT \'线索登记时间\'');

CALL _safe_add_column('oa_student', 'converted_at',
  'converted_at DATETIME DEFAULT NULL COMMENT \'转化时间（付款）\'');

-- v2 扩展字段：已购课程
CALL _safe_add_column('oa_student', 'enrolled_courses',
  'enrolled_courses JSON DEFAULT NULL COMMENT \'已购课程JSON列表\');

CALL _safe_add_index('oa_student', 'idx_student_campaign',
  'idx_student_campaign (campaign_id)');

-- =====================================================
-- 2. oa_order 表：新增订单类型字段
-- =====================================================
CALL _safe_add_column('oa_order', 'order_type',
  'order_type ENUM(\'first\',\'renewal\',\'upgrade\') NOT NULL DEFAULT \'first\' COMMENT \'订单类型：first首单/renewal续单/upgrade增课\'');

CALL _safe_add_index('oa_order', 'idx_order_type',
  'idx_order_type (order_type)');

-- =====================================================
-- 3. oa_finance_record 表：增加来源关联字段，支持自动对账
-- =====================================================
CALL _safe_add_column('oa_finance_record', 'source_type',
  'source_type VARCHAR(30) DEFAULT \'manual\' COMMENT \'来源类型：manual手动/order_payment订单收款/order_refund退款\'');

CALL _safe_add_column('oa_finance_record', 'source_id',
  'source_id INT UNSIGNED DEFAULT NULL COMMENT \'来源ID（订单ID或收款单ID）\'');

CALL _safe_add_column('oa_finance_record', 'operator_user_id',
  'operator_user_id INT UNSIGNED DEFAULT NULL COMMENT \'操作人用户ID\'');

CALL _safe_add_column('oa_finance_record', 'created_at',
  'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT \'创建时间\'');

CALL _safe_add_index('oa_finance_record', 'idx_finance_source',
  'idx_finance_source (source_type, source_id)');

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
('经营分析', '引流漏斗', 'leads_funnel', '/leads_funnel', '📈', 31, 1),
('经营分析', '老板看板', 'boss_dashboard', '/boss_dashboard', '👑', 32, 1),
('运营管理', '资料库', 'materials_ops', '/materials', '📦', 41, 1),
('运营管理', '资料投放', 'material_campaigns_ops', '/material_campaigns', '📡', 42, 1),
('运营管理', '资料领取', 'material_claims_ops', '/material_claims', '📥', 43, 1)
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
('consultant', '顾问 6万以下档',      0.00,    60000.00, 0.0350, '2026-01-01', 1),
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
-- 9. 扩展 oa_commission_calc 字段（关联班期 + 状态机）
-- =====================================================
CALL _safe_add_column('oa_commission_calc', 'term_rel_id',
  'term_rel_id INT UNSIGNED DEFAULT NULL COMMENT \'学员-班期关系ID，关联 oa_student_term_rel.id\'');
CALL _safe_add_column('oa_commission_calc', 'calc_status',
  'calc_status VARCHAR(20) DEFAULT \'pending\' COMMENT \'pending待计算/calculated已算/pre_final预提/final已终算\'');
CALL _safe_add_column('oa_commission_calc', 'pay_status',
  'pay_status VARCHAR(20) DEFAULT \'pending\' COMMENT \'pending待发/released已发/withheld暂缓\'');
CALL _safe_add_column('oa_commission_calc', 'tier_id',
  'tier_id INT UNSIGNED DEFAULT NULL COMMENT \'命中阶梯ID\'');
CALL _safe_add_column('oa_commission_calc', 'final_amount',
  'final_amount DECIMAL(10,2) DEFAULT NULL COMMENT \'终算后最终金额（NULL=同预提金额）\'');
CALL _safe_add_column('oa_commission_calc', 'settled_at',
  'settled_at DATETIME DEFAULT NULL COMMENT \'实际发放时间\'');

CALL _safe_add_index('oa_commission_calc', 'idx_calc_term_rel',
  'idx_calc_term_rel (term_rel_id)');
CALL _safe_add_index('oa_commission_calc', 'idx_calc_status',
  'idx_calc_status (calc_status, pay_status)');

-- =====================================================
-- 10. 扩展 oa_student_term_rel（存放分成快照）
-- =====================================================
CALL _safe_add_column('oa_student_term_rel', 'commission_info',
  'commission_info JSON DEFAULT NULL COMMENT \'分成快照JSON（终算后写入）\'');
CALL _safe_add_column('oa_student_term_rel', 'commission_calc_ids',
  'commission_calc_ids VARCHAR(255) DEFAULT \'\' COMMENT \'分成记录ID列表，逗号分隔\'');
CALL _safe_add_column('oa_student_term_rel', 'commission_calc_at',
  'commission_calc_at DATETIME DEFAULT NULL COMMENT \'分成计算时间\'');

-- =====================================================
-- 11. 扩展 oa_order（标记是否已触发过分成计算）
-- =====================================================
CALL _safe_add_column('oa_order', 'commission_status',
  'commission_status VARCHAR(20) DEFAULT \'pending\' COMMENT \'pending待触发/calculated已算\'');

-- =====================================================
-- 12. 替换分成基础规则为兜底规则（阶梯无法命中时使用）
-- =====================================================
INSERT INTO oa_commission_rule (rule_name, role_type, calc_base, commission_type, rate, fixed_amount, priority_no, start_date, end_date, status) VALUES
-- 顾问兜底：订单类型区分（最低档）
('顾问首单兜底',    'consultant',         'order', 'rate', 0.0350, 0, 10, '2026-01-01', NULL, 1),
('顾问续单兜底',    'consultant_renewal', 'order', 'rate', 0.0350, 0, 11, '2026-01-01', NULL, 1),
('顾问增课兜底',    'consultant_upgrade', 'order', 'rate', 0.0350, 0, 12, '2026-01-01', NULL, 1),
-- 教练兜底
('教练首单兜底',    'coach',              'order', 'rate', 0.0300, 0, 20, '2026-01-01', NULL, 1),
('教练续单兜底',    'coach_renewal',      'order', 'rate', 0.0300, 0, 21, '2026-01-01', NULL, 1),
('教练增课兜底',    'coach_upgrade',      'order', 'rate', 0.0300, 0, 22, '2026-01-01', NULL, 1)
ON DUPLICATE KEY UPDATE rate=VALUES(rate), priority_no=VALUES(priority_no), start_date=VALUES(start_date), status=VALUES(status);

-- =====================================================
-- 清理工具存储过程
-- =====================================================
DROP PROCEDURE IF EXISTS _safe_add_column;
DROP PROCEDURE IF EXISTS _safe_add_index;

-- =====================================================
-- 完成提示
-- =====================================================
SELECT 'patch_v2.sql 执行完成！' AS result;
