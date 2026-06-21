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
$adminNotes      = isset($body['admin_notes'])    ? $body['admin_notes']            : null;

if ($orderId <= 0) respondError('Valid order_id is required.');

$validOrderStatuses = [
    'Pending Payment',
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

/* Check if admin_notes column exists — add it if not */
try {
    $chk = $db->query("SHOW COLUMNS FROM orders LIKE 'admin_notes'");
    if ($chk->rowCount() === 0) {
        $db->exec("ALTER TABLE orders ADD COLUMN admin_notes TEXT NULL");
    }
} catch (Throwable $e) { /* ignore — column already exists */ }

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
        /* Create order_items from items_json (payment-request model: items are
           stored as JSON on the order and only materialised here on approval) */
        $orderRow = $db->prepare('SELECT items_json FROM orders WHERE order_id = ? LIMIT 1');
        $orderRow->execute([$orderId]);
        $orderData = $orderRow->fetch();
        if ($orderData && !empty($orderData['items_json'])) {
            $pendingItems = json_decode($orderData['items_json'], true) ?: [];
            /* Only insert if order_items don't already exist (idempotent) */
            $cntStmt = $db->prepare('SELECT COUNT(*) FROM order_items WHERE order_id = ?');
            $cntStmt->execute([$orderId]);
            $existingCount = (int)$cntStmt->fetchColumn();
            if ($existingCount === 0) {
                $itmStmt = $db->prepare('INSERT INTO order_items (order_id, product_id, price) VALUES (?, ?, ?)');
                foreach ($pendingItems as $item) {
                    $pid   = (int)($item['product_id'] ?? 0);
                    $price = (float)($item['price'] ?? 0);
                    if ($pid > 0) $itmStmt->execute([$orderId, $pid, $price]);
                }
            }
        }

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

    /* ── Save admin notes if provided ── */
    if ($adminNotes !== null) {
        try {
            $db->prepare('UPDATE orders SET admin_notes = ? WHERE order_id = ?')
               ->execute([$adminNotes === '' ? null : $adminNotes, $orderId]);
        } catch (Throwable $e) { /* column may not exist yet on first request */ }
    }

    $db->commit();
    respond(['success' => true, 'order_status' => $finalOrderStatus]);

} catch (Throwable $e) {
    $db->rollBack();
    respondError('Failed to update: ' . $e->getMessage(), 500);
}
