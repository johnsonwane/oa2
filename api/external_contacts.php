<?php
require_once __DIR__ . '/common.php';

function wecom_config(): array
{
    $config = require __DIR__ . '/config.php';
    return [
        'corp_id' => (string)($config['wecom']['corp_id'] ?? getenv('WECOM_CORP_ID') ?: ''),
        'contact_secret' => (string)($config['wecom']['contact_secret'] ?? getenv('WECOM_CONTACT_SECRET') ?: ''),
    ];
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

function wecom_assert_ok(array $data): array
{
    if ((int)($data['errcode'] ?? 0) !== 0) {
        throw new RuntimeException('企业微信接口错误：' . (string)($data['errmsg'] ?? 'unknown'));
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
    $res = wecom_assert_ok(wecom_request_json($url, new stdClass()));
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

function wecom_user_info(string $token, string $userId): array
{
    $url = 'https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token=' . rawurlencode($token) . '&userid=' . rawurlencode($userId);
    $data = wecom_get_json($url);
    if ((int)($data['errcode'] ?? 0) !== 0) {
        return ['name' => '', 'userid' => $userId, 'department' => []];
    }
    return [
        'name' => (string)($data['name'] ?? ''),
        'userid' => (string)($data['userid'] ?? $userId),
        'department' => is_array($data['department'] ?? null) ? $data['department'] : [],
    ];
}

function wecom_department_map(string $token): array
{
    $url = 'https://qyapi.weixin.qq.com/cgi-bin/department/simplelist?access_token=' . rawurlencode($token);
    $data = wecom_get_json($url);
    if ((int)($data['errcode'] ?? 0) !== 0) {
        return [];
    }
    $map = [];
    foreach (($data['department_id'] ?? []) as $row) {
        $id = (string)($row['id'] ?? '');
        $name = (string)($row['name'] ?? '');
        if ($id !== '') {
            $map[$id] = $name;
        }
    }
    return $map;
}

function wecom_list_external_user_ids_by_user(string $token, string $userId): array
{
    $url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/list?access_token=' . rawurlencode($token);

    try {
        $res = wecom_assert_ok(wecom_request_json($url, ['userid' => $userId]));
    } catch (Throwable $e) {
        $msg = (string)$e->getMessage();
        if (strpos($msg, 'missing field `userid`') !== false || strpos($msg, 'missing field userid') !== false) {
            $res = wecom_assert_ok(wecom_get_json($url . '&userid=' . rawurlencode($userId)));
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
    $data = wecom_get_json($url);
    if ((int)($data['errcode'] ?? 0) !== 0) {
        return ['external_contact' => ['external_userid' => $externalUserId], 'follow_user' => []];
    }
    return $data;
}

function find_tag_group(array $tags, string $groupName): string
{
    $names = [];
    foreach ($tags as $tag) {
        if (!is_array($tag)) {
            continue;
        }
        $g = (string)($tag['group_name'] ?? '');
        $t = (string)($tag['name'] ?? '');
        if ($g === $groupName && $t !== '') {
            $names[] = $t;
        }
    }
    return implode('、', array_values(array_unique($names)));
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        json_response(405, 'method not allowed', null, 405);
    }

    $cfg = wecom_config();
    if ($cfg['corp_id'] === '' || $cfg['contact_secret'] === '') {
        json_response(400, '未配置企业微信参数：WECOM_CORP_ID / WECOM_CONTACT_SECRET', null, 400);
    }

    $token = wecom_access_token($cfg['corp_id'], $cfg['contact_secret']);
    $deptMap = wecom_department_map($token);
    $followUsers = wecom_follow_user_ids($token);

    if (empty($followUsers)) {
        json_response(0, 'ok', []);
    }

    $userInfoMap = [];
    $externalIds = [];
    foreach ($followUsers as $uid) {
        $uid = trim((string)$uid);
        if ($uid === '') {
            continue;
        }
        if (!isset($userInfoMap[$uid])) {
            $userInfoMap[$uid] = wecom_user_info($token, $uid);
        }
        foreach (wecom_list_external_user_ids_by_user($token, $uid) as $eid) {
            $externalIds[$eid] = true;
        }
    }

    $rows = [];
    foreach (array_keys($externalIds) as $externalId) {
        $detail = wecom_external_detail($token, $externalId);
        $ec = is_array($detail['external_contact'] ?? null) ? $detail['external_contact'] : [];
        $fus = is_array($detail['follow_user'] ?? null) ? $detail['follow_user'] : [];

        if (empty($fus)) {
            $rows[] = [
                '客户名称' => (string)($ec['name'] ?? ''),
                '描述' => '',
                '添加人' => '',
                '添加人账号' => '',
                '添加人所属部门' => '',
                '添加时间' => '',
                '来源' => '',
                '手机' => (string)($ec['mobile'] ?? ''),
                '企业' => (string)($ec['corp_name'] ?? ''),
                '邮箱' => (string)($ec['email'] ?? ''),
                '地址' => (string)($ec['address'] ?? ''),
                '职务' => (string)($ec['position'] ?? ''),
                '电话' => (string)($ec['tel'] ?? ''),
                '标签组1(学员等级)' => '',
                '标签组2(来源)' => '',
                'external_userid' => $externalId,
            ];
            continue;
        }

        foreach ($fus as $fu) {
            if (!is_array($fu)) {
                continue;
            }
            $uid = (string)($fu['userid'] ?? '');
            $user = $userInfoMap[$uid] ?? ['name' => '', 'userid' => $uid, 'department' => []];
            $deptNames = [];
            foreach (($user['department'] ?? []) as $deptId) {
                $k = (string)$deptId;
                if (isset($deptMap[$k]) && $deptMap[$k] !== '') {
                    $deptNames[] = $deptMap[$k];
                }
            }
            $tags = is_array($fu['tags'] ?? null) ? $fu['tags'] : [];
            $rows[] = [
                '客户名称' => (string)($ec['name'] ?? ''),
                '描述' => (string)($fu['description'] ?? ''),
                '添加人' => (string)($user['name'] ?? ''),
                '添加人账号' => $uid,
                '添加人所属部门' => implode('、', array_values(array_unique($deptNames))),
                '添加时间' => !empty($fu['createtime']) ? date('Y-m-d H:i:s', (int)$fu['createtime']) : '',
                '来源' => (string)($fu['add_way'] ?? ''),
                '手机' => (string)($ec['mobile'] ?? ''),
                '企业' => (string)($ec['corp_name'] ?? ''),
                '邮箱' => (string)($ec['email'] ?? ''),
                '地址' => (string)($ec['address'] ?? ''),
                '职务' => (string)($ec['position'] ?? ''),
                '电话' => (string)($ec['tel'] ?? ''),
                '标签组1(学员等级)' => find_tag_group($tags, '学员等级'),
                '标签组2(来源)' => find_tag_group($tags, '来源'),
                'external_userid' => $externalId,
            ];
        }
    }

    json_response(0, 'ok', $rows);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
