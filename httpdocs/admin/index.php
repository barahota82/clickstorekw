<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>لوحة التحكم - Click Company</title>
  <style>
    * { box-sizing: border-box; }

    :root{
      --bg:#081120;
      --card:#0f172a;
      --card-2:#111c34;
      --line:rgba(255,255,255,0.08);
      --text:#ffffff;
      --muted:#b8c4d9;
      --primary:#2563eb;
      --primary-2:#1d4ed8;
      --success:#22c55e;
      --danger:#ef4444;
      --shadow:0 20px 50px rgba(0,0,0,0.35);
      --radius:22px;
    }

    body{
      margin:0;
      font-family:Arial, sans-serif;
      background:
        radial-gradient(circle at top, rgba(37,99,235,0.22), transparent 35%),
        linear-gradient(180deg, #081120 0%, #0b1220 100%);
      color:var(--text);
      min-height:100vh;
    }

    .page{
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:24px;
    }

    .login-shell{
      width:min(1100px,100%);
      display:grid;
      grid-template-columns: 1.05fr 0.95fr;
      gap:24px;
      align-items:stretch;
    }

    .brand-card,
    .login-card,
    .dashboard-card{
      background:linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
      border:1px solid var(--line);
      border-radius:var(--radius);
      box-shadow:var(--shadow);
      backdrop-filter: blur(8px);
    }

    .brand-card{
      padding:34px;
      display:flex;
      flex-direction:column;
      justify-content:space-between;
      min-height:560px;
    }

    .brand-badge{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:10px 14px;
      border-radius:999px;
      background:rgba(37,99,235,0.14);
      border:1px solid rgba(37,99,235,0.32);
      color:#cfe0ff;
      width:max-content;
      font-size:13px;
      font-weight:bold;
    }

    .brand-title{
      font-size:44px;
      line-height:1.15;
      margin:18px 0 12px;
      font-weight:800;
    }

    .brand-desc{
      color:var(--muted);
      line-height:1.9;
      font-size:16px;
      max-width:540px;
    }

    .feature-list{
      display:grid;
      grid-template-columns:repeat(2, 1fr);
      gap:14px;
      margin-top:30px;
    }

    .feature{
      background:rgba(255,255,255,0.04);
      border:1px solid var(--line);
      border-radius:18px;
      padding:16px;
    }

    .feature strong{
      display:block;
      margin-bottom:8px;
      font-size:15px;
    }

    .feature span{
      color:var(--muted);
      font-size:13px;
      line-height:1.8;
    }

    .brand-footer{
      margin-top:26px;
      color:#d9e5ff;
      font-weight:bold;
      font-size:15px;
    }

    .login-card{
      padding:32px;
      min-height:560px;
      display:flex;
      flex-direction:column;
      justify-content:center;
    }

    .login-title{
      margin:0 0 10px;
      font-size:38px;
      font-weight:800;
    }

    .login-subtitle{
      margin:0 0 24px;
      color:var(--muted);
      line-height:1.9;
      font-size:15px;
    }

    .form-group{
      margin-bottom:18px;
    }

    label{
      display:block;
      margin-bottom:8px;
      font-size:14px;
      color:#dce7f9;
      font-weight:bold;
    }

    input{
      width:100%;
      border:1px solid rgba(255,255,255,0.12);
      background:#0b1326;
      color:#fff;
      border-radius:16px;
      padding:15px 16px;
      font-size:15px;
      outline:none;
      transition:0.2s ease;
    }

    input:focus{
      border-color:rgba(37,99,235,0.7);
      box-shadow:0 0 0 4px rgba(37,99,235,0.12);
    }

    .submit-btn,
    .logout-btn{
      width:100%;
      border:0;
      border-radius:16px;
      padding:15px 18px;
      font-size:16px;
      font-weight:800;
      color:#fff;
      cursor:pointer;
      background:linear-gradient(135deg, var(--primary), var(--primary-2));
      transition:0.2s ease;
      box-shadow:0 14px 28px rgba(37,99,235,0.25);
    }

    .submit-btn:hover,
    .logout-btn:hover{
      transform:translateY(-1px);
    }

    .status{
      margin-top:18px;
      padding:14px 16px;
      border-radius:16px;
      font-size:14px;
      line-height:1.8;
      display:none;
      white-space:pre-wrap;
    }

    .status.show{ display:block; }
    .status.ok{
      background:rgba(34,197,94,0.12);
      border:1px solid rgba(34,197,94,0.26);
      color:#c9f7d8;
    }
    .status.err{
      background:rgba(239,68,68,0.12);
      border:1px solid rgba(239,68,68,0.26);
      color:#ffd0d0;
    }
    .status.info{
      background:rgba(37,99,235,0.12);
      border:1px solid rgba(37,99,235,0.26);
      color:#d6e4ff;
    }

    .dashboard{
      min-height:100vh;
      padding:24px;
      display:none;
    }

    .dashboard.show{
      display:block;
    }

    .dashboard-wrap{
      width:min(1200px, 100%);
      margin:0 auto;
    }

    .dashboard-card{
      padding:24px;
    }

    .dashboard-head{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:16px;
      flex-wrap:wrap;
    }

    .dashboard-title{
      margin:0 0 6px;
      font-size:34px;
      font-weight:800;
    }

    .dashboard-desc{
      margin:0;
      color:var(--muted);
      line-height:1.8;
      font-size:14px;
    }

    .welcome-grid{
      display:grid;
      grid-template-columns:repeat(3, 1fr);
      gap:16px;
      margin-top:18px;
    }

    .welcome-box{
      background:rgba(255,255,255,0.04);
      border:1px solid var(--line);
      border-radius:18px;
      padding:18px;
    }

    .welcome-box strong{
      display:block;
      margin-bottom:10px;
      font-size:14px;
    }

    .welcome-box span{
      color:var(--muted);
      font-size:14px;
    }

    .hidden{
      display:none !important;
    }

    @media (max-width: 980px){
      .login-shell{
        grid-template-columns:1fr;
      }
      .brand-card,
      .login-card{
        min-height:auto;
      }
      .feature-list,
      .welcome-grid{
        grid-template-columns:1fr;
      }
      .brand-title{
        font-size:34px;
      }
      .login-title{
        font-size:30px;
      }
    }
  </style>
</head>
<body>

<div id="loginPage" class="page">
  <div class="login-shell">
    <div class="brand-card">
      <div>
        <div class="brand-badge">Click Company • Admin System</div>
        <h1 class="brand-title">لوحة تحكم احترافية لإدارة المنتجات والطلبات والمخزون</h1>
        <p class="brand-desc">
          هذه النسخة مخصصة لإدارة نظام Click Company بشكل احترافي، وتشمل تسجيل الدخول الآمن،
          إدارة الصلاحيات، وتتوسع لاحقًا إلى OCR والمنتجات والمخزون والطلبات والإحصائيات.
        </p>

        <div class="feature-list">
          <div class="feature">
            <strong>حماية دخول</strong>
            <span>تسجيل دخول حقيقي باستخدام قاعدة البيانات والجلسات الآمنة.</span>
          </div>
          <div class="feature">
            <strong>صلاحيات احترافية</strong>
            <span>دعم الأدوار والصلاحيات وربط المستخدمين بالنظام من البداية.</span>
          </div>
          <div class="feature">
            <strong>إدارة متقدمة</strong>
            <span>تمهيد لربط المنتجات والمخزون والطلبات داخل لوحة واحدة.</span>
          </div>
          <div class="feature">
            <strong>بنية قوية</strong>
            <span>السستم مبني ليعمل بشكل احترافي على الاستضافة بدون حلول مؤقتة.</span>
          </div>
        </div>
      </div>

      <div class="brand-footer">Click Company — You Can Depend on Us</div>
    </div>

    <div class="login-card">
      <h2 class="login-title">تسجيل دخول الأدمن</h2>
      <p class="login-subtitle">
        أدخل اسم المستخدم وكلمة المرور للوصول إلى لوحة التحكم.
      </p>

      <div class="form-group">
        <label for="username">اسم المستخدم</label>
        <input id="username" type="text" placeholder="اكتب اسم المستخدم" autocomplete="username">
      </div>

      <div class="form-group">
        <label for="password">كلمة المرور</label>
        <input id="password" type="password" placeholder="اكتب كلمة المرور" autocomplete="current-password">
      </div>

      <button id="loginBtn" class="submit-btn" type="button">دخول</button>

      <div id="loginStatus" class="status"></div>
    </div>
  </div>
</div>

<div id="dashboardPage" class="dashboard">
  <div class="dashboard-wrap">
    <div class="dashboard-card">
      <div class="dashboard-head">
        <div>
          <h2 class="dashboard-title">مرحبًا بك في لوحة التحكم</h2>
          <p class="dashboard-desc">
            تم تسجيل الدخول بنجاح. هذه هي نقطة البداية الرسمية لتشغيل النظام على الاستضافة.
          </p>
        </div>

        <div style="width:min(220px,100%);">
          <button id="logoutBtn" class="logout-btn" type="button">تسجيل خروج</button>
        </div>
      </div>

      <div class="welcome-grid">
        <div class="welcome-box">
          <strong>الاسم</strong>
          <span id="viewerFullName">-</span>
        </div>
        <div class="welcome-box">
          <strong>اسم المستخدم</strong>
          <span id="viewerUsername">-</span>
        </div>
        <div class="welcome-box">
          <strong>الدور</strong>
          <span id="viewerRole">-</span>
        </div>
      </div>

      <div id="dashboardStatus" class="status" style="margin-top:20px;"></div>
    </div>
  </div>
</div>

<script>
  function setStatus(id, type, message) {
    const box = document.getElementById(id);
    if (!box) return;
    box.className = `status show ${type}`;
    box.textContent = message;
  }

  function clearStatus(id) {
    const box = document.getElementById(id);
    if (!box) return;
    box.className = 'status';
    box.textContent = '';
  }

  function showDashboard(user) {
    document.getElementById('loginPage').classList.add('hidden');
    document.getElementById('dashboardPage').classList.add('show');

    document.getElementById('viewerFullName').textContent = user?.full_name || '-';
    document.getElementById('viewerUsername').textContent = user?.username || '-';
    document.getElementById('viewerRole').textContent = user?.role_name || '-';
  }

  function showLogin() {
    document.getElementById('dashboardPage').classList.remove('show');
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
    clearStatus('loginStatus');

    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;

    if (!username || !password) {
      setStatus('loginStatus', 'err', 'اكتب اسم المستخدم وكلمة المرور.');
      return;
    }

    try {
      setStatus('loginStatus', 'info', 'جاري تسجيل الدخول...');

      const res = await fetch('/admin/api/login.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          username,
          password
        })
      });

      const data = await res.json();

      if (!data.ok) {
        setStatus('loginStatus', 'err', data.message || 'فشل تسجيل الدخول.');
        return;
      }

      setStatus('loginStatus', 'ok', data.message || 'تم تسجيل الدخول بنجاح.');
      await checkAuth();
    } catch (e) {
      setStatus('loginStatus', 'err', 'حدث خطأ أثناء تسجيل الدخول.');
    }
  }

  async function doLogout() {
    clearStatus('dashboardStatus');

    try {
      const res = await fetch('/admin/api/logout.php', {
        method: 'POST',
        credentials: 'same-origin'
      });

      const data = await res.json();

      if (!data.ok) {
        setStatus('dashboardStatus', 'err', data.message || 'فشل تسجيل الخروج.');
        return;
      }

      showLogin();
      document.getElementById('password').value = '';
      setStatus('loginStatus', 'ok', 'تم تسجيل الخروج بنجاح.');
    } catch (e) {
      setStatus('dashboardStatus', 'err', 'حدث خطأ أثناء تسجيل الخروج.');
    }
  }

  document.getElementById('loginBtn').addEventListener('click', doLogin);
  document.getElementById('logoutBtn').addEventListener('click', doLogout);

  document.getElementById('password').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      doLogin();
    }
  });

  checkAuth();
</script>
</body>
</html>
