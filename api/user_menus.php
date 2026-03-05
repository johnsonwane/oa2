<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/rbac_bootstrap.php';


function normalize_menu_row(array $menu): array
{
    $key = (string)($menu['menu_key'] ?? '');
    if ($key === 'users') {
        $menu['menu_name'] = '员工管理';
    }
    if ($key === 'todos') {
        $menu['menu_name'] = '待办';
        $menu['parent_name'] = '总览';
        $menu['sort_no'] = 2;
    }
    return $menu;
}

function user_menus_get_user_columns(PDO $pdo): array
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

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        json_response(405, 'method not allowed', null, 405);
    }

    $pdo = get_db_connection();
    ensure_rbac_tables($pdo);

    $userId = (int)($_GET['user_id'] ?? 0);
    if ($userId <= 0) {
        json_response(400, 'user_id 非法', null, 400);
    }

    $userColumns = user_menus_get_user_columns($pdo);
    $roleSelect = isset($userColumns['role']) ? 'role' : "'' AS role";
    $userStmt = $pdo->prepare("SELECT id, {$roleSelect} FROM oa_user WHERE id=? LIMIT 1");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
    if (!$user) {
        json_response(404, '用户不存在', null, 404);
    }

    $role = trim((string)($user['role'] ?? ''));
    if ($role === '超管' || $role === '老板' || stripos($role, 'admin') !== false) {
        $menus = $pdo->query('SELECT id, menu_name, menu_key, path, icon, sort_no, status, parent_name FROM oa_menu WHERE status=1 ORDER BY sort_no ASC, id ASC')->fetchAll();
        $menus = array_map('normalize_menu_row', $menus);
        json_response(0, 'ok', $menus);
    }

    $permStmt = $pdo->prepare('SELECT DISTINCT p.perm_code
        FROM oa_user_group_rel ugr
        JOIN oa_group_permission_rel gpr ON gpr.group_id = ugr.group_id
        JOIN oa_permission p ON p.id = gpr.perm_id
        WHERE ugr.user_id = ? AND p.status = 1');
    $permStmt->execute([$userId]);
    $permCodes = array_map(static fn($x) => (string)$x['perm_code'], $permStmt->fetchAll());

    $menuPermMap = [
        'overview' => ['menu_overview'],
        'students' => ['menu_students'],
        'courses' => ['menu_courses'],
        'orders' => ['menu_orders'],
        'finance' => ['menu_finance'],
        'todos' => ['menu_todos'],
        'notifications' => ['menu_notifications'],
        'referrers' => ['menu_referrers'],
        'external_contacts' => ['menu_external_contacts', 'menu_referrers', 'menu_manage'],
        'external_contacts_local' => ['menu_external_contacts_local', 'menu_external_contacts', 'menu_referrers', 'menu_manage'],
        'users' => ['user_view', 'user_edit', 'menu_users'],
        'menus' => ['menu_manage'],
        'rbac_groups' => ['group_manage'],
        'rbac_permissions' => ['perm_manage'],
        'rbac_assign' => ['rbac_assign'],
        'departments' => ['group_manage','menu_departments','menu_manage'],
        'receipts' => ['menu_receipts','menu_finance'],
        'delivery_logs' => ['menu_delivery_logs'],
        'department_stats' => ['menu_department_stats'],
        'materials' => ['menu_overview', 'menu_todos', 'menu_manage'],
        'material_campaigns' => ['menu_overview', 'menu_todos', 'menu_manage'],
        'material_claims' => ['menu_overview', 'menu_todos', 'menu_manage'],
        'contracts' => ['menu_overview', 'menu_todos', 'menu_manage'],
        'invoice_profiles' => ['menu_overview', 'menu_todos', 'menu_manage'],
        'invoices' => ['menu_overview', 'menu_todos', 'menu_manage'],
        'class_terms' => ['menu_overview', 'menu_todos', 'menu_manage'],
        'student_terms' => ['menu_overview', 'menu_todos', 'menu_manage'],
        'shipments' => ['menu_overview', 'menu_todos', 'menu_manage'],
        'certificate_templates' => ['menu_overview', 'menu_todos', 'menu_manage'],
        'certificate_issues' => ['menu_overview', 'menu_todos', 'menu_manage'],
        'commission_scopes' => ['menu_overview', 'menu_todos', 'menu_manage'],
        'commission_rules' => ['menu_overview', 'menu_todos', 'menu_manage'],
        'commission_calcs' => ['menu_overview', 'menu_todos', 'menu_manage'],
        'commission_adjustments' => ['menu_overview', 'menu_todos', 'menu_manage'],
        'payroll_periods' => ['menu_overview', 'menu_todos', 'menu_manage'],
        'payroll_slips' => ['menu_overview', 'menu_todos', 'menu_manage'],
    ];

    $allMenus = $pdo->query('SELECT id, menu_name, menu_key, path, icon, sort_no, status, parent_name FROM oa_menu WHERE status=1 ORDER BY sort_no ASC, id ASC')->fetchAll();
    $allMenus = array_map('normalize_menu_row', $allMenus);
    $permSet = array_fill_keys($permCodes, true);

    $visible = [];
    foreach ($allMenus as $menu) {
        $parentName = trim((string)($menu['parent_name'] ?? ''));
        if ($parentName === 'Sprint123') {
            $visible[] = $menu;
            continue;
        }

        $key = (string)$menu['menu_key'];
        $need = $menuPermMap[$key] ?? [];
        if (empty($need)) {
            continue;
        }
        foreach ($need as $perm) {
            if (isset($permSet[$perm])) {
                $visible[] = $menu;
                break;
            }
        }
    }

    // 待办作为默认入口，兜底确保菜单可见（兼容历史权限未补齐场景）。
    if (!array_filter($visible, static fn($m) => (string)($m['menu_key'] ?? '') === 'todos')) {
        foreach ($allMenus as $menu) {
            if ((string)($menu['menu_key'] ?? '') === 'todos') {
                $visible[] = $menu;
                break;
            }
        }
    }

    usort($visible, static function ($a, $b) {
        $sa = (int)($a['sort_no'] ?? 0);
        $sb = (int)($b['sort_no'] ?? 0);
        if ($sa === $sb) {
            return (int)($a['id'] ?? 0) <=> (int)($b['id'] ?? 0);
        }
        return $sa <=> $sb;
    });

    json_response(0, 'ok', $visible);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
