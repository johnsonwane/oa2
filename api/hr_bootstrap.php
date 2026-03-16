<?php
require_once __DIR__ . '/profile_bootstrap.php';

function ensure_hr_schema(PDO $pdo): void
{
    // P0 稳定性整改：禁止运行时建表/补列/种子初始化。
}
