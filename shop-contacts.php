<?php
session_start();
include('connection.php');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contact — BuyTheBella</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="shop-styles.css" />
    <style>
      /* â”€â”€ Contact hero split â”€â”€ */
      .contact-hero {
        display: grid;
        grid-template-columns: 1fr 1fr;
        min-height: 520px;
      }
      .contact-hero-left {
        padding: 4rem 3.5rem;
        background: var(--bg);
        display: flex;
        flex-direction: column;
        justify-content: center;
      }
      .contact-hero-left h1 {
        font-family: "Bebas Neue", sans-serif;
        font-size: 3.2rem;
        letter-spacing: 0.1em;
        margin-bottom: 0.5rem;
        color: var(--text);
      }
      .contact-hero-left .subtitle {
        color: var(--text-muted);
        font-size: 0.93rem;
        line-height: 1.75;
        margin-bottom: 2rem;
      }
      .contact-hero-right {
        overflow: hidden;
        background: #ccc9c2;
      }
      .contact-hero-right img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
      }

      /* â”€â”€ Contact option cards â”€â”€ */
      .contact-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.2rem;
        background: var(--bg-card);
        border: 1.5px solid var(--border);
        border-radius: 14px;
        text-decoration: none;
        color: var(--text);
        transition:
          border-color 0.2s,
          transform 0.15s,
          box-shadow 0.2s;
      }
      .contact-card:hover {
        border-color: var(--accent);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.09);
        color: var(--text);
      }
      .contact-card-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.3rem;
        color: #fff;
      }
      .contact-card-label {
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--text-muted);
      }
      .contact-card-value {
        font-size: 0.9rem;
        font-weight: 500;
        color: var(--text);
      }
      .contact-card-arrow {
        margin-left: auto;
        color: var(--text-light);
        font-size: 1rem;
        transition: transform 0.2s;
      }
      .contact-card:hover .contact-card-arrow {
        transform: translate(3px, -3px);
        color: var(--accent);
      }

      @media (max-width: 768px) {
        .contact-hero {
          grid-template-columns: 1fr;
        }
        .contact-hero-right {
          display: none;
        }
        .contact-hero-left {
          padding: 2.5rem 1.5rem;
        }
      }
    </style>
  </head>
  <body>
    <!-- Nav, cart, auth, toast injected by shop-layout.js -->

    <!-- â”€â”€ HERO SPLIT â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <div class="contact-hero">
      <div class="contact-hero-left">
        <h1>Contact Us</h1>
        <p class="subtitle">Have a question or want to know more about an item?<br />Reach out through any of the options below.</p>

        <div class="d-flex flex-column gap-3">
          <a class="contact-card" href="mailto:">
            <div class="contact-card-icon" style="background: #ea4335">
              <i class="bi bi-envelope-fill"></i>
            </div>
            <div>
              <div class="contact-card-label">Email</div>
              <div class="contact-card-value">—</div>
            </div>
            <i class="bi bi-arrow-up-right contact-card-arrow"></i>
          </a>

          <a class="contact-card" href="tel:+639606867753">
            <div class="contact-card-icon" style="background: #25d366">
              <i class="bi bi-telephone-fill"></i>
            </div>
            <div>
              <div class="contact-card-label">Phone / SMS</div>
              <div class="contact-card-value">0960 686 7753</div>
            </div>
            <i class="bi bi-arrow-up-right contact-card-arrow"></i>
          </a>

          <a class="contact-card" href="https://facebook.com/your-page" target="_blank" rel="noopener">
            <div class="contact-card-icon" style="background: #1877f2">
              <i class="bi bi-facebook"></i>
            </div>
            <div>
              <div class="contact-card-label">Facebook</div>
              <div class="contact-card-value">facebook.com/your-page</div>
            </div>
            <i class="bi bi-arrow-up-right contact-card-arrow"></i>
          </a>

          <a class="contact-card" href="https://instagram.com/your-handle" target="_blank" rel="noopener">
            <div class="contact-card-icon" style="background: radial-gradient(circle at 30% 107%, #fdf497 0%, #fd5949 45%, #d6249f 60%, #285aeb 90%)">
              <i class="bi bi-instagram"></i>
            </div>
            <div>
              <div class="contact-card-label">Instagram</div>
              <div class="contact-card-value">@your-handle</div>
            </div>
            <i class="bi bi-arrow-up-right contact-card-arrow"></i>
          </a>
        </div>
      </div>

      <div class="contact-hero-right">
        <img src="https://images.unsplash.com/photo-1567401893414-76b7b1e5a7a5?w=800&q=85&auto=format&fit=crop" alt="Contact BuyTheBella" loading="lazy" />
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
      initShopLayout("contacts");
    </script>
  </body>
</html>
