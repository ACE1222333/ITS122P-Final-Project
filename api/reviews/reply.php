<?php
/* ════════════════════════════════════════════════════════════════
   api/reviews/reply.php — Save or clear an admin reply on a review.
   POST { review_id, reply }  — requires admin session.
   Passing reply = "" clears the existing reply.
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respondError('Method not allowed.', 405);
requireAdmin();

$body      = getJsonBody();
$reviewId  = isset($body['review_id']) ? (int)$body['review_id'] : 0;
$reply     = trim($body['reply'] ?? '');

if ($reviewId <= 0) respondError('Valid review_id is required.');

$db  = getDB();
$chk = $db->prepare('SELECT review_id FROM reviews WHERE review_id = ? LIMIT 1');
$chk->execute([$reviewId]);
if (!$chk->fetch()) respondError('Review not found.', 404);

if ($reply === '') {
    $db->prepare('UPDATE reviews SET admin_reply = NULL, reply_date = NULL WHERE review_id = ?')
       ->execute([$reviewId]);
    respond(['success' => true, 'reply' => null, 'reply_date' => null]);
}

$db->prepare('UPDATE reviews SET admin_reply = ?, reply_date = NOW() WHERE review_id = ?')
   ->execute([$reply, $reviewId]);

$replyDate = fmtDate(date('Y-m-d H:i:s'));
respond(['success' => true, 'reply' => $reply, 'reply_date' => $replyDate]);
