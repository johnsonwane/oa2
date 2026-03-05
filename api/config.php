<?php

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '10.0.0.7',
        'port' => getenv('DB_PORT') ?: '3306',
        'dbname' => getenv('DB_NAME') ?: 'oa2',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '?(kFMNWb)5Fv+8-_uYwG',
        'charset' => 'utf8mb4',
    ],
    'wecom' => [
        'corp_id' => getenv('WECOM_CORP_ID') ?: 'ww72e258e75a2f4e35',
        'contact_secret' => getenv('WECOM_CONTACT_SECRET') ?: 'aQ1xXj_DTscWbXrbBepwTL1D7wSDjuBjqtzGpjaRLyI',
    ],
];
