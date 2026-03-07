<?php
require_once __DIR__ . '/common.php';

function crud_list(PDO $pdo, string $table, string $orderBy = 'id DESC'): array
{
    $stmt = $pdo->query("SELECT * FROM {$table} ORDER BY {$orderBy}");
    return $stmt->fetchAll();
}

function crud_insert(PDO $pdo, string $table, array $fields, array $data): int
{
    $cols = [];
    $holders = [];
    $vals = [];
    foreach ($fields as $f) {
        if (array_key_exists($f, $data)) {
            $cols[] = $f;
            $holders[] = '?';
            $vals[] = $data[$f];
        }
    }
    if (empty($cols)) {
        throw new RuntimeException('没有可写入字段');
    }
    $sql = 'INSERT INTO ' . $table . '(' . implode(',', $cols) . ') VALUES(' . implode(',', $holders) . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($vals);
    return (int)$pdo->lastInsertId();
}

function crud_update(PDO $pdo, string $table, int $id, array $fields, array $data): void
{
    $sets = [];
    $vals = [];
    foreach ($fields as $f) {
        if (array_key_exists($f, $data)) {
            $sets[] = $f . '=?';
            $vals[] = $data[$f];
        }
    }
    if (empty($sets)) {
        return;
    }
    $vals[] = $id;
    $sql = 'UPDATE ' . $table . ' SET ' . implode(',', $sets) . ' WHERE id=?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($vals);
}

function crud_delete(PDO $pdo, string $table, int $id): void
{
    $stmt = $pdo->prepare('DELETE FROM ' . $table . ' WHERE id=?');
    $stmt->execute([$id]);
}
