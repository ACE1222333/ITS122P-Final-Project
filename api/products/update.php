<?php
/* ════════════════════════════════════════════════════════════════
   api/products/update.php — Update an existing product (admin)
════════════════════════════════════════════════════════════════ */
require_once dirname(__DIR__) . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respondError('Method not allowed.', 405);
requireAdmin();

$body = getJsonBody();

$productId   = isset($body['product_id']) ? (int)$body['product_id'] : 0;
$name        = trim($body['name']        ?? '');
$desc        = trim($body['desc']        ?? '');
$price       = isset($body['price'])       ? (float)$body['price']       : null;
$category_id = isset($body['category_id']) && $body['category_id'] !== '' ? (int)$body['category_id'] : null;
$size_id     = isset($body['size_id'])     && $body['size_id'] !== ''     ? (int)$body['size_id']     : null;
$condition   = trim($body['condition']   ?? '');
$status      = trim($body['status']      ?? 'available');
$featured    = !empty($body['featured']) ? 1 : 0;
$cover_index = isset($body['cover_index']) ? (int)$body['cover_index'] : 0;
$images      = isset($body['images']) && is_array($body['images']) ? $body['images'] : [];

if ($productId <= 0)                                                         respondError('Valid product_id is required.');
if ($name === '')                                                             respondError('Product name is required.');
if ($price === null || $price < 0)                                           respondError('Valid price is required.');
if (!in_array($condition, ['Like New','Excellent','Good','Fair']))            respondError('Invalid condition value.');
if (!in_array($status, ['available','sold']))                                 respondError('Invalid status value.');

$db = getDB();

/* Verify product exists */
$check = $db->prepare('SELECT product_id FROM products WHERE product_id = ?');
$check->execute([$productId]);
if (!$check->fetch()) respondError('Product not found.', 404);

$upd = $db->prepare(
    'UPDATE products SET category_id=?, size_id=?, name=?, description=?, price=?,
     `condition`=?, status=?, featured=?, cover_index=? WHERE product_id=?'
);
$upd->execute([$category_id, $size_id, $name, $desc, $price, $condition, $status, $featured, $cover_index, $productId]);

/* Replace images */
$db->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$productId]);
if ($images) {
    $imgStmt = $db->prepare('INSERT INTO product_images (product_id, image_url, sort_order) VALUES (?, ?, ?)');
    foreach ($images as $idx => $url) {
        if (is_string($url) && $url !== '') {
            $imgStmt->execute([$productId, $url, $idx]);
        }
    }
}

/* Return updated product */
$sel = $db->prepare(
    'SELECT p.*, c.name AS category_name, s.label AS size_label
     FROM products p
     LEFT JOIN categories c ON c.category_id = p.category_id
     LEFT JOIN sizes      s ON s.size_id      = p.size_id
     WHERE p.product_id = ?'
);
$sel->execute([$productId]);
$p = $sel->fetch();

$imgSel = $db->prepare('SELECT image_url FROM product_images WHERE product_id = ? ORDER BY sort_order ASC');
$imgSel->execute([$productId]);
$imgs = array_column($imgSel->fetchAll(), 'image_url');

respond([
    'success' => true,
    'product' => [
        'id'          => $productId,
        'product_id'  => $productId,
        'name'        => $p['name'],
        'desc'        => $p['description'],
        'price'       => (float)$p['price'],
        'condition'   => $p['condition'],
        'status'      => $p['status'],
        'featured'    => (bool)$p['featured'],
        'cover_index' => (int)$p['cover_index'],
        'coverIndex'  => (int)$p['cover_index'],
        'category_id' => $p['category_id'] ? (int)$p['category_id'] : null,
        'category'    => $p['category_name'] ?? '',
        'size_id'     => $p['size_id'] ? (int)$p['size_id'] : null,
        'size'        => $p['size_label'] ?? '',
        'images'      => $imgs,
        'image'       => $imgs[0] ?? '',
        'date_added'  => $p['date_added'],
    ],
]);
