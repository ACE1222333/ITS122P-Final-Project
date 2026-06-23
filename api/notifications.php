<?php
/* ════════════════════════════════════════════════════════════════
   api/notifications.php  (admin only)
   GET — returns unread notification counts + recent items.
════════════════════════════════════════════════════════════════ */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') respondError('Method not allowed.', 405);
requireAdmin();

try {
    $db = getDB();

    /* ── Pending payments ───────────────────────────────────── */
    $pending_payments = (int) $db->query(
        "SELECT COUNT(*) FROM payments WHERE status = 'Pending Verification'"
    )->fetchColumn();

    /* ── Unanswered reviews ─────────────────────────────────── */
    $pending_reviews = (int) $db->query(
        "SELECT COUNT(*) FROM reviews WHERE admin_reply IS NULL OR admin_reply = ''"
    )->fetchColumn();

    /* ── Recent pending payment items ───────────────────────── */
    $pay_rows = $db->query(
        "SELECT py.payment_id, py.date_paid, py.amount,
                u.first_name, u.last_name
         FROM payments py
         JOIN orders o ON o.order_id = py.order_id
         JOIN users  u ON u.user_id  = o.user_id
         WHERE py.status = 'Pending Verification'
         ORDER BY py.date_paid DESC
         LIMIT 10"
    )->fetchAll();

    /* ── Recent unanswered reviews ──────────────────────────── */
    $rev_rows = $db->query(
        "SELECT r.review_id, r.date_reviewed, r.rating, r.comment,
                u.first_name, u.last_name
         FROM reviews r
         JOIN users u ON u.user_id = r.user_id
         WHERE r.admin_reply IS NULL OR r.admin_reply = ''
         ORDER BY r.date_reviewed DESC
         LIMIT 10"
    )->fetchAll();

    $items = [];

    foreach ($pay_rows as $r) {
        $items[] = [
            'id'         => 'pay_' . $r['payment_id'],
            'type'       => 'payment',
            'title'      => 'Payment Submitted',
            'body'       => trim($r['first_name'] . ' ' . $r['last_name']) . ' — ₱' . number_format((float)$r['amount'], 2),
            'href'       => 'admin-payments.php',
            'created_at' => strtotime($r['date_paid']),
        ];
    }

    foreach ($rev_rows as $r) {
        $snippet = mb_strlen($r['comment']) > 60
            ? mb_substr($r['comment'], 0, 60) . '…'
            : $r['comment'];
        $items[] = [
            'id'         => 'rev_' . $r['review_id'],
            'type'       => 'review',
            'title'      => 'New Review (' . $r['rating'] . '★)',
            'body'       => trim($r['first_name'] . ' ' . $r['last_name']) . ': "' . $snippet . '"',
            'href'       => 'admin-reviews.php',
            'created_at' => strtotime($r['date_reviewed']),
        ];
    }

    /* Sort newest first */
    usort($items, fn($a, $b) => $b['created_at'] - $a['created_at']);
    $items = array_slice($items, 0, 15);

    while (ob_get_level() > 0) ob_end_clean();
    echo json_encode([
        'pending_payments' => $pending_payments,
        'pending_reviews'  => $pending_reviews,
        'total'            => $pending_payments + $pending_reviews,
        'items'            => $items,
    ]);

} catch (Throwable $e) {
    respondError('Failed to load notifications: ' . $e->getMessage());
}
