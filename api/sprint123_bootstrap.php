<?php


function sprint123_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
}

function ensure_sprint123_tables(PDO $pdo): void
{
    $sqlList = [
        "CREATE TABLE IF NOT EXISTS oa_material (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS oa_material_campaign (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS oa_material_claim (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS oa_contract (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS oa_invoice_profile (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS oa_invoice (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS oa_class_term (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT,
          course_id INT UNSIGNED NOT NULL,
          term_name VARCHAR(100) NOT NULL,
          start_date DATE DEFAULT NULL,
          end_date DATE DEFAULT NULL,
          headteacher_user_id INT UNSIGNED DEFAULT NULL,
          status VARCHAR(20) DEFAULT 'planning',
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS oa_student_term_rel (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT,
          student_id INT UNSIGNED NOT NULL,
          term_id INT UNSIGNED NOT NULL,
          joined_at DATETIME NOT NULL,
          status VARCHAR(20) DEFAULT 'learning',
          PRIMARY KEY (id),
          UNIQUE KEY uk_student_term (student_id, term_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS oa_shipment (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS oa_commission_rule (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",



        "CREATE TABLE IF NOT EXISTS oa_payment_callback_log (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT,
          channel VARCHAR(30) NOT NULL,
          biz_type VARCHAR(30) DEFAULT 'receipt',
          biz_id INT UNSIGNED DEFAULT NULL,
          callback_payload LONGTEXT,
          verify_status TINYINT NOT NULL DEFAULT 0,
          callback_time DATETIME NOT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS oa_certificate_template (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT,
          template_name VARCHAR(100) NOT NULL,
          cert_type VARCHAR(20) NOT NULL,
          template_url VARCHAR(255) DEFAULT '',
          status TINYINT NOT NULL DEFAULT 1,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS oa_certificate_issue (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS oa_commission_scope (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT,
          rule_id INT UNSIGNED NOT NULL,
          scope_type VARCHAR(20) NOT NULL,
          scope_value VARCHAR(80) NOT NULL,
          PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS oa_commission_calc (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS oa_payroll_item (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT,
          slip_id INT UNSIGNED NOT NULL,
          item_type VARCHAR(30) NOT NULL,
          item_name VARCHAR(80) NOT NULL,
          amount DECIMAL(10,2) NOT NULL DEFAULT 0,
          remark VARCHAR(255) DEFAULT '',
          PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS oa_salary_payment_log (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS oa_commission_adjustment (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT,
          calc_id INT UNSIGNED DEFAULT NULL,
          user_id INT UNSIGNED DEFAULT NULL,
          adjust_amount DECIMAL(10,2) NOT NULL,
          reason VARCHAR(255) NOT NULL,
          operator_user_id INT UNSIGNED DEFAULT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS oa_payroll_period (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT,
          period_name VARCHAR(20) NOT NULL,
          period_month VARCHAR(7) NOT NULL,
          start_date DATE NOT NULL,
          end_date DATE NOT NULL,
          status VARCHAR(20) DEFAULT 'draft',
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY uk_period_month (period_month)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS oa_payroll_slip (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS oa_expense_voucher (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($sqlList as $sql) {
        try {
            $pdo->exec($sql);
        } catch (Throwable $e) {
            // 兼容受限账号，忽略建表失败，交由部署期建表。
        }
    }

    ensure_sprint123_menus($pdo);
}

function ensure_sprint123_menus(PDO $pdo): void
{
    if (!sprint123_table_exists($pdo, 'oa_menu')) {
        return;
    }

    $menus = [
        ['Sprint123', '资料库', 'materials', '/materials', '📚', 41],
        ['Sprint123', '资料投放', 'material_campaigns', '/material_campaigns', '📣', 42],
        ['Sprint123', '资料领取', 'material_claims', '/material_claims', '📝', 43],
        ['Sprint123', '合同管理', 'contracts', '/contracts', '📄', 44],
        ['Sprint123', '开票档案', 'invoice_profiles', '/invoice_profiles', '🧾', 45],
        ['Sprint123', '发票管理', 'invoices', '/invoices', '🧮', 46],
        ['Sprint123', '班期管理', 'class_terms', '/class_terms', '📆', 47],
        ['Sprint123', '学员班期', 'student_terms', '/student_terms', '👨‍🎓', 48],
        ['Sprint123', '寄送管理', 'shipments', '/shipments', '🚚', 49],
        ['Sprint123', '证书模板', 'certificate_templates', '/certificate_templates', '🏅', 50],
        ['Sprint123', '证书发放', 'certificate_issues', '/certificate_issues', '🎖️', 51],
        ['Sprint123', '分成范围', 'commission_scopes', '/commission_scopes', '🧭', 52],
        ['Sprint123', '分成规则', 'commission_rules', '/commission_rules', '📐', 53],
        ['Sprint123', '分成结算', 'commission_calcs', '/commission_calcs', '🧮', 54],
        ['Sprint123', '分成调整', 'commission_adjustments', '/commission_adjustments', '✏️', 55],
        ['Sprint123', '工资期次', 'payroll_periods', '/payroll_periods', '🗓️', 56],
        ['Sprint123', '工资单', 'payroll_slips', '/payroll_slips', '💵', 57],
    ];

    $stmt = $pdo->prepare("INSERT INTO oa_menu (parent_name, menu_name, menu_key, path, icon, sort_no, status)
        SELECT ?, ?, ?, ?, ?, ?, 1 FROM DUAL
        WHERE NOT EXISTS (SELECT 1 FROM oa_menu WHERE menu_key = ?)");

    foreach ($menus as $menu) {
        try {
            $stmt->execute([$menu[0], $menu[1], $menu[2], $menu[3], $menu[4], $menu[5], $menu[2]]);
        } catch (Throwable $e) {
            // 菜单插入失败不影响业务接口。
        }
    }
}
