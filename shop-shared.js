/* ════════════════════════════════════════════════════════════════
   shop-shared.js — Shared state and functions for all shop pages.

   Session token stored in localStorage as carousell_session JSON:
     { user_id, first_name, last_name, email, role, token, phone, address }
   Cart stored separately in carousell_cart.
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
  home:          'shop.html',
  shop:          'shop-products.html',
  reviews:       'shop-reviews.html',
  about:         'shop-about.html',
  contacts:      'shop-contacts.html',
  payment:       'shop-payment.html',
  'my-orders':   'shop-my-orders.html',
  'order-history': 'shop-order-history.html',
};

function goPage(id) {
  const url = PAGE_URLS[id];
  if (url) window.location.href = url;
}

/* ════════════════════════════════════════════════════════════════
   CART PERSISTENCE
════════════════════════════════════════════════════════════════ */
function saveCart() {
  localStorage.setItem('carousell_cart', JSON.stringify(cart));
}
function loadCart() {
  try {
    const stored = localStorage.getItem('carousell_cart');
    if (stored) cart = JSON.parse(stored);
  } catch(e) { cart = []; }
}

/* ════════════════════════════════════════════════════════════════
   AUTH TOKEN HELPERS
════════════════════════════════════════════════════════════════ */
function getShopToken() {
  try {
    const sess = localStorage.getItem('carousell_session');
    if (!sess) return '';
    const obj = JSON.parse(sess);
    return obj.token || '';
  } catch(e) { return ''; }
}

async function shopFetch(url, options = {}) {
  const token = getShopToken();
  const headers = { ...(options.headers || {}) };
  if (token) headers['Authorization'] = 'Bearer ' + token;
  if (!(options.body instanceof FormData)) {
    headers['Content-Type'] = 'application/json';
  }
  return fetch(url, { ...options, headers });
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
  const cond = (p.condition || '').toLowerCase().replace(/\s+/g, '-');
  condBadge.textContent = p.condition || '—';
  condBadge.className   = 'condition-badge' + (cond ? ` ${cond}` : '');

  document.getElementById('modal-category-tag').textContent = p.category || '—';

  const inCart      = cart.some(c => c.product.id == p.id);
  const isUnavailable = p.status === 'sold' || p.status === 'reserved';
  const cartBtn     = document.getElementById('modal-cart-btn');
  if (p.status === 'sold')     { cartBtn.textContent = 'Sold Out';  cartBtn.disabled = true;  cartBtn.style.opacity = '0.4'; }
  else if (p.status === 'reserved') { cartBtn.textContent = 'Reserved'; cartBtn.disabled = true;  cartBtn.style.opacity = '0.4'; }
  else if (inCart)             { cartBtn.textContent = '✓ In Cart'; cartBtn.disabled = true;  cartBtn.style.opacity = '0.5'; }
  else                         { cartBtn.textContent = 'Add to Cart'; cartBtn.disabled = false; cartBtn.style.opacity = ''; }

  document.getElementById('modal-overlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function renderGallery() {
  const mainImg = document.getElementById('modal-img-main');
  mainImg.src   = currentImages[currentImgIdx] || '';
  mainImg.alt   = currentProduct ? currentProduct.name : '';
  const strip   = document.getElementById('modal-thumbs-strip');
  strip.innerHTML = '';
  if (currentImages.length <= 1) return;
  currentImages.forEach((src, i) => {
    const t = document.createElement('div');
    t.className = 'thumb-img' + (i === currentImgIdx ? ' active' : '');
    t.innerHTML = `<img src="${src}" alt="view ${i+1}" loading="lazy">`;
    t.onclick   = () => { currentImgIdx = i; renderGallery(); };
    strip.appendChild(t);
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

  /* Review form visibility */
  const wall = document.getElementById('review-login-wall');
  const form = document.getElementById('review-form-card');
  if (wall) wall.style.display = user ? 'none' : '';
  if (form) form.style.display = user ? ''     : 'none';

  /* Contact page — show form or login wall */
  const contactWall = document.getElementById('contact-login-wall');
  const contactForm = document.getElementById('contact-form-wrap');
  const senderName  = document.getElementById('contact-sender-name');
  if (contactWall) contactWall.style.display = user ? 'none'  : 'block';
  if (contactForm) contactForm.style.display = user ? 'block' : 'none';
  if (senderName && user) senderName.textContent = `${user.first_name} ${user.last_name}`;
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
      localStorage.setItem('carousell_session', JSON.stringify(merged));
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
    localStorage.setItem('carousell_session', JSON.stringify(updated));
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
      localStorage.setItem('carousell_session', JSON.stringify(data.user));
      /* Admin accounts go straight to the admin dashboard */
      if (data.user.role === 'admin') {
        window.location.href = 'admin.html';
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

async function doRegister() {
  const fname = document.getElementById('reg-fname').value.trim();
  const lname = document.getElementById('reg-lname').value.trim();
  const email = document.getElementById('reg-email').value.trim();
  const phone = document.getElementById('reg-phone').value.trim();
  const pass  = document.getElementById('reg-pass').value;
  if (!fname || !lname) { showAuthError('reg-error', 'Please enter your first and last name.'); return; }
  if (!email)           { showAuthError('reg-error', 'Please enter a valid email.'); return; }
  if (pass.length < 8)  { showAuthError('reg-error', 'Password must be at least 8 characters.'); return; }

  try {
    const res  = await fetch('api/auth.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ action: 'register', first_name: fname, last_name: lname, email, phone, password: pass }),
    });
    const data = await res.json();
    if (data.error) { showAuthError('reg-error', data.error); return; }
    if (data.success && data.user) {
      localStorage.setItem('carousell_session', JSON.stringify(data.user));
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
  localStorage.removeItem('carousell_session');
  cart = [];
  saveCart();
  /* Return to shop home — user icon becomes the login trigger */
  window.location.href = 'shop.html';
}

function restoreSession() {
  const stored = localStorage.getItem('carousell_session');
  if (stored) {
    try {
      const user = JSON.parse(stored);
      if (user && user.token) setCurrentUser(user);
      else localStorage.removeItem('carousell_session');
    } catch(e) { localStorage.removeItem('carousell_session'); }
  }
}

/* ════════════════════════════════════════════════════════════════
   REVIEWS
════════════════════════════════════════════════════════════════ */
function setRating(val) {
  currentRating = val;
  document.querySelectorAll('.star-btn').forEach((btn,i) => btn.classList.toggle('lit', i<val));
}

async function submitReview() {
  if (!currentUser) { showToast('Please log in to post a review.'); openAuth(); return; }
  const product_id = document.getElementById('rv-product').value;
  const body       = document.getElementById('rv-body').value.trim();
  if (!currentRating) { showToast('Please select a star rating.'); return; }
  if (!body)          { showToast('Please write your review.'); return; }

  try {
    const res  = await shopFetch('api/reviews.php', {
      method: 'POST',
      body:   JSON.stringify({ product_id: product_id || null, rating: currentRating, body }),
    });
    const data = await res.json();
    if (data.error) { showToast(data.error); return; }
    if (data.success && data.review) {
      reviews.unshift(data.review);
      renderReviews();
      document.getElementById('rv-product').value = '';
      document.getElementById('rv-body').value    = '';
      setRating(0);
      showToast('Review posted! Thank you!');
    }
  } catch(e) {
    showToast('Failed to post review. Please try again.');
    console.error('submitReview error:', e);
  }
}

function renderReviews() {
  const listEl = document.getElementById('reviews-list');
  if (!listEl) return;
  if (!reviews.length) { listEl.innerHTML='<div class="reviews-empty">No reviews yet. Be the first!</div>'; updateRatingSummary(); return; }
  listEl.innerHTML = reviews.map(r => `
    <div class="review-card">
      <div class="review-header">
        <span class="review-name">${escHtml(r.name)}</span>
        <span class="review-date">${r.date}</span>
      </div>
      <div class="review-stars">${'★'.repeat(r.rating)}${'☆'.repeat(5-r.rating)}</div>
      ${r.product ? `<div class="review-product-ref">re: ${escHtml(r.product)}</div>` : ''}
      <div class="review-body">${escHtml(r.body)}</div>
    </div>`).join('');
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

  /* PHP integration point: POST api/contact.php
     { user_id: currentUser.user_id, name, email, subject, message } */
  const btn = document.getElementById('contact-send-btn');
  if (btn) { btn.disabled = true; btn.textContent = 'Sending…'; }

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
  linesEl.innerHTML = cart.map(({product:p}) =>
    `<div class="order-line"><span>${p.name} (Size ${p.size||'—'})</span><span>₱${p.price.toLocaleString()}</span></div>`
  ).join('');
  const totEl = document.getElementById('order-total-display');
  if (totEl) totEl.textContent = '₱' + cartTotal().toLocaleString();
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

async function confirmPayment() {
  const name    = document.getElementById('pf-name').value.trim();
  const phone   = document.getElementById('pf-phone').value.trim();
  const email   = document.getElementById('pf-email').value.trim();
  const ref     = document.getElementById('pf-ref').value.trim();
  const address = document.getElementById('pf-address').value.trim();
  const proofInput = document.getElementById('proof-file');
  const proof   = proofInput ? proofInput.files[0] : null;

  if (!name)    { showToast('Please enter your full name.'); return; }
  if (!phone)   { showToast('Please enter your contact number.'); return; }
  if (!ref)     { showToast('Please enter the GCash reference number.'); return; }
  if (!address) { showToast('Please enter your delivery address.'); return; }
  if (!proof)   { showToast('Please upload your GCash payment screenshot.'); return; }
  if (!currentUser) { showToast('Please log in first.'); openAuth(); return; }

  /* Build FormData */
  const formData = new FormData();
  formData.append('items',            JSON.stringify(cart.map(({product:p}) => ({ product_id: p.id, price: p.price }))));
  formData.append('total_amount',     cartTotal());
  formData.append('payment_method',   'GCash');
  formData.append('reference_number', ref);
  formData.append('address',          address);
  formData.append('phone',            phone);
  formData.append('proof_image',      proof);

  const submitBtn = document.getElementById('btn-submit-payment');
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
  ['pf-name','pf-phone','pf-email','pf-ref','pf-address'].forEach(id => {
    const el = document.getElementById(id); if(el) el.value='';
  });
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

  /* Remove active state from all nav links while panel is open */
  document.querySelectorAll('.nav-links a').forEach(a => a.classList.remove('active'));

  switchOrdersTab(tab);
  _fetchAndRenderOrders();
}

function closeMyOrdersPanel() {
  const panel = document.getElementById('orders-panel');
  if (!panel) return;
  panel.classList.remove('open');

  /* Restore the active nav link for the current page */
  const path  = window.location.pathname.split('/').pop();
  const pages = {
    'shop.html':               'nav-home',
    'shop-products.html':      'nav-shop',
    'shop-reviews.html':       'nav-reviews',
    'shop-about.html':         'nav-about',
    'shop-contacts.html':      'nav-contacts',
  };
  const navId = pages[path];
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
    const tab = document.getElementById('orders-panel')?.dataset.ordersTab || 'active';
    _renderOrdersTab(tab, _ordersCache);
  } catch(err) {
    /* Show a readable error inside the panel rather than silent empty state */
    if (content) {
      content.innerHTML = `
        <div style="text-align:center;padding:3rem 1rem;">
          <div style="font-size:2rem;margin-bottom:1rem;opacity:0.4;">⚠️</div>
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
  const filtered = allOrders.filter(o =>
    isActive
      ? ACTIVE_ORDER_STATUSES.includes(o.orderStatus)
      : FINAL_ORDER_STATUSES.includes(o.orderStatus)
  );

  if (!filtered.length) {
    content.innerHTML = `
      <div class="orders-empty" style="border:none;background:transparent;padding:3rem 1rem;">
        <div class="orders-empty-icon">${isActive ? '📦' : '🕐'}</div>
        <div class="orders-empty-title">${isActive ? 'No Active Orders' : 'No Order History'}</div>
        <div class="orders-empty-sub" style="font-size:0.8rem;">
          ${isActive
            ? 'You have no orders currently in progress.'
            : 'Completed and past orders will appear here.'}
        </div>
        ${isActive ? `<a href="shop-products.html" class="btn-primary" style="display:inline-block;" onclick="closeMyOrdersPanel()">Browse Products</a>` : ''}
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
      window.location.href = 'shop.html';
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
const ACTIVE_ORDER_STATUSES = [
  'Pending Payment', 'Pending Verification', 'Processing', 'Shipping', 'Shipped'
];
/* Final order statuses → shown in "Order History" */
const FINAL_ORDER_STATUSES = ['Completed', 'Rejected', 'Cancelled'];

/* Returns a styled status badge HTML string */
function orderStatusBadgeShop(status) {
  const map = {
    'Pending Verification': 'pending',
    'Processing':           'processing',
    'Shipping':             'shipping',
    'Shipped':              'shipped',
    'Completed':            'completed',
    'Rejected':             'rejected',
    'Cancelled':            'cancelled',
  };
  const cls = map[status] || 'cancelled';
  return `<span class="status-badge ${cls}">${status}</span>`;
}

/* Renders the step-by-step progress tracker for an order */
function renderOrderTracker(orderStatus) {
  const steps = [
    { key: 'Pending Verification', label: 'Payment\nVerification' },
    { key: 'Processing',           label: 'Processing' },
    { key: 'Shipping',             label: 'Shipping' },
    { key: 'Shipped',              label: 'Shipped' },
    { key: 'Completed',            label: 'Completed' },
  ];
  const idx = steps.findIndex(s => s.key === orderStatus);

  return steps.map((step, i) => {
    let cls = '';
    if (i < idx)       cls = 'done';
    else if (i === idx) cls = 'active';
    const label = step.label.replace('\n', '<br>');
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
  const date       = (o.dateOrdered || '').split(' ')[0];
  const isPending  = o.orderStatus === 'Pending Verification';
  const isRejected = ['Rejected','Cancelled'].includes(o.orderStatus);
  const isActive   = ACTIVE_ORDER_STATUSES.includes(o.orderStatus);

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

  let bannerHtml = '';
  if (isPending) {
    bannerHtml = `
      <div class="order-pending-banner" style="margin-bottom:0.9rem;">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        <span>Your payment is awaiting admin verification. The product is reserved for you while we review your GCash screenshot and reference number.</span>
      </div>`;
  } else if (isRejected) {
    const msg = o.orderStatus === 'Cancelled'
      ? 'This reservation was automatically cancelled because the review period expired. The product has been released.'
      : 'Your payment was rejected by our team. The product has been released. Please check your GCash details and place a new order if needed.';
    bannerHtml = `
      <div class="order-rejected-banner" style="margin-bottom:0.9rem;">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        <span>${msg}</span>
      </div>`;
  }

  const trackerHtml = isActive && !isPending ? `
    <div class="order-tracker">
      <div class="tracker-label">Order Progress</div>
      <div class="tracker-steps">${renderOrderTracker(o.orderStatus)}</div>
    </div>` : '';

  return `
    <div class="order-card">
      <div class="order-card-header">
        <div>
          <div class="order-card-id">Order #${o.id}</div>
          <div class="order-card-date">Placed ${date}</div>
        </div>
        <div class="order-card-header-right">
          ${orderStatusBadgeShop(o.payment.status)}
          ${orderStatusBadgeShop(o.orderStatus)}
        </div>
      </div>
      ${bannerHtml ? `<div style="padding:0.9rem 1.4rem 0;">${bannerHtml}</div>` : ''}
      <div class="order-items">${itemsHtml}</div>
      ${trackerHtml}
      <div class="order-card-footer">
        <div>
          <div class="order-total-label">Total Paid</div>
          <div class="order-total-amount">₱${Number(o.totalAmount).toLocaleString()}</div>
        </div>
        ${o.payment.referenceNumber ? `<div style="font-size:0.75rem;color:var(--text-muted);">Ref: <strong>${escHtml(o.payment.referenceNumber)}</strong></div>` : ''}
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

  const completedBanner = isCompleted ? `
    <div style="padding:0.9rem 1.4rem 0;">
      <div class="order-completed-banner">
        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        Order completed — tap <strong>★ Write a Review</strong> on any item to share your experience.
      </div>
    </div>` : '';

  return `
    <div class="order-card">
      <div class="order-card-header">
        <div>
          <div class="order-card-id">Order #${o.id}</div>
          <div class="order-card-date">Placed ${date}</div>
        </div>
        <div class="order-card-header-right">
          ${orderStatusBadgeShop(o.orderStatus)}
        </div>
      </div>
      ${completedBanner}
      <div class="order-items">${itemsHtml}</div>
      <div class="order-card-footer">
        <div>
          <div class="order-total-label">Total</div>
          <div class="order-total-amount">₱${Number(o.totalAmount).toLocaleString()}</div>
        </div>
        ${o.payment.referenceNumber
          ? `<div style="font-size:0.75rem;color:var(--text-muted);">Ref: <strong>${escHtml(o.payment.referenceNumber)}</strong></div>`
          : ''}
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

  if (!_revRating)                              { _revShowError('Please select a star rating.'); return; }

  const body = document.getElementById('review-modal-body')?.value.trim();
  if (!body)                                    { _revShowError('Please write your review.'); return; }

  const btn = document.getElementById('review-submit-btn');
  btn.disabled = true; btn.textContent = 'Posting…';

  try {
    /* PHP integration point: POST api/reviews.php */
    const res  = await shopFetch('api/reviews.php', {
      method: 'POST',
      body:   JSON.stringify({
        product_id: _revProductId,
        rating:     _revRating,
        body,
      }),
    });
    const data = await res.json();

    if (data.error) { _revShowError(data.error); return; }

    closeReviewModal();
    showToast('Review posted! Thank you ✨');

  } catch(err) {
    _revShowError('Network error. Please try again.');
    console.error('submitProductReview:', err);
  } finally {
    btn.disabled = false; btn.textContent = 'Post Review';
  }
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

/* ── ADMIN PREVIEW BAR ───────────────────────────────────────────── */
function checkAdminPreviewMode() {
  if (new URLSearchParams(window.location.search).get('from') === 'admin') {
    sessionStorage.setItem('carousell_admin_preview', '1');
  }
  if (sessionStorage.getItem('carousell_admin_preview') === '1') {
    const bar = document.getElementById('admin-bar');
    if (bar) { bar.classList.add('visible'); document.body.classList.add('admin-preview-mode'); }
  }
}
