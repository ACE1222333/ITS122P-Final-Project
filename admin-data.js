/* ════════════════════════════════════════════════════════════════
   admin-data.js — Shared data layer for all admin pages.
   All data is fetched from the PHP API (no localStorage products/orders).
════════════════════════════════════════════════════════════════ */

/* ── GLOBAL STATE ────────────────────────────────────────────── */
let products = [];
let orders   = [];

/* ── AUTH HELPERS ────────────────────────────────────────────── */
function getAdminToken() {
  try {
    const sess = localStorage.getItem('carousell_session');
    if (!sess) return '';
    const obj = JSON.parse(sess);
    return obj.token || '';
  } catch(e) { return ''; }
}

function checkAdminAuth() {
  try {
    const sess = localStorage.getItem('carousell_session');
    if (!sess) { window.location.href = 'shop.html'; return; }
    const obj = JSON.parse(sess);
    if (!obj.token || obj.role !== 'admin') { window.location.href = 'shop.html'; }
  } catch(e) { window.location.href = 'shop.html'; }
}

/* ── FETCH WRAPPER ───────────────────────────────────────────── */
async function adminFetch(url, options = {}) {
  const token = getAdminToken();
  const headers = { ...(options.headers || {}) };
  if (token) headers['Authorization'] = 'Bearer ' + token;
  /* Only set Content-Type: application/json if the body is NOT FormData */
  if (!(options.body instanceof FormData)) {
    headers['Content-Type'] = 'application/json';
  }
  const res = await fetch(url, { ...options, headers });
  if (res.status === 401) {
    showToast('Session expired. Please log in again.');
    setTimeout(() => { window.location.href = 'shop.html'; }, 1500);
    throw new Error('Unauthorized');
  }
  return res;
}

/* ── DATA FETCHERS ───────────────────────────────────────────── */
async function fetchProducts(onLoaded) {
  try {
    const res  = await adminFetch('api/products.php');
    const data = await res.json();
    if (Array.isArray(data)) {
      products = data;
    } else {
      console.error('fetchProducts: unexpected response', data);
      products = [];
    }
  } catch(e) {
    console.error('fetchProducts error:', e);
    products = [];
  }
  if (typeof onLoaded === 'function') onLoaded(products);
}

async function fetchOrders(onLoaded) {
  try {
    const res  = await adminFetch('api/orders.php');
    const data = await res.json();
    if (Array.isArray(data)) {
      orders = data;
    } else {
      console.error('fetchOrders: unexpected response', data);
      orders = [];
    }
  } catch(e) {
    console.error('fetchOrders error:', e);
    orders = [];
  }
  if (typeof onLoaded === 'function') onLoaded(orders);
}

/* ── PRODUCT CRUD ────────────────────────────────────────────── */
async function createProduct(data) {
  const res  = await adminFetch('api/products/create.php', {
    method: 'POST',
    body:   JSON.stringify(data),
  });
  return res.json();
}

async function updateProduct(data) {
  const res = await adminFetch('api/products/update.php', {
    method: 'POST',
    body:   JSON.stringify(data),
  });
  return res.json();
}

async function deleteProduct(productId, onSuccess) {
  try {
    const res  = await adminFetch('api/products/delete.php', {
      method: 'POST',
      body:   JSON.stringify({ product_id: productId }),
    });
    const data = await res.json();
    if (data.success) {
      showToast(data.message || 'Product deleted.');
      if (typeof onSuccess === 'function') onSuccess();
    } else {
      showToast(data.error || 'Failed to delete product.');
    }
  } catch(e) {
    showToast('Error deleting product.');
    console.error(e);
  }
}

/* ── ORDER STATUS ────────────────────────────────────────────── */
async function updateOrderStatus(orderId, orderStatus, paymentStatus) {
  const res = await adminFetch('api/orders/update_status.php', {
    method: 'POST',
    body:   JSON.stringify({ order_id: orderId, order_status: orderStatus, payment_status: paymentStatus }),
  });
  return res.json();
}

/* ── CATEGORIES (dynamic, user-managed) ─────────────────────── */
async function fetchAdminCategories(onLoaded) {
  try {
    const res  = await fetch('api/categories.php');
    const data = await res.json();
    if (typeof onLoaded === 'function') onLoaded(Array.isArray(data) ? data : []);
    return Array.isArray(data) ? data : [];
  } catch(e) {
    console.error('fetchAdminCategories:', e);
    if (typeof onLoaded === 'function') onLoaded([]);
    return [];
  }
}

async function createAdminCategory(name, description) {
  const res = await adminFetch('api/categories/create.php', {
    method: 'POST',
    body:   JSON.stringify({ name, description }),
  });
  return res.json();
}

async function deleteAdminCategory(categoryId) {
  const res = await adminFetch('api/categories/delete.php', {
    method: 'POST',
    body:   JSON.stringify({ category_id: categoryId }),
  });
  return res.json();
}

/* ── FEATURED ────────────────────────────────────────────────── */
async function toggleProductFeatured(productId, featured) {
  const res = await adminFetch('api/featured.php', {
    method: 'POST',
    body:   JSON.stringify({ product_id: productId, featured }),
  });
  return res.json();
}

/* ════════════════════════════════════════════════════════════════
   XML BUILDERS — kept for reference (unused in API mode)
════════════════════════════════════════════════════════════════ */
function buildProductsXML(prods) {
  const items = prods.map(p => `
  <product>
    <id>${p.id}</id>
    <name><![CDATA[${p.name}]]></name>
    <price>${p.price}</price>
    <category><![CDATA[${p.category || ''}]]></category>
    <size>${p.size || ''}</size>
    <condition><![CDATA[${p.condition || ''}]]></condition>
    <featured>${p.featured ? 1 : 0}</featured>
    <cover_index>${p.coverIndex || 0}</cover_index>
    <images>${(p.images || []).map(img => `<image><![CDATA[${img}]]></image>`).join('')}</images>
    <desc><![CDATA[${p.desc || ''}]]></desc>
  </product>`).join('');
  return `<?xml version="1.0" encoding="UTF-8"?>\n<products>${items}\n</products>`;
}

function buildOrderStatusXML(orderId, payStatus, ordStatus) {
  return `<?xml version="1.0" encoding="UTF-8"?>
<order_update>
  <order_id>${orderId}</order_id>
  <payment_status><![CDATA[${payStatus}]]></payment_status>
  <order_status><![CDATA[${ordStatus}]]></order_status>
</order_update>`;
}

/* ════════════════════════════════════════════════════════════════
   SHARED UI UTILITIES
════════════════════════════════════════════════════════════════ */
function showToast(msg) {
  const t = document.getElementById('toast');
  if (!t) return;
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.classList.remove('show'), 2800);
}

function openConfirm(title, msg, onConfirm, confirmLabel = 'Confirm', isDanger = true) {
  document.getElementById('confirm-title').textContent = title;
  document.getElementById('confirm-msg').textContent   = msg;
  document.getElementById('confirm-ok').textContent    = confirmLabel;
  document.getElementById('confirm-ok').className      = isDanger ? 'btn-confirm-del' : 'btn-save';
  document.getElementById('confirm-ok').onclick        = () => { onConfirm(); closeConfirm(); };
  document.getElementById('confirm-overlay').classList.add('open');
}

function closeConfirm() {
  document.getElementById('confirm-overlay').classList.remove('open');
}

/* ── BADGE HELPERS ───────────────────────────────────────────── */
function conditionBadge(condition) {
  const map = { 'Like New':'badge-green', 'Excellent':'badge-blue', 'Good':'badge-yellow', 'Fair':'badge-gray' };
  return condition ? `<span class="badge ${map[condition]||'badge-gray'}">${condition}</span>` : '—';
}

function paymentStatusBadge(status) {
  const map = { 'Pending Verification':'badge-yellow', 'Verified':'badge-green', 'Rejected':'badge-red' };
  return `<span class="badge ${map[status]||'badge-gray'}">${status}</span>`;
}

function orderStatusBadge(status) {
  const map = { 'Pending Payment':'badge-gray', 'Processing':'badge-blue', 'Shipping':'badge-yellow', 'Shipped':'badge-blue', 'Completed':'badge-green' };
  return `<span class="badge ${map[status]||'badge-gray'}">${status}</span>`;
}

/* ── COVER IMAGE HELPER ─────────────────────────────────────── */
function getCoverImage(p) {
  if (!p.images || !p.images.length) return p.image || '';
  const idx = (typeof p.coverIndex === 'number' && p.coverIndex < p.images.length) ? p.coverIndex
            : (typeof p.cover_index === 'number' && p.cover_index < p.images.length) ? p.cover_index : 0;
  return p.images[idx] || p.images[0] || '';
}

/* ── MULTI-IMAGE DRAG STUBS ─────────────────────────────────── */
/* Upload now happens immediately in admin-product-form.html via api/upload.php */
function handleMultiDragOver(e, dropId)  { e.preventDefault(); document.getElementById(dropId).classList.add('dragover'); }
function handleMultiDragLeave(e, dropId) { document.getElementById(dropId).classList.remove('dragover'); }

/* ── FEATURED TOGGLE ─────────────────────────────────────────── */
function setToggle(trackId, labelId, isOn) {
  const track = document.getElementById(trackId);
  const label = document.getElementById(labelId);
  if (!track || !label) return;
  isOn ? track.classList.add('on') : track.classList.remove('on');
  label.textContent = isOn ? 'Featured' : 'Not Featured';
}
