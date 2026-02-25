<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';

try {
    $pdo = get_db_connection();
    $m = $_SERVER['REQUEST_METHOD'];
    if ($m === 'GET') {
        $stmt = $pdo->query('SELECT id, name, phone, level, consultant, created_at FROM oa_student ORDER BY id DESC');
        json_response(0, 'ok', $stmt->fetchAll());
    }
    if ($m === 'POST') {
        $d = request_body(); require_fields($d, ['name','phone']);
        $stmt = $pdo->prepare('INSERT INTO oa_student(name, phone, level, consultant) VALUES(?,?,?,?)');
        $stmt->execute([trim($d['name']), trim($d['phone']), trim((string)($d['level'] ?? '')) , trim((string)($d['consultant'] ?? ''))]);
        json_response(0, 'created', ['id'=>(int)$pdo->lastInsertId()]);
    }
    if ($m === 'PUT') {
        $d = request_body(); $id=(int)($d['id']??0); if($id<=0) json_response(400,'id非法',null,400); require_fields($d,['name','phone']);
        $stmt=$pdo->prepare('UPDATE oa_student SET name=?, phone=?, level=?, consultant=? WHERE id=?');
        $stmt->execute([trim($d['name']), trim($d['phone']), trim((string)($d['level'] ?? '')), trim((string)($d['consultant'] ?? '')), $id]);
        json_response(0,'updated');
    }
    if ($m === 'DELETE') {
        $id=(int)($_GET['id']??0); if($id<=0) json_response(400,'id非法',null,400);
        $stmt=$pdo->prepare('DELETE FROM oa_student WHERE id=?'); $stmt->execute([$id]); json_response(0,'deleted');
    }
    json_response(405,'method not allowed',null,405);
} catch (Throwable $e) { json_response(500, '服务异常：'.$e->getMessage(), null, 500);} 
