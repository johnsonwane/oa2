<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';

function wecom_config(): array
{
    $config = require __DIR__ . '/config.php';
    return [
        'corp_id' => (string)($config['wecom']['corp_id'] ?? getenv('WECOM_CORP_ID') ?: ''),
        'contact_secret' => (string)($config['wecom']['contact_secret'] ?? getenv('WECOM_CONTACT_SECRET') ?: ''),
    ];
}

function ensure_external_contact_cache_table(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS oa_external_contact_cache_v2 (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      external_userid VARCHAR(128) NOT NULL DEFAULT '',
      errcode INT NOT NULL DEFAULT 0,
      errmsg VARCHAR(255) NOT NULL DEFAULT '',
      name VARCHAR(120) NOT NULL DEFAULT '',
      avatar VARCHAR(500) NOT NULL DEFAULT '',
      type VARCHAR(64) NOT NULL DEFAULT '',
      gender VARCHAR(32) NOT NULL DEFAULT '',
      unionid VARCHAR(128) NOT NULL DEFAULT '',
      position VARCHAR(120) NOT NULL DEFAULT '',
      corp_name VARCHAR(255) NOT NULL DEFAULT '',
      corp_full_name VARCHAR(255) NOT NULL DEFAULT '',
      external_profile TEXT,
      follow_user_userid VARCHAR(120) NOT NULL DEFAULT '',
      follow_user_remark VARCHAR(255) NOT NULL DEFAULT '',
      follow_user_description VARCHAR(255) NOT NULL DEFAULT '',
      follow_user_createtime DATETIME DEFAULT NULL,
      follow_user_tags_group_name TEXT,
      follow_user_tags_tag_name TEXT,
      follow_user_tags_type TEXT,
      follow_user_tags_tag_id TEXT,
      follow_user_remark_corp_name VARCHAR(255) NOT NULL DEFAULT '',
      follow_user_remark_mobiles TEXT,
      follow_user_add_way VARCHAR(64) NOT NULL DEFAULT '',
      follow_user_wechat_channels TEXT,
      follow_user_wechat_channels_nickname VARCHAR(120) NOT NULL DEFAULT '',
      follow_user_wechat_channels_source VARCHAR(120) NOT NULL DEFAULT '',
      follow_user_oper_userid VARCHAR(120) NOT NULL DEFAULT '',
      follow_user_state VARCHAR(255) NOT NULL DEFAULT '',
      next_cursor VARCHAR(255) NOT NULL DEFAULT '',
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_external_userid (external_userid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $columns = [
        'errcode' => "INT NOT NULL DEFAULT 0",
        'errmsg' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'name' => "VARCHAR(120) NOT NULL DEFAULT ''",
        'avatar' => "VARCHAR(500) NOT NULL DEFAULT ''",
        'type' => "VARCHAR(64) NOT NULL DEFAULT ''",
        'gender' => "VARCHAR(32) NOT NULL DEFAULT ''",
        'unionid' => "VARCHAR(128) NOT NULL DEFAULT ''",
        'position' => "VARCHAR(120) NOT NULL DEFAULT ''",
        'corp_name' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'corp_full_name' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'external_profile' => "TEXT",
        'follow_user_userid' => "VARCHAR(120) NOT NULL DEFAULT ''",
        'follow_user_remark' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'follow_user_description' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'follow_user_createtime' => "DATETIME DEFAULT NULL",
        'follow_user_tags_group_name' => "TEXT",
        'follow_user_tags_tag_name' => "TEXT",
        'follow_user_tags_type' => "TEXT",
        'follow_user_tags_tag_id' => "TEXT",
        'follow_user_remark_corp_name' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'follow_user_remark_mobiles' => "TEXT",
        'follow_user_add_way' => "VARCHAR(64) NOT NULL DEFAULT ''",
        'follow_user_wechat_channels' => "TEXT",
        'follow_user_wechat_channels_nickname' => "VARCHAR(120) NOT NULL DEFAULT ''",
        'follow_user_wechat_channels_source' => "VARCHAR(120) NOT NULL DEFAULT ''",
        'follow_user_oper_userid' => "VARCHAR(120) NOT NULL DEFAULT ''",
        'follow_user_state' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'next_cursor' => "VARCHAR(255) NOT NULL DEFAULT ''",
    ];

    $exists = [];
    $stmt = $pdo->query('SHOW COLUMNS FROM oa_external_contact_cache_v2');
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $exists[(string)($col['Field'] ?? '')] = true;
    }
    foreach ($columns as $name => $ddl) {
        if (!isset($exists[$name])) {
            $pdo->exec("ALTER TABLE oa_external_contact_cache_v2 ADD COLUMN {$name} {$ddl}");
        }
    }
}

function table_exists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->execute([$tableName]);
    return (int)$stmt->fetchColumn() > 0;
}

function table_columns(PDO $pdo, string $tableName): array
{
    $stmt = $pdo->query("SHOW COLUMNS FROM `" . str_replace('`', '``', $tableName) . "`");
    $cols = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $name = (string)($row['Field'] ?? '');
        if ($name !== '') {
            $cols[] = $name;
        }
    }
    return $cols;
}

function migrate_legacy_external_contact_cache_if_needed(PDO $pdo): void
{
    $newTable = 'oa_external_contact_cache_v2';
    $oldTable = 'oa_external_contact_cache';

    if (!table_exists($pdo, $newTable) || !table_exists($pdo, $oldTable)) {
        return;
    }

    $newCount = (int)$pdo->query("SELECT COUNT(*) FROM {$newTable}")->fetchColumn();
    if ($newCount > 0) {
        return;
    }

    $oldCount = (int)$pdo->query("SELECT COUNT(*) FROM {$oldTable}")->fetchColumn();
    if ($oldCount <= 0) {
        return;
    }

    $newCols = table_columns($pdo, $newTable);
    $oldCols = table_columns($pdo, $oldTable);
    $oldSet = array_fill_keys($oldCols, true);

    $copyCols = [];
    foreach ($newCols as $c) {
        if ($c === 'id' || $c === 'updated_at') {
            continue;
        }
        if (isset($oldSet[$c])) {
            $copyCols[] = $c;
        }
    }

    if (empty($copyCols)) {
        return;
    }

    $sqlCols = implode(', ', array_map(function ($c) {
        return "`" . str_replace('`', '``', $c) . "`";
    }, $copyCols));

    $pdo->beginTransaction();
    try {
        $pdo->exec("INSERT INTO {$newTable} ({$sqlCols}) SELECT {$sqlCols} FROM {$oldTable}");
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function cached_rows(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT
      external_userid,
      errcode,
      errmsg,
      name,
      avatar,
      type,
      gender,
      unionid,
      position,
      corp_name,
      corp_full_name,
      IFNULL(external_profile, '') AS external_profile,
      follow_user_userid AS `follow_user.userid`,
      follow_user_remark AS `follow_user.remark`,
      follow_user_description AS `follow_user.description`,
      IFNULL(DATE_FORMAT(follow_user_createtime, '%Y-%m-%d %H:%i:%s'), '') AS `follow_user.createtime`,
      IFNULL(follow_user_tags_group_name, '') AS `follow_user.tags.group_name`,
      IFNULL(follow_user_tags_tag_name, '') AS `follow_user.tags.tag_name`,
      IFNULL(follow_user_tags_type, '') AS `follow_user.tags.type`,
      IFNULL(follow_user_tags_tag_id, '') AS `follow_user.tags.tag_id`,
      follow_user_remark_corp_name AS `follow_user.remark_corp_name`,
      IFNULL(follow_user_remark_mobiles, '') AS `follow_user.remark_mobiles`,
      follow_user_add_way AS `follow_user.add_way`,
      IFNULL(follow_user_wechat_channels, '') AS `follow_user.wechat_channels`,
      follow_user_wechat_channels_nickname AS `follow_user.wechat_channels.nickname`,
      follow_user_wechat_channels_source AS `follow_user.wechat_channels.source`,
      follow_user_oper_userid AS `follow_user.oper_userid`,
      follow_user_state AS `follow_user.state`,
      next_cursor
      FROM oa_external_contact_cache_v2
      ORDER BY id DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function wecom_get_json(string $url): array
{
    $resp = @file_get_contents($url);
    if ($resp === false) {
        $last = error_get_last();
        $msg = is_array($last) ? (string)($last['message'] ?? '') : '';
        throw new RuntimeException('企业微信请求失败' . ($msg !== '' ? '：' . $msg : ''));
    }
    $data = json_decode($resp, true);
    if (!is_array($data)) {
        throw new RuntimeException('企业微信返回格式异常');
    }
    return $data;
}

function wecom_request_json(string $url, $body): array
{
    if (!is_array($body) && !is_object($body)) {
        $body = [];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 20,
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('企业微信请求失败：' . $err);
    }
    curl_close($ch);
    $data = json_decode($resp, true);
    if (!is_array($data)) {
        throw new RuntimeException('企业微信返回格式异常');
    }
    return $data;
}

function wecom_retryable_errcode(int $errcode): bool
{
    return in_array($errcode, [-1, 42001, 45009, 50001, 50002], true);
}

function wecom_retryable_message(string $message): bool
{
    if (preg_match('/errcode\s*=\s*(-?\d+)/', $message, $m)) {
        return wecom_retryable_errcode((int)$m[1]);
    }
    return false;
}

function wecom_with_retry(callable $fn, int $maxAttempts = 6, int $sleepMs = 200)
{
    $attempt = 0;
    $last = null;
    while ($attempt < $maxAttempts) {
        $attempt++;
        try {
            return $fn();
        } catch (Throwable $e) {
            $last = $e;
            if (!wecom_retryable_message((string)$e->getMessage()) || $attempt >= $maxAttempts) {
                throw $e;
            }
            usleep($sleepMs * 1000 * $attempt);
        }
    }
    throw $last ?: new RuntimeException('企业微信请求失败');
}

function wecom_assert_ok(array $data): array
{
    $errcode = (int)($data['errcode'] ?? 0);
    if ($errcode !== 0) {
        $errmsg = (string)($data['errmsg'] ?? 'unknown');
        throw new RuntimeException('企业微信接口错误：errcode=' . $errcode . '; errmsg=' . $errmsg);
    }
    return $data;
}

function wecom_access_token(string $corpId, string $secret): string
{
    $url = 'https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=' . rawurlencode($corpId) . '&corpsecret=' . rawurlencode($secret);
    $data = wecom_assert_ok(wecom_get_json($url));
    $token = (string)($data['access_token'] ?? '');
    if ($token === '') {
        throw new RuntimeException('获取 access_token 失败：返回中缺少 access_token');
    }
    return $token;
}

function wecom_follow_user_ids(string $token): array
{
    $url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/get_follow_user_list?access_token=' . rawurlencode($token);
    $res = wecom_with_retry(function () use ($url) {
        return wecom_assert_ok(wecom_request_json($url, new stdClass()));
    });
    $ids = [];
    foreach (($res['follow_user'] ?? []) as $row) {
        $v = '';
        if (is_string($row) || is_numeric($row)) {
            $v = trim((string)$row);
        } elseif (is_array($row)) {
            $v = trim((string)($row['userid'] ?? ($row['user_id'] ?? ($row['UserId'] ?? ''))));
        } elseif (is_object($row)) {
            $v = trim((string)($row->userid ?? ($row->user_id ?? ($row->UserId ?? ''))));
        }
        if ($v !== '') {
            $ids[] = $v;
        }
    }
    return array_values(array_unique($ids));
}

function wecom_list_external_user_ids_by_user(string $token, string $userId): array
{
    $url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/list?access_token=' . rawurlencode($token);

    try {
        $res = wecom_with_retry(function () use ($url, $userId) {
            return wecom_assert_ok(wecom_request_json($url, ['userid' => $userId]));
        });
    } catch (Throwable $e) {
        $msg = (string)$e->getMessage();
        if (strpos($msg, 'missing field `userid`') !== false || strpos($msg, 'missing field userid') !== false) {
            $res = wecom_with_retry(function () use ($url, $userId) {
                return wecom_assert_ok(wecom_get_json($url . '&userid=' . rawurlencode($userId)));
            });
        } else {
            throw $e;
        }
    }

    $ids = [];
    foreach (($res['external_userid'] ?? []) as $uid) {
        $v = trim((string)$uid);
        if ($v !== '') {
            $ids[] = $v;
        }
    }
    return $ids;
}

function wecom_external_detail(string $token, string $externalUserId): array
{
    $url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/get?access_token=' . rawurlencode($token) . '&external_userid=' . rawurlencode($externalUserId);
    return wecom_with_retry(function () use ($url) {
        return wecom_get_json($url);
    });
}

function push_sync_error(array &$errors, string $stage, string $id, string $message): void
{
    if (count($errors) >= 200) {
        return;
    }
    $errors[] = [
        'stage' => $stage,
        'id' => $id,
        'message' => $message,
    ];
}

function sync_external_contacts(PDO $pdo): array
{
    $cfg = wecom_config();
    if ($cfg['corp_id'] === '' || $cfg['contact_secret'] === '') {
        throw new RuntimeException('未配置企业微信参数：WECOM_CORP_ID / WECOM_CONTACT_SECRET');
    }

    $token = wecom_access_token($cfg['corp_id'], $cfg['contact_secret']);
    $followUsers = wecom_follow_user_ids($token);

    $errors = [];
    $externalIds = [];
    foreach ($followUsers as $uid) {
        $uid = trim((string)$uid);
        if ($uid === '') {
            continue;
        }
        try {
            foreach (wecom_list_external_user_ids_by_user($token, $uid) as $eid) {
                $externalIds[$eid] = true;
            }
        } catch (Throwable $e) {
            push_sync_error($errors, 'list_external_user', $uid, $e->getMessage());
        }
    }

    $rows = [];
    foreach (array_keys($externalIds) as $externalId) {
        try {
            $detail = wecom_external_detail($token, $externalId);
        } catch (Throwable $e) {
            push_sync_error($errors, 'external_detail', $externalId, $e->getMessage());
            continue;
        }

        $errcode = (int)($detail['errcode'] ?? 0);
        $errmsg = (string)($detail['errmsg'] ?? '');
        $nextCursor = (string)($detail['next_cursor'] ?? '');
        if ($errcode !== 0) {
            push_sync_error($errors, 'external_detail_errcode', $externalId, $errmsg !== '' ? $errmsg : ('errcode=' . $errcode));
            continue;
        }

        $ec = is_array($detail['external_contact'] ?? null) ? $detail['external_contact'] : [];
        $fus = is_array($detail['follow_user'] ?? null) ? $detail['follow_user'] : [];

        if (empty($fus)) {
            $rows[] = [
                'external_userid' => (string)($ec['external_userid'] ?? $externalId),
                'errcode' => $errcode,
                'errmsg' => $errmsg,
                'name' => (string)($ec['name'] ?? ''),
                'avatar' => (string)($ec['avatar'] ?? ''),
                'type' => (string)($ec['type'] ?? ''),
                'gender' => (string)($ec['gender'] ?? ''),
                'unionid' => (string)($ec['unionid'] ?? ''),
                'position' => (string)($ec['position'] ?? ''),
                'corp_name' => (string)($ec['corp_name'] ?? ''),
                'corp_full_name' => (string)($ec['corp_full_name'] ?? ''),
                'external_profile' => json_encode($ec['external_profile'] ?? new stdClass(), JSON_UNESCAPED_UNICODE),
                'follow_user_userid' => '',
                'follow_user_remark' => '',
                'follow_user_description' => '',
                'follow_user_createtime' => null,
                'follow_user_tags_group_name' => '',
                'follow_user_tags_tag_name' => '',
                'follow_user_tags_type' => '',
                'follow_user_tags_tag_id' => '',
                'follow_user_remark_corp_name' => '',
                'follow_user_remark_mobiles' => '',
                'follow_user_add_way' => '',
                'follow_user_wechat_channels' => '',
                'follow_user_wechat_channels_nickname' => '',
                'follow_user_wechat_channels_source' => '',
                'follow_user_oper_userid' => '',
                'follow_user_state' => '',
                'next_cursor' => $nextCursor,
            ];
            continue;
        }

        foreach ($fus as $fu) {
            if (!is_array($fu)) {
                continue;
            }
            $tags = is_array($fu['tags'] ?? null) ? $fu['tags'] : [];
            $groupNames = [];
            $tagNames = [];
            $tagTypes = [];
            $tagIds = [];
            foreach ($tags as $tag) {
                if (!is_array($tag)) {
                    continue;
                }
                $group = (string)($tag['group_name'] ?? '');
                $tagName = (string)($tag['tag_name'] ?? ($tag['name'] ?? ''));
                $tagType = (string)($tag['type'] ?? '');
                $tagId = (string)($tag['tag_id'] ?? '');
                if ($group !== '') {
                    $groupNames[] = $group;
                }
                if ($tagName !== '') {
                    $tagNames[] = $tagName;
                }
                if ($tagType !== '') {
                    $tagTypes[] = $tagType;
                }
                if ($tagId !== '') {
                    $tagIds[] = $tagId;
                }
            }

            $wechatChannels = $fu['wechat_channels'] ?? null;
            $wechatChannelsJson = '';
            $wechatChannelsNickname = '';
            $wechatChannelsSource = '';
            if (is_array($wechatChannels)) {
                $wechatChannelsJson = json_encode($wechatChannels, JSON_UNESCAPED_UNICODE);
                $wechatChannelsNickname = (string)($wechatChannels['nickname'] ?? '');
                $wechatChannelsSource = (string)($wechatChannels['source'] ?? '');
            }

            $rows[] = [
                'external_userid' => (string)($ec['external_userid'] ?? $externalId),
                'errcode' => $errcode,
                'errmsg' => $errmsg,
                'name' => (string)($ec['name'] ?? ''),
                'avatar' => (string)($ec['avatar'] ?? ''),
                'type' => (string)($ec['type'] ?? ''),
                'gender' => (string)($ec['gender'] ?? ''),
                'unionid' => (string)($ec['unionid'] ?? ''),
                'position' => (string)($ec['position'] ?? ''),
                'corp_name' => (string)($ec['corp_name'] ?? ''),
                'corp_full_name' => (string)($ec['corp_full_name'] ?? ''),
                'external_profile' => json_encode($ec['external_profile'] ?? new stdClass(), JSON_UNESCAPED_UNICODE),
                'follow_user_userid' => (string)($fu['userid'] ?? ''),
                'follow_user_remark' => (string)($fu['remark'] ?? ''),
                'follow_user_description' => (string)($fu['description'] ?? ''),
                'follow_user_createtime' => !empty($fu['createtime']) ? date('Y-m-d H:i:s', (int)$fu['createtime']) : null,
                'follow_user_tags_group_name' => implode('、', array_values(array_unique($groupNames))),
                'follow_user_tags_tag_name' => implode('、', array_values(array_unique($tagNames))),
                'follow_user_tags_type' => implode('、', array_values(array_unique($tagTypes))),
                'follow_user_tags_tag_id' => implode('、', array_values(array_unique($tagIds))),
                'follow_user_remark_corp_name' => (string)($fu['remark_corp_name'] ?? ''),
                'follow_user_remark_mobiles' => json_encode($fu['remark_mobiles'] ?? [], JSON_UNESCAPED_UNICODE),
                'follow_user_add_way' => (string)($fu['add_way'] ?? ''),
                'follow_user_wechat_channels' => $wechatChannelsJson,
                'follow_user_wechat_channels_nickname' => $wechatChannelsNickname,
                'follow_user_wechat_channels_source' => $wechatChannelsSource,
                'follow_user_oper_userid' => (string)($fu['oper_userid'] ?? ''),
                'follow_user_state' => (string)($fu['state'] ?? ''),
                'next_cursor' => $nextCursor,
            ];
        }
    }

    $pdo->beginTransaction();
    try {
        $pdo->exec('DELETE FROM oa_external_contact_cache_v2');
        $stmt = $pdo->prepare("INSERT INTO oa_external_contact_cache_v2
            (external_userid, errcode, errmsg, name, avatar, type, gender, unionid, position, corp_name, corp_full_name, external_profile,
             follow_user_userid, follow_user_remark, follow_user_description, follow_user_createtime, follow_user_tags_group_name,
             follow_user_tags_tag_name, follow_user_tags_type, follow_user_tags_tag_id, follow_user_remark_corp_name, follow_user_remark_mobiles,
             follow_user_add_way, follow_user_wechat_channels, follow_user_wechat_channels_nickname, follow_user_wechat_channels_source,
             follow_user_oper_userid, follow_user_state, next_cursor)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($rows as $r) {
            $stmt->execute([
                $r['external_userid'],
                $r['errcode'],
                $r['errmsg'],
                $r['name'],
                $r['avatar'],
                $r['type'],
                $r['gender'],
                $r['unionid'],
                $r['position'],
                $r['corp_name'],
                $r['corp_full_name'],
                $r['external_profile'],
                $r['follow_user_userid'],
                $r['follow_user_remark'],
                $r['follow_user_description'],
                $r['follow_user_createtime'],
                $r['follow_user_tags_group_name'],
                $r['follow_user_tags_tag_name'],
                $r['follow_user_tags_type'],
                $r['follow_user_tags_tag_id'],
                $r['follow_user_remark_corp_name'],
                $r['follow_user_remark_mobiles'],
                $r['follow_user_add_way'],
                $r['follow_user_wechat_channels'],
                $r['follow_user_wechat_channels_nickname'],
                $r['follow_user_wechat_channels_source'],
                $r['follow_user_oper_userid'],
                $r['follow_user_state'],
                $r['next_cursor'],
            ]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return [
        'rows' => count($rows),
        'follow_users' => count($followUsers),
        'external_contacts' => count($externalIds),
        'detail_success' => count($rows),
        'detail_failed' => count($errors),
        'errors' => $errors,
    ];
}

try {
    $pdo = get_db_connection();
    ensure_external_contact_cache_table($pdo);
    migrate_legacy_external_contact_cache_if_needed($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        json_response(0, 'ok', cached_rows($pdo));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $result = sync_external_contacts($pdo);
        $errCount = count($result['errors'] ?? []);
        $msg = $errCount > 0 ? ('同步完成，但有 ' . $errCount . ' 条异常') : '同步完成';
        json_response(0, $msg, $result);
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
