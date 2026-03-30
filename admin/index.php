<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>لوحة التحكم - Click Company</title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body>

<div id="loginPage" class="page-shell">
  <div class="auth-layout">
    <section class="panel panel-brand">
      <div>
        <div class="badge-chip">Click Company • Admin System</div>
        <h1 class="page-title">لوحة تحكم احترافية لإدارة المنتجات والطلبات والمخزون</h1>
        <p class="page-desc">
          هذه النسخة مخصصة لإدارة نظام Click Company بشكل احترافي، وتشمل تسجيل الدخول الآمن،
          إدارة الصلاحيات، وتتوسع لاحقًا إلى OCR والمنتجات والمخزون والطلبات والإحصائيات.
        </p>

        <div class="feature-grid">
          <div class="feature-box">
            <strong>حماية دخول</strong>
            <span>تسجيل دخول حقيقي باستخدام قاعدة البيانات والجلسات الآمنة.</span>
          </div>
          <div class="feature-box">
            <strong>صلاحيات احترافية</strong>
            <span>دعم الأدوار والصلاحيات وربط المستخدمين بالنظام من البداية.</span>
          </div>
          <div class="feature-box">
            <strong>إدارة متقدمة</strong>
            <span>تمهيد لربط المنتجات والمخزون والطلبات داخل لوحة واحدة.</span>
          </div>
          <div class="feature-box">
            <strong>بنية قوية</strong>
            <span>السستم مبني ليعمل بشكل احترافي على الاستضافة بدون حلول مؤقتة.</span>
          </div>
        </div>
      </div>

      <div class="brand-footer">Click Company — You Can Depend on Us</div>
    </section>

    <section class="panel panel-form">
      <h2 class="section-title">تسجيل دخول الأدمن</h2>
      <p class="section-desc">أدخل اسم المستخدم وكلمة المرور للوصول إلى لوحة التحكم.</p>

      <div class="form-group">
        <label for="username">اسم المستخدم</label>
        <input id="username" type="text" placeholder="اكتب اسم المستخدم" autocomplete="username">
      </div>

      <div class="form-group">
        <label for="password">كلمة المرور</label>
        <input id="password" type="password" placeholder="اكتب كلمة المرور" autocomplete="current-password">
      </div>

      <button id="loginBtn" class="btn btn-primary btn-block" type="button">دخول</button>

      <div id="loginStatus" class="status-box"></div>
    </section>
  </div>
</div>

<div id="dashboardPage" class="dashboard-shell hidden">
  <div class="dashboard-wrap">
    <section class="panel">
      <div class="dashboard-head">
        <div>
          <h2 class="section-title no-margin">مرحبًا بك في لوحة التحكم</h2>
          <p class="section-desc no-margin">تم تسجيل الدخول بنجاح. هذه هي نقطة البداية الرسمية لتشغيل النظام على الاستضافة.</p>
        </div>

        <div class="dashboard-actions">
          <a href="/admin/add-product.php" class="btn btn-primary">Add Product</a>
          <button id="logoutBtn" class="btn btn-primary" type="button">تسجيل خروج</button>
        </div>
      </div>

      <div class="info-grid">
        <div class="info-card">
          <strong>الاسم</strong>
          <span id="viewerFullName">-</span>
        </div>
        <div class="info-card">
          <strong>اسم المستخدم</strong>
          <span id="viewerUsername">-</span>
        </div>
        <div class="info-card">
          <strong>الدور</strong>
          <span id="viewerRole">-</span>
        </div>
      </div>

      <div id="dashboardStatus" class="status-box mt-20"></div>
    </section>
  </div>
</div>

<script src="/admin/assets/admin.js"></script>
<script>
  function showDashboard(user) {
    document.getElementById('loginPage').classList.add('hidden');
    document.getElementById('dashboardPage').classList.remove('hidden');

    document.getElementById('viewerFullName').textContent = user?.full_name || '-';
    document.getElementById('viewerUsername').textContent = user?.username || '-';
    document.getElementById('viewerRole').textContent = user?.role_name || '-';
  }

  function showLogin() {
    document.getElementById('dashboardPage').classList.add('hidden');
    document.getElementById('loginPage').classList.remove('hidden');
  }

  async function checkAuth() {
    try {
      const res = await fetch('/admin/api/check-auth.php', {
        method: 'GET',
        credentials: 'same-origin'
      });
      const data = await res.json();

      if (data.ok) {
        showDashboard(data.user);
      } else {
        showLogin();
      }
    } catch (e) {
      showLogin();
    }
  }

  async function doLogin() {
    adminSetStatus('loginStatus', 'info', 'جاري تسجيل الدخول...');

    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;

    if (!username || !password) {
      adminSetStatus('loginStatus', 'error', 'اكتب اسم المستخدم وكلمة المرور.');
      return;
    }

    try {
      const res = await fetch('/admin/api/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ username, password })
      });

      const data = await res.json();

      if (!data.ok) {
        adminSetStatus('loginStatus', 'error', data.message || 'فشل تسجيل الدخول.');
        return;
      }

      adminSetStatus('loginStatus', 'success', data.message || 'تم تسجيل الدخول بنجاح.');
      await checkAuth();
    } catch (e) {
      adminSetStatus('loginStatus', 'error', 'حدث خطأ أثناء تسجيل الدخول.');
    }
  }

  async function doLogout() {
    adminSetStatus('dashboardStatus', 'info', 'جاري تسجيل الخروج...');

    try {
      const res = await fetch('/admin/api/logout.php', {
        method: 'POST',
        credentials: 'same-origin'
      });

      const data = await res.json();

      if (!data.ok) {
        adminSetStatus('dashboardStatus', 'error', data.message || 'فشل تسجيل الخروج.');
        return;
      }

      document.getElementById('password').value = '';
      adminSetStatus('loginStatus', 'success', 'تم تسجيل الخروج بنجاح.');
      showLogin();
    } catch (e) {
      adminSetStatus('dashboardStatus', 'error', 'حدث خطأ أثناء تسجيل الخروج.');
    }
  }

  document.getElementById('loginBtn').addEventListener('click', doLogin);
  document.getElementById('logoutBtn').addEventListener('click', doLogout);

  document.getElementById('password').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
      doLogin();
    }
  });

  checkAuth();
</script>
</body>
</html>
