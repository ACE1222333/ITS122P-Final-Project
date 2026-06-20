<?php
/* ════════════════════════════════════════════════════════════════
   api/dashboard.php — Admin dashboard statistics
   Returns counts, sums, and averages for the dashboard.
════════════════════════════════════════════════════════════════ */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') respondError('Method not allowed.', 405);
requireAdmin();

try {
    $db = getDB();

    /* ── Products ─────────────────────────────────────────────── */
    $products = $db->query(
        "SELECT
           COUNT(*)                                     AS total,
           SUM(status = 'available')                    AS available,
           SUM(status = 'reserved')                     AS reserved,
           SUM(status = 'sold')                         AS sold,
           SUM(featured = 1)                            AS featured
         FROM products"
    )->fetch();

    /* ── Orders ───────────────────────────────────────────────── */
    $orders = $db->query(
        "SELECT
           COUNT(*)                                          AS total,
           SUM(status IN ('Payment Verification','Pending Verification','Pending Payment')) AS pending_verification,
           SUM(status = 'Payment Accepted')  AS payment_accepted,
           SUM(status = 'Payment Rejected')  AS payment_rejected,
           SUM(status = 'Pending Payment')                  AS pending_payment,
           SUM(status = 'Processing')                       AS processing,
           SUM(status IN ('Shipping','Shipped'))            AS in_transit,
           SUM(status = 'Completed')                        AS completed,
           SUM(status = 'Rejected')                         AS rejected,
           SUM(status = 'Cancelled')                        AS cancelled
         FROM orders"
    )->fetch();

    /* ── Revenue (completed orders only) ──────────────────────── */
    $revenue = $db->query(
        "SELECT
           COALESCE(SUM(total_amount), 0)   AS total,
           COALESCE(AVG(total_amount), 0)   AS average,
           COALESCE(MAX(total_amount), 0)   AS highest,
           COALESCE(MIN(total_amount), 0)   AS lowest
         FROM orders
         WHERE status = 'Completed'"
    )->fetch();

    /* ── Reviews ──────────────────────────────────────────────── */
    $reviews = $db->query(
        "SELECT
           COUNT(*)              AS total,
           COALESCE(AVG(rating), 0)  AS average_rating,
           SUM(rating = 5)       AS five_star,
           SUM(rating = 4)       AS four_star,
           SUM(rating = 3)       AS three_star,
           SUM(rating <= 2)      AS low_star
         FROM reviews"
    )->fetch();

    /* ── Pending verification orders (compact list) ───────────── */
    $pending = $db->query(
        "SELECT o.order_id, o.total_amount, o.date_ordered,
                u.first_name, u.last_name,
                py.reference_number
         FROM orders o
         JOIN users u ON u.user_id = o.user_id
         LEFT JOIN payments py ON py.order_id = o.order_id
         WHERE o.status IN ('Payment Verification','Pending Verification','Pending Payment')
            OR py.status = 'Pending Verification'
         ORDER BY o.date_ordered ASC
         LIMIT 8"
    )->fetchAll();

    respond([
        'products' => [
            'total'     => (int)$products['total'],
            'available' => (int)$products['available'],
            'reserved'  => (int)$products['reserved'],
            'sold'      => (int)$products['sold'],
            'featured'  => (int)$products['featured'],
        ],
        'orders' => [
            'total'                => (int)$orders['total'],
            'pending_verification' => (int)$orders['pending_verification'],
            'pending_payment'      => (int)$orders['pending_payment'],
            'processing'           => (int)$orders['processing'],
            'in_transit'           => (int)$orders['in_transit'],
            'completed'            => (int)$orders['completed'],
            'rejected'             => (int)$orders['rejected'],
            'cancelled'            => (int)$orders['cancelled'],
        ],
        'revenue' => [
            'total'   => round((float)$revenue['total'],   2),
            'average' => round((float)$revenue['average'], 2),
            'highest' => round((float)$revenue['highest'], 2),
            'lowest'  => round((float)$revenue['lowest'],  2),
        ],
        'reviews' => [
            'total'          => (int)$reviews['total'],
            'average_rating' => round((float)$reviews['average_rating'], 1),
            'five_star'      => (int)$reviews['five_star'],
            'four_star'      => (int)$reviews['four_star'],
            'three_star'     => (int)$reviews['three_star'],
            'low_star'       => (int)$reviews['low_star'],
        ],
        'pending_orders' => array_map(fn($o) => [
            'id'               => (int)$o['order_id'],
            'customer'         => $o['first_name'] . ' ' . $o['last_name'],
            'amount'           => (float)$o['total_amount'],
            'date'             => substr($o['date_ordered'], 0, 10),
            'reference_number' => $o['reference_number'] ?? '',
        ], $pending),
    ]);

} catch (Throwable $e) {
    respondError('Dashboard error: ' . $e->getMessage(), 500);
}
