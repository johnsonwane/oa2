<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(405, '仅支持 GET', null, 405);
}

try {
    $pdo = get_db_connection();
    $dbName = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();

    $checkTable = static function (PDO $pdo, string $table): bool {
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?');
            $stmt->execute([(string)$pdo->query('SELECT DATABASE()')->fetchColumn(), $table]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            try {
                $pdo->query("SELECT 1 FROM `{$table}` LIMIT 1");
                return true;
            } catch (Throwable $e2) {
                return false;
            }
        }
    };

    $tables = [
        'oa_user' => $checkTable($pdo, 'oa_user'),
        'oa_student' => $checkTable($pdo, 'oa_student'),
        'oa_course' => $checkTable($pdo, 'oa_course'),
        'oa_order' => $checkTable($pdo, 'oa_order'),
    ];

    json_response(0, 'ok', [
        'db_connected' => true,
        'database' => $dbName,
        'tables' => $tables,
    ]);
} catch (Throwable $e) {
    json_response(500, 'health_check_failed: ' . $e->getMessage(), [
        'db_connected' => false,
    ], 500);
}
