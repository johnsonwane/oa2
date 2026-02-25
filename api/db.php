<?php

function get_db_connection(): PDO
{
    $host = '127.0.0.1';
    $port = '3306';
    $dbname = 'oa2';
    $username = 'root';
    $password = 'root';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
