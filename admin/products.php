<?php
declare(strict_types=1);

require_once __DIR__ . '/check-auth.php';
require_once __DIR__ . '/helpers/permissions_helper.php';

if (!admin_has_permission('products_edit')) {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Products Manager</title>
      <link rel="stylesheet" href="assets/admin.css">
      <style>
        body { margin: 0; padding: 24px; font-family: Arial, sans-serif; background: #0f172a; color: #fff; }
        .panel { max-width: 760px; margin: 40px auto; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 22px; padding: 24px; }
        h1 { margin: 0 0 12px; font-size: 28px; }
        p { margin: 0; color: #c8d4ea; line-height: 1.9; }
      </style>
    </head>
    <body>
      <div class="panel">
        <h1>ليس لديك صلاحية</h1>
        <p>هذه الصفحة تحتاج صلاحية عرض وتعديل المنتجات داخل لوحة التحكم.</p>
      </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products Manager</title>
<link rel="stylesheet" href="assets/admin.css">
<style>
html, body {
  height: 100%;
}
body {
  font-family: Arial, sans-serif;
  background: #0f172a;
  color: #fff;
  padding: 16px 18px;
  overflow: hidden;
}
.page-title {
  font-size: 24px;
  font-weight: 800;
  margin: 0 0 16px;
}
.panel {
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 22px;
  padding: 18px;
  margin-bottom: 18px;
  box-shadow: 0 18px 40px rgba(0,0,0,0.22);
}
.toolbar {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 14px;
  align-items: end;
}
.toolbar .btn-primary {
  height: 50px;
  min-width: 140px;
  align-self: end;
}
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}
.form-group {
  min-width: 0;
}
.form-group label {
  display: block;
  margin-bottom: 8px;
  color: #c8d4ea;
  font-size: 14px;
  font-weight: 700;
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
  box-sizing: border-box;
  outline: none;
  transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
  color-scheme: dark;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  border-color: rgba(37,99,235,0.55);
  box-shadow: 0 0 0 4px rgba(37,99,235,0.12);
  background: rgba(255,255,255,0.06);
}
.form-group textarea {
  min-height: 110px;
  resize: vertical;
}
.form-group select option {
  background: #14213f;
  color: #ffffff;
}
button {
  padding: 12px 16px;
  border: none;
  border-radius: 14px;
  cursor: pointer;
  font-weight: 700;
}
.btn-primary {
  background: linear-gradient(135deg,#2563eb,#1d4ed8);
  color: #fff;
}
.btn-success {
  background: linear-gradient(135deg,#22c55e,#16a34a);
  color: #fff;
}
.btn-danger {
  background: linear-gradient(135deg,#ef4444,#dc2626);
  color: #fff;
}
.status-box {
  margin-top: 14px;
  padding: 14px 16px;
  border-radius: 14px;
  display: none;
}
.status-box.show { display:block; }
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
.manager-shell {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 18px;
  align-items: stretch;
  min-height: calc(100vh - 170px);
  max-height: calc(100vh - 170px);
}
.editor-pane,
.list-pane {
  min-width: 0;
}
.editor-pane {
  display: flex;
  flex-direction: column;
  gap: 16px;
  min-height: 0;
}
.list-pane {
  display: flex;
  flex-direction: column;
  min-height: 0;
  max-height: 100%;
}
.section-head {
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  margin-bottom: 12px;
}
.section-head h2 {
  margin:0;
  font-size: 18px;
  font-weight: 800;
}
.editor-card,
.list-card,
.preview-card {
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 20px;
  padding: 16px;
}
.preview-card {
  padding: 14px;
}
.image-box {
  width: 100%;
  min-height: 190px;
  border: 1px dashed rgba(255,255,255,0.14);
  border-radius: 18px;
  background: rgba(255,255,255,0.03);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  padding: 16px;
}
.image-box img {
  width: 100%;
  max-height: 220px;
  object-fit: contain;
  display: block;
}
.editor-actions {
  display:flex;
  gap:10px;
  flex-wrap:wrap;
  margin-top:16px;
}
.stock-links-grid {
  display:grid;
  gap:12px;
}
.stock-chip-card {
  padding: 14px;
  border-radius: 16px;
  border: 1px solid rgba(255,255,255,0.08);
  background: rgba(255,255,255,0.04);
}
.stock-chip-card.linked {
  border-color: rgba(34,197,94,0.30);
  background: rgba(34,197,94,0.08);
}
.stock-chip-card.missing {
  border-color: rgba(239,68,68,0.30);
  background: rgba(239,68,68,0.08);
}
.stock-chip-head {
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  margin-bottom:10px;
}
.stock-chip-head strong {
  font-size: 14px;
  line-height: 1.7;
}
.stock-chip-meta {
  display:grid;
  grid-template-columns: repeat(2, minmax(0,1fr));
  gap:10px;
}
.meta-box {
  padding:10px 12px;
  border-radius:14px;
  background:rgba(255,255,255,0.05);
  border:1px solid rgba(255,255,255,0.08);
}
.meta-box small {
  display:block;
  color:#c8d4ea;
  margin-bottom:4px;
  font-size:12px;
}
.meta-box span {
  color:#fff;
  font-size:13px;
  line-height:1.7;
  overflow-wrap:anywhere;
}
.link-actions {
  display:flex;
  gap:10px;
  flex-wrap:wrap;
  align-items:end;
  margin-top:12px;
}
.list-pane .list-card {
  display:flex;
  flex-direction:column;
  min-height:0;
  flex:1;
}
.products-scroll {
  min-height:0;
  overflow-y:auto;
  overflow-x:hidden;
  display:flex;
  flex-direction:column;
  gap:12px;
  padding-left:4px;
}
.products-scroll::-webkit-scrollbar {
  width: 8px;
}
.products-scroll::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.18);
  border-radius: 999px;
}
.product-row-card {
  border:1px solid rgba(255,255,255,0.08);
  background: rgba(255,255,255,0.03);
  border-radius:18px;
  padding:14px;
  display:grid;
  grid-template-columns: 76px 1fr auto;
  gap:14px;
  align-items:start;
}
.product-row-card.selected {
  border-color: rgba(37,99,235,0.42);
  box-shadow: 0 0 0 2px rgba(37,99,235,0.18) inset;
}
.product-row-thumb {
  width:76px;
  height:76px;
  border-radius:16px;
  background: rgba(255,255,255,0.04);
  border:1px solid rgba(255,255,255,0.08);
  padding:6px;
  object-fit:contain;
}
.product-row-main {
  min-width:0;
  display:flex;
  flex-direction:column;
  gap:8px;
}
.product-row-top {
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:10px;
}
.product-row-index {
  color:#c8d4ea;
  font-size:12px;
  font-weight:700;
}
.product-row-title {
  font-size:16px;
  font-weight:800;
  line-height:1.6;
  margin:0;
  color:#fff;
  overflow-wrap:anywhere;
}
.product-row-sku {
  color:#dfe9ff;
  line-height:1.65;
  overflow-wrap:anywhere;
  word-break:break-word;
  font-size: clamp(11px, 0.95vw, 15px);
}
.product-row-meta {
  display:flex;
  flex-wrap:wrap;
  gap:8px;
}
.badge {
  display:inline-flex;
  align-items:center;
  justify-content:center;
  padding:6px 10px;
  border-radius:999px;
  font-size:12px;
  font-weight:700;
  white-space:nowrap;
}
.badge.active {
  background:rgba(34,197,94,0.16);
  border:1px solid rgba(34,197,94,0.28);
  color:#dcfce7;
}
.badge.inactive {
  background:rgba(239,68,68,0.16);
  border:1px solid rgba(239,68,68,0.28);
  color:#fee2e2;
}
.badge.hot {
  background:rgba(245,158,11,0.16);
  border:1px solid rgba(245,158,11,0.28);
  color:#fde68a;
}
.badge.stock-ok {
  background:rgba(34,197,94,0.16);
  border:1px solid rgba(34,197,94,0.28);
  color:#dcfce7;
}
.badge.stock-missing {
  background:rgba(239,68,68,0.16);
  border:1px solid rgba(239,68,68,0.28);
  color:#fee2e2;
}
.product-row-price {
  color:#fff;
  line-height:1.7;
  overflow-wrap:anywhere;
}
.product-row-action {
  display:flex;
  align-items:center;
  justify-content:flex-end;
}
.product-row-action .btn-primary {
  min-width:78px;
}
.empty-box {
  padding:16px;
  border-radius:14px;
  background:rgba(255,255,255,0.04);
  border:1px solid rgba(255,255,255,0.08);
  color:#c8d4ea;
  line-height:1.8;
}
.stock-summary-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 12px;
}
.stock-summary-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 8px 14px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 800;
  white-space: nowrap;
}
.stock-summary-pill.ok {
  background: rgba(34,197,94,0.16);
  border: 1px solid rgba(34,197,94,0.28);
  color: #dcfce7;
}
.stock-summary-pill.missing {
  background: rgba(239,68,68,0.16);
  border: 1px solid rgba(239,68,68,0.28);
  color: #fee2e2;
}
.stock-links-grid {
  margin-top: 12px;
}
.stock-chip-card {
  padding: 12px;
}
.stock-chip-meta {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}
.link-actions .form-group label {
  margin-bottom: 6px;
  font-size: 12px;
}
.hide-empty:empty {
  display:none;
}
/* remove number spinners */
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type="number"] {
  -moz-appearance: textfield;
  appearance: textfield;
}
@media (max-width: 1200px) {
  body { overflow:auto; }
  .manager-shell { grid-template-columns: 1fr; min-height: auto; }
  .list-pane { min-height: auto; max-height: none; }
  .products-scroll { max-height: none; overflow: visible; }
}
@media (max-width: 760px) {
  .toolbar, .form-grid, .stock-chip-meta { grid-template-columns: 1fr; }
  .product-row-card { grid-template-columns: 1fr; }
  .product-row-action { justify-content: flex-start; }
}
</style>
</head>
<body>

<h1 class="page-title">Products Manager</h1>

<div class="panel">
  <div class="toolbar">
    <div class="form-group">
      <label for="productsCategory">Category</label>
      <select id="productsCategory"></select>
    </div>

    <div class="form-group">
      <label for="productsBrand">Brand</label>
      <select id="productsBrand"></select>
    </div>

    <button type="button" class="btn-primary" id="loadProductsBtn">Load Products</button>
  </div>
  <div id="productsStatus" class="status-box"></div>
</div>

<div class="panel">
  <div class="manager-shell">
    <div class="editor-pane">
      <div class="editor-card">
        <div class="section-head">
          <h2>Edit Product</h2>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label for="editProductId">Product ID</label>
            <input id="editProductId" type="text" readonly>
          </div>

          <div class="form-group">
            <label for="editProductSlug">Slug</label>
            <input id="editProductSlug" type="text" readonly>
          </div>

          <div class="form-group" style="grid-column:1 / -1;">
            <label for="editProductTitle">Title</label>
            <input id="editProductTitle" type="text">
          </div>

          <div class="form-group">
            <label for="editProductCategory">Category</label>
            <select id="editProductCategory"></select>
          </div>

          <div class="form-group">
            <label for="editProductBrand">Brand</label>
            <select id="editProductBrand"></select>
          </div>

          <div class="form-group">
            <label for="editProductDevicesCount">Devices Count</label>
            <input id="editProductDevicesCount" type="number" min="1" max="4">
          </div>

          <div class="form-group">
            <label for="editProductDuration">Duration Months</label>
            <input id="editProductDuration" type="number" min="1">
          </div>

          <div class="form-group">
            <label for="editProductDownPayment">Down Payment</label>
            <input id="editProductDownPayment" type="number" min="0" step="0.001">
          </div>

          <div class="form-group">
            <label for="editProductMonthly">Monthly Amount</label>
            <input id="editProductMonthly" type="number" min="0" step="0.001">
          </div>

          <div class="form-group">
            <label for="editProductAvailable">Available</label>
            <select id="editProductAvailable">
              <option value="1">Yes</option>
              <option value="0">No</option>
            </select>
          </div>

          <div class="form-group">
            <label for="editProductHotOffer">Hot Offer</label>
            <select id="editProductHotOffer">
              <option value="0">No</option>
              <option value="1">Yes</option>
            </select>
          </div>
        </div>
      </div>

      <div class="preview-card">
        <div class="image-box">
          <img id="editProductPreviewImage" src="" alt="">
        </div>

        <div class="form-group" style="margin-top:14px;">
          <label for="editProductImageInput">Replace Image</label>
          <input id="editProductImageInput" type="file" accept=".jpg,.jpeg,.png,.webp">
        </div>

        <div id="productStockLinksWrap" class="stock-links-grid hide-empty" style="margin-top:14px;"></div>

        <div class="editor-actions">
          <button type="button" class="btn-danger" id="deleteProductBtn">Delete Product</button>
          <button type="button" class="btn-success" id="saveProductChangesBtn">Save Changes</button>
        </div>
      </div>
    </div>

    <div class="list-pane">
      <div class="list-card">
        <div class="section-head">
          <h2>Products List</h2>
        </div>
        <div id="productsTableBody" class="products-scroll">
          <div class="empty-box">&nbsp;</div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="products-manager.js?v=20260413-2"></script>
</body>
</html>
