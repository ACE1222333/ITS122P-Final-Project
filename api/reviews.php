<?php
/* ════════════════════════════════════════════════════════════════
   api/reviews.php
     GET    — public list of all reviews (with user replies)
     POST   — submit a review (requires customer auth)
     PUT    — edit own review (requires customer auth)
     DELETE — delete own review (requires customer auth)
════════════════════════════════════════════════════════════════ */
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];

function fmtDate(?string $ts): string {
    if (!$ts) return '';
    $tz = new DateTimeZone(date_default_timezone_get());
    $d  = new DateTime($ts, $tz);
    $now = new DateTime('now', $tz);
    $isToday = $d->format('Y-m-d') === $now->format('Y-m-d');
    return $isToday ? $d->format('g:i A') : $d->format('M d, Y');
}

/* ══════════════ GET ════════════════════════════════════════ */
if ($method === 'GET') {
    $db = getDB();

    $stmt = $db->query(
        'SELECT r.review_id, r.user_id, r.product_id, r.rating, r.comment, r.date_reviewed,
                r.admin_reply, r.reply_date,
                u.first_name, u.last_name,
                p.name AS product_name, p.price AS product_price, p.cover_index
         FROM reviews r
         JOIN users u ON u.user_id = r.user_id
         LEFT JOIN products p ON p.product_id = r.product_id
         ORDER BY r.date_reviewed DESC'
    );
    $rows = $stmt->fetchAll();

    /* Fetch cover images for all referenced products */
    $productIds = array_filter(array_unique(array_column($rows, 'product_id')));
    $productCoverMap = [];
    if ($productIds) {
        $ph = implode(',', array_fill(0, count($productIds), '?'));
        $imgRows = $db->prepare(
            "SELECT pi.product_id, pi.image_url, p.cover_index
             FROM product_images pi
             JOIN products p ON p.product_id = pi.product_id
             WHERE pi.product_id IN ($ph)
             ORDER BY pi.sort_order ASC"
        );
        $imgRows->execute(array_values($productIds));
        $grouped = [];
        foreach ($imgRows->fetchAll() as $img) {
            $grouped[$img['product_id']][] = $img['image_url'];
        }
        foreach ($grouped as $pid => $urls) {
            $coverIdx = 0;
            foreach ($rows as $rw) {
                if ((int)$rw['product_id'] === (int)$pid) { $coverIdx = (int)$rw['cover_index']; break; }
            }
            $productCoverMap[$pid] = $urls[$coverIdx] ?? $urls[0] ?? null;
        }
    }

    /* Fetch review images — graceful if table not created yet */
    $imagesByReview = [];
    $reviewIds = array_column($rows, 'review_id');
    if ($reviewIds) {
        try {
            $ph = implode(',', array_fill(0, count($reviewIds), '?'));
            $imgStmt = $db->prepare(
                "SELECT image_id, review_id, image_path FROM review_images WHERE review_id IN ($ph) ORDER BY image_id ASC"
            );
            $imgStmt->execute($reviewIds);
            foreach ($imgStmt->fetchAll() as $img) {
                $imagesByReview[(int)$img['review_id']][] = [
                    'id'   => (int)$img['image_id'],
                    'path' => $img['image_path'],
                ];
            }
        } catch (Throwable $e) { /* table not created yet */ }
    }

    $reviews = array_map(function($r) use ($productCoverMap, $imagesByReview) {
        $rid = (int)$r['review_id'];
        $pid = $r['product_id'] ? (int)$r['product_id'] : null;
        return [
            'review_id'      => $rid,
            'user_id'        => (int)$r['user_id'],
            'name'           => $r['first_name'] . ' ' . $r['last_name'],
            'rating'         => (int)$r['rating'],
            'product'        => $r['product_name'] ?? null,
            'product_id'     => $pid,
            'product_price'  => $pid && $r['product_price'] ? (float)$r['product_price'] : null,
            'product_image'  => $pid ? ($productCoverMap[$pid] ?? null) : null,
            'body'           => $r['comment'],
            'date'           => fmtDate($r['date_reviewed']),
            'timestamp'      => $r['date_reviewed'] ? strtotime($r['date_reviewed']) : null,
            'admin_reply'    => $r['admin_reply'],
            'reply_date'     => fmtDate($r['reply_date']),
            'images'         => $imagesByReview[$rid] ?? [],
        ];
    }, $rows);

    respond($reviews);
}

/* ══════════════ POST ═══════════════════════════════════════ */
if ($method === 'POST' && (($_POST['action'] ?? '') !== 'edit')) {
    $user = requireAuth();

    $isMultipart = isset($_SERVER['CONTENT_TYPE'])
                && strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false;

    if ($isMultipart) {
        $product_id = (isset($_POST['product_id']) && $_POST['product_id'] !== '') ? (int)$_POST['product_id'] : null;
        $rating     = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $comment    = trim($_POST['body'] ?? '');
    } else {
        $body       = getJsonBody();
        $product_id = (isset($body['product_id']) && $body['product_id'] !== '') ? (int)$body['product_id'] : null;
        $rating     = isset($body['rating']) ? (int)$body['rating'] : 0;
        $comment    = trim($body['body'] ?? '');
    }

    if ($rating < 1 || $rating > 5) respondError('Rating must be between 1 and 5.');
    if ($comment === '')             respondError('Review body is required.');

    $db = getDB();

    if ($product_id !== null) {
        $chk = $db->prepare('SELECT product_id FROM products WHERE product_id = ?');
        $chk->execute([$product_id]);
        if (!$chk->fetch()) $product_id = null;
    }

    $ins = $db->prepare(
        'INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)'
    );
    $ins->execute([$user['user_id'], $product_id, $rating, $comment]);
    $reviewId = (int)$db->lastInsertId();

    /* Handle uploaded images */
    $imagePaths = [];
    if (!empty($_FILES['images'])) {
        $uploadDir    = __DIR__ . '/../uploads/reviews/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $files   = $_FILES['images'];
        $count   = is_array($files['name']) ? count($files['name']) : 1;
        for ($i = 0; $i < min($count, 5); $i++) {
            $tmp  = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $mime = is_array($files['type'])     ? $files['type'][$i]     : $files['type'];
            $size = is_array($files['size'])     ? $files['size'][$i]     : $files['size'];
            $err  = is_array($files['error'])    ? $files['error'][$i]    : $files['error'];
            $orig = is_array($files['name'])     ? $files['name'][$i]     : $files['name'];
            if ($err !== UPLOAD_ERR_OK || !in_array($mime, $allowed) || $size > 5*1024*1024) continue;
            $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            $fn   = uniqid('rev_', true) . '.' . $ext;
            if (move_uploaded_file($tmp, $uploadDir . $fn)) {
                $path = '/websystem/uploads/reviews/' . $fn;
                $imagePaths[] = $path;
                try {
                    $db->prepare('INSERT INTO review_images (review_id, image_path) VALUES (?,?)')
                       ->execute([$reviewId, $path]);
                } catch (Throwable $e) { /* table not ready */ }
            }
        }
    }

    $now = date('Y-m-d H:i:s');
    respond([
        'success' => true,
        'review'  => [
            'review_id'     => $reviewId,
            'user_id'       => (int)$user['user_id'],
            'name'          => $user['first_name'] . ' ' . $user['last_name'],
            'rating'        => $rating,
            'product'       => null,
            'product_id'    => $product_id,
            'product_price' => null,
            'product_image' => null,
            'body'          => $comment,
            'date'          => fmtDate($now),
            'timestamp'     => strtotime($now),
            'admin_reply'   => null,
            'reply_date'    => null,
            'images'        => $imagePaths,
        ],
    ]);
}

/* ══════════════ POST action=edit (edit own review) ═════════ */
if ($method === 'POST' && (($_POST['action'] ?? '') === 'edit')) {
    $user = requireAuth();

    $reviewId     = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
    $rating       = isset($_POST['rating'])    ? (int)$_POST['rating']    : 0;
    $comment      = trim($_POST['body']        ?? '');
    $deleteIdsRaw = $_POST['delete_image_ids'] ?? '';

    if ($reviewId <= 0)              respondError('Valid review_id is required.');
    if ($rating < 1 || $rating > 5) respondError('Rating must be between 1 and 5.');
    if ($comment === '')             respondError('Review body is required.');

    $db  = getDB();
    $chk = $db->prepare('SELECT user_id FROM reviews WHERE review_id = ? LIMIT 1');
    $chk->execute([$reviewId]);
    $row = $chk->fetch();

    if (!$row)                                                   respondError('Review not found.', 404);
    if ((int)$row['user_id'] !== (int)$user['user_id']) respondError('You can only edit your own reviews.', 403);

    $db->prepare('UPDATE reviews SET rating = ?, comment = ? WHERE review_id = ?')
       ->execute([$rating, $comment, $reviewId]);

    /* Delete requested images */
    $deleteIds = array_filter(array_map('intval', explode(',', $deleteIdsRaw)));
    if ($deleteIds) {
        try {
            $ph   = implode(',', array_fill(0, count($deleteIds), '?'));
            $rows = $db->prepare("SELECT image_id, image_path FROM review_images WHERE image_id IN ($ph) AND review_id = ?");
            $rows->execute([...$deleteIds, $reviewId]);
            foreach ($rows->fetchAll() as $img) {
                $fullPath = __DIR__ . '/../' . ltrim($img['image_path'], '/');
                $fullPath = str_replace('/websystem/', '', $fullPath);
                $realPath = __DIR__ . '/../' . ltrim(parse_url($img['image_path'], PHP_URL_PATH), '/websystem/');
                @unlink($_SERVER['DOCUMENT_ROOT'] . $img['image_path']);
                $db->prepare('DELETE FROM review_images WHERE image_id = ?')->execute([$img['image_id']]);
            }
        } catch (Throwable $e) { /* ignore */ }
    }

    /* Upload new images */
    $newImagePaths = [];
    if (!empty($_FILES['images'])) {
        $uploadDir = __DIR__ . '/../uploads/reviews/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $files   = $_FILES['images'];
        $count   = is_array($files['name']) ? count($files['name']) : 1;
        for ($i = 0; $i < min($count, 5); $i++) {
            $tmp  = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $mime = is_array($files['type'])     ? $files['type'][$i]     : $files['type'];
            $size = is_array($files['size'])     ? $files['size'][$i]     : $files['size'];
            $err  = is_array($files['error'])    ? $files['error'][$i]    : $files['error'];
            $orig = is_array($files['name'])     ? $files['name'][$i]     : $files['name'];
            if ($err !== UPLOAD_ERR_OK || !in_array($mime, $allowed) || $size > 5*1024*1024) continue;
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            $fn  = uniqid('rev_', true) . '.' . $ext;
            if (move_uploaded_file($tmp, $uploadDir . $fn)) {
                $path = '/websystem/uploads/reviews/' . $fn;
                $newImagePaths[] = $path;
                try {
                    $db->prepare('INSERT INTO review_images (review_id, image_path) VALUES (?,?)')->execute([$reviewId, $path]);
                } catch (Throwable $e) { /* ignore */ }
            }
        }
    }

    /* Return updated images list */
    $updatedImages = [];
    try {
        $imgRows = $db->prepare('SELECT image_id, image_path FROM review_images WHERE review_id = ? ORDER BY image_id ASC');
        $imgRows->execute([$reviewId]);
        foreach ($imgRows->fetchAll() as $img) {
            $updatedImages[] = ['id' => (int)$img['image_id'], 'path' => $img['image_path']];
        }
    } catch (Throwable $e) { /* ignore */ }

    respond(['success' => true, 'rating' => $rating, 'body' => $comment, 'images' => $updatedImages]);
}

/* ══════════════ DELETE (admin or review owner) ═════════════ */
if ($method === 'DELETE') {
    $user = requireAuth();
    $body = getJsonBody();

    $reviewId = isset($body['review_id']) ? (int)$body['review_id'] : 0;
    if ($reviewId <= 0) respondError('Valid review_id is required.');

    $db  = getDB();
    $chk = $db->prepare('SELECT user_id FROM reviews WHERE review_id = ? LIMIT 1');
    $chk->execute([$reviewId]);
    $row = $chk->fetch();

    if (!$row) respondError('Review not found.', 404);

    $isAdmin = $user['role'] === 'admin';
    $isOwner = (int)$row['user_id'] === (int)$user['user_id'];
    if (!$isAdmin && !$isOwner) respondError('You can only delete your own reviews.', 403);

    $db->prepare('DELETE FROM reviews WHERE review_id = ?')->execute([$reviewId]);

    respond(['success' => true]);
}

respondError('Method not allowed.', 405);
