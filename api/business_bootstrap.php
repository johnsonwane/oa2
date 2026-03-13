<?php
require_once __DIR__ . '/profile_bootstrap.php';

function ensure_business_workflow_schema(PDO $pdo): void
{
    // P0 稳定性整改：禁止在请求链路执行建表/补列/菜单初始化。
}
