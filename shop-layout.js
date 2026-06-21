/* ════════════════════════════════════════════════════════════════
   shop-layout.js — Injects the shared nav, overlays, and footer
   into every shop page.

   Call: initShopLayout('pageId')
   pageId values: 'home' | 'shop' | 'reviews' | 'about' | 'contacts' | 'payment'
════════════════════════════════════════════════════════════════ */

function initShopLayout(activePageId) {
  _injectAdminBar();
  _injectNav(activePageId);
  _injectProductModal();
  _injectImgLightbox();
  _injectCartDrawer();
  _injectAuthModal();
  _injectEditProfileModal();
  _injectOrdersPanel();
  _injectReviewModal();
  _injectToast();

  // Init shared state
  loadCart();
  restoreSession();
  updateCartBadge();

  /* Nothing needed here — My Orders opens directly from the payment page */

  // Close dropdown when clicking anywhere outside it
  document.addEventListener('click', function(e) {
    const pill     = document.getElementById('nav-auth-user');
    const dropdown = document.getElementById('user-dropdown');
    if (!pill || !dropdown) return;
    if (!pill.contains(e.target)) {
      dropdown.classList.remove('open');
      pill.classList.remove('open');
    }
  });
}

/* ── ADMIN BAR ─────────────────────────────────────────────────── */
function _injectAdminBar() {
  const bar = document.createElement('div');
  bar.className = 'admin-bar';
  bar.id = 'admin-bar';
  bar.innerHTML = `
    <div class="admin-bar-left">
      <div class="admin-bar-dot"></div>
      Admin Preview Mode — you are viewing the shop as customers see it
    </div>
    <button class="admin-bar-back" onclick="sessionStorage.removeItem('carousell_admin_preview'); window.location.href='admin.php'">
      ← Back to Admin
    </button>`;
  document.body.prepend(bar);
  checkAdminPreviewMode();
}

/* ── NAV ───────────────────────────────────────────────────────── */
function _injectNav(activePageId) {
  const pages = [
    { id:'home',     label:'Home',     href:'shop.php' },
    { id:'shop',     label:'Shop',     href:'shop-products.php' },
    { id:'reviews',  label:'Reviews',  href:'shop-reviews.php' },
    { id:'about',    label:'About',    href:'shop-about.php' },
    { id:'contacts', label:'Contacts', href:'shop-contacts.php' },
    { id:'faq',      label:'FAQ',      href:'shop-faq.php' },
  ];

  const links = pages.map(p =>
    `<li class="nav-item">
       <a id="nav-${p.id}" class="nav-link${p.id===activePageId?' active':''}" href="${p.href}">${p.label}</a>
     </li>`
  ).join('');

  const nav = document.createElement('nav');
  nav.id = 'main-nav';
  nav.className = 'navbar navbar-expand-lg fixed-top';
  nav.innerHTML = `
    <div class="container-fluid px-4">
      <a class="navbar-brand" href="shop.php">Carousell</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navMain">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">${links}</ul>
        <div class="d-flex align-items-center gap-3 py-2 py-lg-0">

          <button class="theme-toggle" id="theme-toggle-btn" onclick="toggleTheme()" title="Toggle dark mode">
            <svg id="theme-icon-moon" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            <svg id="theme-icon-sun"  viewBox="0 0 24 24" style="display:none;"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
          </button>

          <div class="cart-wrap" onclick="openCart()">
            <svg viewBox="0 0 24 24" style="width:20px;height:20px;stroke:#aaa;fill:none;stroke-width:1.8;"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
            <span class="cart-badge" id="cart-count">0</span>
          </div>

          <div id="nav-auth-guest" onclick="openAuth()" title="Log in" style="cursor:pointer;">
            <svg viewBox="0 0 24 24" style="width:20px;height:20px;stroke:#aaa;fill:none;stroke-width:1.8;">
              <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
          </div>

          <div id="nav-auth-user" class="nav-user-pill" style="display:none;" onclick="toggleUserDropdown(event)">
            <svg class="u-avatar" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span class="u-name" id="nav-username"></span>
            <svg class="u-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
            <div class="nav-user-dropdown" id="user-dropdown">
              <div class="dropdown-user-info">
                <div class="dropdown-user-name"  id="dropdown-fullname">—</div>
                <div class="dropdown-user-email" id="dropdown-email">—</div>
              </div>
              <button onclick="openEditProfile(event)">
                <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Edit Profile
              </button>
              <div class="dropdown-divider"></div>
              <button onclick="openMyOrders(event)">
                <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>My Orders
              </button>
              <button onclick="openOrderHistory(event)">
                <svg viewBox="0 0 24 24"><polyline points="12 8 12 12 14 14"/><path d="M3.05 11a9 9 0 1 0 .5-4"/><polyline points="3 3 3 9 9 9"/></svg>Order History
              </button>
              <div class="dropdown-divider"></div>
              <button class="danger" onclick="logOut(event)">
                <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Log out
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>`;
  document.body.prepend(nav);
}

/* ── PRODUCT MODAL ─────────────────────────────────────────────── */
function _injectProductModal() {
  document.body.insertAdjacentHTML('beforeend', `
    <div class="modal-overlay" id="modal-overlay" onclick="closeModalOutside(event)">
      <div class="modal" id="modal">
        <button class="modal-back" onclick="closeModal()">
          <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <div class="modal-img-pane">
          <div class="modal-main-img" id="modal-main-img" style="cursor:zoom-in;" onclick="openImgLightbox()" title="Click to enlarge">
            <img id="modal-img-main" src="" alt="">
            <div class="gallery-dots" id="modal-gallery-dots"></div>
            <div class="gallery-nav">
              <button class="gallery-arrow" onclick="galleryPrev(event)">
                <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
              </button>
              <button class="gallery-arrow" onclick="galleryNext(event)">
                <svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
              </button>
            </div>
          </div>
          <div class="modal-thumbs-strip" id="modal-thumbs-strip"></div>
        </div>
        <div class="modal-details">
          <div class="modal-name"  id="modal-name"></div>
          <div class="modal-price" id="modal-price"></div>
          <div class="modal-divider"></div>
          <div class="modal-size-row">
            <span class="modal-size-label">Size:</span>
            <span class="modal-size-value" id="modal-size-val">—</span>
          </div>
          <div class="modal-condition-row">
            <span class="modal-condition-label">Condition:</span>
            <span class="condition-badge" id="modal-condition-badge">—</span>
            <span class="condition-desc" id="modal-condition-desc"></span>
          </div>
          <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.65rem;">
            <span class="modal-condition-label">Category:</span>
            <span class="product-category-tag" id="modal-category-tag" style="max-width:none;">—</span>
          </div>
          <div class="modal-divider"></div>
          <div class="modal-desc" id="modal-desc"></div>
          <div class="modal-actions">
            <button class="btn-cart" id="modal-cart-btn" onclick="addToCart()">Add to Cart</button>
            <button class="btn-buy"  onclick="buyNow()">Buy Now</button>
          </div>
        </div>
      </div>
    </div>`);
}

/* ── IMAGE LIGHTBOX ────────────────────────────────────────────── */
function _injectImgLightbox() {
  document.body.insertAdjacentHTML('beforeend', `
    <div id="img-lightbox" class="img-lightbox" onclick="closeImgLightbox()">
      <button class="img-lightbox-close" onclick="closeImgLightbox()" title="Close">&#x2715;</button>
      <button class="img-lightbox-arrow left"  onclick="lightboxPrev(event)" title="Previous">&#8249;</button>
      <img id="img-lightbox-img" src="" alt="" onclick="event.stopPropagation()">
      <button class="img-lightbox-arrow right" onclick="lightboxNext(event)" title="Next">&#8250;</button>
    </div>`);

  document.addEventListener('keydown', e => {
    const lb = document.getElementById('img-lightbox');
    if (!lb || !lb.classList.contains('open')) return;
    if (e.key === 'Escape')     closeImgLightbox();
    if (e.key === 'ArrowLeft')  lightboxPrev(e);
    if (e.key === 'ArrowRight') lightboxNext(e);
  });
}

function openImgLightbox() {
  const src = document.getElementById('modal-img-main')?.src;
  if (!src) return;
  const lb = document.getElementById('img-lightbox');
  document.getElementById('img-lightbox-img').src = src;
  lb.classList.add('open');
}

function closeImgLightbox() {
  document.getElementById('img-lightbox')?.classList.remove('open');
}

function lightboxPrev(e) {
  e.stopPropagation();
  galleryPrev(e);
  document.getElementById('img-lightbox-img').src = document.getElementById('modal-img-main').src;
}

function lightboxNext(e) {
  e.stopPropagation();
  galleryNext(e);
  document.getElementById('img-lightbox-img').src = document.getElementById('modal-img-main').src;
}

/* ── CART DRAWER ───────────────────────────────────────────────── */
function _injectCartDrawer() {
  document.body.insertAdjacentHTML('beforeend', `
    <div class="cart-drawer-overlay" id="cart-overlay" onclick="closeCart()"></div>
    <div class="cart-drawer" id="cart-drawer">
      <div class="cart-drawer-head">
        <span class="cart-drawer-title">Your Cart</span>
        <button class="cart-close-btn" onclick="closeCart()">
          <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
      <div class="cart-items" id="cart-items-list"></div>
      <div class="cart-footer">
        <div class="cart-total-row">
          <span class="cart-total-label">Total</span>
          <span class="cart-total-amount" id="cart-total-display">₱0</span>
        </div>
        <button class="btn-checkout" id="btn-checkout" onclick="goToCheckout()" disabled>Proceed to Checkout →</button>
        <p class="cart-note" id="cart-note-default">Payment via GCash QR only. Each item is unique — first to pay wins!</p>
        <p class="cart-note" id="cart-note-login" style="display:none;color:var(--accent2);">
          <a href="#" onclick="closeCart();openAuth();" style="color:inherit;text-decoration:underline;">Log in</a> to place an order.
        </p>
      </div>
    </div>`);
}

/* ── AUTH MODAL ────────────────────────────────────────────────── */
function _injectAuthModal() {
  document.body.insertAdjacentHTML('beforeend', `
    <div class="auth-overlay" id="auth-overlay" onclick="closeAuthOutside(event)">
      <div class="auth-box">
        <button class="auth-close" onclick="closeAuth()">✕</button>
        <div class="auth-tabs">
          <button class="auth-tab active" id="tab-login"    onclick="switchAuthTab('login')">Log in</button>
          <button class="auth-tab"        id="tab-register" onclick="switchAuthTab('register')">Register</button>
        </div>
        <!-- LOGIN -->
        <div class="auth-panel active" id="panel-login">
          <div class="auth-title">Welcome back</div>
          <div class="auth-sub">Log in to place orders and post reviews.</div>
          <div class="auth-field">
            <label class="auth-label" for="login-email">Email</label>
            <input class="auth-input" type="email" id="login-email" placeholder="you@email.com" onkeydown="if(event.key==='Enter')doLogin()">
          </div>
          <div class="auth-field">
            <label class="auth-label" for="login-pass">Password</label>
            <div class="pass-wrap">
              <input class="auth-input" type="password" id="login-pass" placeholder="••••••••" onkeydown="if(event.key==='Enter')doLogin()">
              <button type="button" class="eye-btn" onclick="togglePassVisibility('login-pass',this)" tabindex="-1" aria-label="Show/hide password">
                <svg class="eye-icon eye-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-icon eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
          </div>
          <div class="auth-error" id="login-error"></div>
          <button class="btn-auth" onclick="doLogin()">Log in</button>
        </div>
        <!-- REGISTER -->
        <div class="auth-panel" id="panel-register">
          <div class="auth-title">Create account</div>
          <div class="auth-sub">Free to join. Required to place orders and review items.</div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.7rem;">
            <div class="auth-field">
              <label class="auth-label" for="reg-fname">First name</label>
              <input class="auth-input" type="text" id="reg-fname" placeholder="Maria">
            </div>
            <div class="auth-field">
              <label class="auth-label" for="reg-lname">Last name</label>
              <input class="auth-input" type="text" id="reg-lname" placeholder="Santos">
            </div>
          </div>
          <div class="auth-field">
            <label class="auth-label" for="reg-email">Email</label>
            <input class="auth-input" type="email" id="reg-email" placeholder="you@email.com">
          </div>
          <div class="auth-field">
            <label class="auth-label" for="reg-phone">Phone (optional)</label>
            <input class="auth-input" type="tel" id="reg-phone" placeholder="09XX XXX XXXX">
          </div>
          <div class="auth-field">
            <label class="auth-label" for="reg-pass">Password</label>
            <div class="pass-wrap">
              <input class="auth-input" type="password" id="reg-pass" placeholder="Min. 8 characters" onkeydown="if(event.key==='Enter')doRegister()">
              <button type="button" class="eye-btn" onclick="togglePassVisibility('reg-pass',this)" tabindex="-1" aria-label="Show/hide password">
                <svg class="eye-icon eye-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-icon eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
          </div>
          <div class="auth-field">
            <label class="auth-label" for="reg-confirm-pass">Confirm Password</label>
            <div class="pass-wrap">
              <input class="auth-input" type="password" id="reg-confirm-pass" placeholder="Re-enter password" onkeydown="if(event.key==='Enter')doRegister()">
              <button type="button" class="eye-btn" onclick="togglePassVisibility('reg-confirm-pass',this)" tabindex="-1" aria-label="Show/hide password">
                <svg class="eye-icon eye-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-icon eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              </button>
            </div>
          </div>
          <div class="auth-error" id="reg-error"></div>
          <button class="btn-auth" onclick="doRegister()">Create account</button>
        </div>
      </div>
    </div>`);
}

/* ── EDIT PROFILE MODAL ────────────────────────────────────────── */
function _injectEditProfileModal() {
  document.body.insertAdjacentHTML('beforeend', `
    <div class="profile-modal-overlay" id="profile-modal-overlay" onclick="closeProfileOutside(event)">
      <div class="profile-modal" id="profile-modal">

        <div class="profile-modal-header">
          <div class="profile-modal-title">Edit Profile</div>
          <button class="profile-modal-close" onclick="closeEditProfile()">✕</button>
        </div>

        <div class="profile-modal-body">
          <div class="profile-error"   id="profile-error"></div>
          <div class="profile-success" id="profile-success">Profile updated successfully!</div>

          <!-- Personal Information -->
          <div class="profile-section-label">Personal Information</div>

          <div class="profile-row">
            <div class="profile-field">
              <label class="profile-label" for="prof-fname">First Name</label>
              <input class="profile-input" type="text" id="prof-fname" placeholder="Maria">
            </div>
            <div class="profile-field">
              <label class="profile-label" for="prof-lname">Last Name</label>
              <input class="profile-input" type="text" id="prof-lname" placeholder="Santos">
            </div>
          </div>

          <div class="profile-field">
            <label class="profile-label" for="prof-email">Email Address</label>
            <input class="profile-input" type="email" id="prof-email" placeholder="you@email.com">
          </div>

          <div class="profile-field">
            <label class="profile-label" for="prof-phone">Phone Number</label>
            <input class="profile-input" type="tel" id="prof-phone" placeholder="09XX XXX XXXX">
          </div>

          <div class="profile-field">
            <label class="profile-label" for="prof-address">Delivery Address</label>
            <input class="profile-input" type="text" id="prof-address" placeholder="House No., Street, City">
          </div>

          <!-- Change Password -->
          <div class="profile-section-label">Change Password <span style="font-weight:400;text-transform:none;letter-spacing:0;">(leave blank to keep current)</span></div>

          <div class="profile-field">
            <label class="profile-label" for="prof-cur-pass">Current Password</label>
            <input class="profile-input" type="password" id="prof-cur-pass" placeholder="Enter current password">
          </div>

          <div class="profile-row">
            <div class="profile-field">
              <label class="profile-label" for="prof-new-pass">New Password</label>
              <input class="profile-input" type="password" id="prof-new-pass" placeholder="Min. 8 characters">
            </div>
            <div class="profile-field">
              <label class="profile-label" for="prof-confirm-pass">Confirm New Password</label>
              <input class="profile-input" type="password" id="prof-confirm-pass" placeholder="Repeat new password">
            </div>
          </div>
        </div>

        <div class="profile-modal-footer">
          <button class="btn-profile-save" id="profile-save-btn" onclick="saveProfile()">Save Changes</button>
          <button class="btn-profile-cancel" onclick="closeEditProfile()">Cancel</button>
        </div>

      </div>
    </div>`);
}

/* ── MY ORDERS PANEL ───────────────────────────────────────────── */
function _injectOrdersPanel() {
  document.body.insertAdjacentHTML('beforeend', `
    <div class="orders-panel" id="orders-panel">

      <!-- Sub-header: title + tabs. The original site navbar stays above this. -->
      <div class="orders-panel-head">
        <div class="orders-panel-head-inner">
          <div class="orders-panel-title" id="orders-panel-title">My Orders</div>
          <div style="display:flex;align-items:center;gap:0.5rem;">
            <button class="orders-panel-close" onclick="closeMyOrdersPanel()">
              <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              Close
            </button>
          </div>
        </div>
        <div class="orders-panel-tabs">
          <button class="orders-panel-tab active" id="op-tab-active"  onclick="switchOrdersTab('active')">My Orders</button>
          <button class="orders-panel-tab"        id="op-tab-history" onclick="switchOrdersTab('history')">Order History</button>
        </div>
      </div>

      <!-- Scrollable content -->
      <div class="orders-panel-body">
        <div class="orders-panel-inner">
          <div class="orders-panel-content" id="orders-panel-content">
            <div class="orders-panel-loading">Loading your orders…</div>
          </div>
        </div>
      </div>

    </div>`);
}

/* ── PRODUCT REVIEW MODAL ──────────────────────────────────────── */
function _injectReviewModal() {
  document.body.insertAdjacentHTML('beforeend', `
    <div class="review-modal-overlay" id="review-modal-overlay" onclick="closeReviewModalOutside(event)">
      <div class="review-modal" id="review-modal">

        <div class="review-modal-header">
          <div class="review-modal-title">Write a Review</div>
          <button class="review-modal-close" onclick="closeReviewModal()">✕</button>
        </div>

        <div class="review-modal-body">
          <!-- Product chip — shows which product is being reviewed -->
          <div class="review-product-chip" id="review-product-chip">
            <img class="review-product-chip-img" id="review-chip-img" src="" alt="">
            <div class="review-product-chip-info">
              <div class="review-product-chip-label">Reviewing</div>
              <div class="review-product-chip-name" id="review-chip-name">—</div>
            </div>
          </div>

          <!-- Star rating -->
          <div class="review-modal-stars-label">Your Rating <span style="color:var(--accent2);">*</span></div>
          <div class="review-modal-stars" id="review-modal-stars">
            <button class="review-modal-star" onclick="setReviewRating(1)">★</button>
            <button class="review-modal-star" onclick="setReviewRating(2)">★</button>
            <button class="review-modal-star" onclick="setReviewRating(3)">★</button>
            <button class="review-modal-star" onclick="setReviewRating(4)">★</button>
            <button class="review-modal-star" onclick="setReviewRating(5)">★</button>
          </div>

          <!-- Review text -->
          <div class="review-modal-text-label">Your Review <span style="color:var(--accent2);">*</span></div>
          <textarea class="review-modal-textarea" id="review-modal-body"
            placeholder="Share your experience with this item — quality, sizing, condition…"></textarea>

          <!-- Photo upload -->
          <div class="review-modal-text-label" style="margin-top:0.9rem;">
            Photos <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:0.75rem;color:var(--text-muted);">(optional · up to 5)</span>
          </div>
          <label class="rv-file-label" for="review-modal-images">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;flex-shrink:0;"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            Choose photos…
          </label>
          <input type="file" id="review-modal-images" class="rv-file-input" multiple accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewModalReviewImages(this)">
          <div id="review-modal-img-previews" class="review-img-previews"></div>

          <!-- Error -->
          <div class="review-modal-err" id="review-modal-err"></div>
        </div>

        <div class="review-modal-footer">
          <button class="btn-review-submit" id="review-submit-btn" onclick="submitProductReview()">Post Review</button>
          <button class="btn-review-cancel" onclick="closeReviewModal()">Cancel</button>
        </div>

      </div>
    </div>`);
}

/* ── TOAST ─────────────────────────────────────────────────────── */
function _injectToast() {
  document.body.insertAdjacentHTML('beforeend', `<div class="toast" id="toast"></div>`);
}

/* ── THEME (light / dark) ──────────────────────────────────────── */
function _applyTheme(dark) {
  document.body.classList.toggle('dark', dark);
  /* Sync Bootstrap's theme so navbar, offcanvas, forms & cards go dark too */
  document.documentElement.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
  const moon = document.getElementById('theme-icon-moon');
  const sun  = document.getElementById('theme-icon-sun');
  if (moon) moon.style.display = dark ? 'none'  : '';
  if (sun)  sun.style.display  = dark ? ''      : 'none';
}

function toggleTheme() {
  const isDark = !document.body.classList.contains('dark');
  localStorage.setItem('carousell_theme', isDark ? 'dark' : 'light');
  _applyTheme(isDark);
}

/* Apply saved theme immediately on load */
(function() {
  const saved = localStorage.getItem('carousell_theme');
  if (saved === 'dark') _applyTheme(true);
})();

