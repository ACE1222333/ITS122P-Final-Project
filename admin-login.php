<?php
session_start();
include('connection.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — ByTheBel</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'DM Sans', sans-serif;
  background: #111111;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  background-image: radial-gradient(ellipse at 60% 20%, rgba(255,255,255,0.03) 0%, transparent 60%);
}
.card {
  background: #fff;
  border-radius: 18px;
  width: 100%;
  max-width: 400px;
  padding: 2.8rem 2.4rem 2.4rem;
  box-shadow: 0 32px 80px rgba(0,0,0,0.55), 0 0 0 1px rgba(255,255,255,0.05);
}
.brand-wrap {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  margin-bottom: 0.3rem;
}
.brand-icon {
  width: 36px;
  height: 36px;
  background: #1a1a1a;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.brand-icon svg { width: 18px; height: 18px; fill: #fff; }
.brand {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 1.75rem;
  letter-spacing: 0.1em;
  color: #1a1a1a;
  line-height: 1;
}
.sub {
  font-size: 0.8rem;
  color: #999;
  margin-bottom: 2.2rem;
  letter-spacing: 0.03em;
  padding-left: 1px;
}
.field {
  margin-bottom: 1.1rem;
}
label {
  display: block;
  font-size: 0.68rem;
  font-weight: 600;
  letter-spacing: 0.09em;
  text-transform: uppercase;
  color: #777;
  margin-bottom: 0.4rem;
}
.input-wrap {
  position: relative;
}
input {
  width: 100%;
  background: #f5f4f1;
  border: 1.5px solid #e8e6e1;
  border-radius: 10px;
  padding: 0.82rem 1rem;
  font-family: 'DM Sans', sans-serif;
  font-size: 0.875rem;
  color: #1a1a1a;
  outline: none;
  transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
}
input:focus {
  border-color: #1a1a1a;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(26,26,26,0.07);
}
input::placeholder { color: #c0bdb7; }
input[type="password"],
input[type="text"].pass-input {
  padding-right: 3rem;
}
.toggle-pw {
  position: absolute;
  right: 0;
  top: 0;
  height: 100%;
  width: 3rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: none;
  border: none;
  cursor: pointer;
  color: #aaa;
  transition: color 0.2s;
  padding: 0;
}
.toggle-pw:hover { color: #1a1a1a; }
.toggle-pw svg { width: 18px; height: 18px; pointer-events: none; }
.btn {
  width: 100%;
  background: #1a1a1a;
  color: #fff;
  border: none;
  border-radius: 10px;
  padding: 0.9rem;
  font-family: 'DM Sans', sans-serif;
  font-size: 0.875rem;
  font-weight: 500;
  letter-spacing: 0.07em;
  cursor: pointer;
  transition: background 0.2s, opacity 0.2s, transform 0.1s;
  margin-top: 0.5rem;
}
.btn:hover:not(:disabled) { background: #2e2e2e; }
.btn:active:not(:disabled) { transform: scale(0.99); }
.btn:disabled { opacity: 0.45; cursor: not-allowed; }
.error {
  background: rgba(239,68,68,0.08);
  border: 1px solid rgba(239,68,68,0.25);
  color: #c72020;
  border-radius: 10px;
  padding: 0.7rem 0.9rem;
  font-size: 0.815rem;
  margin-bottom: 1.2rem;
  display: none;
  line-height: 1.55;
}
.error.show { display: flex; align-items: flex-start; gap: 0.5rem; }
.error svg { flex-shrink: 0; margin-top: 1px; width: 15px; height: 15px; }
.divider {
  border: none;
  border-top: 1px solid #eeece8;
  margin: 1.6rem 0 1.2rem;
}
.back {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.3rem;
  font-size: 0.8rem;
  color: #999;
  text-decoration: none;
  transition: color 0.2s;
}
.back:hover { color: #1a1a1a; }
.back svg { width: 13px; height: 13px; }
</style>
</head>
<body>

<div class="card">
  <div class="brand-wrap">
    <div class="brand-icon">
      <!-- shield/admin icon -->
      <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L3 6v6c0 5.25 3.75 10.15 9 11.25C17.25 22.15 21 17.25 21 12V6L12 2z"/></svg>
    </div>
    <div class="brand">ByTheBel</div>
  </div>
  <div class="sub">Admin Panel &mdash; Sign in to continue</div>

  <div class="error" id="err-box">
    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
    <span id="err-msg"></span>
  </div>

  <div class="field">
    <label for="email">Email Address</label>
    <div class="input-wrap">
      <input type="email" id="email" placeholder="admin@example.com"
             autocomplete="email" onkeydown="if(event.key==='Enter')login()">
    </div>
  </div>

  <div class="field">
    <label for="pass">Password</label>
    <div class="input-wrap">
      <input type="password" id="pass" class="pass-input" placeholder="Enter your password"
             autocomplete="current-password" onkeydown="if(event.key==='Enter')login()">
      <button class="toggle-pw" type="button" onclick="togglePassword()" aria-label="Toggle password visibility" title="Show / hide password">
        <!-- eye icon (show) -->
        <svg id="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
          <circle cx="12" cy="12" r="3"/>
        </svg>
        <!-- eye-off icon (hide) — hidden by default -->
        <svg id="eye-off-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
          <path d="M17.94 17.94A10.94 10.94 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/>
          <path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/>
          <line x1="1" y1="1" x2="23" y2="23"/>
        </svg>
      </button>
    </div>
  </div>

  <button class="btn" id="btn" onclick="login()">Sign In</button>

  <hr class="divider">

  <a class="back" href="shop.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Shop
  </a>
</div>

<script>
/* Skip login page if already authenticated as admin */
(function() {
  try {
    const sess = JSON.parse(localStorage.getItem('bythebel_session') || 'null');
    if (sess && sess.token && sess.role === 'admin') {
      window.location.replace('admin.php');
    }
  } catch(e) {}
})();

function showError(msg) {
  document.getElementById('err-msg').textContent = msg;
  document.getElementById('err-box').classList.add('show');
}
function clearError() {
  document.getElementById('err-box').classList.remove('show');
}
function togglePassword() {
  const input = document.getElementById('pass');
  const eyeOn  = document.getElementById('eye-icon');
  const eyeOff = document.getElementById('eye-off-icon');
  const showing = input.type === 'text';
  input.type = showing ? 'password' : 'text';
  eyeOn.style.display  = showing ? '' : 'none';
  eyeOff.style.display = showing ? 'none' : '';
}

async function login() {
  clearError();
  const email = document.getElementById('email').value.trim();
  const pass  = document.getElementById('pass').value;

  if (!email || !pass) { showError('Please enter your email and password.'); return; }

  const btn = document.getElementById('btn');
  btn.disabled = true; btn.textContent = 'Signing in…';

  try {
    const res  = await fetch('api/auth.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ action: 'login', email, password: pass }),
    });

    let data;
    try { data = await res.json(); }
    catch(e) { showError('Server error — check that XAMPP is running and you are on http://localhost/'); return; }

    if (data.error) { showError(data.error); return; }

    if (!data.success || !data.user) { showError('Login failed. Please try again.'); return; }

    if (data.user.role !== 'admin') {
      showError('This account does not have admin access. Only admin accounts can log in here.');
      return;
    }

    /* ✓ Admin authenticated — store session and go to dashboard */
    localStorage.setItem('bythebel_session', JSON.stringify(data.user));
    window.location.href = 'admin.php';

  } catch(e) {
    showError('Network error. Make sure you are accessing via http://localhost/ (not Live Server).');
    console.error(e);
  } finally {
    btn.disabled = false; btn.textContent = 'Sign In';
  }
}
</script>
</body>
</html>
