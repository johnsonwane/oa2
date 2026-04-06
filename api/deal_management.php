<?php
require_once __DIR__ . '/crm_sales_store.php';

try {
    crm_sales_handle('deal_management');
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
