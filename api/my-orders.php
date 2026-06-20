<?php
/* ════════════════════════════════════════════════════════════════
   api/my-orders.php — Returns all orders for the logged-in user.
   Requires a valid customer session token.
════════════════════════════════════════════════════════════════ */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') respondError('Method not allowed.', 405);
$user = requireAuth();

try {
    $db = getDB();

    /* ── Check optional columns added by migrations ── */
    $expiryCol    = '';
    $rejectionCol = '';
    try {
        $chk = $db->query("SHOW COLUMNS FROM orders LIKE 'reservation_expires_at'");
        if ($chk->rowCount() > 0) $expiryCol = ', o.reservation_expires_at';
    } catch (Throwable $e) { /* skip */ }
    try {
        $chk = $db->query("SHOW COLUMNS FROM orders LIKE 'rejection_reason'");
        if ($chk->rowCount() > 0) $rejectionCol = ', o.rejection_reason';
    } catch (Throwable $e) { /* skip */ }

    /* ── Fetch all orders for this user ──────────────────────────────────── */
    $stmt = $db->prepare(
        "SELECT o.order_id, o.total_amount, o.status AS order_status,
                o.date_ordered $expiryCol $rejectionCol,
                py.payment_method, py.reference_number,
                py.status AS payment_status, py.proof_image, py.date_paid,
                r.receipt_number, r.generated_at AS receipt_generated_at
         FROM orders o
         LEFT JOIN payments py ON py.order_id = o.order_id
         LEFT JOIN receipts r  ON r.order_id  = o.order_id
         WHERE o.user_id = ?
         ORDER BY o.date_ordered DESC"
    );
    $stmt->execute([$user['user_id']]);
    $orderRows = $stmt->fetchAll();

    if (empty($orderRows)) { respond([]); }

    /* ── Fetch order items for all orders in one query ───────────────────── */
    /* array_values() ensures sequential keys — required for PDO positional ? binding */
    $orderIds = array_values(array_column($orderRows, 'order_id'));
    $in       = implode(',', array_fill(0, count($orderIds), '?'));

    $itemStmt = $db->prepare(
        "SELECT oi.order_id, oi.product_id, oi.price,
                p.name AS product_name, p.cover_index,
                s.label AS size_label
         FROM order_items oi
         JOIN products p ON p.product_id = oi.product_id
         LEFT JOIN sizes s ON s.size_id = p.size_id
         WHERE oi.order_id IN ($in)
         ORDER BY oi.item_id ASC"
    );
    $itemStmt->execute($orderIds);
    $itemRows = $itemStmt->fetchAll();

    /* ── Fetch product images ─────────────────────────────────────────────── */
    /* array_unique() preserves original keys → array_values() reindexes them */
    $productIds = array_values(array_unique(array_column($itemRows, 'product_id')));
    $imgMap     = [];

    if (!empty($productIds)) {
        $inP     = implode(',', array_fill(0, count($productIds), '?'));
        $imgStmt = $db->prepare(
            "SELECT product_id, image_url, sort_order
             FROM product_images
             WHERE product_id IN ($inP)
             ORDER BY sort_order ASC"
        );
        $imgStmt->execute($productIds);
        foreach ($imgStmt->fetchAll() as $img) {
            $imgMap[(int)$img['product_id']][] = $img['image_url'];
        }
    }

    /* ── Group items by order ─────────────────────────────────────────────── */
    $itemMap = [];
    foreach ($itemRows as $item) {
        $pid   = (int)$item['product_id'];
        $imgs  = $imgMap[$pid] ?? [];
        $cover = (int)($item['cover_index'] ?? 0);
        $image = $imgs[$cover] ?? ($imgs[0] ?? '');
        $itemMap[$item['order_id']][] = [
            'product_id' => $pid,
            'name'       => $item['product_name'],
            'size'       => $item['size_label'] ?? '—',
            'price'      => (float)$item['price'],
            'image'      => $image,
        ];
    }

    /* ── Build response ───────────────────────────────────────────────────── */
    $result = array_map(function($o) use ($itemMap) {
        return [
            'id'                   => (int)$o['order_id'],
            'orderStatus'          => $o['order_status'],
            'dateOrdered'          => $o['date_ordered'],
            'totalAmount'          => (float)$o['total_amount'],
            'reservationExpiresAt' => $o['reservation_expires_at'] ?? null,
            'rejectionReason'      => $o['rejection_reason'] ?? null,
            'products'             => $itemMap[$o['order_id']] ?? [],
            'payment' => [
                'method'          => $o['payment_method'] ?? 'GCash',
                'referenceNumber' => $o['reference_number'] ?? '',
                'status'          => $o['payment_status'] ?? 'Pending Verification',
                'proofImage'      => $o['proof_image'] ?? '',
                'datePaid'        => $o['date_paid'] ?? '',
            ],
            'receipt' => $o['receipt_number'] ? [
                'receiptNumber' => $o['receipt_number'],
                'generatedAt'   => $o['receipt_generated_at'],
            ] : null,
        ];
    }, $orderRows);

    respond($result);

} catch (Throwable $e) {
    respondError('Failed to load orders: ' . $e->getMessage(), 500);
}
