<?php require_once __DIR__ . '/check-auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Brand Ordering</title>
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
  grid-template-columns: 1fr auto auto;
  gap: 12px;
  align-items: end;
}
.form-group label {
  display: block;
  margin-bottom: 8px;
  color: #c8d4ea;
  font-size: 14px;
}
.form-group select,
.form-group input {
  width: 100%;
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid rgba(255,255,255,0.10);
  background: rgba(255,255,255,0.04);
  color: #fff;
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
.table-wrap {
  overflow-x: auto;
}
table {
  width: 100%;
  border-collapse: collapse;
  min-width: 760px;
}
th, td {
  padding: 12px 14px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  text-align: left;
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
</style>
</head>
<body>

<h1 class="page-title">Brand Ordering</h1>
<p class="page-desc">اختر الفئة ثم عدّل ترتيب البراندات واحفظ الترتيب مباشرة في قاعدة البيانات.</p>

<div class="panel">
  <div class="toolbar">
    <div class="form-group">
      <label for="brandOrderingCategory">Category</label>
      <select id="brandOrderingCategory"></select>
    </div>

    <button type="button" class="btn-primary" id="loadBrandOrderingBtn">Load Brands</button>
    <button type="button" class="btn-success" id="saveBrandOrderingBtn">Save Ordering</button>
  </div>

  <div id="brandOrderingStatus" class="status-box"></div>
</div>

<div class="panel">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Brand Name</th>
          <th>Slug</th>
          <th>Status</th>
          <th>Sort Order</th>
        </tr>
      </thead>
      <tbody id="brandOrderingTableBody">
        <tr>
          <td colspan="5">
            <div class="empty-box">اختر Category ثم اضغط Load Brands.</div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<script src="brand-ordering.js"></script>
</body>
</html>
