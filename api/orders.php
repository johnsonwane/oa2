<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';

try {
    $pdo=get_db_connection(); $m=$_SERVER['REQUEST_METHOD'];
    if($m==='GET'){
        $sql='SELECT o.id,o.student_id,s.name student_name,o.course_id,c.course_name,o.amount,o.pay_status,o.created_at FROM oa_order o LEFT JOIN oa_student s ON s.id=o.student_id LEFT JOIN oa_course c ON c.id=o.course_id ORDER BY o.id DESC';
        $stmt=$pdo->query($sql); json_response(0,'ok',$stmt->fetchAll());
    }
    if($m==='POST'){
        $d=request_body(); require_fields($d,['student_id','course_id']);
        $stmt=$pdo->prepare('INSERT INTO oa_order(student_id,course_id,amount,pay_status) VALUES(?,?,?,?)');
        $stmt->execute([(int)$d['student_id'],(int)$d['course_id'],(float)($d['amount']??0),(int)($d['pay_status']??0)]);
        json_response(0,'created',['id'=>(int)$pdo->lastInsertId()]);
    }
    if($m==='PUT'){
        $d=request_body(); $id=(int)($d['id']??0); if($id<=0) json_response(400,'id非法',null,400); require_fields($d,['student_id','course_id']);
        $stmt=$pdo->prepare('UPDATE oa_order SET student_id=?,course_id=?,amount=?,pay_status=? WHERE id=?');
        $stmt->execute([(int)$d['student_id'],(int)$d['course_id'],(float)($d['amount']??0),(int)($d['pay_status']??0),$id]); json_response(0,'updated');
    }
    if($m==='DELETE'){
        $id=(int)($_GET['id']??0); if($id<=0) json_response(400,'id非法',null,400); $stmt=$pdo->prepare('DELETE FROM oa_order WHERE id=?'); $stmt->execute([$id]); json_response(0,'deleted');
    }
    json_response(405,'method not allowed',null,405);
} catch (Throwable $e) { json_response(500,'服务异常：'.$e->getMessage(),null,500);} 
