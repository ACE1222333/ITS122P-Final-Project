<?php
session_start();
include('connection.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reviews — Carousell</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="shop-styles.css">
</head>
<body>

<!-- Nav, cart, auth, toast injected by shop-layout.js -->

<div class="reviews-hero">
  <h1>Customer Reviews</h1>
  <p>Real feedback from real Carousell customers.</p>
</div>

<div class="reviews-layout" style="max-width:1100px;margin:0 auto;">

  <!-- Left: list + rating summary -->
  <div class="reviews-list-wrap">
    <div class="rating-summary" id="rating-summary">
      <div class="avg-score">
        <div class="big-num"   id="avg-num">—</div>
        <div class="stars-row" id="avg-stars">☆☆☆☆☆</div>
        <small id="review-count-label">0 reviews</small>
      </div>
      <div class="rating-bars" id="rating-bars"></div>
    </div>

    <h2>All Reviews</h2>
    <div id="reviews-list">
      <div class="reviews-empty">No reviews yet. Be the first!</div>
    </div>
  </div>

  <!-- Right: form or login wall -->
  <div class="review-form-wrap">

    <!-- Shown when NOT logged in -->
    <div class="login-wall" id="review-login-wall">
      <h4>Sign in to Review</h4>
      <p>Only verified customers can post reviews. Log in or create a free account to share your experience.</p>
      <button class="btn-login-prompt" onclick="openAuth()">Log in / Register</button>
    </div>

    <!-- Shown when logged in -->
    <div class="review-form-card" id="review-form-card" style="display:none;">
      <h3>Write a Review</h3>

      <label class="rf-label">Your Rating</label>
      <div class="star-picker" id="star-picker">
        <button class="star-btn" onclick="setRating(1)">★</button>
        <button class="star-btn" onclick="setRating(2)">★</button>
        <button class="star-btn" onclick="setRating(3)">★</button>
        <button class="star-btn" onclick="setRating(4)">★</button>
        <button class="star-btn" onclick="setRating(5)">★</button>
      </div>

      <label class="rf-label">Your Review</label>
      <textarea class="rf-textarea" id="rv-body" placeholder="Share your experience with this piece…"></textarea>

      <button class="btn-submit-review" onclick="submitReview()">Post Review</button>
    </div>
  </div>
</div>

<footer><span>© 2025 Carousell. All rights reserved.</span><span style="margin-left:1.5rem;"><a href="shop-terms.php" style="color:inherit;opacity:0.6;font-size:0.78rem;text-decoration:none;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.6">Terms &amp; Privacy</a></span></footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="shop-shared.js?v=2"></script>
<script src="shop-layout.js"></script>
<script>
initShopLayout('reviews');

loadReviews(renderReviews);
</script>
</body>
</html>
