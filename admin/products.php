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
  background: #071224;
  color: #fff;
  padding: 18px;
  overflow: hidden;
}
.page-frame {
  height: calc(100vh - 36px);
  border-radius: 28px;
  border: 1px solid rgba(255,255,255,0.08);
  background:
    radial-gradient(circle at top center, rgba(37,99,235,0.14), transparent 42%),
    linear-gradient(180deg, rgba(15,28,57,0.98), rgba(10,19,40,0.98));
  box-shadow: 0 22px 60px rgba(0,0,0,0.28);
  padding: 18px;
  overflow: hidden;
}
.page-title {
  font-size: 28px;
  font-weight: 900;
  margin: 0;
  text-align: right;
  color: #ffffff;
}
.manager-topbar {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  margin-bottom: 18px;
  padding-inline: 6px;
}
.panel {
  background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.025));
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 24px;
  padding: 18px;
  margin-bottom: 18px;
  box-shadow: 0 18px 40px rgba(0,0,0,0.18);
}
.manager-panel {
  height: calc(100% - 62px);
  margin-bottom: 0;
  display: flex;
  flex-direction: column;
}
.toolbar-wrap {
  max-width: 100%;
  border-radius: 22px;
  padding: 16px;
  background: linear-gradient(180deg, rgba(8,20,46,0.82), rgba(13,27,58,0.9));
  border: 1px solid rgba(255,255,255,0.06);
}
.toolbar {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 14px;
  align-items: end;
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
  color: #d7e6ff;
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
  background: rgba(24,101,73,0.42);
  border: 1px solid rgba(47,158,111,0.34);
  color: #d7fff0;
}
.status-box.error {
  background: rgba(239,68,68,0.14);
  border: 1px solid rgba(239,68,68,0.28);
  color: #fee2e2;
}
.manager-shell {
  display: grid;
  grid-template-columns: minmax(0, 0.98fr) 12px minmax(0, 1.08fr);
  gap: 0;
  align-items: stretch;
  min-height: 0;
  flex: 1;
  padding-top: 6px;
}
.editor-pane,
.list-pane {
  min-width: 0;
  min-height: 0;
}
.editor-pane {
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding-inline-end: 18px;
}
.vertical-divider {
  width: 12px;
  align-self: stretch;
  border-radius: 999px;
  background: linear-gradient(180deg, rgba(255,255,255,0.95), rgba(241,245,249,0.9));
  box-shadow: inset 0 0 0 1px rgba(15,23,42,0.18);
  position: relative;
}
.vertical-divider::after {
  content: '';
  position: absolute;
  top: 16px;
  left: 2px;
  right: 2px;
  height: 34%;
  border-radius: 999px;
  background: linear-gradient(180deg, rgba(156,163,175,0.95), rgba(107,114,128,0.95));
}
.list-pane {
  display: flex;
  flex-direction: column;
  min-height: 0;
  padding-inline-start: 18px;
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
  font-size: 20px;
  font-weight: 900;
}
.editor-card,
.list-card,
.preview-card {
  background: rgba(12,25,53,0.82);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 22px;
  padding: 16px;
}
.preview-card {
  padding: 14px;
}
.utility-fields {
  display: none;
}
.image-box {
  width: 100%;
  min-height: 300px;
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
  max-height: 360px;
  object-fit: contain;
  display: block;
}
.editor-actions {
  display:flex;
  gap:10px;
  flex-wrap:wrap;
  margin-top:18px;
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
  padding-inline: 2px 8px;
}
.products-scroll::-webkit-scrollbar {
  width: 10px;
}
.products-scroll::-webkit-scrollbar-track {
  background: rgba(255,255,255,0.05);
  border-radius: 999px;
}
.products-scroll::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.76);
  border-radius: 999px;
}
.product-row-card {
  border-top: 1px solid rgba(255,255,255,0.09);
  padding: 12px 0 14px;
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 12px;
  align-items: center;
}
.product-row-card:first-child {
  border-top: none;
  padding-top: 0;
}
.product-row-card.selected {
  background: rgba(255,255,255,0.02);
}
.product-row-main {
  min-width:0;
  display:flex;
  flex-direction:column;
  gap:6px;
}
.product-row-top {
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:10px;
}
.product-row-index {
  color:#ffffff;
  font-size:14px;
  font-weight:800;
}
.product-row-title {
  display:none;
}
.product-row-sku,
.product-row-devices,
.product-row-price {
  color:#ffffff;
  line-height:1.6;
  overflow-wrap:anywhere;
  word-break:break-word;
  font-size: 13px;
}
.product-row-label {
  color:#dbe6ff;
  font-weight:800;
  display:inline-block;
  min-width:48px;
}
.product-row-meta {
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  align-items:center;
  margin-top: 4px;
}
.badge {
  display:inline-flex;
  align-items:center;
  justify-content:center;
  padding:6px 12px;
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
  background:linear-gradient(135deg, #22c55e, #16a34a);
  border: none;
  color:#081120;
  font-weight:800;
}
.badge.stock-missing {
  background:linear-gradient(135deg, #ef4444, #dc2626);
  border:none;
  color:#fff;
}
.product-row-action {
  display:flex;
  align-items:center;
  justify-content:flex-end;
}
.product-row-action .btn-primary {
  min-width:78px;
}
.products-hint-card {
  border-top: 1px solid rgba(255,255,255,0.09);
  padding-top: 14px;
  text-align: center;
}
.products-hint-arrow {
  color: #ffffff;
  font-size: 34px;
  line-height: 1;
  margin-bottom: 8px;
}
.products-hint-card p {
  margin: 12px auto 0;
  max-width: 88%;
  color: #ffffff;
  line-height: 1.9;
}
.empty-box {
  padding:16px;
  border-radius:14px;
  background:rgba(255,255,255,0.04);
  border:1px solid rgba(255,255,255,0.08);
  color:#c8d4ea;
  line-height:1.8;
}
.hide-empty:empty {
  display:none;
}
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
  .page-frame { height: auto; overflow: visible; }
  .manager-panel { height: auto; }
  .manager-shell { grid-template-columns: 1fr; gap: 18px; }
  .vertical-divider { display: none; }
  .editor-pane { padding-inline-end: 0; }
  .list-pane { padding-inline-start: 0; min-height: auto; }
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
<div class="page-frame">
  <div class="manager-topbar">
    <h1 class="page-title">Products Manager</h1>
  </div>

  <div class="panel manager-panel">
    <div class="toolbar-wrap">
      <div class="section-head">
        <h2>Products Manager</h2>
      </div>
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

    <div class="manager-shell">
      <div class="editor-pane">
        <div class="editor-card">
          <div class="section-head">
            <h2>Edit Product</h2>
          </div>

          <div class="form-grid">
            <div class="utility-fields form-group">
              <label for="editProductId">Product ID</label>
              <input id="editProductId" type="text" readonly>
            </div>

            <div class="utility-fields form-group">
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
              <label for="editProductDownPayment">Down Payment</label>
              <input id="editProductDownPayment" type="number" min="0" step="0.001">
            </div>

            <div class="form-group">
              <label for="editProductMonthly">Monthly Amount</label>
              <input id="editProductMonthly" type="number" min="0" step="0.001">
            </div>

            <div class="form-group">
              <label for="editProductDuration">Duration Months</label>
              <input id="editProductDuration" type="number" min="1">
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

      <div class="vertical-divider" aria-hidden="true"></div>

      <div class="list-pane">
        <div class="list-card">
          <div class="section-head">
            <h2>Products List</h2>
          </div>
          <div id="productsTableBody" class="products-scroll">
            <div class="empty-box">لا توجد منتجات مطابقة.</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="products-manager.js?v=20260413-2"></script>
</body>
</html>
