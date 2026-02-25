<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/db.php';

try {
    $pdo=get_db_connection(); $m=$_SERVER['REQUEST_METHOD'];
    if($m==='GET'){
        $stmt=$pdo->query('SELECT id,record_type,item_name,amount,record_date,remark FROM oa_finance_record ORDER BY record_date DESC,id DESC');
        $rows=$stmt->fetchAll();
        $sumIn=$pdo->query("SELECT IFNULL(SUM(amount),0) FROM oa_finance_record WHERE record_type='income'")->fetchColumn();
        $sumOut=$pdo->query("SELECT IFNULL(SUM(amount),0) FROM oa_finance_record WHERE record_type='expense'")->fetchColumn();
        json_response(0,'ok',['list'=>$rows,'summary'=>['income'=>(float)$sumIn,'expense'=>(float)$sumOut,'balance'=>(float)$sumIn-(float)$sumOut]]);
    }
    if($m==='POST'){
        $d=request_body(); require_fields($d,['record_type','item_name','amount','record_date']);
        $stmt=$pdo->prepare('INSERT INTO oa_finance_record(record_type,item_name,amount,record_date,remark) VALUES(?,?,?,?,?)');
        $stmt->execute([trim($d['record_type']),trim($d['item_name']),(float)$d['amount'],trim($d['record_date']),trim((string)($d['remark']??''))]); json_response(0,'created',['id'=>(int)$pdo->lastInsertId()]);
    }
    if($m==='DELETE'){
        $id=(int)($_GET['id']??0); if($id<=0) json_response(400,'id非法',null,400);
        $stmt=$pdo->prepare('DELETE FROM oa_finance_record WHERE id=?'); $stmt->execute([$id]); json_response(0,'deleted');
    }
    json_response(405,'method not allowed',null,405);
} catch (Throwable $e) { json_response(500,'服务异常：'.$e->getMessage(),null,500);} 
