<?php
/* ════════════════════════════════════════════════════════════════
   api/categories/delete.php — Delete a category (admin only)
   Products that used this category have category_id set to NULL.
════════════════════════════════════════════════════════════════ */
require_once dirname(__DIR__) . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respondError('Method not allowed.', 405);
requireAdmin();

$body = getJsonBody();
$id   = isset($body['category_id']) ? (int) $body['category_id'] : 0;

if ($id <= 0) respondError('Valid category_id is required.');

try {
    $db = getDB();

    /* Verify it exists */
    $chk = $db->prepare('SELECT name FROM categories WHERE category_id = ? LIMIT 1');
    $chk->execute([$id]);
    $cat = $chk->fetch();
    if (!$cat) respondError('Category not found.', 404);

    /* Count how many products will be affected */
    $cntStmt = $db->prepare('SELECT COUNT(*) FROM product_categories WHERE category_id = ?');
    $cntStmt->execute([$id]);
    $affected = (int) $cntStmt->fetchColumn();

    /* Remove category from all products (junction table) */
    $db->prepare('DELETE FROM product_categories WHERE category_id = ?')->execute([$id]);

    /* Delete the category */
    $db->prepare('DELETE FROM categories WHERE category_id = ?')->execute([$id]);

    respond([
        'success'          => true,
        'deleted_name'     => $cat['name'],
        'products_updated' => $affected,
    ]);
} catch (Throwable $e) {
    respondError('Failed to delete category: ' . $e->getMessage(), 500);
}
