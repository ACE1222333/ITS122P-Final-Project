/* ════════════════════════════════════════════════════════════════
   UTILITY
════════════════════════════════════════════════════════════════ */
/* ════════════════════════════════════════════════════════════════
   MY ORDERS PANEL — opens as a side drawer from the profile icon
   PHP integration point: GET api/my-orders.php (requires auth)
════════════════════════════════════════════════════════════════ */

let _ordersCache = null; /* cached after first fetch per session */
let _ordersLoaded = false;

function openMyOrders(e) {
  if (e) e.stopPropagation();
  _closeUserDropdown();
  if (!currentUser) {
    openAuth();
    showToast("Please log in to view your orders.");
    return;
  }
  _openOrdersPanel("active");
}

function openOrderHistory(e) {
  if (e) e.stopPropagation();
  _closeUserDropdown();
  if (!currentUser) {
    openAuth();
    showToast("Please log in to view your order history.");
    return;
  }
  _openOrdersPanel("history");
}

function _openOrdersPanel(tab) {
  const panel = document.getElementById("orders-panel");
  if (!panel) return;

  panel.classList.add("open");
  document
    .querySelectorAll(".nav-link")
    .forEach((a) => a.classList.remove("active"));
  switchOrdersTab(tab);

  /* Always fetch fresh data when panel opens — never show stale cache */
  _ordersCache = null;
  _fetchAndRenderOrders();
}

function closeMyOrdersPanel() {
  const panel = document.getElementById("orders-panel");
  if (!panel) return;
  panel.classList.remove("open");

  /* Restore active nav link for the current page */
  const path = window.location.pathname.split("/").pop();
  const pageNavMap = {
    "shop.php": "nav-home",
    "shop-products.php": "nav-shop",
    "shop-reviews.php": "nav-reviews",
    "shop-about.php": "nav-about",
    "shop-contacts.php": "nav-contacts",
  };
  const navId = pageNavMap[path];
  if (navId) {
    const el = document.getElementById(navId);
    if (el) el.classList.add("active");
  }
}

function switchOrdersTab(tab) {
  const tabActive = document.getElementById("op-tab-active");
  const tabHistory = document.getElementById("op-tab-history");
  const panel = document.getElementById("orders-panel");
  const title = document.getElementById("orders-panel-title");
  if (!panel) return;

  panel.dataset.ordersTab = tab;
  if (tabActive) tabActive.classList.toggle("active", tab === "active");
  if (tabHistory) tabHistory.classList.toggle("active", tab === "history");
  if (title)
    title.textContent = tab === "active" ? "My Orders" : "Order History";

  /* Re-render from cache if available */
  if (_ordersCache) _renderOrdersTab(tab, _ordersCache);
}

async function _fetchAndRenderOrders() {
  const content = document.getElementById("orders-panel-content");
  if (content)
    content.innerHTML =
      '<div class="orders-panel-loading">Loading your orders…</div>';

  try {
    _ordersCache = await loadMyOrders();
    _ordersLoaded = true;
    updateOrderBadges(_ordersCache);
    const tab =
      document.getElementById("orders-panel")?.dataset.ordersTab || "active";
    _renderOrdersTab(tab, _ordersCache);
  } catch (err) {
    /* Show a readable error inside the panel rather than silent empty state */
    if (content) {
      content.innerHTML = `
        <div style="text-align:center;padding:3rem 1rem;">
          <div style="font-size:2rem;margin-bottom:1rem;opacity:0.4;"></div>
          <div style="font-weight:600;margin-bottom:0.5rem;">Could not load orders</div>
          <div style="font-size:0.82rem;color:var(--text-muted);margin-bottom:1.2rem;line-height:1.6;">
            ${err.message || "A server error occurred. Please try again."}
          </div>
          <button onclick="_fetchAndRenderOrders()"
            style="background:var(--accent);color:#fff;border:none;border-radius:8px;
                   padding:0.65rem 1.5rem;font-family:'DM Sans',sans-serif;
                   font-size:0.84rem;font-weight:500;cursor:pointer;">
            Try Again
          </button>
        </div>`;
    }
    console.error("_fetchAndRenderOrders error:", err);
  }
}

function _renderOrdersTab(tab, allOrders) {
  const content = document.getElementById("orders-panel-content");
  if (!content) return;

  const isActive = tab === "active";
  const statusList = isActive ? ACTIVE_ORDER_STATUSES : FINAL_ORDER_STATUSES;
  const filtered = allOrders.filter((o) => statusList.includes(o.orderStatus));

  /* Mark orders in this tab as seen and refresh badges */
  _markTabSeen(allOrders, statusList);
  updateOrderBadges(allOrders);

  if (!filtered.length) {
    content.innerHTML = `
      <div class="orders-empty" style="border:none;background:transparent;padding:3rem 1rem;">
        <div class="orders-empty-icon"></div>
        <div class="orders-empty-title">${isActive ? "No Active Orders" : "No Order History"}</div>
        <div class="orders-empty-sub" style="font-size:0.8rem;">
          ${
            isActive
              ? "You have no orders currently in progress."
              : "Completed, rejected, and cancelled orders will appear here."
          }
        </div>
        ${isActive ? `<a href="shop-products.php" class="btn-primary" style="display:inline-block;" onclick="closeMyOrdersPanel()">Browse Products</a>` : ""}
      </div>`;
    return;
  }

  content.innerHTML = filtered
    .map((o) => (isActive ? renderOrderCard(o) : renderHistoryCard(o)))
    .join("");
}

async function loadMyOrders() {
  try {
    const res = await shopFetch("api/my-orders.php");

    /* Handle auth failure before parsing body */
    if (res.status === 401) {
      window.location.href = "shop.php";
      return [];
    }

    const data = await res.json();

    if (data && data.error) {
      /* Surface the server error so it shows in the panel */
      throw new Error(data.error);
    }

    return Array.isArray(data) ? data : [];
  } catch (e) {
    console.error("loadMyOrders failed:", e);
    /* Re-throw so _fetchAndRenderOrders can show the error */
    throw e;
  }
}

/* Active order statuses → shown in "My Orders"
   Includes 'Pending Payment' for backward compatibility with orders
   placed before the reservation flow was introduced. */
/* My Orders — in-progress orders only */
const ACTIVE_ORDER_STATUSES = [
  "Payment Verification",
  "Payment Accepted",
  "Processing",
  "Shipping",
  "Shipped",
  /* legacy */
  "Pending Payment",
  "Pending Verification",
];
/* Order History — finished and rejected orders */
const FINAL_ORDER_STATUSES = [
  "Completed",
  "Payment Rejected",
  "Cancelled",
  "Rejected",
];

/* ── User-facing display label for each backend status ────────── */
function _orderDisplayLabel(orderStatus) {
  const map = {
    "Payment Verification": "Payment Verification",
    "Payment Accepted": "Payment Accepted",
    "Payment Rejected": "Payment Rejected",
    Processing: "Processing",
    Shipping: "Shipping",
    Shipped: "Shipped",
    Completed: "Completed",
    /* legacy */
    "Pending Payment": "Payment Verification",
    "Pending Verification": "Payment Verification",
    Rejected: "Payment Rejected",
  };
  return map[orderStatus] || orderStatus;
}

/* Returns a styled status badge using user-facing labels */
function orderStatusBadgeShop(status) {
  const label = _orderDisplayLabel(status);
  const clsMap = {
    "Payment Verification": "pending",
    "Payment Accepted": "processing",
    "Payment Rejected": "rejected",
    Processing: "processing",
    Shipping: "shipping",
    Shipped: "shipped",
    Completed: "completed",
  };
  const cls = clsMap[label] || "completed";
  return `<span class="status-badge ${cls}">${label}</span>`;
}

/* Full 7-step workflow tracker — visible from order creation to completion */
function renderOrderTracker(orderStatus) {
  const isRejected = ["Payment Rejected", "Rejected"].includes(orderStatus);

  /* ── Rejection branch: 3-step short track ── */
  if (isRejected) {
    return [
      { label: "Payment<br>Submitted", cls: "done" },
      { label: "Payment<br>Verification", cls: "done" },
      { label: "Payment<br>Rejected", cls: "rejected" },
    ]
      .map(
        (s) => `
      <div class="tracker-step ${s.cls}">
        <div class="tracker-step-dot"></div>
        <div class="tracker-line"></div>
        <div class="tracker-step-label">${s.label}</div>
      </div>`,
      )
      .join("");
  }

  /* ── Normal 7-step flow ── */
  const steps = [
    "Payment<br>Submitted",
    "Payment<br>Verification",
    "Payment<br>Accepted",
    "Processing",
    "Shipping",
    "Shipped",
    "Completed",
  ];

  /* Map each status to which step index is currently ACTIVE */
  const activeIdx = {
    "Payment Verification": 1,
    "Pending Verification": 1,
    "Pending Payment": 1,
    "Payment Accepted": 2,
    Processing: 3,
    Shipping: 4,
    Shipped: 5,
    Completed: 6,
  };

  const current = activeIdx[orderStatus] ?? 0;

  return steps
    .map((label, i) => {
      let cls = "";
      if (i < current) cls = "done";
      else if (i === current) cls = "active";
      return `
      <div class="tracker-step ${cls}">
        <div class="tracker-step-dot"></div>
        <div class="tracker-line"></div>
        <div class="tracker-step-label">${label}</div>
      </div>`;
    })
    .join("");
}

/* Renders a full order card for "My Orders" */
function renderOrderCard(o) {
  const date = (o.dateOrdered || "").split(" ")[0];
  /* Check both order status and payment status to determine display state.
     payment.status = 'Approved' is the canonical signal that fulfillment started. */
  const isRejectedInActive =
    ["Payment Rejected", "Rejected"].includes(o.orderStatus) ||
    o.payment?.status === "Rejected";
  const isPendingPayment =
    !isRejectedInActive &&
    (o.payment?.status === "Pending Verification" ||
      [
        "Payment Verification",
        "Pending Payment",
        "Pending Verification",
      ].includes(o.orderStatus));
  const isAccepted =
    !isRejectedInActive &&
    !isPendingPayment &&
    (o.payment?.status === "Approved" ||
      ["Payment Accepted", "Processing", "Shipping", "Shipped"].includes(
        o.orderStatus,
      ));
  const displayLabel = isRejectedInActive
    ? "Payment Rejected"
    : _orderDisplayLabel(o.orderStatus);

  const itemsHtml = (o.products || [])
    .map(
      (p) => `
    <div class="order-item">
      ${
        p.image
          ? `<img class="order-item-img" src="${escHtml(p.image)}" alt="${escHtml(p.name)}" loading="lazy">`
          : `<div class="order-item-img-placeholder"></div>`
      }
      <div style="flex:1;min-width:0;">
        <div class="order-item-name">${escHtml(p.name)}</div>
        <div class="order-item-meta">Size: ${escHtml(p.size || "—")}</div>
      </div>
      <div class="order-item-price">₱${Number(p.price).toLocaleString()}</div>
    </div>`,
    )
    .join("");

  /* ── Status banner ── */
  let bannerHtml = "";
  if (isPendingPayment) {
    bannerHtml = `
      <div class="order-pending-banner">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        <div>
          <span>Your payment is under review. We are verifying your GCash screenshot and reference number. Your item is reserved while we check.</span>
        </div>
      </div>`;
  } else if (isAccepted) {
    const acceptedMsgs = {
      "Payment Accepted":
        "Payment verified! We will start packing your items soon.",
      Processing: "We are packing your items and preparing them for shipment.",
      Shipping:
        "Your package has been handed to the courier and is on its way.",
      Shipped: "Your package is out for delivery. Please watch for it!",
    };
    const acceptedMsg =
      acceptedMsgs[o.orderStatus] ||
      "Payment accepted! Your order is now being processed.";
    bannerHtml = `
      <div class="order-completed-banner">
        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        ${acceptedMsg}
      </div>`;
  } else if (isRejectedInActive) {
    const reasonHtml = o.rejectionReason
      ? `<div style="margin-top:0.35rem;font-size:0.8rem;opacity:0.9;">
           <strong>Reason:</strong> ${escHtml(o.rejectionReason)}
         </div>`
      : "";
    bannerHtml = `
      <div class="order-rejected-banner">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        <div>
          <div style="font-weight:600;margin-bottom:0.25rem;">Payment Rejected</div>
          <div>Your payment could not be verified. Please check your GCash details and place a new order.</div>
          ${reasonHtml}
          <a href="shop-products.php"
            style="display:inline-block;margin-top:0.75rem;background:#fff;color:#dc2626;
                   border:1.5px solid #dc2626;border-radius:7px;padding:0.45rem 1rem;
                   font-size:0.8rem;font-weight:600;text-decoration:none;">
            Browse Products &amp; Place New Order
          </a>
        </div>
      </div>`;
  }

  /* ── Admin note (seller message) ── */
  const adminNoteHtml = o.adminNotes
    ? `
    <div class="order-seller-note">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0;color:var(--accent);"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      <div>
        <div style="font-size:0.68rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:var(--accent);margin-bottom:0.2rem;">Message from Seller</div>
        <div style="font-size:0.82rem;color:var(--text);line-height:1.55;">${escHtml(o.adminNotes)}</div>
      </div>
    </div>`
    : "";

  /* ── Progress tracker — always visible so users know where they are ── */
  const trackerHtml = `
    <div class="order-tracker">
      <div class="tracker-label">Order Tracking</div>
      <div class="tracker-steps">${renderOrderTracker(o.orderStatus)}</div>
    </div>`;

  return `
    <div class="order-card">
      <div class="order-card-header">
        <div>
          <div class="order-card-id">Order #${o.id}</div>
          <div class="order-card-date">Placed ${date}</div>
        </div>
        <div class="order-card-header-right">
          <span class="status-badge ${isPendingPayment ? "pending" : isAccepted ? "processing" : isRejectedInActive ? "rejected" : "cancelled"}">${displayLabel}</span>
        </div>
      </div>
      ${bannerHtml ? `<div style="padding:0.9rem 1.4rem 0;">${bannerHtml}</div>` : ""}
      ${adminNoteHtml ? `<div style="padding:0.75rem 1.4rem 0;">${adminNoteHtml}</div>` : ""}
      <div class="order-items">${itemsHtml}</div>
      ${trackerHtml}
      <div class="order-card-footer">
        <div>
          ${
            o.shippingFee > 0
              ? `
          <div style="font-size:0.72rem;color:var(--text-muted);margin-bottom:0.15rem;">
            Items: ₱${(Number(o.totalAmount) - o.shippingFee).toLocaleString()}
            &nbsp;+&nbsp; Shipping: ₱${Number(o.shippingFee).toLocaleString()}
          </div>`
              : ""
          }
          <div class="order-total-label">Total Paid</div>
          <div class="order-total-amount">₱${Number(o.totalAmount).toLocaleString()}</div>
        </div>
        <div style="text-align:right;display:flex;flex-direction:column;gap:0.25rem;align-items:flex-end;">
          ${
            o.payment?.status
              ? `
            <div style="font-size:0.72rem;color:var(--text-muted);">
              Payment Status:
              <strong style="color:${o.payment.status === "Rejected" ? "var(--danger,#e53e3e)" : ["Approved", "Verified"].includes(o.payment.status) ? "var(--success,#38a169)" : "inherit"}">
                ${escHtml(o.payment.status)}
              </strong>
            </div>`
              : ""
          }
          ${o.payment?.referenceNumber ? `<div style="font-size:0.72rem;color:var(--text-muted);">Ref: <strong>${escHtml(o.payment.referenceNumber)}</strong></div>` : ""}
          ${
            o.receipt
              ? `
            <a class="btn-view-receipt" href="shop-receipt.php?order_id=${o.id}&from=orders" title="View Receipt">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;vertical-align:-2px;margin-right:4px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
              View Receipt
            </a>`
              : ""
          }
        </div>
      </div>
    </div>`;
}

/* Renders a compact card for "Order History" (final orders) */
function renderHistoryCard(o) {
  const date = (o.dateOrdered || "").split(" ")[0];
  const isCompleted = o.orderStatus === "Completed";

  /* Each product row — completed orders show a "Write a Review" button per item */
  const itemsHtml = (o.products || [])
    .map((p) => {
      const imgSrc = escHtml(p.image || "");
      const imgHtml = imgSrc
        ? `<img class="order-item-img" src="${imgSrc}" alt="${escHtml(p.name)}" loading="lazy">`
        : `<div class="order-item-img-placeholder"></div>`;

      /* Safe values for onclick — avoid single-quote conflicts */
      const nameAttr = (p.name || "")
        .replace(/'/g, "&#39;")
        .replace(/"/g, "&quot;");
      const imgAttr = imgSrc.replace(/'/g, "&#39;");

      const reviewBtn = isCompleted
        ? `
      <button class="btn-write-review"
        onclick="openReviewModal(${p.product_id}, '${nameAttr}', '${imgAttr}', ${o.id})"
        title="Review ${escHtml(p.name)}">
        ★ Write a Review
      </button>`
        : "";

      return `
      <div class="order-item">
        ${imgHtml}
        <div style="flex:1;min-width:0;">
          <div class="order-item-name">${escHtml(p.name)}</div>
          <div class="order-item-meta">Size: ${escHtml(p.size || "—")}</div>
          ${reviewBtn}
        </div>
        <div class="order-item-price">₱${Number(p.price).toLocaleString()}</div>
      </div>`;
    })
    .join("");

  const isPaymentRejected = ["Payment Rejected", "Rejected"].includes(
    o.orderStatus,
  );
  const displayLabel = _orderDisplayLabel(o.orderStatus);

  /* ── Banner for each final state ── */
  let bannerHtml = "";
  if (isCompleted) {
    bannerHtml = `
      <div style="padding:0.9rem 1.4rem 0;">
        <div class="order-completed-banner">
          <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          Order completed — tap <strong>★ Write a Review</strong> on any item to share your experience.
        </div>
      </div>`;
  } else if (isPaymentRejected) {
    const reasonHtml = o.rejectionReason
      ? `<div style="margin-top:0.35rem;font-size:0.8rem;opacity:0.9;"><strong>Reason:</strong> ${escHtml(o.rejectionReason)}</div>`
      : "";
    bannerHtml = `
      <div style="padding:0.9rem 1.4rem 0;">
        <div class="order-rejected-banner">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          <div>
            <div style="font-weight:600;margin-bottom:0.25rem;">Payment Rejected</div>
            <div>Your payment could not be verified. Please check your GCash details and place a new order.</div>
            ${reasonHtml}
            <a href="shop-products.php"
              style="display:inline-block;margin-top:0.75rem;background:#fff;color:#dc2626;
                     border:1.5px solid #dc2626;border-radius:7px;padding:0.45rem 1rem;
                     font-size:0.8rem;font-weight:600;text-decoration:none;">
              Browse Products &amp; Place New Order
            </a>
          </div>
        </div>
      </div>`;
  }

  const badgeCls = isCompleted
    ? "completed"
    : isPaymentRejected
      ? "rejected"
      : "completed";

  return `
    <div class="order-card">
      <div class="order-card-header">
        <div>
          <div class="order-card-id">Order #${o.id}</div>
          <div class="order-card-date">Placed ${date}</div>
        </div>
        <div class="order-card-header-right">
          <span class="status-badge ${badgeCls}">${displayLabel}</span>
        </div>
      </div>
      ${bannerHtml}
      <div class="order-items">${itemsHtml}</div>
      <!-- Tracker also shown in history so users see the full completed or rejected journey -->
      <div class="order-tracker">
        <div class="tracker-label">Order Tracking</div>
        <div class="tracker-steps">${renderOrderTracker(o.orderStatus)}</div>
      </div>
      <div class="order-card-footer">
        <div>
          <div class="order-total-label">Total</div>
          <div class="order-total-amount">₱${Number(o.totalAmount).toLocaleString()}</div>
        </div>
        <div style="display:flex;flex-direction:column;gap:0.3rem;align-items:flex-end;">
          ${
            o.payment?.referenceNumber
              ? `<div style="font-size:0.75rem;color:var(--text-muted);">Ref: <strong>${escHtml(o.payment.referenceNumber)}</strong></div>`
              : ""
          }
          ${
            isPaymentRejected
              ? `
            <a class="btn-view-receipt" href="shop-order-submission.php?order_id=${o.id}&from=history" title="View Submission">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;vertical-align:-2px;margin-right:4px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
              View Submission
            </a>`
              : o.receipt
                ? `
            <a class="btn-view-receipt" href="shop-receipt.php?order_id=${o.id}&from=history" title="View Receipt">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;vertical-align:-2px;margin-right:4px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
              View Receipt
            </a>`
                : ""
          }
        </div>
      </div>
    </div>`;
}
