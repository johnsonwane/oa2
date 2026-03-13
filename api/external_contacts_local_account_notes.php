<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';

function ensure_external_contacts_local_note_table(PDO $pdo): void
{
    // P0 稳定性整改：禁止运行时建表。
}

try {
    $pdo = get_db_connection();
    ensure_external_contacts_local_note_table($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $rows = $pdo->query("SELECT id, follower_account, account_note, updated_at FROM oa_external_contacts_local_account_note ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
        json_response(0, 'ok', $rows);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = request_body();
        require_fields($data, ['follower_account', 'account_note']);
        $account = trim((string)$data['follower_account']);
        $note = trim((string)$data['account_note']);

        $stmt = $pdo->prepare("INSERT INTO oa_external_contacts_local_account_note (follower_account, account_note) VALUES (?, ?) ON DUPLICATE KEY UPDATE account_note=VALUES(account_note)");
        $stmt->execute([$account, $note]);
        json_response(0, '保存成功');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_response(400, 'id无效', null, 400);
        }
        $stmt = $pdo->prepare('DELETE FROM oa_external_contacts_local_account_note WHERE id=?');
        $stmt->execute([$id]);
        json_response(0, '删除成功');
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
