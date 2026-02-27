<?php

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

function ensure_order_referrer_schema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS oa_referrer (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if (!column_exists($pdo, 'oa_order', 'payment_stage')) {
        $pdo->exec("ALTER TABLE oa_order ADD COLUMN payment_stage VARCHAR(20) NOT NULL DEFAULT 'full' AFTER pay_status");
    }
    if (!column_exists($pdo, 'oa_order', 'total_amount')) {
        $pdo->exec('ALTER TABLE oa_order ADD COLUMN total_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER amount');
    }
    if (!column_exists($pdo, 'oa_order', 'paid_amount')) {
        $pdo->exec('ALTER TABLE oa_order ADD COLUMN paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER total_amount');
    }
    if (!column_exists($pdo, 'oa_order', 'sales_commission_amount')) {
        $pdo->exec('ALTER TABLE oa_order ADD COLUMN sales_commission_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER paid_amount');
    }
    if (!column_exists($pdo, 'oa_order', 'referrer_id')) {
        $pdo->exec('ALTER TABLE oa_order ADD COLUMN referrer_id INT UNSIGNED DEFAULT NULL AFTER sales_commission_amount');
    }
    if (!column_exists($pdo, 'oa_order', 'referrer_commission_amount')) {
        $pdo->exec('ALTER TABLE oa_order ADD COLUMN referrer_commission_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER referrer_id');
    }

    if (table_exists($pdo, 'oa_menu')) {
        $pdo->exec("INSERT INTO oa_menu (parent_name, menu_name, menu_key, path, icon, sort_no, status)
                    SELECT '业务管理', '推荐者管理', 'referrers', '/referrers', '🤝', 17, 1
                    FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM oa_menu WHERE menu_key='referrers')");
    }
}
