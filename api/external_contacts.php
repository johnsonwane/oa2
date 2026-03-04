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
    if ((int)($data['errcode'] ?? 0) !== 0) {
        throw new RuntimeException('企业微信接口错误：' . (string)($data['errmsg'] ?? 'unknown'));
    }
    return $data;
}

function wecom_access_token(string $corpId, string $secret): string
{
    $url = 'https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=' . rawurlencode($corpId) . '&corpsecret=' . rawurlencode($secret);
    $resp = @file_get_contents($url);
    if ($resp === false) {
        $last = error_get_last();
        $msg = is_array($last) ? (string)($last['message'] ?? '') : '';
        throw new RuntimeException('获取企业微信 access_token 失败' . ($msg !== '' ? '：' . $msg : ''));
    }
    $data = json_decode($resp, true);
    if (!is_array($data) || (int)($data['errcode'] ?? 0) !== 0 || empty($data['access_token'])) {
        throw new RuntimeException('获取 access_token 失败：' . (string)($data['errmsg'] ?? 'unknown'));
    }
    return (string)$data['access_token'];
}

function wecom_follow_user_ids(string $token): array
{
    $url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/get_follow_user_list?access_token=' . rawurlencode($token);
    $res = wecom_request_json($url, new stdClass());
    $ids = [];
    foreach (($res['follow_user'] ?? []) as $id) {
        $v = trim((string)$id);
        if ($v !== '') {
            $ids[] = $v;
        }
    }
    return array_values(array_unique($ids));
}

function wecom_list_external_user_ids_by_user(string $token, string $userId): array
{
    $url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/list?access_token=' . rawurlencode($token);
    $res = wecom_request_json($url, ['userid' => $userId]);
    $ids = [];
    foreach (($res['external_userid'] ?? []) as $uid) {
        $v = trim((string)$uid);
        if ($v !== '') {
            $ids[] = $v;
        }
    }
    return $ids;
}

function wecom_get_external_detail(string $token, string $externalUserId): array
{
    $url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/get?access_token=' . rawurlencode($token) . '&external_userid=' . rawurlencode($externalUserId);
    $resp = @file_get_contents($url);
    if ($resp === false) {
        return ['external_userid' => $externalUserId, 'name' => '', 'type' => '', 'position' => '', 'corp_name' => ''];
    }
    $data = json_decode($resp, true);
    if (!is_array($data) || (int)($data['errcode'] ?? 0) !== 0) {
        return ['external_userid' => $externalUserId, 'name' => '', 'type' => '', 'position' => '', 'corp_name' => ''];
    }
    $ec = $data['external_contact'] ?? [];
    return [
        'external_userid' => $externalUserId,
        'name' => (string)($ec['name'] ?? ''),
        'type' => (string)($ec['type'] ?? ''),
        'position' => (string)($ec['position'] ?? ''),
        'corp_name' => (string)($ec['corp_name'] ?? ''),
    ];
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        json_response(405, 'method not allowed', null, 405);
    }

    $cfg = wecom_config();
    if ($cfg['corp_id'] === '' || $cfg['contact_secret'] === '') {
        json_response(400, '未配置企业微信参数：WECOM_CORP_ID / WECOM_CONTACT_SECRET', [
            'required' => [
                'WECOM_CORP_ID' => '企业ID（企业微信管理后台）',
                'WECOM_CONTACT_SECRET' => '通讯录/客户联系应用 Secret（企业微信管理后台）',
            ],
        ], 400);
    }

    $token = wecom_access_token($cfg['corp_id'], $cfg['contact_secret']);
    $followUsers = wecom_follow_user_ids($token);
    if (empty($followUsers)) {
        json_response(0, 'ok', []);
    }

    $ids = [];
    foreach ($followUsers as $uid) {
        foreach (wecom_list_external_user_ids_by_user($token, $uid) as $id) {
            $ids[] = $id;
        }
    }
    $ids = array_values(array_unique($ids));

    $withDetail = isset($_GET['detail']) && (string)$_GET['detail'] === '1';
    if (!$withDetail) {
        json_response(0, 'ok', array_map(static fn($id) => ['external_userid' => $id], $ids));
    }

    $rows = [];
    foreach ($ids as $id) {
        $rows[] = wecom_get_external_detail($token, $id);
    }

    json_response(0, 'ok', $rows);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
