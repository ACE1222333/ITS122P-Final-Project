<?php
/* ════════════════════════════════════════════════════════════════
   api/receipts.php — Fetch receipt details for a specific order.
   GET ?order_id=<id>  — requires a valid customer session.
   Only returns the receipt if the order belongs to the logged-in user.
════════════════════════════════════════════════════════════════ */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') respondError('Method not allowed.', 405);
$user    = requireAuth();
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($orderId <= 0) respondError('Valid order_id is required.');

try {
    $db = getDB();

    /* Verify the order belongs to this user and fetch core data */
    $stmt = $db->prepare(
        "SELECT o.order_id, o.total_amount, o.status AS order_status,
                o.date_ordered, u.address,
                py.payment_method, py.reference_number,
                py.status AS payment_status, py.date_paid, py.amount AS payment_amount,
                r.receipt_number, r.generated_at AS receipt_generated_at
         FROM orders o
         LEFT JOIN users    u  ON u.user_id   = o.user_id
         LEFT JOIN payments py ON py.order_id = o.order_id
         LEFT JOIN receipts r  ON r.order_id  = o.order_id
         WHERE o.order_id = ? AND o.user_id = ?
         LIMIT 1"
    );
    $stmt->execute([$orderId, $user['user_id']]);
    $order = $stmt->fetch();

    if (!$order)           respondError('Order not found.', 404);
    if (!$order['receipt_number']) respondError('No receipt available for this order.', 404);

    /* Fetch items */
    $itemStmt = $db->prepare(
        "SELECT oi.product_id, oi.price, p.name AS product_name, s.label AS size_label
         FROM order_items oi
         JOIN products p   ON p.product_id = oi.product_id
         LEFT JOIN sizes s ON s.size_id    = p.size_id
         WHERE oi.order_id = ?
         ORDER BY oi.item_id ASC"
    );
    $itemStmt->execute([$orderId]);
    $items = $itemStmt->fetchAll();

    /* Shipping fee: total_amount minus sum of item prices */
    $itemsSubtotal = array_sum(array_column($items, 'price'));
    $shippingFee   = round((float)$order['total_amount'] - $itemsSubtotal, 2);

    respond([
        'receiptNumber'  => $order['receipt_number'],
        'generatedAt'    => $order['receipt_generated_at'],
        'orderId'        => (int)$order['order_id'],
        'orderStatus'    => $order['order_status'],
        'dateOrdered'    => $order['date_ordered'],
        'deliveryAddress' => $order['address'] ?? '',
        'subtotal'       => round($itemsSubtotal, 2),
        'shippingFee'    => $shippingFee,
        'totalAmount'    => (float)$order['total_amount'],
        'payment' => [
            'method'          => $order['payment_method'] ?? 'GCash',
            'referenceNumber' => $order['reference_number'] ?? '',
            'status'          => $order['payment_status'] ?? '',
            'datePaid'        => $order['date_paid'] ?? '',
        ],
        'items' => array_map(fn($i) => [
            'productId' => (int)$i['product_id'],
            'name'      => $i['product_name'],
            'size'      => $i['size_label'] ?? '—',
            'price'     => (float)$i['price'],
            'quantity'  => 1,
        ], $items),
    ]);

} catch (Throwable $e) {
    respondError('Failed to load receipt: ' . $e->getMessage(), 500);
}
