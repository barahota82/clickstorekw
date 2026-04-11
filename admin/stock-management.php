<?php require_once __DIR__ . '/check-auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Stock Management</title>
<link rel="stylesheet" href="assets/admin.css">
<style>
body {
  font-family: Arial, sans-serif;
  background: #0f172a;
  color: #fff;
  padding: 20px;
}
.page-title {
  font-size: 24px;
  font-weight: 800;
  margin-bottom: 10px;
}
.page-desc {
  color: #c8d4ea;
  line-height: 1.8;
  margin-bottom: 22px;
}
.panel {
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 18px;
  padding: 18px;
  margin-bottom: 18px;
}
.toolbar {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr auto;
  gap: 12px;
  align-items: end;
}
.form-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}
.form-grid-4 {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}
.form-group label {
  display: block;
  margin-bottom: 8px;
  color: #c8d4ea;
  font-size: 14px;
}
.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid rgba(255,255,255,0.10);
  background: rgba(255,255,255,0.04);
  color: #fff;
}
.form-group textarea {
  min-height: 90px;
  resize: vertical;
}
button {
  padding: 12px 16px;
  border: none;
  border-radius: 14px;
  cursor: pointer;
  font-weight: 700;
}
.btn-primary {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: #fff;
}
.btn-success {
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: #fff;
}
.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
  margin-bottom: 18px;
}
.summary-card {
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 16px;
  padding: 16px;
}
.summary-card strong {
  display: block;
  color: #c8d4ea;
  margin-bottom: 8px;
  font-size: 13px;
}
.summary-card span {
  font-size: 26px;
  font-weight: 800;
  color: #fff;
}
.section-title {
  font-size: 18px;
  font-weight: 800;
  margin: 0 0 14px;
  color: #fff;
}
.table-wrap {
  overflow-x: auto;
}
table {
  width: 100%;
  border-collapse: collapse;
  min-width: 980px;
}
th, td {
  padding: 12px 14px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  text-align: left;
  vertical-align: middle;
}
th {
  color: #c8d4ea;
  font-size: 13px;
}
.badge {
  display: inline-flex;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
}
.badge.active {
  background: rgba(34,197,94,0.16);
  border: 1px solid rgba(34,197,94,0.28);
  color: #dcfce7;
}
.badge.inactive {
  background: rgba(239,68,68,0.16);
  border: 1px solid rgba(239,68,68,0.28);
  color: #fee2e2;
}
.badge.low {
  background: rgba(245,158,11,0.16);
  border: 1px solid rgba(245,158,11,0.28);
  color: #fde68a;
}
.thumb {
  width: 58px;
  height: 58px;
  object-fit: contain;
  border-radius: 12px;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  padding: 5px;
}
.status-box {
  margin-top: 14px;
  padding: 14px 16px;
  border-radius: 14px;
  display: none;
}
.status-box.show {
  display: block;
}
.status-box.info {
  background: rgba(59,130,246,0.14);
  border: 1px solid rgba(59,130,246,0.28);
  color: #dbeafe;
}
.status-box.success {
  background: rgba(34,197,94,0.14);
  border: 1px solid rgba(34,197,94,0.28);
  color: #dcfce7;
}
.status-box.error {
  background: rgba(239,68,68,0.14);
  border: 1px solid rgba(239,68,68,0.28);
  color: #fee2e2;
}
.empty-box {
  padding: 16px;
  border-radius: 14px;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  color: #c8d4ea;
}
@media (max-width: 1100px) {
  .toolbar,
  .form-grid,
  .form-grid-4,
  .summary-grid {
    grid-template-columns: 1fr;
  }
}
</style>
</head>
<body>

<h1 class="page-title">Stock Management</h1>
<p class="page-desc">إدارة stock catalog، متابعة كميات المنتجات، وتسجيل الحركات اليدوية من نفس الشاشة.</p>

<div class="summary-grid">
  <div class="summary-card">
    <strong>Stock Catalog Items</strong>
    <span id="stockSummaryCatalog">0</span>
  </div>
  <div class="summary-card">
    <strong>Products With Stock</strong>
    <span id="stockSummaryProducts">0</span>
  </div>
  <div class="summary-card">
    <strong>Recent Movements</strong>
    <span id="stockSummaryMovements">0</span>
  </div>
  <div class="summary-card">
    <strong>Low Stock</strong>
    <span id="stockSummaryLow">0</span>
  </div>
</div>

<div class="panel">
  <h2 class="section-title">Filters</h2>
  <div class="toolbar">
    <div class="form-group">
      <label for="stockFilterCategory">Category</label>
      <select id="stockFilterCategory"></select>
    </div>

    <div class="form-group">
      <label for="stockFilterBrand">Brand</label>
      <select id="stockFilterBrand"></select>
    </div>

    <div class="form-group">
      <label for="stockFilterSearch">Search</label>
      <input id="stockFilterSearch" type="text" placeholder="Title / SKU / Slug">
    </div>

    <button type="button" class="btn-primary" id="loadStockDashboardBtn">Load Stock</button>
  </div>

  <div id="stockManagementStatus" class="status-box"></div>
</div>

<div class="panel">
  <h2 class="section-title">Add Stock Catalog Item</h2>
  <div class="form-grid-4">
    <div class="form-group">
      <label for="stockCatalogCategory">Category</label>
      <select id="stockCatalogCategory"></select>
    </div>

    <div class="form-group">
      <label for="stockCatalogBrand">Brand</label>
      <select id="stockCatalogBrand"></select>
    </div>

    <div class="form-group">
      <label for="stockCatalogTitle">Title</label>
      <input id="stockCatalogTitle" type="text" placeholder="Exact stock title">
    </div>

    <div class="form-group">
      <label for="stockCatalogStorage">Storage</label>
      <input id="stockCatalogStorage" type="text" placeholder="256GB">
    </div>

    <div class="form-group">
      <label for="stockCatalogRam">RAM</label>
      <input id="stockCatalogRam" type="text" placeholder="8GB">
    </div>

    <div class="form-group">
      <label for="stockCatalogNetwork">Network</label>
      <input id="stockCatalogNetwork" type="text" placeholder="5G">
    </div>

    <div class="form-group">
      <label>&nbsp;</label>
      <button type="button" class="btn-success" id="saveStockCatalogBtn">Save Catalog Item</button>
    </div>
  </div>
</div>

<div class="panel">
  <h2 class="section-title">Stock Movement</h2>
  <div class="form-grid-4">
    <div class="form-group">
      <label for="stockMovementProduct">Product</label>
      <select id="stockMovementProduct"></select>
    </div>

    <div class="form-group">
      <label for="stockMovementType">Movement Type</label>
      <select id="stockMovementType">
        <option value="in">IN</option>
        <option value="out">OUT</option>
        <option value="adjust">ADJUST</option>
        <option value="reserve">RESERVE</option>
        <option value="release">RELEASE</option>
      </select>
    </div>

    <div class="form-group">
      <label for="stockMovementQty">Quantity</label>
      <input id="stockMovementQty" type="number" min="0" value="0">
    </div>

    <div class="form-group">
      <label for="stockMovementReorder">Reorder Level</label>
      <input id="stockMovementReorder" type="number" min="0" value="0">
    </div>

    <div class="form-group" style="grid-column: 1 / -1;">
      <label for="stockMovementNotes">Notes</label>
      <textarea id="stockMovementNotes" placeholder="Optional notes for this movement"></textarea>
    </div>

    <div class="form-group">
      <label>&nbsp;</label>
      <button type="button" class="btn-success" id="saveStockMovementBtn">Save Movement</button>
    </div>
  </div>
</div>

<div class="panel">
  <h2 class="section-title">Stock Catalog</h2>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Title</th>
          <th>Category</th>
          <th>Brand</th>
          <th>Storage</th>
          <th>RAM</th>
          <th>Network</th>
          <th>Status</th>
          <th>Updated</th>
        </tr>
      </thead>
      <tbody id="stockCatalogTableBody">
        <tr>
          <td colspan="9"><div class="empty-box">اضغط Load Stock لتحميل البيانات.</div></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<div class="panel">
  <h2 class="section-title">Product Stock Items</h2>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Image</th>
          <th>Product</th>
          <th>Brand</th>
          <th>SKU</th>
          <th>On Hand</th>
          <th>Reserved</th>
          <th>Available</th>
          <th>Reorder Level</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody id="stockItemsTableBody">
        <tr>
          <td colspan="10"><div class="empty-box">اضغط Load Stock لتحميل البيانات.</div></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<div class="panel">
  <h2 class="section-title">Recent Stock Movements</h2>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Product</th>
          <th>SKU</th>
          <th>Type</th>
          <th>Qty</th>
          <th>Notes</th>
          <th>By</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody id="stockMovementsTableBody">
        <tr>
          <td colspan="8"><div class="empty-box">اضغط Load Stock لتحميل البيانات.</div></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<script src="stock-management.js"></script>
</body>
</html>
