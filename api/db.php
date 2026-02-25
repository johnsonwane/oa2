<?php

function get_db_connection(): PDO
{
    $config = require __DIR__ . '/config.php';
    $db = $config['db'] ?? [];

    $host = (string)($db['host'] ?? '127.0.0.1');
    $port = (string)($db['port'] ?? '3306');
    $dbname = (string)($db['dbname'] ?? 'oa2');
    $username = (string)($db['user'] ?? 'root');
    $password = (string)($db['pass'] ?? '');
    $charset = (string)($db['charset'] ?? 'utf8mb4');

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
