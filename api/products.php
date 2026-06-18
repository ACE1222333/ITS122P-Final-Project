<?php
/* ════════════════════════════════════════════════════════════════
   api/products.php — Public product listing (GET)
════════════════════════════════════════════════════════════════ */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') respondError('Method not allowed.', 405);

try {
    $db = getDB();

    /* ── Auto-release expired reservations ────────────────────────
       If the admin has not reviewed an order within 48 hours the
       product is released back to 'available' so other buyers can
       purchase it.  The order is marked 'Cancelled' automatically.
    ────────────────────────────────────────────────────────────── */
    $db->exec(
        "UPDATE orders
         SET status = 'Cancelled'
         WHERE status IN ('Pending Verification', 'Pending Payment')
           AND reservation_expires_at IS NOT NULL
           AND reservation_expires_at < NOW()"
    );

    /* Release any reserved products whose only active orders are now cancelled */
    $db->exec(
        "UPDATE products
         SET status = 'available'
         WHERE status = 'reserved'
           AND product_id NOT IN (
               SELECT DISTINCT oi.product_id
               FROM order_items oi
               INNER JOIN orders o ON o.order_id = oi.order_id
               WHERE o.status NOT IN ('Cancelled','Rejected','Completed')
           )"
    );

    /* Admins see all statuses (available, reserved, sold).
       Public / shop users see only 'available' products —
       reserved and sold items are hidden entirely. */
    $viewer  = getAuthUser();
    $isAdmin = ($viewer !== null && $viewer['role'] === 'admin');

    $sql = $isAdmin
        ? 'SELECT p.*, c.name AS category_name, s.label AS size_label
           FROM products p
           LEFT JOIN categories c ON c.category_id = p.category_id
           LEFT JOIN sizes      s ON s.size_id      = p.size_id
           ORDER BY p.date_added DESC'
        : "SELECT p.*, c.name AS category_name, s.label AS size_label
           FROM products p
           LEFT JOIN categories c ON c.category_id = p.category_id
           LEFT JOIN sizes      s ON s.size_id      = p.size_id
           WHERE p.status = 'available'
           ORDER BY p.date_added DESC";

    $rows = $db->query($sql)->fetchAll();

    /* Fetch all images in one query, indexed by product_id */
    $allImgs = $db->query(
        'SELECT product_id, image_url, sort_order FROM product_images ORDER BY sort_order ASC'
    )->fetchAll();

    $imgMap = [];
    foreach ($allImgs as $img) {
        $imgMap[(int) $img['product_id']][] = $img['image_url'];
    }

    $products = [];
    foreach ($rows as $p) {
        $pid    = (int) $p['product_id'];
        $images = $imgMap[$pid] ?? [];
        $products[] = [
            'id'          => $pid,
            'product_id'  => $pid,
            'name'        => $p['name'],
            'desc'        => $p['description'] ?? '',
            'price'       => (float) $p['price'],
            'condition'   => $p['condition'],
            'status'      => $p['status'],
            'featured'    => (bool) $p['featured'],
            'cover_index' => (int) $p['cover_index'],
            'coverIndex'  => (int) $p['cover_index'],
            'category_id' => $p['category_id'] !== null ? (int) $p['category_id'] : null,
            'category'    => $p['category_name'] ?? '',
            'size_id'     => $p['size_id'] !== null ? (int) $p['size_id'] : null,
            'size'        => $p['size_label'] ?? '',
            'images'      => $images,
            'image'       => $images[0] ?? '',
            'date_added'  => $p['date_added'] ?? '',
        ];
    }

    respond($products);

} catch (Throwable $e) {
    respondError('Failed to load products: ' . $e->getMessage(), 500);
}
