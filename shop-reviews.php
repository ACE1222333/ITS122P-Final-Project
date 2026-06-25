<?php
session_start();
include('connection.php');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reviews — BuyTheBella</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="shop-styles.css" />
  </head>
  <body>
    <!-- Nav, cart, auth, toast injected by shop-layout.js -->

    <div class="reviews-hero">
      <h1>Customer Reviews</h1>
      <p>Real feedback from real BuyTheBella customers.</p>
    </div>

    <div class="reviews-layout" style="max-width: 1100px; margin: 0 auto">
      <!-- Left: list + rating summary -->
      <div class="reviews-list-wrap">
        <!-- Mobile-only: Write a Review toggle button -->
        <div class="mobile-write-review-btn-wrap">
          <button class="mobile-write-review-btn" id="mobile-write-review-btn" onclick="toggleMobileReviewForm()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
              <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
            </svg>
            Write a Review
          </button>
          <!-- Collapsible form panel (mobile only) -->
          <div class="mobile-review-form-panel" id="mobile-review-form-panel">
            <div class="mobile-review-form-inner">
              <div class="login-wall" id="mobile-review-login-wall">
                <h4>Sign in to Review</h4>
                <p>Only verified customers can post reviews. Log in or create a free account.</p>
                <button class="btn-login-prompt" onclick="openAuth()">Log in / Register</button>
              </div>
              <div class="review-form-card" id="mobile-review-form-card" style="display: none">
                <label class="rf-label">Your Rating</label>
                <div class="star-picker" id="mobile-star-picker">
                  <button class="star-btn" onclick="setRating(1, 'mobile')">★</button>
                  <button class="star-btn" onclick="setRating(2, 'mobile')">★</button>
                  <button class="star-btn" onclick="setRating(3, 'mobile')">★</button>
                  <button class="star-btn" onclick="setRating(4, 'mobile')">★</button>
                  <button class="star-btn" onclick="setRating(5, 'mobile')">★</button>
                </div>
                <label class="rf-label">Your Review</label>
                <textarea class="rf-textarea" id="mobile-rv-body" placeholder="Share your experience with this piece…"></textarea>
                <button class="btn-submit-review" onclick="submitReview('mobile')">Post Review</button>
              </div>
            </div>
          </div>
        </div>

        <div class="rating-summary" id="rating-summary">
          <div class="avg-score">
            <div class="big-num" id="avg-num">—</div>
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

      <!-- Right: form or login wall (desktop sidebar) -->
      <div class="review-form-wrap">
        <div class="login-wall" id="review-login-wall">
          <h4>Sign in to Review</h4>
          <p>Only verified customers can post reviews. Log in or create a free account to share your experience.</p>
          <button class="btn-login-prompt" onclick="openAuth()">Log in / Register</button>
        </div>

        <div class="review-form-card" id="review-form-card" style="display: none">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="shop-shared.js?v=3"></script>
    <script src="shop-cart.js"></script>
    <script src="shop-auth.js"></script>
    <script src="shop-orders.js"></script>
    <script src="shop-reviews-modal.js"></script>
    <script src="shop-payment-form.js"></script>
    <script src="shop-layout.js"></script>
    <script>
      initShopLayout("reviews");

      loadReviews(renderReviews);
    </script>
  </body>
</html>
