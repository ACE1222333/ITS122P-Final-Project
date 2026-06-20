<?php
/* ════════════════════════════════════════════════════════════════
   api/reviews/user-reply.php
   POST   { review_id, body }        — post a reply (auth required)
   DELETE { reply_id }               — delete own reply (auth required)
════════════════════════════════════════════════════════════════ */
require_once dirname(__DIR__) . '/config.php';

function fmtDate(?string $ts): string {
    if (!$ts) return '';
    $tz = new DateTimeZone(date_default_timezone_get());
    $d  = new DateTime($ts, $tz);
    $now = new DateTime('now', $tz);
    $isToday = $d->format('Y-m-d') === $now->format('Y-m-d');
    return $isToday ? $d->format('g:i A') : $d->format('M d, Y');
}

$method = $_SERVER['REQUEST_METHOD'];
$user   = requireAuth();
$body   = getJsonBody();

/* Auto-create table if migration has not been run yet */
try {
    getDB()->exec(
        'CREATE TABLE IF NOT EXISTS review_replies (
            reply_id   INT AUTO_INCREMENT PRIMARY KEY,
            review_id  INT NOT NULL,
            user_id    INT NOT NULL,
            body       TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (review_id),
            INDEX (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
} catch (Throwable $e) { /* ignore */ }

/* ══════════════ POST ═══════════════════════════════════════ */
if ($method === 'POST') {
    $reviewId  = isset($body['review_id']) ? (int)$body['review_id'] : 0;
    $replyBody = trim($body['body'] ?? '');

    if ($reviewId <= 0)                    respondError('Valid review_id is required.');
    if ($replyBody === '')                 respondError('Reply body is required.');
    if (mb_strlen($replyBody) > 2000)      respondError('Reply is too long (max 2000 characters).');

    $db  = getDB();
    $chk = $db->prepare('SELECT review_id FROM reviews WHERE review_id = ? LIMIT 1');
    $chk->execute([$reviewId]);
    if (!$chk->fetch()) respondError('Review not found.', 404);

    $db->prepare('INSERT INTO review_replies (review_id, user_id, body) VALUES (?, ?, ?)')
       ->execute([$reviewId, $user['user_id'], $replyBody]);
    $replyId = (int)$db->lastInsertId();

    respond([
        'success' => true,
        'reply'   => [
            'reply_id'   => $replyId,
            'user_id'    => (int)$user['user_id'],
            'name'       => $user['first_name'] . ' ' . $user['last_name'],
            'body'       => $replyBody,
            'created_at' => fmtDate(date('Y-m-d H:i:s')),
        ],
    ]);
}

/* ══════════════ DELETE ═════════════════════════════════════ */
if ($method === 'DELETE') {
    $replyId = isset($body['reply_id']) ? (int)$body['reply_id'] : 0;
    if ($replyId <= 0) respondError('Valid reply_id is required.');

    $db  = getDB();
    $chk = $db->prepare('SELECT user_id FROM review_replies WHERE reply_id = ? LIMIT 1');
    $chk->execute([$replyId]);
    $row = $chk->fetch();

    if (!$row)                              respondError('Reply not found.', 404);
    if ((int)$row['user_id'] !== (int)$user['user_id']) respondError('You can only delete your own replies.', 403);

    $db->prepare('DELETE FROM review_replies WHERE reply_id = ?')->execute([$replyId]);

    respond(['success' => true]);
}

respondError('Method not allowed.', 405);
