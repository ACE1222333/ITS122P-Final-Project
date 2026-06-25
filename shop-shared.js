// GLOBAL STATE
let allProducts = [];
let cart = []; // [{ product, size }]
let currentProduct = null;
let currentImages = [];
let currentImgIdx = 0;
let currentRating = 0;
let reviews = [];
let currentUser = null;
let allSizes = [];
let allCategories = [];

const PAGE_URLS = {
  home: "shop.php",
  shop: "shop-products.php",
  reviews: "shop-reviews.php",
  about: "shop-about.php",
  contacts: "shop-contacts.php",
  faq: "shop-faq.php",
  terms: "shop-terms.php",
  payment: "shop-payment.php",
  "my-orders": "shop-my-orders.php",
  "order-history": "shop-order-history.php",
};

function goPage(id) {
  const url = PAGE_URLS[id];
  if (url) window.location.href = url;
}

function saveCart() {
  localStorage.setItem("buythebella_cart", JSON.stringify(cart));
}
function loadCart() {
  try {
    const stored = localStorage.getItem("buythebella_cart");
    if (stored) cart = JSON.parse(stored);
  } catch (e) {
    cart = [];
  }
}

function getShopToken() {
  try {
    const sess = localStorage.getItem("buythebella_session");
    if (!sess) return "";
    const obj = JSON.parse(sess);
    return obj.token || "";
  } catch (e) {
    return "";
  }
}

async function shopFetch(url, options = {}) {
  const token = getShopToken();
  const method = (options.method || "GET").toUpperCase();
  const headers = { ...(options.headers || {}) };

  if (token) headers["Authorization"] = "Bearer " + token;

  /* Always append token as query param — guarantees auth even when Apache
     strips the Authorization header (common on shared/XAMPP hosts) */
  let finalUrl = url;
  if (token) {
    finalUrl +=
      (url.includes("?") ? "&" : "?") + "token=" + encodeURIComponent(token);
  }

  /* POST JSON: embed token in body */
  if (method !== "GET" && !(options.body instanceof FormData)) {
    headers["Content-Type"] = "application/json";
    if (token && options.body) {
      try {
        const parsed = JSON.parse(options.body);
        if (!parsed.token) parsed.token = token;
        options = { ...options, body: JSON.stringify(parsed) };
      } catch (e) {
        /* leave body as-is */
      }
    }
  }

  return fetch(finalUrl, { ...options, headers });
}

async function loadProducts(onLoaded) {
  try {
    const res = await fetch("api/products.php");
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    allProducts = Array.isArray(data) ? data : [];
  } catch (err) {
    console.error("loadProducts failed:", err);
    allProducts = [];
  }
  if (typeof onLoaded === "function") onLoaded(allProducts);
}

// SIZE & CATEGORY LOADERS
async function loadSizes(onLoaded) {
  try {
    const res = await fetch("api/sizes.php");
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    allSizes = Array.isArray(data) ? data : [];
  } catch (e) {
    console.error("loadSizes failed:", e);
    allSizes = [];
  }
  if (typeof onLoaded === "function") onLoaded(allSizes);
}

async function loadCategories(onLoaded) {
  try {
    const res = await fetch("api/categories.php");
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    allCategories = Array.isArray(data) ? data : [];
  } catch (e) {
    console.error("loadCategories failed:", e);
    allCategories = [];
  }
  if (typeof onLoaded === "function") onLoaded(allCategories);
}

async function loadReviews(onLoaded) {
  try {
    const res = await fetch("api/reviews.php");
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    reviews = Array.isArray(data) ? data : [];
  } catch (e) {
    console.error("loadReviews failed:", e);
    reviews = [];
  }
  if (typeof onLoaded === "function") onLoaded(reviews);
}

function openModal(p) {
  currentProduct = p;
  currentImages =
    p.images && p.images.length ? p.images : [p.image].filter(Boolean);
  const ci =
    typeof p.coverIndex === "number"
      ? p.coverIndex
      : typeof p.cover_index === "number"
        ? p.cover_index
        : 0;
  currentImgIdx = ci < currentImages.length ? ci : 0;
  renderGallery();
  document.getElementById("modal-name").textContent = p.name;
  document.getElementById("modal-price").textContent =
    "₱" + p.price.toLocaleString();
  document.getElementById("modal-size-val").textContent = p.size || "—";
  document.getElementById("modal-desc").textContent = p.desc || "";

  const condBadge = document.getElementById("modal-condition-badge");
  const condDesc = document.getElementById("modal-condition-desc");
  const cond = (p.condition || "").toLowerCase().replace(/\s+/g, "-");
  condBadge.textContent = p.condition || "—";
  condBadge.className = "condition-badge" + (cond ? ` ${cond}` : "");
  const condDescMap = {
    "brand new": "Never worn. Tags may still be attached.",
    "like new": "Worn once or twice. No visible flaws.",
    "lightly used": "Gently worn a few times. Minimal signs of use.",
    "well used": "Noticeable wear but still in good shape.",
    "heavily used": "Significant wear. Priced accordingly.",
  };
  if (condDesc)
    condDesc.textContent = p.condition
      ? "— " + (condDescMap[(p.condition || "").toLowerCase()] || "")
      : "";

  const catContainer = document.getElementById("modal-category-tag");
  const catList =
    p.categories && p.categories.length
      ? p.categories
      : p.category
        ? [{ name: p.category }]
        : [];
  if (catList.length) {
    catContainer.textContent = catList.map((c) => c.name).join(", ");
  } else {
    catContainer.textContent = "—";
  }

  const inCart = cart.some((c) => c.product.id == p.id);
  const isUnavailable = p.status === "sold" || p.status === "reserved";
  const cartBtn = document.getElementById("modal-cart-btn");
  const buyBtn =
    document.getElementById("modal-buy-btn") ||
    document.querySelector(".btn-buy");

  if (p.status === "sold") {
    cartBtn.textContent = "Sold Out";
    cartBtn.disabled = true;
    cartBtn.style.opacity = "0.4";
  } else if (p.status === "reserved") {
    cartBtn.textContent = "Reserved";
    cartBtn.disabled = true;
    cartBtn.style.opacity = "0.4";
  } else if (inCart) {
    cartBtn.textContent = "Remove from Cart";
    cartBtn.disabled = false;
    cartBtn.style.opacity = "";
    cartBtn.onclick = () => { removeFromCart(p.id); closeModal(); };
  } else if (!currentUser) {
    cartBtn.textContent = "Log in to Add to Cart";
    cartBtn.disabled = false;
    cartBtn.style.opacity = "";
  } else {
    cartBtn.textContent = "Add to Cart";
    cartBtn.disabled = false;
    cartBtn.style.opacity = "";
  }

  /* Buy Now button label for guests */
  if (buyBtn) {
    buyBtn.textContent = currentUser ? "Buy Now" : "Log in to Buy";
  }

  document.getElementById("modal-overlay").classList.add("open");
  document.body.style.overflow = "hidden";
}

function renderGallery() {
  const mainImg = document.getElementById("modal-img-main");
  mainImg.src = currentImages[currentImgIdx] || "";
  mainImg.alt = currentProduct ? currentProduct.name : "";

  const dotsEl = document.getElementById("modal-gallery-dots");
  const strip = document.getElementById("modal-thumbs-strip");
  if (dotsEl) dotsEl.innerHTML = "";
  if (strip) strip.innerHTML = "";

  if (currentImages.length <= 1) return;

  currentImages.forEach((src, i) => {
    /* dot */
    if (dotsEl) {
      const d = document.createElement("button");
      d.className = "gallery-dot" + (i === currentImgIdx ? " active" : "");
      d.setAttribute("aria-label", `Photo ${i + 1}`);
      d.onclick = (e) => {
        e.stopPropagation();
        currentImgIdx = i;
        renderGallery();
      };
      dotsEl.appendChild(d);
    }
    /* thumbnail */
    if (strip) {
      const t = document.createElement("div");
      t.className = "thumb-img" + (i === currentImgIdx ? " active" : "");
      t.innerHTML = `<img src="${src}" alt="view ${i + 1}" loading="lazy">`;
      t.onclick = () => {
        currentImgIdx = i;
        renderGallery();
      };
      strip.appendChild(t);
    }
  });
}

function galleryPrev(e) {
  if (e) e.stopPropagation();
  if (!currentImages.length) return;
  currentImgIdx =
    (currentImgIdx - 1 + currentImages.length) % currentImages.length;
  renderGallery();
}
function galleryNext(e) {
  if (e) e.stopPropagation();
  if (!currentImages.length) return;
  currentImgIdx = (currentImgIdx + 1) % currentImages.length;
  renderGallery();
}
function closeModal() {
  document.getElementById("modal-overlay").classList.remove("open");
  document.body.style.overflow = "";
}
function closeModalOutside(e) {
  if (e.target === document.getElementById("modal-overlay")) closeModal();
}

function setRating(val, ctx) {
  currentRating = val;
  const pickerId = ctx === "mobile" ? "mobile-star-picker" : "star-picker";
  const picker = document.getElementById(pickerId);
  if (picker)
    picker
      .querySelectorAll(".star-btn")
      .forEach((btn, i) => btn.classList.toggle("lit", i < val));
  /* Keep both pickers in sync visually */
  const otherId = ctx === "mobile" ? "star-picker" : "mobile-star-picker";
  const other = document.getElementById(otherId);
  if (other)
    other
      .querySelectorAll(".star-btn")
      .forEach((btn, i) => btn.classList.toggle("lit", i < val));
}

/* Tracks which edit forms are open and which images are pending deletion */
const _openEditForms = new Set();
const _pendingDeleteImages = new Map(); // reviewId -> Set of image_ids

function relativeTime(timestamp) {
  if (!timestamp) return "";
  const d = new Date(timestamp * 1000);
  const today = new Date();
  const isToday =
    d.getFullYear() === today.getFullYear() &&
    d.getMonth() === today.getMonth() &&
    d.getDate() === today.getDate();
  if (isToday) {
    return d.toLocaleTimeString("en-US", {
      hour: "numeric",
      minute: "2-digit",
      hour12: true,
    });
  }
  return d.toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

async function submitReview(ctx) {
  if (!currentUser) {
    showToast("Please log in to post a review.");
    openAuth();
    return;
  }
  const bodyId = ctx === "mobile" ? "mobile-rv-body" : "rv-body";
  const body = document.getElementById(bodyId)?.value.trim();
  if (!currentRating) {
    showToast("Please select a star rating.");
    return;
  }
  if (!body) {
    showToast("Please write your review.");
    return;
  }

  try {
    const res = await shopFetch("api/reviews.php", {
      method: "POST",
      body: JSON.stringify({ product_id: null, rating: currentRating, body }),
    });
    const data = await res.json();
    if (data.error) {
      showToast(data.error);
      return;
    }
    if (data.success && data.review) {
      reviews.unshift(data.review);
      renderReviews();
      /* Clear both textareas and reset stars */
      const rvBody = document.getElementById("rv-body");
      const mRvBody = document.getElementById("mobile-rv-body");
      if (rvBody) rvBody.value = "";
      if (mRvBody) mRvBody.value = "";
      setRating(0);
      /* Collapse mobile panel after submit */
      document
        .getElementById("mobile-review-form-panel")
        ?.classList.remove("open");
      showToast("Review posted! Thank you!");
    }
  } catch (e) {
    showToast("Failed to post review. Please try again.");
    console.error("submitReview error:", e);
  }
}

// Edit review
function toggleEditReview(reviewId) {
  if (_openEditForms.has(reviewId)) {
    _openEditForms.delete(reviewId);
    _pendingDeleteImages.delete(reviewId);
  } else {
    _openEditForms.add(reviewId);
    _pendingDeleteImages.set(reviewId, new Set());
  }
  renderReviews();
  if (_openEditForms.has(reviewId)) {
    setTimeout(
      () => document.getElementById(`edit-body-${reviewId}`)?.focus(),
      50,
    );
  }
}

function toggleDeleteImage(reviewId, imageId) {
  if (!_pendingDeleteImages.has(reviewId))
    _pendingDeleteImages.set(reviewId, new Set());
  const set = _pendingDeleteImages.get(reviewId);
  if (set.has(imageId)) set.delete(imageId);
  else set.add(imageId);
  /* Just update the visual state without full re-render */
  const imgEl = document.getElementById(`edit-img-${imageId}`);
  if (imgEl) imgEl.classList.toggle("pending-delete", set.has(imageId));
  const btn = document.getElementById(`edit-img-btn-${imageId}`);
  if (btn) btn.textContent = set.has(imageId) ? "Undo" : "✕";
}

async function saveEditReview(reviewId) {
  const stars = document.querySelectorAll(
    `#edit-form-${reviewId} .edit-star-btn`,
  );
  let rating = 0;
  stars.forEach((s, i) => {
    if (s.classList.contains("lit")) rating = i + 1;
  });
  const body = document.getElementById(`edit-body-${reviewId}`)?.value.trim();

  if (!rating) {
    showToast("Please select a star rating.");
    return;
  }
  if (!body) {
    showToast("Please write your review.");
    return;
  }

  const btn = document.getElementById(`edit-save-btn-${reviewId}`);
  if (btn) {
    btn.disabled = true;
    btn.textContent = "Saving…";
  }

  try {
    const fd = new FormData();
    fd.append("action", "edit");
    fd.append("review_id", reviewId);
    fd.append("rating", rating);
    fd.append("body", body);

    const deleteSet = _pendingDeleteImages.get(reviewId) || new Set();
    fd.append("delete_image_ids", [...deleteSet].join(","));

    const newImgInput = document.getElementById(`edit-new-images-${reviewId}`);
    if (newImgInput && newImgInput.files.length) {
      [...newImgInput.files]
        .slice(0, 5)
        .forEach((f) => fd.append("images[]", f));
    }

    const res = await shopFetch("api/reviews.php", {
      method: "POST",
      body: fd,
    });
    const data = await res.json();
    if (data.error) {
      showToast(data.error);
      return;
    }

    const review = reviews.find((r) => r.review_id === reviewId);
    if (review) {
      review.rating = rating;
      review.body = body;
      review.images = data.images || [];
    }
    _openEditForms.delete(reviewId);
    _pendingDeleteImages.delete(reviewId);
    renderReviews();
    showToast("Review updated!");
  } catch (e) {
    showToast("Failed to update review.");
    console.error("saveEditReview:", e);
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.textContent = "Save";
    }
  }
}

function setEditRating(reviewId, val) {
  document
    .querySelectorAll(`#edit-form-${reviewId} .edit-star-btn`)
    .forEach((btn, i) => btn.classList.toggle("lit", i < val));
}

// Delete review
async function deleteReview(reviewId) {
  if (!confirm("Delete this review? This cannot be undone.")) return;
  try {
    const res = await shopFetch("api/reviews.php", {
      method: "DELETE",
      body: JSON.stringify({ review_id: reviewId }),
    });
    const data = await res.json();
    if (data.error) {
      showToast(data.error);
      return;
    }
    reviews = reviews.filter((r) => r.review_id !== reviewId);
    renderReviews();
    showToast("Review deleted.");
  } catch (e) {
    showToast("Failed to delete review.");
    console.error("deleteReview:", e);
  }
}

// Render
function renderReviews() {
  const listEl = document.getElementById("reviews-list");
  if (!listEl) return;
  if (!reviews.length) {
    listEl.innerHTML =
      '<div class="reviews-empty">No reviews yet. Be the first!</div>';
    updateRatingSummary();
    return;
  }

  listEl.innerHTML = reviews
    .map((r) => {
      const rid = r.review_id;
      const isOwner = currentUser && currentUser.user_id === r.user_id;
      const isEditing = _openEditForms.has(rid);

      // Owner actions
      const ownerActionsHtml = isOwner
        ? `
      <div class="review-owner-actions">
        <button class="btn-review-action" onclick="toggleEditReview(${rid})" title="${isEditing ? "Cancel" : "Edit"}">
          ${isEditing ? "Cancel" : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> Edit'}
        </button>
        <button class="btn-review-action btn-review-delete" onclick="deleteReview(${rid})" title="Delete">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg> Delete
        </button>
      </div>`
        : "";

      // Edit form
      const pendingDeletes = _pendingDeleteImages.get(rid) || new Set();
      const existingImagesHtml =
        r.images && r.images.length
          ? `
      <div class="edit-existing-images">
        ${r.images
          .map(
            (img) => `
          <div class="edit-img-wrap${pendingDeletes.has(img.id) ? " pending-delete" : ""}" id="edit-img-${img.id}">
            <img src="${escHtml(img.path)}" alt="Review photo">
            <button class="btn-edit-img-delete" id="edit-img-btn-${img.id}" onclick="toggleDeleteImage(${rid},${img.id})">${pendingDeletes.has(img.id) ? "Undo" : "✕"}</button>
          </div>`,
          )
          .join("")}
      </div>`
          : "";

      const editFormHtml = isEditing
        ? `
      <div class="review-edit-form" id="edit-form-${rid}">
        <div class="edit-stars-row">
          ${[1, 2, 3, 4, 5].map((v) => `<button class="edit-star-btn${v <= r.rating ? " lit" : ""}" onclick="setEditRating(${rid},${v})">★</button>`).join("")}
        </div>
        <textarea class="review-edit-textarea" id="edit-body-${rid}">${escHtml(r.body)}</textarea>
        ${existingImagesHtml}
        <label class="rv-file-label" for="edit-new-images-${rid}" style="margin-top:0.5rem;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0;"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
          Add photos…
        </label>
        <input type="file" id="edit-new-images-${rid}" class="rv-file-input" multiple accept="image/jpeg,image/png,image/gif,image/webp">
        <div class="review-edit-btns">
          <button class="btn-edit-save" id="edit-save-btn-${rid}" onclick="saveEditReview(${rid})">Save</button>
          <button class="btn-edit-cancel" onclick="toggleEditReview(${rid})">Cancel</button>
        </div>
      </div>`
        : "";

      // Admin reply
      const adminReplyHtml = r.admin_reply
        ? `
      <div class="review-admin-reply">
        <div class="review-admin-reply-label">Admin Reply${r.reply_date ? `<span class="review-admin-reply-date"> · ${escHtml(r.reply_date)}</span>` : ""}</div>
        <div class="review-admin-reply-text">${escHtml(r.admin_reply)}</div>
      </div>`
        : "";

      const timeLabel = r.date;

      const imagesHtml =
        r.images && r.images.length && !isEditing
          ? `
      <div class="review-images">
        ${r.images
          .map((img) => {
            const src = typeof img === "object" ? img.path : img;
            return `<img class="review-img-full" src="${escHtml(src)}" alt="Review photo" loading="lazy" onclick="openReviewPhoto('${escHtml(src)}')")>`;
          })
          .join("")}
      </div>`
          : "";

      const productChipHtml =
        r.product && !isEditing
          ? `
      <div class="review-product-chip-bottom">
        ${r.product_image ? `<img class="review-product-chip-thumb" src="${escHtml(r.product_image)}" alt="${escHtml(r.product)}" loading="lazy">` : ""}
        <div class="review-product-chip-info">
          <div class="review-product-chip-name">${escHtml(r.product)}</div>
          ${r.product_price ? `<div class="review-product-chip-price">PHP ${Number(r.product_price).toLocaleString()}</div>` : ""}
        </div>
      </div>`
          : "";

      return `
      <div class="review-card">
        <div class="review-header">
          <span class="review-name">${escHtml(r.name)}</span>
          <div style="display:flex;align-items:center;gap:0.6rem;">
            <span class="review-date">· ${timeLabel}</span>
            ${ownerActionsHtml}
          </div>
        </div>
        <div class="review-stars">${"★".repeat(r.rating)}${"☆".repeat(5 - r.rating)}</div>
        ${isEditing ? editFormHtml : `<div class="review-body">${escHtml(r.body)}</div>`}
        ${imagesHtml}
        ${productChipHtml}
        ${adminReplyHtml}
      </div>`;
    })
    .join("");

  updateRatingSummary();
}

function updateRatingSummary() {
  const total = reviews.length;
  const avgEl = document.getElementById("avg-num");
  const stEl = document.getElementById("avg-stars");
  const cntEl = document.getElementById("review-count-label");
  const barsEl = document.getElementById("rating-bars");
  if (!avgEl) return;
  if (!total) {
    avgEl.textContent = "—";
    stEl.textContent = "☆☆☆☆☆";
    cntEl.textContent = "0 reviews";
    barsEl.innerHTML = "";
    return;
  }
  const avg = reviews.reduce((s, r) => s + r.rating, 0) / total;
  avgEl.textContent = avg.toFixed(1);
  const filled = Math.round(avg);
  stEl.textContent = "★".repeat(filled) + "☆".repeat(5 - filled);
  cntEl.textContent = `${total} review${total === 1 ? "" : "s"}`;
  const counts = [5, 4, 3, 2, 1].map(
    (v) => reviews.filter((r) => r.rating === v).length,
  );
  barsEl.innerHTML = [5, 4, 3, 2, 1]
    .map(
      (star, i) => `
    <div class="r-bar-row">
      <span class="r-bar-label">${star}★</span>
      <div class="r-bar-track"><div class="r-bar-fill" style="width:${total ? Math.round((counts[i] / total) * 100) : 0}%"></div></div>
    </div>`,
    )
    .join("");
}

function sendMessage() {
  if (!currentUser) {
    openAuth();
    showToast("Please log in to send a message.");
    return;
  }
  const subject = document.getElementById("c-subject")?.value.trim();
  const message = document.getElementById("c-message")?.value.trim();
  if (!subject) {
    showToast("Please enter a subject.");
    return;
  }
  if (!message) {
    showToast("Please write your message.");
    return;
  }

  const btn = document.getElementById("contact-send-btn");
  if (btn) {
    btn.disabled = true;
    btn.textContent = "Sending…";
  }

  // TODO: wire up to api/contact.php or EmailJS when ready
  setTimeout(() => {
    showToast(`Message sent! We'll be in touch soon.`);
    ["c-subject", "c-message"].forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.value = "";
    });
    if (btn) {
      btn.disabled = false;
      btn.textContent = "Send Message";
    }
  }, 600);
}

/* Background order polling — fetches every 15 s, updates badges and
   re-renders the panel live if it is currently open. */
function _pollOrders() {
  if (!currentUser) return;
  loadMyOrders()
    .then((orders) => {
      _ordersCache = orders;
      updateOrderBadges(orders);

      /* If the panel is open, re-render the active tab silently */
      const panel = document.getElementById("orders-panel");
      if (panel && panel.classList.contains("open")) {
        const tab = panel.dataset.ordersTab || "active";
        _renderOrdersTab(tab, orders);
      }
    })
    .catch(() => {});
}

let _orderPollInterval = null;

function _startOrderPolling() {
  if (!currentUser || _orderPollInterval) return;
  _pollOrders();
  _orderPollInterval = setInterval(_pollOrders, 15000);
}

function _stopOrderPolling() {
  if (_orderPollInterval) {
    clearInterval(_orderPollInterval);
    _orderPollInterval = null;
  }
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", _startOrderPolling);
} else {
  setTimeout(_startOrderPolling, 0);
}

function escHtml(s) {
  return String(s)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function getCoverImage(p) {
  if (!p.images || !p.images.length) return p.image || "";
  const idx =
    typeof p.coverIndex === "number" && p.coverIndex < p.images.length
      ? p.coverIndex
      : typeof p.cover_index === "number" && p.cover_index < p.images.length
        ? p.cover_index
        : 0;
  return p.images[idx] || p.images[0] || "";
}

function showToast(msg) {
  const t = document.getElementById("toast");
  if (!t) return;
  t.textContent = msg;
  t.classList.add("show");
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.classList.remove("show"), 2800);
}

// KEYBOARD SHORTCUTS
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    const reviewModal = document.getElementById("review-modal-overlay");
    if (reviewModal && reviewModal.classList.contains("open")) {
      closeReviewModal();
      return;
    }
    const productModal = document.getElementById("modal-overlay");
    if (productModal && productModal.classList.contains("open")) {
      closeModal();
      return;
    }
    const profileModal = document.getElementById("profile-modal-overlay");
    if (profileModal && profileModal.classList.contains("open")) {
      closeEditProfile();
      return;
    }
    const ordersPanel = document.getElementById("orders-panel");
    if (ordersPanel && ordersPanel.classList.contains("open")) {
      closeMyOrdersPanel();
      return;
    }
    _closeUserDropdown();
    closeCart();
    closeAuth();
  }
  const productModal = document.getElementById("modal-overlay");
  if (productModal && productModal.classList.contains("open")) {
    if (e.key === "ArrowLeft") galleryPrev(e);
    if (e.key === "ArrowRight") galleryNext(e);
  }
});

/* viewReceipt and viewSubmission now navigate to dedicated pages */
function viewReceipt(orderId, from) {
  window.location.href =
    "shop-receipt.php?order_id=" + orderId + "&from=" + (from || "orders");
}

// ADMIN PREVIEW BAR
function checkAdminPreviewMode() {
  /* Set the flag when navigating from the admin panel */
  if (new URLSearchParams(window.location.search).get("from") === "admin") {
    sessionStorage.setItem("buythebella_admin_preview", "1");
  }

  /* Also treat any logged-in admin as being in preview mode — the bar
     should be visible whenever an admin account is active on the shop,
     regardless of how they got here. */
  try {
    const sess = JSON.parse(
      localStorage.getItem("buythebella_session") || "null",
    );
    if (sess && sess.role === "admin") {
      sessionStorage.setItem("buythebella_admin_preview", "1");
    }
  } catch (e) {}

  if (sessionStorage.getItem("buythebella_admin_preview") === "1") {
    const bar = document.getElementById("admin-bar");
    if (bar) {
      bar.classList.add("visible");
      document.body.classList.add("admin-preview-mode");
    }
  }
}
