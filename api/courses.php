<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';

try {
    $pdo = get_db_connection();
    $m = $_SERVER['REQUEST_METHOD'];
    if ($m === 'GET') {
        $stmt = $pdo->query('SELECT id, course_name, coach_name, period_weeks, price, status FROM oa_course ORDER BY id DESC');
        json_response(0, 'ok', $stmt->fetchAll());
    }
    if ($m === 'POST') {
        $d = request_body(); require_fields($d, ['course_name','coach_name']);
        $stmt = $pdo->prepare('INSERT INTO oa_course(course_name, coach_name, period_weeks, price, status) VALUES(?,?,?,?,?)');
        $stmt->execute([trim($d['course_name']), trim($d['coach_name']), (int)($d['period_weeks']??0), (float)($d['price']??0), (int)($d['status']??1)]);
        json_response(0, 'created', ['id'=>(int)$pdo->lastInsertId()]);
    }
    if ($m === 'PUT') {
        $d=request_body(); $id=(int)($d['id']??0); if($id<=0) json_response(400,'id非法',null,400);
        require_fields($d, ['course_name','coach_name']);
        $stmt=$pdo->prepare('UPDATE oa_course SET course_name=?, coach_name=?, period_weeks=?, price=?, status=? WHERE id=?');
        $stmt->execute([trim($d['course_name']), trim($d['coach_name']), (int)($d['period_weeks']??0), (float)($d['price']??0), (int)($d['status']??1), $id]);
        json_response(0,'updated');
    }
    if ($m==='DELETE') {
        $id=(int)($_GET['id']??0); if($id<=0) json_response(400,'id非法',null,400);
        $stmt=$pdo->prepare('DELETE FROM oa_course WHERE id=?'); $stmt->execute([$id]); json_response(0,'deleted');
    }
    json_response(405,'method not allowed',null,405);
} catch (Throwable $e) { json_response(500, '服务异常：'.$e->getMessage(), null, 500);} 
