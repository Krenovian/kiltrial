<?php
session_start();
$_ok = !empty($_SESSION['admin_logged_in']) && (time() - ($_SESSION['admin_login_time'] ?? 0)) <= 43200;
if (!$_ok) { unset($_SESSION['admin_logged_in'], $_SESSION['admin_login_time'], $_SESSION['admin_username']); }

if (!$_ok):
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Kilippadam — Admin Login</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="toast-container" id="toastContainer"></div>
<div class="login-screen">
  <div class="login-card">
    <div class="login-brand-icon" style="padding:0;overflow:hidden"><img src="72x72.png" alt="Kilippadam" style="width:100%;height:100%;object-fit:cover;display:block;border-radius:inherit;"></div>
    <h2 class="login-title">Kilippadam</h2>
    <p class="login-sub">Admin Panel Login</p>
    <form onsubmit="doLogin(event)" style="margin-top:28px">
      <div class="form-group">
        <label>Username</label>
        <input type="text" id="lu" placeholder="Enter username" required autocomplete="username">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" id="lp" placeholder="Enter password" required autocomplete="current-password">
      </div>
      <div id="lerr" class="login-error" style="display:none"></div>
      <button type="submit" class="bill-btn" id="loginBtn" style="margin-top:8px">Login →</button>
    </form>
  </div>
</div>
<script>
async function doLogin(e) {
  e.preventDefault();
  const btn = document.getElementById('loginBtn');
  const err = document.getElementById('lerr');
  btn.disabled = true; btn.textContent = 'Logging in…';
  err.style.display = 'none';
  const fd = new FormData();
  fd.append('username', document.getElementById('lu').value);
  fd.append('password', document.getElementById('lp').value);
  try {
    const res = await (await fetch('api.php?action=admin_login', {method:'POST', body:fd})).json();
    if (res.success) { location.reload(); }
    else { err.style.display = 'block'; err.textContent = res.error || 'Invalid credentials'; }
  } catch(e) { err.style.display = 'block'; err.textContent = 'Connection error. Try again.'; }
  btn.disabled = false; btn.textContent = 'Login →';
}
</script>
</body>
</html>
<?php
exit;
endif;
$adminUser = htmlspecialchars($_SESSION['admin_username'] ?? 'Admin');
