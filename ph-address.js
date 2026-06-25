/* ════════════════════════════════════════════════════════════════
   ph-address.js — Philippine address cascade (Region → Province →
   City/Municipality → Barangay) using the free PSGC GitLab API.

   API base: https://psgc.gitlab.io/api/
   Exposed as window.phAddr so shop-shared.js can call getFullAddress().
════════════════════════════════════════════════════════════════ */

(function () {
  const BASE = "https://psgc.gitlab.io/api";

  /* ── helpers ─────────────────────────────────────────────────── */
  function sel(id) {
    return document.getElementById(id);
  }

  function setOptions(selectEl, items, placeholder) {
    selectEl.innerHTML =
      `<option value="">${placeholder}</option>` +
      items
        .sort((a, b) => a.name.localeCompare(b.name))
        .map(
          (i) =>
            `<option value="${i.code}" data-name="${i.name}">${i.name}</option>`,
        )
        .join("");
    selectEl.disabled = false;
  }

  function resetAfter(...ids) {
    ids.forEach((id) => {
      const el = sel(id);
      if (!el) return;
      el.innerHTML = `<option value="">—</option>`;
      el.disabled = true;
    });
  }

  async function apiFetch(path) {
    const res = await fetch(BASE + path);
    if (!res.ok) throw new Error(`PSGC ${path} → ${res.status}`);
    return res.json();
  }

  /* NCR (code 130000000) and CAR (code 140000000) have no provinces —
     cities hang directly under the region.                           */
  const NO_PROVINCE_REGIONS = new Set(["130000000", "140000000"]);

  /* ── public API ─────────────────────────────────────────────── */
  const phAddr = {
    async init() {
      const regionEl = sel("pf-region");
      if (!regionEl) return;
      try {
        const regions = await apiFetch("/regions/");
        setOptions(regionEl, regions, "Select Region");
      } catch (e) {
        regionEl.innerHTML = '<option value="">Could not load regions</option>';
        console.error("phAddr.init:", e);
      }
    },

    async loadProvinces() {
      resetAfter("pf-province", "pf-city", "pf-barangay");
      const regionCode = sel("pf-region")?.value;
      if (!regionCode) return;

      const provinceEl = sel("pf-province");
      if (!provinceEl) return;
      provinceEl.innerHTML = '<option value="">Loading…</option>';

      try {
        if (NO_PROVINCE_REGIONS.has(regionCode)) {
          /* For NCR/CAR skip provinces, go straight to cities */
          provinceEl.innerHTML =
            '<option value="__none__" selected>N/A — No Province</option>';
          provinceEl.disabled = true;
          await phAddr._loadCitiesForRegion(regionCode);
        } else {
          const provinces = await apiFetch(`/regions/${regionCode}/provinces/`);
          setOptions(provinceEl, provinces, "Select Province");
        }
      } catch (e) {
        provinceEl.innerHTML =
          '<option value="">Error loading provinces</option>';
        console.error("phAddr.loadProvinces:", e);
      }
    },

    async _loadCitiesForRegion(regionCode) {
      const cityEl = sel("pf-city");
      if (!cityEl) return;
      cityEl.innerHTML = '<option value="">Loading…</option>';
      try {
        const cities = await apiFetch(
          `/regions/${regionCode}/cities-municipalities/`,
        );
        setOptions(cityEl, cities, "Select City / Municipality");
      } catch (e) {
        cityEl.innerHTML = '<option value="">Error loading cities</option>';
        console.error("phAddr._loadCitiesForRegion:", e);
      }
    },

    async loadCities() {
      resetAfter("pf-city", "pf-barangay");
      const provinceCode = sel("pf-province")?.value;
      if (!provinceCode || provinceCode === "__none__") return;

      const cityEl = sel("pf-city");
      if (!cityEl) return;
      cityEl.innerHTML = '<option value="">Loading…</option>';

      try {
        const cities = await apiFetch(
          `/provinces/${provinceCode}/cities-municipalities/`,
        );
        setOptions(cityEl, cities, "Select City / Municipality");
      } catch (e) {
        cityEl.innerHTML = '<option value="">Error loading cities</option>';
        console.error("phAddr.loadCities:", e);
      }
    },

    async loadBarangays() {
      resetAfter("pf-barangay");
      const cityCode = sel("pf-city")?.value;
      if (!cityCode) return;

      const bgyEl = sel("pf-barangay");
      if (!bgyEl) return;
      bgyEl.innerHTML = '<option value="">Loading…</option>';

      try {
        const barangays = await apiFetch(
          `/cities-municipalities/${cityCode}/barangays/`,
        );
        setOptions(bgyEl, barangays, "Select Barangay");
      } catch (e) {
        bgyEl.innerHTML = '<option value="">Error loading barangays</option>';
        console.error("phAddr.loadBarangays:", e);
      }
    },

    /* Returns the full formatted address string, or null if incomplete. */
    getFullAddress() {
      const street = sel("pf-street")?.value.trim();
      const bgyOpt = sel("pf-barangay");
      const cityOpt = sel("pf-city");
      const provOpt = sel("pf-province");
      const regOpt = sel("pf-region");
      const zip = sel("pf-zip")?.value.trim();

      const bgy =
        bgyOpt?.options[bgyOpt.selectedIndex]?.dataset.name || bgyOpt?.value;
      const city =
        cityOpt?.options[cityOpt.selectedIndex]?.dataset.name || cityOpt?.value;
      const prov = provOpt?.options[provOpt.selectedIndex]?.dataset.name || "";
      const reg =
        regOpt?.options[regOpt.selectedIndex]?.dataset.name || regOpt?.value;

      if (!street || !bgy || !city || !reg) return null;

      const parts = [street, bgy, city];
      if (prov && prov !== "N/A — No Province") parts.push(prov);
      parts.push(reg);
      if (zip) parts.push(zip);

      return parts.join(", ");
    },

    /* Validate and return address, or show a toast and return null. */
    validate() {
      if (!sel("pf-region")?.value) {
        showToast("Please select a Region.");
        return null;
      }
      if (!sel("pf-city")?.value) {
        showToast("Please select a City/Municipality.");
        return null;
      }
      if (!sel("pf-barangay")?.value) {
        showToast("Please select a Barangay.");
        return null;
      }
      if (!sel("pf-street")?.value.trim()) {
        showToast("Please enter your street/house details.");
        return null;
      }
      return phAddr.getFullAddress();
    },

    /* Clear all address fields (used by resetPayment). */
    reset() {
      ["pf-region", "pf-province", "pf-city", "pf-barangay"].forEach((id) => {
        const el = sel(id);
        if (el) {
          el.selectedIndex = 0;
          el.disabled = true;
        }
      });
      const regionEl = sel("pf-region");
      if (regionEl) regionEl.disabled = false;
      const streetEl = sel("pf-street");
      if (streetEl) streetEl.value = "";
      const zipEl = sel("pf-zip");
      if (zipEl) zipEl.value = "";
    },
  };

  window.phAddr = phAddr;

  /* Auto-init when DOM is ready */
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => phAddr.init());
  } else {
    phAddr.init();
  }
})();
