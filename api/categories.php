<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') respondError('Method not allowed.', 405);

try {
    $rows = getDB()->query(
        'SELECT category_id, name, description FROM categories ORDER BY name ASC'
    )->fetchAll();

    respond(array_map(fn($r) => [
        'category_id' => (int) $r['category_id'],
        'name'        => $r['name'],
        'description' => $r['description'] ?? '',
    ], $rows));

} catch (Throwable $e) {
    respondError('Failed to load categories: ' . $e->getMessage(), 500);
}
