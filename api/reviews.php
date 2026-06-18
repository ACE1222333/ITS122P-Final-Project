<?php
/* ════════════════════════════════════════════════════════════════
   api/reviews.php
     GET  — public list of all reviews
     POST — submit a review (requires customer auth)
════════════════════════════════════════════════════════════════ */
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];

/* ══════════════ GET ════════════════════════════════════════ */
if ($method === 'GET') {
    $db = getDB();

    $stmt = $db->query(
        'SELECT r.review_id, r.user_id, r.product_id, r.rating, r.comment, r.date_reviewed,
                u.first_name, u.last_name,
                p.name AS product_name
         FROM reviews r
         JOIN users u ON u.user_id = r.user_id
         LEFT JOIN products p ON p.product_id = r.product_id
         ORDER BY r.date_reviewed DESC'
    );
    $rows = $stmt->fetchAll();

    $reviews = array_map(function($r) {
        $date = '';
        if ($r['date_reviewed']) {
            $ts   = strtotime($r['date_reviewed']);
            $date = date('M d, Y', $ts);
        }
        return [
            'review_id'  => (int)$r['review_id'],
            'user_id'    => (int)$r['user_id'],
            'name'       => $r['first_name'] . ' ' . $r['last_name'],
            'rating'     => (int)$r['rating'],
            'product'    => $r['product_name'] ?? null,
            'product_id' => $r['product_id'] ? (int)$r['product_id'] : null,
            'body'       => $r['comment'],
            'date'       => $date,
        ];
    }, $rows);

    respond($reviews);
}

/* ══════════════ POST ═══════════════════════════════════════ */
if ($method === 'POST') {
    $user = requireAuth();
    $body = getJsonBody();

    $product_id = isset($body['product_id']) && $body['product_id'] !== '' ? (int)$body['product_id'] : null;
    $rating     = isset($body['rating'])     ? (int)$body['rating']     : 0;
    $comment    = trim($body['body']         ?? '');

    if ($rating < 1 || $rating > 5) respondError('Rating must be between 1 and 5.');
    if ($comment === '')             respondError('Review body is required.');

    $db = getDB();

    /* Verify product exists if provided */
    if ($product_id !== null) {
        $chk = $db->prepare('SELECT product_id FROM products WHERE product_id = ?');
        $chk->execute([$product_id]);
        if (!$chk->fetch()) $product_id = null; // silently ignore invalid product
    }

    $ins = $db->prepare(
        'INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)'
    );
    $ins->execute([$user['user_id'], $product_id, $rating, $comment]);
    $reviewId = (int)$db->lastInsertId();

    $date = date('M d, Y');

    respond([
        'success' => true,
        'review'  => [
            'review_id'  => $reviewId,
            'user_id'    => (int)$user['user_id'],
            'name'       => $user['first_name'] . ' ' . $user['last_name'],
            'rating'     => $rating,
            'product'    => null,
            'product_id' => $product_id,
            'body'       => $comment,
            'date'       => $date,
        ],
    ]);
}

respondError('Method not allowed.', 405);
