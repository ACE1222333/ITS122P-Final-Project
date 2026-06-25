<?php
session_start();
include('connection.php');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Featured Products — BuyTheBella Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="admin-styles.css" />
  </head>
  <body>
    <div class="shell">
      <!-- sidebar injected by admin-layout.js -->
      <main class="main">
        <div class="page-header">
          <div class="page-title">Featured Products</div>
        </div>

        <p style="font-size: 0.84rem; color: var(--text-muted); margin-bottom: 1.4rem">
          Select up to 4 products to feature on the shop homepage. Changes reflect immediately on
          <a href="shop.php?from=admin" style="color: var(--accent)">shop.php</a>.
        </p>

        <div class="featured-grid" id="featured-grid"></div>

        <p style="font-size: 0.78rem; color: var(--text-light); font-style: italic">Featured products are saved to localStorage and synced with shop.php automatically.</p>
      </main>
    </div>

    <!-- Pick Product Modal -->
    <div class="pick-overlay" id="pick-overlay">
      <div class="pick-box">
        <div class="pick-box-title">Select Product</div>
        <input class="form-input" type="text" placeholder="Search products…" oninput="filterPickList(this.value)" />
        <div class="pick-list" id="pick-list"></div>
        <div class="pick-actions">
          <button class="btn-save" onclick="confirmPick()">Select</button>
          <button class="btn-cancel" onclick="closePick()">Cancel</button>
        </div>
      </div>
    </div>

    <script src="admin-data.js"></script>
    <script src="admin-layout.js?v=2"></script>
    <script>
      initAdminLayout("featured");
      checkAdminAuth();
      fetchProducts(() => renderFeaturedGrid());

      let pickSelected = null;
      let currentPickSlot = null;

      function renderFeaturedGrid() {
        const grid = document.getElementById("featured-grid");
        const featured = products.filter((p) => p.featured).slice(0, 4);

        const slots = Array.from({ length: 4 }, (_, i) => {
          const p = featured[i];
          if (p) {
            const img = getCoverImage(p);
            return `
        <div class="featured-slot filled">
          ${img ? `<img class="featured-slot-img" src="${img}" alt="${p.name}">` : '<div class="featured-slot-img" style="background:var(--bg-section)"></div>'}
          <div class="featured-slot-name">${p.name}</div>
          <div class="featured-slot-price">₱${p.price}</div>
          <button class="featured-slot-remove" onclick="removeFeatured(${p.id})" title="Remove">✕</button>
        </div>`;
          }
          return `
      <div class="featured-slot" onclick="openPick(${i})">
        <div style="font-size:1.5rem;opacity:0.3;">+</div>
        <div class="featured-slot-add-label">Add Featured</div>
        <div class="featured-slot-num">Slot ${i + 1}</div>
      </div>`;
        });

        grid.innerHTML = slots.join("");
      }

      async function removeFeatured(productId) {
        try {
          const data = await toggleProductFeatured(productId, false);
          if (data.error) {
            showToast(data.error);
            return;
          }
          await fetchProducts(() => renderFeaturedGrid());
          showToast("Product removed from featured.");
        } catch (e) {
          showToast("Failed to update featured products.");
          console.error(e);
        }
      }

      function openPick(slotIndex) {
        currentPickSlot = slotIndex;
        pickSelected = null;
        renderPickList(products.filter((p) => !p.featured));
        document.getElementById("pick-overlay").classList.add("open");
      }

      function renderPickList(list) {
        const el = document.getElementById("pick-list");
        if (!list.length) {
          el.innerHTML = '<p style="font-size:0.83rem;color:var(--text-muted);padding:0.5rem;">No available products to feature.</p>';
          return;
        }
        el.innerHTML = list
          .map((p) => {
            const img = getCoverImage(p);
            return `
      <div class="pick-item" id="pick-item-${p.id}" onclick="selectPickItem(${p.id})">
        ${img ? `<img class="pick-item-img" src="${img}" alt="${p.name}">` : '<div class="pick-item-img"></div>'}
        <div>
          <div class="pick-item-name">${p.name}</div>
          <div class="pick-item-sub">₱${p.price} · ${p.size || "—"} · ${p.condition || "—"}</div>
        </div>
      </div>`;
          })
          .join("");
      }

      function selectPickItem(id) {
        pickSelected = id;
        document.querySelectorAll(".pick-item").forEach((el) => el.classList.remove("selected"));
        const el = document.getElementById("pick-item-" + id);
        if (el) el.classList.add("selected");
      }

      function filterPickList(q) {
        renderPickList(products.filter((p) => !p.featured && p.name.toLowerCase().includes(q.toLowerCase())));
      }

      async function confirmPick() {
        if (!pickSelected) {
          showToast("Please select a product.");
          return;
        }
        if (products.filter((p) => p.featured).length >= 4) {
          showToast("Already 4 featured products.");
          return;
        }
        const p = products.find((x) => x.id === pickSelected);
        if (!p) return;
        try {
          const data = await toggleProductFeatured(pickSelected, true);
          if (data.error) {
            showToast(data.error);
            return;
          }
          closePick();
          await fetchProducts(() => renderFeaturedGrid());
          showToast(`"${p.name}" added to featured!`);
        } catch (e) {
          showToast("Failed to update featured products.");
          console.error(e);
        }
      }

      function closePick() {
        document.getElementById("pick-overlay").classList.remove("open");
        pickSelected = null;
        currentPickSlot = null;
      }
    </script>
  </body>
</html>
