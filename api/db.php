<?php

function get_db_connection(): PDO
{
    $config = require __DIR__ . '/config.php';
    $db = $config['db'] ?? [];

    $host = (string)($db['host'] ?? '10.0.0.7');
    $port = (string)($db['port'] ?? '3306');
    $dbname = (string)($db['dbname'] ?? 'oa2');
    $username = (string)($db['user'] ?? 'root');
    $password = (string)($db['pass'] ?? '');
    $charset = (string)($db['charset'] ?? 'utf8mb4');

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

    try {
        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (strpos($msg, 'Access denied for user') !== false) {
            throw new RuntimeException(
                '数据库连接失败：账号授权被拒绝。当前连接目标=' . $host . ':' . $port .
                '，账号=' . $username . '。请优先配置环境变量 DB_USER / DB_PASS（或 MYSQL_USER / MYSQL_PASSWORD）为已授权业务账号；若使用 root，需在 MySQL 上授权该来源IP。原始错误：' . $msg,
                0,
                $e
            );
        }
        throw $e;
    }
}
