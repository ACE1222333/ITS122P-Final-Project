<?php
/* ════════════════════════════════════════════════════════════════
   api/orders.php
     GET  (admin) — list all orders with items + payment
     POST (customer) — create a new order
════════════════════════════════════════════════════════════════ */
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];

/* ══════════════ GET — admin order list (approved payments only) ═ */
if ($method === 'GET') {
    requireAdmin();
    $db = getDB();

    /* Only orders whose payment has been Approved appear in the fulfillment
       view.  Pending / Rejected payments live on the Payments page instead. */
    /* Check for optional columns */
    $notesCol    = '';
    $shippingCol = '';
    try {
        $chk = $db->query("SHOW COLUMNS FROM orders LIKE 'admin_notes'");
        if ($chk->rowCount() > 0) $notesCol = ', o.admin_notes';
    } catch (Throwable $e) {}
    try {
        $chk = $db->query("SHOW COLUMNS FROM orders LIKE 'shipping_fee'");
        if ($chk->rowCount() > 0) $shippingCol = ', o.shipping_fee';
    } catch (Throwable $e) {}

    $orders = $db->query(
        "SELECT o.order_id, o.user_id, o.total_amount, o.status AS order_status,
                o.date_ordered $notesCol $shippingCol,
                u.first_name, u.last_name, u.email, u.phone, u.address,
                py.payment_id, py.payment_method, py.reference_number,
                py.proof_image, py.status AS payment_status,
                py.amount AS payment_amount, py.date_paid
         FROM orders o
         JOIN users u ON u.user_id = o.user_id
         INNER JOIN payments py ON py.order_id = o.order_id AND py.status = 'Approved'
         ORDER BY o.date_ordered DESC"
    )->fetchAll();

    /* Fetch all order items with product info in one query */
    $items = $db->query(
        'SELECT oi.order_id, oi.product_id, oi.price,
                p.name AS product_name, p.cover_index,
                s.label AS size_label,
                pi.image_url
         FROM order_items oi
         JOIN products p ON p.product_id = oi.product_id
         LEFT JOIN sizes s ON s.size_id = p.size_id
         LEFT JOIN product_images pi ON pi.product_id = oi.product_id AND pi.sort_order = p.cover_index
         ORDER BY oi.order_id ASC'
    )->fetchAll();

    /* Group items by order_id */
    $itemMap = [];
    foreach ($items as $item) {
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
        return [
            'id'          => $oid,
            'order_id'    => $oid,
            'user'        => [
                'name'    => $o['first_name'] . ' ' . $o['last_name'],
                'email'   => $o['email'],
                'phone'   => $o['phone'] ?? '',
                'address' => $o['address'] ?? '',
            ],
            'products'    => $itemMap[$oid] ?? [],
            'totalAmount' => (float)$o['total_amount'],
            'orderStatus' => $o['order_status'],
            'payment'     => [
                'method'          => $o['payment_method'] ?? 'GCash',
                'referenceNumber' => $o['reference_number'] ?? '',
                'status'          => $o['payment_status'] ?? 'Pending Verification',
                'amount'          => $o['payment_amount'] ? (float)$o['payment_amount'] : (float)$o['total_amount'],
                'proofImage'      => $o['proof_image'] ?? '',
                'datePaid'        => $o['date_paid'] ?? '',
            ],
            'dateOrdered'           => $o['date_ordered'],
            'reservationExpiresAt'  => $o['reservation_expires_at'] ?? null,
            'adminNotes'            => $o['admin_notes'] ?? null,
            'shippingFee'           => isset($o['shipping_fee']) ? (float)$o['shipping_fee'] : 0,
        ];
    }, $orders);

    respond($result);
}

/* ══════════════ POST — create order ════════════════════════ */
if ($method === 'POST') {
    $user = requireAuth();

    /* Accept both JSON body and multipart/form-data */
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $isFormData  = (strpos($contentType, 'multipart/form-data') !== false);

    if ($isFormData) {
        /* Parse fields from FormData */
        $itemsJson    = $_POST['items']            ?? '[]';
        $totalAmount  = isset($_POST['total_amount'])  ? (float)$_POST['total_amount']  : 0;
        $shippingFee  = isset($_POST['shipping_fee'])  ? (float)$_POST['shipping_fee']  : 0;
        $payMethod    = trim($_POST['payment_method']  ?? 'GCash');
        $refNumber    = trim($_POST['reference_number'] ?? '');
        $address      = trim($_POST['address']         ?? '');
        $phone        = trim($_POST['phone']           ?? '');
        $items        = json_decode($itemsJson, true) ?: [];
        $proofUrl     = '';

        /* Handle proof image upload */
        if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['proof_image'];
            if ($file['size'] <= 5 * 1024 * 1024) {
                $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                $allowed  = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
                if (array_key_exists($mimeType, $allowed)) {
                    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
                    $filename = uniqid('proof_', true) . '.' . $allowed[$mimeType];
                    if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
                        $proofUrl = UPLOAD_URL . $filename;
                    }
                }
            }
        }
    } else {
        $body        = getJsonBody();
        $items       = $body['items']            ?? [];
        $totalAmount = isset($body['total_amount']) ? (float)$body['total_amount'] : 0;
        $shippingFee = isset($body['shipping_fee']) ? (float)$body['shipping_fee'] : 0;
        $payMethod   = trim($body['payment_method']   ?? 'GCash');
        $refNumber   = trim($body['reference_number'] ?? '');
        $proofUrl    = trim($body['proof_image']       ?? '');
        $address     = trim($body['address']           ?? '');
        $phone       = trim($body['phone']             ?? '');
    }

    if (empty($items))    respondError('Order must contain at least one item.');
    if ($totalAmount <= 0) respondError('Invalid total amount.');
    if ($refNumber === '') respondError('GCash reference number is required.');
    if ($address === '')   respondError('Delivery address is required.');

    $db = getDB();

    /* Auto-migrate shipping_fee column */
    try {
        $chk = $db->query("SHOW COLUMNS FROM orders LIKE 'shipping_fee'");
        if ($chk->rowCount() === 0) {
            $db->exec("ALTER TABLE orders ADD COLUMN shipping_fee DECIMAL(10,2) DEFAULT 0 AFTER total_amount");
        }
    } catch (Throwable $e) { /* ignore */ }

    /* Auto-migrate items_json column — stores cart snapshot until payment is approved */
    try {
        $chk = $db->query("SHOW COLUMNS FROM orders LIKE 'items_json'");
        if ($chk->rowCount() === 0) {
            $db->exec("ALTER TABLE orders ADD COLUMN items_json TEXT NULL AFTER shipping_fee");
        }
    } catch (Throwable $e) { /* ignore */ }

    $db->beginTransaction();

    try {
        /* Insert order — status is Pending Payment until admin approves.
           Order items are NOT created yet; they are stored as JSON and
           inserted only when the payment is approved. */
        $ordStmt = $db->prepare(
            "INSERT INTO orders (user_id, total_amount, shipping_fee, items_json, status)
             VALUES (?, ?, ?, ?, 'Pending Payment')"
        );
        $ordStmt->execute([$user['user_id'], $totalAmount, $shippingFee, json_encode($items)]);
        $orderId = (int)$db->lastInsertId();

        /* Insert payment */
        $payStmt = $db->prepare(
            'INSERT INTO payments (order_id, payment_method, reference_number, proof_image, status, amount, date_paid)
             VALUES (?, ?, ?, ?, \'Pending Verification\', ?, NOW())'
        );
        $payStmt->execute([$orderId, $payMethod, $refNumber, $proofUrl, $totalAmount]);

        /* Update user address/phone if provided */
        if ($address !== '' || $phone !== '') {
            $db->prepare('UPDATE users SET address = COALESCE(NULLIF(?, \'\'), address), phone = COALESCE(NULLIF(?, \'\'), phone) WHERE user_id = ?')
               ->execute([$address, $phone, $user['user_id']]);
        }

        $db->commit();
        respond(['success' => true, 'order_id' => $orderId]);

    } catch (Throwable $e) {
        $db->rollBack();
        respondError('Failed to create order: ' . $e->getMessage(), 500);
    }
}

respondError('Method not allowed.', 405);
