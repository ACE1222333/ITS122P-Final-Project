<?php
session_start();
include('connection.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reviews — Carousell Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin-styles.css">
<style>
.page-header { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.8rem; }
.page-title    { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; letter-spacing: 0.08em; line-height: 1; }
.page-subtitle { font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem; }
.rev-stats { display: flex; gap: 1rem; margin-bottom: 1.6rem; flex-wrap: wrap; }
.rev-stat { background: #fff; border: 1px solid var(--border); border-radius: 10px; padding: 1rem 1.4rem; min-width: 120px; flex: 1; }
.rev-stat-num   { font-family: 'Bebas Neue', sans-serif; font-size: 1.8rem; letter-spacing: 0.06em; line-height: 1; }
.rev-stat-label { font-size: 0.72rem; color: var(--text-muted); letter-spacing: 0.05em; text-transform: uppercase; margin-top: 0.2rem; }
.rev-filters { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 1rem 1.2rem; margin-bottom: 1.4rem; display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap; }
.rev-filter-group { display: flex; flex-direction: column; gap: 0.3rem; flex: 1; min-width: 140px; }
.rev-filter-label { font-size: 0.68rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-muted); }
.rev-filter-select, .rev-filter-input { border: 1px solid var(--border); border-radius: 7px; padding: 0.48rem 0.75rem; font-family: 'DM Sans', sans-serif; font-size: 0.83rem; color: var(--text); background: var(--bg); outline: none; transition: border-color 0.15s; }
.rev-filter-select:focus, .rev-filter-input:focus { border-color: var(--accent); }
.btn-filter-clear { background: none; border: 1px solid var(--border); border-radius: 7px; padding: 0.48rem 1rem; font-family: 'DM Sans', sans-serif; font-size: 0.83rem; color: var(--text-muted); cursor: pointer; transition: all 0.15s; white-space: nowrap; align-self: flex-end; }
.btn-filter-clear:hover { border-color: var(--accent); color: var(--accent); }
.rev-list { display: flex; flex-direction: column; gap: 1rem; }
.rev-card { background: #fff; border: 1px solid var(--border); border-radius: 12px; overflow: hidden; transition: box-shadow 0.15s; }
.rev-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,0.08); }
.rev-card-top { padding: 1.2rem 1.4rem 1rem; display: flex; gap: 1rem; align-items: flex-start; }
.rev-avatar { width: 38px; height: 38px; border-radius: 50%; background: var(--accent); display: flex; align-items: center; justify-content: center; font-family: 'Bebas Neue', sans-serif; font-size: 1rem; color: #fff; letter-spacing: 0.06em; flex-shrink: 0; }
.rev-meta { flex: 1; min-width: 0; }
.rev-meta-name { font-weight: 600; font-size: 0.9rem; }
.rev-meta-sub { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.15rem; display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; }
.rev-stars { color: #f59e0b; font-size: 1rem; letter-spacing: 0.05em; }
.rev-stars .empty { color: #ddd; }
.rev-body { padding: 0 1.4rem 1.2rem; font-size: 0.85rem; line-height: 1.65; color: var(--text); }
.rev-reply-block { margin: 0 1.4rem 1.2rem; background: #f7f5ff; border: 1px solid #d8d0f8; border-left: 3px solid var(--accent); border-radius: 8px; padding: 0.9rem 1rem; }
.rev-reply-label { font-size: 0.65rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--accent); margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.35rem; }
.rev-reply-label svg { width: 12px; height: 12px; stroke: var(--accent); fill: none; stroke-width: 2.5; flex-shrink: 0; }
.rev-reply-text { font-size: 0.83rem; line-height: 1.6; color: #333; }
.rev-reply-date { font-size: 0.7rem; color: #999; margin-top: 0.3rem; }
.rev-card-footer { border-top: 1px solid var(--border); padding: 0.75rem 1.4rem; background: #fafafa; display: flex; gap: 0.6rem; align-items: center; }
.btn-open-reply { background: var(--accent); color: #fff; border: none; border-radius: 7px; padding: 0.45rem 1.1rem; font-family: 'DM Sans', sans-serif; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: opacity 0.15s; display: inline-flex; align-items: center; gap: 0.35rem; }
.btn-open-reply:hover { opacity: 0.88; }
.btn-remove-reply { background: none; border: 1px solid #fca5a5; border-radius: 7px; padding: 0.45rem 0.9rem; font-family: 'DM Sans', sans-serif; font-size: 0.82rem; color: #e53e3e; cursor: pointer; transition: all 0.15s; }
.btn-remove-reply:hover { background: #fef2f2; }
.rev-reply-form { border-top: 1px solid var(--border); padding: 1rem 1.4rem; background: #fafafa; display: none; flex-direction: column; gap: 0.6rem; }
.rev-reply-form.open { display: flex; }
.rev-reply-form-label { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-muted); }
.rev-reply-textarea { border: 1px solid var(--border); border-radius: 8px; padding: 0.65rem 0.85rem; font-family: 'DM Sans', sans-serif; font-size: 0.83rem; color: var(--text); resize: vertical; min-height: 72px; outline: none; background: #fff; transition: border-color 0.15s; }
.rev-reply-textarea:focus { border-color: var(--accent); }
.rev-reply-actions { display: flex; gap: 0.6rem; }
.btn-save-reply { background: var(--accent); color: #fff; border: none; border-radius: 7px; padding: 0.5rem 1.2rem; font-family: 'DM Sans', sans-serif; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: opacity 0.15s; }
.btn-save-reply:hover { opacity: 0.88; }
.btn-save-reply:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-cancel-reply-form { background: none; border: 1px solid var(--border); border-radius: 7px; padding: 0.5rem 0.9rem; font-family: 'DM Sans', sans-serif; font-size: 0.82rem; color: var(--text-muted); cursor: pointer; transition: all 0.15s; }
.btn-cancel-reply-form:hover { border-color: var(--accent); color: var(--accent); }
.btn-delete-review { margin-left: auto; background: none; border: 1px solid #fca5a5; border-radius: 7px; padding: 0.3rem 0.75rem; font-family: 'DM Sans', sans-serif; font-size: 0.76rem; font-weight: 500; color: #e53e3e; cursor: pointer; display: inline-flex; align-items: center; gap: 0.3rem; transition: background 0.15s, color 0.15s; }
.btn-delete-review:hover { background: #fef2f2; }
.rev-images { display: flex; flex-wrap: wrap; gap: 0.5rem; padding: 0 1.4rem 1rem; }
.rev-img-thumb { width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border); cursor: zoom-in; transition: opacity 0.15s; }
.rev-img-thumb:hover { opacity: 0.82; }
.rev-product-chip { display: flex; align-items: center; gap: 0.75rem; margin: 0 1.4rem 1rem; background: var(--bg); border: 1px solid var(--border); border-radius: 10px; padding: 0.6rem 0.85rem; max-width: 320px; }
.rev-product-chip-thumb { width: 42px; height: 42px; object-fit: cover; border-radius: 6px; flex-shrink: 0; border: 1px solid var(--border); }
.rev-product-chip-name  { font-size: 0.82rem; font-weight: 500; line-height: 1.3; }
.rev-product-chip-price { font-size: 0.76rem; color: var(--text-muted); margin-top: 0.15rem; }
.rev-empty { text-align: center; padding: 4rem 2rem; color: var(--text-muted); font-size: 0.88rem; background: #fff; border: 1px solid var(--border); border-radius: 12px; }
.rev-empty-icon { font-size: 2.5rem; margin-bottom: 0.8rem; opacity: 0.4; }
.rev-count-bar { font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.8rem; }
.rating-pill { display: inline-flex; align-items: center; gap: 0.25rem; background: #fff8e1; color: #b07c00; border: 1px solid #f0d87a; border-radius: 20px; padding: 0.15rem 0.6rem; font-size: 0.72rem; font-weight: 600; }
.rating-pill.r5 { background: #e6f9f0; color: #1a8a55; border-color: #a8e8c8; }
.rating-pill.r4 { background: #eef6ff; color: #1a5fa8; border-color: #a8d0f0; }
.rating-pill.r3 { background: #fff8e1; color: #b07c00; border-color: #f0d87a; }
.rating-pill.r2 { background: #fff0e8; color: #b05a00; border-color: #f0c0a0; }
.rating-pill.r1 { background: #fdf0f0; color: #a03030; border-color: #e8b0b0; }
</style>
</head>
<body>

<div class="shell">
  <main class="main">
    <div class="page-header">
      <div>
        <div class="page-title">Reviews</div>
        <div class="page-subtitle">Manage customer feedback and post admin replies.</div>
      </div>
    </div>

    <div class="rev-stats">
      <div class="rev-stat"><div class="rev-stat-num" id="stat-total">—</div><div class="rev-stat-label">Total Reviews</div></div>
      <div class="rev-stat"><div class="rev-stat-num" id="stat-avg">—</div><div class="rev-stat-label">Avg. Rating</div></div>
      <div class="rev-stat"><div class="rev-stat-num" id="stat-replied">—</div><div class="rev-stat-label">Replied</div></div>
      <div class="rev-stat"><div class="rev-stat-num" id="stat-pending">—</div><div class="rev-stat-label">Awaiting Reply</div></div>
    </div>

    <div class="rev-filters">
      <div class="rev-filter-group">
        <label class="rev-filter-label">Product</label>
        <select class="rev-filter-select" id="filter-product" onchange="applyFilters()"><option value="">All Products</option></select>
      </div>
      <div class="rev-filter-group">
        <label class="rev-filter-label">Rating</label>
        <select class="rev-filter-select" id="filter-rating" onchange="applyFilters()">
          <option value="">All Ratings</option>
          <option value="5">★★★★★ 5 stars</option><option value="4">★★★★☆ 4 stars</option>
          <option value="3">★★★☆☆ 3 stars</option><option value="2">★★☆☆☆ 2 stars</option>
          <option value="1">★☆☆☆☆ 1 star</option>
        </select>
      </div>
      <div class="rev-filter-group">
        <label class="rev-filter-label">Reply Status</label>
        <select class="rev-filter-select" id="filter-replied" onchange="applyFilters()">
          <option value="">All</option><option value="yes">Replied</option><option value="no">No Reply</option>
        </select>
      </div>
      <div class="rev-filter-group">
        <label class="rev-filter-label">Search</label>
        <input class="rev-filter-input" id="filter-search" type="text" placeholder="Customer name or keyword…" oninput="applyFilters()">
      </div>
      <button class="btn-filter-clear" onclick="clearFilters()">Clear</button>
    </div>

    <div class="rev-count-bar" id="rev-count-bar"></div>
    <div class="rev-list" id="rev-list">
      <div class="rev-empty"><div class="rev-empty-icon">⭐</div>Loading reviews…</div>
    </div>
  </main>
</div>

<script src="admin-data.js"></script>
<script src="admin-layout.js"></script>
<script>
checkAdminAuth();
initAdminLayout('reviews');

let _allReviews = [];

async function loadReviews() {
  try {
    const res  = await adminFetch('api/reviews.php');
    const data = await res.json();
    _allReviews = Array.isArray(data) ? data : [];
    _populateProductFilter();
    _updateStats();
    applyFilters();
  } catch(e) {
    document.getElementById('rev-list').innerHTML =
      `<div class="rev-empty"><div class="rev-empty-icon">⚠️</div>Failed to load reviews: ${e.message}</div>`;
  }
}

function _populateProductFilter() {
  const sel  = document.getElementById('filter-product');
  const seen = new Set();
  _allReviews.forEach(r => {
    if (r.product && !seen.has(r.product_id)) {
      seen.add(r.product_id);
      const opt = document.createElement('option');
      opt.value = r.product_id;
      opt.textContent = r.product;
      sel.appendChild(opt);
    }
  });
}

function _updateStats() {
  const total   = _allReviews.length;
  const avg     = total ? (_allReviews.reduce((s,r) => s + r.rating, 0) / total).toFixed(1) : '—';
  const replied = _allReviews.filter(r => r.admin_reply).length;
  document.getElementById('stat-total').textContent   = total;
  document.getElementById('stat-avg').textContent     = avg;
  document.getElementById('stat-replied').textContent = replied;
  document.getElementById('stat-pending').textContent = total - replied;
}

function applyFilters() {
  const prod    = document.getElementById('filter-product').value;
  const rating  = document.getElementById('filter-rating').value;
  const replied = document.getElementById('filter-replied').value;
  const search  = document.getElementById('filter-search').value.toLowerCase().trim();

  let filtered = _allReviews.filter(r => {
    if (prod    && String(r.product_id) !== prod) return false;
    if (rating  && r.rating !== parseInt(rating))  return false;
    if (replied === 'yes' && !r.admin_reply)        return false;
    if (replied === 'no'  && r.admin_reply)         return false;
    if (search) {
      const hay = (r.name + ' ' + (r.body || '') + ' ' + (r.product || '')).toLowerCase();
      if (!hay.includes(search)) return false;
    }
    return true;
  });

  document.getElementById('rev-count-bar').textContent =
    `Showing ${filtered.length} of ${_allReviews.length} review${_allReviews.length !== 1 ? 's' : ''}`;
  renderReviewList(filtered);
}

function clearFilters() {
  document.getElementById('filter-product').value  = '';
  document.getElementById('filter-rating').value   = '';
  document.getElementById('filter-replied').value  = '';
  document.getElementById('filter-search').value   = '';
  applyFilters();
}

function renderReviewList(reviews) {
  const list = document.getElementById('rev-list');
  if (!reviews.length) {
    list.innerHTML = `<div class="rev-empty"><div class="rev-empty-icon">⭐</div>No reviews match your filters.</div>`;
    return;
  }
  list.innerHTML = reviews.map(r => renderReviewCard(r)).join('');
}

function _stars(n) {
  return Array.from({length:5}, (_,i) => `<span${i < n ? '' : ' class="empty"'}>★</span>`).join('');
}

function _esc(s) {
  if (!s) return '';
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function renderReviewCard(r) {
  const initials = (r.name || '?').split(' ').map(w => w[0]).join('').toUpperCase().slice(0,2);
  const ratingPillCls = 'rating-pill r' + r.rating;

  const replyBlock = r.admin_reply ? `
    <div class="rev-reply-block">
      <div class="rev-reply-label">
        <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Admin Reply
      </div>
      <div class="rev-reply-text">${_esc(r.admin_reply)}</div>
      ${r.reply_date ? `<div class="rev-reply-date">${_esc(r.reply_date)}</div>` : ''}
    </div>` : '';

  const imagesHtml = (r.images && r.images.length) ? `
    <div class="rev-images">
      ${r.images.map(img => {
        const src = typeof img === 'object' ? img.path : img;
        return `<img class="rev-img-thumb" src="${_esc(src)}" alt="Review photo" loading="lazy" onclick="openAdminPhoto('${_esc(src)}')">`;
      }).join('')}
    </div>` : '';

  const productChipHtml = r.product ? `
    <div class="rev-product-chip">
      ${r.product_image ? `<img class="rev-product-chip-thumb" src="${_esc(r.product_image)}" alt="${_esc(r.product)}" loading="lazy">` : ''}
      <div>
        <div class="rev-product-chip-name">${_esc(r.product)}</div>
        ${r.product_price ? `<div class="rev-product-chip-price">PHP ${Number(r.product_price).toLocaleString()}</div>` : ''}
      </div>
    </div>` : '';

  return `
    <div class="rev-card" id="rev-card-${r.review_id}">
      <div class="rev-card-top">
        <div class="rev-avatar">${_esc(initials)}</div>
        <div class="rev-meta">
          <div class="rev-meta-name">${_esc(r.name)}</div>
          <div class="rev-meta-sub">
            <span class="rev-stars">${_stars(r.rating)}</span>
            <span class="${ratingPillCls}">★ ${r.rating}</span>
            <span>${_esc(r.date)}</span>
            ${r.product ? `<span style="color:var(--accent);font-weight:500;">· ${_esc(r.product)}</span>` : ''}
          </div>
        </div>
        <button class="btn-delete-review" onclick="deleteReview(${r.review_id})" title="Delete review">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
          Delete
        </button>
      </div>
      <div class="rev-body">${_esc(r.body)}</div>
      ${imagesHtml}
      ${replyBlock}
      ${productChipHtml}
      <div class="rev-card-footer">
        <button class="btn-open-reply" onclick="toggleReplyForm(${r.review_id})">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          ${r.admin_reply ? 'Edit Reply' : 'Reply'}
        </button>
        ${r.admin_reply ? `<button class="btn-remove-reply" onclick="clearReply(${r.review_id})">Remove Reply</button>` : ''}
      </div>
      <div class="rev-reply-form" id="reply-form-${r.review_id}">
        <div class="rev-reply-form-label">${r.admin_reply ? 'Edit Reply' : 'Reply to this review'}</div>
        <textarea class="rev-reply-textarea" id="reply-ta-${r.review_id}" placeholder="Write your response…">${_esc(r.admin_reply || '')}</textarea>
        <div class="rev-reply-actions">
          <button class="btn-save-reply" onclick="saveReply(${r.review_id})">${r.admin_reply ? 'Update Reply' : 'Post Reply'}</button>
          <button class="btn-cancel-reply-form" onclick="toggleReplyForm(${r.review_id})">Cancel</button>
        </div>
      </div>
    </div>`;
}

function toggleReplyForm(reviewId) {
  const form = document.getElementById('reply-form-' + reviewId);
  if (!form) return;
  const opening = !form.classList.contains('open');
  form.classList.toggle('open', opening);
  if (opening) setTimeout(() => form.querySelector('.rev-reply-textarea')?.focus(), 50);
}

async function saveReply(reviewId) {
  const ta   = document.getElementById('reply-ta-' + reviewId);
  const text = ta ? ta.value.trim() : '';
  if (!text) { showAdminToast('Reply cannot be empty.'); return; }

  const btn = document.querySelector(`#rev-card-${reviewId} .btn-save-reply`);
  if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }

  try {
    const res  = await adminFetch('api/reviews/reply.php', { method: 'POST', body: JSON.stringify({ review_id: reviewId, reply: text }) });
    const data = await res.json();
    if (!res.ok || data.error) throw new Error(data.error || 'Failed to save reply.');
    const idx = _allReviews.findIndex(r => r.review_id === reviewId);
    if (idx !== -1) { _allReviews[idx].admin_reply = data.reply; _allReviews[idx].reply_date = data.reply_date; }
    _updateStats();
    applyFilters();
    showAdminToast('Reply saved.');
  } catch(e) {
    showAdminToast('Error: ' + e.message);
    if (btn) { btn.disabled = false; btn.textContent = 'Post Reply'; }
  }
}

async function clearReply(reviewId) {
  if (!confirm('Remove this admin reply?')) return;
  try {
    const res  = await adminFetch('api/reviews/reply.php', { method: 'POST', body: JSON.stringify({ review_id: reviewId, reply: '' }) });
    const data = await res.json();
    if (!res.ok || data.error) throw new Error(data.error || 'Failed to clear reply.');
    const idx = _allReviews.findIndex(r => r.review_id === reviewId);
    if (idx !== -1) { _allReviews[idx].admin_reply = null; _allReviews[idx].reply_date = null; }
    _updateStats();
    applyFilters();
    showAdminToast('Reply removed.');
  } catch(e) { showAdminToast('Error: ' + e.message); }
}

async function deleteReview(reviewId) {
  if (!confirm('Permanently delete this review? This cannot be undone.')) return;
  try {
    const res  = await adminFetch('api/reviews.php', { method: 'DELETE', body: JSON.stringify({ review_id: reviewId }) });
    const data = await res.json();
    if (!res.ok || data.error) throw new Error(data.error || 'Failed to delete review.');
    _allReviews = _allReviews.filter(r => r.review_id !== reviewId);
    _updateStats();
    applyFilters();
    showAdminToast('Review deleted.');
  } catch(e) { showAdminToast('Error: ' + e.message); }
}

function showAdminToast(msg) {
  const t = document.getElementById('toast');
  if (!t) return;
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3000);
}

function openAdminPhoto(src) {
  let ov = document.getElementById('admin-photo-overlay');
  if (!ov) {
    ov = document.createElement('div');
    ov.id = 'admin-photo-overlay';
    ov.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.88);z-index:1200;display:flex;align-items:center;justify-content:center;cursor:zoom-out;';
    ov.addEventListener('click', () => ov.remove());
    document.body.appendChild(ov);
  }
  ov.innerHTML = `<img src="${src.replace(/"/g,'&quot;')}" style="max-width:92vw;max-height:90vh;border-radius:8px;object-fit:contain;" alt="Review photo">`;
  ov.style.display = 'flex';
}

loadReviews();
</script>
</body>
</html>
