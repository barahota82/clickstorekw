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
body{
  margin:0;
  padding:20px;
  font-family:Arial,sans-serif;
  background:#0f172a;
  color:#fff;
  overflow-x:hidden;
}
.page-title{
  margin:0 0 18px;
  font-size:24px;
  font-weight:800;
}
.panel{
  background:rgba(255,255,255,0.04);
  border:1px solid rgba(255,255,255,0.08);
  border-radius:18px;
  padding:18px;
  margin-bottom:18px;
  box-shadow:0 16px 34px rgba(0,0,0,0.20);
}
.toolbar{
  display:grid;
  grid-template-columns:minmax(0,1fr) minmax(0,1fr) auto;
  gap:12px;
  align-items:end;
}
.manager-grid{
  display:grid;
  grid-template-columns:minmax(0,1fr) minmax(0,1fr);
  gap:18px;
  align-items:start;
}
.panel-edit{
  grid-column:1;
}
.panel-list{
  grid-column:2;
}
.panel-head{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  margin-bottom:14px;
}
.panel-head h2{
  margin:0;
  font-size:18px;
  font-weight:800;
}
.form-grid{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:12px;
}
.form-grid-4{
  display:grid;
  grid-template-columns:repeat(4,minmax(0,1fr));
  gap:12px;
}
.form-group{
  min-width:0;
}
.form-group label{
  display:block;
  margin-bottom:8px;
  color:#c8d4ea;
  font-size:13px;
  font-weight:700;
}
.form-group input,
.form-group select,
.form-group textarea{
  width:100%;
  box-sizing:border-box;
  padding:12px 14px;
  border-radius:14px;
  border:1px solid rgba(255,255,255,0.10);
  background:rgba(255,255,255,0.04);
  color:#fff;
  outline:none;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus{
  border-color:rgba(37,99,235,0.55);
  box-shadow:0 0 0 4px rgba(37,99,235,0.10);
}
.form-group textarea{
  min-height:110px;
  resize:vertical;
}
.form-group input[readonly]{
  background:#0a1120;
  opacity:0.92;
}
select{
  appearance:none;
  -webkit-appearance:none;
  -moz-appearance:none;
  background-image:linear-gradient(45deg,transparent 50%,#fff 50%),linear-gradient(135deg,#fff 50%,transparent 50%);
  background-position:calc(16px) calc(50% - 3px),calc(10px) calc(50% - 3px);
  background-size:6px 6px,6px 6px;
  background-repeat:no-repeat;
  padding-left:34px !important;
}
select option{
  background:#0f172a;
  color:#fff;
}
button{
  padding:12px 16px;
  border:none;
  border-radius:14px;
  cursor:pointer;
  font-weight:800;
}
.btn-primary{
  background:linear-gradient(135deg,#2563eb,#1d4ed8);
  color:#fff;
}
.btn-success{
  background:linear-gradient(135deg,#22c55e,#16a34a);
  color:#fff;
}
.btn-danger{
  background:linear-gradient(135deg,#ef4444,#dc2626);
  color:#fff;
}
.status-box{
  margin-top:14px;
  padding:14px 16px;
  border-radius:14px;
  display:none;
}
.status-box.show{display:block;}
.status-box.info{
  background:rgba(59,130,246,0.14);
  border:1px solid rgba(59,130,246,0.28);
  color:#dbeafe;
}
.status-box.success{
  background:rgba(34,197,94,0.14);
  border:1px solid rgba(34,197,94,0.28);
  color:#dcfce7;
}
.status-box.error{
  background:rgba(239,68,68,0.14);
  border:1px solid rgba(239,68,68,0.28);
  color:#fee2e2;
}
.empty-box,
.stock-placeholder{
  padding:16px;
  border-radius:14px;
  background:rgba(255,255,255,0.04);
  border:1px solid rgba(255,255,255,0.08);
  color:#c8d4ea;
  line-height:1.8;
  min-height:80px;
}
.stock-placeholder{
  color:transparent;
}
.products-cards{
  display:flex;
  flex-direction:column;
  gap:12px;
}
.product-card-item{
  display:grid;
  grid-template-columns:72px minmax(0,1fr) auto;
  gap:14px;
  align-items:start;
  padding:14px;
  border-radius:18px;
  border:1px solid rgba(255,255,255,0.08);
  background:rgba(255,255,255,0.03);
  min-width:0;
}
.product-card-thumb{
  width:72px;
  height:72px;
  border-radius:14px;
  object-fit:contain;
  background:rgba(255,255,255,0.05);
  border:1px solid rgba(255,255,255,0.08);
  padding:6px;
}
.product-card-body{
  min-width:0;
}
.product-card-top{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:10px;
}
.product-card-title{
  margin:0 0 8px;
  font-size:16px;
  line-height:1.55;
  font-weight:800;
  color:#fff;
  overflow-wrap:anywhere;
}
.sku-line{
  color:#dbe6ff;
  line-height:1.75;
  overflow-wrap:anywhere;
  word-break:break-word;
  white-space:normal;
  font-size:14px;
}
.sku-line.sku-sm{font-size:13px;}
.sku-line.sku-xs{font-size:12px;}
.product-meta-line{
  margin-top:8px;
  color:#c8d4ea;
  font-size:13px;
  line-height:1.8;
  overflow-wrap:anywhere;
}
.product-card-actions{
  display:flex;
  align-items:flex-start;
  justify-content:flex-end;
}
.product-badges{
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  margin-top:10px;
}
.badge{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  padding:6px 10px;
  border-radius:999px;
  font-size:12px;
  font-weight:800;
}
.badge.active{
  background:rgba(34,197,94,0.16);
  border:1px solid rgba(34,197,94,0.28);
  color:#dcfce7;
}
.badge.inactive{
  background:rgba(239,68,68,0.16);
  border:1px solid rgba(239,68,68,0.28);
  color:#fee2e2;
}
.badge.hot{
  background:rgba(245,158,11,0.16);
  border:1px solid rgba(245,158,11,0.28);
  color:#fde68a;
}
.badge.stock-complete{
  background:rgba(34,197,94,0.16);
  border:1px solid rgba(34,197,94,0.28);
  color:#dcfce7;
}
.badge.stock-incomplete{
  background:rgba(239,68,68,0.16);
  border:1px solid rgba(239,68,68,0.28);
  color:#fee2e2;
}
.badge.stock-unknown{
  background:rgba(255,255,255,0.08);
  border:1px solid rgba(255,255,255,0.12);
  color:#e5eefc;
}
.filter-chip{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  min-height:34px;
  padding:0 12px;
  border-radius:999px;
  background:rgba(255,255,255,0.08);
  border:1px solid rgba(255,255,255,0.12);
  color:#fff;
  font-size:12px;
  font-weight:800;
  white-space:nowrap;
}
.image-box{
  width:100%;
  min-height:260px;
  border:1px dashed rgba(255,255,255,0.14);
  border-radius:18px;
  background:rgba(255,255,255,0.03);
  display:flex;
  align-items:center;
  justify-content:center;
  overflow:hidden;
  padding:16px;
  margin-top:16px;
}
.image-box img{
  width:100%;
  max-height:320px;
  object-fit:contain;
}
.links-wrap{
  display:flex;
  flex-direction:column;
  gap:12px;
  margin-top:16px;
}
.link-card{
  padding:14px;
  border-radius:16px;
  border:1px solid rgba(255,255,255,0.08);
  background:rgba(255,255,255,0.04);
}
.link-card.missing{
  border-color:rgba(239,68,68,0.30);
  background:rgba(239,68,68,0.08);
}
.link-card.linked{
  border-color:rgba(34,197,94,0.30);
  background:rgba(34,197,94,0.08);
}
.link-title{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  margin-bottom:10px;
}
.link-title strong{
  font-size:15px;
  line-height:1.7;
  overflow-wrap:anywhere;
}
.link-meta{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:10px;
}
.meta-box{
  padding:10px 12px;
  border-radius:14px;
  background:rgba(255,255,255,0.05);
  border:1px solid rgba(255,255,255,0.08);
  min-width:0;
}
.meta-box small{
  display:block;
  color:#c8d4ea;
  margin-bottom:4px;
  font-size:12px;
}
.meta-box span{
  color:#fff;
  font-size:13px;
  line-height:1.7;
  overflow-wrap:anywhere;
  word-break:break-word;
}
.link-actions{
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  align-items:end;
  margin-top:12px;
}
.stock-state-chip{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  min-height:36px;
  padding:0 14px;
  border-radius:999px;
  font-size:12px;
  font-weight:800;
  white-space:nowrap;
}
.stock-state-chip.complete{
  background:rgba(34,197,94,0.16);
  border:1px solid rgba(34,197,94,0.28);
  color:#dcfce7;
}
.stock-state-chip.incomplete{
  background:rgba(239,68,68,0.16);
  border:1px solid rgba(239,68,68,0.28);
  color:#fee2e2;
}
.stock-state-chip.neutral{
  background:rgba(255,255,255,0.08);
  border:1px solid rgba(255,255,255,0.12);
  color:#e5eefc;
}
.action-row{
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  margin-top:16px;
}
.full-col{grid-column:1 / -1;}
@media (max-width: 1180px){
  .manager-grid{grid-template-columns:1fr;}
  .panel-edit,.panel-list{grid-column:auto;}
}
@media (max-width: 860px){
  .toolbar,.form-grid,.form-grid-4,.link-meta{grid-template-columns:1fr;}
  .product-card-item{grid-template-columns:60px minmax(0,1fr);}
  .product-card-actions{grid-column:1 / -1; justify-content:flex-start;}
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

<div class="manager-grid">
  <div class="panel panel-edit">
    <div class="panel-head">
      <h2>Edit Product</h2>
      <span id="editStockStateBadge" class="stock-state-chip neutral">—</span>
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

      <div class="form-group full-col">
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
    </div>

    <div class="form-grid-4" style="margin-top:12px;">
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
    </div>

    <div class="form-grid" style="margin-top:12px;">
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

    <div class="image-box">
      <img id="editProductPreviewImage" src="" alt="">
    </div>

    <div class="form-group" style="margin-top:16px;">
      <label for="editProductImageInput">Replace Image</label>
      <input id="editProductImageInput" type="file" accept=".jpg,.jpeg,.png,.webp">
    </div>

    <div id="productStockLinksWrap" class="links-wrap">
      <div class="stock-placeholder"></div>
    </div>

    <div class="action-row">
      <button type="button" class="btn-danger" id="deleteProductBtn">Delete Product</button>
      <button type="button" class="btn-success" id="saveProductChangesBtn">Save Changes</button>
    </div>
  </div>

  <div class="panel panel-list">
    <div class="panel-head">
      <h2>Products List</h2>
      <span id="productsListCount" class="filter-chip">0</span>
    </div>

    <div id="productsCardsWrap" class="products-cards">
      <div class="empty-box">اختر Category و Brand ثم اضغط Load Products.</div>
    </div>
  </div>
</div>

<script src="products-manager.js?v=20260413-2"></script>
</body>
</html>
