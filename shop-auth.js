function openAuth() {
  document.getElementById("auth-overlay").classList.add("open");
  document.body.style.overflow = "hidden";
}
function closeAuth() {
  document.getElementById("auth-overlay").classList.remove("open");
  document.body.style.overflow = "";
  clearAuthErrors();
}
function closeAuthOutside(e) {
  if (e.target === document.getElementById("auth-overlay")) closeAuth();
}

function switchAuthTab(tab) {
  document
    .getElementById("panel-login")
    .classList.toggle("active", tab === "login");
  document
    .getElementById("panel-register")
    .classList.toggle("active", tab === "register");
  document
    .getElementById("tab-login")
    .classList.toggle("active", tab === "login");
  document
    .getElementById("tab-register")
    .classList.toggle("active", tab === "register");
  clearAuthErrors();
}

function clearAuthErrors() {
  ["login-error", "reg-error"].forEach((id) => {
    const el = document.getElementById(id);
    if (el) {
      el.textContent = "";
      el.classList.remove("show");
    }
  });
}

function showAuthError(id, msg) {
  const el = document.getElementById(id);
  if (el) {
    el.textContent = msg;
    el.classList.add("show");
  }
}

function setCurrentUser(user) {
  currentUser = user;
  const guest = document.getElementById("nav-auth-guest");
  const pill = document.getElementById("nav-auth-user");
  const uname = document.getElementById("nav-username");
  if (guest) guest.style.display = user ? "none" : "";
  if (pill) pill.style.display = user ? "" : "none";
  if (uname && user) uname.textContent = user.first_name;

  /* Populate dropdown header */
  const dfull = document.getElementById("dropdown-fullname");
  const demail = document.getElementById("dropdown-email");
  if (dfull)
    dfull.textContent = user ? `${user.first_name} ${user.last_name}` : "—";
  if (demail) demail.textContent = user ? user.email || "—" : "—";

  /* Review form visibility — desktop sidebar */
  const wall = document.getElementById("review-login-wall");
  const form = document.getElementById("review-form-card");
  if (wall) wall.style.display = user ? "none" : "";
  if (form) form.style.display = user ? "" : "none";
  /* Review form visibility — mobile panel */
  const mWall = document.getElementById("mobile-review-login-wall");
  const mForm = document.getElementById("mobile-review-form-card");
  if (mWall) mWall.style.display = user ? "none" : "";
  if (mForm) mForm.style.display = user ? "" : "none";

  /* Contact page — show form or login wall */
  const contactWall = document.getElementById("contact-login-wall");
  const contactForm = document.getElementById("contact-form-wrap");
  const senderName = document.getElementById("contact-sender-name");
  if (contactWall) contactWall.style.display = user ? "none" : "block";
  if (contactForm) contactForm.style.display = user ? "block" : "none";
  if (senderName && user)
    senderName.textContent = `${user.first_name} ${user.last_name}`;

  /* Refresh product modal button labels if the modal is currently open */
  const modalOverlay = document.getElementById("modal-overlay");
  if (
    currentProduct &&
    modalOverlay &&
    modalOverlay.classList.contains("open")
  ) {
    const cartBtn = document.getElementById("modal-cart-btn");
    const buyBtn = document.querySelector(".btn-buy");
    const inCart = cart.some((c) => c.product.id == currentProduct.id);
    if (cartBtn && !inCart && currentProduct.status === "available") {
      cartBtn.textContent = user ? "Add to Cart" : "Log in to Add to Cart";
      cartBtn.disabled = false;
      cartBtn.style.opacity = "";
    }
    if (buyBtn) buyBtn.textContent = user ? "Buy Now" : "Log in to Buy";
  }

  renderCart();
}

function toggleUserDropdown(e) {
  if (e) e.stopPropagation();
  const pill = document.getElementById("nav-auth-user");
  const dropdown = document.getElementById("user-dropdown");
  if (!pill || !dropdown) return;
  const isOpen = dropdown.classList.contains("open");
  dropdown.classList.toggle("open", !isOpen);
  pill.classList.toggle("open", !isOpen);
}

function _closeUserDropdown() {
  const dropdown = document.getElementById("user-dropdown");
  const pill = document.getElementById("nav-auth-user");
  if (dropdown) dropdown.classList.remove("open");
  if (pill) pill.classList.remove("open");
}

async function openEditProfile(e) {
  if (e) e.stopPropagation();
  _closeUserDropdown();
  if (!currentUser) {
    openAuth();
    return;
  }

  /* Open the modal immediately with cached session data */
  _fillProfileModal(currentUser);
  document.getElementById("profile-modal-overlay").classList.add("open");
  document.body.style.overflow = "hidden";

  /* Then refresh from API to get the latest data (phone, address, etc.) */
  try {
    const res = await shopFetch("api/profile.php");
    const data = await res.json();
    if (data && !data.error) {
      /* Merge fresh data with current session */
      const merged = { ...currentUser, ...data };
      currentUser = merged;
      localStorage.setItem("buythebella_session", JSON.stringify(merged));
      _fillProfileModal(merged);
    }
  } catch (err) {
    /* Use cached data — already filled above */
    console.warn("Could not refresh profile from API:", err);
  }
}

function _fillProfileModal(user) {
  const set = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.value = val || "";
  };
  set("prof-fname", user.first_name || "");
  set("prof-lname", user.last_name || "");
  set("prof-email", user.email || "");
  set("prof-phone", user.phone || "");
  set("prof-address", user.address || "");
  set("prof-cur-pass", "");
  set("prof-new-pass", "");
  set("prof-confirm-pass", "");
  _profileMsg("error", "");
  _profileMsg("success", "");
}

function closeEditProfile() {
  document.getElementById("profile-modal-overlay").classList.remove("open");
  document.body.style.overflow = "";
}

function closeProfileOutside(e) {
  if (e.target === document.getElementById("profile-modal-overlay"))
    closeEditProfile();
}

function _profileMsg(type, msg) {
  const el = document.getElementById("profile-" + type);
  if (!el) return;
  if (msg) {
    el.textContent = msg;
    el.classList.add("show");
    /* Auto-hide success after 4 s */
    if (type === "success") {
      clearTimeout(el._timer);
      el._timer = setTimeout(() => el.classList.remove("show"), 4000);
    }
  } else {
    el.textContent = "";
    el.classList.remove("show");
  }
}

async function saveProfile() {
  const fname = document.getElementById("prof-fname").value.trim();
  const lname = document.getElementById("prof-lname").value.trim();
  const email = document.getElementById("prof-email").value.trim();
  const phone = document.getElementById("prof-phone").value.trim();
  const address = document.getElementById("prof-address").value.trim();
  const curPass = document.getElementById("prof-cur-pass").value;
  const newPass = document.getElementById("prof-new-pass").value;
  const confirmPass = document.getElementById("prof-confirm-pass").value;

  _profileMsg("error", "");
  _profileMsg("success", "");

  if (!fname || !lname) {
    _profileMsg("error", "First and last name are required.");
    return;
  }
  if (!email) {
    _profileMsg("error", "Email is required.");
    return;
  }
  if (newPass && newPass.length < 8) {
    _profileMsg("error", "New password must be at least 8 characters.");
    return;
  }
  if (newPass && newPass !== confirmPass) {
    _profileMsg("error", "New passwords do not match.");
    return;
  }
  if (newPass && !curPass) {
    _profileMsg(
      "error",
      "Please enter your current password to set a new one.",
    );
    return;
  }

  const btn = document.getElementById("profile-save-btn");
  btn.disabled = true;
  btn.textContent = "Saving…";

  try {
    const payload = {
      first_name: fname,
      last_name: lname,
      email,
      phone,
      address,
    };
    if (newPass) {
      payload.current_password = curPass;
      payload.new_password = newPass;
    }

    const res = await shopFetch("api/profile.php", {
      method: "POST",
      body: JSON.stringify(payload),
    });
    const data = await res.json();

    if (data.error) {
      _profileMsg("error", data.error);
      return;
    }

    /* Update local session with new data */
    const updated = {
      ...currentUser,
      first_name: fname,
      last_name: lname,
      email,
      phone,
      address,
    };
    localStorage.setItem("buythebella_session", JSON.stringify(updated));
    setCurrentUser(updated);

    _profileMsg("success", "Profile updated successfully!");
    showToast("Profile updated!");

    /* Auto-close after a short delay */
    setTimeout(closeEditProfile, 1800);
  } catch (err) {
    _profileMsg("error", "Network error. Please try again.");
    console.error("saveProfile error:", err);
  } finally {
    btn.disabled = false;
    btn.textContent = "Save Changes";
  }
}

async function doLogin() {
  const email = document.getElementById("login-email").value.trim();
  const pass = document.getElementById("login-pass").value;
  if (!email || !pass) {
    showAuthError("login-error", "Please enter your email and password.");
    return;
  }

  try {
    const res = await fetch("api/auth.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "login", email, password: pass }),
    });
    const data = await res.json();
    if (data.error) {
      showAuthError("login-error", data.error);
      return;
    }
    if (data.success && data.user) {
      localStorage.setItem("buythebella_session", JSON.stringify(data.user));
      /* Admin accounts go straight to the admin dashboard */
      if (data.user.role === "admin") {
        window.location.href = "admin.php";
        return;
      }
      /* Customer accounts stay on the shop — close modal */
      setCurrentUser(data.user);
      closeAuth();
      showToast(`Welcome back, ${data.user.first_name}!`);
    } else {
      showAuthError("login-error", "Login failed. Please try again.");
    }
  } catch (e) {
    showAuthError("login-error", "Network error. Please try again.");
    console.error("doLogin error:", e);
  }
}

function togglePassVisibility(inputId, btn) {
  const input = document.getElementById(inputId);
  const isHidden = input.type === "password";
  input.type = isHidden ? "text" : "password";
  btn.querySelector(".eye-open").style.display = isHidden ? "none" : "";
  btn.querySelector(".eye-off").style.display = isHidden ? "" : "none";
}

async function doRegister() {
  const fname = document.getElementById("reg-fname").value.trim();
  const lname = document.getElementById("reg-lname").value.trim();
  const email = document.getElementById("reg-email").value.trim();
  const phone = document.getElementById("reg-phone").value.trim();
  const pass = document.getElementById("reg-pass").value;
  const confirm = document.getElementById("reg-confirm-pass").value;
  if (!fname || !lname) {
    showAuthError("reg-error", "Please enter your first and last name.");
    return;
  }
  if (!email) {
    showAuthError("reg-error", "Please enter a valid email.");
    return;
  }
  if (pass.length < 8) {
    showAuthError("reg-error", "Password must be at least 8 characters.");
    return;
  }
  if (pass !== confirm) {
    showAuthError("reg-error", "Passwords do not match. Please try again.");
    return;
  }

  try {
    const res = await fetch("api/auth.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "register",
        first_name: fname,
        last_name: lname,
        email,
        phone,
        password: pass,
      }),
    });
    const data = await res.json();
    if (data.error) {
      showAuthError("reg-error", data.error);
      return;
    }
    if (data.success && data.user) {
      localStorage.setItem("buythebella_session", JSON.stringify(data.user));
      setCurrentUser(data.user);
      closeAuth();
      showToast(`Account created! Welcome, ${fname}!`);
    } else {
      showAuthError("reg-error", "Registration failed. Please try again.");
    }
  } catch (e) {
    showAuthError("reg-error", "Network error. Please try again.");
    console.error("doRegister error:", e);
  }
}

async function logOut(e) {
  if (e) e.stopPropagation();
  _closeUserDropdown();

  const token = getShopToken();
  if (token) {
    try {
      await fetch("api/auth.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: "Bearer " + token,
        },
        body: JSON.stringify({ action: "logout", token }),
      });
    } catch (err) {
      /* ignore network errors on logout */
    }
  }
  localStorage.removeItem("buythebella_session");
  cart = [];
  saveCart();
  /* Return to shop home — user icon becomes the login trigger */
  window.location.href = "shop.php";
}

function restoreSession() {
  const stored = localStorage.getItem("buythebella_session");
  if (stored) {
    try {
      const user = JSON.parse(stored);
      if (user && user.token) setCurrentUser(user);
      else localStorage.removeItem("buythebella_session");
    } catch (e) {
      localStorage.removeItem("buythebella_session");
    }
  }
}
