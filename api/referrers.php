<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/order_referrer_bootstrap.php';

try {
    $pdo = get_db_connection();
    ensure_order_referrer_schema($pdo);
    ensure_table_columns($pdo, 'oa_referrer', [
        'wechat_name' => "`wechat_name` VARCHAR(80) DEFAULT ''",
        'commission_type' => "`commission_type` VARCHAR(20) NOT NULL DEFAULT 'rate'",
        'fixed_amount' => "`fixed_amount` DECIMAL(10,2) NOT NULL DEFAULT 0",
        'payout_detail' => "`payout_detail` VARCHAR(255) DEFAULT ''",
    ]);
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        $keyword = trim((string)($_GET['keyword'] ?? ''));
        $status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';

        $sql = 'SELECT id,name,wechat_name,phone,channel,commission_type,commission_rate,fixed_amount,payout_detail,remark,status,created_at FROM oa_referrer';
        $where = [];
        $params = [];
        if ($keyword !== '') {
            $where[] = '(name LIKE :kw OR wechat_name LIKE :kw OR phone LIKE :kw OR channel LIKE :kw)';
            $params[':kw'] = "%{$keyword}%";
        }
        if ($status !== '') {
            $where[] = 'status = :status';
            $params[':status'] = (int)$status;
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';
        $stmt = $pdo->prepare($sql . $whereSql . ' ORDER BY id DESC');
        $stmt->execute($params);
        json_response(0, 'ok', $stmt->fetchAll());
    }

    if ($m === 'POST' || $m === 'PUT') {
        $d = request_body();
        require_fields($d, ['name', 'phone']);
        $name = trim((string)$d['name']);
        $wechat = trim((string)($d['wechat_name'] ?? ''));
        $phone = trim((string)$d['phone']);
        $channel = trim((string)($d['channel'] ?? ''));
        $type = trim((string)($d['commission_type'] ?? 'rate'));
        if (!in_array($type, ['rate', 'fixed'], true)) {
            json_response(400, 'commission_type 只能为 rate 或 fixed', null, 400);
        }
        $rate = (float)($d['commission_rate'] ?? 0);
        $fixed = (float)($d['fixed_amount'] ?? 0);
        if ($rate < 0 || $rate > 100) json_response(400, 'commission_rate 需在 0-100 之间', null, 400);
        if ($fixed < 0) json_response(400, 'fixed_amount 不能为负数', null, 400);

        if ($m === 'POST') {
            $dup = $pdo->prepare('SELECT id FROM oa_referrer WHERE phone=? LIMIT 1');
            $dup->execute([$phone]);
            if ($dup->fetchColumn()) json_response(409, '手机号已存在', null, 409);
            $stmt = $pdo->prepare('INSERT INTO oa_referrer(name,wechat_name,phone,channel,commission_type,commission_rate,fixed_amount,payout_detail,remark,status) VALUES(?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$name, $wechat, $phone, $channel, $type, $rate, $fixed, trim((string)($d['payout_detail'] ?? '')), trim((string)($d['remark'] ?? '')), (int)($d['status'] ?? 1)]);
            json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
        }

        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $dup = $pdo->prepare('SELECT id FROM oa_referrer WHERE phone=? AND id<>? LIMIT 1');
        $dup->execute([$phone, $id]);
        if ($dup->fetchColumn()) json_response(409, '手机号已存在', null, 409);
        $stmt = $pdo->prepare('UPDATE oa_referrer SET name=?,wechat_name=?,phone=?,channel=?,commission_type=?,commission_rate=?,fixed_amount=?,payout_detail=?,remark=?,status=? WHERE id=?');
        $stmt->execute([$name, $wechat, $phone, $channel, $type, $rate, $fixed, trim((string)($d['payout_detail'] ?? '')), trim((string)($d['remark'] ?? '')), (int)($d['status'] ?? 1), $id]);
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $stmt = $pdo->prepare('DELETE FROM oa_referrer WHERE id=?');
        $stmt->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
