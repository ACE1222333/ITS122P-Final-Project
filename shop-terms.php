<?php
session_start();
include('connection.php');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Terms &amp; Privacy &mdash; BuyTheBella</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="shop-styles.css" />
    <style>
      .terms-page {
        max-width: 780px;
        margin: 0 auto;
        padding: 3rem 1.5rem 5rem;
      }
      .terms-hero {
        margin-bottom: 2.5rem;
      }
      .terms-hero h1 {
        font-family: "Bebas Neue", sans-serif;
        font-size: 3rem;
        letter-spacing: 0.1em;
        margin-bottom: 0.4rem;
      }
      .terms-hero p {
        color: var(--text-muted);
        font-size: 0.84rem;
      }

      .terms-toc {
        background: var(--bg-section);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 1.2rem 1.5rem;
        margin-bottom: 2.5rem;
      }
      .terms-toc-title {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 0.75rem;
      }
      .terms-toc ol {
        margin: 0;
        padding-left: 1.2rem;
      }
      .terms-toc li {
        font-size: 0.84rem;
        margin-bottom: 0.35rem;
      }
      .terms-toc a {
        color: var(--accent);
        text-decoration: none;
      }
      .terms-toc a:hover {
        text-decoration: underline;
      }

      .terms-section {
        margin-bottom: 2.5rem;
      }
      .terms-section-title {
        font-family: "Bebas Neue", sans-serif;
        font-size: 1.5rem;
        letter-spacing: 0.08em;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--accent);
        color: var(--text);
      }
      .terms-section p {
        font-size: 0.88rem;
        color: var(--text-muted);
        line-height: 1.8;
        margin-bottom: 0.9rem;
      }
      .terms-section ul {
        padding-left: 1.4rem;
        margin-bottom: 0.9rem;
      }
      .terms-section ul li {
        font-size: 0.88rem;
        color: var(--text-muted);
        line-height: 1.8;
        margin-bottom: 0.3rem;
      }
      .terms-section strong {
        color: var(--text);
      }

      .terms-highlight {
        background: rgba(239, 68, 68, 0.07);
        border: 1px solid rgba(239, 68, 68, 0.25);
        border-left: 3px solid #e53e3e;
        border-radius: 8px;
        padding: 1rem 1.2rem;
        margin-bottom: 1rem;
        font-size: 0.86rem;
        color: var(--text);
        line-height: 1.7;
      }
      .terms-highlight strong {
        color: #e53e3e;
      }

      .terms-divider {
        border: none;
        border-top: 1px solid var(--border);
        margin: 3rem 0;
      }
    </style>
  </head>
  <body>
    <!-- Nav, cart, auth, toast injected by shop-layout.js -->

    <div class="terms-page">
      <div class="terms-hero">
        <h1>Terms &amp; Privacy</h1>
        <p>Last updated: June 2025 &mdash; Please read these terms carefully before making a purchase.</p>
      </div>

      <!-- Table of Contents -->
      <div class="terms-toc">
        <div class="terms-toc-title">Contents</div>
        <ol>
          <li><a href="#terms">Terms &amp; Conditions</a></li>
          <li><a href="#no-refund">No Refund Policy</a></li>
          <li><a href="#shipping">Shipping Policy</a></li>
          <li><a href="#privacy">Privacy Policy</a></li>
          <li><a href="#contact">Contact Us</a></li>
        </ol>
      </div>

      <!-- TERMS & CONDITIONS -->
      <div class="terms-section" id="terms">
        <div class="terms-section-title">1. Terms &amp; Conditions</div>

        <p>By accessing and placing an order on BuyTheBella, you confirm that you have read, understood, and agree to these Terms &amp; Conditions. These terms apply to all visitors, users, and customers of our shop.</p>

        <p>
          <strong>1.1 Eligibility</strong><br />
          You must be at least 18 years old, or have parental consent, to place an order on this website. By placing an order, you confirm you meet this requirement.
        </p>

        <p>
          <strong>1.2 Product Listings</strong><br />
          All items listed on BuyTheBella are second-hand / pre-loved clothing. Each item is unique &mdash; once sold, it cannot be restocked. Product photos and descriptions accurately represent the item. Minor color variations due to lighting or screen settings may occur.
        </p>

        <p>
          <strong>1.3 Order Confirmation</strong><br />
          Placing an order and submitting payment does not guarantee your purchase. Your order is only confirmed once your payment has been verified and approved by our team. We reserve the right to cancel any order if payment cannot be verified.
        </p>

        <p>
          <strong>1.4 Pricing</strong><br />
          All prices are listed in Philippine Peso (&#8369;). Prices are final and include the item cost only. Shipping fees are separate and will be communicated after payment verification.
        </p>

        <p>
          <strong>1.5 Item Availability</strong><br />
          All items are on a <strong>first to pay, first to own</strong> basis. In rare cases where an item becomes unavailable after your order is placed, we will notify you and your payment will not be processed.
        </p>

        <p>
          <strong>1.6 Right to Refuse</strong><br />
          We reserve the right to refuse service, cancel orders, or limit quantities at our discretion.
        </p>
      </div>

      <!-- NO REFUND POLICY -->
      <div class="terms-section" id="no-refund">
        <div class="terms-section-title">2. No Refund Policy</div>

        <div class="terms-highlight"><strong>All sales are final.</strong> Once your payment is verified and your order is confirmed, we do not offer refunds, returns, or exchanges for any reason &mdash; including change of mind, incorrect size selection, or order cancellation after approval.</div>

        <p>We strongly encourage you to:</p>
        <ul>
          <li>Review all product photos carefully before purchasing</li>
          <li>Check the listed size and condition against your measurements</li>
          <li>Message us with any questions <strong>before</strong> placing an order</li>
        </ul>

        <p>
          <strong>Exceptions &mdash; Item Significantly Not as Described</strong><br />
          If you receive an item that is <em>significantly different</em> from what was described or photographed (e.g. wrong item entirely, or undisclosed major damage), please contact us within <strong>48 hours</strong> of delivery with photos. We will review each case individually and resolve it fairly. This exception does not apply to subjective differences in color, texture, or personal fit preference.
        </p>

        <p>
          <strong>No Cancellations</strong><br />
          Orders cannot be cancelled once placed. All sales are final from the moment of order submission.
        </p>
      </div>

      <!-- SHIPPING POLICY -->
      <div class="terms-section" id="shipping">
        <div class="terms-section-title">3. Shipping Policy</div>

        <p>
          <strong>3.1 Delivery Areas</strong><br />
          We ship nationwide across the Philippines via <strong>J&amp;T Express</strong>.
        </p>

        <p>
          <strong>3.2 Shipping Fees</strong><br />
          Shipping fees are <strong>shouldered by the buyer</strong> and vary by location. The exact fee will be communicated to you after your payment is verified.
        </p>

        <p>
          <strong>3.3 Handling Time</strong><br />
          Orders are packed and handed to the courier within <strong>1&ndash;3 business days</strong> after payment approval.
        </p>

        <p>
          <strong>3.4 Delivery Time</strong><br />
          Shipping fees and estimated delivery times via J&amp;T Express:
        </p>
        <ul>
          <li>Metro Manila &mdash; <strong>₱70</strong> &bull; 1&ndash;3 business days</li>
          <li>Luzon (provincial) &mdash; <strong>₱100</strong> &bull; 3&ndash;5 business days</li>
          <li>Visayas &mdash; <strong>₱130</strong> &bull; 5&ndash;7 business days</li>
          <li>Mindanao &mdash; <strong>₱160</strong> &bull; 5&ndash;7 business days</li>
        </ul>

        <p>
          <strong>3.5 Shipping Responsibility</strong><br />
          Once your order is handed to the courier, it is the courier&rsquo;s responsibility. We are not liable for delays, loss, or damage caused by the courier. However, we will assist you in filing a claim if necessary.
        </p>

        <p>
          <strong>3.6 Incorrect Address</strong><br />
          Please ensure your delivery address is complete and correct at checkout. We are not responsible for failed deliveries due to an incorrect or incomplete address provided by the buyer.
        </p>
      </div>

      <hr class="terms-divider" />

      <!-- PRIVACY POLICY -->
      <div class="terms-section" id="privacy">
        <div class="terms-section-title">4. Privacy Policy</div>

        <p>Your privacy matters to us. This Privacy Policy explains what information we collect, how we use it, and how we protect it.</p>

        <p>
          <strong>4.1 Information We Collect</strong><br />
          When you create an account or place an order, we collect:
        </p>
        <ul>
          <li>Full name and email address</li>
          <li>Phone number</li>
          <li>Delivery address (region, province, city, barangay, street)</li>
          <li>GCash reference number and payment screenshot</li>
        </ul>

        <p>
          <strong>4.2 How We Use Your Information</strong><br />
          Your information is used solely for:
        </p>
        <ul>
          <li>Processing and fulfilling your orders</li>
          <li>Communicating order status updates</li>
          <li>Verifying your GCash payment</li>
          <li>Resolving disputes or complaints</li>
        </ul>

        <p>
          <strong>4.3 Data Sharing</strong><br />
          We do <strong>not</strong> sell, rent, or share your personal information with third parties for marketing purposes. Your delivery address is shared only with J&amp;T Express for delivery purposes.
        </p>

        <p>
          <strong>4.4 Data Security</strong><br />
          Your password is encrypted and never stored in plain text. We use secure server-side sessions to manage your login. Payment screenshots are stored securely and accessed only by our team for verification purposes.
        </p>

        <p>
          <strong>4.5 Cookies</strong><br />
          We use session cookies to keep you logged in while browsing. We do not use third-party tracking or advertising cookies.
        </p>

        <p>
          <strong>4.6 Data Retention</strong><br />
          We retain your order and account information for record-keeping and dispute resolution purposes. You may request deletion of your account by contacting us directly.
        </p>

        <p>
          <strong>4.7 Your Rights</strong><br />
          You have the right to access, correct, or request deletion of your personal data. To exercise these rights, please contact us via the channels listed below.
        </p>

        <p>
          <strong>4.8 Changes to This Policy</strong><br />
          We may update this Privacy Policy from time to time. Continued use of our website after changes constitutes your acceptance of the updated policy.
        </p>
      </div>

      <!-- CONTACT -->
      <div class="terms-section" id="contact">
        <div class="terms-section-title">5. Contact Us</div>
        <p>If you have any questions about these terms or our privacy practices, please reach out:</p>
        <ul>
          <li>
            Facebook:
            <a href="https://facebook.com/your-page" target="_blank" rel="noopener" style="color: var(--accent)">facebook.com/your-page</a>
          </li>
          <li>
            Instagram:
            <a href="https://instagram.com/your-handle" target="_blank" rel="noopener" style="color: var(--accent)">@your-handle</a>
          </li>
          <li>Phone / SMS: +63 9XX XXX XXXX</li>
          <li>Email: <a href="mailto:" style="color: var(--accent)">your@email.com</a></li>
        </ul>
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
      initShopLayout("terms");
    </script>
  </body>
</html>
