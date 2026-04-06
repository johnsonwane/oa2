<?php
/**
 * info.php — PHP 环境信息
 */
while (ob_get_level()) ob_end_clean();
ob_start();

header('Content-Type: text/plain; charset=utf-8');

echo "PHP Version: " . PHP_VERSION . "\n";
echo "PHP Major: " . PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION . "\n";
echo "SAPI: " . php_sapi_name() . "\n";
echo "Loaded PHP: " . PHP_BINARY . "\n";
echo "---\n";
echo "JSON: " . (function_exists('json_encode') ? 'OK' : 'MISSING') . "\n";
echo "PDO: " . (class_exists('PDO') ? 'OK' : 'MISSING') . "\n";
echo "MBstring: " . (function_exists('mb_detect_encoding') ? 'OK' : 'MISSING') . "\n";
echo "---\n";

// 测试空合并运算符
$a = null;
$b = $a ?? 'default';
echo "Null coalescing (??): OK -> $b\n";

// 测试 spaceship
$c = 1 <=> 2;
echo "Spaceship (<=>): OK -> $c\n";

// 测试箭头函数
$fn = fn($x) => $x * 2;
echo "Arrow function (fn): OK -> " . $fn(3) . "\n";

echo "---\n";
echo "All features test passed!\n";
