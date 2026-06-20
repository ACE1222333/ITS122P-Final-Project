<?php
/* ════════════════════════════════════════════════════════════════
   api/orders/update_status.php — Update order + payment status (admin)

   Payment flow (Payments page):
     Pending Verification → Approved  (products sold, order → Processing)
     Pending Verification → Rejected  (products released, order → Payment Rejected)

   Fulfillment flow (Orders page — approved payments only):
     Processing → Shipping → Shipped → Completed
     Any stage  → Cancelled
════════════════════════════════════════════════════════════════ */
require_once dirname(__DIR__) . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respondError('Method not allowed.', 405);
requireAdmin();

$body            = getJsonBody();
$orderId         = isset($body['order_id'])       ? (int)$body['order_id']         : 0;
$orderStatus     = trim($body['order_status']     ?? '');
$paymentStatus   = trim($body['payment_status']   ?? '');
$rejectionReason = trim($body['rejection_reason'] ?? '');

if ($orderId <= 0) respondError('Valid order_id is required.');

$validOrderStatuses = [
    'Payment Verification',
    'Payment Rejected',
    'Processing',
    'Shipping',
    'Shipped',
    'Completed',
    'Cancelled',
    /* legacy */
    'Payment Accepted',
    'Pending Verification',
    'Rejected',
];
$validPaymentStatuses = ['Pending Verification', 'Approved', 'Rejected'];

if ($orderStatus   !== '' && !in_array($orderStatus,   $validOrderStatuses))   respondError('Invalid order status.');
if ($paymentStatus !== '' && !in_array($paymentStatus, $validPaymentStatuses)) respondError('Invalid payment status.');

/* Normalize legacy order statuses */
$legacyOrderMap = [
    'Rejected'             => 'Payment Rejected',
    'Pending Verification' => 'Payment Verification',
    'Payment Accepted'     => 'Processing',
];
if (isset($legacyOrderMap[$orderStatus])) $orderStatus = $legacyOrderMap[$orderStatus];

$db = getDB();
$db->beginTransaction();

try {
    $chk = $db->prepare('SELECT order_id FROM orders WHERE order_id = ? LIMIT 1');
    $chk->execute([$orderId]);
    if (!$chk->fetch()) respondError('Order not found.', 404);

    /* ── Step 1: Update payments table ── */
    if ($paymentStatus !== '') {
        $pay = $db->prepare('SELECT payment_id FROM payments WHERE order_id = ? LIMIT 1');
        $pay->execute([$orderId]);
        if ($pay->fetch()) {
            $db->prepare('UPDATE payments SET status = ? WHERE order_id = ?')
               ->execute([$paymentStatus, $orderId]);
        } else {
            $db->prepare('INSERT INTO payments (order_id, status) VALUES (?, ?)')
               ->execute([$orderId, $paymentStatus]);
        }
    }

    /* ── Step 2: Product side-effects + receipt generation ── */
    if ($paymentStatus === 'Approved') {
        $db->prepare(
            "UPDATE products p
             INNER JOIN order_items oi ON oi.product_id = p.product_id
             SET p.status = 'sold'
             WHERE oi.order_id = ?"
        )->execute([$orderId]);

        /* Auto-generate receipt — INSERT IGNORE so re-approvals are idempotent */
        $receiptNumber = 'RCP-' . date('Ymd') . '-' . str_pad($orderId, 5, '0', STR_PAD_LEFT);
        $db->prepare(
            "INSERT IGNORE INTO receipts (receipt_number, order_id, generated_at) VALUES (?, ?, NOW())"
        )->execute([$receiptNumber, $orderId]);
    }
    if ($paymentStatus === 'Rejected') {
        $db->prepare(
            "UPDATE products p
             INNER JOIN order_items oi ON oi.product_id = p.product_id
             SET p.status = 'available'
             WHERE oi.order_id = ?"
        )->execute([$orderId]);
    }

    /* ── Step 3: Determine final order status ── */
    $finalOrderStatus = '';

    if ($orderStatus !== '') {
        $finalOrderStatus = $orderStatus;
    } elseif ($paymentStatus === 'Approved') {
        $finalOrderStatus = 'Processing';   /* payment approved → enter fulfillment */
    } elseif ($paymentStatus === 'Rejected') {
        $finalOrderStatus = 'Payment Rejected';
    }

    if ($finalOrderStatus !== '') {
        if ($finalOrderStatus === 'Payment Rejected' && $rejectionReason !== '') {
            $db->prepare('UPDATE orders SET status = ?, rejection_reason = ? WHERE order_id = ?')
               ->execute([$finalOrderStatus, $rejectionReason, $orderId]);
        } else {
            $db->prepare('UPDATE orders SET status = ? WHERE order_id = ?')
               ->execute([$finalOrderStatus, $orderId]);
        }
    }

    $db->commit();
    respond(['success' => true, 'order_status' => $finalOrderStatus]);

} catch (Throwable $e) {
    $db->rollBack();
    respondError('Failed to update: ' . $e->getMessage(), 500);
}
