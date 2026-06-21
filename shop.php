<?php
session_start();
include('connection.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Carousell — Second-Hand Clothing</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="shop-styles.css">
</head>
<body>

<!-- Nav, modal, cart, auth, toast injected by shop-layout.js -->

<!-- ══ HERO ══════════════════════════════════════════════════════ -->
<div class="hero">
  <div class="hero-slides" id="hero-slides">
    <div class="hero-slide active" style="background-image:url('https://images.unsplash.com/photo-1483985988355-763728e1935b?w=1600&q=85&auto=format&fit=crop')"></div>
    <div class="hero-slide" style="background-image:url('https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=1600&q=85&auto=format&fit=crop')"></div>
    <div class="hero-slide" style="background-image:url('https://images.unsplash.com/photo-1496747611176-843222e1e57c?w=1600&q=85&auto=format&fit=crop')"></div>
    <div class="hero-slide" style="background-image:url('https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=1600&q=85&auto=format&fit=crop')"></div>
    <div class="hero-slide" style="background-image:url('https://images.unsplash.com/photo-1529139574466-a303027c1d8b?w=1600&q=85&auto=format&fit=crop')"></div>
  </div>
  <div class="hero-slide-overlay"></div>
  <div class="hero-content">
    <div class="hero-tag">Curated Second-Hand — 2025</div>
    <h1>Best Second-Hand Clothing<br>for the Better Planet</h1>
    <p>Every piece has a story. We curate quality pre-loved clothing so nothing goes to waste — and you get something truly one-of-a-kind.</p>
    <button class="btn-primary" onclick="goPage('shop')">Shop All</button>
  </div>
  <div class="hero-dots" id="hero-dots"></div>
</div>


<!-- ══ CATEGORY QUICK-LINKS ══════════════════════════════════════ -->
<div class="section" style="padding-bottom:1.5rem;">
  <div class="section-header">
    <div class="section-title">Shop by Category</div>
    <a class="section-link" href="shop-products.php">Browse all →</a>
  </div>
  <div class="cat-pills" id="cat-pills">
    <div class="loading-state" style="font-size:0.82rem;padding:0.5rem 0;">Loading…</div>
  </div>
</div>

<!-- ══ FEATURED PIECES ════════════════════════════════════════════ -->
<div class="section">
  <div class="section-header">
    <div class="section-title">Featured Pieces</div>
    <a class="section-link" href="shop-products.php">View all →</a>
  </div>
  <div class="featured-carousel" id="featured-carousel">
    <button class="feat-arrow feat-arrow-left"  id="feat-prev" onclick="featSlide(-1)" aria-label="Previous">&#8249;</button>
    <div class="featured-grid" id="featured-grid">
      <div class="loading-state">Loading products…</div>
    </div>
    <button class="feat-arrow feat-arrow-right" id="feat-next" onclick="featSlide(1)"  aria-label="Next">&#8250;</button>
  </div>
</div>

<!-- ══ NEW ARRIVALS ═══════════════════════════════════════════════ -->
<div class="section" style="padding-top:1rem;">
  <div class="section-header">
    <div class="section-title">New Arrivals</div>
    <a class="section-link" href="shop-products.php">See all →</a>
  </div>
  <div class="featured-grid" id="arrivals-grid">
    <div class="loading-state">Loading…</div>
  </div>
</div>

<!-- ══ HOW IT WORKS ═══════════════════════════════════════════════ -->
<div class="how-section">
  <div class="how-title">How It Works</div>
  <div class="how-steps">
    <div class="how-step">
      <div class="how-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </div>
      <div class="how-step-num">01</div>
      <div class="how-step-title">Browse</div>
      <div class="how-step-desc">Explore our curated collection of quality pre-loved clothing — filtered by size, category, and condition.</div>
    </div>
    <div class="how-connector"></div>
    <div class="how-step">
      <div class="how-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
      </div>
      <div class="how-step-num">02</div>
      <div class="how-step-title">Reserve</div>
      <div class="how-step-desc">Add to cart and pay via GCash. Your item is reserved while we verify your payment — first to pay wins.</div>
    </div>
    <div class="how-connector"></div>
    <div class="how-step">
      <div class="how-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </div>
      <div class="how-step-num">03</div>
      <div class="how-step-title">Receive</div>
      <div class="how-step-desc">Once confirmed, we pack and ship your piece straight to your door. Track every step in My Orders.</div>
    </div>
  </div>
</div>

<!-- ══ CUSTOMER REVIEWS STRIP ════════════════════════════════════ -->
<div class="section">
  <div class="section-header">
    <div class="section-title">What Customers Say</div>
    <a class="section-link" href="shop-reviews.php">All reviews →</a>
  </div>
  <div class="reviews-strip" id="reviews-strip">
    <div class="loading-state">Loading reviews…</div>
  </div>
</div>

<!-- ══ PROMO STRIP ════════════════════════════════════════════════ -->
<div class="promo-strip">
  <h2>Pre-Loved Style,<br>Zero Compromise</h2>
  <p>Each item is hand-picked, cleaned, and quality-checked. When you shop Carousell, you give clothes a second life — and your wardrobe something unique.</p>
  <button class="btn-outline" onclick="goPage('about')">Our Story</button>
</div>

<footer><span>© 2025 Carousell. All rights reserved.</span><span style="margin-left:1.5rem;"><a href="shop-terms.php" style="color:inherit;opacity:0.6;font-size:0.78rem;text-decoration:none;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.6">Terms &amp; Privacy</a></span></footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="shop-shared.js?v=2"></script>
<script src="shop-layout.js"></script>
<script>
initShopLayout('home');

/* ══ FEATURED CAROUSEL ══════════════════════════════════════════ */
const PAGE_SIZE = 4;
let _featList   = [];
let _featPage   = 0;
let _allProds   = [];

function buildFeatured(products) {
  _allProds = products;
  const featured = products.filter(p => p.featured);
  _featList = featured.length ? featured : products.slice(0, 4);
  _featPage = 0;
  renderFeatPage();
  buildArrivals(products);
  buildStats(products);
}

function featSlide(dir) {
  const maxPage = Math.ceil(_featList.length / PAGE_SIZE) - 1;
  _featPage = Math.max(0, Math.min(_featPage + dir, maxPage));
  renderFeatPage();
}

function renderFeatPage() {
  const grid    = document.getElementById('featured-grid');
  const prev    = document.getElementById('feat-prev');
  const next    = document.getElementById('feat-next');
  const maxPage = Math.ceil(_featList.length / PAGE_SIZE) - 1;

  const hasMore = _featList.length > PAGE_SIZE;
  prev.style.display = hasMore ? '' : 'none';
  next.style.display = hasMore ? '' : 'none';
  prev.disabled = _featPage === 0;
  next.disabled = _featPage >= maxPage;

  const inCart = new Set(cart.map(c => c.product.id));
  const slice  = _featList.slice(_featPage * PAGE_SIZE, (_featPage + 1) * PAGE_SIZE);

  grid.innerHTML = '';
  slice.forEach(p => buildProductCard(p, grid, inCart));
}

function buildProductCard(p, container, inCart) {
  const inCartItem    = inCart.has(p.id);
  const isSold        = p.status === 'sold';
  const isReserved    = p.status === 'reserved';
  const isUnavailable = inCartItem || isSold || isReserved;

  let overlayHtml = '';
  if (inCartItem)      overlayHtml = '<div class="sold-overlay"><div class="sold-stamp">In Cart</div></div>';
  else if (isSold)     overlayHtml = '<div class="sold-overlay"><div class="sold-stamp">Sold</div></div>';
  else if (isReserved) overlayHtml = '<div class="sold-overlay"><div class="reserved-stamp">Reserved</div></div>';

  const card = document.createElement('div');
  card.className = 'feat-card';
  card.innerHTML = `
    <div class="feat-img" style="position:relative;">
      <img src="${getCoverImage(p)}" alt="${p.name}" loading="lazy">
      ${overlayHtml}
    </div>
    <div class="feat-info">
      <div class="product-name-price-row">
        <div class="feat-name">${p.name}</div>
        <div class="feat-price-display">₱${p.price.toLocaleString()}</div>
      </div>
      ${p.condition ? `<div class="product-condition-label">${p.condition}</div>` : ''}
    </div>`;
  if (!isUnavailable) card.addEventListener('click', () => openModal(p));
  container.appendChild(card);
}

/* ══ NEW ARRIVALS ═══════════════════════════════════════════════ */
function buildArrivals(products) {
  const grid   = document.getElementById('arrivals-grid');
  const inCart = new Set(cart.map(c => c.product.id));
  const recent = [...products]
    .filter(p => p.status === 'available')
    .sort((a, b) => new Date(b.date_added) - new Date(a.date_added))
    .slice(0, 4);

  grid.innerHTML = '';
  if (!recent.length) {
    grid.innerHTML = '<div class="loading-state">No products yet.</div>';
    return;
  }
  recent.forEach(p => buildProductCard(p, grid, inCart));
}

/* ══ STATS BAR ══════════════════════════════════════════════════ */
function buildStats(products) {
  const available = products.filter(p => p.status === 'available').length;
  const sold      = products.filter(p => p.status === 'sold').length;
  document.getElementById('stat-available').textContent = available + '+';
  document.getElementById('stat-sold').textContent      = sold + '+';
}

/* ══ CATEGORY PILLS ════════════════════════════════════════════ */
async function buildCategoryPills() {
  const container = document.getElementById('cat-pills');
  try {
    const res  = await fetch('api/categories.php');
    const cats = await res.json();
    if (!Array.isArray(cats) || !cats.length) {
      container.innerHTML = '<span style="font-size:0.82rem;color:var(--text-muted);">No categories yet.</span>';
      return;
    }
    container.innerHTML = cats.map(c => `
      <a class="cat-pill" href="shop-products.php?cat=${encodeURIComponent(c.name)}">
        ${escHtml(c.name)}
      </a>`).join('');
  } catch(e) {
    container.innerHTML = '';
  }
}

/* ══ REVIEWS STRIP ═════════════════════════════════════════════ */
async function buildReviewsStrip() {
  const strip = document.getElementById('reviews-strip');
  try {
    const res  = await fetch('api/reviews.php');
    const data = await res.json();
    const list = Array.isArray(data) ? data.filter(r => r.body && r.rating >= 4).slice(0, 3) : [];
    if (!list.length) { strip.innerHTML = '<div class="loading-state" style="font-size:0.82rem;">No reviews yet.</div>'; return; }
    strip.innerHTML = list.map(r => `
      <div class="review-strip-card">
        <div class="rsc-stars">${'★'.repeat(r.rating)}${'☆'.repeat(5 - r.rating)}</div>
        <div class="rsc-body">"${escHtml(r.body)}"</div>
        <div class="rsc-footer">
          <span class="rsc-name">${escHtml(r.name)}</span>
          ${r.product ? `<span class="rsc-product">on ${escHtml(r.product)}</span>` : ''}
        </div>
      </div>`).join('');
  } catch(e) {
    strip.innerHTML = '';
  }
}

/* ══ RATING STAT ════════════════════════════════════════════════ */
async function buildRatingStat() {
  try {
    const res  = await fetch('api/reviews.php');
    const data = await res.json();
    if (Array.isArray(data) && data.length) {
      const avg = (data.reduce((s, r) => s + r.rating, 0) / data.length).toFixed(1);
      document.getElementById('stat-rating').textContent = avg + '★';
    }
  } catch(e) {}
}

/* ══ INIT ═══════════════════════════════════════════════════════ */
loadProducts(buildFeatured);
buildCategoryPills();
buildReviewsStrip();
buildRatingStat();

/* ══ HERO SLIDESHOW ════════════════════════════════════════════ */
(function() {
  const slides = document.querySelectorAll('.hero-slide');
  const dotsEl = document.getElementById('hero-dots');
  let current  = 0;

  slides.forEach((_, i) => {
    const dot = document.createElement('button');
    dot.className = 'hero-dot' + (i === 0 ? ' active' : '');
    dot.setAttribute('aria-label', 'Slide ' + (i + 1));
    dot.addEventListener('click', () => goTo(i));
    dotsEl.appendChild(dot);
  });

  function goTo(n) {
    slides[current].classList.remove('active');
    dotsEl.children[current].classList.remove('active');
    current = (n + slides.length) % slides.length;
    slides[current].classList.add('active');
    dotsEl.children[current].classList.add('active');
  }

  setInterval(() => goTo(current + 1), 5000);
})();
</script>
</body>
</html>
