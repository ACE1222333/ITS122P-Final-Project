<?php
/* ════════════════════════════════════════════════════════════════
   api/products/delete.php — Delete or soft-retire a product (admin)
════════════════════════════════════════════════════════════════ */
require_once dirname(__DIR__) . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respondError('Method not allowed.', 405);
requireAdmin();

$body      = getJsonBody();
$productId = isset($body['product_id']) ? (int)$body['product_id'] : 0;

if ($productId <= 0) respondError('Valid product_id is required.');

$db = getDB();

/* Check if product exists */
$check = $db->prepare('SELECT product_id, name FROM products WHERE product_id = ?');
$check->execute([$productId]);
$prod = $check->fetch();
if (!$prod) respondError('Product not found.', 404);

/* Check if referenced in order_items (RESTRICT FK) */
$refCheck = $db->prepare('SELECT COUNT(*) AS cnt FROM order_items WHERE product_id = ?');
$refCheck->execute([$productId]);
$refCount = (int)$refCheck->fetch()['cnt'];

if ($refCount > 0) {
    /* Soft-delete: mark as sold instead */
    $db->prepare('UPDATE products SET status = \'sold\' WHERE product_id = ?')->execute([$productId]);
    respond([
        'success' => true,
        'message' => "Product is part of existing orders and cannot be deleted. It has been marked as sold instead.",
        'action'  => 'marked_sold',
    ]);
}

/* Hard delete (cascades to product_images) */
$db->prepare('DELETE FROM products WHERE product_id = ?')->execute([$productId]);

respond([
    'success' => true,
    'message' => "Product deleted successfully.",
    'action'  => 'deleted',
]);
