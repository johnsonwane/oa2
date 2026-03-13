<?php

if (!function_exists('ensure_table_columns')) {
    function ensure_table_columns(PDO $pdo, string $table, array $columns): void
    {
        // P0 稳定性整改：禁止在请求链路执行 DDL。
        // 表结构变更应通过发布期迁移脚本完成。
    }
}

function ensure_student_user_profile_columns(PDO $pdo): void
{
    // P0 稳定性整改：禁止运行时补列。
}
