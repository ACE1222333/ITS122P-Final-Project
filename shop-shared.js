/* ════════════════════════════════════════════════════════════════
   shop-shared.js — Shared state and functions for all shop pages.

   Session token stored in localStorage as bythebel_session JSON:
     { user_id, first_name, last_name, email, role, token, phone, address }
   Cart stored separately in bythebel_cart.
════════════════════════════════════════════════════════════════ */

/* ── GLOBAL STATE ────────────────────────────────────────────────── */
let allProducts    = [];
let cart           = [];        // [{ product, size }]
let currentProduct = null;
let currentImages  = [];
let currentImgIdx  = 0;
let currentRating  = 0;
let reviews        = [];
let currentUser    = null;
let allSizes       = [];
let allCategories  = [];

/* ════════════════════════════════════════════════════════════════
   NAVIGATION
════════════════════════════════════════════════════════════════ */
const PAGE_URLS = {
  home:            'shop.php',
  shop:            'shop-products.php',
  reviews:         'shop-reviews.php',
  about:           'shop-about.php',
  contacts:        'shop-contacts.php',
  faq:             'shop-faq.php',
  terms:           'shop-terms.php',
  payment:         'shop-payment.php',
  'my-orders':     'shop-my-orders.php',
  'order-history': 'shop-order-history.php',
};

function goPage(id) {
  const url = PAGE_URLS[id];
  if (url) window.location.href = url;
}

/* ════════════════════════════════════════════════════════════════
   CART PERSISTENCE
════════════════════════════════════════════════════════════════ */
function saveCart() {
  localStorage.setItem('bythebel_cart', JSON.stringify(cart));
}
function loadCart() {
  try {
    const stored = localStorage.getItem('bythebel_cart');
    if (stored) cart = JSON.parse(stored);
  } catch(e) { cart = []; }
}

/* ════════════════════════════════════════════════════════════════
   AUTH TOKEN HELPERS
════════════════════════════════════════════════════════════════ */
function getShopToken() {
  try {
    const sess = localStorage.getItem('bythebel_session');
    if (!sess) return '';
    const obj = JSON.parse(sess);
    return obj.token || '';
  } catch(e) { return ''; }
}

async function shopFetch(url, options = {}) {
  const token  = getShopToken();
  const method = (options.method || 'GET').toUpperCase();
  const headers = { ...(options.headers || {}) };

  if (token) headers['Authorization'] = 'Bearer ' + token;

  /* Always append token as query param — guarantees auth even when Apache
     strips the Authorization header (common on shared/XAMPP hosts) */
  let finalUrl = url;
  if (token) {
    finalUrl += (url.includes('?') ? '&' : '?') + 'token=' + encodeURIComponent(token);
  }

  /* POST JSON: embed token in body */
  if (method !== 'GET' && !(options.body instanceof FormData)) {
    headers['Content-Type'] = 'application/json';
    if (token && options.body) {
      try {
        const parsed = JSON.parse(options.body);
        if (!parsed.token) parsed.token = token;
        options = { ...options, body: JSON.stringify(parsed) };
      } catch(e) { /* leave body as-is */ }
    }
  }

  return fetch(finalUrl, { ...options, headers });
}

/* ════════════════════════════════════════════════════════════════
   PRODUCT LOADER — fetches from api/products.php only
════════════════════════════════════════════════════════════════ */
async function loadProducts(onLoaded) {
  try {
    const res = await fetch('api/products.php');
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    allProducts = Array.isArray(data) ? data : [];
  } catch(err) {
    console.error('loadProducts failed:', err);
    allProducts = [];
  }
  if (typeof onLoaded === 'function') onLoaded(allProducts);
}

/* ── SIZE & CATEGORY LOADERS ─────────────────────────────────────── */
async function loadSizes(onLoaded) {
  try {
    const res = await fetch('api/sizes.php');
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    allSizes = Array.isArray(data) ? data : [];
  } catch(e) {
    console.error('loadSizes failed:', e);
    allSizes = [];
  }
  if (typeof onLoaded === 'function') onLoaded(allSizes);
}

async function loadCategories(onLoaded) {
  try {
    const res = await fetch('api/categories.php');
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    allCategories = Array.isArray(data) ? data : [];
  } catch(e) {
    console.error('loadCategories failed:', e);
    allCategories = [];
  }
  if (typeof onLoaded === 'function') onLoaded(allCategories);
}

async function loadReviews(onLoaded) {
  try {
    const res = await fetch('api/reviews.php');
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    reviews = Array.isArray(data) ? data : [];
  } catch(e) {
    console.error('loadReviews failed:', e);
    reviews = [];
  }
  if (typeof onLoaded === 'function') onLoaded(reviews);
}

/* ════════════════════════════════════════════════════════════════
   PRODUCT MODAL
════════════════════════════════════════════════════════════════ */
function openModal(p) {
  currentProduct = p;
  currentImages  = (p.images && p.images.length) ? p.images : [p.image].filter(Boolean);
  const ci       = typeof p.coverIndex === 'number' ? p.coverIndex : (typeof p.cover_index === 'number' ? p.cover_index : 0);
  currentImgIdx  = (ci < currentImages.length) ? ci : 0;
  renderGallery();
  document.getElementById('modal-name').textContent      = p.name;
  document.getElementById('modal-price').textContent     = '₱' + p.price.toLocaleString();
  document.getElementById('modal-size-val').textContent  = p.size || '—';
  document.getElementById('modal-desc').textContent      = p.desc || '';

  const condBadge = document.getElementById('modal-condition-badge');
  const condDesc  = document.getElementById('modal-condition-desc');
  const cond = (p.condition || '').toLowerCase().replace(/\s+/g, '-');
  condBadge.textContent = p.condition || '—';
  condBadge.className   = 'condition-badge' + (cond ? ` ${cond}` : '');
  const condDescMap = {
    'brand new':    'Never worn. Tags may still be attached.',
    'like new':     'Worn once or twice. No visible flaws.',
    'lightly used': 'Gently worn a few times. Minimal signs of use.',
    'well used':    'Noticeable wear but still in good shape.',
    'heavily used': 'Significant wear. Priced accordingly.',
  };
  if (condDesc) condDesc.textContent = p.condition ? '— ' + (condDescMap[(p.condition || '').toLowerCase()] || '') : '';

  const catContainer = document.getElementById('modal-category-tag');
  const catList = p.categories && p.categories.length ? p.categories : (p.category ? [{name: p.category}] : []);
  if (catList.length) {
    catContainer.textContent = catList.map(c => c.name).join(', ');
  } else {
    catContainer.textContent = '—';
  }

  const inCart      = cart.some(c => c.product.id == p.id);
  const isUnavailable = p.status === 'sold' || p.status === 'reserved';
  const cartBtn     = document.getElementById('modal-cart-btn');
  const buyBtn      = document.getElementById('modal-buy-btn') || document.querySelector('.btn-buy');

  if (p.status === 'sold')          { cartBtn.textContent = 'Sold Out';          cartBtn.disabled = true;  cartBtn.style.opacity = '0.4'; }
  else if (p.status === 'reserved') { cartBtn.textContent = 'Reserved';           cartBtn.disabled = true;  cartBtn.style.opacity = '0.4'; }
  else if (inCart)                  { cartBtn.textContent = 'In Cart';            cartBtn.disabled = true;  cartBtn.style.opacity = '0.5'; }
  else if (!currentUser)            { cartBtn.textContent = 'Log in to Add to Cart'; cartBtn.disabled = false; cartBtn.style.opacity = ''; }
  else                              { cartBtn.textContent = 'Add to Cart';        cartBtn.disabled = false; cartBtn.style.opacity = ''; }

  /* Buy Now button label for guests */
  if (buyBtn) {
    buyBtn.textContent = currentUser ? 'Buy Now' : 'Log in to Buy';
  }

  document.getElementById('modal-overlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function renderGallery() {
  const mainImg = document.getElementById('modal-img-main');
  mainImg.src   = currentImages[currentImgIdx] || '';
  mainImg.alt   = currentProduct ? currentProduct.name : '';

  const dotsEl  = document.getElementById('modal-gallery-dots');
  const strip   = document.getElementById('modal-thumbs-strip');
  if (dotsEl)  dotsEl.innerHTML  = '';
  if (strip)   strip.innerHTML   = '';

  if (currentImages.length <= 1) return;

  currentImages.forEach((src, i) => {
    /* dot */
    if (dotsEl) {
      const d = document.createElement('button');
      d.className = 'gallery-dot' + (i === currentImgIdx ? ' active' : '');
      d.setAttribute('aria-label', `Photo ${i + 1}`);
      d.onclick = (e) => { e.stopPropagation(); currentImgIdx = i; renderGallery(); };
      dotsEl.appendChild(d);
    }
    /* thumbnail */
    if (strip) {
      const t = document.createElement('div');
      t.className = 'thumb-img' + (i === currentImgIdx ? ' active' : '');
      t.innerHTML = `<img src="${src}" alt="view ${i + 1}" loading="lazy">`;
      t.onclick   = () => { currentImgIdx = i; renderGallery(); };
      strip.appendChild(t);
    }
  });
}

function galleryPrev(e) { if(e) e.stopPropagation(); if(!currentImages.length) return; currentImgIdx=(currentImgIdx-1+currentImages.length)%currentImages.length; renderGallery(); }
function galleryNext(e) { if(e) e.stopPropagation(); if(!currentImages.length) return; currentImgIdx=(currentImgIdx+1)%currentImages.length; renderGallery(); }
function closeModal()   { document.getElementById('modal-overlay').classList.remove('open'); document.body.style.overflow=''; }
function closeModalOutside(e) { if(e.target===document.getElementById('modal-overlay')) closeModal(); }

/* ════════════════════════════════════════════════════════════════
   CART
════════════════════════════════════════════════════════════════ */
function addToCart() {
  if (!currentProduct) return;
  /* Must be logged in to add items */
  if (!currentUser) {
    closeModal();
    openAuth();
    showToast('Please log in to add items to your cart.');
    return;
  }
  if (currentProduct.status === 'sold')     { showToast('This item has already been sold.'); return; }
  if (currentProduct.status === 'reserved') { showToast('This item is currently reserved by another buyer.'); return; }
  if (cart.some(c => c.product.id == currentProduct.id)) { showToast('Already in cart!'); return; }
  cart.push({ product: currentProduct, size: currentProduct.size });
  saveCart();
  updateCartBadge();
  renderCart();
  if (typeof buildFeatured === 'function') buildFeatured(allProducts);
  if (typeof renderProducts === 'function') renderProducts(applyFiltersAndSortSilent ? applyFiltersAndSortSilent() : allProducts);
  showToast(`${currentProduct.name} added to cart`);
  closeModal();
}

function buyNow() {
  if (!currentProduct) return;
  /* Must be logged in to buy */
  if (!currentUser) {
    closeModal();
    openAuth();
    showToast('Please log in to place an order.');
    return;
  }
  if (!cart.some(c => c.product.id == currentProduct.id)) {
    cart.push({ product: currentProduct, size: currentProduct.size });
    saveCart();
    updateCartBadge();
  }
  closeModal();
  goToCheckout();
}

function removeFromCart(productId) {
  const removed = cart.find(c => c.product.id == productId);
  if (!removed) return;
  const itemEl = document.querySelector(`.cart-item[data-id="${productId}"]`);
  const doRemove = () => {
    cart = cart.filter(c => c.product.id != productId);
    saveCart();
    updateCartBadge();
    renderCart();
    if (typeof buildFeatured === 'function') buildFeatured(allProducts);
    if (typeof renderProducts === 'function') renderProducts(applyFiltersAndSortSilent ? applyFiltersAndSortSilent() : allProducts);
    showToast(`${removed.product.name} removed from cart.`);
  };
  if (itemEl) { itemEl.classList.add('removing'); itemEl.addEventListener('animationend', doRemove, { once:true }); }
  else doRemove();
}

function cartTotal() { return cart.reduce((s,c) => s + c.product.price, 0); }

function updateCartBadge() {
  const badge = document.getElementById('cart-count');
  if (badge) badge.textContent = cart.length;
}

/* ── ORDER STATUS NOTIFICATION BADGES ───────────────────────────
   Persists a map of { orderId: lastSeenStatus } in localStorage.
   Any order whose current status differs from the stored one is
   counted as "unseen". Badges clear when the user opens that tab. */

function _orderSeenKey() {
  return 'order_seen_' + (currentUser?.user_id || 'guest');
}

function _getSeenMap() {
  try { return JSON.parse(localStorage.getItem(_orderSeenKey()) || '{}'); } catch { return {}; }
}

function _saveSeenMap(map) {
  try { localStorage.setItem(_orderSeenKey(), JSON.stringify(map)); } catch {}
}

function _countUnseen(orders, statusList) {
  const seen = _getSeenMap();
  return orders.filter(o =>
    statusList.includes(o.orderStatus) && seen[o.id] !== o.orderStatus
  ).length;
}

function _markTabSeen(orders, statusList) {
  const seen = _getSeenMap();
  orders.forEach(o => { if (statusList.includes(o.orderStatus)) seen[o.id] = o.orderStatus; });
  _saveSeenMap(seen);
}

function updateOrderBadges(orders) {
  if (!orders) return;
  const activeCount  = _countUnseen(orders, ACTIVE_ORDER_STATUSES);
  const historyCount = _countUnseen(orders, FINAL_ORDER_STATUSES);

  _setBadge('order-badge-active',  activeCount);
  _setBadge('order-badge-history', historyCount);
  _setBadge('op-tab-badge-active',  activeCount);
  _setBadge('op-tab-badge-history', historyCount);
}

function _setBadge(id, count) {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = count || '';
  el.style.display = count ? 'inline-flex' : 'none';
}

function renderCart() {
  const list      = document.getElementById('cart-items-list');
  const totalEl   = document.getElementById('cart-total-display');
  const btn       = document.getElementById('btn-checkout');
  const noteDefault = document.getElementById('cart-note-default');
  const noteLogin   = document.getElementById('cart-note-login');
  if (!list) return;

  if (!cart.length) {
    list.innerHTML = '<div class="cart-empty-msg">Your cart is empty.<br>Browse the shop and add something you love!</div>';
    if (totalEl) totalEl.textContent = '₱0';
    if (btn)     btn.disabled = true;
    if (noteDefault) noteDefault.style.display = '';
    if (noteLogin)   noteLogin.style.display   = 'none';
    return;
  }
  list.innerHTML = cart.map(({product:p}) => `
    <div class="cart-item" data-id="${p.id}">
      <div class="cart-item-img"><img src="${getCoverImage(p)}" alt="${p.name}" loading="lazy"></div>
      <div class="cart-item-info">
        <div class="cart-item-name">${p.name}</div>
        <div class="cart-item-size">Size: ${p.size||'—'}</div>
        <div class="cart-item-price">₱${p.price.toLocaleString()}</div>
      </div>
      <button class="cart-item-remove" onclick="removeFromCart('${p.id}')" title="Remove">✕</button>
    </div>`).join('');
  if (totalEl) totalEl.textContent = '₱' + cartTotal().toLocaleString();

  if (currentUser) {
    if (btn) btn.disabled = false;
    if (noteDefault) noteDefault.style.display = '';
    if (noteLogin)   noteLogin.style.display   = 'none';
  } else {
    if (btn) btn.disabled = true;
    if (noteDefault) noteDefault.style.display = 'none';
    if (noteLogin)   noteLogin.style.display   = '';
  }
}

function openCart() {
  renderCart();
  document.getElementById('cart-overlay').classList.add('open');
  document.getElementById('cart-drawer').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeCart() {
  document.getElementById('cart-overlay').classList.remove('open');
  document.getElementById('cart-drawer').classList.remove('open');
  document.body.style.overflow = '';
}

function goToCheckout() {
  if (!currentUser) { closeCart(); openAuth(); showToast('Please log in to place an order.'); return; }
  closeCart();
  window.location.href = PAGE_URLS.payment;
}

/* ════════════════════════════════════════════════════════════════
   AUTH SYSTEM
════════════════════════════════════════════════════════════════ */
function openAuth()  { document.getElementById('auth-overlay').classList.add('open'); document.body.style.overflow='hidden'; }
function closeAuth() { document.getElementById('auth-overlay').classList.remove('open'); document.body.style.overflow=''; clearAuthErrors(); }
function closeAuthOutside(e) { if(e.target===document.getElementById('auth-overlay')) closeAuth(); }

function switchAuthTab(tab) {
  document.getElementById('panel-login').classList.toggle('active', tab==='login');
  document.getElementById('panel-register').classList.toggle('active', tab==='register');
  document.getElementById('tab-login').classList.toggle('active', tab==='login');
  document.getElementById('tab-register').classList.toggle('active', tab==='register');
  clearAuthErrors();
}

function clearAuthErrors() {
  ['login-error','reg-error'].forEach(id => {
    const el = document.getElementById(id);
    if (el) { el.textContent=''; el.classList.remove('show'); }
  });
}

function showAuthError(id, msg) {
  const el = document.getElementById(id);
  if (el) { el.textContent=msg; el.classList.add('show'); }
}

function setCurrentUser(user) {
  currentUser = user;
  const guest = document.getElementById('nav-auth-guest');
  const pill  = document.getElementById('nav-auth-user');
  const uname = document.getElementById('nav-username');
  if (guest) guest.style.display = user ? 'none' : '';
  if (pill)  pill.style.display  = user ? '' : 'none';
  if (uname && user) uname.textContent = user.first_name;

  /* Populate dropdown header */
  const dfull  = document.getElementById('dropdown-fullname');
  const demail = document.getElementById('dropdown-email');
  if (dfull)  dfull.textContent  = user ? `${user.first_name} ${user.last_name}` : '—';
  if (demail) demail.textContent = user ? (user.email || '—') : '—';

  /* Review form visibility — desktop sidebar */
  const wall = document.getElementById('review-login-wall');
  const form = document.getElementById('review-form-card');
  if (wall) wall.style.display = user ? 'none' : '';
  if (form) form.style.display = user ? ''     : 'none';
  /* Review form visibility — mobile panel */
  const mWall = document.getElementById('mobile-review-login-wall');
  const mForm = document.getElementById('mobile-review-form-card');
  if (mWall) mWall.style.display = user ? 'none' : '';
  if (mForm) mForm.style.display = user ? ''     : 'none';

  /* Contact page — show form or login wall */
  const contactWall = document.getElementById('contact-login-wall');
  const contactForm = document.getElementById('contact-form-wrap');
  const senderName  = document.getElementById('contact-sender-name');
  if (contactWall) contactWall.style.display = user ? 'none'  : 'block';
  if (contactForm) contactForm.style.display = user ? 'block' : 'none';
  if (senderName && user) senderName.textContent = `${user.first_name} ${user.last_name}`;

  /* Refresh product modal button labels if the modal is currently open */
  const modalOverlay = document.getElementById('modal-overlay');
  if (currentProduct && modalOverlay && modalOverlay.classList.contains('open')) {
    const cartBtn = document.getElementById('modal-cart-btn');
    const buyBtn  = document.querySelector('.btn-buy');
    const inCart  = cart.some(c => c.product.id == currentProduct.id);
    if (cartBtn && !inCart && currentProduct.status === 'available') {
      cartBtn.textContent = user ? 'Add to Cart' : 'Log in to Add to Cart';
      cartBtn.disabled    = false;
      cartBtn.style.opacity = '';
    }
    if (buyBtn) buyBtn.textContent = user ? 'Buy Now' : 'Log in to Buy';
  }

  renderCart();
}

/* ════════════════════════════════════════════════════════════════
   USER DROPDOWN
════════════════════════════════════════════════════════════════ */
function toggleUserDropdown(e) {
  if (e) e.stopPropagation();
  const pill     = document.getElementById('nav-auth-user');
  const dropdown = document.getElementById('user-dropdown');
  if (!pill || !dropdown) return;
  const isOpen = dropdown.classList.contains('open');
  dropdown.classList.toggle('open', !isOpen);
  pill.classList.toggle('open', !isOpen);
}

function _closeUserDropdown() {
  const dropdown = document.getElementById('user-dropdown');
  const pill     = document.getElementById('nav-auth-user');
  if (dropdown) dropdown.classList.remove('open');
  if (pill)     pill.classList.remove('open');
}

/* ════════════════════════════════════════════════════════════════
   EDIT PROFILE
════════════════════════════════════════════════════════════════ */
async function openEditProfile(e) {
  if (e) e.stopPropagation();
  _closeUserDropdown();
  if (!currentUser) { openAuth(); return; }

  /* Open the modal immediately with cached session data */
  _fillProfileModal(currentUser);
  document.getElementById('profile-modal-overlay').classList.add('open');
  document.body.style.overflow = 'hidden';

  /* Then refresh from API to get the latest data (phone, address, etc.) */
  try {
    const res  = await shopFetch('api/profile.php');
    const data = await res.json();
    if (data && !data.error) {
      /* Merge fresh data with current session */
      const merged = { ...currentUser, ...data };
      currentUser  = merged;
      localStorage.setItem('bythebel_session', JSON.stringify(merged));
      _fillProfileModal(merged);
    }
  } catch(err) {
    /* Use cached data — already filled above */
    console.warn('Could not refresh profile from API:', err);
  }
}

function _fillProfileModal(user) {
  const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };
  set('prof-fname',        user.first_name || '');
  set('prof-lname',        user.last_name  || '');
  set('prof-email',        user.email      || '');
  set('prof-phone',        user.phone      || '');
  set('prof-address',      user.address    || '');
  set('prof-cur-pass',     '');
  set('prof-new-pass',     '');
  set('prof-confirm-pass', '');
  _profileMsg('error',   '');
  _profileMsg('success', '');
}

function closeEditProfile() {
  document.getElementById('profile-modal-overlay').classList.remove('open');
  document.body.style.overflow = '';
}

function closeProfileOutside(e) {
  if (e.target === document.getElementById('profile-modal-overlay')) closeEditProfile();
}

function _profileMsg(type, msg) {
  const el = document.getElementById('profile-' + type);
  if (!el) return;
  if (msg) {
    el.textContent = msg;
    el.classList.add('show');
    /* Auto-hide success after 4 s */
    if (type === 'success') {
      clearTimeout(el._timer);
      el._timer = setTimeout(() => el.classList.remove('show'), 4000);
    }
  } else {
    el.textContent = '';
    el.classList.remove('show');
  }
}

async function saveProfile() {
  const fname      = document.getElementById('prof-fname').value.trim();
  const lname      = document.getElementById('prof-lname').value.trim();
  const email      = document.getElementById('prof-email').value.trim();
  const phone      = document.getElementById('prof-phone').value.trim();
  const address    = document.getElementById('prof-address').value.trim();
  const curPass    = document.getElementById('prof-cur-pass').value;
  const newPass    = document.getElementById('prof-new-pass').value;
  const confirmPass = document.getElementById('prof-confirm-pass').value;

  _profileMsg('error', '');
  _profileMsg('success', '');

  if (!fname || !lname)              { _profileMsg('error', 'First and last name are required.'); return; }
  if (!email)                        { _profileMsg('error', 'Email is required.'); return; }
  if (newPass && newPass.length < 8) { _profileMsg('error', 'New password must be at least 8 characters.'); return; }
  if (newPass && newPass !== confirmPass) { _profileMsg('error', 'New passwords do not match.'); return; }
  if (newPass && !curPass)           { _profileMsg('error', 'Please enter your current password to set a new one.'); return; }

  const btn = document.getElementById('profile-save-btn');
  btn.disabled    = true;
  btn.textContent = 'Saving…';

  try {
    const payload = { first_name: fname, last_name: lname, email, phone, address };
    if (newPass) { payload.current_password = curPass; payload.new_password = newPass; }

    const res  = await shopFetch('api/profile.php', { method: 'POST', body: JSON.stringify(payload) });
    const data = await res.json();

    if (data.error) { _profileMsg('error', data.error); return; }

    /* Update local session with new data */
    const updated = { ...currentUser, first_name: fname, last_name: lname, email, phone, address };
    localStorage.setItem('bythebel_session', JSON.stringify(updated));
    setCurrentUser(updated);

    _profileMsg('success', 'Profile updated successfully!');
    showToast('Profile updated!');

    /* Auto-close after a short delay */
    setTimeout(closeEditProfile, 1800);
  } catch(err) {
    _profileMsg('error', 'Network error. Please try again.');
    console.error('saveProfile error:', err);
  } finally {
    btn.disabled    = false;
    btn.textContent = 'Save Changes';
  }
}

async function doLogin() {
  const email = document.getElementById('login-email').value.trim();
  const pass  = document.getElementById('login-pass').value;
  if (!email || !pass) { showAuthError('login-error', 'Please enter your email and password.'); return; }

  try {
    const res  = await fetch('api/auth.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ action: 'login', email, password: pass }),
    });
    const data = await res.json();
    if (data.error) { showAuthError('login-error', data.error); return; }
    if (data.success && data.user) {
      localStorage.setItem('bythebel_session', JSON.stringify(data.user));
      /* Admin accounts go straight to the admin dashboard */
      if (data.user.role === 'admin') {
        window.location.href = 'admin.php';
        return;
      }
      /* Customer accounts stay on the shop — close modal */
      setCurrentUser(data.user);
      closeAuth();
      showToast(`Welcome back, ${data.user.first_name}!`);
    } else {
      showAuthError('login-error', 'Login failed. Please try again.');
    }
  } catch(e) {
    showAuthError('login-error', 'Network error. Please try again.');
    console.error('doLogin error:', e);
  }
}

function togglePassVisibility(inputId, btn) {
  const input = document.getElementById(inputId);
  const isHidden = input.type === 'password';
  input.type = isHidden ? 'text' : 'password';
  btn.querySelector('.eye-open').style.display = isHidden ? 'none' : '';
  btn.querySelector('.eye-off').style.display  = isHidden ? '' : 'none';
}

async function doRegister() {
  const fname   = document.getElementById('reg-fname').value.trim();
  const lname   = document.getElementById('reg-lname').value.trim();
  const email   = document.getElementById('reg-email').value.trim();
  const phone   = document.getElementById('reg-phone').value.trim();
  const pass    = document.getElementById('reg-pass').value;
  const confirm = document.getElementById('reg-confirm-pass').value;
  if (!fname || !lname) { showAuthError('reg-error', 'Please enter your first and last name.'); return; }
  if (!email)           { showAuthError('reg-error', 'Please enter a valid email.'); return; }
  if (pass.length < 8)  { showAuthError('reg-error', 'Password must be at least 8 characters.'); return; }
  if (pass !== confirm)  { showAuthError('reg-error', 'Passwords do not match. Please try again.'); return; }

  try {
    const res  = await fetch('api/auth.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ action: 'register', first_name: fname, last_name: lname, email, phone, password: pass }),
    });
    const data = await res.json();
    if (data.error) { showAuthError('reg-error', data.error); return; }
    if (data.success && data.user) {
      localStorage.setItem('bythebel_session', JSON.stringify(data.user));
      setCurrentUser(data.user);
      closeAuth();
      showToast(`Account created! Welcome, ${fname}!`);
    } else {
      showAuthError('reg-error', 'Registration failed. Please try again.');
    }
  } catch(e) {
    showAuthError('reg-error', 'Network error. Please try again.');
    console.error('doRegister error:', e);
  }
}

async function logOut(e) {
  if (e) e.stopPropagation();
  _closeUserDropdown();

  const token = getShopToken();
  if (token) {
    try {
      await fetch('api/auth.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
        body:    JSON.stringify({ action: 'logout', token }),
      });
    } catch(err) { /* ignore network errors on logout */ }
  }
  localStorage.removeItem('bythebel_session');
  cart = [];
  saveCart();
  /* Return to shop home — user icon becomes the login trigger */
  window.location.href = 'shop.php';
}

function restoreSession() {
  const stored = localStorage.getItem('bythebel_session');
  if (stored) {
    try {
      const user = JSON.parse(stored);
      if (user && user.token) setCurrentUser(user);
      else localStorage.removeItem('bythebel_session');
    } catch(e) { localStorage.removeItem('bythebel_session'); }
  }
}

/* ════════════════════════════════════════════════════════════════
   REVIEWS
════════════════════════════════════════════════════════════════ */
function setRating(val, ctx) {
  currentRating = val;
  const pickerId = ctx === 'mobile' ? 'mobile-star-picker' : 'star-picker';
  const picker = document.getElementById(pickerId);
  if (picker) picker.querySelectorAll('.star-btn').forEach((btn,i) => btn.classList.toggle('lit', i<val));
  /* Keep both pickers in sync visually */
  const otherId = ctx === 'mobile' ? 'star-picker' : 'mobile-star-picker';
  const other = document.getElementById(otherId);
  if (other) other.querySelectorAll('.star-btn').forEach((btn,i) => btn.classList.toggle('lit', i<val));
}

/* Tracks which edit forms are open and which images are pending deletion */
const _openEditForms      = new Set();
const _pendingDeleteImages = new Map(); // reviewId -> Set of image_ids

function relativeTime(timestamp) {
  if (!timestamp) return '';
  const d     = new Date(timestamp * 1000);
  const today = new Date();
  const isToday = d.getFullYear() === today.getFullYear()
               && d.getMonth()    === today.getMonth()
               && d.getDate()     === today.getDate();
  if (isToday) {
    return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
  }
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

async function submitReview(ctx) {
  if (!currentUser) { showToast('Please log in to post a review.'); openAuth(); return; }
  const bodyId = ctx === 'mobile' ? 'mobile-rv-body' : 'rv-body';
  const body = document.getElementById(bodyId)?.value.trim();
  if (!currentRating) { showToast('Please select a star rating.'); return; }
  if (!body)          { showToast('Please write your review.'); return; }

  try {
    const res  = await shopFetch('api/reviews.php', {
      method: 'POST',
      body:   JSON.stringify({ product_id: null, rating: currentRating, body }),
    });
    const data = await res.json();
    if (data.error) { showToast(data.error); return; }
    if (data.success && data.review) {
      reviews.unshift(data.review);
      renderReviews();
      /* Clear both textareas and reset stars */
      const rvBody = document.getElementById('rv-body');
      const mRvBody = document.getElementById('mobile-rv-body');
      if (rvBody)  rvBody.value  = '';
      if (mRvBody) mRvBody.value = '';
      setRating(0);
      /* Collapse mobile panel after submit */
      document.getElementById('mobile-review-form-panel')?.classList.remove('open');
      showToast('Review posted! Thank you!');
    }
  } catch(e) {
    showToast('Failed to post review. Please try again.');
    console.error('submitReview error:', e);
  }
}

/* ── Edit review ─────────────────────────────────────────────── */
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
    setTimeout(() => document.getElementById(`edit-body-${reviewId}`)?.focus(), 50);
  }
}

function toggleDeleteImage(reviewId, imageId) {
  if (!_pendingDeleteImages.has(reviewId)) _pendingDeleteImages.set(reviewId, new Set());
  const set = _pendingDeleteImages.get(reviewId);
  if (set.has(imageId)) set.delete(imageId); else set.add(imageId);
  /* Just update the visual state without full re-render */
  const imgEl = document.getElementById(`edit-img-${imageId}`);
  if (imgEl) imgEl.classList.toggle('pending-delete', set.has(imageId));
  const btn = document.getElementById(`edit-img-btn-${imageId}`);
  if (btn) btn.textContent = set.has(imageId) ? 'Undo' : '✕';
}

async function saveEditReview(reviewId) {
  const stars = document.querySelectorAll(`#edit-form-${reviewId} .edit-star-btn`);
  let rating = 0;
  stars.forEach((s, i) => { if (s.classList.contains('lit')) rating = i + 1; });
  const body = document.getElementById(`edit-body-${reviewId}`)?.value.trim();

  if (!rating) { showToast('Please select a star rating.'); return; }
  if (!body)   { showToast('Please write your review.'); return; }

  const btn = document.getElementById(`edit-save-btn-${reviewId}`);
  if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }

  try {
    const fd = new FormData();
    fd.append('action', 'edit');
    fd.append('review_id', reviewId);
    fd.append('rating', rating);
    fd.append('body', body);

    const deleteSet = _pendingDeleteImages.get(reviewId) || new Set();
    fd.append('delete_image_ids', [...deleteSet].join(','));

    const newImgInput = document.getElementById(`edit-new-images-${reviewId}`);
    if (newImgInput && newImgInput.files.length) {
      [...newImgInput.files].slice(0, 5).forEach(f => fd.append('images[]', f));
    }

    const res  = await shopFetch('api/reviews.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.error) { showToast(data.error); return; }

    const review = reviews.find(r => r.review_id === reviewId);
    if (review) {
      review.rating = rating;
      review.body   = body;
      review.images = data.images || [];
    }
    _openEditForms.delete(reviewId);
    _pendingDeleteImages.delete(reviewId);
    renderReviews();
    showToast('Review updated!');
  } catch(e) {
    showToast('Failed to update review.');
    console.error('saveEditReview:', e);
  } finally {
    if (btn) { btn.disabled = false; btn.textContent = 'Save'; }
  }
}

function setEditRating(reviewId, val) {
  document.querySelectorAll(`#edit-form-${reviewId} .edit-star-btn`)
    .forEach((btn, i) => btn.classList.toggle('lit', i < val));
}

/* ── Delete review ───────────────────────────────────────────── */
async function deleteReview(reviewId) {
  if (!confirm('Delete this review? This cannot be undone.')) return;
  try {
    const res  = await shopFetch('api/reviews.php', {
      method: 'DELETE',
      body:   JSON.stringify({ review_id: reviewId }),
    });
    const data = await res.json();
    if (data.error) { showToast(data.error); return; }
    reviews = reviews.filter(r => r.review_id !== reviewId);
    renderReviews();
    showToast('Review deleted.');
  } catch(e) {
    showToast('Failed to delete review.');
    console.error('deleteReview:', e);
  }
}

/* ── Render ──────────────────────────────────────────────────── */
function renderReviews() {
  const listEl = document.getElementById('reviews-list');
  if (!listEl) return;
  if (!reviews.length) { listEl.innerHTML='<div class="reviews-empty">No reviews yet. Be the first!</div>'; updateRatingSummary(); return; }

  listEl.innerHTML = reviews.map(r => {
    const rid     = r.review_id;
    const isOwner = currentUser && currentUser.user_id === r.user_id;
    const isEditing = _openEditForms.has(rid);

    /* ── Owner actions ── */
    const ownerActionsHtml = isOwner ? `
      <div class="review-owner-actions">
        <button class="btn-review-action" onclick="toggleEditReview(${rid})" title="${isEditing ? 'Cancel' : 'Edit'}">
          ${isEditing ? 'Cancel' : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> Edit'}
        </button>
        <button class="btn-review-action btn-review-delete" onclick="deleteReview(${rid})" title="Delete">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg> Delete
        </button>
      </div>` : '';

    /* ── Edit form ── */
    const pendingDeletes = _pendingDeleteImages.get(rid) || new Set();
    const existingImagesHtml = (r.images && r.images.length) ? `
      <div class="edit-existing-images">
        ${r.images.map(img => `
          <div class="edit-img-wrap${pendingDeletes.has(img.id) ? ' pending-delete' : ''}" id="edit-img-${img.id}">
            <img src="${escHtml(img.path)}" alt="Review photo">
            <button class="btn-edit-img-delete" id="edit-img-btn-${img.id}" onclick="toggleDeleteImage(${rid},${img.id})">${pendingDeletes.has(img.id) ? 'Undo' : '✕'}</button>
          </div>`).join('')}
      </div>` : '';

    const editFormHtml = isEditing ? `
      <div class="review-edit-form" id="edit-form-${rid}">
        <div class="edit-stars-row">
          ${[1,2,3,4,5].map(v => `<button class="edit-star-btn${v <= r.rating ? ' lit' : ''}" onclick="setEditRating(${rid},${v})">★</button>`).join('')}
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
      </div>` : '';

    /* ── Admin reply ── */
    const adminReplyHtml = r.admin_reply ? `
      <div class="review-admin-reply">
        <div class="review-admin-reply-label">Admin Reply${r.reply_date ? `<span class="review-admin-reply-date"> · ${escHtml(r.reply_date)}</span>` : ''}</div>
        <div class="review-admin-reply-text">${escHtml(r.admin_reply)}</div>
      </div>` : '';

    const timeLabel = r.date;

    const imagesHtml = (r.images && r.images.length && !isEditing) ? `
      <div class="review-images">
        ${r.images.map(img => {
          const src = typeof img === 'object' ? img.path : img;
          return `<img class="review-img-full" src="${escHtml(src)}" alt="Review photo" loading="lazy" onclick="openReviewPhoto('${escHtml(src)}')")>`;
        }).join('')}
      </div>` : '';

    const productChipHtml = (r.product && !isEditing) ? `
      <div class="review-product-chip-bottom">
        ${r.product_image ? `<img class="review-product-chip-thumb" src="${escHtml(r.product_image)}" alt="${escHtml(r.product)}" loading="lazy">` : ''}
        <div class="review-product-chip-info">
          <div class="review-product-chip-name">${escHtml(r.product)}</div>
          ${r.product_price ? `<div class="review-product-chip-price">PHP ${Number(r.product_price).toLocaleString()}</div>` : ''}
        </div>
      </div>` : '';

    return `
      <div class="review-card">
        <div class="review-header">
          <span class="review-name">${escHtml(r.name)}</span>
          <div style="display:flex;align-items:center;gap:0.6rem;">
            <span class="review-date">· ${timeLabel}</span>
            ${ownerActionsHtml}
          </div>
        </div>
        <div class="review-stars">${'★'.repeat(r.rating)}${'☆'.repeat(5-r.rating)}</div>
        ${isEditing ? editFormHtml : `<div class="review-body">${escHtml(r.body)}</div>`}
        ${imagesHtml}
        ${productChipHtml}
        ${adminReplyHtml}
      </div>`;
  }).join('');

  updateRatingSummary();
}

function updateRatingSummary() {
  const total  = reviews.length;
  const avgEl  = document.getElementById('avg-num');
  const stEl   = document.getElementById('avg-stars');
  const cntEl  = document.getElementById('review-count-label');
  const barsEl = document.getElementById('rating-bars');
  if (!avgEl) return;
  if (!total) { avgEl.textContent='—'; stEl.textContent='☆☆☆☆☆'; cntEl.textContent='0 reviews'; barsEl.innerHTML=''; return; }
  const avg = reviews.reduce((s,r)=>s+r.rating,0)/total;
  avgEl.textContent = avg.toFixed(1);
  const filled = Math.round(avg);
  stEl.textContent = '★'.repeat(filled)+'☆'.repeat(5-filled);
  cntEl.textContent = `${total} review${total===1?'':'s'}`;
  const counts = [5,4,3,2,1].map(v=>reviews.filter(r=>r.rating===v).length);
  barsEl.innerHTML = [5,4,3,2,1].map((star,i)=>`
    <div class="r-bar-row">
      <span class="r-bar-label">${star}★</span>
      <div class="r-bar-track"><div class="r-bar-fill" style="width:${total?Math.round(counts[i]/total*100):0}%"></div></div>
    </div>`).join('');
}

/* ════════════════════════════════════════════════════════════════
   CONTACT
════════════════════════════════════════════════════════════════ */
function sendMessage() {
  if (!currentUser) {
    openAuth();
    showToast('Please log in to send a message.');
    return;
  }
  const subject = document.getElementById('c-subject')?.value.trim();
  const message = document.getElementById('c-message')?.value.trim();
  if (!subject) { showToast('Please enter a subject.'); return; }
  if (!message) { showToast('Please write your message.'); return; }

  const btn = document.getElementById('contact-send-btn');
  if (btn) { btn.disabled = true; btn.textContent = 'Sending…'; }

  // TODO: wire up to api/contact.php or EmailJS when ready
  setTimeout(() => {
    showToast(`Message sent! We'll be in touch soon.`);
    ['c-subject','c-message'].forEach(id => {
      const el = document.getElementById(id); if (el) el.value = '';
    });
    if (btn) { btn.disabled = false; btn.textContent = 'Send Message'; }
  }, 600);
}

/* ════════════════════════════════════════════════════════════════
   PAYMENT
════════════════════════════════════════════════════════════════ */
function buildOrderSummary() {
  const linesEl = document.getElementById('order-lines');
  if (!linesEl) return;
  linesEl.innerHTML = cart.map(({product:p}) => {
    const img = getCoverImage(p);
    return `
      <div class="order-line order-line-product">
        ${img ? `<img class="order-line-thumb" src="${img}" alt="${p.name}">` : '<div class="order-line-thumb order-line-thumb-placeholder"></div>'}
        <div class="order-line-info">
          <span class="order-line-name">${p.name}</span>
          <span class="order-line-size">Size: ${p.size || '—'}</span>
        </div>
        <span class="order-line-price">₱${p.price.toLocaleString()}</span>
      </div>`;
  }).join('');
  const totEl = document.getElementById('order-total-display');
  if (totEl) totEl.textContent = '₱' + cartTotal().toLocaleString();
  const grandEl = document.getElementById('order-grand-total');
  if (grandEl) grandEl.textContent = '₱' + cartTotal().toLocaleString();
  if (currentUser) {
    const nameEl  = document.getElementById('pf-name');
    const emailEl = document.getElementById('pf-email');
    const phoneEl = document.getElementById('pf-phone');
    if (nameEl  && !nameEl.value)  nameEl.value  = `${currentUser.first_name} ${currentUser.last_name}`;
    if (emailEl && !emailEl.value) emailEl.value = currentUser.email || '';
    if (phoneEl && !phoneEl.value) phoneEl.value = currentUser.phone || '';
  }
}

function handleProofUpload(e) {
  const file = e.target.files[0];
  if (!file) return;
  if (file.size > 5*1024*1024) { showToast('File too large. Max 5 MB.'); return; }
  const reader = new FileReader();
  reader.onload = ev => {
    document.getElementById('proof-preview-img').src = ev.target.result;
    document.getElementById('proof-preview').style.display = 'block';
  };
  reader.readAsDataURL(file);
}

function toggleConfirmBtn() {
  const checkbox = document.getElementById('agree-no-refund');
  const btn      = document.getElementById('btn-confirm-payment');
  if (btn) btn.disabled = !checkbox?.checked;
}

async function confirmPayment() {
  const agreed = document.getElementById('agree-no-refund')?.checked;
  if (!agreed) { showToast('Please agree to the No Refund Policy before proceeding.'); return; }
  const name  = document.getElementById('pf-name').value.trim();
  const phone = document.getElementById('pf-phone').value.trim();
  const email = document.getElementById('pf-email').value.trim();
  const ref   = document.getElementById('pf-ref').value.trim();
  const proofInput = document.getElementById('proof-file');
  const proof = proofInput ? proofInput.files[0] : null;

  if (!name)  { showToast('Please enter your full name.');            return; }
  if (!phone) { showToast('Please enter your contact number.');       return; }
  if (!ref)   { showToast('Please enter the GCash reference number.'); return; }
  if (!proof) { showToast('Please upload your GCash payment screenshot.'); return; }

  /* Validate and compose the Philippine address */
  const address = window.phAddr ? window.phAddr.validate() : null;
  if (!address) return;
  if (!currentUser) { showToast('Please log in first.'); openAuth(); return; }

  /* Build FormData */
  const shippingFee  = parseFloat(document.getElementById('pf-shipping-fee')?.value || 0);
  const grandTotal   = cartTotal() + shippingFee;

  const formData = new FormData();
  formData.append('items',            JSON.stringify(cart.map(({product:p}) => ({ product_id: p.id, price: p.price, name: p.name || '', image: (p.images && p.images[0]) || '' }))));
  formData.append('total_amount',     grandTotal);
  formData.append('shipping_fee',     shippingFee);
  formData.append('payment_method',   'GCash');
  formData.append('reference_number', ref);
  formData.append('address',          address);
  formData.append('phone',            phone);
  formData.append('proof_image',      proof);

  const submitBtn = document.getElementById('btn-confirm-payment');
  if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Submitting…'; }

  try {
    const res  = await shopFetch('api/orders.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.error) {
      showToast(data.error);
      if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Confirm Payment'; }
      return;
    }
    if (data.success) {
      cart = [];
      saveCart();
      updateCartBadge();
      document.getElementById('payment-form-view').style.display = 'none';
      document.getElementById('payment-success').style.display   = 'block';
      /* Invalidate the orders cache so My Orders fetches fresh data */
      _ordersCache  = null;
      _ordersLoaded = false;
    }
  } catch(e) {
    showToast('Network error. Please try again.');
    console.error('confirmPayment error:', e);
    if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Confirm Payment'; }
  }
}

function resetPayment() {
  document.getElementById('payment-form-view').style.display = '';
  document.getElementById('payment-success').style.display   = 'none';
  const prev = document.getElementById('proof-preview');
  if (prev) prev.style.display = 'none';
  const pf = document.getElementById('proof-file');
  if (pf) pf.value = '';
  ['pf-name','pf-phone','pf-email','pf-ref'].forEach(id => {
    const el = document.getElementById(id); if (el) el.value = '';
  });
  if (window.phAddr) window.phAddr.reset();
  updateCartBadge();
  window.location.href = PAGE_URLS.shop;
}

/* ════════════════════════════════════════════════════════════════
   UTILITY
════════════════════════════════════════════════════════════════ */
/* ════════════════════════════════════════════════════════════════
   MY ORDERS PANEL — opens as a side drawer from the profile icon
   PHP integration point: GET api/my-orders.php (requires auth)
════════════════════════════════════════════════════════════════ */

let _ordersCache  = null;   /* cached after first fetch per session */
let _ordersLoaded = false;

function openMyOrders(e) {
  if (e) e.stopPropagation();
  _closeUserDropdown();
  if (!currentUser) { openAuth(); showToast('Please log in to view your orders.'); return; }
  _openOrdersPanel('active');
}

function openOrderHistory(e) {
  if (e) e.stopPropagation();
  _closeUserDropdown();
  if (!currentUser) { openAuth(); showToast('Please log in to view your order history.'); return; }
  _openOrdersPanel('history');
}

function _openOrdersPanel(tab) {
  const panel = document.getElementById('orders-panel');
  if (!panel) return;

  panel.classList.add('open');
  document.querySelectorAll('.nav-link').forEach(a => a.classList.remove('active'));
  switchOrdersTab(tab);

  /* Always fetch fresh data when panel opens — never show stale cache */
  _ordersCache  = null;
  _fetchAndRenderOrders();
}

function closeMyOrdersPanel() {
  const panel = document.getElementById('orders-panel');
  if (!panel) return;
  panel.classList.remove('open');

  /* Restore active nav link for the current page */
  const path = window.location.pathname.split('/').pop();
  const pageNavMap = {
    'shop.php':          'nav-home',
    'shop-products.php': 'nav-shop',
    'shop-reviews.php':  'nav-reviews',
    'shop-about.php':    'nav-about',
    'shop-contacts.php': 'nav-contacts',
  };
  const navId = pageNavMap[path];
  if (navId) {
    const el = document.getElementById(navId);
    if (el) el.classList.add('active');
  }
}

function switchOrdersTab(tab) {
  const tabActive  = document.getElementById('op-tab-active');
  const tabHistory = document.getElementById('op-tab-history');
  const panel      = document.getElementById('orders-panel');
  const title      = document.getElementById('orders-panel-title');
  if (!panel) return;

  panel.dataset.ordersTab = tab;
  if (tabActive)  tabActive.classList.toggle('active',  tab === 'active');
  if (tabHistory) tabHistory.classList.toggle('active', tab === 'history');
  if (title) title.textContent = tab === 'active' ? 'My Orders' : 'Order History';

  /* Re-render from cache if available */
  if (_ordersCache) _renderOrdersTab(tab, _ordersCache);
}

async function _fetchAndRenderOrders() {
  const content = document.getElementById('orders-panel-content');
  if (content) content.innerHTML = '<div class="orders-panel-loading">Loading your orders…</div>';

  try {
    _ordersCache  = await loadMyOrders();
    _ordersLoaded = true;
    updateOrderBadges(_ordersCache);
    const tab = document.getElementById('orders-panel')?.dataset.ordersTab || 'active';
    _renderOrdersTab(tab, _ordersCache);
  } catch(err) {
    /* Show a readable error inside the panel rather than silent empty state */
    if (content) {
      content.innerHTML = `
        <div style="text-align:center;padding:3rem 1rem;">
          <div style="font-size:2rem;margin-bottom:1rem;opacity:0.4;"></div>
          <div style="font-weight:600;margin-bottom:0.5rem;">Could not load orders</div>
          <div style="font-size:0.82rem;color:var(--text-muted);margin-bottom:1.2rem;line-height:1.6;">
            ${err.message || 'A server error occurred. Please try again.'}
          </div>
          <button onclick="_fetchAndRenderOrders()"
            style="background:var(--accent);color:#fff;border:none;border-radius:8px;
                   padding:0.65rem 1.5rem;font-family:'DM Sans',sans-serif;
                   font-size:0.84rem;font-weight:500;cursor:pointer;">
            Try Again
          </button>
        </div>`;
    }
    console.error('_fetchAndRenderOrders error:', err);
  }
}

function _renderOrdersTab(tab, allOrders) {
  const content = document.getElementById('orders-panel-content');
  if (!content) return;

  const isActive = tab === 'active';
  const statusList = isActive ? ACTIVE_ORDER_STATUSES : FINAL_ORDER_STATUSES;
  const filtered = allOrders.filter(o => statusList.includes(o.orderStatus));

  /* Mark orders in this tab as seen and refresh badges */
  _markTabSeen(allOrders, statusList);
  updateOrderBadges(allOrders);

  if (!filtered.length) {
    content.innerHTML = `
      <div class="orders-empty" style="border:none;background:transparent;padding:3rem 1rem;">
        <div class="orders-empty-icon"></div>
        <div class="orders-empty-title">${isActive ? 'No Active Orders' : 'No Order History'}</div>
        <div class="orders-empty-sub" style="font-size:0.8rem;">
          ${isActive
            ? 'You have no orders currently in progress.'
            : 'Completed, rejected, and cancelled orders will appear here.'}
        </div>
        ${isActive ? `<a href="shop-products.php" class="btn-primary" style="display:inline-block;" onclick="closeMyOrdersPanel()">Browse Products</a>` : ''}
      </div>`;
    return;
  }

  content.innerHTML = filtered.map(o =>
    isActive ? renderOrderCard(o) : renderHistoryCard(o)
  ).join('');
}


async function loadMyOrders() {
  try {
    const res = await shopFetch('api/my-orders.php');

    /* Handle auth failure before parsing body */
    if (res.status === 401) {
      window.location.href = 'shop.php';
      return [];
    }

    const data = await res.json();

    if (data && data.error) {
      /* Surface the server error so it shows in the panel */
      throw new Error(data.error);
    }

    return Array.isArray(data) ? data : [];
  } catch(e) {
    console.error('loadMyOrders failed:', e);
    /* Re-throw so _fetchAndRenderOrders can show the error */
    throw e;
  }
}

/* Active order statuses → shown in "My Orders"
   Includes 'Pending Payment' for backward compatibility with orders
   placed before the reservation flow was introduced. */
/* My Orders — in-progress orders only */
const ACTIVE_ORDER_STATUSES = [
  'Payment Verification',
  'Payment Accepted',
  'Processing',
  'Shipping',
  'Shipped',
  /* legacy */
  'Pending Payment', 'Pending Verification',
];
/* Order History — finished and rejected orders */
const FINAL_ORDER_STATUSES = ['Completed', 'Payment Rejected', 'Cancelled', 'Rejected'];

/* ── User-facing display label for each backend status ────────── */
function _orderDisplayLabel(orderStatus) {
  const map = {
    'Payment Verification': 'Payment Verification',
    'Payment Accepted':     'Payment Accepted',
    'Payment Rejected':     'Payment Rejected',
    'Processing':           'Processing',
    'Shipping':             'Shipping',
    'Shipped':              'Shipped',
    'Completed':            'Completed',
    /* legacy */
    'Pending Payment':      'Payment Verification',
    'Pending Verification': 'Payment Verification',
    'Rejected':             'Payment Rejected',
  };
  return map[orderStatus] || orderStatus;
}

/* Returns a styled status badge using user-facing labels */
function orderStatusBadgeShop(status) {
  const label = _orderDisplayLabel(status);
  const clsMap = {
    'Payment Verification': 'pending',
    'Payment Accepted':     'processing',
    'Payment Rejected':     'rejected',
    'Processing':           'processing',
    'Shipping':             'shipping',
    'Shipped':              'shipped',
    'Completed':            'completed',
  };
  const cls = clsMap[label] || 'completed';
  return `<span class="status-badge ${cls}">${label}</span>`;
}

/* Full 7-step workflow tracker — visible from order creation to completion */
function renderOrderTracker(orderStatus) {
  const isRejected = ['Payment Rejected','Rejected'].includes(orderStatus);

  /* ── Rejection branch: 3-step short track ── */
  if (isRejected) {
    return [
      { label: 'Payment<br>Submitted',    cls: 'done' },
      { label: 'Payment<br>Verification', cls: 'done' },
      { label: 'Payment<br>Rejected',     cls: 'rejected' },
    ].map(s => `
      <div class="tracker-step ${s.cls}">
        <div class="tracker-step-dot"></div>
        <div class="tracker-line"></div>
        <div class="tracker-step-label">${s.label}</div>
      </div>`).join('');
  }

  /* ── Normal 7-step flow ── */
  const steps = [
    'Payment<br>Submitted',
    'Payment<br>Verification',
    'Payment<br>Accepted',
    'Processing',
    'Shipping',
    'Shipped',
    'Completed',
  ];

  /* Map each status to which step index is currently ACTIVE */
  const activeIdx = {
    'Payment Verification': 1,
    'Pending Verification': 1,
    'Pending Payment':      1,
    'Payment Accepted':     2,
    'Processing':           3,
    'Shipping':             4,
    'Shipped':              5,
    'Completed':            6,
  };

  const current = activeIdx[orderStatus] ?? 0;

  return steps.map((label, i) => {
    let cls = '';
    if (i < current)        cls = 'done';
    else if (i === current) cls = 'active';
    return `
      <div class="tracker-step ${cls}">
        <div class="tracker-step-dot"></div>
        <div class="tracker-line"></div>
        <div class="tracker-step-label">${label}</div>
      </div>`;
  }).join('');
}

/* Renders a full order card for "My Orders" */
function renderOrderCard(o) {
  const date               = (o.dateOrdered || '').split(' ')[0];
  /* Check both order status and payment status to determine display state.
     payment.status = 'Approved' is the canonical signal that fulfillment started. */
  const isRejectedInActive = ['Payment Rejected','Rejected'].includes(o.orderStatus)
                          || o.payment?.status === 'Rejected';
  const isPendingPayment   = !isRejectedInActive
                          && (o.payment?.status === 'Pending Verification'
                              || ['Payment Verification','Pending Payment','Pending Verification'].includes(o.orderStatus));
  const isAccepted         = !isRejectedInActive && !isPendingPayment
                          && (o.payment?.status === 'Approved'
                              || ['Payment Accepted','Processing','Shipping','Shipped'].includes(o.orderStatus));
  const displayLabel = isRejectedInActive ? 'Payment Rejected' : _orderDisplayLabel(o.orderStatus);

  const itemsHtml = (o.products || []).map(p => `
    <div class="order-item">
      ${p.image
        ? `<img class="order-item-img" src="${escHtml(p.image)}" alt="${escHtml(p.name)}" loading="lazy">`
        : `<div class="order-item-img-placeholder"></div>`}
      <div style="flex:1;min-width:0;">
        <div class="order-item-name">${escHtml(p.name)}</div>
        <div class="order-item-meta">Size: ${escHtml(p.size || '—')}</div>
      </div>
      <div class="order-item-price">₱${Number(p.price).toLocaleString()}</div>
    </div>`).join('');

  /* ── Status banner ── */
  let bannerHtml = '';
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
      'Payment Accepted': 'Payment verified! We will start packing your items soon.',
      'Processing':       'We are packing your items and preparing them for shipment.',
      'Shipping':         'Your package has been handed to the courier and is on its way.',
      'Shipped':          'Your package is out for delivery. Please watch for it!',
    };
    const acceptedMsg = acceptedMsgs[o.orderStatus] || 'Payment accepted! Your order is now being processed.';
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
      : '';
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
  const adminNoteHtml = o.adminNotes ? `
    <div class="order-seller-note">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0;color:var(--accent);"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      <div>
        <div style="font-size:0.68rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:var(--accent);margin-bottom:0.2rem;">Message from Seller</div>
        <div style="font-size:0.82rem;color:var(--text);line-height:1.55;">${escHtml(o.adminNotes)}</div>
      </div>
    </div>` : '';

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
          <span class="status-badge ${isPendingPayment ? 'pending' : isAccepted ? 'processing' : isRejectedInActive ? 'rejected' : 'cancelled'}">${displayLabel}</span>
        </div>
      </div>
      ${bannerHtml ? `<div style="padding:0.9rem 1.4rem 0;">${bannerHtml}</div>` : ''}
      ${adminNoteHtml ? `<div style="padding:0.75rem 1.4rem 0;">${adminNoteHtml}</div>` : ''}
      <div class="order-items">${itemsHtml}</div>
      ${trackerHtml}
      <div class="order-card-footer">
        <div>
          ${o.shippingFee > 0 ? `
          <div style="font-size:0.72rem;color:var(--text-muted);margin-bottom:0.15rem;">
            Items: ₱${(Number(o.totalAmount) - o.shippingFee).toLocaleString()}
            &nbsp;+&nbsp; Shipping: ₱${Number(o.shippingFee).toLocaleString()}
          </div>` : ''}
          <div class="order-total-label">Total Paid</div>
          <div class="order-total-amount">₱${Number(o.totalAmount).toLocaleString()}</div>
        </div>
        <div style="text-align:right;display:flex;flex-direction:column;gap:0.25rem;align-items:flex-end;">
          ${o.payment?.status ? `
            <div style="font-size:0.72rem;color:var(--text-muted);">
              Payment Status:
              <strong style="color:${o.payment.status === 'Rejected' ? 'var(--danger,#e53e3e)' : ['Approved','Verified'].includes(o.payment.status) ? 'var(--success,#38a169)' : 'inherit'}">
                ${escHtml(o.payment.status)}
              </strong>
            </div>` : ''}
          ${o.payment?.referenceNumber ? `<div style="font-size:0.72rem;color:var(--text-muted);">Ref: <strong>${escHtml(o.payment.referenceNumber)}</strong></div>` : ''}
          ${o.receipt ? `
            <a class="btn-view-receipt" href="shop-receipt.php?order_id=${o.id}&from=orders" title="View Receipt">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;vertical-align:-2px;margin-right:4px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
              View Receipt
            </a>` : ''}
        </div>
      </div>
    </div>`;
}

/* Renders a compact card for "Order History" (final orders) */
function renderHistoryCard(o) {
  const date        = (o.dateOrdered || '').split(' ')[0];
  const isCompleted = o.orderStatus === 'Completed';

  /* Each product row — completed orders show a "Write a Review" button per item */
  const itemsHtml = (o.products || []).map(p => {
    const imgSrc = escHtml(p.image || '');
    const imgHtml = imgSrc
      ? `<img class="order-item-img" src="${imgSrc}" alt="${escHtml(p.name)}" loading="lazy">`
      : `<div class="order-item-img-placeholder"></div>`;

    /* Safe values for onclick — avoid single-quote conflicts */
    const nameAttr = (p.name || '').replace(/'/g, "&#39;").replace(/"/g, '&quot;');
    const imgAttr  = imgSrc.replace(/'/g, "&#39;");

    const reviewBtn = isCompleted ? `
      <button class="btn-write-review"
        onclick="openReviewModal(${p.product_id}, '${nameAttr}', '${imgAttr}', ${o.id})"
        title="Review ${escHtml(p.name)}">
        ★ Write a Review
      </button>` : '';

    return `
      <div class="order-item">
        ${imgHtml}
        <div style="flex:1;min-width:0;">
          <div class="order-item-name">${escHtml(p.name)}</div>
          <div class="order-item-meta">Size: ${escHtml(p.size || '—')}</div>
          ${reviewBtn}
        </div>
        <div class="order-item-price">₱${Number(p.price).toLocaleString()}</div>
      </div>`;
  }).join('');

  const isPaymentRejected = ['Payment Rejected','Rejected'].includes(o.orderStatus);
  const displayLabel      = _orderDisplayLabel(o.orderStatus);

  /* ── Banner for each final state ── */
  let bannerHtml = '';
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
      : '';
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

  const badgeCls = isCompleted ? 'completed' : isPaymentRejected ? 'rejected' : 'completed';

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
          ${o.payment?.referenceNumber
            ? `<div style="font-size:0.75rem;color:var(--text-muted);">Ref: <strong>${escHtml(o.payment.referenceNumber)}</strong></div>`
            : ''}
          ${isPaymentRejected ? `
            <a class="btn-view-receipt" href="shop-order-submission.php?order_id=${o.id}&from=history" title="View Submission">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;vertical-align:-2px;margin-right:4px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
              View Submission
            </a>` : o.receipt ? `
            <a class="btn-view-receipt" href="shop-receipt.php?order_id=${o.id}&from=history" title="View Receipt">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;vertical-align:-2px;margin-right:4px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
              View Receipt
            </a>` : ''}
        </div>
      </div>
    </div>`;
}

/* ════════════════════════════════════════════════════════════════
   PRODUCT REVIEW MODAL
   Opens from "Write a Review" button in Order History.
   Pre-selects the specific product for the review.
   PHP integration point: POST api/reviews.php
════════════════════════════════════════════════════════════════ */
let _revProductId   = null;
let _revProductName = '';
let _revOrderId     = null;
let _revRating      = 0;

function openReviewModal(productId, productName, productImage, orderId) {
  if (!currentUser) { openAuth(); showToast('Please log in to write a review.'); return; }

  _revProductId   = productId;
  _revProductName = productName;
  _revOrderId     = orderId;
  _revRating      = 0;

  /* Populate the product chip */
  const nameEl  = document.getElementById('review-chip-name');
  const imgEl   = document.getElementById('review-chip-img');
  if (nameEl) nameEl.textContent = productName;
  if (imgEl)  { imgEl.src = productImage || ''; imgEl.style.display = productImage ? '' : 'none'; }

  /* Reset form */
  const bodyEl = document.getElementById('review-modal-body');
  if (bodyEl) bodyEl.value = '';
  _setReviewStars(0);
  _revShowError('');

  document.getElementById('review-modal-overlay').classList.add('open');
  setTimeout(() => document.getElementById('review-modal-body')?.focus(), 120);
}

function closeReviewModal() {
  document.getElementById('review-modal-overlay').classList.remove('open');
  const inp = document.getElementById('review-modal-images');
  if (inp) inp.value = '';
  const prev = document.getElementById('review-modal-img-previews');
  if (prev) prev.innerHTML = '';
}

function previewModalReviewImages(input) {
  const wrap = document.getElementById('review-modal-img-previews');
  if (!wrap) return;
  wrap.innerHTML = '';
  [...input.files].slice(0, 5).forEach(file => {
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.createElement('img');
      img.src = e.target.result;
      img.className = 'rv-img-thumb';
      img.alt = 'Preview';
      wrap.appendChild(img);
    };
    reader.readAsDataURL(file);
  });
}

function closeReviewModalOutside(e) {
  if (e.target === document.getElementById('review-modal-overlay')) closeReviewModal();
}

function setReviewRating(val) {
  _revRating = val;
  _setReviewStars(val);
}

function _setReviewStars(val) {
  document.querySelectorAll('#review-modal-stars .review-modal-star')
    .forEach((btn, i) => btn.classList.toggle('lit', i < val));
}

function _revShowError(msg) {
  const el = document.getElementById('review-modal-err');
  if (!el) return;
  el.textContent = msg;
  el.classList.toggle('show', !!msg);
}

async function submitProductReview() {
  _revShowError('');

  if (!_revRating) { _revShowError('Please select a star rating.'); return; }
  const body = document.getElementById('review-modal-body')?.value.trim();
  if (!body)  { _revShowError('Please write your review.'); return; }

  const btn = document.getElementById('review-submit-btn');
  btn.disabled = true; btn.textContent = 'Posting…';

  try {
    const imageInput = document.getElementById('review-modal-images');
    const fd = new FormData();
    fd.append('product_id', _revProductId || '');
    fd.append('rating', _revRating);
    fd.append('body', body);
    if (imageInput && imageInput.files.length) {
      [...imageInput.files].slice(0, 5).forEach(f => fd.append('images[]', f));
    }

    const res  = await shopFetch('api/reviews.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.error) { _revShowError(data.error); return; }

    closeReviewModal();
    showToast('Review posted! Thank you.');
    if (document.getElementById('reviews-list')) loadReviews(renderReviews);

  } catch(err) {
    _revShowError('Network error. Please try again.');
    console.error('submitProductReview:', err);
  } finally {
    btn.disabled = false; btn.textContent = 'Post Review';
  }
}

function openReviewPhoto(src) {
  let ov = document.getElementById('rv-photo-overlay');
  if (!ov) {
    ov = document.createElement('div');
    ov.id = 'rv-photo-overlay';
    ov.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.88);z-index:1200;display:flex;align-items:center;justify-content:center;cursor:zoom-out;';
    ov.addEventListener('click', () => ov.remove());
    document.body.appendChild(ov);
  }
  ov.innerHTML = `<img src="${escHtml(src)}" style="max-width:92vw;max-height:90vh;border-radius:8px;object-fit:contain;" alt="Review photo">`;
  ov.style.display = 'flex';
}

/* Background order polling — fetches every 15 s, updates badges and
   re-renders the panel live if it is currently open. */
function _pollOrders() {
  if (!currentUser) return;
  loadMyOrders().then(orders => {
    _ordersCache = orders;
    updateOrderBadges(orders);

    /* If the panel is open, re-render the active tab silently */
    const panel = document.getElementById('orders-panel');
    if (panel && panel.classList.contains('open')) {
      const tab = panel.dataset.ordersTab || 'active';
      _renderOrdersTab(tab, orders);
    }
  }).catch(() => {});
}

let _orderPollInterval = null;

function _startOrderPolling() {
  if (!currentUser || _orderPollInterval) return;
  _pollOrders();
  _orderPollInterval = setInterval(_pollOrders, 15000);
}

function _stopOrderPolling() {
  if (_orderPollInterval) { clearInterval(_orderPollInterval); _orderPollInterval = null; }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', _startOrderPolling);
} else {
  setTimeout(_startOrderPolling, 0);
}

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function getCoverImage(p) {
  if (!p.images || !p.images.length) return p.image || '';
  const idx = (typeof p.coverIndex === 'number' && p.coverIndex < p.images.length) ? p.coverIndex
            : (typeof p.cover_index === 'number' && p.cover_index < p.images.length) ? p.cover_index : 0;
  return p.images[idx] || p.images[0] || '';
}

function showToast(msg) {
  const t = document.getElementById('toast');
  if (!t) return;
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.classList.remove('show'), 2800);
}

/* ── KEYBOARD SHORTCUTS ──────────────────────────────────────────── */
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    const reviewModal  = document.getElementById('review-modal-overlay');
    if (reviewModal  && reviewModal.classList.contains('open'))  { closeReviewModal();      return; }
    const productModal = document.getElementById('modal-overlay');
    if (productModal && productModal.classList.contains('open')) { closeModal();             return; }
    const profileModal = document.getElementById('profile-modal-overlay');
    if (profileModal && profileModal.classList.contains('open')) { closeEditProfile();       return; }
    const ordersPanel  = document.getElementById('orders-panel');
    if (ordersPanel  && ordersPanel.classList.contains('open'))  { closeMyOrdersPanel();    return; }
    _closeUserDropdown();
    closeCart();
    closeAuth();
  }
  const productModal = document.getElementById('modal-overlay');
  if (productModal && productModal.classList.contains('open')) {
    if (e.key === 'ArrowLeft')  galleryPrev(e);
    if (e.key === 'ArrowRight') galleryNext(e);
  }
});

/* viewReceipt and viewSubmission now navigate to dedicated pages */
function viewReceipt(orderId, from) {
  window.location.href = 'shop-receipt.php?order_id=' + orderId + '&from=' + (from || 'orders');
}

/* ── ADMIN PREVIEW BAR ───────────────────────────────────────────── */
function checkAdminPreviewMode() {
  /* Set the flag when navigating from the admin panel */
  if (new URLSearchParams(window.location.search).get('from') === 'admin') {
    sessionStorage.setItem('bythebel_admin_preview', '1');
  }

  /* Also treat any logged-in admin as being in preview mode — the bar
     should be visible whenever an admin account is active on the shop,
     regardless of how they got here. */
  try {
    const sess = JSON.parse(localStorage.getItem('bythebel_session') || 'null');
    if (sess && sess.role === 'admin') {
      sessionStorage.setItem('bythebel_admin_preview', '1');
    }
  } catch(e) {}

  if (sessionStorage.getItem('bythebel_admin_preview') === '1') {
    const bar = document.getElementById('admin-bar');
    if (bar) { bar.classList.add('visible'); document.body.classList.add('admin-preview-mode'); }
  }
}



