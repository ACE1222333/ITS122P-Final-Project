<?php
session_start();
include('connection.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products — ByTheBel Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin-styles.css">
<style>
/* â”€â”€ Filter bar â”€â”€ */
.filter-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
  align-items: center;
  margin-bottom: 1.2rem;
}
.filter-select {
  background: var(--bg-card);
  border: 1.5px solid var(--border);
  border-radius: 8px;
  padding: 0.55rem 0.85rem;
  font-family: 'DM Sans', sans-serif;
  font-size: 0.8rem;
  color: var(--text);
  cursor: pointer;
  outline: none;
  transition: border-color 0.2s;
  min-width: 140px;
}
.filter-select:focus { border-color: var(--accent); }
.filter-select.active { border-color: var(--accent); background: var(--accent); color: #fff; }
.filter-clear-btn {
  background: none;
  border: 1.5px solid var(--border);
  border-radius: 8px;
  padding: 0.55rem 0.85rem;
  font-family: 'DM Sans', sans-serif;
  font-size: 0.78rem;
  color: var(--text-muted);
  cursor: pointer;
  transition: background 0.2s, color 0.2s;
}
.filter-clear-btn:hover { background: var(--bg-section); color: var(--text); }

/* â”€â”€ Status badge extras â”€â”€ */
.badge-available { background: #dcfce7; color: #166534; }
.badge-reserved  { background: #fef3c7; color: #92400e; }
.badge-sold      { background: #fee2e2; color: #991b1b; }

/* â”€â”€ Sold row styling â”€â”€ */
tr.row-sold td { opacity: 0.55; }
tr.row-sold td:first-child { opacity: 1; }
.sold-actions-note {
  font-size: 0.72rem;
  color: var(--text-muted);
  font-style: italic;
}
</style>
</head>
<body>

<div class="shell">
  <!-- sidebar injected by admin-layout.js -->
  <main class="main">

    <div class="page-header">
      <div class="page-title">Products</div>
      <a class="btn-add" href="admin-product-form.php">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Add Product
      </a>
    </div>

    <!-- Search + Filters -->
    <div style="margin-bottom:0.8rem;">
      <input class="search-bar" type="text" placeholder="Search products by name…"
             id="search-input" oninput="applyFilters()" style="margin-bottom:0;">
    </div>

    <div class="filter-bar">
      <select class="filter-select" id="filter-status" onchange="applyFilters()">
        <option value="">All Statuses</option>
        <option value="available">Available</option>
        <option value="reserved">Reserved</option>
        <option value="sold">Sold</option>
      </select>

      <select class="filter-select" id="filter-category" onchange="applyFilters()">
        <option value="">All Categories</option>
      </select>

      <select class="filter-select" id="filter-size" onchange="applyFilters()">
        <option value="">All Sizes</option>
        <option>XS</option><option>S</option><option>M</option>
        <option>L</option><option>XL</option><option>XXL</option>
        <option>Free Size</option>
      </select>

      <button class="filter-clear-btn" onclick="clearFilters()">Clear Filters</button>

      <span id="filter-count" style="font-size:0.78rem;color:var(--text-muted);margin-left:auto;"></span>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Size</th>
            <th>Condition</th>
            <th>Status</th>
            <th>Featured</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="product-tbody">
          <tr class="empty-row"><td colspan="8">Loading products…</td></tr>
        </tbody>
      </table>
    </div>

  </main>
</div>

<script src="admin-data.js"></script>
<script src="admin-layout.js?v=2"></script>
<script>
initAdminLayout('products');
checkAdminAuth();

Promise.all([
  fetchProducts(),
  fetch('api/categories.php').then(r => r.json()).catch(() => []),
]).then(([, cats]) => {
  if (Array.isArray(cats)) {
    const sel = document.getElementById('filter-category');
    cats.forEach(c => {
      const opt = document.createElement('option');
      opt.value = c.name; opt.textContent = c.name;
      sel.appendChild(opt);
    });
  }
  applyFilters();
});

function applyFilters() {
  const q        = document.getElementById('search-input').value.trim().toLowerCase();
  const status   = document.getElementById('filter-status').value;
  const category = document.getElementById('filter-category').value.toLowerCase();
  const size     = document.getElementById('filter-size').value.toLowerCase();

  let list = products.filter(p => {
    if (q        && !p.name.toLowerCase().includes(q))                      return false;
    if (status   && (p.status || 'available') !== status)                   return false;
    if (category) {
      const pCats = (p.categories || []).map(c => c.name.toLowerCase());
      if (!pCats.includes(category)) return false;
    }
    if (size     && (p.size || '').toLowerCase() !== size.toLowerCase())     return false;
    return true;
  });

  renderProductTable(list);

  const countEl = document.getElementById('filter-count');
  countEl.textContent = list.length === products.length
    ? `${products.length} product${products.length !== 1 ? 's' : ''}`
    : `${list.length} of ${products.length} products`;
}

function clearFilters() {
  document.getElementById('search-input').value    = '';
  document.getElementById('filter-status').value   = '';
  document.getElementById('filter-category').value = '';
  document.getElementById('filter-size').value     = '';
  applyFilters();
}

function statusBadge(status) {
  const map = {
    available: ['badge-available', 'Available'],
    reserved:  ['badge-reserved',  'Reserved'],
    sold:      ['badge-sold',      'Sold'],
  };
  const [cls, label] = map[status] || ['badge-gray', status || 'Available'];
  return `<span class="badge ${cls}">${label}</span>`;
}

function renderProductTable(list) {
  const tbody = document.getElementById('product-tbody');
  if (!list || !list.length) {
    tbody.innerHTML = '<tr class="empty-row"><td colspan="8">No products found.</td></tr>';
    return;
  }

  tbody.innerHTML = list.map(p => {
    const img    = getCoverImage(p);
    const status = p.status || 'available';
    const isSold = status === 'sold';

    const actionsHtml = isSold
      ? `<span class="sold-actions-note">No actions — item sold</span>`
      : `<div class="actions-cell">
           <a class="btn-edit" href="admin-product-form.php?id=${p.id}">Edit</a>
           <button class="btn-delete" onclick="confirmDeleteProduct(${p.id})">Delete</button>
         </div>`;

    return `
      <tr class="${isSold ? 'row-sold' : ''}">
        <td>
          <div class="td-name-cell">
            <div class="td-img">${img ? `<img src="${img}" alt="${p.name}">` : ''}</div>
            <div>
              <div class="td-name">${p.name}</div>
              <div class="td-desc-preview">${p.desc ? p.desc.substring(0,40) + (p.desc.length > 40 ? '…' : '') : ''}</div>
            </div>
          </div>
        </td>
        <td>${(p.categories && p.categories.length) ? p.categories.map(c => c.name).join(', ') : '—'}</td>
        <td>₱${Number(p.price).toLocaleString()}</td>
        <td>${p.size || '—'}</td>
        <td>${conditionBadge(p.condition)}</td>
        <td>${statusBadge(status)}</td>
        <td>${p.featured ? '<span class="badge badge-featured">Featured</span>' : '<span class="badge badge-gray">No</span>'}</td>
        <td>${actionsHtml}</td>
      </tr>`;
  }).join('');
}

function confirmDeleteProduct(id) {
  const p = products.find(x => x.id === id);
  if (!p) return;
  openConfirm(
    'Delete Product?',
    `Delete "${p.name}"? This cannot be undone.`,
    () => deleteProduct(id, () => fetchProducts(applyFilters)),
    'Delete', true
  );
}
</script>
</body>
</html>
