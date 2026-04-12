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
  font-family:Arial,sans-serif;
  background:#0f172a;
  color:#fff;
  padding:20px;
}
.page-title{
  font-size:24px;
  font-weight:800;
  margin-bottom:10px;
}
.page-desc{
  color:#c8d4ea;
  line-height:1.8;
  margin-bottom:22px;
}
.panel{
  background:rgba(255,255,255,0.04);
  border:1px solid rgba(255,255,255,0.08);
  border-radius:18px;
  padding:18px;
  margin-bottom:18px;
}
.toolbar{
  display:grid;
  grid-template-columns:1fr 1fr auto;
  gap:12px;
  align-items:end;
}
.form-grid{
  display:grid;
  grid-template-columns:repeat(2,1fr);
  gap:12px;
}
.form-group label{
  display:block;
  margin-bottom:8px;
  color:#c8d4ea;
  font-size:14px;
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
}
.form-group textarea{
  min-height:100px;
  resize:vertical;
}
button{
  padding:12px 16px;
  border:none;
  border-radius:14px;
  cursor:pointer;
  font-weight:700;
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
.table-wrap{
  overflow-x:auto;
}
table{
  width:100%;
  border-collapse:collapse;
  min-width:1100px;
}
th,td{
  padding:12px 14px;
  border-bottom:1px solid rgba(255,255,255,0.08);
  text-align:right;
  vertical-align:top;
}
th{
  color:#c8d4ea;
  font-size:13px;
}
.thumb{
  width:60px;
  height:60px;
  object-fit:contain;
  border-radius:12px;
  background:rgba(255,255,255,0.04);
  border:1px solid rgba(255,255,255,0.08);
  padding:5px;
}
.badge{
  display:inline-flex;
  padding:6px 10px;
  border-radius:999px;
  font-size:12px;
  font-weight:700;
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
.empty-box{
  padding:16px;
  border-radius:14px;
  background:rgba(255,255,255,0.04);
  border:1px solid rgba(255,255,255,0.08);
  color:#c8d4ea;
  line-height:1.8;
}
.edit-grid{
  display:grid;
  grid-template-columns:1.05fr 0.95fr;
  gap:18px;
}
.image-box{
  width:100%;
  min-height:340px;
  border:1px dashed rgba(255,255,255,0.14);
  border-radius:18px;
  background:rgba(255,255,255,0.03);
  display:flex;
  align-items:center;
  justify-content:center;
  overflow:hidden;
  padding:16px;
}
.image-box img{
  width:100%;
  max-height:420px;
  object-fit:contain;
}
.links-wrap{
  display:flex;
  flex-direction:column;
  gap:12px;
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
}
.link-meta{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:10px;
}
.meta-box{
  padding:10px 12px;
  border-radius:14px;
  background:rgba(255,255,255,0.05);
  border:1px solid rgba(255,255,255,0.08);
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
}
.link-actions{
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  align-items:end;
  margin-top:12px;
}
.helper-note{
  margin-top:12px;
  padding:12px 14px;
  border-radius:14px;
  background:rgba(255,255,255,0.04);
  border:1px solid rgba(255,255,255,0.08);
  color:#c8d4ea;
  line-height:1.8;
  font-size:13px;
}
.note-inline{
  margin:0 0 14px;
  color:#c8d4ea;
  font-size:13px;
  line-height:1.8;
}
@media (max-width: 1100px){
  .toolbar,.form-grid,.edit-grid,.link-meta{grid-template-columns:1fr;}
}
</style>
</head>
<body>

<h1 class="page-title">Products Manager</h1>
<p class="page-desc">تحميل المنتجات حسب الفئة والبراند، تعديل بياناتها، استبدال الصورة، ومراجعة الأجهزة المرتبطة بالمخزن أو غير المضافة من نفس الشاشة.</p>

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

  <div class="helper-note">اختر الفئة ثم البراند، وبعدها حمّل القائمة. عند فتح منتج للتعديل ستظهر لك حالة الأجهزة: أخضر = موجودة بالمخزن، أحمر = غير مضافة بالمخزن.</div>
  <div id="productsStatus" class="status-box"></div>
</div>

<div class="panel">
  <h2 style="margin:0 0 14px;">Products List</h2>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Image</th>
          <th>Title</th>
          <th>SKU</th>
          <th>Devices</th>
          <th>Price Logic</th>
          <th>Availability</th>
          <th>Hot</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="productsTableBody">
        <tr>
          <td colspan="9"><div class="empty-box">اختر Category و Brand ثم اضغط Load Products.</div></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<div class="panel">
  <h2 style="margin:0 0 14px;">Edit Product</h2>
  <p class="note-inline">حفظ التعديلات سيحدّث قاعدة البيانات وملفات JSON، وسيعيد فحص الربط مع المخزن بناءً على الصورة الجديدة إن وُجدت، أو على العرض الحالي إن لم تغيّر الصورة.</p>

  <div class="edit-grid">
    <div>
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

        <div class="form-group" style="grid-column:1 / -1;">
          <label for="editProductImageInput">Replace Image</label>
          <input id="editProductImageInput" type="file" accept=".jpg,.jpeg,.png,.webp">
        </div>
      </div>

      <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:16px;">
        <button type="button" class="btn-success" id="saveProductChangesBtn">Save Changes</button>
        <button type="button" class="btn-danger" id="deleteProductBtn">Delete Product</button>
      </div>
    </div>

    <div>
      <div class="image-box">
        <img id="editProductPreviewImage" src="" alt="">
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

<script src="products-manager.js?v=20260412-1"></script>
</body>
</html>
