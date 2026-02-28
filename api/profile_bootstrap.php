<?php

function ensure_table_columns(PDO $pdo, string $table, array $columns): void
{
    if ($table === '' || empty($columns)) {
        return;
    }

    $tableStmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $tableStmt->execute([$table]);
    if ((int)$tableStmt->fetchColumn() === 0) {
        return;
    }

    $exists = [];
    $columnStmt = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $columnStmt->execute([$table]);
    foreach ($columnStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $name = (string)($row['COLUMN_NAME'] ?? '');
        if ($name !== '') {
            $exists[$name] = true;
        }
    }

    foreach ($columns as $name => $ddl) {
        if (!isset($exists[$name])) {
            try {
                $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN {$ddl}");
            } catch (Throwable $e) {
                // 兼容生产环境只读账号或受限权限，忽略补列失败。
            }
        }
    }
}

function ensure_student_user_profile_columns(PDO $pdo): void
{
    ensure_table_columns($pdo, 'oa_student', [
        'gender' => "`gender` VARCHAR(10) DEFAULT ''",
        'birthday' => "`birthday` DATE DEFAULT NULL",
        'wechat' => "`wechat` VARCHAR(50) DEFAULT ''",
        'id_no' => "`id_no` VARCHAR(30) DEFAULT ''",
        'intention_level' => "`intention_level` VARCHAR(30) DEFAULT ''",
        'follow_status' => "`follow_status` VARCHAR(30) DEFAULT ''",
        'source' => "`source` VARCHAR(50) DEFAULT ''",
        'enrolled_courses' => "`enrolled_courses` JSON DEFAULT NULL",
        'delivery_coach' => "`delivery_coach` VARCHAR(50) DEFAULT ''",
        'guardian_name' => "`guardian_name` VARCHAR(50) DEFAULT ''",
        'guardian_phone' => "`guardian_phone` VARCHAR(20) DEFAULT ''",
        'address' => "`address` VARCHAR(255) DEFAULT ''",
        'remark' => "`remark` VARCHAR(255) DEFAULT ''",
    ]);

    ensure_table_columns($pdo, 'oa_user', [
        'gender' => "`gender` VARCHAR(10) DEFAULT ''",
        'mobile' => "`mobile` VARCHAR(20) DEFAULT ''",
        'email' => "`email` VARCHAR(100) DEFAULT ''",
        'id_no' => "`id_no` VARCHAR(30) DEFAULT ''",
        'department' => "`department` VARCHAR(50) DEFAULT ''",
        'position' => "`position` VARCHAR(50) DEFAULT ''",
        'hire_date' => "`hire_date` DATE DEFAULT NULL",
        'last_login_at' => "`last_login_at` DATETIME DEFAULT NULL",
        'remark' => "`remark` VARCHAR(255) DEFAULT ''",
    ]);
}
