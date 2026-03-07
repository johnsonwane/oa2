<?php

function ensure_rbac_tables(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS oa_user_group (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      group_name VARCHAR(50) NOT NULL,
      group_code VARCHAR(50) NOT NULL,
      remark VARCHAR(255) DEFAULT '',
      status TINYINT NOT NULL DEFAULT 1,
      PRIMARY KEY (id),
      UNIQUE KEY uk_group_code (group_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS oa_permission (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      perm_name VARCHAR(80) NOT NULL,
      perm_code VARCHAR(80) NOT NULL,
      module_name VARCHAR(50) DEFAULT '系统管理',
      remark VARCHAR(255) DEFAULT '',
      status TINYINT NOT NULL DEFAULT 1,
      PRIMARY KEY (id),
      UNIQUE KEY uk_perm_code (perm_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS oa_user_group_rel (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      user_id INT UNSIGNED NOT NULL,
      group_id INT UNSIGNED NOT NULL,
      PRIMARY KEY (id),
      UNIQUE KEY uk_user_group (user_id, group_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS oa_group_permission_rel (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      group_id INT UNSIGNED NOT NULL,
      perm_id INT UNSIGNED NOT NULL,
      PRIMARY KEY (id),
      UNIQUE KEY uk_group_perm (group_id, perm_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("INSERT INTO oa_user_group (group_name, group_code, remark, status) VALUES
      ('超级管理员', 'super_admin', '拥有全部权限', 1),
      ('顾问', 'consultant_role', '仅管理未成交准学员', 1),
      ('班主任', 'headteacher_role', '管理所有学员及销课信息', 1),
      ('教练', 'coach_role', '管理自己跟进学员销课记录', 1),
      ('财务', 'finance_role', '负责收款数据上报和统计', 1),
      ('部门经理', 'manager_role', '查看本部门经营统计', 1),
      ('老板', 'boss_role', '查看全局经营统计', 1)
      ON DUPLICATE KEY UPDATE group_name=VALUES(group_name), remark=VALUES(remark), status=VALUES(status)");

    $pdo->exec("INSERT INTO oa_permission (perm_name, perm_code, module_name, remark, status) VALUES
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
      ('菜单外部联系人本地版', 'menu_external_contacts_local', '菜单可见性', '可见企微外部联系人本地版', 1),
      ('菜单添加人账号备注映射', 'menu_external_contacts_local_notes', '菜单可见性', '可见添加人账号备注映射', 1),
      ('菜单员工管理', 'menu_users', '菜单可见性', '可见员工管理', 1),
      ('菜单菜单管理', 'menu_manage', '菜单可见性', '可见菜单管理', 1),
      ('菜单部门管理', 'menu_departments', '菜单可见性', '可见部门管理', 1),
      ('菜单收款单', 'menu_receipts', '菜单可见性', '可见收款单管理', 1),
      ('菜单交付记录', 'menu_delivery_logs', '菜单可见性', '可见交付记录', 1),
      ('菜单部门统计', 'menu_department_stats', '菜单可见性', '可见部门统计', 1)
      ON DUPLICATE KEY UPDATE perm_name=VALUES(perm_name), module_name=VALUES(module_name), remark=VALUES(remark), status=VALUES(status)");
}
