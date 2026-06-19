<?php require_once 'auth_admin.php'; ?>
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
        <div class="brand-icon" style="padding:0;overflow:hidden"><img src="42x42.png" alt="Kilippadam" style="width:100%;height:100%;object-fit:cover;display:block;border-radius:inherit;"></div>
        <div><h1>Kilippadam</h1><span>Admin Panel</span></div>
      </div>
      <div class="top-bar-actions">
        <a href="index.php" class="nav-btn">🧾 POS Billing</a>
        <span class="nav-btn" style="cursor:default;opacity:0.75">👤 <?=$adminUser?></span>
        <button class="nav-btn" onclick="doLogout()" style="background:rgba(239,68,68,0.12);color:var(--red);border-color:rgba(239,68,68,0.3)">🚪 Logout</button>
      </div>
    </div>
    <div class="admin-container">
      <div class="admin-tabs">
        <button class="nav-btn active" onclick="showTab('dashboard',this)">📊 Dashboard</button>
        <button class="nav-btn" onclick="showTab('categories',this)">📁 Categories</button>
        <button class="nav-btn" onclick="showTab('items',this)">🍽️ Menu Items</button>
        <button class="nav-btn" onclick="showTab('bills',this)">📋 Bills</button>
        <button class="nav-btn" onclick="showTab('analytics',this)">📈 Analytics</button>
        <button class="nav-btn" onclick="showTab('users',this)">👥 Users</button>
        <button class="nav-btn" onclick="showTab('reports',this)">📄 Reports</button>
      </div>

      <!-- Dashboard -->
      <div id="tab-dashboard" class="tab-content">
        <div class="stat-grid" id="statsGrid"></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="data-card"><div class="data-card-header"><h3>🏆 Top Selling Items</h3></div>
            <div style="padding:8px 0"><table><thead><tr><th>#</th><th>Item</th><th>Qty</th><th>Revenue</th></tr></thead>
            <tbody id="topItemsBody"></tbody></table></div>
          <div class="pagination" id="topItemsPagination"></div></div>
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

      <!-- Users -->
      <div id="tab-users" class="tab-content" style="display:none">
        <!-- Admin Profile -->
        <div class="data-card" style="margin-bottom:20px">
          <div class="data-card-header">
            <h3>🔐 Admin Profile</h3>
            <span style="font-size:12px;color:var(--text-muted)">Currently logged in as <strong style="color:var(--accent)"><?=$adminUser?></strong></span>
          </div>
          <form onsubmit="updateAdminCredentials(event)" style="padding:20px">
            <div class="form-row">
              <div class="form-group">
                <label>New Username</label>
                <input type="text" id="adminNewUsername" placeholder="<?=$adminUser?>" value="<?=$adminUser?>" required>
              </div>
              <div class="form-group">
                <label>Current Password <span style="color:var(--red)">*</span></label>
                <input type="password" id="adminCurrentPass" placeholder="Required to save changes" required>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>New Password <span style="color:var(--text-muted)">(leave blank to keep)</span></label>
                <input type="password" id="adminNewPass" placeholder="New password">
              </div>
              <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" id="adminConfirmPass" placeholder="Repeat new password">
              </div>
            </div>
            <button type="submit" class="add-btn">💾 Update Credentials</button>
          </form>
        </div>

        <!-- POS Cashier Users -->
        <div class="data-card">
          <div class="data-card-header"><h3>👥 POS Cashier Users</h3>
            <button class="add-btn" onclick="showUserForm()">+ Add User</button></div>
          <div class="table-scroll">
          <table><thead><tr><th>Name</th><th>Username</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody id="usersBody"></tbody></table>
          </div>
          <div class="pagination" id="userPagination"></div>
        </div>
      </div>

      <!-- Reports -->
      <div id="tab-reports" class="tab-content" style="display:none">
        <div class="data-card">
          <div class="data-card-header"><h3>📄 Sales Report</h3></div>
          <div style="padding:24px">
            <p style="color:var(--text-secondary);font-size:13px;margin-bottom:20px">Select a date range to generate a PDF report with income, expenses, and net profit — broken down per day.</p>
            <div class="form-row" style="max-width:500px">
              <div class="form-group">
                <label>From Date</label>
                <input type="date" id="reportFrom" class="discount-input" style="width:100%">
              </div>
              <div class="form-group">
                <label>To Date</label>
                <input type="date" id="reportTo" class="discount-input" style="width:100%">
              </div>
            </div>
            <button class="add-btn" onclick="downloadReport()" style="margin-top:8px;padding:12px 28px;font-size:14px">📥 Download PDF Report</button>
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
let categories = [], allCategoriesData = [], allItemsData = [], allExpensesData = [], allUsersData = [], topItemsData = [];
let catPage = 1, itemPage = 1, billPage = 1, expensePage = 1, userPage = 1, topItemsPage = 1;
const PER_PAGE = 10;

// ─── API ──────────────────────────────
async function api(action, params = {}) {
  const url = new URL('api.php', window.location.href);
  url.searchParams.set('action', action);
  Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
  const res = await fetch(url);
  if (res.status === 401) { location.reload(); return { success: false }; }
  return res.json();
}
async function apiPostForm(action, formData) {
  formData.append('action', action);
  const res = await fetch('api.php', { method: 'POST', body: formData });
  if (res.status === 401) { location.reload(); return { success: false }; }
  return res.json();
}
async function doLogout() {
  await fetch('api.php?action=admin_logout');
  location.href = 'admin.php';
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
    if(!document.getElementById('analyticsDate').value) document.getElementById('analyticsDate').value = localToday();
    loadAnalytics();
  }
  if (name === 'users') loadUsers();
  if (name === 'reports') initReportDates();
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
  topItemsData = res.top_items || []; topItemsPage = 1; renderTopItemsPage();
  document.getElementById('recentBillsBody').innerHTML = (res.recent_bills || []).map(b =>
    `<tr><td style="color:var(--accent);font-weight:600">${b.bill_number}</td><td style="font-weight:600">₹${parseFloat(b.total).toFixed(0)}</td><td>${b.payment_method}</td></tr>`
  ).join('') || '<tr><td colspan="3" style="text-align:center;color:var(--text-muted)">No bills yet</td></tr>';
}
function renderTopItemsPage() {
  const total = Math.ceil(topItemsData.length / PER_PAGE);
  const paged = topItemsData.slice((topItemsPage-1)*PER_PAGE, topItemsPage*PER_PAGE);
  const offset = (topItemsPage-1)*PER_PAGE;
  document.getElementById('topItemsBody').innerHTML = paged.map((i,idx) =>
    `<tr><td style="color:var(--text-muted);font-weight:700">#${offset+idx+1}</td><td style="font-weight:600">${i.item_name}</td><td>${i.total_qty}</td><td style="color:var(--green);font-weight:600">₹${parseFloat(i.total_revenue).toFixed(0)}</td></tr>`
  ).join('') || '<tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:30px">No data yet</td></tr>';
  renderPagination('topItemsPagination', topItemsPage, total, p => { topItemsPage=p; renderTopItemsPage(); });
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
  const fd = new FormData(); fd.append('action', 'delete_expense'); fd.append('id', id);
  const res = await (await fetch('api.php', { method: 'POST', body: fd })).json();
  if (res.success) { showToast('Expense deleted!'); loadAnalytics(); }
  else showToast(res.error, 'error');
}

// ─── USERS ────────────────────────────
async function loadUsers() {
  const tbody = document.getElementById('usersBody');
  tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:30px">Loading…</td></tr>';
  const res = await api('get_pos_users');
  if (!res.success) {
    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:var(--red);padding:30px">Failed to load users. Please try again.</td></tr>';
    return;
  }
  allUsersData = res.data || []; userPage = 1; renderUsersPage();
}
function renderUsersPage() {
  const total = Math.ceil(allUsersData.length / PER_PAGE);
  const paged = allUsersData.slice((userPage-1)*PER_PAGE, userPage*PER_PAGE);
  document.getElementById('usersBody').innerHTML = paged.map(u => `<tr>
    <td style="font-weight:600">${u.name}</td>
    <td style="color:var(--text-secondary)">${u.username}</td>
    <td><span class="status-badge ${u.active==1?'active':'inactive'}">${u.active==1?'Active':'Inactive'}</span></td>
    <td><div class="action-btns">
      <button class="action-btn" onclick='showUserForm(${JSON.stringify(u)})'>Edit</button>
      <button class="action-btn delete" onclick="deleteUser(${u.id})">Delete</button>
    </div></td></tr>`).join('') ||
    '<tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:40px">No users yet. Add a cashier to get started.</td></tr>';
  renderPagination('userPagination', userPage, total, p => { userPage=p; renderUsersPage(); });
}
function showUserForm(user = null) {
  document.getElementById('formTitle').textContent = user ? 'Edit User' : 'Add User';
  document.getElementById('formBody').innerHTML = `
    <form onsubmit="saveUser(event,${user ? user.id : 'null'})">
      <div class="form-row">
        <div class="form-group"><label>Full Name</label><input id="uName" value="${user ? user.name : ''}" placeholder="e.g. Ravi Kumar" required></div>
        <div class="form-group"><label>Username</label><input id="uUsername" value="${user ? user.username : ''}" placeholder="e.g. ravi" required></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Password ${user ? '(leave blank to keep)' : ''}</label><input type="password" id="uPassword" placeholder="Password" ${user ? '' : 'required'}></div>
        <div class="form-group"><label>Status</label><select id="uActive"><option value="1" ${!user||user.active==1?'selected':''}>Active</option><option value="0" ${user&&user.active==0?'selected':''}>Inactive</option></select></div>
      </div>
      <button type="submit" class="bill-btn" style="margin-top:8px">💾 Save User</button>
    </form>`;
  document.getElementById('formModal').style.display = 'flex';
}
async function saveUser(e, id) {
  e.preventDefault();
  const fd = new FormData();
  fd.append('name', document.getElementById('uName').value);
  fd.append('username', document.getElementById('uUsername').value);
  fd.append('password', document.getElementById('uPassword').value);
  fd.append('active', document.getElementById('uActive').value);
  if (id) fd.append('id', id);
  const res = await apiPostForm(id ? 'update_pos_user' : 'add_pos_user', fd);
  if (res.success) { showToast('User saved!'); closeFormModal(); loadUsers(); }
  else showToast(res.error || 'Username may already exist', 'error');
}
async function deleteUser(id) {
  if (!confirm('Delete this user? They will no longer be able to log in.')) return;
  const fd = new FormData(); fd.append('action','delete_pos_user'); fd.append('id',id);
  const res = await (await fetch('api.php',{method:'POST',body:fd})).json();
  if (res.success) { showToast('User deleted!'); loadUsers(); }
  else showToast(res.error, 'error');
}

async function updateAdminCredentials(e) {
  e.preventDefault();
  const newUser = document.getElementById('adminNewUsername').value.trim();
  const curPass = document.getElementById('adminCurrentPass').value;
  const newPass = document.getElementById('adminNewPass').value;
  const conPass = document.getElementById('adminConfirmPass').value;
  if (newPass && newPass !== conPass) { showToast('New passwords do not match', 'error'); return; }
  const fd = new FormData();
  fd.append('new_username', newUser);
  fd.append('current_password', curPass);
  fd.append('new_password', newPass);
  fd.append('confirm_password', conPass);
  const res = await apiPostForm('update_admin_credentials', fd);
  if (res.success) {
    showToast('Credentials updated! Reloading…');
    document.getElementById('adminCurrentPass').value = '';
    document.getElementById('adminNewPass').value = '';
    document.getElementById('adminConfirmPass').value = '';
    setTimeout(() => location.reload(), 1500);
  } else {
    showToast(res.error || 'Update failed', 'error');
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
document.addEventListener('DOMContentLoaded', () => {
  loadDashboard();
  document.getElementById('analyticsDate').value = localToday();
});

function localToday() {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

// ─── REPORTS ──────────────────────────────────
function initReportDates() {
  const t = localToday();
  if (!document.getElementById('reportFrom').value) document.getElementById('reportFrom').value = t;
  if (!document.getElementById('reportTo').value)   document.getElementById('reportTo').value = t;
}

async function downloadReport() {
  const from = document.getElementById('reportFrom').value;
  const to   = document.getElementById('reportTo').value;
  if (!from || !to) { showToast('Please select both dates', 'error'); return; }
  showToast('Generating report…', 'info');
  const res = await api('get_report_data', { from, to });
  if (!res.success) { showToast('Failed to load report data', 'error'); return; }
  const html = buildReportHTML(res);
  const win = window.open('', '_blank');
  win.document.write(html);
  win.document.close();
  win.focus();
  setTimeout(() => win.print(), 600);
}

function fmt(n) { return '₹' + parseFloat(n).toFixed(2); }
function fmtDate(d) {
  const dt = new Date(d + 'T00:00:00');
  return dt.toLocaleDateString('en-IN', { weekday:'long', day:'2-digit', month:'long', year:'numeric' });
}

function buildReportHTML(data) {
  const profitColor = data.net_profit >= 0 ? '#166534' : '#991b1b';
  const now = new Date().toLocaleString('en-IN', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
  const periodLabel = data.from === data.to ? fmtDate(data.from) : `${fmtDate(data.from)} &mdash; ${fmtDate(data.to)}`;

  const dayRows = data.days.map(day => {
    const pColor = day.net_profit >= 0 ? '#166534' : '#991b1b';
    const expRows = day.expenses.length
      ? day.expenses.map(e => `<tr><td style="padding:4px 8px;color:#555">&nbsp;&nbsp;• ${e.description}</td><td style="padding:4px 8px;text-align:right;color:#555">₹${parseFloat(e.amount).toFixed(2)}</td></tr>`).join('')
      : '';
    return `
      <tr style="background:#f0fdf4">
        <td colspan="2" style="padding:10px 8px;font-weight:700;font-size:14px;border-top:2px solid #968E5C;color:#14532d">${fmtDate(day.date)}</td>
      </tr>
      <tr>
        <td style="padding:5px 8px">Sales Income</td>
        <td style="padding:5px 8px;text-align:right;font-weight:600;color:#166534">${fmt(day.income)}</td>
      </tr>
      ${expRows}
      <tr>
        <td style="padding:5px 8px">Total Expenses</td>
        <td style="padding:5px 8px;text-align:right;color:#991b1b">-${fmt(day.total_expenses)}</td>
      </tr>
      <tr style="background:#fefce8">
        <td style="padding:6px 8px;font-weight:700">Net Profit</td>
        <td style="padding:6px 8px;text-align:right;font-weight:800;color:${pColor}">${fmt(day.net_profit)}</td>
      </tr>
      <tr><td style="padding:3px 8px;color:#888;font-size:12px">Bills raised</td><td style="padding:3px 8px;text-align:right;color:#888;font-size:12px">${day.bill_count}</td></tr>`;
  }).join('<tr><td colspan="2" style="padding:2px"></td></tr>');

  return `<!DOCTYPE html><html><head><meta charset="UTF-8">
<title>Kilippadam Sales Report</title>
<style>
  body{font-family:'Segoe UI',Arial,sans-serif;font-size:13px;color:#1a1a1a;margin:0;padding:30px;}
  h1{font-size:22px;margin:0 0 2px}h2{font-size:15px;margin:0 0 4px;color:#555}
  .header{border-bottom:3px solid #968E5C;padding-bottom:14px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:flex-end}
  .summary{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px}
  .sum-box{border:1px solid #ddd;border-radius:8px;padding:14px;text-align:center}
  .sum-box .val{font-size:20px;font-weight:800;margin-top:4px}
  .sum-box .lbl{font-size:11px;color:#888;text-transform:uppercase;letter-spacing:.5px}
  table{width:100%;border-collapse:collapse;font-size:13px}
  @media print{body{padding:10px}button{display:none}}
</style></head><body>
<div class="header">
  <div><h1>🍽 Kilippadam</h1><h2>Sales Report</h2><p style="margin:4px 0;color:#555;font-size:12px">${periodLabel}</p></div>
  <div style="text-align:right;font-size:11px;color:#888">Generated: ${now}</div>
</div>
<div class="summary">
  <div class="sum-box"><div class="lbl">Total Income</div><div class="val" style="color:#166534">${fmt(data.total_income)}</div></div>
  <div class="sum-box"><div class="lbl">Total Expenses</div><div class="val" style="color:#991b1b">${fmt(data.total_expenses)}</div></div>
  <div class="sum-box"><div class="lbl">Net Profit</div><div class="val" style="color:${profitColor}">${fmt(data.net_profit)}</div></div>
  <div class="sum-box"><div class="lbl">Total Bills</div><div class="val">${data.total_bills}</div></div>
</div>
${ data.days.length > 1 ? `<h3 style="margin-bottom:10px;color:#444">Day-by-Day Breakdown</h3><table>${dayRows}</table>` : `<table>${dayRows}</table>` }
<p style="margin-top:30px;font-size:11px;color:#aaa;text-align:center">Kilippadam POS &bull; Confidential Sales Report</p>
</body></html>`;
}
</script>
</body>
</html>
