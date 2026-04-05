<?php
/**
 * 用户菜单 API
 *
 * 优先级：超管/老板 → 全部菜单
 * 次之：查 oa_role_menu_rel（角色直接绑定菜单，管理员可视化配置）
 * 兜底：查 oa_group_permission_rel + menuPermMap（老逻辑，向后兼容）
 */
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

// 老版本 menuPermMap（向后兼容，当角色没有在 oa_role_menu_rel 中配置时使用）
function get_legacy_menu_perm_map(): array
{
    return [
        'overview'                      => ['menu_overview'],
        'students'                      => ['menu_students'],
        'courses'                       => ['menu_courses'],
        'orders'                        => ['menu_orders'],
        'finance'                       => ['menu_finance'],
        'todos'                         => ['menu_todos'],
        'notifications'                 => ['menu_notifications'],
        'referrers'                     => ['menu_referrers'],
        'external_contacts'             => ['menu_external_contacts', 'menu_referrers', 'menu_manage'],
        'external_contacts_local'       => ['menu_external_contacts_local', 'menu_external_contacts', 'menu_referrers', 'menu_manage'],
        'external_contacts_local_notes' => ['menu_external_contacts_local_notes', 'menu_external_contacts_local', 'menu_manage'],
        'users'                         => ['user_view', 'user_edit', 'menu_users'],
        'menus'                         => ['menu_manage'],
        'rbac_groups'                   => ['group_manage'],
        'rbac_permissions'              => ['perm_manage'],
        'rbac_assign'                   => ['rbac_assign'],
        'departments'                   => ['group_manage', 'menu_departments', 'menu_manage'],
        'receipts'                      => ['menu_receipts', 'menu_finance'],
        'refund_requests'               => ['menu_receipts', 'menu_finance'],
        'delivery_logs'                 => ['menu_delivery_logs'],
        'department_stats'              => ['menu_department_stats'],
        'boss_dashboard'                => ['menu_overview', 'menu_manage'],
        'leads_funnel'                  => ['menu_overview', 'menu_manage'],
        'materials'                     => ['menu_overview', 'menu_todos', 'menu_manage'],
        'material_campaigns'            => ['menu_overview', 'menu_todos', 'menu_manage'],
        'material_claims'               => ['menu_overview', 'menu_todos', 'menu_manage'],
        'contracts'                     => ['menu_overview', 'menu_todos', 'menu_manage'],
        'invoice_profiles'              => ['menu_overview', 'menu_todos', 'menu_manage'],
        'invoices'                      => ['menu_overview', 'menu_todos', 'menu_manage'],
        'class_terms'                   => ['menu_overview', 'menu_todos', 'menu_manage'],
        'student_terms'                 => ['menu_overview', 'menu_todos', 'menu_manage'],
        'shipments'                     => ['menu_overview', 'menu_todos', 'menu_manage'],
        'certificate_templates'         => ['menu_overview', 'menu_todos', 'menu_manage'],
        'certificate_issues'            => ['menu_overview', 'menu_todos', 'menu_manage'],
        'commission_scopes'             => ['menu_overview', 'menu_todos', 'menu_manage'],
        'commission_rules'              => ['menu_overview', 'menu_todos', 'menu_manage'],
        'commission_calcs'              => ['menu_overview', 'menu_todos', 'menu_manage'],
        'commission_adjustments'        => ['menu_overview', 'menu_todos', 'menu_manage'],
        'payroll_periods'               => ['menu_overview', 'menu_todos', 'menu_manage'],
        'payroll_slips'                 => ['menu_overview', 'menu_todos', 'menu_manage'],
        'payment_callback_logs'         => ['menu_finance', 'menu_receipts', 'menu_manage'],
    ];
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
    $roleSelect  = isset($userColumns['role']) ? 'role' : "'' AS role";
    $userStmt    = $pdo->prepare("SELECT id, {$roleSelect} FROM oa_user WHERE id=? LIMIT 1");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
    if (!$user) {
        json_response(404, '用户不存在', null, 404);
    }

    $allMenus = $pdo->query(
        'SELECT id, menu_name, menu_key, path, icon, sort_no, status, parent_name FROM oa_menu WHERE status=1 ORDER BY sort_no ASC, id ASC'
    )->fetchAll();
    $allMenus = array_map('normalize_menu_row', $allMenus);

    // ── 1. 超管 / 老板 → 直接返回全部菜单 ──────────────────────────
    $role = trim((string)($user['role'] ?? ''));
    if ($role === '超管' || $role === '老板' || stripos($role, 'admin') !== false) {
        json_response(0, 'ok', $allMenus);
    }

    // ── 2. 获取该用户所属的角色组 ID 列表 ─────────────────────────
    $groupStmt = $pdo->prepare(
        'SELECT group_id FROM oa_user_group_rel WHERE user_id = ?'
    );
    $groupStmt->execute([$userId]);
    $groupIds = array_column($groupStmt->fetchAll(PDO::FETCH_ASSOC), 'group_id');

    $visible = [];

    if (!empty($groupIds)) {
        // ── 3. 优先：检查是否存在「角色菜单直接绑定」配置 ─────────────
        // 检查 oa_role_menu_rel 表是否存在
        $tableExists = false;
        try {
            $pdo->query("SELECT 1 FROM oa_role_menu_rel LIMIT 1");
            $tableExists = true;
        } catch (Throwable $_) {
            // 表不存在，降级到老逻辑
        }

        if ($tableExists) {
            $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
            $relStmt = $pdo->prepare(
                "SELECT DISTINCT menu_key FROM oa_role_menu_rel WHERE group_id IN ({$placeholders})"
            );
            $relStmt->execute($groupIds);
            $allowedKeys = array_fill_keys(
                array_column($relStmt->fetchAll(PDO::FETCH_ASSOC), 'menu_key'),
                true
            );

            if (!empty($allowedKeys)) {
                // 角色有直接菜单绑定，使用此配置
                foreach ($allMenus as $menu) {
                    if (isset($allowedKeys[(string)$menu['menu_key']])) {
                        $visible[] = $menu;
                    }
                }
                // 补充兜底：确保 todos 始终可见
                $hasTodos = (bool)array_filter($visible, static fn($m) => (string)($m['menu_key'] ?? '') === 'todos');
                if (!$hasTodos) {
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
                    return $sa !== $sb ? $sa <=> $sb : (int)($a['id'] ?? 0) <=> (int)($b['id'] ?? 0);
                });
                json_response(0, 'ok', $visible);
            }
        }

        // ── 4. 兜底：老版本权限代码逻辑 ──────────────────────────────
        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
        $permStmt = $pdo->prepare(
            "SELECT DISTINCT p.perm_code
             FROM oa_group_permission_rel gpr
             JOIN oa_permission p ON p.id = gpr.perm_id
             WHERE gpr.group_id IN ({$placeholders}) AND p.status = 1"
        );
        $permStmt->execute($groupIds);
        $permCodes = array_column($permStmt->fetchAll(PDO::FETCH_ASSOC), 'perm_code');
        $permSet   = array_fill_keys($permCodes, true);

        $menuPermMap = get_legacy_menu_perm_map();

        foreach ($allMenus as $menu) {
            $key  = (string)$menu['menu_key'];
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
    }

    // ── 5. 待办兜底（确保至少有一个菜单可见） ────────────────────────
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
        return $sa !== $sb ? $sa <=> $sb : (int)($a['id'] ?? 0) <=> (int)($b['id'] ?? 0);
    });

    json_response(0, 'ok', $visible);

} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
