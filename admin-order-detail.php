<?php
session_start();
include('connection.php');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Order Detail — BuyTheBella Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="admin-styles.css" />
    <style>
      .detail-wrap {
        max-width: 860px;
        margin: 0 auto;
        padding: 2rem 1.5rem 4rem;
      }
      .detail-back {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        font-size: 0.8rem;
        color: var(--text-muted);
        text-decoration: none;
        margin-bottom: 1.6rem;
        transition: color 0.2s;
      }
      .detail-back:hover {
        color: var(--text);
      }
      .detail-back svg {
        width: 15px;
        height: 15px;
        stroke: currentColor;
        fill: none;
        stroke-width: 2;
      }

      .detail-heading {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 2rem;
      }
      .detail-order-id {
        font-family: "Bebas Neue", sans-serif;
        font-size: 2.2rem;
        letter-spacing: 0.08em;
        line-height: 1;
      }
      .detail-order-date {
        font-size: 0.78rem;
        color: var(--text-muted);
        margin-top: 0.3rem;
      }

      .detail-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
        margin-bottom: 1.25rem;
      }
      @media (max-width: 640px) {
        .detail-grid {
          grid-template-columns: 1fr;
        }
      }

      .detail-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 1.4rem 1.5rem;
      }
      .detail-card-full {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 1.4rem 1.5rem;
        margin-bottom: 1.25rem;
      }
      .detail-card-title {
        font-size: 0.66rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }
      .detail-card-title svg {
        width: 13px;
        height: 13px;
        stroke: var(--text-muted);
        fill: none;
        stroke-width: 2;
        flex-shrink: 0;
      }

      .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 0.55rem;
        font-size: 0.83rem;
      }
      .detail-row:last-child {
        margin-bottom: 0;
      }
      .detail-key {
        color: var(--text-muted);
        flex-shrink: 0;
      }
      .detail-val {
        text-align: right;
        font-weight: 500;
        word-break: break-word;
        max-width: 62%;
      }
      .detail-val.mono {
        font-family: monospace;
        font-size: 0.78rem;
        letter-spacing: 0.04em;
      }

      .product-line {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--border);
      }
      .product-line:last-child {
        border-bottom: none;
      }
      .product-img {
        width: 56px;
        height: 66px;
        border-radius: 8px;
        object-fit: cover;
        background: var(--bg-section);
        flex-shrink: 0;
      }
      .product-img-ph {
        width: 56px;
        height: 66px;
        border-radius: 8px;
        background: var(--bg-section);
        flex-shrink: 0;
      }
      .product-name {
        font-size: 0.86rem;
        font-weight: 600;
        margin-bottom: 0.2rem;
      }
      .product-meta {
        font-size: 0.75rem;
        color: var(--text-muted);
      }
      .product-price {
        font-size: 0.86rem;
        font-weight: 600;
        margin-left: auto;
        flex-shrink: 0;
      }

      .total-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        padding-top: 0.55rem;
        margin-top: 0.35rem;
        font-size: 0.83rem;
      }
      .total-row.grand {
        border-top: 1px solid var(--border);
        padding-top: 0.75rem;
        margin-top: 0.5rem;
        font-weight: 600;
      }
      .total-label {
        color: var(--text-muted);
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
      }
      .total-label.grand {
        color: var(--text);
        font-size: 0.78rem;
      }
      .total-amount-big {
        font-family: "Bebas Neue", sans-serif;
        font-size: 1.6rem;
        letter-spacing: 0.06em;
      }

      .proof-box {
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid var(--border);
        cursor: pointer;
        position: relative;
        background: var(--bg-section);
        margin-top: 0.75rem;
      }
      .proof-box img {
        display: block;
        width: 100%;
        max-height: 240px;
        object-fit: cover;
        transition: opacity 0.2s;
      }
      .proof-box:hover img {
        opacity: 0.85;
      }
      .proof-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0);
        transition: background 0.2s;
      }
      .proof-box:hover .proof-overlay {
        background: rgba(0, 0, 0, 0.12);
      }
      .proof-hint {
        background: rgba(0, 0, 0, 0.6);
        color: #fff;
        font-size: 0.72rem;
        font-weight: 500;
        padding: 0.3rem 0.7rem;
        border-radius: 20px;
        opacity: 0;
        transition: opacity 0.2s;
      }
      .proof-box:hover .proof-hint {
        opacity: 1;
      }
      .proof-none {
        font-size: 0.8rem;
        color: var(--text-muted);
        font-style: italic;
        padding: 0.4rem 0;
      }

      /* rejection banner */
      .rejection-banner {
        margin-top: 0.75rem;
        padding: 0.65rem 0.9rem;
        background: rgba(239, 68, 68, 0.07);
        border: 1px solid rgba(239, 68, 68, 0.25);
        border-radius: 8px;
        font-size: 0.8rem;
        color: var(--red, #dc2626);
        line-height: 1.5;
        display: none;
      }

      /* action card */
      .action-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 1.4rem 1.5rem;
        margin-bottom: 1.25rem;
      }
      .action-title {
        font-size: 0.66rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 1rem;
      }
      .action-btns {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.65rem;
      }
      .btn-approve {
        background: var(--green);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 0.8rem;
        font-family: "DM Sans", sans-serif;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        transition: opacity 0.2s;
      }
      .btn-approve:hover:not(:disabled) {
        opacity: 0.85;
      }
      .btn-approve:disabled {
        opacity: 0.4;
        cursor: not-allowed;
      }
      .btn-reject {
        background: var(--red);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 0.8rem;
        font-family: "DM Sans", sans-serif;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        transition: opacity 0.2s;
      }
      .btn-reject:hover:not(:disabled) {
        opacity: 0.85;
      }
      .btn-reject:disabled {
        opacity: 0.4;
        cursor: not-allowed;
      }
      .btn-approve svg,
      .btn-reject svg {
        width: 15px;
        height: 15px;
        stroke: currentColor;
        fill: none;
        stroke-width: 2.5;
      }

      /* fulfillment */
      .form-label {
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--text-muted);
        display: block;
        margin-bottom: 0.4rem;
      }
      .form-select {
        width: 100%;
        background: var(--bg);
        border: 1.5px solid var(--border);
        border-radius: 8px;
        padding: 0.65rem 0.85rem;
        font-family: "DM Sans", sans-serif;
        font-size: 0.82rem;
        color: var(--text);
        outline: none;
        cursor: pointer;
        transition: border-color 0.2s;
        margin-bottom: 0.9rem;
      }
      .form-select:focus {
        border-color: var(--accent);
      }
      .form-textarea {
        width: 100%;
        border: 1.5px solid var(--border);
        border-radius: 8px;
        padding: 0.6rem 0.85rem;
        font-family: "DM Sans", sans-serif;
        font-size: 0.8rem;
        color: var(--text);
        background: var(--bg);
        outline: none;
        resize: vertical;
        min-height: 80px;
        transition: border-color 0.2s;
        line-height: 1.55;
        margin-bottom: 0.9rem;
      }
      .form-textarea:focus {
        border-color: var(--accent);
      }
      .btn-save {
        width: 100%;
        background: var(--accent);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 0.8rem;
        font-family: "DM Sans", sans-serif;
        font-size: 0.86rem;
        font-weight: 500;
        letter-spacing: 0.05em;
        cursor: pointer;
        transition: opacity 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
      }
      .btn-save:hover:not(:disabled) {
        opacity: 0.85;
      }
      .btn-save:disabled {
        opacity: 0.5;
        cursor: not-allowed;
      }
      .btn-save svg {
        width: 15px;
        height: 15px;
        stroke: currentColor;
        fill: none;
        stroke-width: 2.5;
      }
      .save-success {
        display: none;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.78rem;
        color: var(--green);
        font-weight: 500;
        justify-content: center;
        margin-top: 0.6rem;
      }
      .save-success.show {
        display: flex;
      }
      .save-success svg {
        width: 14px;
        height: 14px;
        stroke: var(--green);
        fill: none;
        stroke-width: 2.5;
      }

      /* loading / error */
      .state-msg {
        text-align: center;
        padding: 4rem 2rem;
        color: var(--text-muted);
        font-size: 0.9rem;
      }

      /* lightbox */
      .lightbox-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.88);
        z-index: 900;
        align-items: center;
        justify-content: center;
        padding: 1rem;
      }
      .lightbox-overlay.open {
        display: flex;
      }
      .lightbox-img {
        max-width: 90vw;
        max-height: 90vh;
        object-fit: contain;
        border-radius: 8px;
      }
      .lightbox-close {
        position: absolute;
        top: 1.2rem;
        right: 1.2rem;
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 38px;
        height: 38px;
        font-size: 1.1rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
      }
      .lightbox-close:hover {
        background: rgba(255, 255, 255, 0.3);
      }

      @keyframes spin {
        to {
          transform: rotate(360deg);
        }
      }
    </style>
  </head>
  <body>
    <div class="shell">
      <main class="main">
        <div class="detail-wrap" id="detail-wrap">
          <div class="state-msg" id="state-msg">Loading…</div>
        </div>
      </main>
    </div>

    <div class="lightbox-overlay" id="lightbox" onclick="closeLightbox()">
      <button class="lightbox-close" onclick="closeLightbox()">✕</button>
      <img class="lightbox-img" id="lightbox-img" src="" alt="Proof of Payment" />
    </div>

    <script src="admin-data.js"></script>
    <script src="admin-layout.js?v=2"></script>
    <script>
      const params = new URLSearchParams(location.search);
      const orderId = params.get("id");
      const from = params.get("from") || "orders"; // 'payments' | 'orders'
      const backUrl = from === "payments" ? "admin-payments.php" : "admin-orders.php";
      const backLabel = from === "payments" ? "Back to Payments" : "Back to Orders";

      const activeNav = from === "payments" ? "payments" : "orders";
      initAdminLayout(activeNav);
      checkAdminAuth();

      if (!orderId) {
        document.getElementById("state-msg").textContent = "No order ID specified.";
      } else {
        if (from === "payments") {
          fetchPayments((data) => renderDetail(data.find((x) => String(x.id) === String(orderId))));
        } else {
          fetchOrders((data) => renderDetail(data.find((x) => String(x.id) === String(orderId))));
        }
      }

      function renderDetail(o) {
        const wrap = document.getElementById("detail-wrap");
        if (!o) {
          wrap.innerHTML = '<div class="state-msg">Order not found.</div>';
          return;
        }

        const shippingFee = o.shippingFee || 0;
        const itemsSubtotal = Number(o.totalAmount) - shippingFee;

        const productsHtml = (o.products || [])
          .map((p) => {
            const img = p.coverImage || p.image || "";
            return `<div class="product-line">
      ${img ? `<img class="product-img" src="${esc(img)}" alt="${esc(p.name)}" loading="lazy">` : `<div class="product-img-ph"></div>`}
      <div style="flex:1;min-width:0;">
        <div class="product-name">${esc(p.name)}</div>
        <div class="product-meta">Size: ${esc(p.size || "—")}</div>
      </div>
      <div class="product-price">₱${Number(p.price).toLocaleString()}</div>
    </div>`;
          })
          .join("");

        const shippingRowHtml = shippingFee > 0 ? `<div class="total-row"><span class="total-label">Shipping (J&T Express)</span><span style="color:var(--text-muted)">₱${shippingFee.toLocaleString()}</span></div>` : "";

        /* Payment panel — shown only for payments view */
        let paymentSectionHtml = "";
        let actionCardHtml = "";

        if (from === "payments") {
          const proofHtml = o.payment?.proofImage
            ? `<div class="proof-box" onclick="openLightbox('${escAttr(o.payment.proofImage)}')">
           <img src="${esc(o.payment.proofImage)}" alt="Proof of Payment">
           <div class="proof-overlay"><span class="proof-hint">Click to enlarge</span></div>
         </div>`
            : `<p class="proof-none">Screenshot not yet uploaded.</p>`;

          const rejectionHtml = o.payment?.status === "Rejected" && o.rejectionReason ? `<div class="rejection-banner" style="display:block;" id="rejection-banner">✕ Reason: ${esc(o.rejectionReason)}</div>` : `<div class="rejection-banner" id="rejection-banner"></div>`;

          paymentSectionHtml = `
    <div class="detail-card-full">
      <div class="detail-card-title">
        <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        Payment Information
      </div>
      <div class="detail-row"><span class="detail-key">Method</span><span class="detail-val" id="d-pay-method">${esc(o.payment?.method || "GCash")}</span></div>
      <div class="detail-row"><span class="detail-key">Reference #</span><span class="detail-val mono" id="d-pay-ref">${esc(o.payment?.referenceNumber || "Not provided")}</span></div>
      <div class="detail-row"><span class="detail-key">Payment Status</span><span class="detail-val" id="d-pay-status">${paymentStatusBadge(o.payment?.status)}</span></div>
      ${rejectionHtml}
      <div style="margin-top:1rem;">
        <div style="font-size:0.72rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:var(--text-muted);margin-bottom:0.5rem;">Proof of Payment</div>
        ${proofHtml}
      </div>
    </div>`;

          const isFinal = o.payment?.status === "Approved";
          const isRejected = o.payment?.status === "Rejected";
          actionCardHtml = `
    <div class="action-card">
      <div class="action-title">Payment Decision</div>
      <div class="action-btns">
        <button class="btn-approve" id="btn-approve" onclick="doApprove()" ${isFinal ? "disabled" : ""}>
          <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          ${isFinal ? "Approved" : "Approve"}
        </button>
        <button class="btn-reject" id="btn-reject" onclick="doReject()" ${isRejected ? "disabled" : ""}>
          <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          ${isRejected ? "Rejected" : "Reject"}
        </button>
      </div>
    </div>`;
        } else {
          /* Orders view: show order status + admin notes form */
          const statusOptions = ["Processing", "Shipping", "Shipped", "Completed"].map((s) => `<option value="${s}" ${o.orderStatus === s ? "selected" : ""}>${s}</option>`).join("");
          actionCardHtml = `
    <div class="action-card">
      <div class="action-title">Update Fulfillment Status</div>
      <label class="form-label">Order Status</label>
      <select class="form-select" id="ord-select">${statusOptions}</select>
      <label class="form-label">Admin Notes</label>
      <textarea class="form-textarea" id="admin-notes" placeholder="Internal notes about this order…">${esc(o.adminNotes || "")}</textarea>
      <button class="btn-save" id="btn-save" onclick="doSave()">
        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        Save Changes
      </button>
      <div class="save-success" id="save-success">
        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        Status updated successfully
      </div>
    </div>`;
        }

        const orderInfoHtml =
          from === "orders"
            ? `
    <div class="detail-row"><span class="detail-key">Order Status</span><span class="detail-val" id="d-ord-status">${orderStatusBadge(o.orderStatus)}</span></div>
    <div class="detail-row"><span class="detail-key">GCash Ref #</span><span class="detail-val mono">${esc(o.payment?.referenceNumber || "—")}</span></div>`
            : `
    <div class="detail-row"><span class="detail-key">Order Status</span><span class="detail-val">${orderStatusBadge(o.orderStatus)}</span></div>`;

        wrap.innerHTML = `
    <a class="detail-back" href="${esc(backUrl)}">
      <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
      ${esc(backLabel)}
    </a>

    <div class="detail-heading">
      <div>
        <div class="detail-order-id">Order #${o.id}</div>
        <div class="detail-order-date">${o.dateOrdered ? "Placed on " + esc(o.dateOrdered) : ""}</div>
      </div>
      <div style="display:flex;align-items:center;gap:0.6rem;flex-wrap:wrap;">
        ${paymentStatusBadge(o.payment?.status || "")}
        ${orderStatusBadge(o.orderStatus || "")}
      </div>
    </div>

    ${actionCardHtml}

    <div class="detail-grid">
      <div class="detail-card">
        <div class="detail-card-title">
          <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          Customer
        </div>
        <div class="detail-row"><span class="detail-key">Name</span><span class="detail-val">${esc(o.user?.name || "—")}</span></div>
        <div class="detail-row"><span class="detail-key">Email</span><span class="detail-val">${esc(o.user?.email || "—")}</span></div>
        <div class="detail-row"><span class="detail-key">Contact</span><span class="detail-val">${esc(o.user?.phone || "Not provided")}</span></div>
        <div class="detail-row"><span class="detail-key">Address</span><span class="detail-val">${esc(o.user?.address || "Not provided")}</span></div>
      </div>

      <div class="detail-card">
        <div class="detail-card-title">
          <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          Order Info
        </div>
        <div class="detail-row"><span class="detail-key">Order #</span><span class="detail-val mono">${o.id}</span></div>
        <div class="detail-row"><span class="detail-key">Date</span><span class="detail-val">${esc(o.dateOrdered || "—")}</span></div>
        ${orderInfoHtml}
      </div>
    </div>

    <div class="detail-card-full">
      <div class="detail-card-title">
        <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
        Ordered Products
      </div>
      ${productsHtml}
      <div class="total-row"><span class="total-label">Subtotal</span><span>₱${itemsSubtotal.toLocaleString()}</span></div>
      ${shippingRowHtml}
      <div class="total-row grand">
        <span class="total-label grand">Grand Total</span>
        <span class="total-amount-big">₱${Number(o.totalAmount).toLocaleString()}</span>
      </div>
    </div>

    ${paymentSectionHtml}
  `;

        /* store ref for action handlers */
        window._order = o;
      }

      /* ── Payments: Approve ── */
      async function doApprove() {
        const o = window._order;
        const btnA = document.getElementById("btn-approve");
        const btnR = document.getElementById("btn-reject");
        btnA.disabled = true;
        btnR.disabled = true;
        btnA.innerHTML = '<svg viewBox="0 0 24 24" style="animation:spin 0.8s linear infinite"><polyline points="20 6 9 17 4 12"/></svg> Approving…';
        try {
          const data = await updateOrderStatus(o.id, "Processing", "Approved");
          if (data.error) {
            showToast(data.error);
            resetPaymentBtns(o);
            return;
          }
          o.payment.status = "Approved";
          o.orderStatus = "Processing";
          document.getElementById("d-pay-status").innerHTML = paymentStatusBadge("Approved");
          document.getElementById("rejection-banner").style.display = "none";
          btnA.disabled = true;
          btnA.innerHTML = '<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> Approved';
          btnR.disabled = true;
          showToast("Payment approved — order moved to Processing.");
        } catch (e) {
          showToast("Failed to approve. Please try again.");
          console.error(e);
          resetPaymentBtns(o);
        }
      }

      /* ── Payments: Reject ── */
      function doReject() {
        const o = window._order;
        openRejectWithReason(
          "Reject Payment?",
          `Reject the payment for Order #${o.id}? The reserved item will be released back to available. A reason is required and will be shown to the buyer.`,
          async (reason) => {
            if (!reason) {
              showToast("Please provide a rejection reason.");
              return;
            }
            const btnA = document.getElementById("btn-approve");
            const btnR = document.getElementById("btn-reject");
            btnA.disabled = true;
            btnR.disabled = true;
            btnR.innerHTML = '<svg viewBox="0 0 24 24" style="animation:spin 0.8s linear infinite"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Rejecting…';
            try {
              const data = await updateOrderStatus(o.id, "Payment Rejected", "Rejected", reason);
              if (data.error) {
                showToast(data.error);
                resetPaymentBtns(o);
                return;
              }
              o.payment.status = "Rejected";
              o.orderStatus = "Payment Rejected";
              o.rejectionReason = reason;
              document.getElementById("d-pay-status").innerHTML = paymentStatusBadge("Rejected");
              const banner = document.getElementById("rejection-banner");
              banner.textContent = "✕ Reason: " + reason;
              banner.style.display = "block";
              btnA.disabled = true;
              btnR.disabled = true;
              btnR.innerHTML = '<svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Rejected';
              showToast("Payment rejected. Product released back to available.");
            } catch (e) {
              showToast("Failed to reject. Please try again.");
              console.error(e);
              resetPaymentBtns(o);
            }
          },
          "Reject",
        );
      }

      function resetPaymentBtns(o) {
        const btnA = document.getElementById("btn-approve");
        const btnR = document.getElementById("btn-reject");
        if (!btnA || !btnR) return;
        btnA.disabled = o.payment?.status === "Approved";
        btnA.innerHTML = '<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> Approve';
        btnR.disabled = o.payment?.status === "Rejected";
        btnR.innerHTML = '<svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Reject';
      }

      /* ── Orders: Save status ── */
      function doSave() {
        const newStatus = document.getElementById("ord-select").value;
        openConfirm(`Update to "${newStatus}"?`, `This will change the order status to "${newStatus}". The customer will see the updated status on their order page.`, _doSaveConfirmed, "Update Status", false);
      }

      async function _doSaveConfirmed() {
        const o = window._order;
        const newStatus = document.getElementById("ord-select").value;
        const adminNotes = document.getElementById("admin-notes").value.trim();
        const btn = document.getElementById("btn-save");
        btn.disabled = true;
        btn.innerHTML = '<svg viewBox="0 0 24 24" style="animation:spin 0.8s linear infinite"><polyline points="20 6 9 17 4 12"/></svg> Saving…';
        try {
          const data = await updateOrderStatus(o.id, newStatus, "", "", adminNotes);
          if (data.error) {
            showToast(data.error);
            resetSaveBtn();
            return;
          }
          o.orderStatus = newStatus;
          o.adminNotes = adminNotes;
          const badge = document.getElementById("d-ord-status");
          if (badge) badge.innerHTML = orderStatusBadge(newStatus);
          btn.disabled = false;
          btn.innerHTML = '<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> Save Changes';
          const ok = document.getElementById("save-success");
          ok.classList.add("show");
          setTimeout(() => ok.classList.remove("show"), 3000);
        } catch (e) {
          showToast("Failed to update status. Please try again.");
          console.error(e);
          resetSaveBtn();
        }
      }

      function resetSaveBtn() {
        const btn = document.getElementById("btn-save");
        if (!btn) return;
        btn.disabled = false;
        btn.innerHTML = '<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> Save Changes';
      }

      function openLightbox(src) {
        document.getElementById("lightbox-img").src = src;
        document.getElementById("lightbox").classList.add("open");
        document.body.style.overflow = "hidden";
      }
      function closeLightbox() {
        document.getElementById("lightbox").classList.remove("open");
        document.body.style.overflow = "";
      }

      document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeLightbox();
      });

      function esc(s) {
        return String(s ?? "")
          .replace(/&/g, "&amp;")
          .replace(/</g, "&lt;")
          .replace(/>/g, "&gt;")
          .replace(/"/g, "&quot;");
      }
      function escAttr(s) {
        return String(s ?? "").replace(/'/g, "&#39;");
      }
    </script>
  </body>
</html>
