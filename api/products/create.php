<?php
/* ════════════════════════════════════════════════════════════════
   api/products/create.php — Create a new product (admin)
════════════════════════════════════════════════════════════════ */
require_once dirname(__DIR__) . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respondError('Method not allowed.', 405);
requireAdmin();

$body = getJsonBody();

$name         = trim($body['name']        ?? '');
$desc         = trim($body['desc']        ?? '');
$price        = isset($body['price'])       ? (float)$body['price']   : null;
$category_ids = isset($body['category_ids']) && is_array($body['category_ids'])
                ? array_values(array_unique(array_filter(array_map('intval', $body['category_ids']))))
                : [];
$size_id      = isset($body['size_id']) && $body['size_id'] !== '' ? (int)$body['size_id'] : null;
$condition    = trim($body['condition']   ?? '');
$featured     = !empty($body['featured']) ? 1 : 0;
$cover_index  = isset($body['cover_index']) ? (int)$body['cover_index'] : 0;
$images       = isset($body['images']) && is_array($body['images']) ? $body['images'] : [];

if ($name === '')    respondError('Product name is required.');
if ($price === null || $price < 0) respondError('Valid price is required.');
if (!in_array($condition, ['Brand new','Like new','Lightly used','Well used','Heavily used'])) respondError('Invalid condition value.');

$db = getDB();
$db->beginTransaction();

try {
    $stmt = $db->prepare(
        'INSERT INTO products (size_id, name, description, price, `condition`, status, featured, cover_index)
         VALUES (?, ?, ?, ?, ?, \'available\', ?, ?)'
    );
    $stmt->execute([$size_id, $name, $desc, $price, $condition, $featured, $cover_index]);
    $productId = (int)$db->lastInsertId();

    /* Insert category links */
    if ($category_ids) {
        $catStmt = $db->prepare('INSERT IGNORE INTO product_categories (product_id, category_id) VALUES (?, ?)');
        foreach ($category_ids as $cid) {
            $catStmt->execute([$productId, $cid]);
        }
    }

    /* Insert images */
    if ($images) {
        $imgStmt = $db->prepare('INSERT INTO product_images (product_id, image_url, sort_order) VALUES (?, ?, ?)');
        foreach ($images as $idx => $url) {
            if (is_string($url) && $url !== '') $imgStmt->execute([$productId, $url, $idx]);
        }
    }

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    respondError('Failed to create product: ' . $e->getMessage(), 500);
}

/* Return full product object */
$p = $db->prepare(
    'SELECT p.*, s.label AS size_label FROM products p LEFT JOIN sizes s ON s.size_id = p.size_id WHERE p.product_id = ?'
);
$p->execute([$productId]);
$p = $p->fetch();

$cats = $db->prepare(
    'SELECT c.category_id, c.name FROM product_categories pc JOIN categories c ON c.category_id = pc.category_id WHERE pc.product_id = ? ORDER BY c.name ASC'
);
$cats->execute([$productId]);
$categories = $cats->fetchAll();

$imgs = array_column(
    $db->prepare('SELECT image_url FROM product_images WHERE product_id = ? ORDER BY sort_order ASC')->execute([$productId]) ? [] : [],
    'image_url'
);
$imgSel = $db->prepare('SELECT image_url FROM product_images WHERE product_id = ? ORDER BY sort_order ASC');
$imgSel->execute([$productId]);
$imgs = array_column($imgSel->fetchAll(), 'image_url');

$firstCat = $categories[0] ?? null;
respond([
    'success' => true,
    'product' => [
        'id'           => $productId,
        'product_id'   => $productId,
        'name'         => $p['name'],
        'desc'         => $p['description'],
        'price'        => (float)$p['price'],
        'condition'    => $p['condition'],
        'status'       => $p['status'],
        'featured'     => (bool)$p['featured'],
        'cover_index'  => (int)$p['cover_index'],
        'coverIndex'   => (int)$p['cover_index'],
        'categories'   => array_map(fn($c) => ['id' => (int)$c['category_id'], 'name' => $c['name']], $categories),
        'category_ids' => array_column($categories, 'category_id'),
        'category_id'  => $firstCat ? (int)$firstCat['category_id'] : null,
        'category'     => implode(', ', array_column($categories, 'name')),
        'size_id'      => $p['size_id'] ? (int)$p['size_id'] : null,
        'size'         => $p['size_label'] ?? '',
        'images'       => $imgs,
        'image'        => $imgs[0] ?? '',
        'date_added'   => $p['date_added'],
    ],
]);
