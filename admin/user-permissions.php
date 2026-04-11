<?php require_once __DIR__ . '/check-auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Permissions</title>
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
  grid-template-columns: 1fr auto;
  gap: 12px;
  align-items: end;
}
.form-group label {
  display: block;
  margin-bottom: 8px;
  color: #c8d4ea;
  font-size: 14px;
}
.form-group input,
.form-group select {
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
  min-width: 1100px;
}
th, td {
  padding: 12px 14px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  text-align: left;
  vertical-align: top;
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
.permission-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  max-width: 420px;
}
.permission-chip {
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.08);
  font-size: 12px;
  color: #fff;
}
.permissions-modal {
  position: fixed;
  inset: 0;
  background: rgba(2,6,23,0.82);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
}
.permissions-modal.active {
  display: flex;
}
.permissions-box {
  width: min(960px, 100%);
  max-height: 88vh;
  overflow: auto;
  background: #0f172a;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 20px;
  padding: 20px;
}
.permissions-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}
.permissions-title {
  color: #fff;
  font-size: 20px;
  font-weight: 800;
  margin: 0;
}
.permissions-close {
  border: none;
  background: rgba(255,255,255,0.08);
  color: #fff;
  width: 42px;
  height: 42px;
  border-radius: 12px;
  cursor: pointer;
  font-size: 22px;
}
.permissions-groups {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}
.permission-group {
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 16px;
  padding: 14px;
}
.permission-group h4 {
  margin: 0 0 12px;
  color: #fff;
  font-size: 15px;
}
.permission-option {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
  color: #c8d4ea;
  font-size: 14px;
}
.permission-option input {
  width: auto;
}
.modal-actions {
  display: flex;
  gap: 10px;
  margin-top: 18px;
  flex-wrap: wrap;
}
@media (max-width: 1100px) {
  .permissions-groups {
    grid-template-columns: 1fr;
  }
}
</style>
</head>
<body>

<h1 class="page-title">User Permissions</h1>
<p class="page-desc">إدارة المستخدمين والأدوار والصلاحيات المباشرة من نفس الشاشة.</p>

<div class="panel">
  <div class="toolbar">
    <div class="form-group">
      <label for="usersSearchInput">Search</label>
      <input id="usersSearchInput" type="text" placeholder="Username / Name / Email / Role">
    </div>

    <button type="button" class="btn-primary" id="loadUsersPermissionsBtn">Load Users</button>
  </div>

  <div id="usersPermissionsStatus" class="status-box"></div>
</div>

<div class="panel">
  <h2 style="margin:0 0 14px;">Users</h2>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Username</th>
          <th>Full Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Status</th>
          <th>Direct Permissions</th>
          <th>Last Login</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="usersPermissionsTableBody">
        <tr>
          <td colspan="9"><div class="empty-box">اضغط Load Users لتحميل البيانات.</div></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<div id="userPermissionsModal" class="permissions-modal">
  <div class="permissions-box">
    <div class="permissions-head">
      <h3 class="permissions-title" id="userPermissionsModalTitle">User Permissions</h3>
      <button type="button" class="permissions-close" id="userPermissionsCloseBtn">×</button>
    </div>

    <div id="userPermissionsGroups" class="permissions-groups"></div>

    <div class="modal-actions">
      <button type="button" class="btn-success" id="saveUserPermissionsBtn">Save Permissions</button>
    </div>
  </div>
</div>

<script src="user-permissions.js"></script>
</body>
</html>
