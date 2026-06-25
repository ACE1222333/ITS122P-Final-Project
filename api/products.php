<?php
/* ════════════════════════════════════════════════════════════════
   api/products.php — Public product listing (GET)
════════════════════════════════════════════════════════════════ */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') respondError('Method not allowed.', 405);

/* Prevent browsers from caching the product list — statuses change in real time */
header('Cache-Control: no-store, no-cache, must-revalidate');

try {
    $db = getDB();

    /* ── Auto-release expired reservations ─────────────────────── */
    $db->exec(
        "UPDATE orders
         SET status = 'Cancelled'
         WHERE status IN ('Payment Verification','Pending Verification','Pending Payment')
           AND reservation_expires_at IS NOT NULL
           AND reservation_expires_at < NOW()"
    );

    $db->exec(
        "UPDATE products
         SET status = 'available'
         WHERE status = 'reserved'
           AND product_id NOT IN (
               SELECT DISTINCT oi.product_id
               FROM order_items oi
               INNER JOIN orders o ON o.order_id = oi.order_id
               WHERE o.status NOT IN ('Cancelled','Payment Rejected','Rejected','Completed')
           )"
    );

    /* ── Admin vs public filter ─────────────────────────────────── */
    $token   = getRequestToken();
    $viewer  = ($token !== '') ? getAuthUser() : null;
    $isAdmin = ($viewer !== null && $viewer['role'] === 'admin');

    $where = $isAdmin ? '' : "WHERE p.status = 'available'";

    $sql = "SELECT p.*, s.label AS size_label
            FROM products p
            LEFT JOIN sizes s ON s.size_id = p.size_id
            $where
            ORDER BY p.date_added DESC";

    $rows = $db->query($sql)->fetchAll();

    if (empty($rows)) { respond([]); }

    /* ── Fetch all images ────────────────────────────────────────── */
    $allImgs = $db->query(
        'SELECT product_id, image_url, sort_order FROM product_images ORDER BY sort_order ASC'
    )->fetchAll();
    $imgMap = [];
    foreach ($allImgs as $img) {
        $imgMap[(int)$img['product_id']][] = $img['image_url'];
    }

    /* ── Fetch all categories via junction table ─────────────────── */
    $catRows = $db->query(
        'SELECT pc.product_id, c.category_id, c.name
         FROM product_categories pc
         JOIN categories c ON c.category_id = pc.category_id
         ORDER BY c.name ASC'
    )->fetchAll();
    $catMap = [];
    foreach ($catRows as $cr) {
        $catMap[(int)$cr['product_id']][] = ['id' => (int)$cr['category_id'], 'name' => $cr['name']];
    }

    /* ── Build response ──────────────────────────────────────────── */
    $products = [];
    foreach ($rows as $p) {
        $pid        = (int)$p['product_id'];
        $images     = $imgMap[$pid] ?? [];
        $categories = $catMap[$pid] ?? [];

        /* Backward-compat single-value fields */
        $firstCat   = $categories[0] ?? null;

        $products[] = [
            'id'           => $pid,
            'product_id'   => $pid,
            'name'         => $p['name'],
            'desc'         => $p['description'] ?? '',
            'price'        => (float)$p['price'],
            'condition'    => $p['condition'],
            'status'       => $p['status'],
            'featured'     => (bool)$p['featured'],
            'cover_index'  => (int)$p['cover_index'],
            'coverIndex'   => (int)$p['cover_index'],
            'categories'   => $categories,                           /* [{id, name}, …] */
            'category_ids' => array_column($categories, 'id'),      /* [id, …] */
            'category_id'  => $firstCat ? $firstCat['id']   : null, /* compat */
            'category'     => implode(', ', array_column($categories, 'name')), /* compat */
            'size_id'      => $p['size_id'] !== null ? (int)$p['size_id'] : null,
            'size'         => $p['size_label'] ?? '',
            'images'       => $images,
            'image'        => $images[0] ?? '',
            'date_added'   => $p['date_added'] ?? '',
        ];
    }

    respond($products);

} catch (Throwable $e) {
    respondError('Failed to load products: ' . $e->getMessage(), 500);
}
