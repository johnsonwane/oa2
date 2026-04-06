<?php
/**
 * 企业微信配置（敏感信息，请勿提交到代码仓库）
 * 建议将本文件加入 .gitignore
 */

$_wecom_local = file_exists(__DIR__ . '/wecom_config.php') ? require __DIR__ . '/wecom_config.php' : [];

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '10.0.0.7',
        'port' => getenv('DB_PORT') ?: '3306',
        'dbname' => getenv('DB_NAME') ?: 'oa2',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: (getenv('MYSQL_ROOT_PASSWORD') ?: '?(kFMNWb)5Fv+8-_uYwG'),
        'charset' => 'utf8mb4',
    ],
    'wecom' => [
        // 环境变量优先，其次本地配置文件
        'corp_id' => getenv('WECOM_CORP_ID') ?: ($_wecom_local['corp_id'] ?? ''),
        'contact_secret' => getenv('WECOM_CONTACT_SECRET') ?: ($_wecom_local['contact_secret'] ?? ''),
        'agent_id' => $_wecom_local['agent_id'] ?? null,
    ],
];
