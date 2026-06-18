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
    <div class="topnav-logo">Carousell</div>
    <ul class="topnav-links">
      <li><a href="shop.html?from=admin">View Shop</a></li>
    </ul>`;
  document.body.prepend(nav);
}

function _injectSidebar(activeNavId) {
  const nav_items = [
    { id: 'dashboard',   label: 'Dashboard',    href: 'admin.html' },
    { id: 'products',   label: 'Products',     href: 'admin-products.html' },
    { id: 'categories', label: 'Categories',   href: 'admin-categories.html' },
    { id: 'orders',     label: 'Orders',       href: 'admin-orders.html' },
    { id: 'featured',   label: 'Featured',     href: 'admin-featured.html' },
    { id: 'profile',    label: 'Edit Profile', href: 'admin-profile.html' },
  ];

  const links = nav_items.map(item => `
    <a href="${item.href}" class="${item.id === activeNavId ? 'active' : ''}">${item.label}</a>
  `).join('');

  const aside = document.createElement('aside');
  aside.className = 'sidebar';
  aside.innerHTML = `
    <div class="sidebar-brand">Carousell Admin</div>
    <nav class="sidebar-nav">${links}</nav>
    <div class="sidebar-footer">
      <button class="btn-logout" onclick="_confirmLogout()">Log Out</button>
    </div>`;
  document.querySelector('.shell').prepend(aside);
}

function _injectOverlays() {
  document.body.insertAdjacentHTML('beforeend', `
    <!-- Confirm Dialog -->
    <div class="confirm-overlay" id="confirm-overlay">
      <div class="confirm-box">
        <div class="confirm-title" id="confirm-title">Confirm</div>
        <div class="confirm-msg"   id="confirm-msg">Are you sure?</div>
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
      const sess  = localStorage.getItem('carousell_session');
      const token = sess ? (JSON.parse(sess).token || '') : '';
      if (token) {
        await fetch('api/auth.php', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
          body:    JSON.stringify({ action: 'logout', token }),
        });
      }
    } catch(e) { /* ignore network errors on logout */ }
    localStorage.removeItem('carousell_session');
    window.location.href = 'shop.html';
  }, 'Log Out', true);
}
