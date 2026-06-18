<?php
/* ════════════════════════════════════════════════════════════════
   api/orders/update_status.php — Update order + payment status (admin)

   Product status is automatically managed based on payment status:
     payment → Verified  : product → sold      (confirmed sale)
     payment → Rejected  : product → available  (released back for others)
════════════════════════════════════════════════════════════════ */
require_once dirname(__DIR__) . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respondError('Method not allowed.', 405);
requireAdmin();

$body          = getJsonBody();
$orderId       = isset($body['order_id'])     ? (int)$body['order_id']         : 0;
$orderStatus   = trim($body['order_status']   ?? '');
$paymentStatus = trim($body['payment_status'] ?? '');

if ($orderId <= 0) respondError('Valid order_id is required.');

$validOrderStatuses = [
    'Pending Verification','Processing','Shipping','Shipped',
    'Completed','Rejected','Cancelled',
];
$validPaymentStatuses = ['Pending Verification','Verified','Rejected'];

if ($orderStatus   !== '' && !in_array($orderStatus,   $validOrderStatuses))   respondError('Invalid order status.');
if ($paymentStatus !== '' && !in_array($paymentStatus, $validPaymentStatuses)) respondError('Invalid payment status.');

$db = getDB();
$db->beginTransaction();

try {
    /* Verify order exists */
    $chk = $db->prepare('SELECT order_id FROM orders WHERE order_id = ? LIMIT 1');
    $chk->execute([$orderId]);
    if (!$chk->fetch()) respondError('Order not found.', 404);

    /* ── Update order status ── */
    if ($orderStatus !== '') {
        $db->prepare('UPDATE orders SET status = ? WHERE order_id = ?')
           ->execute([$orderStatus, $orderId]);
    }

    /* ── Update payment status + trigger product status change ── */
    if ($paymentStatus !== '') {
        /* Upsert payment row */
        $pay = $db->prepare('SELECT payment_id FROM payments WHERE order_id = ? LIMIT 1');
        $pay->execute([$orderId]);
        if ($pay->fetch()) {
            $db->prepare('UPDATE payments SET status = ? WHERE order_id = ?')
               ->execute([$paymentStatus, $orderId]);
        } else {
            $db->prepare('INSERT INTO payments (order_id, status) VALUES (?, ?)')
               ->execute([$orderId, $paymentStatus]);
        }

        /* ── VERIFIED: mark products as sold (confirmed purchase) ── */
        if ($paymentStatus === 'Verified') {
            $db->prepare(
                "UPDATE products p
                 INNER JOIN order_items oi ON oi.product_id = p.product_id
                 SET p.status = 'sold'
                 WHERE oi.order_id = ?"
            )->execute([$orderId]);

            /* Also advance order to Processing if it's still Pending Verification */
            if ($orderStatus === '' || $orderStatus === 'Pending Verification') {
                $db->prepare(
                    "UPDATE orders SET status = 'Processing'
                     WHERE order_id = ? AND status = 'Pending Verification'"
                )->execute([$orderId]);
            }
        }

        /* ── REJECTED: release products back to available (buyer must retry) ── */
        if ($paymentStatus === 'Rejected') {
            $db->prepare(
                "UPDATE products p
                 INNER JOIN order_items oi ON oi.product_id = p.product_id
                 SET p.status = 'available'
                 WHERE oi.order_id = ?"
            )->execute([$orderId]);

            /* Mark order as Rejected */
            $db->prepare(
                "UPDATE orders SET status = 'Rejected'
                 WHERE order_id = ? AND status IN ('Pending Verification','Pending Payment')"
            )->execute([$orderId]);
        }
    }

    $db->commit();
    respond(['success' => true]);

} catch (Throwable $e) {
    $db->rollBack();
    respondError('Failed to update order status: ' . $e->getMessage(), 500);
}
