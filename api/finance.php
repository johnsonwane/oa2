<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';

try {
    $pdo = get_db_connection();
    $m = $_SERVER['REQUEST_METHOD'];

    if ($m === 'GET') {
        auth_require_roles(['顾问', '班主任', '教练', '财务', '部门经理']);

        $where = [];
        $params = [];
        $recordType = trim((string)($_GET['record_type'] ?? ''));
        $fromDate = trim((string)($_GET['from_date'] ?? ''));
        $toDate = trim((string)($_GET['to_date'] ?? ''));

        if ($recordType !== '') {
            $where[] = 'record_type = :record_type';
            $params[':record_type'] = $recordType;
        }
        if ($fromDate !== '') {
            $where[] = 'record_date >= :from_date';
            $params[':from_date'] = normalize_date_or_empty($fromDate);
        }
        if ($toDate !== '') {
            $where[] = 'record_date <= :to_date';
            $params[':to_date'] = normalize_date_or_empty($toDate);
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        if (paged_mode($_GET)) {
            $p = parse_pagination($_GET);
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM oa_finance_record' . $whereSql);
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $stmt = $pdo->prepare('SELECT id,record_type,item_name,amount,record_date,remark FROM oa_finance_record' . $whereSql . ' ORDER BY record_date DESC,id DESC LIMIT :limit OFFSET :offset');
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', $p['page_size'], PDO::PARAM_INT);
            $stmt->bindValue(':offset', $p['offset'], PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();
        } else {
            $stmt = $pdo->prepare('SELECT id,record_type,item_name,amount,record_date,remark FROM oa_finance_record' . $whereSql . ' ORDER BY record_date DESC,id DESC');
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
        }

        $sumInStmt = $pdo->prepare("SELECT IFNULL(SUM(amount),0) FROM oa_finance_record WHERE record_type='income'");
        $sumOutStmt = $pdo->prepare("SELECT IFNULL(SUM(amount),0) FROM oa_finance_record WHERE record_type='expense'");
        $sumInStmt->execute();
        $sumOutStmt->execute();
        $sumIn = (float)$sumInStmt->fetchColumn();
        $sumOut = (float)$sumOutStmt->fetchColumn();

        $data = [
            'list' => $rows,
            'summary' => ['income' => $sumIn, 'expense' => $sumOut, 'balance' => $sumIn - $sumOut],
        ];

        if (isset($p)) {
            $data['pagination'] = ['page' => $p['page'], 'page_size' => $p['page_size'], 'total' => $total];
        }

        json_response(0, 'ok', $data);
    }

    if ($m === 'POST' || $m === 'PUT' || $m === 'DELETE') {
        auth_require_roles(['财务']);
    }

    if ($m === 'POST' || $m === 'PUT') {
        $d = request_body();
        require_fields($d, ['record_type', 'item_name', 'amount', 'record_date']);
        $recordType = trim((string)$d['record_type']);
        if (!in_array($recordType, ['income', 'expense'], true)) {
            json_response(400, 'record_type 只能是 income 或 expense', null, 400);
        }
        $amount = (float)$d['amount'];
        if ($amount < 0) {
            json_response(400, 'amount 不能为负数', null, 400);
        }
        $recordDate = normalize_date_or_empty($d['record_date']);

        if ($m === 'POST') {
            $stmt = $pdo->prepare('INSERT INTO oa_finance_record(record_type,item_name,amount,record_date,remark) VALUES(?,?,?,?,?)');
            $stmt->execute([$recordType, trim((string)$d['item_name']), $amount, $recordDate, trim((string)($d['remark'] ?? ''))]);
            json_response(0, 'created', ['id' => (int)$pdo->lastInsertId()]);
        }

        $id = (int)($d['id'] ?? 0);
        if ($id <= 0) {
            json_response(400, 'id非法', null, 400);
        }
        $stmt = $pdo->prepare('UPDATE oa_finance_record SET record_type=?,item_name=?,amount=?,record_date=?,remark=? WHERE id=?');
        $stmt->execute([$recordType, trim((string)$d['item_name']), $amount, $recordDate, trim((string)($d['remark'] ?? '')), $id]);
        json_response(0, 'updated');
    }

    if ($m === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) json_response(400, 'id非法', null, 400);
        $stmt = $pdo->prepare('DELETE FROM oa_finance_record WHERE id=?');
        $stmt->execute([$id]);
        json_response(0, 'deleted');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
