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
:root{
  --pm-bg:#0f172a;
  --pm-card:rgba(255,255,255,0.04);
  --pm-line:rgba(255,255,255,0.08);
  --pm-text:#ffffff;
  --pm-muted:#c8d4ea;
  --pm-soft:rgba(255,255,255,0.03);
  --pm-blue-1:#2563eb;
  --pm-blue-2:#1d4ed8;
  --pm-green-1:#22c55e;
  --pm-green-2:#16a34a;
  --pm-red-1:#ef4444;
  --pm-red-2:#dc2626;
  --pm-gold-bg:rgba(245,158,11,0.16);
  --pm-gold-line:rgba(245,158,11,0.28);
  --pm-gold-text:#fde68a;
}
*{box-sizing:border-box;}
html,body{height:100%;}
body{
  margin:0;
  font-family:Arial,sans-serif;
  background:var(--pm-bg);
  color:var(--pm-text);
  padding:20px;
  overflow:hidden;
}
.page-title{
  font-size:24px;
  font-weight:800;
  margin:0 0 14px;
}
.panel{
  background:var(--pm-card);
  border:1px solid var(--pm-line);
  border-radius:18px;
  padding:18px;
}
.toolbar-panel{margin-bottom:18px;}
.toolbar{
  display:grid;
  grid-template-columns:1fr 1fr auto;
  gap:12px;
  align-items:end;
}
.form-grid{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:12px;
}
.form-group label{
  display:block;
  margin-bottom:8px;
  color:var(--pm-muted);
  font-size:14px;
  font-weight:700;
}
.form-group input,
.form-group select,
.form-group textarea{
  width:100%;
  padding:12px 14px;
  border-radius:14px;
  border:1px solid rgba(255,255,255,0.10);
  background:rgba(255,255,255,0.04);
  color:#fff;
  outline:none;
}
.form-group textarea{
  min-height:120px;
  resize:vertical;
}
.form-group input[type="file"]{padding:10px 12px;}
button{
  padding:12px 16px;
  border:none;
  border-radius:14px;
  cursor:pointer;
  font-weight:700;
}
.btn-primary{background:linear-gradient(135deg,var(--pm-blue-1),var(--pm-blue-2));color:#fff;}
.btn-success{background:linear-gradient(135deg,var(--pm-green-1),var(--pm-green-2));color:#fff;}
.btn-danger{background:linear-gradient(135deg,var(--pm-red-1),var(--pm-red-2));color:#fff;}
.status-box{
  margin-top:14px;
  padding:14px 16px;
  border-radius:14px;
  display:none;
}
.status-box.show{display:block;}
.status-box.info{background:rgba(59,130,246,0.14);border:1px solid rgba(59,130,246,0.28);color:#dbeafe;}
.status-box.success{background:rgba(34,197,94,0.14);border:1px solid rgba(34,197,94,0.28);color:#dcfce7;}
.status-box.error{background:rgba(239,68,68,0.14);border:1px solid rgba(239,68,68,0.28);color:#fee2e2;}
.pm-layout{
  display:grid;
  grid-template-columns:minmax(420px,1fr) minmax(420px,1fr);
  gap:18px;
  align-items:stretch;
  height:calc(100vh - 180px);
  min-height:620px;
}
.pm-col{min-width:0;}
.pm-edit-panel,.pm-list-panel{
  height:100%;
  display:flex;
  flex-direction:column;
}
.pm-section-title{
  margin:0 0 14px;
  font-size:18px;
  font-weight:800;
  color:#fff;
}
.pm-edit-scroll,.pm-products-scroll{
  flex:1;
  min-height:0;
  overflow-y:auto;
  overflow-x:hidden;
  padding-inline-end:6px;
}
.pm-edit-scroll::-webkit-scrollbar,.pm-products-scroll::-webkit-scrollbar{width:10px;}
.pm-edit-scroll::-webkit-scrollbar-thumb,.pm-products-scroll::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.18);border-radius:999px;}
.pm-edit-scroll::-webkit-scrollbar-track,.pm-products-scroll::-webkit-scrollbar-track{background:rgba(255,255,255,0.03);border-radius:999px;}
.readonly-input{background:rgba(255,255,255,0.03);opacity:.92;}
.image-box{
  width:100%;
  min-height:280px;
  border:1px dashed rgba(255,255,255,0.14);
  border-radius:18px;
  background:rgba(255,255,255,0.03);
  display:flex;
  align-items:center;
  justify-content:center;
  overflow:hidden;
  padding:16px;
  margin-bottom:16px;
}
.image-box img{width:100%;max-height:340px;object-fit:contain;}
.links-wrap{display:flex;flex-direction:column;gap:12px;}
.link-card{
  padding:14px;
  border-radius:16px;
  border:1px solid rgba(255,255,255,0.08);
  background:rgba(255,255,255,0.04);
}
.link-card.missing{border-color:rgba(239,68,68,0.30);background:rgba(239,68,68,0.08);}
.link-card.linked{border-color:rgba(34,197,94,0.30);background:rgba(34,197,94,0.08);}
.link-title{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  margin-bottom:10px;
}
.link-title strong{font-size:15px;line-height:1.7;}
.link-meta{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.meta-box{
  padding:10px 12px;
  border-radius:14px;
  background:rgba(255,255,255,0.05);
  border:1px solid rgba(255,255,255,0.08);
}
.meta-box small{display:block;color:var(--pm-muted);margin-bottom:4px;font-size:12px;}
.meta-box span{color:#fff;font-size:13px;line-height:1.7;}
.link-actions{display:flex;flex-wrap:wrap;gap:10px;align-items:end;margin-top:12px;}
.empty-box{
  padding:16px;
  border-radius:14px;
  background:rgba(255,255,255,0.04);
  border:1px solid rgba(255,255,255,0.08);
  color:var(--pm-muted);
  line-height:1.8;
}
.pm-products-grid{display:flex;flex-direction:column;gap:14px;}
.pm-product-card{
  background:rgba(255,255,255,0.03);
  border:1px solid rgba(255,255,255,0.08);
  border-radius:20px;
  padding:14px;
  display:grid;
  grid-template-columns:92px 1fr auto;
  gap:14px;
  align-items:start;
}
.pm-product-thumb{
  width:92px;
  height:92px;
  object-fit:contain;
  border-radius:16px;
  background:rgba(255,255,255,0.04);
  border:1px solid rgba(255,255,255,0.08);
  padding:6px;
}
.pm-product-main{min-width:0;}
.pm-product-title{
  margin:0 0 8px;
  font-size:18px;
  line-height:1.5;
  font-weight:800;
  color:#fff;
  white-space:normal;
  word-break:break-word;
  overflow-wrap:anywhere;
}
.pm-product-sku{
  margin:0 0 10px;
  color:var(--pm-muted);
  font-size:12px;
  line-height:1.7;
  direction:ltr;
  text-align:left;
  white-space:normal;
  word-break:break-word;
  overflow-wrap:anywhere;
}
.pm-product-price{margin:0 0 10px;color:#fff;font-size:14px;line-height:1.8;}
.pm-product-chips{display:flex;gap:8px;flex-wrap:wrap;}
.badge{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  padding:6px 10px;
  border-radius:999px;
  font-size:12px;
  font-weight:700;
  white-space:nowrap;
}
.badge.active{background:rgba(34,197,94,0.16);border:1px solid rgba(34,197,94,0.28);color:#dcfce7;}
.badge.inactive{background:rgba(239,68,68,0.16);border:1px solid rgba(239,68,68,0.28);color:#fee2e2;}
.badge.hot{background:var(--pm-gold-bg);border:1px solid var(--pm-gold-line);color:var(--pm-gold-text);}
.badge.info{background:rgba(59,130,246,0.16);border:1px solid rgba(59,130,246,0.28);color:#dbeafe;}
.pm-product-side{display:flex;flex-direction:column;gap:10px;align-items:flex-end;}
.pm-action-btn{min-width:98px;}
.pm-no-wrap{white-space:nowrap;}
@media (max-width: 1180px){
  body{overflow:auto;}
  .pm-layout{grid-template-columns:1fr;height:auto;}
  .pm-edit-panel,.pm-list-panel{height:auto;}
  .pm-edit-scroll,.pm-products-scroll{overflow:visible;max-height:none;padding-inline-end:0;}
}
@media (max-width: 760px){
  .toolbar,.form-grid,.link-meta{grid-template-columns:1fr;}
  .pm-product-card{grid-template-columns:1fr;}
  .pm-product-side{align-items:stretch;}
  .pm-product-thumb{width:100%;max-width:140px;height:140px;}
}
</style>
</head>
<body>

<h1 class="page-title">Products Manager</h1>

<div class="panel toolbar-panel">
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

<div class="pm-layout">
  <div class="pm-col">
    <div class="panel pm-edit-panel">
      <h2 class="pm-section-title">Edit Product</h2>

      <div class="pm-edit-scroll">
        <div class="form-grid">
          <div class="form-group">
            <label for="editProductId">Product ID</label>
            <input id="editProductId" class="readonly-input" type="text" readonly>
          </div>

          <div class="form-group">
            <label for="editProductSlug">Slug</label>
            <input id="editProductSlug" class="readonly-input" type="text" readonly>
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

          <div class="form-group" style="grid-column:1 / -1;">
            <label for="editProductImageInput">Replace Image</label>
            <input id="editProductImageInput" type="file" accept=".jpg,.jpeg,.png,.webp">
          </div>
        </div>

        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:16px;">
          <button type="button" class="btn-success" id="saveProductChangesBtn">Save Changes</button>
          <button type="button" class="btn-danger" id="deleteProductBtn">Delete Product</button>
        </div>

        <div style="margin-top:18px;">
          <div class="image-box">
            <img id="editProductPreviewImage" src="" alt="">
          </div>
        </div>

        <div style="margin-top:16px;">
          <h3 style="margin:0 0 12px; font-size:16px;">Stock Review / Product Links</h3>
          <div id="productStockLinksWrap" class="links-wrap">
            <div class="empty-box">اختر منتجًا أولًا لتحميل مراجعة الأجهزة والربط مع المخزن.</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="pm-col">
    <div class="panel pm-list-panel">
      <h2 class="pm-section-title">Products List</h2>

      <div class="pm-products-scroll">
        <div id="productsTableBody" class="pm-products-grid">
          <div class="empty-box">اختر Category و Brand ثم اضغط Load Products.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="products-manager.js?v=20260415-1"></script>
</body>
</html>
