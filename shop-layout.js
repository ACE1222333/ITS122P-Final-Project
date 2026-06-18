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
    <button class="admin-bar-back" onclick="sessionStorage.removeItem('carousell_admin_preview'); window.location.href='admin.html'">
      ← Back to Admin
    </button>`;
  document.body.prepend(bar);
  checkAdminPreviewMode();
}

/* ── NAV ───────────────────────────────────────────────────────── */
function _injectNav(activePageId) {
  const pages = [
    { id:'home',     label:'Home',     href:'shop.html' },
    { id:'shop',     label:'Shop',     href:'shop-products.html' },
    { id:'reviews',  label:'Reviews',  href:'shop-reviews.html' },
    { id:'about',    label:'About',    href:'shop-about.html' },
    { id:'contacts', label:'Contacts', href:'shop-contacts.html' },
  ];

  const links = pages.map(p =>
    `<li><a href="${p.href}" class="${p.id===activePageId?'active':''}">${p.label}</a></li>`
  ).join('');

  const nav = document.createElement('nav');
  nav.innerHTML = `
    <a class="logo" href="shop.html">Carousell</a>
    <ul class="nav-links">${links}</ul>
    <div class="nav-icons">

      <!-- Cart -->
      <div class="cart-wrap" onclick="openCart()">
        <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
        <span class="cart-badge" id="cart-count">0</span>
      </div>

      <!-- Guest: show login icon -->
      <div id="nav-auth-guest" onclick="openAuth()" title="Log in" style="cursor:pointer;">
        <svg viewBox="0 0 24 24" style="width:18px;height:18px;stroke:var(--nav-muted);fill:none;stroke-width:1.8;transition:stroke 0.2s;"
             onmouseover="this.style.stroke='var(--nav-text)'" onmouseout="this.style.stroke='var(--nav-muted)'">
          <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
        </svg>
      </div>

      <!-- Logged in: user pill with dropdown -->
      <div id="nav-auth-user" class="nav-user-pill" style="display:none;" onclick="toggleUserDropdown(event)">
        <svg class="u-avatar" viewBox="0 0 24 24">
          <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
        </svg>
        <span class="u-name" id="nav-username"></span>
        <svg class="u-chevron" viewBox="0 0 24 24">
          <polyline points="6 9 12 15 18 9"/>
        </svg>

        <div class="nav-user-dropdown" id="user-dropdown">
          <!-- User info header -->
          <div class="dropdown-user-info">
            <div class="dropdown-user-name"  id="dropdown-fullname">—</div>
            <div class="dropdown-user-email" id="dropdown-email">—</div>
          </div>

          <!-- Edit Profile -->
          <button onclick="openEditProfile(event)">
            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Edit Profile
          </button>

          <div class="dropdown-divider"></div>

          <!-- My Orders -->
          <button onclick="openMyOrders(event)">
            <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
            My Orders
          </button>

          <!-- Order History -->
          <button onclick="openOrderHistory(event)">
            <svg viewBox="0 0 24 24"><polyline points="12 8 12 12 14 14"/><path d="M3.05 11a9 9 0 1 0 .5-4"/><polyline points="3 3 3 9 9 9"/></svg>
            Order History
          </button>

          <div class="dropdown-divider"></div>

          <!-- Logout -->
          <button class="danger" onclick="logOut(event)">
            <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Log out
          </button>
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
          <div class="modal-main-img" id="modal-main-img">
            <img id="modal-img-main" src="" alt="">
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
          <div class="modal-size-row">
            <span class="modal-size-label">Size:</span>
            <span class="modal-size-value" id="modal-size-val">—</span>
          </div>
          <div class="modal-condition-row">
            <span class="modal-condition-label">Condition:</span>
            <span class="condition-badge" id="modal-condition-badge">—</span>
          </div>
          <div style="margin-bottom:0.9rem;display:flex;align-items:center;gap:0.5rem;">
            <span class="modal-condition-label">Category:</span>
            <span class="product-category-tag" id="modal-category-tag" style="max-width:none;">—</span>
          </div>
          <div class="modal-desc" id="modal-desc"></div>
          <div class="modal-actions">
            <button class="btn-cart" id="modal-cart-btn" onclick="addToCart()">Add to Cart</button>
            <button class="btn-buy"  onclick="buyNow()">Buy Now</button>
          </div>
        </div>
      </div>
    </div>`);
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
            <input class="auth-input" type="password" id="login-pass" placeholder="••••••••" onkeydown="if(event.key==='Enter')doLogin()">
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
            <input class="auth-input" type="password" id="reg-pass" placeholder="Min. 8 characters" onkeydown="if(event.key==='Enter')doRegister()">
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
          <button class="orders-panel-close" onclick="closeMyOrdersPanel()">
            <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Close
          </button>
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
