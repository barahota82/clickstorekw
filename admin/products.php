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
        body {
          margin: 0;
          padding: 24px;
          font-family: Arial, sans-serif;
          background: #0f172a;
          color: #fff;
        }
        .panel {
          max-width: 760px;
          margin: 40px auto;
          background: rgba(255,255,255,0.04);
          border: 1px solid rgba(255,255,255,0.08);
          border-radius: 22px;
          padding: 24px;
        }
        h1 {
          margin: 0 0 12px;
          font-size: 28px;
        }
        p {
          margin: 0;
          color: #c8d4ea;
          line-height: 1.9;
        }
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
body {
  margin: 0;
  padding: 20px;
  font-family: Arial, sans-serif;
  background: #0f172a;
  color: #fff;
}

.pm-page {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.pm-shell {
  background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 24px;
  padding: 16px;
  box-shadow: 0 20px 44px rgba(0,0,0,0.20);
}

.pm-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 14px;
}

.pm-title {
  margin: 0;
  font-size: 32px;
  font-weight: 800;
  color: #fff;
}

.pm-desc {
  margin: 10px 0 0;
  color: #c8d4ea;
  line-height: 1.9;
  font-size: 14px;
}

.pm-filter-card,
.pm-column-card {
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 20px;
  padding: 18px;
}

.pm-filter-grid {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 12px;
  align-items: end;
}

.pm-manager-grid {
  display: grid;
  grid-template-columns: 1.03fr 0.97fr;
  gap: 18px;
  align-items: start;
}

.pm-column-title {
  margin: 0 0 12px;
  font-size: 19px;
  font-weight: 800;
  color: #fff;
}

.pm-field-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.pm-field-grid .full {
  grid-column: 1 / -1;
}

.pm-field-grid .span-half {
  grid-column: span 1;
}

.pm-form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.pm-form-group label {
  color: #c8d4ea;
  font-size: 13px;
  font-weight: 700;
}

.pm-form-group input,
.pm-form-group select,
.pm-form-group textarea {
  width: 100%;
  box-sizing: border-box;
  min-height: 48px;
  padding: 12px 14px;
  border-radius: 16px;
  border: 1px solid rgba(255,255,255,0.10);
  background: rgba(255,255,255,0.04);
  color: #fff;
  outline: none;
  transition: 0.2s ease;
}

.pm-form-group textarea {
  min-height: 110px;
  resize: vertical;
}

.pm-form-group input:focus,
.pm-form-group select:focus,
.pm-form-group textarea:focus {
  border-color: rgba(37,99,235,0.55);
  box-shadow: 0 0 0 4px rgba(37,99,235,0.12);
}

.pm-form-group input[readonly] {
  background: #0a1120;
  opacity: 0.95;
}

.pm-form-group input[type="number"]::-webkit-outer-spin-button,
.pm-form-group input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.pm-form-group input[type="number"] {
  -moz-appearance: textfield;
  appearance: textfield;
}

.pm-form-group select {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 20 20' fill='none'%3E%3Cpath d='M5 7.5L10 12.5L15 7.5' stroke='white' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: left 14px center;
  padding-left: 40px;
  color-scheme: dark;
}

.pm-form-group select option {
  background: #0f172a;
  color: #ffffff;
}

.pm-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 16px;
}

.pm-btn {
  border: none;
  border-radius: 14px;
  padding: 12px 16px;
  cursor: pointer;
  font-weight: 800;
  color: #fff;
}

.pm-btn-primary {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
}

.pm-btn-success {
  background: linear-gradient(135deg, #22c55e, #16a34a);
}

.pm-btn-danger {
  background: linear-gradient(135deg, #ef4444, #dc2626);
}

.pm-image-box {
  width: 100%;
  min-height: 340px;
  border: 1px dashed rgba(255,255,255,0.14);
  border-radius: 18px;
  background: rgba(255,255,255,0.03);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  padding: 14px;
  margin-bottom: 14px;
}

.pm-image-box img {
  width: 100%;
  max-height: 420px;
  object-fit: contain;
  display: block;
}

.pm-status-box {
  margin-top: 14px;
  padding: 14px 16px;
  border-radius: 14px;
  display: none;
}

.pm-status-box.show {
  display: block;
}

.pm-status-box.info {
  background: rgba(59,130,246,0.14);
  border: 1px solid rgba(59,130,246,0.28);
  color: #dbeafe;
}

.pm-status-box.success {
  background: rgba(34,197,94,0.14);
  border: 1px solid rgba(34,197,94,0.28);
  color: #dcfce7;
}

.pm-status-box.error {
  background: rgba(239,68,68,0.14);
  border: 1px solid rgba(239,68,68,0.28);
  color: #fee2e2;
}

.pm-helper-note,
.pm-empty-box {
  padding: 14px 16px;
  border-radius: 16px;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  color: #c8d4ea;
  line-height: 1.9;
  font-size: 13px;
}

.pm-products-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-height: 980px;
  overflow-y: auto;
  padding-left: 6px;
}

.pm-products-list::-webkit-scrollbar {
  width: 8px;
}
.pm-products-list::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.18);
  border-radius: 999px;
}
.pm-products-list::-webkit-scrollbar-track {
  background: transparent;
}

.pm-product-card {
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 18px;
  padding: 14px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.pm-product-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
}

.pm-product-head-right {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
  flex: 1 1 auto;
}

.pm-product-index {
  color: #c8d4ea;
  font-size: 12px;
  font-weight: 800;
}

.pm-product-title {
  color: #fff;
  font-size: 18px;
  font-weight: 800;
  line-height: 1.5;
  overflow-wrap: anywhere;
}

.pm-product-sku {
  color: #e4ecff;
  font-weight: 800;
  line-height: 1.6;
  white-space: normal;
  word-break: break-word;
  overflow-wrap: anywhere;
  font-size: 15px;
}

.pm-product-sku.is-long {
  font-size: 13px;
  line-height: 1.65;
}

.pm-product-sku.is-very-long {
  font-size: 12px;
  line-height: 1.7;
}

.pm-product-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}

.pm-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 30px;
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 800;
  white-space: nowrap;
}

.pm-badge.active {
  background: rgba(34,197,94,0.16);
  border: 1px solid rgba(34,197,94,0.30);
  color: #dcfce7;
}

.pm-badge.inactive {
  background: rgba(239,68,68,0.16);
  border: 1px solid rgba(239,68,68,0.30);
  color: #fee2e2;
}

.pm-badge.hot {
  background: rgba(245,158,11,0.16);
  border: 1px solid rgba(245,158,11,0.30);
  color: #fde68a;
}

.pm-badge.stock-ok {
  background: rgba(34,197,94,0.18);
  border: 1px solid rgba(34,197,94,0.34);
  color: #dcfce7;
}

.pm-badge.stock-missing {
  background: rgba(239,68,68,0.16);
  border: 1px solid rgba(239,68,68,0.30);
  color: #fee2e2;
}

.pm-product-price {
  color: #fff;
  line-height: 1.8;
  font-size: 15px;
  overflow-wrap: anywhere;
}

.pm-product-actions {
  display: flex;
  justify-content: flex-end;
}

.pm-list-thumb {
  width: 64px;
  height: 64px;
  object-fit: contain;
  border-radius: 14px;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  padding: 5px;
  flex: 0 0 auto;
}

.pm-stock-summary {
  margin-top: 14px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.pm-stock-summary-box {
  padding: 14px 16px;
  border-radius: 16px;
  border: 1px solid rgba(255,255,255,0.08);
  background: rgba(255,255,255,0.04);
}

.pm-stock-summary-box.good {
  border-color: rgba(34,197,94,0.28);
  background: rgba(34,197,94,0.10);
}

.pm-stock-summary-box.bad {
  border-color: rgba(239,68,68,0.28);
  background: rgba(239,68,68,0.10);
}

.pm-stock-summary-box strong {
  display: block;
  font-size: 15px;
  margin-bottom: 6px;
  color: #fff;
}

.pm-stock-summary-box span {
  display: block;
  line-height: 1.85;
  color: #dbe7fb;
  font-size: 13px;
}

.pm-links-wrap {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.pm-link-card {
  padding: 14px;
  border-radius: 16px;
  border: 1px solid rgba(255,255,255,0.08);
  background: rgba(255,255,255,0.04);
}

.pm-link-card.missing {
  border-color: rgba(239,68,68,0.28);
  background: rgba(239,68,68,0.08);
}

.pm-link-card.linked {
  border-color: rgba(34,197,94,0.28);
  background: rgba(34,197,94,0.08);
}

.pm-link-title {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 10px;
}

.pm-link-title strong {
  font-size: 15px;
  line-height: 1.7;
  overflow-wrap: anywhere;
}

.pm-link-meta {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.pm-meta-box {
  padding: 10px 12px;
  border-radius: 14px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.08);
}

.pm-meta-box small {
  display: block;
  color: #c8d4ea;
  margin-bottom: 4px;
  font-size: 12px;
}

.pm-meta-box span {
  color: #fff;
  font-size: 13px;
  line-height: 1.7;
  overflow-wrap: anywhere;
}

.pm-link-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: end;
  margin-top: 12px;
}

.pm-link-actions .pm-form-group {
  min-width: 220px;
  margin: 0;
}

.pm-legend {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 12px;
}

.pm-legend .pm-badge {
  min-height: 34px;
}

@media (max-width: 1180px) {
  .pm-filter-grid,
  .pm-manager-grid,
  .pm-field-grid,
  .pm-link-meta {
    grid-template-columns: 1fr;
  }

  .pm-products-list {
    max-height: none;
  }
}
</style>
</head>
<body>

<div class="pm-page">
  <div class="pm-shell">
    <div class="pm-header">
      <div>
        <h1 class="pm-title">Products Manager</h1>
        <p class="pm-desc">تحميل المنتجات حسب الفئة والبراند، تعديل بياناتها، استبدال الصورة، ومراجعة حالة الأصناف المضافة إلى المخزن من نفس الشاشة.</p>
      </div>
    </div>

    <div class="pm-filter-card">
      <div class="pm-filter-grid">
        <div class="pm-form-group">
          <label for="productsCategory">Category</label>
          <select id="productsCategory"></select>
        </div>

        <div class="pm-form-group">
          <label for="productsBrand">Brand</label>
          <select id="productsBrand"></select>
        </div>

        <button type="button" class="pm-btn pm-btn-primary" id="loadProductsBtn">Load Products</button>
      </div>

      <div class="pm-helper-note" style="margin-top:12px;">اختر الفئة ثم البراند وبعدها حمّل المنتجات. الحالة الخضراء تعني أن جميع أجهزة العرض مرتبطة بالمخزن، والحالة الحمراء تعني أن بعض الأجهزة غير مضافة بالكامل.</div>
      <div id="productsStatus" class="pm-status-box"></div>
    </div>

    <div class="pm-manager-grid" style="margin-top:18px;">
      <section class="pm-column-card">
        <h2 class="pm-column-title">Edit Product</h2>

        <div class="pm-field-grid">
          <div class="pm-form-group full">
            <label for="editProductTitle">Title</label>
            <input id="editProductTitle" type="text">
          </div>

          <div class="pm-form-group">
            <label for="editProductCategory">Category</label>
            <select id="editProductCategory"></select>
          </div>

          <div class="pm-form-group">
            <label for="editProductBrand">Brand</label>
            <select id="editProductBrand"></select>
          </div>

          <div class="pm-form-group">
            <label for="editProductDevicesCount">Devices Count</label>
            <input id="editProductDevicesCount" type="number" min="1" max="4">
          </div>

          <div class="pm-form-group">
            <label for="editProductDownPayment">Down Payment</label>
            <input id="editProductDownPayment" type="number" min="0" step="0.001">
          </div>

          <div class="pm-form-group">
            <label for="editProductMonthly">Monthly Amount</label>
            <input id="editProductMonthly" type="number" min="0" step="0.001">
          </div>

          <div class="pm-form-group">
            <label for="editProductDuration">Duration Months</label>
            <input id="editProductDuration" type="number" min="1">
          </div>

          <div class="pm-form-group">
            <label for="editProductAvailable">Available</label>
            <select id="editProductAvailable">
              <option value="1">Yes</option>
              <option value="0">No</option>
            </select>
          </div>

          <div class="pm-form-group">
            <label for="editProductHotOffer">Hot Offer</label>
            <select id="editProductHotOffer">
              <option value="0">No</option>
              <option value="1">Yes</option>
            </select>
          </div>
        </div>

        <div class="pm-image-box" style="margin-top:16px;">
          <img id="editProductPreviewImage" src="" alt="">
        </div>

        <div class="pm-form-group">
          <label for="editProductImageInput">Replace Image</label>
          <input id="editProductImageInput" type="file" accept=".jpg,.jpeg,.png,.webp">
        </div>

        <div class="pm-actions">
          <button type="button" class="pm-btn pm-btn-danger" id="deleteProductBtn">Delete Product</button>
          <button type="button" class="pm-btn pm-btn-success" id="saveProductChangesBtn">Save Changes</button>
        </div>

        <input id="editProductId" type="hidden">
        <input id="editProductSlug" type="hidden">
      </section>

      <section class="pm-column-card">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:12px;">
          <h2 class="pm-column-title" style="margin:0;">Products List</h2>
          <div class="pm-legend">
            <span class="pm-badge stock-ok">الأصناف مضافة إلى المخزن</span>
            <span class="pm-badge stock-missing">الأصناف غير مضافة بالكامل</span>
          </div>
        </div>

        <div id="productsTableBody" class="pm-products-list">
          <div class="pm-empty-box">اختر Category و Brand ثم اضغط Load Products.</div>
        </div>

        <div class="pm-stock-summary">
          <div id="editProductStockSummary" class="pm-stock-summary-box">
            <strong>حالة المخزن</strong>
            <span>اختر منتجًا من القائمة لمعرفة هل جميع أجهزة العرض مضافة إلى المخزن أم لا.</span>
          </div>

          <div id="productStockLinksWrap" class="pm-links-wrap">
            <div class="pm-empty-box">اختر منتجًا أولًا لتحميل مراجعة الأجهزة والربط مع المخزن.</div>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>

<script src="products-manager.js?v=20260413-2"></script>
</body>
</html>
