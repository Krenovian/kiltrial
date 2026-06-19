<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$_posOk = !empty($_SESSION['pos_logged_in']) && (time() - ($_SESSION['pos_login_time'] ?? 0)) <= 43200;
if (!$_posOk) { unset($_SESSION['pos_logged_in'], $_SESSION['pos_login_time'], $_SESSION['pos_user_id'], $_SESSION['pos_user_name']); }

if (!$_posOk):
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Kilippadam — Cashier Login</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="toast-container" id="toastContainer"></div>
<div class="login-screen">
  <div class="login-card">
    <div class="login-brand-icon" style="padding:0;overflow:hidden"><img src="72x72.png" alt="Kilippadam" style="width:100%;height:100%;object-fit:cover;display:block;border-radius:inherit;"></div>
    <h2 class="login-title">Kilippadam</h2>
    <p class="login-sub">POS Cashier Login</p>
    <form onsubmit="doPosLogin(event)" style="margin-top:28px">
      <div class="form-group">
        <label>Username</label>
        <input type="text" id="pu" placeholder="Enter username" required autocomplete="username">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" id="pp" placeholder="Enter password" required autocomplete="current-password">
      </div>
      <div id="perr" class="login-error" style="display:none"></div>
      <button type="submit" class="bill-btn" id="posLoginBtn" style="margin-top:8px">Login →</button>
    </form>
    <p style="margin-top:20px;font-size:12px;color:var(--text-muted)">Contact admin if you need access</p>
  </div>
</div>
<script>
async function doPosLogin(e) {
  e.preventDefault();
  const btn = document.getElementById('posLoginBtn');
  const err = document.getElementById('perr');
  btn.disabled = true; btn.textContent = 'Logging in…';
  err.style.display = 'none';
  const fd = new FormData();
  fd.append('username', document.getElementById('pu').value);
  fd.append('password', document.getElementById('pp').value);
  try {
    const res = await (await fetch('api.php?action=pos_login', {method:'POST', body:fd})).json();
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
$posUserName = htmlspecialchars($_SESSION['pos_user_name'] ?? 'Cashier');
