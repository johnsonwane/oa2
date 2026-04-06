<?php
/**
 * 角色菜单关系 API
 *
 * 支持管理员直接配置：某角色可以看到哪些菜单
 * 这是在原有「角色→权限代码」体系之外，更直观的菜单可见性控制层。
 *
 * GET  ?group_id=X           → 返回该角色已绑定的菜单 key 列表
 * GET  (无参数)              → 返回所有角色的菜单绑定情况（矩阵）
 * POST { group_id, menu_keys:[] }  → 覆盖保存该角色的菜单绑定
 */

require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';

try {
    $pdo = get_db_connection();

    // 确保角色菜单关系表存在
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `oa_role_menu_rel` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `group_id` INT UNSIGNED NOT NULL COMMENT '角色ID',
            `menu_key` VARCHAR(64) NOT NULL COMMENT '菜单KEY',
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_group_menu` (`group_id`, `menu_key`),
            KEY `idx_group_id` (`group_id`),
            KEY `idx_menu_key` (`menu_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色菜单关系表'
    ");

    $method = $_SERVER['REQUEST_METHOD'];

    // -----------------------------------------------
    // GET: 查询角色菜单绑定
    // -----------------------------------------------
    if ($method === 'GET') {
        $groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;

        if ($groupId > 0) {
            // 返回单个角色的菜单 key 列表
            $stmt = $pdo->prepare("SELECT menu_key FROM oa_role_menu_rel WHERE group_id = ?");
            $stmt->execute([$groupId]);
            $keys = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'menu_key');
            json_response(0, 'ok', $keys);
        } else {
            // 返回所有角色的菜单绑定（用于矩阵视图）
            // 获取所有角色
            $groups = $pdo->query("SELECT id, group_name, group_code FROM oa_user_group WHERE status=1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

            // 获取所有菜单
            $menus = $pdo->query("SELECT id, menu_key, menu_name, parent_name, sort_no FROM oa_menu WHERE status=1 ORDER BY sort_no ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

            // 获取所有绑定关系
            $rels = $pdo->query("SELECT group_id, menu_key FROM oa_role_menu_rel")->fetchAll(PDO::FETCH_ASSOC);

            // 构建绑定 map: [group_id][menu_key] = true
            $bindMap = [];
            foreach ($rels as $r) {
                $bindMap[(int)$r['group_id']][$r['menu_key']] = true;
            }

            json_response(0, 'ok', [
                'groups' => $groups,
                'menus'  => $menus,
                'binds'  => $bindMap
            ]);
        }
    }

    // -----------------------------------------------
    // POST: 覆盖保存某角色的菜单绑定
    // -----------------------------------------------
    elseif ($method === 'POST') {
        $d = request_body();
        $groupId  = (int)($d['group_id'] ?? 0);
        $menuKeys = (array)($d['menu_keys'] ?? []);

        if ($groupId <= 0) {
            json_response(400, 'group_id 非法', null, 400);
        }

        // 验证角色存在
        $groupStmt = $pdo->prepare("SELECT id FROM oa_user_group WHERE id = ?");
        $groupStmt->execute([$groupId]);
        if (!$groupStmt->fetch()) {
            json_response(404, '角色不存在', null, 404);
        }

        $pdo->beginTransaction();
        try {
            // 先删除该角色的所有绑定
            $pdo->prepare("DELETE FROM oa_role_menu_rel WHERE group_id = ?")->execute([$groupId]);

            // 重新插入
            if (!empty($menuKeys)) {
                $insert = $pdo->prepare("INSERT IGNORE INTO oa_role_menu_rel (group_id, menu_key) VALUES (?, ?)");
                foreach ($menuKeys as $key) {
                    $key = trim((string)$key);
                    if ($key !== '') {
                        $insert->execute([$groupId, $key]);
                    }
                }
            }
            $pdo->commit();
            json_response(0, 'saved', ['group_id' => $groupId, 'count' => count($menuKeys)]);
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // -----------------------------------------------
    // DELETE: 删除单条绑定（可选，按 group_id + menu_key）
    // -----------------------------------------------
    elseif ($method === 'DELETE') {
        $groupId = (int)($_GET['group_id'] ?? 0);
        $menuKey = trim((string)($_GET['menu_key'] ?? ''));

        if ($groupId <= 0 || $menuKey === '') {
            json_response(400, '参数非法', null, 400);
        }

        $pdo->prepare("DELETE FROM oa_role_menu_rel WHERE group_id=? AND menu_key=?")->execute([$groupId, $menuKey]);
        json_response(0, 'deleted');
    }

    // -----------------------------------------------
    // PATCH: 复制一个角色的菜单绑定到另一个角色
    // Body: { from_group_id, to_group_id, overwrite: true/false }
    // -----------------------------------------------
    elseif ($method === 'PATCH') {
        $d         = request_body();
        $fromId    = (int)($d['from_group_id'] ?? 0);
        $toId      = (int)($d['to_group_id']   ?? 0);
        $overwrite = (bool)($d['overwrite']     ?? true);

        if ($fromId <= 0 || $toId <= 0 || $fromId === $toId) {
            json_response(400, 'from_group_id / to_group_id 非法', null, 400);
        }

        // 验证两个角色都存在
        $checkStmt = $pdo->prepare("SELECT id FROM oa_user_group WHERE id IN (?,?)");
        $checkStmt->execute([$fromId, $toId]);
        if ($checkStmt->rowCount() < 2) {
            json_response(404, '角色不存在', null, 404);
        }

        // 获取来源角色的菜单列表
        $srcStmt = $pdo->prepare("SELECT menu_key FROM oa_role_menu_rel WHERE group_id = ?");
        $srcStmt->execute([$fromId]);
        $srcKeys = array_column($srcStmt->fetchAll(PDO::FETCH_ASSOC), 'menu_key');

        $pdo->beginTransaction();
        try {
            if ($overwrite) {
                $pdo->prepare("DELETE FROM oa_role_menu_rel WHERE group_id = ?")->execute([$toId]);
            }
            if (!empty($srcKeys)) {
                $insert = $pdo->prepare("INSERT IGNORE INTO oa_role_menu_rel (group_id, menu_key) VALUES (?, ?)");
                foreach ($srcKeys as $key) {
                    $insert->execute([$toId, $key]);
                }
            }
            $pdo->commit();
            json_response(0, 'copied', ['from' => $fromId, 'to' => $toId, 'count' => count($srcKeys)]);
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    else {
        json_response(405, 'method not allowed', null, 405);
    }

} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
