<?php
/* ════════════════════════════════════════════════════════════════
   api/orders.php
     GET  (admin) — list all orders with items + payment
     POST (customer) — create a new order
════════════════════════════════════════════════════════════════ */
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];

/* ══════════════ GET — admin order list ══════════════════════ */
if ($method === 'GET') {
    requireAdmin();
    $db = getDB();

    $orders = $db->query(
        'SELECT o.order_id, o.user_id, o.total_amount, o.status AS order_status,
                o.date_ordered,
                u.first_name, u.last_name, u.email, u.phone, u.address,
                py.payment_id, py.payment_method, py.reference_number,
                py.proof_image, py.status AS payment_status,
                py.amount AS payment_amount, py.date_paid
         FROM orders o
         JOIN users u ON u.user_id = o.user_id
         LEFT JOIN payments py ON py.order_id = o.order_id
         ORDER BY o.date_ordered DESC'
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
    $db->beginTransaction();

    try {
        /* Reservation expires in 48 hours — gives admin time to review proof */
        $reservationExpiry = date('Y-m-d H:i:s', strtotime('+48 hours'));

        /* Insert order with 'Pending Verification' (proof is submitted in same step) */
        $ordStmt = $db->prepare(
            'INSERT INTO orders (user_id, total_amount, status, reservation_expires_at)
             VALUES (?, ?, \'Pending Verification\', ?)'
        );
        $ordStmt->execute([$user['user_id'], $totalAmount, $reservationExpiry]);
        $orderId = (int)$db->lastInsertId();

        /* Insert order items + RESERVE products (not sold yet — pending admin verification) */
        $itmStmt = $db->prepare('INSERT INTO order_items (order_id, product_id, price) VALUES (?, ?, ?)');
        $rsvStmt = $db->prepare("UPDATE products SET status = 'reserved' WHERE product_id = ? AND status = 'available'");

        foreach ($items as $item) {
            $pid   = (int)($item['product_id'] ?? 0);
            $price = (float)($item['price'] ?? 0);
            if ($pid <= 0) continue;
            $itmStmt->execute([$orderId, $pid, $price]);
            $rsvStmt->execute([$pid]);
        }

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
