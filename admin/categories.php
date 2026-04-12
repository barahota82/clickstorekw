<?php
declare(strict_types=1);

require_once __DIR__ . '/check-auth.php';
require_once __DIR__ . '/helpers/permissions_helper.php';

if (!admin_has_permission('brands_order')) {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Access Denied</title>
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
        <p>هذه الصفحة تحتاج صلاحية عرض وإدارة الفئات والبراندات داخل لوحة التحكم.</p>
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
<title>Categories & Brands</title>
<link rel="stylesheet" href="assets/admin.css">
<style>
body {
  margin: 0;
  padding: 20px;
  background: #0f172a;
  color: #fff;
  font-family: Arial, sans-serif;
}
.page-title {
  margin: 0 0 10px;
  font-size: 28px;
  font-weight: 800;
}
.page-desc {
  margin: 0 0 22px;
  color: #c8d4ea;
  line-height: 1.9;
  font-size: 14px;
}
.grid-2 {
  display: grid;
  grid-template-columns: 1.05fr 0.95fr;
  gap: 18px;
}
.panel {
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 20px;
  padding: 18px;
  margin-bottom: 18px;
}
.panel-title {
  margin: 0 0 10px;
  font-size: 19px;
  font-weight: 800;
  color: #fff;
}
.panel-note {
  margin: 0 0 16px;
  color: #c8d4ea;
  line-height: 1.8;
  font-size: 13px;
}
.toolbar,
.form-grid,
.inline-grid {
  display: grid;
  gap: 12px;
}
.form-grid {
  grid-template-columns: repeat(2, 1fr);
}
.inline-grid {
  grid-template-columns: 1fr auto;
  align-items: end;
}
.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.form-group.full {
  grid-column: 1 / -1;
}
label {
  color: #c8d4ea;
  font-size: 13px;
}
input,
select {
  width: 100%;
  box-sizing: border-box;
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid rgba(255,255,255,0.10);
  background: rgba(255,255,255,0.04);
  color: #fff;
  outline: none;
}
input:focus,
select:focus {
  border-color: rgba(37,99,235,0.6);
  box-shadow: 0 0 0 4px rgba(37,99,235,0.12);
}
.checkbox-wrap {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 46px;
  padding: 0 2px;
}
.checkbox-wrap input {
  width: 18px;
  height: 18px;
  margin: 0;
}
.action-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 14px;
}
button {
  border: 0;
  border-radius: 14px;
  padding: 12px 16px;
  font-weight: 800;
  cursor: pointer;
}
.btn-primary {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: #fff;
}
.btn-success {
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: #fff;
}
.btn-secondary {
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.12);
  color: #fff;
}
.table-wrap {
  overflow-x: auto;
}
table {
  width: 100%;
  min-width: 980px;
  border-collapse: collapse;
}
th,
td {
  padding: 12px 14px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  text-align: right;
  vertical-align: middle;
}
th {
  color: #c8d4ea;
  font-size: 13px;
}
.mini-input {
  min-width: 100px;
}
.slug-cell {
  color: #93c5fd;
  font-family: Consolas, Monaco, monospace;
  font-size: 12px;
  white-space: nowrap;
}
.badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 800;
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
  line-height: 1.8;
}
.helper-box {
  margin-top: 14px;
  padding: 14px 16px;
  border-radius: 16px;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  color: #c8d4ea;
  line-height: 1.9;
  font-size: 13px;
}
.section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 14px;
}
.section-head .right-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}
@media (max-width: 1100px) {
  .grid-2,
  .form-grid,
  .inline-grid {
    grid-template-columns: 1fr;
  }
}
</style>
</head>
<body>

<h1 class="page-title">Categories & Brands</h1>
<p class="page-desc">
  من هنا تضيف فئات جديدة للنظام، تتحكم في ظهورها داخل الواجهة الديناميكية، وترتبها، ثم تربط البراندات بكل فئة بنفس البناء الحالي للموقع.
</p>

<div class="grid-2">
  <section class="panel">
    <div class="section-head">
      <div>
        <h2 class="panel-title">إضافة / تعديل الفئات</h2>
        <p class="panel-note">الـ slug يتم إنشاؤه مرة واحدة عند الإضافة ويظل ثابتًا حتى لا تتكسر مسارات المنتجات والصفحات.</p>
      </div>
      <div class="right-actions">
        <button type="button" class="btn-secondary" id="reloadCategoriesBtn">إعادة تحميل</button>
      </div>
    </div>

    <div class="form-grid">
      <div class="form-group">
        <label for="addCategoryNameEn">اسم الفئة EN</label>
        <input id="addCategoryNameEn" type="text" placeholder="مثال: Smart Watches">
      </div>

      <div class="form-group">
        <label for="addCategoryNamePh">اسم الفئة PH</label>
        <input id="addCategoryNamePh" type="text" placeholder="اختياري - لو تركته فارغًا سيأخذ قيمة EN">
      </div>

      <div class="form-group">
        <label for="addCategoryNameHi">اسم الفئة HI</label>
        <input id="addCategoryNameHi" type="text" placeholder="اختياري - لو تركته فارغًا سيأخذ قيمة EN">
      </div>

      <div class="form-group">
        <label for="addCategorySortOrder">Sort Order</label>
        <input id="addCategorySortOrder" type="number" min="1" value="9999">
      </div>

      <div class="form-group">
        <label for="addCategoryNavOrder">Nav Order</label>
        <input id="addCategoryNavOrder" type="number" min="1" value="9999">
      </div>

      <div class="form-group">
        <label>الإظهار / الإخفاء في الواجهة</label>
        <label class="checkbox-wrap">
          <input id="addCategoryVisible" type="checkbox" checked>
          <span>Visible in dynamic category navigation</span>
        </label>
      </div>

      <div class="form-group">
        <label>تفعيل الفئة</label>
        <label class="checkbox-wrap">
          <input id="addCategoryActive" type="checkbox" checked>
          <span>Active</span>
        </label>
      </div>
    </div>

    <div class="action-row">
      <button type="button" class="btn-success" id="addCategoryBtn">إضافة الفئة</button>
    </div>

    <div id="categoriesStatus" class="status-box"></div>

    <div class="table-wrap" style="margin-top:18px;">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Slug</th>
            <th>EN</th>
            <th>PH</th>
            <th>HI</th>
            <th>Sort</th>
            <th>Nav</th>
            <th>Visible</th>
            <th>Active</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody id="categoriesTableBody">
          <tr>
            <td colspan="10">
              <div class="empty-box">جاري تحميل الفئات...</div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

  <section class="panel">
    <div class="section-head">
      <div>
        <h2 class="panel-title">إدارة البراندات داخل الفئة</h2>
        <p class="panel-note">اختر الفئة أولًا، ثم أضف براندات جديدة أو عدّل ترتيب البراندات الحالية داخل نفس الفئة.</p>
      </div>
      <div class="right-actions">
        <button type="button" class="btn-secondary" id="reloadBrandsBtn">إعادة تحميل</button>
      </div>
    </div>

    <div class="form-grid">
      <div class="form-group full">
        <label for="brandsCategoryId">الفئة المحددة</label>
        <select id="brandsCategoryId"></select>
      </div>

      <div class="form-group">
        <label for="addBrandName">اسم البراند</label>
        <input id="addBrandName" type="text" placeholder="مثال: Samsung">
      </div>

      <div class="form-group">
        <label for="addBrandDisplayName">Display Name</label>
        <input id="addBrandDisplayName" type="text" placeholder="اختياري - لو تركته فارغًا سيأخذ نفس الاسم">
      </div>

      <div class="form-group">
        <label for="addBrandSortOrder">Sort Order</label>
        <input id="addBrandSortOrder" type="number" min="1" value="9999">
      </div>

      <div class="form-group">
        <label>تفعيل البراند</label>
        <label class="checkbox-wrap">
          <input id="addBrandActive" type="checkbox" checked>
          <span>Active</span>
        </label>
      </div>
    </div>

    <div class="action-row">
      <button type="button" class="btn-success" id="addBrandBtn">إضافة البراند</button>
    </div>

    <div id="brandsStatus" class="status-box"></div>

    <div class="helper-box">
      تغيير الفئة الخاصة ببراند موجود ليس مفضلًا داخل هذا النظام، لأن المنتجات والصور ومسارات الملفات تعتمد على البنية الحالية. عند الحاجة لفئة مختلفة أنشئ البراند داخل الفئة الجديدة مباشرة.
    </div>

    <div class="table-wrap" style="margin-top:18px;">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Display Name</th>
            <th>Slug</th>
            <th>Sort</th>
            <th>Active</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody id="brandsTableBody">
          <tr>
            <td colspan="7">
              <div class="empty-box">اختر فئة أولًا لعرض البراندات.</div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</div>

<script src="categories.js?v=20260412-1"></script>
</body>
</html>
