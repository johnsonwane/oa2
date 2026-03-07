<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/business_bootstrap.php';

function dashboard_get_user_columns(PDO $pdo): array
{
    $columns = [];
    $stmt = $pdo->query('SHOW COLUMNS FROM `oa_user`');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $name = (string)($row['Field'] ?? '');
        if ($name !== '') {
            $columns[$name] = true;
        }
    }
    return $columns;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(405, '仅支持 GET', null, 405);
}

try {
    $pdo = get_db_connection();
    ensure_business_workflow_schema($pdo);

    $userId = (int)($_GET['user_id'] ?? 0);
    $extraWhere = '';
    $params = [];

    if ($userId > 0) {
        $userColumns = dashboard_get_user_columns($pdo);
        $roleSelect = isset($userColumns['role']) ? 'role' : "'' AS role";
        $realNameSelect = isset($userColumns['real_name']) ? 'real_name' : "'' AS real_name";
        $departmentSelect = isset($userColumns['department']) ? 'department' : "'' AS department";

        $uStmt = $pdo->prepare("SELECT {$roleSelect}, {$realNameSelect}, {$departmentSelect} FROM oa_user WHERE id=? LIMIT 1");
        $uStmt->execute([$userId]);
        $u = $uStmt->fetch();
        if ($u) {
            $role = trim((string)$u['role']);
            $realName = trim((string)$u['real_name']);
            if ($role === '顾问') {
                $extraWhere = " WHERE (follow_status <> '已报名' OR follow_status='' OR follow_status IS NULL)";
            } elseif ($role === '教练') {
                $extraWhere = ' WHERE delivery_coach = :coach_name';
                $params[':coach_name'] = $realName;
            }
        }
    }

    $studentStmt = $pdo->prepare('SELECT COUNT(*) FROM oa_student' . $extraWhere);
    $studentStmt->execute($params);
    $students = (int)$studentStmt->fetchColumn();

    $summary = [
        'students' => $students,
        'leads' => (int)$pdo->query('SELECT COUNT(*) FROM oa_student WHERE is_student=0')->fetchColumn(),
        'courses' => (int)$pdo->query('SELECT COUNT(*) FROM oa_course')->fetchColumn(),
        'orders' => (int)$pdo->query('SELECT COUNT(*) FROM oa_order')->fetchColumn(),
        'receipts' => (int)$pdo->query('SELECT COUNT(*) FROM oa_receipt')->fetchColumn(),
        'todos_open' => (int)$pdo->query("SELECT COUNT(*) FROM oa_todo WHERE status <> 'done'")->fetchColumn(),
        'notifications_unread' => (int)$pdo->query('SELECT COUNT(*) FROM oa_notification WHERE is_read = 0')->fetchColumn(),
    ];
    json_response(0, 'ok', $summary);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
