<?php
/* ════════════════════════════════════════════════════════════════
   api/payments.php
     GET (admin) — all orders/payments for the Payments page.
     Returns every order regardless of payment status so the admin
     can see Pending, Approved, and Rejected payments in one place.
════════════════════════════════════════════════════════════════ */
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') respondError('Method not allowed.', 405);
requireAdmin();

try {
    $db = getDB();

    /* Check for optional columns */
    $shippingCol  = '';
    $itemsJsonCol = '';
    try {
        $chk = $db->query("SHOW COLUMNS FROM orders LIKE 'shipping_fee'");
        if ($chk->rowCount() > 0) $shippingCol = ', o.shipping_fee';
    } catch (Throwable $e) {}
    try {
        $chk = $db->query("SHOW COLUMNS FROM orders LIKE 'items_json'");
        if ($chk->rowCount() > 0) $itemsJsonCol = ', o.items_json';
    } catch (Throwable $e) {}

    $rows = $db->query(
        "SELECT o.order_id, o.total_amount $shippingCol, o.status AS order_status,
                o.date_ordered, o.rejection_reason $itemsJsonCol,
                u.first_name, u.last_name, u.email, u.phone, u.address,
                py.payment_id, py.payment_method, py.reference_number,
                py.proof_image, py.status AS payment_status,
                py.amount AS payment_amount, py.date_paid
         FROM orders o
         JOIN users u ON u.user_id = o.user_id
         LEFT JOIN payments py ON py.order_id = o.order_id
         ORDER BY o.date_ordered DESC"
    )->fetchAll();

    if (empty($rows)) { respond([]); }

    /* ── Items in one query ── */
    $orderIds = array_values(array_column($rows, 'order_id'));
    $in       = implode(',', array_fill(0, count($orderIds), '?'));

    $items = $db->prepare(
        "SELECT oi.order_id, oi.product_id, oi.price,
                p.name AS product_name, p.cover_index,
                s.label AS size_label,
                pi.image_url
         FROM order_items oi
         JOIN products p ON p.product_id = oi.product_id
         LEFT JOIN sizes s ON s.size_id = p.size_id
         LEFT JOIN product_images pi
           ON pi.product_id = oi.product_id AND pi.sort_order = p.cover_index
         WHERE oi.order_id IN ($in)
         ORDER BY oi.order_id ASC"
    );
    $items->execute($orderIds);

    $itemMap = [];
    foreach ($items->fetchAll() as $item) {
        $itemMap[$item['order_id']][] = [
            'productId' => (int)$item['product_id'],
            'name'      => $item['product_name'],
            'size'      => $item['size_label'] ?? '—',
            'price'     => (float)$item['price'],
            'image'     => $item['image_url'] ?? '',
        ];
    }

    $result = array_map(function($o) use ($itemMap) {
        $oid = (int)$o['order_id'];

        /* Before approval, order_items don't exist yet — fall back to items_json */
        $products = $itemMap[$oid] ?? [];
        if (empty($products) && !empty($o['items_json'])) {
            $raw = json_decode($o['items_json'], true) ?: [];
            foreach ($raw as $item) {
                $products[] = [
                    'productId' => (int)($item['product_id'] ?? 0),
                    'name'      => $item['name'] ?? ('Product #' . ($item['product_id'] ?? '?')),
                    'size'      => $item['size'] ?? '—',
                    'price'     => (float)($item['price'] ?? 0),
                    'image'     => $item['image'] ?? '',
                ];
            }
        }

        return [
            'id'          => $oid,
            'order_id'    => $oid,
            'user'        => [
                'name'    => trim($o['first_name'] . ' ' . $o['last_name']),
                'email'   => $o['email'],
                'phone'   => $o['phone']   ?? '',
                'address' => $o['address'] ?? '',
            ],
            'products'        => $products,
            'totalAmount'     => (float)$o['total_amount'],
            'shippingFee'     => isset($o['shipping_fee']) ? (float)$o['shipping_fee'] : 0,
            'orderStatus'     => $o['order_status'],
            'rejectionReason' => $o['rejection_reason'] ?? null,
            'payment'         => [
                'method'          => $o['payment_method']   ?? 'GCash',
                'referenceNumber' => $o['reference_number'] ?? '',
                'status'          => $o['payment_status']   ?? 'Pending Verification',
                'amount'          => $o['payment_amount']
                                     ? (float)$o['payment_amount']
                                     : (float)$o['total_amount'],
                'proofImage' => $o['proof_image'] ?? '',
                'datePaid'   => $o['date_paid']   ?? '',
            ],
            'dateOrdered'          => $o['date_ordered'],
            'reservationExpiresAt' => $o['reservation_expires_at'] ?? null,
        ];
    }, $rows);

    respond($result);

} catch (Throwable $e) {
    respondError('Failed to load payments: ' . $e->getMessage(), 500);
}
