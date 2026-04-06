<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['code' => 0, 'message' => 'ok', 'data' => ['test' => 'hello']]);
