<?php
/* ════════════════════════════════════════════════════════════════
   api/products/create.php — Create a new product (admin)
════════════════════════════════════════════════════════════════ */
require_once dirname(__DIR__) . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respondError('Method not allowed.', 405);
requireAdmin();

$body = getJsonBody();

$name        = trim($body['name']        ?? '');
$desc        = trim($body['desc']        ?? '');
$price       = isset($body['price'])       ? (float)$body['price']       : null;
$category_id = isset($body['category_id']) && $body['category_id'] !== '' ? (int)$body['category_id'] : null;
$size_id     = isset($body['size_id'])     && $body['size_id'] !== ''     ? (int)$body['size_id']     : null;
$condition   = trim($body['condition']   ?? '');
$featured    = !empty($body['featured']) ? 1 : 0;
$cover_index = isset($body['cover_index']) ? (int)$body['cover_index'] : 0;
$images      = isset($body['images']) && is_array($body['images']) ? $body['images'] : [];

if ($name === '')                                respondError('Product name is required.');
if ($price === null || $price < 0)               respondError('Valid price is required.');
if (!in_array($condition, ['Like New','Excellent','Good','Fair'])) respondError('Invalid condition value.');

$db = getDB();

$stmt = $db->prepare(
    'INSERT INTO products (category_id, size_id, name, description, price, `condition`, status, featured, cover_index)
     VALUES (?, ?, ?, ?, ?, ?, \'available\', ?, ?)'
);
$stmt->execute([$category_id, $size_id, $name, $desc, $price, $condition, $featured, $cover_index]);
$productId = (int)$db->lastInsertId();

/* Insert images */
if ($images) {
    $imgStmt = $db->prepare('INSERT INTO product_images (product_id, image_url, sort_order) VALUES (?, ?, ?)');
    foreach ($images as $idx => $url) {
        if (is_string($url) && $url !== '') {
            $imgStmt->execute([$productId, $url, $idx]);
        }
    }
}

/* Return full product object */
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
