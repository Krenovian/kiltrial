<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Kilippadam — Admin Panel</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <div class="toast-container" id="toastContainer"></div>
  <div class="admin-layout">
    <div class="top-bar">
      <div class="brand">
        <div class="brand-icon">🔥</div>
        <div><h1>Kilippadam</h1><span>Admin Panel</span></div>
      </div>
      <div class="top-bar-actions">
        <a href="index.php" class="nav-btn">🧾 POS Billing</a>
      </div>
    </div>
    <div class="admin-container">
      <div class="admin-tabs">
        <button class="nav-btn active" onclick="showTab('dashboard',this)">📊 Dashboard</button>
        <button class="nav-btn" onclick="showTab('categories',this)">📁 Categories</button>
        <button class="nav-btn" onclick="showTab('items',this)">🍽️ Menu Items</button>
        <button class="nav-btn" onclick="showTab('bills',this)">📋 Bills</button>
        <button class="nav-btn" onclick="showTab('analytics',this)">📈 Analytics</button>
      </div>

      <!-- Dashboard -->
      <div id="tab-dashboard" class="tab-content">
        <div class="stat-grid" id="statsGrid"></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="data-card"><div class="data-card-header"><h3>🏆 Top Selling Items</h3></div>
            <div style="padding:8px 0"><table><thead><tr><th>Item</th><th>Qty</th><th>Revenue</th></tr></thead>
            <tbody id="topItemsBody"></tbody></table></div></div>
          <div class="data-card"><div class="data-card-header"><h3>🕐 Recent Bills</h3></div>
            <div style="padding:8px 0"><table><thead><tr><th>Bill #</th><th>Total</th><th>Method</th></tr></thead>
            <tbody id="recentBillsBody"></tbody></table></div></div>
        </div>
      </div>

      <!-- Categories -->
      <div id="tab-categories" class="tab-content" style="display:none">
        <div class="data-card">
          <div class="data-card-header"><h3>📁 Categories</h3>
            <button class="add-btn" onclick="showCategoryForm()">+ Add Category</button></div>
          <div class="table-scroll">
          <table><thead><tr><th>Name</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody id="categoriesBody"></tbody></table>
          </div>
          <div class="pagination" id="catPagination"></div>
        </div>
      </div>

      <!-- Items -->
      <div id="tab-items" class="tab-content" style="display:none">
        <div class="data-card">
          <div class="data-card-header"><h3>🍽️ Menu Items</h3>
            <button class="add-btn" onclick="showItemForm()">+ Add Item</button></div>
          <div class="table-scroll">
          <table><thead><tr><th>Name</th><th>Category</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody id="itemsBody"></tbody></table>
          </div>
          <div class="pagination" id="itemPagination"></div>
        </div>
      </div>

      <!-- Bills -->
      <div id="tab-bills" class="tab-content" style="display:none">
        <div class="data-card">
          <div class="data-card-header"><h3>📋 All Bills</h3>
            <input type="date" class="discount-input" style="width:auto" id="billDateFilter" onchange="billPage=1;loadBillsAdmin()"></div>
          <div class="table-scroll">
          <table><thead><tr><th>Bill #</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody id="billsBody"></tbody></table>
          </div>
          <div class="pagination" id="billPagination"></div>
        </div>
      </div>

      <!-- Analytics -->
      <div id="tab-analytics" class="tab-content" style="display:none">
        <div class="data-card-header" style="margin-bottom:16px;">
          <h2>📈 Daily Analytics</h2>
          <input type="date" class="discount-input" style="width:auto" id="analyticsDate" onchange="loadAnalytics()">
        </div>
        
        <div class="stat-grid" id="analyticsStats" style="margin-bottom:16px;">
          <!-- Populated by JS -->
        </div>

        <div style="display:grid;grid-template-columns:1fr 2fr;gap:16px">
          <!-- Add Expense Form -->
          <div class="data-card">
            <div class="data-card-header"><h3>💸 Add Expense</h3></div>
            <form onsubmit="addExpense(event)" style="padding:16px;">
              <div class="form-group">
                <label>Description</label>
                <input type="text" id="expenseDesc" placeholder="e.g. Vegetables, Rent" required>
              </div>
              <div class="form-group">
                <label>Amount (₹)</label>
                <input type="number" step="0.01" id="expenseAmount" placeholder="0.00" required>
              </div>
              <button type="submit" class="bill-btn" style="width:100%">+ Add Expense</button>
            </form>
          </div>

          <!-- Expenses List -->
          <div class="data-card">
            <div class="data-card-header"><h3>📝 Expenses List</h3></div>
            <div class="table-scroll" style="max-height:400px;">
              <table>
                <thead><tr><th>Description</th><th>Amount</th><th>Actions</th></tr></thead>
                <tbody id="expensesBody"></tbody>
              </table>
            </div>
            <div class="pagination" id="expensePagination"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Form Modal -->
  <div class="modal-overlay" id="formModal" style="display:none" onclick="if(event.target===this)closeFormModal()">
    <div class="modal">
      <div class="modal-header"><h2 id="formTitle">Add</h2>
        <button class="modal-close" onclick="closeFormModal()">✕</button></div>
      <div class="modal-body" id="formBody"></div>
    </div>
  </div>

<script>
let categories = [];
let allCategoriesData = [];
let allItemsData = [];
let allExpensesData = [];
let catPage = 1, itemPage = 1, billPage = 1, expensePage = 1;
const PER_PAGE = 10;

// ─── API ──────────────────────────────
async function api(action, params = {}) {
  const url = new URL('api.php', window.location.href);
  url.searchParams.set('action', action);
  Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
  return (await fetch(url)).json();
}
async function apiPostForm(action, formData) {
  formData.append('action', action);
  return (await fetch('api.php', { method: 'POST', body: formData })).json();
}

function showToast(msg, type = 'success') {
  const c = document.getElementById('toastContainer');
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `${type === 'success' ? '✅' : '❌'} ${msg}`;
  c.appendChild(t);
  setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 300); }, 3000);
}

// ─── TABS ─────────────────────────────
function showTab(name, el) {
  document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
  document.querySelectorAll('.admin-tabs .nav-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + name).style.display = 'block';
  el.classList.add('active');
  if (name === 'dashboard') loadDashboard();
  if (name === 'categories') loadCategories();
  if (name === 'items') loadItemsAdmin();
  if (name === 'bills') loadBillsAdmin();
  if (name === 'analytics') {
    if(!document.getElementById('analyticsDate').value) document.getElementById('analyticsDate').value = new Date().toISOString().split('T')[0];
    loadAnalytics();
  }
}

// ─── DASHBOARD ────────────────────────
async function loadDashboard() {
  const res = await api('get_dashboard');
  if (!res.success) return;
  document.getElementById('statsGrid').innerHTML = `
    <div class="stat-card"><div class="stat-icon">🧾</div><div class="stat-value">${res.today_bills}</div><div class="stat-label">Today's Bills</div></div>
    <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-value" style="color:var(--green)">₹${parseFloat(res.today_revenue).toFixed(0)}</div><div class="stat-label">Today's Revenue</div></div>
    <div class="stat-card"><div class="stat-icon">📊</div><div class="stat-value">${res.total_bills}</div><div class="stat-label">Total Bills</div></div>
    <div class="stat-card"><div class="stat-icon">🍽️</div><div class="stat-value">${res.item_count}</div><div class="stat-label">Menu Items</div></div>`;

  document.getElementById('topItemsBody').innerHTML = (res.top_items || []).map(i =>
    `<tr><td style="font-weight:600">${i.item_name}</td><td>${i.total_qty}</td><td style="color:var(--green);font-weight:600">₹${parseFloat(i.total_revenue).toFixed(0)}</td></tr>`
  ).join('') || '<tr><td colspan="3" style="text-align:center;color:var(--text-muted)">No data yet</td></tr>';

  document.getElementById('recentBillsBody').innerHTML = (res.recent_bills || []).map(b =>
    `<tr><td style="color:var(--accent);font-weight:600">${b.bill_number}</td><td style="font-weight:600">₹${parseFloat(b.total).toFixed(0)}</td><td>${b.payment_method}</td></tr>`
  ).join('') || '<tr><td colspan="3" style="text-align:center;color:var(--text-muted)">No bills yet</td></tr>';
}

// ─── CATEGORIES ───────────────────────
async function loadCategories() {
  const res = await api('get_all_categories');
  if (!res.success) return;
  categories = res.data;
  allCategoriesData = res.data;
  renderCategoriesPage();
}

function renderCategoriesPage() {
  const data = allCategoriesData;
  const total = Math.ceil(data.length / PER_PAGE);
  const paged = data.slice((catPage - 1) * PER_PAGE, catPage * PER_PAGE);
  document.getElementById('categoriesBody').innerHTML = paged.map(c => `<tr>
    <td style="font-weight:600">${c.name}</td><td>${c.sort_order}</td>
    <td><span class="status-badge ${c.active == 1 ? 'active' : 'inactive'}">${c.active == 1 ? 'Active' : 'Inactive'}</span></td>
    <td><div class="action-btns">
      <button class="action-btn" onclick='editCategory(${JSON.stringify(c)})'>Edit</button>
      <button class="action-btn delete" onclick="deleteCategory(${c.id})">Delete</button>
    </div></td></tr>`).join('') || '<tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:40px">No categories</td></tr>';
  renderPagination('catPagination', catPage, total, p => { catPage = p; renderCategoriesPage(); });
}

function showCategoryForm(cat = null) {
  document.getElementById('formTitle').textContent = cat ? 'Edit Category' : 'Add Category';
  document.getElementById('formBody').innerHTML = `
    <form onsubmit="saveCategory(event,${cat ? cat.id : 'null'})">
      <div class="form-group"><label>Name</label><input id="catName" value="${cat ? cat.name : ''}" required></div>
      <div class="form-row">
        <div class="form-group"><label>Sort Order</label><input type="number" id="catSort" value="${cat ? cat.sort_order : 0}"></div>
        <div class="form-group"><label>Status</label><select id="catActive"><option value="1" ${!cat || cat.active == 1 ? 'selected' : ''}>Active</option><option value="0" ${cat && cat.active == 0 ? 'selected' : ''}>Inactive</option></select></div>
      </div>
      <button type="submit" class="bill-btn" style="margin-top:8px">💾 Save Category</button>
    </form>`;
  document.getElementById('formModal').style.display = 'flex';
}
function editCategory(cat) { showCategoryForm(cat); }

async function saveCategory(e, id) {
  e.preventDefault();
  const fd = new FormData();
  fd.append('name', document.getElementById('catName').value);
  fd.append('icon', '🍽️');
  fd.append('sort_order', document.getElementById('catSort').value);
  fd.append('active', document.getElementById('catActive').value);
  if (id) fd.append('id', id);
  const res = await apiPostForm(id ? 'update_category' : 'add_category', fd);
  if (res.success) { showToast('Category saved!'); closeFormModal(); loadCategories(); }
  else showToast(res.error, 'error');
}

async function deleteCategory(id) {
  if (!confirm('Delete this category and all its items?')) return;
  const fd = new FormData(); fd.append('id', id); fd.append('action', 'delete_category');
  const res = await (await fetch('api.php', { method: 'POST', body: fd })).json();
  if (res.success) { showToast('Deleted!'); loadCategories(); }
  else showToast(res.error, 'error');
}

// ─── ITEMS ────────────────────────────
async function loadItemsAdmin() {
  if (categories.length === 0) { const r = await api('get_all_categories'); categories = r.data || []; }
  const res = await api('get_all_items');
  if (!res.success) return;
  allItemsData = res.data;
  renderItemsPage();
}

function renderItemsPage() {
  const data = allItemsData;
  const total = Math.ceil(data.length / PER_PAGE);
  const paged = data.slice((itemPage - 1) * PER_PAGE, itemPage * PER_PAGE);
  document.getElementById('itemsBody').innerHTML = paged.map(i => `<tr>
    <td style="font-weight:600">${i.name}</td>
    <td>${i.category_name || '-'}</td><td style="color:var(--green);font-weight:700">₹${parseFloat(i.price).toFixed(2)}</td>
    <td><span class="status-badge ${i.active == 1 ? 'active' : 'inactive'}">${i.active == 1 ? 'Active' : 'Inactive'}</span></td>
    <td><div class="action-btns">
      <button class="action-btn" onclick='editItem(${JSON.stringify(i)})'>Edit</button>
      <button class="action-btn delete" onclick="deleteItem(${i.id})">Delete</button>
    </div></td></tr>`).join('') || '<tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:40px">No items</td></tr>';
  renderPagination('itemPagination', itemPage, total, p => { itemPage = p; renderItemsPage(); });
}

function showItemForm(item = null) {
  document.getElementById('formTitle').textContent = item ? 'Edit Item' : 'Add Item';
  const catOpts = categories.map(c => `<option value="${c.id}" ${item && item.category_id == c.id ? 'selected' : ''}>${c.name}</option>`).join('');
  document.getElementById('formBody').innerHTML = `
    <form onsubmit="saveItem(event,${item ? item.id : 'null'})">
      <div class="form-group"><label>Name</label><input id="itemName" value="${item ? item.name : ''}" required></div>
      <div class="form-row">
        <div class="form-group"><label>Category</label><select id="itemCat" required>${catOpts}</select></div>
        <div class="form-group"><label>Price (₹)</label><input type="number" step="0.01" id="itemPrice" value="${item ? item.price : ''}" required></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Status</label><select id="itemActive"><option value="1" ${!item || item.active == 1 ? 'selected' : ''}>Active</option><option value="0" ${item && item.active == 0 ? 'selected' : ''}>Inactive</option></select></div>
      </div>
      <div class="form-group"><label>Description</label><textarea id="itemDesc">${item ? (item.description || '') : ''}</textarea></div>
      <button type="submit" class="bill-btn" style="margin-top:8px">💾 Save Item</button>
    </form>`;
  document.getElementById('formModal').style.display = 'flex';
}
function editItem(item) { showItemForm(item); }

async function saveItem(e, id) {
  e.preventDefault();
  const fd = new FormData();
  fd.append('name', document.getElementById('itemName').value);
  fd.append('category_id', document.getElementById('itemCat').value);
  fd.append('price', document.getElementById('itemPrice').value);
  fd.append('emoji', '🍴');
  fd.append('description', document.getElementById('itemDesc').value);
  fd.append('active', document.getElementById('itemActive').value);
  if (id) fd.append('id', id);
  const res = await apiPostForm(id ? 'update_item' : 'add_item', fd);
  if (res.success) { showToast('Item saved!'); closeFormModal(); loadItemsAdmin(); }
  else showToast(res.error, 'error');
}

async function deleteItem(id) {
  if (!confirm('Delete this item?')) return;
  const fd = new FormData(); fd.append('id', id); fd.append('action', 'delete_item');
  const res = await (await fetch('api.php', { method: 'POST', body: fd })).json();
  if (res.success) { showToast('Deleted!'); loadItemsAdmin(); }
  else showToast(res.error, 'error');
}

// ─── BILLS ────────────────────────────
async function loadBillsAdmin() {
  const date = document.getElementById('billDateFilter').value;
  const params = { page: billPage };
  if (date) params.date = date;
  const res = await api('get_bills', params);
  if (!res.success) return;
  document.getElementById('billsBody').innerHTML = res.data.map(b => `<tr>
    <td style="font-weight:600;color:var(--accent)">${b.bill_number}</td>
    <td>${new Date(b.created_at).toLocaleString('en-IN',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'})}</td>
    <td style="font-weight:700;color:var(--green)">₹${parseFloat(b.total).toFixed(2)}</td>
    <td>${b.payment_method === 'cash' ? '💵' : b.payment_method === 'card' ? '💳' : '📱'} ${b.payment_method}</td>
    <td><span class="status-badge ${b.status === 'completed' ? 'active' : 'inactive'}">${b.status}</span></td>
    <td><button class="action-btn delete" onclick="deleteBill(${b.id})">Delete</button></td>
  </tr>`).join('') || '<tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px">No bills found</td></tr>';
  renderPagination('billPagination', billPage, res.pages || 1, p => { billPage = p; loadBillsAdmin(); });
}

function closeFormModal() { document.getElementById('formModal').style.display = 'none'; }

async function deleteBill(id) {
  if (!confirm('Are you sure you want to delete this bill? This cannot be undone.')) return;
  const fd = new FormData();
  fd.append('id', id);
  fd.append('action', 'delete_bill');
  const res = await (await fetch('api.php', { method: 'POST', body: fd })).json();
  if (res.success) { showToast('Bill deleted!'); loadBillsAdmin(); }
  else showToast(res.error, 'error');
}

// ─── ANALYTICS ──────────────────────────
async function loadAnalytics() {
  const date = document.getElementById('analyticsDate').value;
  const res = await api('get_daily_stats', { date });
  if (!res.success) return;

  const income = parseFloat(res.income).toFixed(2);
  const expenses = parseFloat(res.total_expenses).toFixed(2);
  const profit = parseFloat(res.net_profit).toFixed(2);
  const profitColor = res.net_profit >= 0 ? 'var(--green)' : 'var(--red)';

  document.getElementById('analyticsStats').innerHTML = `
    <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-value" style="color:var(--green)">₹${income}</div><div class="stat-label">Total Income</div></div>
    <div class="stat-card"><div class="stat-icon">💸</div><div class="stat-value" style="color:var(--red)">₹${expenses}</div><div class="stat-label">Total Expenses</div></div>
    <div class="stat-card"><div class="stat-icon">📈</div><div class="stat-value" style="color:${profitColor}">₹${profit}</div><div class="stat-label">Net Profit</div></div>
  `;

  allExpensesData = res.expenses || [];
  expensePage = 1;
  renderExpensesPage();
}

function renderExpensesPage() {
  const data = allExpensesData;
  const total = Math.ceil(data.length / PER_PAGE);
  const paged = data.slice((expensePage - 1) * PER_PAGE, expensePage * PER_PAGE);
  
  document.getElementById('expensesBody').innerHTML = paged.map(e => `
    <tr>
      <td>${e.description}</td>
      <td style="color:var(--red);font-weight:600;">₹${parseFloat(e.amount).toFixed(2)}</td>
      <td><button class="action-btn delete" onclick="deleteExpense(${e.id})">Delete</button></td>
    </tr>
  `).join('') || '<tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:40px">No expenses for this day</td></tr>';
  
  renderPagination('expensePagination', expensePage, total, p => { expensePage = p; renderExpensesPage(); });
}

async function addExpense(e) {
  e.preventDefault();
  const date = document.getElementById('analyticsDate').value;
  const desc = document.getElementById('expenseDesc').value;
  const amount = document.getElementById('expenseAmount').value;

  const fd = new FormData();
  fd.append('action', 'add_expense');
  fd.append('date', date);
  fd.append('description', desc);
  fd.append('amount', amount);

  const res = await (await fetch('api.php', { method: 'POST', body: fd })).json();
  if (res.success) {
    showToast('Expense added!');
    document.getElementById('expenseDesc').value = '';
    document.getElementById('expenseAmount').value = '';
    loadAnalytics();
  } else {
    showToast(res.error, 'error');
  }
}

async function deleteExpense(id) {
  if (!confirm('Delete this expense?')) return;
  const fd = new FormData();
  fd.append('action', 'delete_expense');
  fd.append('id', id);
  const res = await (await fetch('api.php', { method: 'POST', body: fd })).json();
  if (res.success) {
    showToast('Expense deleted!');
    loadAnalytics();
  } else {
    showToast(res.error, 'error');
  }
}

// ─── PAGINATION RENDERER ───────────────
function renderPagination(containerId, currentPage, totalPages, onPageChange) {
  const container = document.getElementById(containerId);
  if (totalPages <= 1) { container.innerHTML = ''; return; }
  let html = '';
  html += `<button class="page-btn" ${currentPage <= 1 ? 'disabled' : ''} onclick="void(0)">← Prev</button>`;
  const start = Math.max(1, currentPage - 2);
  const end = Math.min(totalPages, currentPage + 2);
  if (start > 1) html += `<button class="page-btn" onclick="void(0)">1</button>`;
  if (start > 2) html += `<span class="page-dots">…</span>`;
  for (let i = start; i <= end; i++) {
    html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="void(0)">${i}</button>`;
  }
  if (end < totalPages - 1) html += `<span class="page-dots">…</span>`;
  if (end < totalPages) html += `<button class="page-btn" onclick="void(0)">${totalPages}</button>`;
  html += `<button class="page-btn" ${currentPage >= totalPages ? 'disabled' : ''} onclick="void(0)">Next →</button>`;
  container.innerHTML = html;
  // Attach click handlers
  container.querySelectorAll('.page-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      if (btn.disabled) return;
      const text = btn.textContent.trim();
      if (text === '← Prev') onPageChange(currentPage - 1);
      else if (text === 'Next →') onPageChange(currentPage + 1);
      else onPageChange(parseInt(text));
    });
  });
}

// Init
document.addEventListener('DOMContentLoaded', loadDashboard);
</script>
</body>
</html>
