<?php
session_start();
include('connection.php');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Receipt — BuyTheBella</title>
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
        text-decoration: none;
        margin-bottom: 1.6rem;
        background: none;
        border: none;
        font-family: "DM Sans", sans-serif;
        cursor: pointer;
        transition: color 0.2s;
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
        padding: 2rem 2rem 1.5rem;
        margin-bottom: 1.2rem;
      }
      .doc-logo {
        font-family: "Bebas Neue", sans-serif;
        font-size: 1.6rem;
        letter-spacing: 0.12em;
        margin-bottom: 0.25rem;
      }
      .doc-tagline {
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-bottom: 1.4rem;
      }
      .doc-title-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1.4rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border);
      }
      .doc-title {
        font-family: "Bebas Neue", sans-serif;
        font-size: 1.6rem;
        letter-spacing: 0.08em;
      }
      .doc-number {
        font-size: 0.78rem;
        color: var(--text-muted);
        font-family: monospace;
        margin-top: 0.3rem;
      }

      .doc-meta-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem 1.5rem;
        margin-bottom: 1.4rem;
      }
      @media (max-width: 480px) {
        .doc-meta-grid {
          grid-template-columns: 1fr;
        }
      }
      .doc-meta-label {
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 0.15rem;
      }
      .doc-meta-value {
        font-size: 0.84rem;
        font-weight: 500;
      }
      .doc-meta-approved {
        color: #16a34a;
      }

      .doc-address {
        margin-bottom: 1.4rem;
      }
      .doc-divider {
        border: none;
        border-top: 1px solid var(--border);
        margin: 1.2rem 0;
      }

      .doc-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.82rem;
        margin-bottom: 0.5rem;
      }
      .doc-table th {
        text-align: left;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--text-muted);
        padding: 0 0 0.6rem;
        border-bottom: 1px solid var(--border);
      }
      .doc-table th:not(:first-child) {
        text-align: right;
      }
      .doc-table td {
        padding: 0.55rem 0;
        border-bottom: 1px solid var(--border);
        vertical-align: top;
      }
      .doc-table td:not(:first-child) {
        text-align: right;
      }
      .doc-table tbody tr:last-child td {
        border-bottom: none;
      }
      .doc-table tfoot td {
        padding-top: 0.5rem;
        color: var(--text-muted);
        font-size: 0.82rem;
      }
      .doc-table tfoot .total-row td {
        font-weight: 700;
        font-size: 0.9rem;
        color: var(--text);
        border-top: 1px solid var(--border);
        padding-top: 0.65rem;
      }

      .doc-footer-note {
        font-size: 0.76rem;
        color: var(--text-muted);
        text-align: center;
        padding-top: 1rem;
        border-top: 1px solid var(--border);
        margin-top: 0.5rem;
        line-height: 1.6;
      }
      .doc-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
      }
      .btn-doc-print {
        background: var(--accent);
        color: #fff;
        border: none;
        border-radius: 9px;
        padding: 0.75rem 1.6rem;
        font-family: "DM Sans", sans-serif;
        font-size: 0.86rem;
        font-weight: 600;
        cursor: pointer;
        transition: opacity 0.2s;
      }
      .btn-doc-print:hover {
        opacity: 0.85;
      }
      body.dark .btn-doc-print {
        color: #0f0f0f;
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

      @media print {
        .doc-back,
        .doc-actions,
        nav,
        footer,
        .topnav,
        .sidebar {
          display: none !important;
        }
        .doc-wrap {
          padding: 0;
        }
        .doc-card {
          border: none;
          border-radius: 0;
          padding: 0;
        }
      }
    </style>
  </head>
  <body>
    <div style="max-width: 680px; margin: 0 auto; padding: 2.5rem 1.5rem 4rem" id="page-wrap">
      <div class="state-msg" id="state-msg">Loading receipt…</div>
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
      const backUrl = from === "history" ? "shop-order-history.php" : "shop-my-orders.php";
      const backLabel = from === "history" ? "Back to Order History" : "Back to My Orders";

      if (!orderId) {
        document.getElementById("state-msg").textContent = "No order specified.";
      } else {
        loadReceipt(orderId);
      }

      async function loadReceipt(id) {
        try {
          const res = await shopFetch(`api/receipts.php?order_id=${id}`);
          const data = await res.json();
          if (!res.ok || data.error) throw new Error(data.error || "Could not load receipt.");
          renderReceipt(data);
        } catch (e) {
          document.getElementById("state-msg").innerHTML = `<div style="font-size:1.5rem;margin-bottom:1rem;opacity:0.4;">📄</div>
       <div style="font-weight:600;margin-bottom:0.5rem;">Receipt not available</div>
       <div style="font-size:0.82rem;color:var(--text-muted);">${escHtml(e.message)}</div>`;
        }
      }

      function renderReceipt(r) {
        const fmt = (v) => Number(v).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const fmtDt = (s) => (s ? new Date(s).toLocaleString("en-PH", { dateStyle: "medium", timeStyle: "short" }) : "—");

        const itemRows = (r.items || [])
          .map(
            (item) => `
    <tr>
      <td>${escHtml(item.name)}</td>
      <td>${escHtml(item.size || "—")}</td>
      <td>₱${fmt(item.price)}</td>
    </tr>`,
          )
          .join("");

        const shippingRow =
          r.shippingFee > 0
            ? `
    <tr><td colspan="2" style="text-align:right;padding-top:0.5rem;color:var(--text-muted);">Shipping (J&T Express)</td><td style="padding-top:0.5rem;">₱${fmt(r.shippingFee)}</td></tr>`
            : "";

        document.getElementById("page-wrap").innerHTML = `
    <a class="doc-back" href="${backUrl}">
      <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
      ${backLabel}
    </a>

    <div class="doc-card">
      <div class="doc-logo">BuyTheBella</div>
      <div class="doc-tagline">Official Payment Receipt</div>

      <div class="doc-title-row">
        <div>
          <div class="doc-title">Payment Receipt</div>
          <div class="doc-number">${escHtml(r.receiptNumber)}</div>
        </div>
      </div>

      <div class="doc-meta-grid">
        <div>
          <div class="doc-meta-label">Order ID</div>
          <div class="doc-meta-value">#${r.orderId}</div>
        </div>
        <div>
          <div class="doc-meta-label">Receipt Date</div>
          <div class="doc-meta-value">${fmtDt(r.generatedAt)}</div>
        </div>
        <div>
          <div class="doc-meta-label">Payment Method</div>
          <div class="doc-meta-value">${escHtml(r.payment.method || "GCash")}</div>
        </div>
        <div>
          <div class="doc-meta-label">Reference No.</div>
          <div class="doc-meta-value" style="font-family:monospace;">${escHtml(r.payment.referenceNumber || "—")}</div>
        </div>
        <div>
          <div class="doc-meta-label">Payment Date</div>
          <div class="doc-meta-value">${fmtDt(r.payment.datePaid)}</div>
        </div>
        <div>
          <div class="doc-meta-label">Payment Status</div>
          <div class="doc-meta-value doc-meta-approved">✓ ${escHtml(r.payment.status || "Approved")}</div>
        </div>
      </div>

      ${
        r.deliveryAddress
          ? `
      <div class="doc-address">
        <div class="doc-meta-label">Delivery Address</div>
        <div class="doc-meta-value">${escHtml(r.deliveryAddress)}</div>
      </div>`
          : ""
      }

      <hr class="doc-divider">

      <table class="doc-table">
        <thead>
          <tr>
            <th>Item</th>
            <th>Size</th>
            <th>Price</th>
          </tr>
        </thead>
        <tbody>${itemRows}</tbody>
        <tfoot>
          <tr><td colspan="2" style="text-align:right;color:var(--text-muted);">Subtotal</td><td>₱${fmt(r.subtotal)}</td></tr>
          ${shippingRow}
          <tr class="total-row"><td colspan="2" style="text-align:right;">Total</td><td>₱${fmt(r.totalAmount)}</td></tr>
        </tfoot>
      </table>

      <div class="doc-footer-note">
        Thank you for your purchase! This is your official payment receipt from BuyTheBella.
      </div>
    </div>

`;
      }

      function escHtml(s) {
        return String(s ?? "")
          .replace(/&/g, "&amp;")
          .replace(/</g, "&lt;")
          .replace(/>/g, "&gt;")
          .replace(/"/g, "&quot;");
      }
    </script>
  </body>
</html>
