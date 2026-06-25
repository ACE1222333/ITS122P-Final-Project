/* ════════════════════════════════════════════════════════════════
   PAYMENT
════════════════════════════════════════════════════════════════ */
function buildOrderSummary() {
  const linesEl = document.getElementById("order-lines");
  if (!linesEl) return;
  linesEl.innerHTML = cart
    .map(({ product: p }) => {
      const img = getCoverImage(p);
      return `
      <div class="order-line order-line-product">
        ${img ? `<img class="order-line-thumb" src="${img}" alt="${p.name}">` : '<div class="order-line-thumb order-line-thumb-placeholder"></div>'}
        <div class="order-line-info">
          <span class="order-line-name">${p.name}</span>
          <span class="order-line-size">Size: ${p.size || "—"}</span>
        </div>
        <span class="order-line-price">₱${p.price.toLocaleString()}</span>
      </div>`;
    })
    .join("");
  const totEl = document.getElementById("order-total-display");
  if (totEl) totEl.textContent = "₱" + cartTotal().toLocaleString();
  const grandEl = document.getElementById("order-grand-total");
  if (grandEl) grandEl.textContent = "₱" + cartTotal().toLocaleString();
  if (currentUser) {
    const nameEl = document.getElementById("pf-name");
    const emailEl = document.getElementById("pf-email");
    const phoneEl = document.getElementById("pf-phone");
    if (nameEl && !nameEl.value)
      nameEl.value = `${currentUser.first_name} ${currentUser.last_name}`;
    if (emailEl && !emailEl.value) emailEl.value = currentUser.email || "";
    if (phoneEl && !phoneEl.value) phoneEl.value = currentUser.phone || "";
  }
}

function handleProofUpload(e) {
  const file = e.target.files[0];
  if (!file) return;
  if (file.size > 5 * 1024 * 1024) {
    showToast("File too large. Max 5 MB.");
    return;
  }
  const reader = new FileReader();
  reader.onload = (ev) => {
    document.getElementById("proof-preview-img").src = ev.target.result;
    document.getElementById("proof-preview").style.display = "block";
  };
  reader.readAsDataURL(file);
}

function toggleConfirmBtn() {
  const checkbox = document.getElementById("agree-no-refund");
  const btn = document.getElementById("btn-confirm-payment");
  if (btn) btn.disabled = !checkbox?.checked;
}

async function confirmPayment() {
  const agreed = document.getElementById("agree-no-refund")?.checked;
  if (!agreed) {
    showToast("Please agree to the No Refund Policy before proceeding.");
    return;
  }
  const name = document.getElementById("pf-name").value.trim();
  const phone = document.getElementById("pf-phone").value.trim();
  const email = document.getElementById("pf-email").value.trim();
  const ref = document.getElementById("pf-ref").value.trim();
  const proofInput = document.getElementById("proof-file");
  const proof = proofInput ? proofInput.files[0] : null;

  if (!name) {
    showToast("Please enter your full name.");
    return;
  }
  if (!phone) {
    showToast("Please enter your contact number.");
    return;
  }
  if (!ref) {
    showToast("Please enter the GCash reference number.");
    return;
  }
  if (!proof) {
    showToast("Please upload your GCash payment screenshot.");
    return;
  }

  /* Validate and compose the Philippine address */
  const address = window.phAddr ? window.phAddr.validate() : null;
  if (!address) return;
  if (!currentUser) {
    showToast("Please log in first.");
    openAuth();
    return;
  }

  /* Build FormData */
  const shippingFee = parseFloat(
    document.getElementById("pf-shipping-fee")?.value || 0,
  );
  const grandTotal = cartTotal() + shippingFee;

  const formData = new FormData();
  formData.append(
    "items",
    JSON.stringify(
      cart.map(({ product: p }) => ({
        product_id: p.id,
        price: p.price,
        name: p.name || "",
        image: (p.images && p.images[0]) || "",
      })),
    ),
  );
  formData.append("total_amount", grandTotal);
  formData.append("shipping_fee", shippingFee);
  formData.append("payment_method", "GCash");
  formData.append("reference_number", ref);
  formData.append("address", address);
  formData.append("phone", phone);
  formData.append("proof_image", proof);

  const submitBtn = document.getElementById("btn-confirm-payment");
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = "Submitting…";
  }

  try {
    const res = await shopFetch("api/orders.php", {
      method: "POST",
      body: formData,
    });
    const data = await res.json();
    if (data.error) {
      showToast(data.error);
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = "Confirm Payment";
      }
      return;
    }
    if (data.success) {
      cart = [];
      saveCart();
      updateCartBadge();
      document.getElementById("payment-form-view").style.display = "none";
      document.getElementById("payment-success").style.display = "block";
      /* Invalidate the orders cache so My Orders fetches fresh data */
      _ordersCache = null;
      _ordersLoaded = false;
    }
  } catch (e) {
    showToast("Network error. Please try again.");
    console.error("confirmPayment error:", e);
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = "Confirm Payment";
    }
  }
}

function resetPayment() {
  document.getElementById("payment-form-view").style.display = "";
  document.getElementById("payment-success").style.display = "none";
  const prev = document.getElementById("proof-preview");
  if (prev) prev.style.display = "none";
  const pf = document.getElementById("proof-file");
  if (pf) pf.value = "";
  ["pf-name", "pf-phone", "pf-email", "pf-ref"].forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.value = "";
  });
  if (window.phAddr) window.phAddr.reset();
  updateCartBadge();
  window.location.href = PAGE_URLS.shop;
}
