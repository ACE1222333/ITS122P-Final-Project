<?php
/* api/orders/cancel.php — Customer cancels their own order.
   Only allowed when the order is still in Payment Verification.
   Cancelling frees the reserved products back to available. */
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respondError('Method not allowed.', 405);
$user = requireAuth();

$body    = getJsonBody();
$orderId = (int)($body['order_id'] ?? 0);
if ($orderId <= 0) respondError('Valid order_id is required.');

$db = getDB();
$db->beginTransaction();

try {
    /* Verify order belongs to this user and is still cancellable */
    $stmt = $db->prepare(
        "SELECT order_id, status FROM orders
         WHERE order_id = ? AND user_id = ? LIMIT 1"
    );
    $stmt->execute([$orderId, $user['user_id']]);
    $order = $stmt->fetch();

    if (!$order) respondError('Order not found.', 404);

    $cancellable = ['Payment Verification', 'Pending Payment', 'Pending Verification'];
    if (!in_array($order['status'], $cancellable)) {
        respondError('This order can no longer be cancelled. Payment has already been accepted.');
    }

    /* Set order to Cancelled */
    $db->prepare("UPDATE orders SET status = 'Cancelled' WHERE order_id = ?")
       ->execute([$orderId]);

    /* Free reserved products back to available */
    $db->prepare(
        "UPDATE products SET status = 'available'
         WHERE product_id IN (
             SELECT product_id FROM order_items WHERE order_id = ?
         )"
    )->execute([$orderId]);

    $db->commit();
    respond(['success' => true]);

} catch (Throwable $e) {
    $db->rollBack();
    respondError('Failed to cancel order: ' . $e->getMessage(), 500);
}
