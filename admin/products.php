<?php
declare(strict_types=1);

require_once __DIR__ . '/check-auth.php';
require_once __DIR__ . '/helpers/permissions_helper.php';

if (!admin_has_permission('products_edit')) {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en" dir="ltr">
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
        <h1>Access Denied</h1>
        <p>You do not have permission to manage products.</p>
      </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products Manager</title>
<link rel="stylesheet" href="assets/admin.css?v=20260414-8">
<style>
html, body { height: 100%; }
body {
  margin: 0;
  padding: 20px;
  overflow: hidden;
  background: transparent;
  color: #fff;
  direction: ltr;
}
.pm-shell {
  height: 100%;
  display: flex;
  flex-direction: column;
  gap: 18px;
}
.pm-topbar {
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 22px;
  padding: 18px;
}
.pm-toolbar {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
  gap: 14px;
  align-items: end;
}
.pm-split {
  flex: 1;
  min-height: 0;
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 18px;
  align-items: stretch;
}
.pm-editor, .pm-list {
  min-height: 0;
  border: 1px solid rgba(255,255,255,0.08);
  background: rgba(255,255,255,0.04);
  border-radius: 22px;
  padding: 18px;
  box-shadow: 0 18px 40px rgba(0,0,0,0.22);
}
.pm-editor { display: flex; flex-direction: column; gap: 16px; }
.pm-list { display: flex; flex-direction: column; }
.pm-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}
.pm-head h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 800;
  color: #fff;
}
.pm-form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}
.pm-form-grid .full-col { grid-column: 1 / -1; }
.pm-image-box {
  width: 100%;
  min-height: 290px;
  border-radius: 18px;
  border: 1px dashed rgba(255,255,255,0.14);
  background: rgba(255,255,255,0.03);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  padding: 16px;
}
.pm-image-box img {
  width: 100%;
  height: 100%;
  max-height: 340px;
  object-fit: contain;
  display: block;
}
.pm-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-start;
}
.pm-stock-wrap { display: grid; gap: 12px; }
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
.stock-chip-head strong { font-size:14px; line-height:1.7; color:#fff; }
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
.products-scroll {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  overflow-x: hidden;
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding-left: 4px;
}
.products-scroll::-webkit-scrollbar { width: 8px; }
.products-scroll::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.18);
  border-radius: 999px;
}
.product-row-card {
  border: 1px solid rgba(255,255,255,0.08);
  background: rgba(255,255,255,0.03);
  border-radius: 18px;
  padding: 14px;
  display: grid;
  grid-template-columns: 84px minmax(0,1fr) auto;
  gap: 14px;
  align-items: start;
}
.product-row-card.selected {
  border-color: rgba(37,99,235,0.42);
  box-shadow: 0 0 0 2px rgba(37,99,235,0.18) inset;
}
.product-row-thumb {
  width: 84px;
  height: 84px;
  border-radius: 16px;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  padding: 6px;
  object-fit: contain;
}
.product-row-main {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.product-row-top { display:flex; align-items:center; justify-content:space-between; gap:10px; }
.product-row-index { color:#c8d4ea; font-size:12px; font-weight:700; }
.product-row-title {
  font-size: 18px;
  font-weight: 800;
  line-height: 1.6;
  margin: 0;
  color: #fff;
  overflow-wrap: anywhere;
}
.product-row-sku {
  color: #dfe9ff;
  line-height: 1.7;
  overflow-wrap: anywhere;
  word-break: break-word;
  font-size: clamp(11px, 0.85vw, 14px);
}
.product-row-sku strong,
.product-row-price strong { color:#c8d4ea; font-weight:700; margin-right:6px; }
.product-row-price { color:#fff; line-height:1.7; font-size:15px; overflow-wrap:anywhere; }
.product-row-meta { display:flex; flex-wrap:wrap; gap:8px; }
.product-row-action { display:flex; align-items:flex-start; justify-content:flex-end; }
.product-row-action .btn-primary { min-width: 82px; }
.empty-box {
  padding: 16px;
  border-radius: 14px;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  color: #c8d4ea;
  line-height: 1.8;
}
.hide-empty:empty { display:none; }
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
input[type="number"] { -moz-appearance:textfield; appearance:textfield; }
@media (max-width: 1180px) {
  body { overflow: auto; }
  .pm-split { grid-template-columns: 1fr; }
  .pm-list { min-height: 720px; }
}
@media (max-width: 760px) {
  .pm-toolbar,
  .pm-form-grid,
  .stock-chip-meta { grid-template-columns: 1fr; }
  .product-row-card { grid-template-columns: 1fr; }
  .product-row-action { justify-content: flex-start; }
}
</style>
</head>
<body>
<div class="pm-shell">
  <div class="pm-topbar">
    <div class="pm-toolbar">
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
  <div class="pm-split">
    <div class="pm-editor">
      <div class="pm-head"><h2>Edit Product</h2></div>
      <div class="pm-form-grid">
        <div class="form-group"><label for="editProductId">Product ID</label><input id="editProductId" type="text" readonly></div>
        <div class="form-group"><label for="editProductSlug">Slug</label><input id="editProductSlug" type="text" readonly></div>
        <div class="form-group full-col"><label for="editProductTitle">Title</label><input id="editProductTitle" type="text"></div>
        <div class="form-group"><label for="editProductCategory">Category</label><select id="editProductCategory"></select></div>
        <div class="form-group"><label for="editProductBrand">Brand</label><select id="editProductBrand"></select></div>
        <div class="form-group"><label for="editProductDevicesCount">Devices Count</label><input id="editProductDevicesCount" type="number" min="1" max="4"></div>
        <div class="form-group"><label for="editProductDuration">Duration Months</label><input id="editProductDuration" type="number" min="1"></div>
        <div class="form-group"><label for="editProductDownPayment">Down Payment</label><input id="editProductDownPayment" type="number" min="0" step="0.001"></div>
        <div class="form-group"><label for="editProductMonthly">Monthly Amount</label><input id="editProductMonthly" type="number" min="0" step="0.001"></div>
        <div class="form-group"><label for="editProductAvailable">Available</label><select id="editProductAvailable"><option value="1">Yes</option><option value="0">No</option></select></div>
        <div class="form-group"><label for="editProductHotOffer">Hot Offer</label><select id="editProductHotOffer"><option value="0">No</option><option value="1">Yes</option></select></div>
      </div>
      <div class="pm-image-box"><img id="editProductPreviewImage" src="" alt=""></div>
      <div class="form-group"><label for="editProductImageInput">Replace Image</label><input id="editProductImageInput" type="file" accept=".jpg,.jpeg,.png,.webp"></div>
      <div id="productStockLinksWrap" class="pm-stock-wrap hide-empty"></div>
      <div class="pm-actions">
        <button type="button" class="btn-success" id="saveProductChangesBtn">Save Changes</button>
        <button type="button" class="btn-danger" id="deleteProductBtn">Delete Product</button>
      </div>
    </div>
    <div class="pm-list">
      <div class="pm-head"><h2>Products List</h2></div>
      <div id="productsTableBody" class="products-scroll"><div class="empty-box">Load products to begin.</div></div>
    </div>
  </div>
</div>
<script src="products-manager.js?v=20260414-8"></script>
</body>
</html>
