/* ════════════════════════════════════════════════════════════════
   admin-layout.js — Injects shared topnav, sidebar, confirm dialog,
   and toast into every admin page.

   Call initAdminLayout('nav-id') once per page, e.g.:
     initAdminLayout('products')
════════════════════════════════════════════════════════════════ */

function initAdminLayout(activeNavId) {
  _injectTopnav();
  _injectSidebar(activeNavId);
  _injectOverlays();
}

function _injectTopnav() {
  const nav = document.createElement('nav');
  nav.className = 'topnav';
  nav.innerHTML = `
    <div style="display:flex;align-items:center;gap:0.8rem;">
      <button class="sidebar-toggle" id="sidebar-toggle" onclick="toggleAdminSidebar()" aria-label="Toggle menu">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div class="topnav-logo">ByTheBel</div>
    </div>
    <ul class="topnav-links">
      <li><a href="shop.php?from=admin">View Shop</a></li>
    </ul>`;
  document.body.prepend(nav);

  /* Start polling sidebar badges */
  _fetchSidebarBadges();
  setInterval(_fetchSidebarBadges, 30000);
}

function toggleAdminSidebar() {
  document.querySelector('.sidebar')?.classList.toggle('open');
  document.getElementById('sidebar-backdrop')?.classList.toggle('show');
}

function closeAdminSidebar() {
  document.querySelector('.sidebar')?.classList.remove('open');
  document.getElementById('sidebar-backdrop')?.classList.remove('show');
}

async function _fetchSidebarBadges() {
  try {
    const res  = await adminFetch('api/notifications.php');
    const data = await res.json();
    _updateSidebarBadges(data);
  } catch(e) { /* silently fail */ }
}

function _updateSidebarBadges(data) {
  const payBadge = document.getElementById('sidebar-badge-payments');
  const revBadge = document.getElementById('sidebar-badge-reviews');
  if (payBadge) {
    payBadge.textContent    = data.pending_payments > 9 ? '9+' : data.pending_payments;
    payBadge.style.display  = data.pending_payments > 0 ? '' : 'none';
  }
  if (revBadge) {
    revBadge.textContent    = data.pending_reviews > 9 ? '9+' : data.pending_reviews;
    revBadge.style.display  = data.pending_reviews > 0 ? '' : 'none';
  }
}

function _injectSidebar(activeNavId) {
  const nav_items = [
    { id: 'dashboard',  label: 'Dashboard',    href: 'admin.php',          badge: null },
    { id: 'payments',   label: 'Payments',     href: 'admin-payments.php', badge: 'payments' },
    { id: 'orders',     label: 'Orders',       href: 'admin-orders.php',   badge: null },
    { id: 'products',   label: 'Products',     href: 'admin-products.php', badge: null },
    { id: 'reviews',    label: 'Reviews',      href: 'admin-reviews.php',  badge: 'reviews' },
    { id: 'profile',    label: 'Edit Profile', href: 'admin-profile.php',  badge: null },
  ];

  const links = nav_items.map(item => `
    <a href="${item.href}" class="${item.id === activeNavId ? 'active' : ''}">
      ${item.label}
      ${item.badge ? `<span class="sidebar-badge" id="sidebar-badge-${item.badge}" style="display:none;">0</span>` : ''}
    </a>
  `).join('');

  const aside = document.createElement('aside');
  aside.className = 'sidebar';
  aside.innerHTML = `
    <div class="sidebar-brand">ByTheBel Admin</div>
    <nav class="sidebar-nav">${links}</nav>
    <div class="sidebar-footer">
      <button class="btn-logout" onclick="_confirmLogout()">Log Out</button>
    </div>`;
  document.querySelector('.shell').prepend(aside);
}

function _injectOverlays() {
  document.body.insertAdjacentHTML('beforeend', `
    <!-- Sidebar backdrop (mobile) -->
    <div class="sidebar-backdrop" id="sidebar-backdrop" onclick="closeAdminSidebar()"></div>
    <!-- Confirm Dialog -->
    <div class="confirm-overlay" id="confirm-overlay">
      <div class="confirm-box">
        <div class="confirm-title" id="confirm-title">Confirm</div>
        <div class="confirm-msg"   id="confirm-msg">Are you sure?</div>
        <textarea id="confirm-reason-input" placeholder="Reason for rejection (optional)"
          style="display:none;width:100%;margin-top:0.75rem;padding:0.5rem 0.65rem;
                 border:1.5px solid var(--border);border-radius:8px;font-family:inherit;
                 font-size:0.82rem;resize:vertical;min-height:64px;box-sizing:border-box;
                 background:var(--bg-input,#fff);color:var(--text);line-height:1.5;"></textarea>
        <div class="confirm-actions">
          <button class="btn-confirm-del" id="confirm-ok">Confirm</button>
          <button class="btn-confirm-cancel" onclick="closeConfirm()">Cancel</button>
        </div>
      </div>
    </div>
    <!-- Toast -->
    <div class="toast" id="toast"></div>
  `);
}

function _confirmLogout() {
  openConfirm('Log Out?', 'Are you sure you want to log out?', async () => {
    /* Invalidate session token server-side */
    try {
      const sess  = localStorage.getItem('bythebel_session');
      const token = sess ? (JSON.parse(sess).token || '') : '';
      if (token) {
        await fetch('api/auth.php', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
          body:    JSON.stringify({ action: 'logout', token }),
        });
      }
    } catch(e) { /* ignore network errors on logout */ }
    localStorage.removeItem('bythebel_session');
    window.location.href = 'admin-login.php';
  }, 'Log Out', true);
}


