<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/sprint123_bootstrap.php';
require_once __DIR__ . '/sprint123_crud.php';

// GET  ?table=tier|rule|scope
// POST ?table=tier  {role_type, tier_name, min_amount, max_amount, rate, start_date, end_date}
// PUT  ?table=tier  {id, ...}
// DEL  ?table=tier  {id}

try {
    $pdo = get_db_connection();
    ensure_sprint123_tables($pdo);
    $m = $_SERVER['REQUEST_METHOD'];
    $table = trim($_GET['table'] ?? 'rule');

    auth_require_roles(['财务', '部门经理']);

    // ── 分成阶梯表 ─────────────────────────────────────────
    if ($table === 'tier') {
        if ($m === 'GET') {
            $where = [];
            $params = [];
            if (!empty($_GET['role_type'])) {
                $where[] = 'role_type = :role_type';
                $params[':role_type'] = trim($_GET['role_type']);
            }
            if (!empty($_GET['status'])) {
                $where[] = 'status = :status';
                $params[':status'] = (int)$_GET['status'];
            }
            $whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';
            $sql = "SELECT * FROM oa_commission_tier $whereSql ORDER BY role_type, min_amount";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $items = $stmt->fetchAll();
            json_response(0, 'ok', $items);
        }

        if ($m === 'POST') {
            $d = request_body();
            require_fields($d, ['role_type', 'tier_name', 'rate']);
            $fields = ['role_type', 'tier_name', 'min_amount', 'max_amount', 'rate', 'start_date', 'end_date', 'status'];
            $payload = [];
            foreach ($fields as $f) {
                $payload[$f] = $d[$f] ?? ($f === 'min_amount' ? 0 : ($f === 'status' ? 1 : null));
            }
            $id = crud_insert($pdo, 'oa_commission_tier', $fields, $payload);
            json_response(0, 'created', ['id' => $id]);
        }

        if ($m === 'PUT') {
            $d = request_body();
            $id = (int)($d['id'] ?? 0);
            if ($id <= 0) json_response(400, 'id非法', null, 400);
            $fields = ['role_type', 'tier_name', 'min_amount', 'max_amount', 'rate', 'start_date', 'end_date', 'status'];
            crud_update($pdo, 'oa_commission_tier', $id, $fields, $d);
            json_response(0, 'updated');
        }

        if ($m === 'DELETE') {
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) json_response(400, 'id非法', null, 400);
            crud_delete($pdo, 'oa_commission_tier', $id);
            json_response(0, 'deleted');
        }
    }

    // ── 分成基础规则表 ─────────────────────────────────────
    if ($table === 'rule') {
        if ($m === 'GET') {
            json_response(0, 'ok', crud_list($pdo, 'oa_commission_rule'));
        }
        if ($m === 'POST') {
            $d = request_body();
            require_fields($d, ['rule_name', 'role_type']);
            $fields = ['rule_name', 'role_type', 'calc_base', 'commission_type', 'rate', 'fixed_amount', 'priority_no', 'start_date', 'end_date', 'status'];
            $id = crud_insert($pdo, 'oa_commission_rule', $fields, $d);
            json_response(0, 'created', ['id' => $id]);
        }
        if ($m === 'PUT') {
            $d = request_body();
            $id = (int)($d['id'] ?? 0);
            if ($id <= 0) json_response(400, 'id非法', null, 400);
            $fields = ['rule_name', 'role_type', 'calc_base', 'commission_type', 'rate', 'fixed_amount', 'priority_no', 'start_date', 'end_date', 'status'];
            crud_update($pdo, 'oa_commission_rule', $id, $fields, $d);
            json_response(0, 'updated');
        }
        if ($m === 'DELETE') {
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) json_response(400, 'id非法', null, 400);
            crud_delete($pdo, 'oa_commission_rule', $id);
            json_response(0, 'deleted');
        }
    }

    // ── 分成范围表 ─────────────────────────────────────────
    if ($table === 'scope') {
        if ($m === 'GET') {
            $items = crud_list($pdo, 'oa_commission_scope');
            // 补全 rule_name
            $stmt = $pdo->query('SELECT id, rule_name FROM oa_commission_rule');
            $ruleMap = [];
            foreach ($stmt->fetchAll() as $r) $ruleMap[$r['id']] = $r['rule_name'];
            foreach ($items as &$item) {
                $item['rule_name'] = $ruleMap[$item['rule_id']] ?? '';
            }
            json_response(0, 'ok', $items);
        }
        if ($m === 'POST') {
            $d = request_body();
            require_fields($d, ['rule_id', 'scope_type', 'scope_value']);
            $fields = ['rule_id', 'scope_type', 'scope_value'];
            $id = crud_insert($pdo, 'oa_commission_scope', $fields, $d);
            json_response(0, 'created', ['id' => $id]);
        }
        if ($m === 'PUT') {
            $d = request_body();
            $id = (int)($d['id'] ?? 0);
            if ($id <= 0) json_response(400, 'id非法', null, 400);
            $fields = ['rule_id', 'scope_type', 'scope_value'];
            crud_update($pdo, 'oa_commission_scope', $id, $fields, $d);
            json_response(0, 'updated');
        }
        if ($m === 'DELETE') {
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) json_response(400, 'id非法', null, 400);
            crud_delete($pdo, 'oa_commission_scope', $id);
            json_response(0, 'deleted');
        }
    }

    json_response(400, '未知 table 参数，可选值：tier / rule / scope', null, 400);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
