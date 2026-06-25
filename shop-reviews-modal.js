/* ════════════════════════════════════════════════════════════════
   PRODUCT REVIEW MODAL
   Opens from "Write a Review" button in Order History.
   Pre-selects the specific product for the review.
   PHP integration point: POST api/reviews.php
════════════════════════════════════════════════════════════════ */
let _revProductId = null;
let _revProductName = "";
let _revOrderId = null;
let _revRating = 0;

function openReviewModal(productId, productName, productImage, orderId) {
  if (!currentUser) {
    openAuth();
    showToast("Please log in to write a review.");
    return;
  }

  _revProductId = productId;
  _revProductName = productName;
  _revOrderId = orderId;
  _revRating = 0;

  /* Populate the product chip */
  const nameEl = document.getElementById("review-chip-name");
  const imgEl = document.getElementById("review-chip-img");
  if (nameEl) nameEl.textContent = productName;
  if (imgEl) {
    imgEl.src = productImage || "";
    imgEl.style.display = productImage ? "" : "none";
  }

  /* Reset form */
  const bodyEl = document.getElementById("review-modal-body");
  if (bodyEl) bodyEl.value = "";
  _setReviewStars(0);
  _revShowError("");

  document.getElementById("review-modal-overlay").classList.add("open");
  setTimeout(() => document.getElementById("review-modal-body")?.focus(), 120);
}

function closeReviewModal() {
  document.getElementById("review-modal-overlay").classList.remove("open");
  const inp = document.getElementById("review-modal-images");
  if (inp) inp.value = "";
  const prev = document.getElementById("review-modal-img-previews");
  if (prev) prev.innerHTML = "";
}

function previewModalReviewImages(input) {
  const wrap = document.getElementById("review-modal-img-previews");
  if (!wrap) return;
  wrap.innerHTML = "";
  [...input.files].slice(0, 5).forEach((file) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      const img = document.createElement("img");
      img.src = e.target.result;
      img.className = "rv-img-thumb";
      img.alt = "Preview";
      wrap.appendChild(img);
    };
    reader.readAsDataURL(file);
  });
}

function closeReviewModalOutside(e) {
  if (e.target === document.getElementById("review-modal-overlay"))
    closeReviewModal();
}

function setReviewRating(val) {
  _revRating = val;
  _setReviewStars(val);
}

function _setReviewStars(val) {
  document
    .querySelectorAll("#review-modal-stars .review-modal-star")
    .forEach((btn, i) => btn.classList.toggle("lit", i < val));
}

function _revShowError(msg) {
  const el = document.getElementById("review-modal-err");
  if (!el) return;
  el.textContent = msg;
  el.classList.toggle("show", !!msg);
}

async function submitProductReview() {
  _revShowError("");

  if (!_revRating) {
    _revShowError("Please select a star rating.");
    return;
  }
  const body = document.getElementById("review-modal-body")?.value.trim();
  if (!body) {
    _revShowError("Please write your review.");
    return;
  }

  const btn = document.getElementById("review-submit-btn");
  btn.disabled = true;
  btn.textContent = "Posting…";

  try {
    const imageInput = document.getElementById("review-modal-images");
    const fd = new FormData();
    fd.append("product_id", _revProductId || "");
    fd.append("rating", _revRating);
    fd.append("body", body);
    if (imageInput && imageInput.files.length) {
      [...imageInput.files]
        .slice(0, 5)
        .forEach((f) => fd.append("images[]", f));
    }

    const res = await shopFetch("api/reviews.php", {
      method: "POST",
      body: fd,
    });
    const data = await res.json();
    if (data.error) {
      _revShowError(data.error);
      return;
    }

    closeReviewModal();
    showToast("Review posted! Thank you.");
    if (document.getElementById("reviews-list")) loadReviews(renderReviews);
  } catch (err) {
    _revShowError("Network error. Please try again.");
    console.error("submitProductReview:", err);
  } finally {
    btn.disabled = false;
    btn.textContent = "Post Review";
  }
}

function openReviewPhoto(src) {
  let ov = document.getElementById("rv-photo-overlay");
  if (!ov) {
    ov = document.createElement("div");
    ov.id = "rv-photo-overlay";
    ov.style.cssText =
      "position:fixed;inset:0;background:rgba(0,0,0,0.88);z-index:1200;display:flex;align-items:center;justify-content:center;cursor:zoom-out;";
    ov.addEventListener("click", () => ov.remove());
    document.body.appendChild(ov);
  }
  ov.innerHTML = `<img src="${escHtml(src)}" style="max-width:92vw;max-height:90vh;border-radius:8px;object-fit:contain;" alt="Review photo">`;
  ov.style.display = "flex";
}
