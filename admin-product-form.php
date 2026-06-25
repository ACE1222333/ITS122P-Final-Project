<?php
session_start();
include('connection.php');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Product Form — BuyTheBella Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="admin-styles.css" />
  </head>
  <body>
    <div class="shell">
      <!-- sidebar injected by admin-layout.js -->
      <main class="main">
        <div class="page-header">
          <div class="page-title-row">
            <a class="back-btn" href="admin-products.php">
              <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6" /></svg>
            </a>
            <div class="page-title" id="form-title">Add Product</div>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 260px; gap: 1.5rem; align-items: start">
          <!-- â”€â”€ LEFT: main form fields â”€â”€ -->
          <div class="form-card">
            <div class="form-row single">
              <div class="form-group">
                <label class="form-label">Product Name</label>
                <input class="form-input" type="text" id="f-name" placeholder="e.g. Vintage Levi's Jacket" />
              </div>
            </div>

            <div class="form-row double">
              <div class="form-group">
                <label class="form-label">Price (₱)</label>
                <input class="form-input" type="number" id="f-price" placeholder="e.g. 450" min="0" step="1" />
              </div>
              <div class="form-group">
                <label class="form-label" for="f-size">Size</label>
                <select class="form-select" id="f-size">
                  <option value="">Select Size</option>
                  <option value="1">XS</option>
                  <option value="2">S</option>
                  <option value="3">M</option>
                  <option value="4">L</option>
                  <option value="5">XL</option>
                  <option value="6">XXL</option>
                  <option value="7">Free Size</option>
                </select>
              </div>
            </div>

            <div class="form-row single">
              <div class="form-group">
                <label class="form-label">Condition</label>
                <select class="form-select" id="f-condition">
                  <option value="">Select Condition</option>
                  <option value="Brand new">Brand new</option>
                  <option value="Like new">Like new</option>
                  <option value="Lightly used">Lightly used</option>
                  <option value="Well used">Well used</option>
                  <option value="Heavily used">Heavily used</option>
                </select>
              </div>
            </div>

            <div class="form-row single">
              <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-textarea" id="f-desc" placeholder="Describe the item, brand, material, measurements…"></textarea>
              </div>
            </div>

            <div class="form-row single">
              <div class="form-group">
                <label class="form-label">Product Images (up to 5) — click "Set Cover" to choose the thumbnail</label>
                <div class="multi-drop-zone" id="f-drop" onclick="document.getElementById('f-files').click()" ondragover="handleMultiDragOver(event, 'f-drop')" ondragleave="handleMultiDragLeave(event, 'f-drop')" ondrop="handleFormDrop(event)">
                  <input type="file" id="f-files" accept="image/*" multiple onchange="handleFormFileSelect(event)" />
                  <div class="drop-prompt">
                    <span class="drop-icon">&#128444;</span>
                    <span class="drop-label">Drag &amp; Drop Images</span>
                    <span class="drop-sub">or click to browse &middot; JPG, PNG, WEBP &middot; up to 5</span>
                  </div>
                </div>
                <div class="img-preview-grid" id="f-img-grid"></div>
              </div>
            </div>

            <div class="form-row single">
              <div class="form-group">
                <label class="form-label">Featured Product</label>
                <div class="toggle-wrap">
                  <div class="toggle-track" id="f-featured-track" onclick="toggleFeatured()">
                    <div class="toggle-thumb"></div>
                  </div>
                  <span class="toggle-label" id="f-featured-label">Not Featured</span>
                </div>
                <input type="hidden" id="f-featured" value="0" />
              </div>
            </div>

            <div class="form-actions">
              <button class="btn-save" id="submit-btn" onclick="submitForm()">Save Product</button>
              <a class="btn-cancel" href="admin-products.php">Cancel</a>
            </div>
          </div>

          <!-- â”€â”€ RIGHT: categories panel â”€â”€ -->
          <div class="form-card" style="padding: 1.2rem">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem">
              <label class="form-label" style="margin-bottom: 0; font-size: 0.8rem">Categories</label>
              <button type="button" onclick="openAddCategoryModal()" style="background: none; border: none; color: var(--accent); font-family: &quot;DM Sans&quot;, sans-serif; font-size: 0.75rem; font-weight: 600; cursor: pointer; letter-spacing: 0.04em; padding: 0; display: flex; align-items: center; gap: 0.25rem">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                  <line x1="12" y1="5" x2="12" y2="19" />
                  <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                New
              </button>
            </div>
            <div id="f-category-list" style="border: 1px solid var(--border); border-radius: 8px; max-height: 320px; overflow-y: auto; padding: 0.3rem 0; background: var(--bg); font-size: 0.83rem">
              <div style="padding: 0.5rem 0.75rem; color: var(--text-muted)">Loading…</div>
            </div>
          </div>
        </div>

        <!-- Live storefront preview (shown in edit mode only) -->
        <div id="preview-section" style="display: none">
          <div class="section-label">Storefront Preview</div>
          <div class="preview-panel">
            <div class="preview-panel-header">
              <span>User-facing product view</span>
              <span style="font-weight: 400; font-style: italic">Updates live as you edit</span>
            </div>
            <div class="preview-inner">
              <div class="preview-images">
                <img class="preview-main-img" id="preview-main-img" src="" alt="" />
                <div class="preview-thumbs" id="preview-thumbs"></div>
              </div>
              <div class="preview-info">
                <div class="preview-name" id="preview-name">Product Name</div>
                <div class="preview-price" id="preview-price">₱0</div>
                <div class="preview-meta" id="preview-size-cat">Size — | Category —</div>
                <div class="preview-meta" id="preview-condition">Condition —</div>
                <div class="preview-desc" id="preview-desc">Description will appear here.</div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- â”€â”€ Add Category Mini-Modal â”€â”€ -->
    <div id="add-cat-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 15, 15, 0.5); backdrop-filter: blur(3px); z-index: 850; align-items: center; justify-content: center">
      <div style="background: #fff; border-radius: 12px; padding: 2rem; width: 380px; max-width: 95vw; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.18)">
        <div style="font-family: &quot;Bebas Neue&quot;, sans-serif; font-size: 1.4rem; letter-spacing: 0.08em; margin-bottom: 1.2rem">New Category</div>
        <div style="margin-bottom: 0.85rem">
          <label class="form-label" for="new-cat-name">Category Name <span style="color: var(--red)">*</span></label>
          <input class="form-input" type="text" id="new-cat-name" placeholder="e.g. Vintage Tops" maxlength="100" onkeydown="if (event.key === 'Enter') saveNewCategory();" />
        </div>
        <div style="margin-bottom: 1.4rem">
          <label class="form-label" for="new-cat-desc">Description (optional)</label>
          <input class="form-input" type="text" id="new-cat-desc" placeholder="Short description" onkeydown="if (event.key === 'Enter') saveNewCategory();" />
        </div>
        <div id="new-cat-error" style="display: none; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #dc2626; border-radius: 8px; padding: 0.55rem 0.8rem; font-size: 0.82rem; margin-bottom: 1rem"></div>
        <div style="display: flex; gap: 0.75rem">
          <button class="btn-save" id="new-cat-btn" onclick="saveNewCategory()" style="flex: 1">Create Category</button>
          <button class="btn-cancel" onclick="closeAddCategoryModal()">Cancel</button>
        </div>
      </div>
    </div>

    <script src="admin-data.js"></script>
    <script src="admin-layout.js?v=2"></script>
    <script>
      initAdminLayout("products");
      checkAdminAuth();

      const editId = parseInt(new URLSearchParams(window.location.search).get("id"));
      const isEdit = !isNaN(editId);
      let formImages = [];
      let formCoverIndex = 0;
      let allCategoriesForm = [];

      async function initSelects(preserveIds) {
        try {
          const res = await fetch("api/categories.php");
          const cats = await res.json();
          if (Array.isArray(cats)) {
            allCategoriesForm = cats;
            _renderCategoryCheckboxes(cats, preserveIds ?? getCheckedCategoryIds());
          }
        } catch (e) {
          console.warn("Could not load categories from API.", e);
          const list = document.getElementById("f-category-list");
          list.innerHTML = '<div style="padding:0.5rem 0.75rem;color:var(--text-muted);">No categories found — add one first</div>';
        }
      }

      function _renderCategoryCheckboxes(cats, selectedIds = []) {
        const list = document.getElementById("f-category-list");
        if (!cats.length) {
          list.innerHTML = '<div style="padding:0.5rem 0.75rem;color:var(--text-muted);">No categories yet.</div>';
          return;
        }
        list.innerHTML = cats
          .map((c) => {
            const checked = selectedIds.includes(c.category_id) ? "checked" : "";
            return `<div style="display:flex;align-items:center;padding:0.1rem 0.5rem 0.1rem 0.75rem;transition:background 0.12s;"
                 onmouseover="this.style.background='var(--bg-section)'" onmouseout="this.style.background=''">
      <label style="display:flex;align-items:center;gap:0.55rem;flex:1;cursor:pointer;padding:0.25rem 0;">
        <input type="checkbox" value="${c.category_id}" ${checked}
               onchange="updatePreview()"
               style="width:15px;height:15px;accent-color:var(--accent);cursor:pointer;flex-shrink:0;">
        <span>${escHtmlForm(c.name)}</span>
      </label>
      <button type="button" title="Delete category"
        onclick="confirmDeleteCategory(${c.category_id}, '${escHtmlForm(c.name)}')"
        style="background:none;border:none;cursor:pointer;color:var(--text-light);padding:0.2rem 0.3rem;border-radius:5px;flex-shrink:0;transition:color 0.15s,background 0.15s;font-size:0.8rem;"
        onmouseover="this.style.color='var(--red,#e53e3e)';this.style.background='rgba(239,68,68,0.08)'"
        onmouseout="this.style.color='var(--text-light)';this.style.background='none'">✕</button>
    </div>`;
          })
          .join("");
      }

      async function confirmDeleteCategory(id, name) {
        openConfirm(
          "Delete Category?",
          `Delete "${name}"? Products using this category will have it removed.`,
          async () => {
            try {
              const data = await deleteAdminCategory(id);
              if (data.error) {
                showToast(data.error);
                return;
              }
              showToast(`"${name}" deleted.`);
              const current = getCheckedCategoryIds().filter((i) => i !== id);
              await initSelects(current);
            } catch (e) {
              showToast("Failed to delete category.");
              console.error(e);
            }
          },
          "Delete",
          true,
        );
      }

      function getCheckedCategoryIds() {
        return Array.from(document.querySelectorAll("#f-category-list input[type=checkbox]:checked")).map((el) => parseInt(el.value));
      }

      function openAddCategoryModal() {
        document.getElementById("new-cat-name").value = "";
        document.getElementById("new-cat-desc").value = "";
        document.getElementById("new-cat-error").style.display = "none";
        const overlay = document.getElementById("add-cat-overlay");
        overlay.style.display = "flex";
        setTimeout(() => document.getElementById("new-cat-name").focus(), 80);
      }

      function closeAddCategoryModal() {
        document.getElementById("add-cat-overlay").style.display = "none";
      }

      async function saveNewCategory() {
        const name = document.getElementById("new-cat-name").value.trim();
        const desc = document.getElementById("new-cat-desc").value.trim();
        const errEl = document.getElementById("new-cat-error");
        errEl.style.display = "none";

        if (!name) {
          errEl.textContent = "Category name is required.";
          errEl.style.display = "block";
          return;
        }

        const btn = document.getElementById("new-cat-btn");
        btn.disabled = true;
        btn.textContent = "Creating…";

        try {
          const data = await createAdminCategory(name, desc);
          if (data.error) {
            errEl.textContent = data.error;
            errEl.style.display = "block";
            return;
          }

          const current = getCheckedCategoryIds();
          current.push(data.category.category_id);
          await initSelects(current);
          closeAddCategoryModal();
          showToast(`Category "${name}" created!`);
        } catch (e) {
          errEl.textContent = "Network error. Please try again.";
          errEl.style.display = "block";
          console.error("saveNewCategory:", e);
        } finally {
          btn.disabled = false;
          btn.textContent = "Create Category";
        }
      }

      document.getElementById("add-cat-overlay").addEventListener("click", function (e) {
        if (e.target === this) closeAddCategoryModal();
      });

      function escHtmlForm(s) {
        return String(s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
      }

      async function initForm() {
        await initSelects();
        if (isEdit) {
          document.getElementById("form-title").textContent = "Edit Product";
          document.getElementById("submit-btn").textContent = "Update Product";

          try {
            const res = await fetch("api/products.php");
            const list = await res.json();
            const p = Array.isArray(list) ? list.find((x) => x.id === editId || x.product_id === editId) : null;

            if (!p) {
              showToast("Product not found.");
              setTimeout(() => (location.href = "admin-products.php"), 1500);
              return;
            }

            document.getElementById("f-name").value = p.name;
            document.getElementById("f-price").value = p.price;
            document.getElementById("f-desc").value = p.desc || "";
            document.getElementById("f-featured").value = p.featured ? "1" : "0";
            document.getElementById("f-condition").value = p.condition || "";
            setToggle("f-featured-track", "f-featured-label", !!p.featured);

            if (p.category_ids && p.category_ids.length) {
              _renderCategoryCheckboxes(allCategoriesForm, p.category_ids.map(Number));
            }
            if (p.size_id) {
              document.getElementById("f-size").value = p.size_id;
            }

            formImages = p.images ? [...p.images] : [];
            formCoverIndex = typeof p.cover_index === "number" ? p.cover_index : typeof p.coverIndex === "number" ? p.coverIndex : 0;
            if (formCoverIndex >= formImages.length) formCoverIndex = 0;
            refreshGrid();

            document.getElementById("preview-section").style.display = "block";
            updatePreview();

            ["f-name", "f-price", "f-condition", "f-desc"].forEach((id) => document.getElementById(id).addEventListener("input", updatePreview));
            document.getElementById("f-size").addEventListener("change", updatePreview);
          } catch (e) {
            showToast("Failed to load product data.");
            console.error(e);
          }
        }
      }

      initForm();

      function handleFormDrop(e) {
        e.preventDefault();
        document.getElementById("f-drop").classList.remove("dragover");
        uploadFiles(Array.from(e.dataTransfer.files));
      }

      function handleFormFileSelect(e) {
        uploadFiles(Array.from(e.target.files));
        e.target.value = "";
      }

      async function uploadFiles(files) {
        const remaining = 5 - formImages.length;
        if (remaining <= 0) {
          showToast("Maximum 5 images reached.");
          return;
        }
        const toUpload = files.filter((f) => f.type.startsWith("image/")).slice(0, remaining);
        if (!toUpload.length) {
          showToast("Please select valid image files.");
          return;
        }

        const token = getAdminToken();

        for (const file of toUpload) {
          if (file.size > 5 * 1024 * 1024) {
            showToast(`"${file.name}" is too large (max 5 MB).`);
            continue;
          }

          const placeholderId = "placeholder-" + Date.now() + "-" + Math.random().toString(36).slice(2);
          addLoadingPlaceholder(placeholderId);

          try {
            const fd = new FormData();
            fd.append("image", file);
            const res = await fetch("api/upload.php", {
              method: "POST",
              headers: { Authorization: "Bearer " + token },
              body: fd,
            });
            const data = await res.json();
            removePlaceholder(placeholderId);

            if (res.status === 401) {
              showToast("Session expired. Please log in again.");
              setTimeout(() => (location.href = "admin-login.php"), 1500);
              return;
            }
            if (data.error) {
              showToast("Upload failed: " + data.error);
              continue;
            }
            if (data.url) {
              formImages.push(data.url);
              refreshGrid();
            }
          } catch (e) {
            removePlaceholder(placeholderId);
            showToast("Image upload failed. Check your connection.");
            console.error("upload error:", e);
          }
        }
      }

      function addLoadingPlaceholder(id) {
        const grid = document.getElementById("f-img-grid");
        const div = document.createElement("div");
        div.className = "img-thumb-wrap";
        div.id = id;
        div.innerHTML = `<div style="display:flex;align-items:center;justify-content:center;height:100%;min-height:80px;font-size:0.75rem;color:var(--text-muted);">Uploading…</div>`;
        grid.appendChild(div);
      }

      function removePlaceholder(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
      }

      function refreshGrid() {
        const grid = document.getElementById("f-img-grid");
        const placeholders = Array.from(grid.querySelectorAll('[id^="placeholder-"]'));
        grid.innerHTML = formImages
          .map((src, i) => {
            const isCover = i === formCoverIndex;
            return `
      <div class="img-thumb-wrap${isCover ? " is-cover" : ""}">
        <img src="${src}" alt="img ${i + 1}" loading="lazy">
        <button class="img-thumb-del" onclick="removeImg(${i})">&#x2715;</button>
        ${isCover ? `<span class="img-thumb-cover">Cover</span>` : `<button class="img-thumb-set-cover" onclick="setCover(${i})">Set Cover</button>`}
      </div>`;
          })
          .join("");
        placeholders.forEach((p) => grid.appendChild(p));
        if (isEdit) updatePreview();
      }

      function removeImg(idx) {
        formImages.splice(idx, 1);
        if (formCoverIndex >= formImages.length) formCoverIndex = 0;
        else if (idx < formCoverIndex) formCoverIndex--;
        refreshGrid();
      }

      function setCover(idx) {
        formCoverIndex = idx;
        refreshGrid();
        showToast("Cover image updated.");
      }

      function toggleFeatured() {
        const input = document.getElementById("f-featured");
        const isOn = input.value === "1";
        input.value = isOn ? "0" : "1";
        setToggle("f-featured-track", "f-featured-label", !isOn);
      }

      function updatePreview() {
        const sizeSel = document.getElementById("f-size");
        const checkedCats = Array.from(document.querySelectorAll("#f-category-list input:checked")).map((el) => el.closest("label").querySelector("span").textContent);
        const catName = checkedCats.length ? checkedCats.join(", ") : "—";
        const sizeName = sizeSel.options[sizeSel.selectedIndex]?.text || "—";

        document.getElementById("preview-name").textContent = document.getElementById("f-name").value || "Product Name";
        document.getElementById("preview-price").textContent = `₱${document.getElementById("f-price").value || "0"}`;
        document.getElementById("preview-size-cat").textContent = `Size ${sizeName} | ${catName}`;
        document.getElementById("preview-condition").textContent = `Condition: ${document.getElementById("f-condition").value || "—"}`;
        document.getElementById("preview-desc").textContent = document.getElementById("f-desc").value || "Description will appear here.";

        const mainImg = document.getElementById("preview-main-img");
        const thumbsEl = document.getElementById("preview-thumbs");
        if (formImages.length) {
          const coverSrc = formImages[formCoverIndex] || formImages[0];
          mainImg.src = coverSrc;
          thumbsEl.innerHTML = formImages.map((src, i) => `<img class="preview-thumb${i === formCoverIndex ? " active" : ""}" src="${src}" alt="" onclick="setPreviewMain('${src}',this)">`).join("");
        } else {
          mainImg.src = "";
          thumbsEl.innerHTML = "";
        }
      }

      function setPreviewMain(src, el) {
        document.getElementById("preview-main-img").src = src;
        document.querySelectorAll(".preview-thumb").forEach((t) => t.classList.remove("active"));
        el.classList.add("active");
      }

      async function submitForm() {
        const name = document.getElementById("f-name").value.trim();
        const price = parseFloat(document.getElementById("f-price").value);
        const category_ids = getCheckedCategoryIds();
        const size_id = document.getElementById("f-size").value || null;
        const condition = document.getElementById("f-condition").value;
        const desc = document.getElementById("f-desc").value.trim();
        const featured = document.getElementById("f-featured").value === "1";

        if (!name) {
          showToast("Please enter a product name.");
          return;
        }
        if (!price || isNaN(price)) {
          showToast("Please enter a valid price.");
          return;
        }
        if (!condition) {
          showToast("Please select a condition.");
          return;
        }

        const btn = document.getElementById("submit-btn");
        btn.disabled = true;
        btn.textContent = "Saving…";

        const payload = {
          name,
          desc,
          price,
          category_ids,
          size_id: size_id ? parseInt(size_id) : null,
          condition,
          featured,
          cover_index: formCoverIndex,
          images: [...formImages],
        };

        try {
          let data;
          if (isEdit) {
            payload.product_id = editId;
            payload.status = "available";
            data = await updateProduct(payload);
          } else {
            data = await createProduct(payload);
          }

          if (data.error) {
            showToast(data.error);
            btn.disabled = false;
            btn.textContent = isEdit ? "Update Product" : "Save Product";
            return;
          }

          if (data.success) {
            showToast(isEdit ? `"${name}" updated!` : `"${name}" added!`);
            setTimeout(() => (location.href = "admin-products.php"), 900);
          }
        } catch (e) {
          showToast("Failed to save product. Please try again.");
          console.error("submitForm error:", e);
          btn.disabled = false;
          btn.textContent = isEdit ? "Update Product" : "Save Product";
        }
      }
    </script>
  </body>
</html>
