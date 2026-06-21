<?php
session_start();
include('connection.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout &mdash; Carousell</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="shop-styles.css">
<style>

/* ── STEP PANELS ── */
.step-panel { display: none; }
.step-panel.active { display: block; }
.step-title { font-family: 'Bebas Neue', sans-serif; font-size: 1.8rem; letter-spacing: 0.08em; margin-bottom: 1.4rem; color: var(--text); }

/* ── STEP NAV ── */
.checkout-nav { display: flex; gap: 0.75rem; margin-top: 1.8rem; flex-wrap: wrap; align-items: center; }
.btn-step-back {
  background: none; border: 1.5px solid var(--border); color: var(--text-muted);
  border-radius: 10px; padding: 0.75rem 1.4rem; font-family: 'DM Sans', sans-serif;
  font-size: 0.86rem; font-weight: 500; cursor: pointer; transition: all 0.15s;
}
.btn-step-back:hover { border-color: var(--text); color: var(--text); }
.btn-step-next {
  flex: 1; background: var(--accent); color: #fff; border: none;
  border-radius: 10px; padding: 0.8rem 1.6rem; font-family: 'DM Sans', sans-serif;
  font-size: 0.86rem; font-weight: 600; cursor: pointer; transition: opacity 0.2s; letter-spacing: 0.03em;
}
.btn-step-next:hover:not(:disabled) { opacity: 0.87; }
.btn-step-next:disabled { opacity: 0.4; cursor: not-allowed; }

/* ── DELIVERY SUMMARY ── */
.review-delivery-box {
  background: var(--bg-section); border: 1px solid var(--border);
  border-radius: 12px; padding: 1rem 1.2rem; margin-bottom: 1.2rem;
}
.review-delivery-label { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.4rem; }
.review-delivery-value { font-size: 0.88rem; color: var(--text); line-height: 1.6; }
</style>
</head>
<body>

<!-- Nav, cart, auth, toast injected by shop-layout.js -->

<div class="payment-page">
  <div class="payment-inner">

    <button class="btn-back-shop" id="btn-back-shop" onclick="handleBack()">&#8592; Back to Shop</button>

    <!-- ══ SUCCESS STATE ══════════════════════════════════════════ -->
    <div class="payment-success" id="payment-success" style="display:none;">
      <div class="success-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:56px;height:56px;stroke:#22c55e;"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>
      </div>
      <h2>Payment Request Submitted!</h2>
      <p>Your payment proof has been sent. Your purchase is <strong>pending payment approval</strong>. Once approved, your order will be created and processed. Track the status below.</p>
      <div style="display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap;margin-top:0.5rem;">
        <button class="btn-primary" onclick="openMyOrders(null)">Track My Order</button>
        <button class="btn-primary" style="background:var(--bg-section);color:var(--text);border:1.5px solid var(--border);" onclick="resetPayment()">Continue Shopping</button>
      </div>
    </div>

    <!-- ══ STEPPER ══════════════════════════════════════════════ -->
    <div id="payment-form-view">


      <!-- ══ STEP 1: YOUR DETAILS ══════════════════════════════ -->
      <div class="step-panel active" id="step-1">
        <div class="step-title">Your Details</div>

        <div class="payment-card">
          <div class="row g-3">
            <div class="col-12 col-sm-6">
              <label class="form-label pf-label">Full Name <span style="color:var(--accent2)">*</span></label>
              <input class="form-control" type="text" id="pf-name" placeholder="e.g. Maria Santos">
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label pf-label">Contact Number <span style="color:var(--accent2)">*</span></label>
              <input class="form-control" type="tel" id="pf-phone" placeholder="09XX XXX XXXX">
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label pf-label">Email Address <span style="color:var(--accent2)">*</span></label>
              <input class="form-control" type="email" id="pf-email" placeholder="you@email.com">
            </div>
            <div class="col-12">
              <label class="form-label pf-label">Region <span style="color:var(--accent2)">*</span></label>
              <select class="form-select" id="pf-region" onchange="phAddr.loadProvinces(); updateShippingFee();">
                <option value="">Loading regions&hellip;</option>
              </select>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label pf-label">Province <span style="color:var(--accent2)">*</span></label>
              <select class="form-select" id="pf-province" onchange="phAddr.loadCities()" disabled>
                <option value="">Select region first</option>
              </select>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label pf-label">City / Municipality <span style="color:var(--accent2)">*</span></label>
              <select class="form-select" id="pf-city" onchange="phAddr.loadBarangays()" disabled>
                <option value="">Select province first</option>
              </select>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label pf-label">Barangay <span style="color:var(--accent2)">*</span></label>
              <select class="form-select" id="pf-barangay" disabled>
                <option value="">Select city first</option>
              </select>
            </div>
            <div class="col-12 col-sm-6">
              <label class="form-label pf-label">ZIP Code</label>
              <input class="form-control" type="text" id="pf-zip" placeholder="e.g. 1000" maxlength="4" pattern="\d{4}">
            </div>
            <div class="col-12">
              <label class="form-label pf-label">House No. / Street / Subdivision <span style="color:var(--accent2)">*</span></label>
              <input class="form-control" type="text" id="pf-street" placeholder="e.g. 123 Rizal St., Greenville Subd.">
            </div>
          </div>
        </div>

        <div class="checkout-nav">
          <button class="btn-step-next" onclick="goToStep(2)">Next: Payment &rarr;</button>
        </div>
      </div>

      <!-- ══ STEP 2: REVIEW & PAYMENT ══════════════════════════ -->
      <div class="step-panel" id="step-2">
        <div class="step-title">Payment</div>

        <!-- Delivery summary -->
        <div class="review-delivery-box">
          <div class="review-delivery-label">Delivering to</div>
          <div class="review-delivery-value" id="review-address-summary">—</div>
        </div>

        <!-- Order summary -->
        <div class="order-summary-card">
          <h3>Order Summary</h3>
          <div id="order-lines"></div>
          <div class="order-line">
            <span style="color:var(--text-muted);">Subtotal</span>
            <span id="order-total-display">&#8369;0</span>
          </div>
          <input type="hidden" id="pf-shipping-fee" value="0">
          <div class="order-line" id="shipping-estimate-row" style="display:none;">
            <span style="color:var(--text-muted);">Shipping (J&amp;T Express)</span>
            <span id="shipping-estimate-val" style="color:var(--text-muted);">—</span>
          </div>
          <div class="order-line total">
            <span>Total</span>
            <span id="order-grand-total">&#8369;0</span>
          </div>
          <div class="order-summary-shipping-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0;">
              <rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
            </svg>
            <span>Shipping via <strong>J&amp;T Express</strong> &mdash; Metro Manila &#8369;70 &bull; Luzon &#8369;100 &bull; Visayas &#8369;130 &bull; Mindanao &#8369;160.</span>
          </div>
        </div>

        <!-- GCash QR + Instructions -->
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <div class="payment-card h-100">
              <h3>GCash QR Payment</h3>
              <div class="gcash-qr-wrap">
                <img src="images/qr.jpg" alt="GCash QR Code" style="width:180px;height:180px;object-fit:contain;margin:0 auto;display:block;border-radius:10px;">
              </div>
            </div>
          </div>

          <div class="col-12 col-md-6">
            <div class="payment-card h-100">
              <h3>How to Pay</h3>
              <ol class="payment-steps">
                <li><span>Open your <strong>GCash app</strong> and tap <em>Pay QR</em>.</span></li>
                <li><span>Scan the QR code on the left.</span></li>
                <li><span>Enter the <strong>exact total amount</strong> shown above.</span></li>
                <li><span>Complete the payment and <strong>screenshot</strong> the confirmation screen.</span></li>
                <li><span>Upload the screenshot below and enter your reference number, then tap <em>Confirm Payment</em>.</span></li>
              </ol>
            </div>
          </div>
        </div>

        <!-- Proof of payment -->
        <div class="payment-card" style="margin-top:1rem;">
          <h3>Upload Proof of Payment</h3>

          <div class="row g-3 mb-3">
            <div class="col-12 col-sm-6">
              <label class="form-label pf-label">GCash Reference No. <span style="color:var(--accent2)">*</span></label>
              <input class="form-control" type="text" id="pf-ref" placeholder="e.g. 123456789012">
            </div>
            <div class="col-12 col-sm-6" style="display:flex;align-items:flex-end;">
              <div style="background:var(--bg-section);border:1px solid var(--border);border-radius:10px;padding:0.65rem 1rem;font-size:0.82rem;width:100%;">
                <div style="font-size:0.68rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:var(--text-muted);margin-bottom:0.25rem;">Amount to Pay</div>
                <div style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;letter-spacing:0.06em;" id="payment-amount-display">&#8369;0</div>
              </div>
            </div>
          </div>

          <div class="proof-upload-zone" onclick="document.getElementById('proof-file').click()">
            <input type="file" id="proof-file" accept="image/*" onchange="handleProofUpload(event)">
            <div class="proof-upload-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:32px;height:32px;opacity:0.4;"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            </div>
            <p>Click to upload your GCash screenshot</p>
            <span>PNG, JPG, JPEG &mdash; max 5 MB</span>
          </div>
          <div class="proof-preview" id="proof-preview">
            <img id="proof-preview-img" src="" alt="Proof preview">
          </div>

          <!-- Payment assurance note -->
          <div class="payment-assurance-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;flex-shrink:0;margin-top:0.1rem;">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            <div>
              <div style="font-weight:600;margin-bottom:0.3rem;">Your payment is in good hands</div>
              <p>All submitted payments are reviewed carefully and verified accurately before approval or rejection &mdash; ensuring a reliable and fair confirmation process for every order.</p>
              <p>Once your payment is reviewed, we will send updates about your <strong>payment status</strong> and <strong>order status</strong> (if approved) through <strong>SMS</strong> and <strong>email notifications</strong>. Please make sure your contact number and email address are correct.</p>
            </div>
          </div>

          <!-- Contact help -->
          <div class="payment-help-line">
            <span>Need help?</span>
            <a href="tel:+639XXXXXXXXX" title="Call / SMS" class="payment-help-icon" style="background:#25D366;"><i class="bi bi-telephone-fill"></i></a>
            <a href="mailto:" title="Email" class="payment-help-icon" style="background:#EA4335;"><i class="bi bi-envelope-fill"></i></a>
            <a href="https://facebook.com/your-page" target="_blank" rel="noopener" title="Facebook" class="payment-help-icon" style="background:#1877F2;"><i class="bi bi-facebook"></i></a>
            <a href="https://instagram.com/your-handle" target="_blank" rel="noopener" title="Instagram" class="payment-help-icon" style="background:radial-gradient(circle at 30% 110%,#f09433,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888);"><i class="bi bi-instagram"></i></a>
          </div>
        </div>

        <!-- No-refund policy -->
        <div class="no-refund-notice" style="margin-top:1rem;">
          <div class="no-refund-title">&#9888; No Refund Policy</div>
          <p>All sales are <strong>final</strong>. Once your payment is verified and your order is confirmed, we do not offer refunds, exchanges, or cancellations for any reason.</p>
          <p>Read our full <a href="shop-terms.php" target="_blank" style="color:inherit;font-weight:600;text-decoration:underline;">Terms &amp; Conditions and Privacy Policy</a>.</p>
          <label class="no-refund-agree">
            <input type="checkbox" id="agree-no-refund" onchange="toggleConfirmBtn()">
            <span>I have read and agree to the <a href="shop-terms.php" target="_blank" style="color:inherit;font-weight:600;text-decoration:underline;">Terms &amp; Conditions and No Refund Policy</a>.</span>
          </label>
        </div>

        <div class="checkout-nav">
          <button class="btn-step-back" onclick="goToStep(1)">&larr; Back</button>
          <button class="btn-step-next" id="btn-confirm-payment" onclick="confirmPayment()" disabled>Confirm Payment</button>
        </div>
      </div><!-- /step-2 -->

    </div><!-- /payment-form-view -->

  </div><!-- /payment-inner -->
</div><!-- /payment-page -->

<footer><span>&copy; 2025 Carousell. All rights reserved.</span><span style="margin-left:1.5rem;"><a href="shop-terms.php" style="color:inherit;opacity:0.6;font-size:0.78rem;text-decoration:none;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.6">Terms &amp; Privacy</a></span></footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="shop-shared.js?v=2"></script>
<script src="shop-layout.js"></script>
<script src="ph-address.js"></script>
<script>
initShopLayout('payment');

/* Redirect away if cart is empty */
if (!cart.length && document.getElementById('payment-success').style.display !== 'block') {
  showToast('Your cart is empty.');
  setTimeout(() => goPage('shop'), 1500);
}

/* Redirect if not logged in */
if (!currentUser) {
  showToast('Please log in to check out.');
  setTimeout(() => { goPage('home'); openAuth(); }, 800);
}

/* Pre-fill from session */
buildOrderSummary();
if (currentUser) {
  const n = document.getElementById('pf-name');
  const e = document.getElementById('pf-email');
  const p = document.getElementById('pf-phone');
  if (n && !n.value) n.value = `${currentUser.first_name} ${currentUser.last_name}`;
  if (e && !e.value) e.value = currentUser.email || '';
  if (p && !p.value) p.value = currentUser.phone || '';
}

/* ── STEPPER ─────────────────────────────────────────────────── */
let _currentStep = 1;

function goToStep(n) {
  if (n > _currentStep && !validateStep(_currentStep)) return;

  [1, 2].forEach(i => {
    document.getElementById('step-' + i).classList.toggle('active', i === n);
  });

  if (n === 2) {
    populateReview();
    const grandEl = document.getElementById('order-grand-total');
    const amtEl   = document.getElementById('payment-amount-display');
    if (grandEl && amtEl) amtEl.textContent = grandEl.textContent || ('&#8369;' + cartTotal().toLocaleString());
  }

  _currentStep = n;
  document.getElementById('btn-back-shop').textContent = n > 1 ? '← Back' : '← Back to Shop';
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function handleBack() {
  if (_currentStep > 1) { goToStep(_currentStep - 1); return; }
  goPage('shop');
}

/* ── VALIDATION ──────────────────────────────────────────────── */
function validateStep(n) {
  if (n === 1) {
    const name   = document.getElementById('pf-name').value.trim();
    const phone  = document.getElementById('pf-phone').value.trim();
    const email  = document.getElementById('pf-email').value.trim();
    const region = document.getElementById('pf-region').value;
    const city   = document.getElementById('pf-city').value;
    const street = document.getElementById('pf-street').value.trim();
    if (!name)   { showToast('Please enter your full name.');            return false; }
    if (!phone)  { showToast('Please enter your contact number.');       return false; }
    if (!email)  { showToast('Please enter your email address.');        return false; }
    if (!region) { showToast('Please select your region.');              return false; }
    if (!city)   { showToast('Please select your city / municipality.'); return false; }
    if (!street) { showToast('Please enter your street address.');       return false; }
  }
  return true;
}

/* ── REVIEW: populate address + shipping ─────────────────────── */
function populateReview() {
  const get = id => document.getElementById(id);
  const txt = el => el?.options?.[el.selectedIndex]?.text || el?.value || '';

  const name     = get('pf-name')?.value.trim()  || '—';
  const phone    = get('pf-phone')?.value.trim() || '—';
  const email    = get('pf-email')?.value.trim() || '—';
  const region   = txt(get('pf-region'));
  const province = txt(get('pf-province'));
  const city     = txt(get('pf-city'));
  const barangay = txt(get('pf-barangay'));
  const zip      = get('pf-zip')?.value.trim()    || '';
  const street   = get('pf-street')?.value.trim() || '';

  const addrParts = [street, barangay, city, province, zip, region].filter(Boolean);
  const summaryEl = document.getElementById('review-address-summary');
  if (summaryEl) {
    summaryEl.innerHTML =
      `<strong>${name}</strong><br>${phone} &bull; ${email}<br>${addrParts.join(', ')}`;
  }

  updateShippingFee();
}

/* ── SHIPPING FEE ────────────────────────────────────────────── */
function updateShippingFee() {
  const regionEl = document.getElementById('pf-region');
  const row      = document.getElementById('shipping-estimate-row');
  const valEl    = document.getElementById('shipping-estimate-val');
  if (!regionEl || !row || !valEl || !regionEl.value) {
    if (row) row.style.display = 'none';
    return;
  }

  const name = (regionEl.options[regionEl.selectedIndex]?.text || '').toLowerCase();
  let fee;
  if      (name.includes('national capital') || name.includes('ncr')) fee = 70;
  else if (name.includes('visaya'))                                    fee = 130;
  else if (name.includes('mindanao') || name.includes('davao') ||
           name.includes('zamboanga') || name.includes('soccsksargen') ||
           name.includes('caraga')   || name.includes('bangsamoro') ||
           name.includes('barmm'))                                     fee = 160;
  else                                                                 fee = 100;

  valEl.textContent = '₱' + fee.toLocaleString();
  row.style.display = '';

  const feeInput = document.getElementById('pf-shipping-fee');
  if (feeInput) feeInput.value = fee;

  const grandEl = document.getElementById('order-grand-total');
  if (grandEl) grandEl.textContent = '₱' + (cartTotal() + fee).toLocaleString();

  const amtEl = document.getElementById('payment-amount-display');
  if (amtEl && _currentStep === 2) amtEl.textContent = '₱' + (cartTotal() + fee).toLocaleString();
}

/* ── CONFIRM BUTTON: gated by no-refund checkbox ─────────────── */
function toggleConfirmBtn() {
  const agreed = document.getElementById('agree-no-refund')?.checked;
  const btn    = document.getElementById('btn-confirm-payment');
  if (btn) btn.disabled = !agreed;
}
</script>
</body>
</html>
