<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
echo json_encode(['ok'=>true,'time'=>date('Y-m-d H:i:s')]);
