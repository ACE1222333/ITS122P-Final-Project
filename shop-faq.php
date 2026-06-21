<?php
session_start();
include('connection.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FAQ &mdash; Carousell</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="shop-styles.css">
</head>
<body>

<!-- Nav, cart, auth, toast injected by shop-layout.js -->

<div class="faq-page">

  <div class="faq-hero">
    <h1>Frequently Asked Questions</h1>
    <p>Everything you need to know about shopping with us.</p>
  </div>

  <!-- PAYMENT -->
  <div class="faq-section-title">Payment</div>

  <div class="faq-item">
    <button class="faq-question" onclick="toggleFaq(this)">
      How do I pay for my order?
      <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div class="faq-answer">
      We accept payment via <strong>GCash</strong> only. Once you proceed to checkout, scan the QR code shown on the payment page using your GCash app, enter the exact total amount, then upload a screenshot of your payment confirmation. We will verify your payment and update your order status within 24 hours.
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question" onclick="toggleFaq(this)">
      How long does payment verification take?
      <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div class="faq-answer">
      We review payments within <strong>24 hours</strong>, usually much sooner. You can track your payment status anytime under <strong>My Orders</strong> in your account. If your payment is not verified within 24 hours, please contact us directly.
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question" onclick="toggleFaq(this)">
      What happens after my payment is verified?
      <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div class="faq-answer">
      Once approved, your order moves to <strong>Processing</strong>. We will pack your item and hand it to the courier. You will see the order status update to Shipping and then Shipped as it progresses. A receipt is also automatically generated in your My Orders page.
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question" onclick="toggleFaq(this)">
      What if my payment is rejected?
      <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div class="faq-answer">
      If we cannot verify your payment, your order will be marked as <strong>Payment Rejected</strong> and the item will be released back to available. You will see the rejection reason in My Orders. Please check that your GCash screenshot is clear and shows the correct reference number, then place a new order.
    </div>
  </div>

  <!-- SHIPPING -->
  <div class="faq-section-title">Shipping</div>

  <div class="faq-item">
    <button class="faq-question" onclick="toggleFaq(this)">
      Where do you ship?
      <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div class="faq-answer">
      We ship nationwide across the Philippines via <strong>J&amp;T Express</strong>.
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question" onclick="toggleFaq(this)">
      How long does shipping take?
      <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div class="faq-answer">
      Shipping typically takes <strong>3&ndash;7 business days</strong> after your payment is approved and your order is packed. Metro Manila deliveries are usually faster (2&ndash;4 days). Remote areas may take longer.
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question" onclick="toggleFaq(this)">
      How much is the shipping fee?
      <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div class="faq-answer">
      Shipping fees depend on your location and are shouldered by the buyer. The courier fee will be communicated to you after your payment is verified and we know your complete delivery address.
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question" onclick="toggleFaq(this)">
      Can I track my order?
      <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div class="faq-answer">
      Yes. Go to <strong>My Orders</strong> in your account to see your order status in real time. Once your order is shipped, you can use the tracking number on the <a href="https://www.jtexpress.ph/trajectoryQuery" target="_blank" rel="noopener" style="color:var(--accent);">J&amp;T Express website</a> to track your package.
    </div>
  </div>

  <!-- ITEMS & CONDITION -->
  <div class="faq-section-title">Items &amp; Condition</div>

  <div class="faq-item">
    <button class="faq-question" onclick="toggleFaq(this)">
      Are all items authentic?
      <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div class="faq-answer">
      Yes. Every item is personally sourced and inspected before listing. We do not sell counterfeit or replica items. Product descriptions and photos accurately reflect the actual item you will receive.
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question" onclick="toggleFaq(this)">
      What do the condition labels mean?
      <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div class="faq-answer">
      <strong>Brand new</strong> &mdash; Never worn, tags may still be attached.<br>
      <strong>Like new</strong> &mdash; Worn once or twice, no visible flaws.<br>
      <strong>Lightly used</strong> &mdash; Worn a few times, minimal signs of use.<br>
      <strong>Well used</strong> &mdash; Noticeable wear but still in good shape.<br>
      <strong>Heavily used</strong> &mdash; Significant wear, priced accordingly.<br><br>
      You can also click <em>ⓘ What does this mean?</em> on any product to see this guide.
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question" onclick="toggleFaq(this)">
      What if the item I want is already sold?
      <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div class="faq-answer">
      Because all our items are one-of-a-kind second-hand pieces, sold items cannot be restocked. However, we regularly add new arrivals &mdash; follow us on social media to be the first to know about new drops!
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question" onclick="toggleFaq(this)">
      Can I request more photos of an item?
      <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div class="faq-answer">
      Absolutely. Message us on Facebook or Instagram before placing your order and we will send additional photos or answer any questions about the item.
    </div>
  </div>

  <!-- ORDERS & RETURNS -->
  <div class="faq-section-title">Orders &amp; Returns</div>

  <div class="faq-item">
    <button class="faq-question" onclick="toggleFaq(this)">
      Can I cancel my order?
      <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div class="faq-answer">
      Orders cannot be cancelled once placed. All sales are <strong>final</strong> — please review your order and read our <a href="shop-terms.php" target="_blank" style="color:var(--accent);">No Refund Policy</a> carefully before proceeding to payment.
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question" onclick="toggleFaq(this)">
      Do you accept returns or exchanges?
      <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div class="faq-answer">
      All sales are <strong>final</strong>. We do not offer returns, exchanges, or refunds after payment is accepted &mdash; including change-of-mind cancellations. Please review all item photos, the size guide, and condition descriptions carefully before purchasing. If you have any doubts, message us first!
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question" onclick="toggleFaq(this)">
      What if I receive the wrong item or a damaged item?
      <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div class="faq-answer">
      If you receive an item that is significantly different from what was described, or if it arrives damaged due to shipping, please contact us within <strong>48 hours</strong> of receiving your order with photos. We will review each case and do our best to resolve it fairly.
    </div>
  </div>

  <!-- ACCOUNT -->
  <div class="faq-section-title">Account</div>

  <div class="faq-item">
    <button class="faq-question" onclick="toggleFaq(this)">
      Do I need an account to browse?
      <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div class="faq-answer">
      No &mdash; anyone can browse products without an account. However, you need to be logged in to add items to your cart, place orders, and write reviews.
    </div>
  </div>

  <div class="faq-item">
    <button class="faq-question" onclick="toggleFaq(this)">
      Is my personal information safe?
      <svg class="faq-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div class="faq-answer">
      Yes. Your personal details (name, address, contact number) are used solely for order fulfillment and are never shared with third parties. Passwords are encrypted and never stored in plain text.
    </div>
  </div>

  <!-- STILL HAVE QUESTIONS -->
  <div style="margin-top:3rem;text-align:center;padding:2rem;background:var(--bg-card);border-radius:16px;border:1px solid var(--border);">
    <div style="font-family:'Bebas Neue',sans-serif;font-size:1.6rem;letter-spacing:0.08em;margin-bottom:0.5rem;">Still have questions?</div>
    <p style="color:var(--text-muted);font-size:0.88rem;margin-bottom:1.2rem;">We&rsquo;re happy to help. Reach out through any of these channels.</p>
    <button class="btn-primary" onclick="goPage('contacts')">Contact Us</button>
  </div>

</div>

<footer><span>&copy; 2025 Carousell. All rights reserved.</span><span style="margin-left:1.5rem;"><a href="shop-terms.php" style="color:inherit;opacity:0.6;font-size:0.78rem;text-decoration:none;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.6">Terms &amp; Privacy</a></span></footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="shop-shared.js?v=2"></script>
<script src="shop-layout.js"></script>
<script>
initShopLayout('faq');

function toggleFaq(btn) {
  const answer  = btn.nextElementSibling;
  const isOpen  = answer.classList.contains('open');

  /* Close all open answers */
  document.querySelectorAll('.faq-answer.open').forEach(a => a.classList.remove('open'));
  document.querySelectorAll('.faq-question.open').forEach(b => b.classList.remove('open'));

  /* Open clicked one if it was closed */
  if (!isOpen) {
    answer.classList.add('open');
    btn.classList.add('open');
  }
}
</script>
</body>
</html>
