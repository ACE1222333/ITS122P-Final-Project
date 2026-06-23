<?php
/* ── Quick diagnostic — visit http://localhost/websystem/api/test.php ── */
header('Content-Type: application/json; charset=utf-8');
$out = ['php' => PHP_VERSION, 'time' => date('Y-m-d H:i:s')];

try {
    $pdo    = new PDO('mysql:host=localhost;dbname=bythebel_db;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $out['db']     = 'connected';
    $out['tables'] = $tables;

    /* Check products table columns */
    if (in_array('products', $tables)) {
        $cols = $pdo->query("DESCRIBE products")->fetchAll(PDO::FETCH_COLUMN);
        $out['products_columns'] = $cols;
        $out['products_count']   = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    }
    if (in_array('users', $tables)) {
        $out['users_count'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }
} catch (PDOException $e) {
    $out['db_error'] = $e->getMessage();
}

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
