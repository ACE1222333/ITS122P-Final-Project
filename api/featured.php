<?php
/* ════════════════════════════════════════════════════════════════
   api/featured.php
     GET  — public list of up to 4 featured products
     POST — toggle featured flag (admin)
════════════════════════════════════════════════════════════════ */
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];

/* ══════════════ GET ════════════════════════════════════════ */
if ($method === 'GET') {
    $db = getDB();

    /* Only available products appear in the featured section on the shop */
    $stmt = $db->query(
        "SELECT p.*, c.name AS category_name, s.label AS size_label
         FROM products p
         LEFT JOIN categories c ON c.category_id = p.category_id
         LEFT JOIN sizes      s ON s.size_id      = p.size_id
         WHERE p.featured = 1 AND p.status = 'available'
         ORDER BY p.product_id ASC
         LIMIT 4"
    );
    $rows = $stmt->fetchAll();

    if (!$rows) { respond([]); }

    /* Fetch images for these products */
    $ids    = array_column($rows, 'product_id');
    $in     = implode(',', array_fill(0, count($ids), '?'));
    $imgSel = $db->prepare("SELECT product_id, image_url FROM product_images WHERE product_id IN ($in) ORDER BY sort_order ASC");
    $imgSel->execute($ids);
    $allImgs = $imgSel->fetchAll();

    $imgMap = [];
    foreach ($allImgs as $img) {
        $imgMap[$img['product_id']][] = $img['image_url'];
    }

    $products = array_map(function($p) use ($imgMap) {
        $pid    = (int)$p['product_id'];
        $images = $imgMap[$pid] ?? [];
        return [
            'id'          => $pid,
            'product_id'  => $pid,
            'name'        => $p['name'],
            'desc'        => $p['description'],
            'price'       => (float)$p['price'],
            'condition'   => $p['condition'],
            'status'      => $p['status'],
            'featured'    => true,
            'cover_index' => (int)$p['cover_index'],
            'coverIndex'  => (int)$p['cover_index'],
            'category_id' => $p['category_id'] ? (int)$p['category_id'] : null,
            'category'    => $p['category_name'] ?? '',
            'size_id'     => $p['size_id'] ? (int)$p['size_id'] : null,
            'size'        => $p['size_label'] ?? '',
            'images'      => $images,
            'image'       => $images[0] ?? '',
            'date_added'  => $p['date_added'],
        ];
    }, $rows);

    respond($products);
}

/* ══════════════ POST ═══════════════════════════════════════ */
if ($method === 'POST') {
    requireAdmin();
    $body      = getJsonBody();
    $productId = isset($body['product_id']) ? (int)$body['product_id'] : 0;
    $featured  = !empty($body['featured']);

    if ($productId <= 0) respondError('Valid product_id is required.');

    $db = getDB();

    /* Verify product exists */
    $chk = $db->prepare('SELECT product_id FROM products WHERE product_id = ?');
    $chk->execute([$productId]);
    if (!$chk->fetch()) respondError('Product not found.', 404);

    /* If setting featured=true, enforce max 4 */
    if ($featured) {
        $cnt = (int)$db->query('SELECT COUNT(*) FROM products WHERE featured = 1')->fetchColumn();
        if ($cnt >= 4) {
            respondError('Maximum 4 featured products allowed. Remove one first.');
        }
    }

    $db->prepare('UPDATE products SET featured = ? WHERE product_id = ?')->execute([$featured ? 1 : 0, $productId]);

    respond(['success' => true]);
}

respondError('Method not allowed.', 405);
