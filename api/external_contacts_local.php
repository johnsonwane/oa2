<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';

function ensure_external_contacts_local_table(PDO $pdo): void
{
    // P0 稳定性整改：禁止运行时建表/补列。
}


function ensure_external_contacts_local_note_table(PDO $pdo): void
{
    // P0 稳定性整改：禁止运行时建表/补列。
}

function local_select_sql(): string
{
    return "SELECT
      t.id,
      t.customer_name AS `客户名称`,
      t.description_text AS `描述`,
      t.follower_name AS `添加人`,
      IFNULL(NULLIF(n.account_note, ''), t.follower_account) AS `添加人账号`,
      t.follower_account AS `添加人账号原始`,
      t.follower_department AS `添加人所属部门`,
      IFNULL(DATE_FORMAT(t.follow_time, '%Y-%m-%d %H:%i:%s'), '') AS `添加时间`,
      t.source AS `来源`,
      t.mobile AS `手机`,
      t.enterprise AS `企业`,
      t.email AS `邮箱`,
      t.address AS `地址`,
      t.job_title AS `职务`,
      t.phone AS `电话`,
      t.tag_group1_student_level AS `标签组1(学员等级)`,
      t.tag_group2_source AS `标签组2(来源)`
      FROM oa_external_contacts_local t
      LEFT JOIN oa_external_contacts_local_account_note n ON n.follower_account = t.follower_account";
}

function local_rows(PDO $pdo): array
{
    $stmt = $pdo->query(local_select_sql() . " ORDER BY follow_time DESC, id DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function local_rows_paged(PDO $pdo, array $query): array
{
    $pg = parse_pagination($query, 1, 100, 1000);
    $total = (int)$pdo->query('SELECT COUNT(*) FROM oa_external_contacts_local')->fetchColumn();

    $stmt = $pdo->prepare(local_select_sql() . " ORDER BY follow_time DESC, id DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, (int)$pg['page_size'], PDO::PARAM_INT);
    $stmt->bindValue(2, (int)$pg['offset'], PDO::PARAM_INT);
    $stmt->execute();

    return [
        'items' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'pagination' => [
            'page' => (int)$pg['page'],
            'page_size' => (int)$pg['page_size'],
            'total' => $total,
            'total_pages' => $pg['page_size'] > 0 ? (int)ceil($total / $pg['page_size']) : 1,
        ],
    ];
}

function xlsx_shared_strings(ZipArchive $zip): array
{
    $xml = $zip->getFromName('xl/sharedStrings.xml');
    if ($xml === false || trim($xml) === '') {
        return [];
    }
    $sx = @simplexml_load_string($xml);
    if (!$sx) {
        return [];
    }
    $out = [];
    foreach ($sx->si as $si) {
        if (isset($si->t)) {
            $out[] = (string)$si->t;
            continue;
        }
        $text = '';
        foreach ($si->r as $r) {
            $text .= (string)$r->t;
        }
        $out[] = $text;
    }
    return $out;
}

function xlsx_first_sheet_path(ZipArchive $zip): string
{
    $workbook = $zip->getFromName('xl/workbook.xml');
    $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($workbook === false || $rels === false) {
        return 'xl/worksheets/sheet1.xml';
    }

    $wb = @simplexml_load_string($workbook);
    $rb = @simplexml_load_string($rels);
    if (!$wb || !$rb) {
        return 'xl/worksheets/sheet1.xml';
    }

    $nsDoc = $wb->getDocNamespaces(true);
    $nsRel = $rb->getDocNamespaces(true);
    $wb->registerXPathNamespace('s', $nsDoc[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $wb->registerXPathNamespace('r', $nsDoc['r'] ?? 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
    $rb->registerXPathNamespace('p', $nsRel[''] ?? 'http://schemas.openxmlformats.org/package/2006/relationships');

    $sheets = $wb->xpath('/s:workbook/s:sheets/s:sheet');
    if (!$sheets || !isset($sheets[0])) {
        return 'xl/worksheets/sheet1.xml';
    }
    $attrs = $sheets[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
    $rid = (string)($attrs['id'] ?? '');
    if ($rid === '') {
        return 'xl/worksheets/sheet1.xml';
    }

    $relsNodes = $rb->xpath('/p:Relationships/p:Relationship');
    foreach ($relsNodes as $rel) {
        $a = $rel->attributes();
        if ((string)($a['Id'] ?? '') === $rid) {
            $target = (string)($a['Target'] ?? 'worksheets/sheet1.xml');
            return 'xl/' . ltrim($target, '/');
        }
    }
    return 'xl/worksheets/sheet1.xml';
}

function xlsx_rows(string $path): array
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('xlsx 文件打开失败');
    }

    $shared = xlsx_shared_strings($zip);
    $sheetPath = xlsx_first_sheet_path($zip);
    $sheetXml = $zip->getFromName($sheetPath);
    $zip->close();

    if ($sheetXml === false || trim($sheetXml) === '') {
        throw new RuntimeException('xlsx 中未找到工作表');
    }

    $sx = @simplexml_load_string($sheetXml);
    if (!$sx) {
        throw new RuntimeException('xlsx 工作表解析失败');
    }

    $out = [];
    foreach ($sx->sheetData->row as $row) {
        $line = [];
        foreach ($row->c as $c) {
            $ref = (string)($c['r'] ?? '');
            if ($ref === '') {
                continue;
            }
            if (!preg_match('/^([A-Z]+)(\d+)$/i', $ref, $m)) {
                continue;
            }
            $col = strtoupper($m[1]);
            $idx = 0;
            for ($i = 0; $i < strlen($col); $i++) {
                $idx = $idx * 26 + (ord($col[$i]) - ord('A') + 1);
            }
            $idx -= 1;

            $t = (string)($c['t'] ?? '');
            $v = '';
            if ($t === 's') {
                $si = (int)($c->v ?? -1);
                $v = ($si >= 0 && isset($shared[$si])) ? (string)$shared[$si] : '';
            } elseif ($t === 'inlineStr') {
                $v = (string)($c->is->t ?? '');
            } else {
                $v = (string)($c->v ?? '');
            }
            $line[$idx] = trim($v);
        }
        if (!empty($line)) {
            ksort($line);
            $out[] = $line;
        }
    }
    return $out;
}

function normalize_datetime_or_null(string $value): ?string
{
    $v = trim($value);
    if ($v === '') {
        return null;
    }
    $v = str_replace('/', '-', $v);
    $ts = strtotime($v);
    if ($ts === false) {
        return null;
    }
    return date('Y-m-d H:i:s', $ts);
}

function is_note_row(array $line): bool
{
    $text = '';
    foreach ($line as $cell) {
        $v = trim((string)$cell);
        if ($v !== '') {
            $text .= $v . ' ';
        }
    }
    $text = trim($text);
    if ($text === '') {
        return false;
    }

    $notes = [
        '1.学员名称优先取成员为学员设置的备注名',
        '2.暂时不支持企业微信联系人的对外自定义字段导出',
        '3.暂时不支持个人标签的导出',
    ];
    foreach ($notes as $n) {
        if (strpos($text, $n) !== false) {
            return true;
        }
    }
    return false;
}


function external_contacts_local_row_hash(array $data): string
{
    $parts = [
        trim((string)($data['customer_name'] ?? '')),
        trim((string)($data['description_text'] ?? '')),
        trim((string)($data['follower_name'] ?? '')),
        trim((string)($data['follower_account'] ?? '')),
        trim((string)($data['follower_department'] ?? '')),
        trim((string)($data['follow_time'] ?? '')),
        trim((string)($data['source'] ?? '')),
        trim((string)($data['mobile'] ?? '')),
        trim((string)($data['enterprise'] ?? '')),
        trim((string)($data['email'] ?? '')),
        trim((string)($data['address'] ?? '')),
        trim((string)($data['job_title'] ?? '')),
        trim((string)($data['phone'] ?? '')),
        trim((string)($data['tag_group1_student_level'] ?? '')),
        trim((string)($data['tag_group2_source'] ?? '')),
    ];
    return sha1(implode("
", $parts));
}

function import_xlsx(PDO $pdo, string $path, bool $replaceAll = false): array
{
    $rows = xlsx_rows($path);
    if (count($rows) <= 1) {
        return ['inserted' => 0, 'skipped' => 0, 'processed' => 0];
    }

    $map = [
        '客户名称' => 'customer_name',
        '描述' => 'description_text',
        '添加人' => 'follower_name',
        '添加人账号' => 'follower_account',
        '添加人所属部门' => 'follower_department',
        '添加时间' => 'follow_time',
        '来源' => 'source',
        '手机' => 'mobile',
        '企业' => 'enterprise',
        '邮箱' => 'email',
        '地址' => 'address',
        '职务' => 'job_title',
        '电话' => 'phone',
        '标签组1(学员等级)' => 'tag_group1_student_level',
        '标签组2(来源)' => 'tag_group2_source',
    ];

    $headerRowIndex = -1;
    $indexMap = [];
    for ($r = 0; $r < count($rows) && $r < 30; $r++) {
        $line = array_values($rows[$r]);
        $tmp = [];
        foreach ($line as $i => $name) {
            $k = trim((string)$name);
            if (isset($map[$k])) {
                $tmp[$i] = $map[$k];
            }
        }
        if (count($tmp) >= 5) {
            $headerRowIndex = $r;
            $indexMap = $tmp;
            break;
        }
    }

    if ($headerRowIndex < 0 || empty($indexMap)) {
        throw new RuntimeException('未识别到表头，请确认是标准模板');
    }

    $pdo->beginTransaction();
    try {
        if ($replaceAll) {
            $pdo->exec('DELETE FROM oa_external_contacts_local');
        }

        $stmt = $pdo->prepare("INSERT INTO oa_external_contacts_local
          (customer_name, description_text, follower_name, follower_account, follower_department, follow_time, source, mobile, enterprise, email, address, job_title, phone, tag_group1_student_level, tag_group2_source, row_hash)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $existsStmt = $pdo->prepare('SELECT 1 FROM oa_external_contacts_local WHERE row_hash = ? LIMIT 1');

        $processed = 0;
        $inserted = 0;
        for ($r = $headerRowIndex + 1; $r < count($rows); $r++) {
            $line = $rows[$r];

            if (is_note_row($line)) {
                continue;
            }

            $data = [
                'customer_name' => '',
                'description_text' => '',
                'follower_name' => '',
                'follower_account' => '',
                'follower_department' => '',
                'follow_time' => null,
                'source' => '',
                'mobile' => '',
                'enterprise' => '',
                'email' => '',
                'address' => '',
                'job_title' => '',
                'phone' => '',
                'tag_group1_student_level' => '',
                'tag_group2_source' => '',
            ];

            foreach ($line as $i => $cell) {
                if (!isset($indexMap[$i])) {
                    continue;
                }
                $field = $indexMap[$i];
                if ($field === 'follow_time') {
                    $data[$field] = normalize_datetime_or_null((string)$cell);
                } else {
                    $data[$field] = trim((string)$cell);
                }
            }

            $notEmpty = false;
            foreach ($data as $k => $v) {
                if ($k === 'follow_time') {
                    if ($v !== null) {
                        $notEmpty = true;
                        break;
                    }
                } elseif ($v !== '') {
                    $notEmpty = true;
                    break;
                }
            }
            if (!$notEmpty) {
                continue;
            }

            $processed++;
            $hash = external_contacts_local_row_hash($data);
            $existsStmt->execute([$hash]);
            if ($existsStmt->fetchColumn()) {
                continue;
            }

            $stmt->execute([
                $data['customer_name'],
                $data['description_text'],
                $data['follower_name'],
                $data['follower_account'],
                $data['follower_department'],
                $data['follow_time'],
                $data['source'],
                $data['mobile'],
                $data['enterprise'],
                $data['email'],
                $data['address'],
                $data['job_title'],
                $data['phone'],
                $data['tag_group1_student_level'],
                $data['tag_group2_source'],
                $hash,
            ]);
            $inserted++;
        }
        $pdo->commit();
        return [
            'inserted' => $inserted,
            'skipped' => max(0, $processed - $inserted),
            'processed' => $processed,
        ];
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}


try {
    $pdo = get_db_connection();
    ensure_external_contacts_local_table($pdo);
    ensure_external_contacts_local_note_table($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (paged_mode($_GET)) {
            json_response(0, 'ok', local_rows_paged($pdo, $_GET));
        }
        json_response(0, 'ok', local_rows($pdo));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
            json_response(400, '请上传 xlsx 文件', null, 400);
        }
        $file = $_FILES['file'];
        if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            json_response(400, '文件上传失败', null, 400);
        }
        $name = strtolower((string)($file['name'] ?? ''));
        if (substr($name, -5) !== '.xlsx') {
            json_response(400, '仅支持 .xlsx 文件', null, 400);
        }
        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            json_response(400, '上传临时文件无效', null, 400);
        }

        $mode = trim((string)($_POST['mode'] ?? 'incremental'));
        $replaceAll = ($mode === 'replace');
        $result = import_xlsx($pdo, $tmp, $replaceAll);
        $msg = $replaceAll ? '清空并导入成功' : '增量导入成功';
        json_response(0, $msg, $result);
    }

    json_response(405, 'method not allowed', null, 405);
} catch (Throwable $e) {
    json_response(500, '服务异常：' . $e->getMessage(), null, 500);
}
