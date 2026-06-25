<?php
session_start();
include('connection.php');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Order Submission — BuyTheBella</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="shop-styles.css" />
    <style>
      .doc-wrap {
        max-width: 680px;
        margin: 0 auto;
        padding: 2.5rem 1.5rem 4rem;
      }
      .doc-back {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8rem;
        color: var(--text-muted);
        background: none;
        border: none;
        font-family: "DM Sans", sans-serif;
        cursor: pointer;
        transition: color 0.2s;
        margin-bottom: 1.6rem;
      }
      .doc-back:hover {
        color: var(--text);
      }
      .doc-back svg {
        width: 15px;
        height: 15px;
        stroke: currentColor;
        fill: none;
        stroke-width: 2;
      }

      .doc-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 2rem;
        margin-bottom: 1.2rem;
      }
      .doc-heading {
        font-family: "Bebas Neue", sans-serif;
        font-size: 1.6rem;
        letter-spacing: 0.08em;
        margin-bottom: 0.25rem;
      }
      .doc-sub {
        font-size: 0.78rem;
        color: var(--text-muted);
        margin-bottom: 1.4rem;
      }

      .rejected-banner {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        background: rgba(239, 68, 68, 0.07);
        border: 1px solid rgba(239, 68, 68, 0.25);
        border-radius: 10px;
        padding: 0.9rem 1rem;
        margin-bottom: 1.4rem;
        font-size: 0.84rem;
      }
      .rejected-banner svg {
        width: 18px;
        height: 18px;
        stroke: #ef4444;
        fill: none;
        stroke-width: 2;
        flex-shrink: 0;
        margin-top: 0.05rem;
      }
      .rejected-banner strong {
        display: block;
        margin-bottom: 0.2rem;
        color: #ef4444;
      }

      .section-label {
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 0.75rem;
      }
      .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem 1.5rem;
        margin-bottom: 1.4rem;
      }
      @media (max-width: 480px) {
        .info-grid {
          grid-template-columns: 1fr;
        }
      }
      .info-label {
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 0.15rem;
      }
      .info-value {
        font-size: 0.84rem;
        font-weight: 500;
      }

      .doc-divider {
        border: none;
        border-top: 1px solid var(--border);
        margin: 1.2rem 0;
      }

      .item-row {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        padding: 0.65rem 0;
        border-bottom: 1px solid var(--border);
      }
      .item-row:last-child {
        border-bottom: none;
      }
      .item-img {
        width: 48px;
        height: 56px;
        border-radius: 7px;
        object-fit: cover;
        background: var(--bg-section);
        flex-shrink: 0;
      }
      .item-img-ph {
        width: 48px;
        height: 56px;
        border-radius: 7px;
        background: var(--bg-section);
        flex-shrink: 0;
      }
      .item-name {
        font-size: 0.84rem;
        font-weight: 600;
      }
      .item-meta {
        font-size: 0.74rem;
        color: var(--text-muted);
        margin-top: 0.15rem;
      }
      .item-price {
        font-size: 0.86rem;
        font-weight: 600;
        margin-left: auto;
        flex-shrink: 0;
      }

      .total-row {
        display: flex;
        justify-content: space-between;
        font-size: 0.83rem;
        padding: 0.4rem 0;
      }
      .total-row.grand {
        font-weight: 700;
        font-size: 0.95rem;
        border-top: 1px solid var(--border);
        padding-top: 0.65rem;
        margin-top: 0.25rem;
      }
      .total-label {
        color: var(--text-muted);
      }
      .total-label.grand {
        color: var(--text);
      }

      .proof-img-wrap {
        margin-top: 0.75rem;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid var(--border);
        cursor: zoom-in;
      }
      .proof-img-wrap img {
        display: block;
        width: 100%;
        max-height: 300px;
        object-fit: contain;
        background: var(--bg-section);
      }
      .proof-none {
        font-size: 0.8rem;
        color: var(--text-muted);
        font-style: italic;
        padding: 0.5rem 0;
      }

      .btn-doc-back {
        background: none;
        border: 1.5px solid var(--border);
        border-radius: 9px;
        padding: 0.75rem 1.4rem;
        font-family: "DM Sans", sans-serif;
        font-size: 0.86rem;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.2s;
      }
      .btn-doc-back:hover {
        border-color: var(--text);
        color: var(--text);
      }

      .state-msg {
        text-align: center;
        padding: 5rem 2rem;
        color: var(--text-muted);
        font-size: 0.9rem;
      }

      /* lightbox */
      .lbx {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.9);
        z-index: 1200;
        align-items: center;
        justify-content: center;
        cursor: zoom-out;
      }
      .lbx.open {
        display: flex;
      }
      .lbx img {
        max-width: 92vw;
        max-height: 92vh;
        object-fit: contain;
        border-radius: 8px;
      }
    </style>
  </head>
  <body>
    <div class="doc-wrap" id="page-wrap">
      <div class="state-msg" id="state-msg">Loading…</div>
    </div>

    <div class="lbx" id="lbx" onclick="this.classList.remove('open')">
      <img id="lbx-img" src="" alt="Proof of Payment" />
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
      initShopLayout("");

      const orderId = new URLSearchParams(location.search).get("order_id");
      const from = new URLSearchParams(location.search).get("from");
      const backUrl = from === "orders" ? "shop-my-orders.php" : "shop-order-history.php";
      const backLabel = from === "orders" ? "Back to My Orders" : "Back to Order History";

      if (!orderId) {
        document.getElementById("state-msg").textContent = "No order specified.";
      } else {
        loadSubmission(orderId);
      }

      async function loadSubmission(id) {
        try {
          const res = await shopFetch("api/my-orders.php");
          const data = await res.json();
          if (!res.ok || data.error) throw new Error(data.error || "Could not load order.");
          const order = (Array.isArray(data) ? data : []).find((o) => String(o.id) === String(id));
          if (!order) throw new Error("Order not found.");
          renderSubmission(order);
        } catch (e) {
          document.getElementById("state-msg").innerHTML = `<div style="font-size:1.5rem;margin-bottom:1rem;opacity:0.4;">📋</div>
       <div style="font-weight:600;margin-bottom:0.5rem;">Could not load submission</div>
       <div style="font-size:0.82rem;color:var(--text-muted);">${esc(e.message)}</div>`;
        }
      }

      function renderSubmission(o) {
        const date = (o.dateOrdered || "").split(" ")[0];
        const shippingFee = o.shippingFee || 0;
        const subtotal = Number(o.totalAmount) - shippingFee;
        const rejReason = o.rejectionReason || "";

        const itemsHtml = (o.products || [])
          .map((p) => {
            const img = p.image || p.coverImage || "";
            return `<div class="item-row">
      ${img ? `<img class="item-img" src="${esc(img)}" alt="${esc(p.name)}" loading="lazy">` : `<div class="item-img-ph"></div>`}
      <div style="flex:1;min-width:0;">
        <div class="item-name">${esc(p.name)}</div>
        <div class="item-meta">Size: ${esc(p.size || "—")}</div>
      </div>
      <div class="item-price">₱${Number(p.price).toLocaleString()}</div>
    </div>`;
          })
          .join("");

        const shippingRowHtml =
          shippingFee > 0
            ? `
    <div class="total-row"><span class="total-label">Shipping (J&T Express)</span><span>₱${shippingFee.toLocaleString()}</span></div>`
            : "";

        const proofHtml = o.payment?.proofImage
          ? `<div class="proof-img-wrap" onclick="openLbx('${esc(o.payment.proofImage).replace(/'/g, "&#39;")}')">
         <img src="${esc(o.payment.proofImage)}" alt="Proof of Payment">
       </div>
       <div style="font-size:0.72rem;color:var(--text-muted);margin-top:0.4rem;">Tap to enlarge</div>`
          : `<div class="proof-none">No screenshot was uploaded.</div>`;

        document.getElementById("page-wrap").innerHTML = `
    <a class="doc-back" href="${backUrl}">
      <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
      ${backLabel}
    </a>

    <div class="doc-card">
      <div class="doc-heading">Order #${o.id} — Submission</div>
      <div class="doc-sub">Placed ${date}</div>

      <div class="rejected-banner">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        <div>
          <strong>Payment Rejected</strong>
          ${rejReason ? `<div>Reason: ${esc(rejReason)}</div>` : `<div>Your payment could not be verified. Please check your GCash details and try again.</div>`}
        </div>
      </div>

      <div class="section-label">Payment Details Submitted</div>
      <div class="info-grid">
        <div>
          <div class="info-label">Payment Method</div>
          <div class="info-value">${esc(o.payment?.method || "GCash")}</div>
        </div>
        <div>
          <div class="info-label">Reference No.</div>
          <div class="info-value" style="font-family:monospace;">${esc(o.payment?.referenceNumber || "Not provided")}</div>
        </div>
      </div>

      <div class="section-label">Proof of Payment</div>
      ${proofHtml}

      <hr class="doc-divider">

      <div class="section-label">Items Ordered</div>
      ${itemsHtml}

      <div style="margin-top:0.75rem;">
        <div class="total-row"><span class="total-label">Subtotal</span><span>₱${subtotal.toLocaleString()}</span></div>
        ${shippingRowHtml}
        <div class="total-row grand"><span class="total-label grand">Total</span><span>₱${Number(o.totalAmount).toLocaleString()}</span></div>
      </div>

      <hr class="doc-divider">

      <div class="section-label">Delivery Address</div>
      <div class="info-value">${esc(o.user?.address || "Not provided")}</div>
    </div>

    `;
      }

      function openLbx(src) {
        document.getElementById("lbx-img").src = src;
        document.getElementById("lbx").classList.add("open");
      }

      function esc(s) {
        return String(s ?? "")
          .replace(/&/g, "&amp;")
          .replace(/</g, "&lt;")
          .replace(/>/g, "&gt;")
          .replace(/"/g, "&quot;");
      }
    </script>
  </body>
</html>
