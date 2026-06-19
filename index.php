<?php require_once 'auth_pos.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Kilippadam — POS Billing</title>
  <meta name="description" content="Modern restaurant point of sale billing system">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <div class="toast-container" id="toastContainer"></div>

  <div class="app-layout">
    <!-- Mobile Cart FAB -->
    <button class="cart-fab" id="cartFab" onclick="toggleMobileCart()">
      🛒
      <span class="fab-badge" id="fabBadge" style="display:none">0</span>
    </button>
    <div class="cart-overlay" id="cartOverlay" onclick="toggleMobileCart()"></div>
    <div class="main-content">
      <!-- Top Bar -->
      <div class="top-bar">
        <div class="brand">
          <div class="brand-icon">🔥</div>
          <div><h1>Kilippadam</h1><span>Point of Sale</span></div>
        </div>
        <div class="top-bar-actions">
          <span class="nav-btn" style="cursor:default;opacity:0.75">👤 <?=$posUserName?></span>
          <button class="nav-btn" onclick="doPosLogout()" style="background:rgba(239,68,68,0.12);color:var(--red);border-color:rgba(239,68,68,0.3)">🚪 Logout</button>
          <a href="admin.php" class="nav-btn">⚙️ Admin</a>
          <button class="nav-btn" onclick="viewBillHistory()">📋 Bills</button>
        </div>
      </div>

      <div class="pos-grid">
        <!-- Menu Panel -->
        <div class="menu-panel">
          <div class="search-section">
            <div class="search-box">
              <span class="search-icon">🔍</span>
              <input type="text" id="searchInput" placeholder="Search menu items..." oninput="searchItems()">
            </div>
          </div>
          <div class="items-grid" id="itemsGrid"></div>
        </div>

        <!-- Cart Panel -->
        <div class="cart-panel">
          <div class="cart-header">
            <h2>🛒 Cart <span class="count" id="cartCount">0</span></h2>
            <div style="display:flex;align-items:center;gap:10px">
              <button class="clear-cart" onclick="clearCart()">Clear All</button>
              <button class="modal-close cart-close-mobile" onclick="toggleMobileCart()" style="display:none">✕</button>
            </div>
          </div>
          <div class="cart-items" id="cartItems">
            <div class="cart-empty">
              <div class="empty-icon">🛒</div>
              <p>Tap items to add them here</p>
            </div>
          </div>
          <div class="cart-footer">
            <div class="cart-summary">
              <div class="summary-row"><span>Subtotal</span><span id="subtotalDisplay">₹0.00</span></div>
              <div class="discount-row">
                <input type="number" class="discount-input" id="discountInput" placeholder="Discount %" min="0" max="100" value="0" oninput="updateCartTotals()">
                <span style="color:var(--text-muted);font-size:12px;align-self:center;">%</span>
              </div>
              <div class="summary-row"><span>Discount</span><span id="discountDisplay">-₹0.00</span></div>
              <div class="summary-row total"><span>Total</span><span class="amount" id="totalDisplay">₹0.00</span></div>
            </div>
            <div class="payment-methods">
              <button class="pay-btn active" onclick="selectPayment('cash',this)" id="payCash">
                <span class="pay-icon">💵</span>Cash
              </button>
              <button class="pay-btn" onclick="selectPayment('card',this)" id="payCard">
                <span class="pay-icon">💳</span>Card
              </button>
              <button class="pay-btn" onclick="selectPayment('upi',this)" id="payUpi">
                <span class="pay-icon">📱</span>UPI
              </button>
            </div>
            <button class="bill-btn" id="billBtn" onclick="generateBill()" disabled>Generate Bill →</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bill Modal -->
  <div class="modal-overlay" id="billModal" style="display:none" onclick="if(event.target===this)closeBillModal()">
    <div class="modal">
      <div class="modal-header">
        <h2>🧾 Bill Receipt</h2>
        <button class="modal-close" onclick="closeBillModal()">✕</button>
      </div>
      <div class="modal-body" id="billReceipt"></div>
      <div style="padding:0 24px 24px;display:flex;gap:10px">
        <button class="bill-btn" style="background:linear-gradient(135deg,var(--blue),#2563eb);flex:1" onclick="printBill()">🖨️ Print</button>
        <button class="bill-btn" style="flex:1" onclick="closeBillModal();clearCart()">✓ Done</button>
      </div>
    </div>
  </div>

  <!-- Bills History Modal -->
  <div class="modal-overlay" id="historyModal" style="display:none" onclick="if(event.target===this)closeHistoryModal()">
    <div class="modal" style="max-width:700px">
      <div class="modal-header">
        <h2>📋 Bill History</h2>
        <button class="modal-close" onclick="closeHistoryModal()">✕</button>
      </div>
      <div class="modal-body" id="historyContent"></div>
    </div>
  </div>

<script>
// ─── STATE ─────────────────────────────────────────
let cart = [];
let allItems = [];
let paymentMethod = 'cash';

// ─── INIT ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  loadItems();
});

// ─── API HELPERS ───────────────────────────────────
async function api(action, params = {}) {
  const url = new URL('api.php', window.location.href);
  url.searchParams.set('action', action);
  Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
  const res = await fetch(url);
  if (res.status === 401) { location.reload(); return { success: false }; }
  return res.json();
}

async function apiPost(action, data) {
  const res = await fetch('api.php?action=' + encodeURIComponent(action), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action, ...data })
  });
  if (res.status === 401) { location.reload(); return { success: false }; }
  return res.json();
}

async function doPosLogout() {
  await fetch('api.php?action=pos_logout');
  location.href = 'index.php';
}

// ─── TOAST ─────────────────────────────────────────
function showToast(msg, type = 'success') {
  const c = document.getElementById('toastContainer');
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'} ${msg}`;
  c.appendChild(t);
  setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 300); }, 3000);
}


// ─── ITEMS ─────────────────────────────────────────
async function loadItems() {
  const res = await api('get_items');
  if (!res.success) return;
  allItems = res.data;
  renderItems();
}

function renderItems() {
  const grid = document.getElementById('itemsGrid');
  const search = document.getElementById('searchInput').value.toLowerCase();
  let items = allItems;
  if (search) items = items.filter(i => i.name.toLowerCase().includes(search));

  if (items.length === 0) {
    grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--text-muted)">
      <div style="font-size:48px;margin-bottom:12px;opacity:0.4">🔍</div>
      <p>No items found</p></div>`;
    return;
  }

  grid.innerHTML = items.map(item => {
    const inCart = cart.find(c => c.id == item.id);
    return `<div class="item-tile ${inCart ? 'in-cart' : ''}" onclick="addToCart(${item.id})">
      ${inCart ? `<div class="cart-badge">${inCart.quantity}</div>` : ''}
      <div class="name">${item.name}</div>
      <div class="price">₹${parseFloat(item.price).toFixed(2)}</div>
    </div>`;
  }).join('');
}

function searchItems() { renderItems(); }

// ─── CART ──────────────────────────────────────────
function addToCart(itemId) {
  const item = allItems.find(i => i.id == itemId);
  if (!item) return;
  const existing = cart.find(c => c.id == itemId);
  if (existing) {
    existing.quantity++;
  } else {
    cart.push({ id: item.id, name: item.name, price: parseFloat(item.price), emoji: item.emoji, quantity: 1 });
  }
  updateCart();
  showToast(`${item.name} added`, 'info');
}

function updateQty(itemId, delta) {
  const item = cart.find(c => c.id == itemId);
  if (!item) return;
  item.quantity += delta;
  if (item.quantity <= 0) cart = cart.filter(c => c.id != itemId);
  updateCart();
}

function removeFromCart(itemId) {
  cart = cart.filter(c => c.id != itemId);
  updateCart();
}

function clearCart() {
  cart = [];
  document.getElementById('discountInput').value = 0;
  updateCart();
}

function updateCart() {
  renderCartItems();
  updateCartTotals();
  renderItems(); // refresh tile badges
  const count = cart.reduce((s, i) => s + i.quantity, 0);
  document.getElementById('cartCount').textContent = count;
  document.getElementById('billBtn').disabled = cart.length === 0;
  // Update FAB badge
  const fabBadge = document.getElementById('fabBadge');
  if (count > 0) { fabBadge.style.display = 'flex'; fabBadge.textContent = count; }
  else { fabBadge.style.display = 'none'; }
}

function renderCartItems() {
  const container = document.getElementById('cartItems');
  if (cart.length === 0) {
    container.innerHTML = `<div class="cart-empty"><div class="empty-icon">🛒</div><p>Tap items to add them here</p></div>`;
    return;
  }
  container.innerHTML = cart.map(item => `
    <div class="cart-item">
      <span class="item-emoji">${item.emoji || '🍴'}</span>
      <div class="item-info">
        <div class="item-name">${item.name}</div>
        <div class="item-price-each">₹${item.price.toFixed(2)} each</div>
      </div>
      <div class="qty-controls">
        <button class="qty-btn minus" onclick="updateQty(${item.id},-1)">−</button>
        <span class="qty">${item.quantity}</span>
        <button class="qty-btn" onclick="updateQty(${item.id},1)">+</button>
      </div>
      <div class="item-total">₹${(item.price * item.quantity).toFixed(2)}</div>
      <button class="remove-item" onclick="removeFromCart(${item.id})">✕</button>
    </div>
  `).join('');
}

function updateCartTotals() {
  const subtotal = cart.reduce((s, i) => s + i.price * i.quantity, 0);
  const discountPct = parseFloat(document.getElementById('discountInput').value) || 0;
  const discount = subtotal * discountPct / 100;
  const afterDiscount = subtotal - discount;
  const total = afterDiscount;

  document.getElementById('subtotalDisplay').textContent = `₹${subtotal.toFixed(2)}`;
  document.getElementById('discountDisplay').textContent = `-₹${discount.toFixed(2)}`;
  document.getElementById('totalDisplay').textContent = `₹${total.toFixed(2)}`;
}

// ─── PAYMENT ───────────────────────────────────────
function selectPayment(method, el) {
  paymentMethod = method;
  document.querySelectorAll('.pay-btn').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
}

// ─── BILL ──────────────────────────────────────────
async function generateBill() {
  if (cart.length === 0) return;
  const subtotal = cart.reduce((s, i) => s + i.price * i.quantity, 0);
  const discountPct = parseFloat(document.getElementById('discountInput').value) || 0;
  const discount = subtotal * discountPct / 100;
  const afterDiscount = subtotal - discount;
  const total = afterDiscount;

  const billData = {
    items: cart,
    subtotal: subtotal,
    tax_percent: 0,
    tax_amount: 0,
    discount_percent: discountPct,
    discount_amount: discount,
    total: total,
    payment_method: paymentMethod
  };

  try {
    const res = await apiPost('create_bill', billData);
    if (!res.success) throw new Error(res.error);
    showToast(`Bill ${res.bill_number} created!`);
    showBillReceipt(res.bill_number, billData);
  } catch (e) {
    showToast(e.message, 'error');
  }
}

function showBillReceipt(billNumber, data) {
  const now = new Date();
  const dateStr = now.toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' });
  const timeStr = now.toLocaleTimeString('en-IN', { hour:'2-digit', minute:'2-digit' });
  const payLabel = { cash: '💵 Cash', card: '💳 Card', upi: '📱 UPI' };

  document.getElementById('billReceipt').innerHTML = `
    <div class="receipt" id="receiptPrint">
      <div class="receipt-header">
        <h3>🔥 Kilippadam</h3>
        <p>${billNumber}</p>
        <p>${dateStr} • ${timeStr}</p>
      </div>
      <hr class="receipt-divider">
      <div class="receipt-items">
        <div class="receipt-item" style="font-weight:700;color:var(--text-muted);font-size:12px">
          <span class="r-name">ITEM</span><span class="r-qty">QTY</span><span class="r-total">TOTAL</span>
        </div>
        ${data.items.map(i => `
          <div class="receipt-item">
            <span class="r-name">${i.name}</span>
            <span class="r-qty">×${i.quantity}</span>
            <span class="r-total">₹${(i.price * i.quantity).toFixed(2)}</span>
          </div>
        `).join('')}
      </div>
      <hr class="receipt-divider">
      <div class="receipt-totals">
        <div class="summary-row"><span>Subtotal</span><span>₹${data.subtotal.toFixed(2)}</span></div>
        ${data.discount_amount > 0 ? `<div class="summary-row"><span>Discount (${data.discount_percent}%)</span><span style="color:var(--red)">-₹${data.discount_amount.toFixed(2)}</span></div>` : ''}
        ${data.tax_amount > 0 ? `<div class="summary-row"><span>Tax (${data.tax_percent}%)</span><span>₹${data.tax_amount.toFixed(2)}</span></div>` : ''}
        <div class="summary-row total"><span>Grand Total</span><span class="amount">₹${data.total.toFixed(2)}</span></div>
        <div class="summary-row" style="margin-top:8px"><span>Payment</span><span>${payLabel[data.payment_method]}</span></div>
      </div>
      <hr class="receipt-divider">
      <p style="text-align:center;color:var(--text-muted);font-size:12px">Thank you for dining with us! 🙏</p>
    </div>
  `;
  document.getElementById('billModal').style.display = 'flex';
}

function closeBillModal() { document.getElementById('billModal').style.display = 'none'; }

function printBill() {
  const content = document.getElementById('receiptPrint').innerHTML;
  const win = window.open('', '_blank');
  win.document.write(`<html><head><title>Bill</title><style>
    body{font-family:Inter,sans-serif;max-width:300px;margin:20px auto;font-size:13px}
    .receipt-header{text-align:center;margin-bottom:15px}
    .receipt-header h3{font-size:18px;margin-bottom:4px}
    .receipt-header p{color:#888;font-size:12px}
    .receipt-divider{border:none;border-top:1px dashed #ccc;margin:10px 0}
    .receipt-item{display:flex;justify-content:space-between;padding:4px 0}
    .r-name{flex:1}.r-qty{width:40px;text-align:center}.r-total{width:70px;text-align:right;font-weight:600}
    .summary-row{display:flex;justify-content:space-between;padding:4px 0}
    .summary-row.total{font-size:16px;font-weight:800;border-top:1px solid #ccc;padding-top:8px;margin-top:6px}
  </style></head><body>${content}</body></html>`);
  win.document.close();
  win.print();
}

// ─── BILL HISTORY ──────────────────────────────────
let historyPage = 1;

async function viewBillHistory(page = 1) {
  historyPage = page;
  const res = await api('get_bills', { page });
  if (!res.success) return;
  const bills = res.data;
  const totalPages = res.pages || 1;
  const modal = document.getElementById('historyModal');
  const content = document.getElementById('historyContent');

  if (bills.length === 0 && page === 1) {
    content.innerHTML = `<div style="text-align:center;padding:40px;color:var(--text-muted)">
      <div style="font-size:48px;margin-bottom:12px">📋</div><p>No bills yet</p></div>`;
  } else {
    let paginationHtml = '';
    if (totalPages > 1) {
      paginationHtml = `<div class="pagination" style="padding:16px 0;border-top:1px solid var(--border);margin-top:8px;">
        <button class="page-btn" ${page <= 1 ? 'disabled' : ''} onclick="viewBillHistory(${page - 1})">← Prev</button>
        <span style="color:var(--text-secondary);font-size:13px;padding:0 10px;">Page ${page} of ${totalPages}</span>
        <button class="page-btn" ${page >= totalPages ? 'disabled' : ''} onclick="viewBillHistory(${page + 1})">Next →</button>
      </div>`;
    }
    content.innerHTML = `<div class="table-scroll" style="max-height:50vh"><table><thead><tr><th>Bill #</th><th>Date</th><th>Total</th><th>Payment</th></tr></thead><tbody>
      ${bills.map(b => `<tr style="cursor:pointer" onclick="viewBillDetail(${b.id})">
        <td style="font-weight:600;color:var(--accent)">${b.bill_number}</td>
        <td>${new Date(b.created_at).toLocaleString('en-IN',{day:'2-digit',month:'short',hour:'2-digit',minute:'2-digit'})}</td>
        <td style="font-weight:700;color:var(--green)">₹${parseFloat(b.total).toFixed(2)}</td>
        <td>${b.payment_method === 'cash' ? '💵' : b.payment_method === 'card' ? '💳' : '📱'} ${b.payment_method}</td>
      </tr>`).join('')}
    </tbody></table></div>${paginationHtml}`;
  }
  modal.style.display = 'flex';
}

async function viewBillDetail(id) {
  const res = await api('get_bill_detail', { id });
  if (!res.success) return;
  const b = res.data;
  closeHistoryModal();
  showBillReceipt(b.bill_number, {
    items: b.items.map(i => ({ name: i.item_name, price: parseFloat(i.price), quantity: i.quantity })),
    subtotal: parseFloat(b.subtotal),
    tax_percent: parseFloat(b.tax_percent),
    tax_amount: parseFloat(b.tax_amount),
    discount_percent: parseFloat(b.discount_percent),
    discount_amount: parseFloat(b.discount_amount),
    total: parseFloat(b.total),
    payment_method: b.payment_method
  });
}

function closeHistoryModal() { document.getElementById('historyModal').style.display = 'none'; }

// ─── MOBILE CART TOGGLE ───────────────────────────
function toggleMobileCart() {
  const panel = document.querySelector('.cart-panel');
  const overlay = document.getElementById('cartOverlay');
  const isOpen = panel.classList.contains('open');
  panel.classList.toggle('open');
  overlay.classList.toggle('open');
}

// Show/hide close button based on viewport
function handleResize() {
  const closeBtns = document.querySelectorAll('.cart-close-mobile');
  const isMobile = window.innerWidth <= 900;
  closeBtns.forEach(b => b.style.display = isMobile ? 'flex' : 'none');
  if (!isMobile) {
    document.querySelector('.cart-panel').classList.remove('open');
    document.getElementById('cartOverlay').classList.remove('open');
  }
}
window.addEventListener('resize', handleResize);
document.addEventListener('DOMContentLoaded', handleResize);

// ─── KEYBOARD SHORTCUT ────────────────────────────
document.addEventListener('keydown', e => {
  if (e.key === '/' && document.activeElement.tagName !== 'INPUT') {
    e.preventDefault();
    document.getElementById('searchInput').focus();
  }
  if (e.key === 'Escape') {
    closeBillModal();
    closeHistoryModal();
    // Close mobile cart
    document.querySelector('.cart-panel').classList.remove('open');
    document.getElementById('cartOverlay').classList.remove('open');
  }
});
</script>
</body>
</html>
