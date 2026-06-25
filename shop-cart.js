/* ════════════════════════════════════════════════════════════════
   CART
════════════════════════════════════════════════════════════════ */
function addToCart() {
  if (!currentProduct) return;
  /* Must be logged in to add items */
  if (!currentUser) {
    closeModal();
    openAuth();
    showToast("Please log in to add items to your cart.");
    return;
  }
  if (currentProduct.status === "sold") {
    showToast("This item has already been sold.");
    return;
  }
  if (currentProduct.status === "reserved") {
    showToast("This item is currently reserved by another buyer.");
    return;
  }
  if (cart.some((c) => c.product.id == currentProduct.id)) {
    showToast("Already in cart!");
    return;
  }
  cart.push({ product: currentProduct, size: currentProduct.size });
  saveCart();
  updateCartBadge();
  renderCart();
  if (typeof buildFeatured === "function") buildFeatured(allProducts);
  if (typeof renderProducts === "function")
    renderProducts(
      applyFiltersAndSortSilent ? applyFiltersAndSortSilent() : allProducts,
    );
  showToast(`${currentProduct.name} added to cart`);
  closeModal();
}

function buyNow() {
  if (!currentProduct) return;
  /* Must be logged in to buy */
  if (!currentUser) {
    closeModal();
    openAuth();
    showToast("Please log in to place an order.");
    return;
  }
  if (!cart.some((c) => c.product.id == currentProduct.id)) {
    cart.push({ product: currentProduct, size: currentProduct.size });
    saveCart();
    updateCartBadge();
  }
  closeModal();
  goToCheckout();
}

function removeFromCart(productId) {
  const removed = cart.find((c) => c.product.id == productId);
  if (!removed) return;
  const itemEl = document.querySelector(`.cart-item[data-id="${productId}"]`);
  const doRemove = () => {
    cart = cart.filter((c) => c.product.id != productId);
    saveCart();
    updateCartBadge();
    renderCart();
    if (typeof buildFeatured === "function") buildFeatured(allProducts);
    if (typeof renderProducts === "function")
      renderProducts(
        applyFiltersAndSortSilent ? applyFiltersAndSortSilent() : allProducts,
      );
    showToast(`${removed.product.name} removed from cart.`);
  };
  if (itemEl) {
    itemEl.classList.add("removing");
    itemEl.addEventListener("animationend", doRemove, { once: true });
  } else doRemove();
}

function cartTotal() {
  return cart.reduce((s, c) => s + c.product.price, 0);
}

function updateCartBadge() {
  const badge = document.getElementById("cart-count");
  if (badge) badge.textContent = cart.length;
}

/* ── ORDER STATUS NOTIFICATION BADGES ───────────────────────────
   Persists a map of { orderId: lastSeenStatus } in localStorage.
   Any order whose current status differs from the stored one is
   counted as "unseen". Badges clear when the user opens that tab. */

function _orderSeenKey() {
  return "order_seen_" + (currentUser?.user_id || "guest");
}

function _getSeenMap() {
  try {
    return JSON.parse(localStorage.getItem(_orderSeenKey()) || "{}");
  } catch {
    return {};
  }
}

function _saveSeenMap(map) {
  try {
    localStorage.setItem(_orderSeenKey(), JSON.stringify(map));
  } catch {}
}

function _countUnseen(orders, statusList) {
  const seen = _getSeenMap();
  return orders.filter(
    (o) => statusList.includes(o.orderStatus) && seen[o.id] !== o.orderStatus,
  ).length;
}

function _markTabSeen(orders, statusList) {
  const seen = _getSeenMap();
  orders.forEach((o) => {
    if (statusList.includes(o.orderStatus)) seen[o.id] = o.orderStatus;
  });
  _saveSeenMap(seen);
}

function updateOrderBadges(orders) {
  if (!orders) return;
  const activeCount = _countUnseen(orders, ACTIVE_ORDER_STATUSES);
  const historyCount = _countUnseen(orders, FINAL_ORDER_STATUSES);

  _setBadge("order-badge-active", activeCount);
  _setBadge("order-badge-history", historyCount);
  _setBadge("op-tab-badge-active", activeCount);
  _setBadge("op-tab-badge-history", historyCount);
}

function _setBadge(id, count) {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = count || "";
  el.style.display = count ? "inline-flex" : "none";
}

function renderCart() {
  const list = document.getElementById("cart-items-list");
  const totalEl = document.getElementById("cart-total-display");
  const btn = document.getElementById("btn-checkout");
  const noteDefault = document.getElementById("cart-note-default");
  const noteLogin = document.getElementById("cart-note-login");
  if (!list) return;

  if (!cart.length) {
    list.innerHTML =
      '<div class="cart-empty-msg">Your cart is empty.<br>Browse the shop and add something you love!</div>';
    if (totalEl) totalEl.textContent = "₱0";
    if (btn) btn.disabled = true;
    if (noteDefault) noteDefault.style.display = "";
    if (noteLogin) noteLogin.style.display = "none";
    return;
  }
  list.innerHTML = cart
    .map(
      ({ product: p }) => `
    <div class="cart-item" data-id="${p.id}">
      <div class="cart-item-img"><img src="${getCoverImage(p)}" alt="${p.name}" loading="lazy"></div>
      <div class="cart-item-info">
        <div class="cart-item-name">${p.name}</div>
        <div class="cart-item-size">Size: ${p.size || "—"}</div>
        <div class="cart-item-price">₱${p.price.toLocaleString()}</div>
      </div>
      <button class="cart-item-remove" onclick="removeFromCart('${p.id}')" title="Remove">✕</button>
    </div>`,
    )
    .join("");
  if (totalEl) totalEl.textContent = "₱" + cartTotal().toLocaleString();

  if (currentUser) {
    if (btn) btn.disabled = false;
    if (noteDefault) noteDefault.style.display = "";
    if (noteLogin) noteLogin.style.display = "none";
  } else {
    if (btn) btn.disabled = true;
    if (noteDefault) noteDefault.style.display = "none";
    if (noteLogin) noteLogin.style.display = "";
  }
}

function openCart() {
  renderCart();
  document.getElementById("cart-overlay").classList.add("open");
  document.getElementById("cart-drawer").classList.add("open");
  document.body.style.overflow = "hidden";
}

function closeCart() {
  document.getElementById("cart-overlay").classList.remove("open");
  document.getElementById("cart-drawer").classList.remove("open");
  document.body.style.overflow = "";
}

function goToCheckout() {
  if (!currentUser) {
    closeCart();
    openAuth();
    showToast("Please log in to place an order.");
    return;
  }
  closeCart();
  window.location.href = PAGE_URLS.payment;
}
