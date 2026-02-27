<?php
require_once __DIR__ . '/profile_bootstrap.php';

function ensure_business_workflow_schema(PDO $pdo): void
{
    ensure_table_columns($pdo, 'oa_student', [
        'is_student' => "`is_student` TINYINT NOT NULL DEFAULT 0 COMMENT '0线索 1学员'",
        'source_channel' => "`source_channel` VARCHAR(50) DEFAULT ''",
        'miniapp_openid' => "`miniapp_openid` VARCHAR(80) DEFAULT ''",
        'lead_registered_at' => "`lead_registered_at` DATETIME DEFAULT NULL",
        'converted_at' => "`converted_at` DATETIME DEFAULT NULL",
        'owner_consultant_user_id' => "`owner_consultant_user_id` INT UNSIGNED DEFAULT NULL",
        'headteacher_user_id' => "`headteacher_user_id` INT UNSIGNED DEFAULT NULL",
        'coach_user_id' => "`coach_user_id` INT UNSIGNED DEFAULT NULL",
        'student_stage' => "`student_stage` VARCHAR(30) NOT NULL DEFAULT 'lead' COMMENT 'lead线索/pending_payment待缴费/active在读/completed结课'",
    ]);


    ensure_table_columns($pdo, 'oa_order', [
        'seller_user_id' => "`seller_user_id` INT UNSIGNED DEFAULT NULL",
        'seller_role' => "`seller_role` VARCHAR(20) DEFAULT ''",
        'seller_commission_amount' => "`seller_commission_amount` DECIMAL(10,2) NOT NULL DEFAULT 0",
    ]);

    $pdo->exec("CREATE TABLE IF NOT EXISTS oa_delivery_log (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      student_id INT UNSIGNED NOT NULL,
      coach_user_id INT UNSIGNED DEFAULT NULL,
      progress_stage VARCHAR(30) DEFAULT '',
      content TEXT NOT NULL,
      log_date DATE NOT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS oa_receipt (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      order_id INT UNSIGNED NOT NULL,
      receipt_no VARCHAR(60) NOT NULL,
      amount DECIMAL(10,2) NOT NULL,
      pay_method VARCHAR(30) DEFAULT '',
      pay_time DATETIME DEFAULT NULL,
      verified_status TINYINT NOT NULL DEFAULT 0,
      remark VARCHAR(255) DEFAULT '',
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      UNIQUE KEY uk_receipt_no (receipt_no)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("INSERT INTO oa_menu (parent_name, menu_name, menu_key, path, icon, sort_no, status)
                SELECT '业务管理', '收款单管理', 'receipts', '/receipts', '🧾', 18, 1
                FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM oa_menu WHERE menu_key='receipts')");
    $pdo->exec("INSERT INTO oa_menu (parent_name, menu_name, menu_key, path, icon, sort_no, status)
                SELECT '业务管理', '交付记录', 'delivery_logs', '/delivery_logs', '📒', 19, 1
                FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM oa_menu WHERE menu_key='delivery_logs')");
    $pdo->exec("INSERT INTO oa_menu (parent_name, menu_name, menu_key, path, icon, sort_no, status)
                SELECT '经营分析', '部门统计', 'department_stats', '/department_stats', '📊', 30, 1
                FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM oa_menu WHERE menu_key='department_stats')");
}
