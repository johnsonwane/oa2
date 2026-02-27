<?php
require_once __DIR__ . '/profile_bootstrap.php';

function ensure_hr_schema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS oa_department (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      dept_name VARCHAR(50) NOT NULL,
      dept_code VARCHAR(50) NOT NULL,
      parent_name VARCHAR(50) DEFAULT '',
      status TINYINT NOT NULL DEFAULT 1,
      sort_no INT NOT NULL DEFAULT 99,
      remark VARCHAR(255) DEFAULT '',
      PRIMARY KEY (id),
      UNIQUE KEY uk_dept_code (dept_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    ensure_table_columns($pdo, 'oa_user', [
        'employee_no' => "`employee_no` VARCHAR(50) DEFAULT ''",
        'company_name' => "`company_name` VARCHAR(100) DEFAULT ''",
        'birthday' => "`birthday` DATE DEFAULT NULL",
        'social_city' => "`social_city` VARCHAR(50) DEFAULT ''",
        'hukou_place' => "`hukou_place` VARCHAR(100) DEFAULT ''",
        'hukou_type' => "`hukou_type` VARCHAR(20) DEFAULT ''",
        'birth_date' => "`birth_date` DATE DEFAULT NULL",
        'age' => "`age` INT DEFAULT NULL",
        'native_place' => "`native_place` VARCHAR(100) DEFAULT ''",
        'ethnicity' => "`ethnicity` VARCHAR(30) DEFAULT ''",
        'marital_status' => "`marital_status` VARCHAR(20) DEFAULT ''",
        'home_address' => "`home_address` VARCHAR(255) DEFAULT ''",
        'emergency_contact' => "`emergency_contact` VARCHAR(100) DEFAULT ''",
        'education' => "`education` VARCHAR(50) DEFAULT ''",
        'graduation_school' => "`graduation_school` VARCHAR(100) DEFAULT ''",
        'major' => "`major` VARCHAR(100) DEFAULT ''",
        'contract_years' => "`contract_years` VARCHAR(20) DEFAULT ''",
        'working_days' => "`working_days` INT DEFAULT 0",
        'contract_end_date' => "`contract_end_date` DATE DEFAULT NULL",
        'is_probation' => "`is_probation` TINYINT NOT NULL DEFAULT 1",
        'probation_salary' => "`probation_salary` DECIMAL(10,2) DEFAULT 0",
        'regular_date' => "`regular_date` DATE DEFAULT NULL",
        'regular_salary' => "`regular_salary` DECIMAL(10,2) DEFAULT 0",
        'bank_name' => "`bank_name` VARCHAR(100) DEFAULT ''",
        'bank_card_no' => "`bank_card_no` VARCHAR(50) DEFAULT ''",
        'salary_adjust_records' => "`salary_adjust_records` TEXT",
        'employment_status' => "`employment_status` VARCHAR(20) DEFAULT '在职'",
        'leave_date' => "`leave_date` DATE DEFAULT NULL",
    ]);

    $pdo->exec("INSERT INTO oa_department (dept_name, dept_code, parent_name, status, sort_no, remark) VALUES
      ('老板', 'boss_dept', '', 1, 1, '老板办公室'),
      ('管理部', 'management_dept', '', 1, 10, '综合管理'),
      ('顾问部', 'consulting_dept', '', 1, 20, '课程销售与咨询'),
      ('交付部', 'delivery_dept', '', 1, 30, '班主任/教练交付'),
      ('财务部', 'finance_dept', '', 1, 40, '收款与核算'),
      ('运营部', 'ops_dept', '', 1, 50, '增长运营'),
      ('外协', 'outsource_dept', '', 1, 60, '外部协作')
      ON DUPLICATE KEY UPDATE dept_name=VALUES(dept_name), parent_name=VALUES(parent_name), status=VALUES(status), sort_no=VALUES(sort_no), remark=VALUES(remark)");

    $pdo->exec("INSERT INTO oa_menu (parent_name, menu_name, menu_key, path, icon, sort_no, status)
                SELECT '系统管理', '部门管理', 'departments', '/departments', '🏢', 26, 1
                FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM oa_menu WHERE menu_key='departments')");
}
