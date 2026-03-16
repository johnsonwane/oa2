<?php

if (!function_exists('ensure_table_columns')) {
    function ensure_table_columns(PDO $pdo, string $table, array $columns): void
    {
        // P0 稳定性整改：禁止运行时 DDL。
    }
}

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
    // P0 稳定性整改：禁止运行时建表/补列/补种子数据。
}
