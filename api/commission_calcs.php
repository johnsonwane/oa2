<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/sprint123_bootstrap.php';
require_once __DIR__ . '/sprint123_crud.php';
require_once __DIR__ . '/commission_engine.php';

try {
    $pdo = get_db_connection();
    ensure_sprint123_tables($pdo);
    $m = $_SERVER['REQUEST_METHOD'];

    // GET /commission_calcs?term_rel_id=1&user_id=2&status=pre_final&year_month=2026-04
    if ($m === 'GET') {
        auth_require_roles(['班主任', '教练', '顾问', '财务', '部门经理']);

        $select = 'SELECT cc.*,
                   o.course_id, c.course_name,
                   o.student_id, s.name student_name,
                   r.term_name,
                   u.real_name user_name, u.role user_role,
                   t.tier_name matched_tier,
                   ct.tier_name current_tier,
                   ct.rate      current_rate,
                   COALESCE(SUM(case when o2.pay_status >= 1 then o2.paid_amount else 0 end), 0) as user_monthly_amount
            FROM oa_commission_calc cc
            LEFT JOIN oa_order o ON o.id = cc.order_id
            LEFT JOIN oa_course c ON c.id = o.course_id
            LEFT JOIN oa_student s ON s.id = o.student_id
            LEFT JOIN oa_student_term_rel r ON r.id = cc.term_rel_id
            LEFT JOIN oa_user u ON u.id = cc.user_id
            LEFT JOIN oa_commission_tier t ON t.id = cc.tier_id
            LEFT JOIN oa_commission_tier ct ON ct.role_type = cc.role_type
            LEFT JOIN oa_order o2 ON o2.seller_user_id = cc.user_id AND o2.seller_role = CASE cc.role_type WHEN "consultant" THEN "顾问" WHEN "coach" THEN "教练" ELSE "顾问" END AND o2.pay_status >= 1 AND DATE_FORMAT(o2.payment_time, "%Y-%m") = DATE_FORMAT(o.payment_time, "%Y-%m")
            WHERE cc.id > 0';

        $params = [];
        $arg = function(string $field, string $cond, string $sql, array &$params) use ($_GET) {
            if (isset($_GET[$field]) && trim($_GET[$field]) !== '') {
                $params[$cond] = trim($_GET[$field]);
                $sql .= " AND {$cond}";
            }
            return $sql;
        };

        $sql = $arg('id',            'cc.id = :id',           $select, $params);
        $sql = $arg('term_rel_id',   'cc.term_rel_id = :term_rel_id',   $sql, $params);
        $sql = $arg('user_id',       'cc.user_id = :user_id', $sql, $params);
        $sql = $arg('order_id',      'cc.order_id = :order_id', $sql, $params);
        $sql = $arg('calc_status',   'cc.calc_status = :calc_status', $sql, $params);
        $sql = $arg('pay_status',    'cc.pay_status = :pay_status', $sql, $params);
        $sql = $arg('role_type',     'cc.role_type = :role_type', $sql, $params);
        $sql = $arg('user_role',     'u.role = :user_role',   $sql, $params);

        // 按年月筛选（基于付款时间）
        if (!empty($_GET['year_month'])) {
            $ym = trim($_GET['year_month']);
            $params[':ym_start'] = $ym . '-01';
            $params[':ym_end']   = $ym . '-31';
            $sql .= " AND DATE_FORMAT(o.payment_time, '%Y-%m') = DATE_FORMAT(:ym_start, '%Y-%m')";
        }

        $sql .= ' GROUP BY cc.id ORDER BY cc.id DESC LIMIT 200';

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $items = $stmt->fetchAll();

        // 按用户汇总
        $summary = [];
        foreach ($items as $item) {
            $uid = (int)$item['user_id'];
            if (!isset($summary[$uid])) {
                $summary[$uid] = [
                    'user_id'   => $uid,
                    'user_name' => $item['user_name'],
                    'role'      => $item['user_role'],
                    'total_base' => 0.0,
                    'total_commission' => 0.0,
                    'records' => [],
                ];
            }
            $summary[$uid]['total_base']       += (float)$item['base_amount'];
            $summary[$uid]['total_commission'] += (float)$item['commission_amount'];
            $summary[$uid]['records'][] = $item;
        }

        json_response(0, 'ok', [
            'items'   => $items,
            'summary' => array_values($summary),
        ]);
    }

    // POST /commission_calcs?action=finalize&year_month=2026-04
    // POST /commission_calcs?action=recalc&id=123
    if ($m === 'POST') {
        auth_require_roles(['财务', '部门经理']);
        $d = request_body();

        // 月底批量终算
        if (($_GET['action'] ?? '') === 'finalize') {
            $yearMonth = trim($_GET['year_month'] ?? date('Y-m'));
            if (!preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
                json_response(400, 'year_month 格式错误，示例：2026-04', null, 400);
            }
            $result = commission_monthly_finalize($pdo, $yearMonth);
            json_response(0, '终算完成', $result);
        }

        // 单条重新计算
        if (($_GET['action'] ?? '') === 'recalc') {
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) json_response(400, 'id非法', null, 400);

            $stmt = $pdo->prepare('SELECT term_rel_id FROM oa_commission_calc WHERE id=? LIMIT 1');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row || !(int)$row['term_rel_id']) {
                json_response(400, '该记录无法重新计算（缺少 term_rel_id）', null, 400);
            }
            $result = calc_commission_for_term_rel($pdo, (int)$row['term_rel_id']);
            json_response(0, $result['message'], $result);
        }

        // 手动创建
        $d = request_body();
        require_fields($d, ['order_id', 'calc_time']);
        $id = crud_insert($pdo, 'oa_commission_calc', ['order_id', 'term_rel_id', 'rule_id', 'user_id', 'role_type', 'base_amount', 'commission_amount', 'calc_time', 'calc_status', 'pay_status'], $d);
        json_response(0, 'created', ['id' => $id]);
    }

    if ($m === 'PUT') {
        auth_require_roles(['财务', '部门经理']);
        $d = request_body();
        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);

        // 允许更新 pay_status（发放操作）
        $allowed = ['calc_status', 'pay_status', 'commission_amount', 'settled_at', 'remark'];
        $updateFields = [];
        $updateValues = [];
        foreach ($allowed as $field) {
            if (isset($d[$field])) {
                $updateFields[] = $field;
                $updateValues[$field] = $d[$field];
            }
        }
        if (count($updateFields) === 0) {
            json_response(400, '没有需要更新的字段', null, 400);
        }

        $setSql = implode(', ', array_map(fn($f) => "`$f` = :$f", $updateFields));
        $updateValues['id'] = $id;
        $sql = "UPDATE oa_commission_calc SET $setSql WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateValues);
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        auth_require_roles(['财务', '部门经理']);
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        crud_delete($pdo, 'oa_commission_calc', $id);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
