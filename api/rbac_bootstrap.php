<?php

function ensure_rbac_tables(PDO $pdo): void
{
    // P0 稳定性整改：禁止运行时建表与初始化数据。
}
