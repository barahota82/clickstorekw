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
  <style>
    .admin-main-tabs {
      display: grid;
      grid-template-columns: repeat(4, minmax(180px, 1fr));
      gap: 12px;
      margin-top: 24px;
    }

    .admin-tab-btn {
      border: 1px solid rgba(255,255,255,0.10);
      background: rgba(255,255,255,0.04);
      color: #fff;
      border-radius: 16px;
      padding: 14px 16px;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      transition: 0.2s ease;
      text-align: center;
    }

    .admin-tab-btn:hover,
    .admin-tab-btn.active {
      background: linear-gradient(135deg, #2563eb, #1d4ed8);
      box-shadow: 0 12px 24px rgba(37,99,235,0.22);
      border-color: transparent;
    }

    .admin-tab-panels {
      margin-top: 22px;
    }

    .admin-panel {
      display: none;
      background: rgba(255,255,255,0.03);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 20px;
      padding: 22px;
      margin-top: 14px;
    }

    .admin-panel.active {
      display: block;
    }

    .panel-title {
      font-size: 24px;
      font-weight: 800;
      margin: 0 0 8px;
      color: #fff;
    }

    .panel-desc {
      margin: 0 0 20px;
      color: #c8d4ea;
      line-height: 1.9;
      font-size: 14px;
    }

    .panel-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
    }

    .mini-card {
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 18px;
      padding: 18px;
      min-height: 120px;
    }

    .mini-card strong {
      display: block;
      margin-bottom: 10px;
      font-size: 15px;
      color: #fff;
    }

    .mini-card span {
      color: #c8d4ea;
      line-height: 1.8;
      font-size: 14px;
      display: block;
    }

    .quick-actions {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      margin-top: 18px;
    }

    .quick-actions a,
    .quick-actions button {
      border: 0;
      border-radius: 14px;
      padding: 12px 18px;
      font-size: 14px;
      font-weight: 800;
      color: #fff;
      cursor: pointer;
      text-decoration: none;
      background: linear-gradient(135deg, #2563eb, #1d4ed8);
      box-shadow: 0 12px 24px rgba(37,99,235,0.22);
    }

    .coming-soon {
      margin-top: 16px;
      padding: 14px 16px;
      border-radius: 16px;
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      color: #c8d4ea;
      line-height: 1.8;
      font-size: 14px;
    }

    @media (max-width: 1100px) {
      .admin-main-tabs {
        grid-template-columns: repeat(2, minmax(180px, 1fr));
      }

      .panel-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 640px) {
      .admin-main-tabs {
        grid-template-columns: 1fr;
      }
    }
  </style>
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
          <p class="section-desc no-margin">
            تم تسجيل الدخول بنجاح. هذه هي الواجهة الأساسية للإدارة، وبها التابات الرئيسية المتفق عليها.
          </p>
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

      <div class="admin-main-tabs">
        <button class="admin-tab-btn active" data-tab="tab-ocr" type="button">OCR</button>
        <button class="admin-tab-btn" data-tab="tab-brand-order" type="button">Brand Ordering</button>
        <button class="admin-tab-btn" data-tab="tab-product-order" type="button">Product Ordering</button>
        <button class="admin-tab-btn" data-tab="tab-hot-offers" type="button">Hot Offers</button>
        <button class="admin-tab-btn" data-tab="tab-edit-delete" type="button">Edit / Delete Product</button>
        <button class="admin-tab-btn" data-tab="tab-stock" type="button">Stock Management</button>
        <button class="admin-tab-btn" data-tab="tab-users" type="button">User Permissions</button>
        <button class="admin-tab-btn" data-tab="tab-stats" type="button">Statistics / Orders</button>
      </div>

      <div class="admin-tab-panels">
        <div id="tab-ocr" class="admin-panel active">
          <h3 class="panel-title">OCR</h3>
          <p class="panel-desc">
            هذا القسم مخصص لتحليل صور المنتجات، واستخراج أسماء الأجهزة منها وربطها بالمخزون تلقائيًا مع الاستفادة من اسم الملف.
          </p>

          <div class="panel-grid">
            <div class="mini-card">
              <strong>تحليل الصورة</strong>
              <span>الاعتماد على OCR لاحقًا لاستخراج الأجهزة الموجودة في الصورة بدقة أعلى.</span>
            </div>
            <div class="mini-card">
              <strong>اسم الملف</strong>
              <span>يتم الاستفادة من اسم الملف كمدخل مساعد لتحديد البراند والموديل والسعة والرام والـ 5G.</span>
            </div>
            <div class="mini-card">
              <strong>منع التكرار</strong>
              <span>الأجهزة المستخرجة تُقارن بجدول stock_catalog ويُضاف غير الموجود فقط.</span>
            </div>
          </div>

          <div class="coming-soon">هذه الواجهة مركبة الآن كواجهة رئيسية، والربط الداخلي الكامل للـ OCR سنبنيه بعد تثبيت بقية الأقسام.</div>
        </div>

        <div id="tab-brand-order" class="admin-panel">
          <h3 class="panel-title">Brand Ordering</h3>
          <p class="panel-desc">
            هذا القسم مسؤول عن ترتيب البراندات داخل كل فئة بما يظهر للعميل بشكل منظم واحترافي.
          </p>

          <div class="panel-grid">
            <div class="mini-card">
              <strong>ترتيب داخل الفئة</strong>
              <span>كل فئة لها ترتيب مستقل للبراندات الخاصة بها.</span>
            </div>
            <div class="mini-card">
              <strong>سحب وإفلات لاحقًا</strong>
              <span>سيتم تجهيز واجهة سحب وإفلات للترتيب بسهولة.</span>
            </div>
            <div class="mini-card">
              <strong>عرض بصري</strong>
              <span>يُفضّل ربط البراند بصورة أو لوجو لسهولة الإدارة.</span>
            </div>
          </div>
        </div>

        <div id="tab-product-order" class="admin-panel">
          <h3 class="panel-title">Product Ordering</h3>
          <p class="panel-desc">
            هذا القسم مسؤول عن ترتيب المنتجات داخل كل براند أو قسم حسب ما تريد أن يظهر للعميل.
          </p>

          <div class="panel-grid">
            <div class="mini-card">
              <strong>ترتيب يدوي</strong>
              <span>إمكانية التحكم في ترتيب المنتج بشكل مباشر.</span>
            </div>
            <div class="mini-card">
              <strong>ترتيب حسب الحالة</strong>
              <span>يمكن لاحقًا دعم ترتيب حسب التوفر أو العروض أو الأحدث.</span>
            </div>
            <div class="mini-card">
              <strong>ربط مع hot offers</strong>
              <span>بعض المنتجات قد يكون لها أولوية خاصة في العرض.</span>
            </div>
          </div>
        </div>

        <div id="tab-hot-offers" class="admin-panel">
          <h3 class="panel-title">Hot Offers</h3>
          <p class="panel-desc">
            قسم العروض الساخنة مسؤول عن المنتجات المميزة التي تريد إبرازها في الواجهة الرئيسية أو الأقسام.
          </p>

          <div class="panel-grid">
            <div class="mini-card">
              <strong>تفعيل سريع</strong>
              <span>كل منتج يمكن تمييزه كعرض ساخن من صفحة الإضافة أو التعديل.</span>
            </div>
            <div class="mini-card">
              <strong>ترتيب مستقل</strong>
              <span>سيكون للعروض الساخنة ترتيب منفصل عن الترتيب العام للمنتجات.</span>
            </div>
            <div class="mini-card">
              <strong>تحكم إداري</strong>
              <span>إظهار أو إخفاء العروض حسب الحاجة.</span>
            </div>
          </div>
        </div>

        <div id="tab-edit-delete" class="admin-panel">
          <h3 class="panel-title">Edit / Delete Product</h3>
          <p class="panel-desc">
            قسم تعديل وحذف المنتجات لعرض المنتجات الحالية، والبحث فيها، وفتح صفحة تعديل أو تنفيذ الحذف.
          </p>

          <div class="quick-actions">
            <a href="/admin/add-product.php">إضافة منتج جديد</a>
          </div>

          <div class="coming-soon">واجهة الجدول والبحث والتعديل المباشر سنضيفها بعد تثبيت الحفظ والربط بالمخزن.</div>
        </div>

        <div id="tab-stock" class="admin-panel">
          <h3 class="panel-title">Stock Management</h3>
          <p class="panel-desc">
            هذا القسم هو قلب الربط بين المنتج المعروض والمخزن الفعلي عبر stock_catalog و product_stock_links.
          </p>

          <div class="panel-grid">
            <div class="mini-card">
              <strong>stock_catalog</strong>
              <span>حفظ أسماء الأجهزة المعيارية مع بيانات السعة والرام والشبكة.</span>
            </div>
            <div class="mini-card">
              <strong>product_stock_links</strong>
              <span>ربط كل منتج في الموقع بجهاز أو أكثر داخل المخزن.</span>
            </div>
            <div class="mini-card">
              <strong>تحليل تلقائي</strong>
              <span>الربط يبدأ من اسم الملف حاليًا، ثم يتوسع لاحقًا مع OCR.</span>
            </div>
          </div>
        </div>

        <div id="tab-users" class="admin-panel">
          <h3 class="panel-title">User Permissions</h3>
          <p class="panel-desc">
            هذا القسم مسؤول عن إدارة المستخدمين والأدوار والصلاحيات.
          </p>

          <div class="panel-grid">
            <div class="mini-card">
              <strong>Roles</strong>
              <span>ربط كل مستخدم بدور محدد مثل Super Admin أو Viewer.</span>
            </div>
            <div class="mini-card">
              <strong>Permissions</strong>
              <span>تحديد ما الذي يستطيع المستخدم فتحه أو تعديله.</span>
            </div>
            <div class="mini-card">
              <strong>Users API</strong>
              <span>ملفات users-list و user-save و user-delete موجودة بالفعل كبنية أولية.</span>
            </div>
          </div>
        </div>

        <div id="tab-stats" class="admin-panel">
          <h3 class="panel-title">Statistics / Orders</h3>
          <p class="panel-desc">
            هذا القسم مخصص لإحصائيات الطلبات والعمليات اليومية والشهرية، وسيتوسع لاحقًا إلى تقارير مفصلة.
          </p>

          <div class="panel-grid">
            <div class="mini-card">
              <strong>إحصاء يومي</strong>
              <span>عدد الطلبات وقيمتها في اليوم الحالي.</span>
            </div>
            <div class="mini-card">
              <strong>إحصاء شهري</strong>
              <span>ملخص شهري للطلبات والمبيعات.</span>
            </div>
            <div class="mini-card">
              <strong>مدى زمني مخصص</strong>
              <span>اختيار من تاريخ إلى تاريخ لعرض النتائج.</span>
            </div>
          </div>
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

  document.querySelectorAll('.admin-tab-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.admin-tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.admin-panel').forEach(p => p.classList.remove('active'));

      this.classList.add('active');
      const target = document.getElementById(this.dataset.tab);
      if (target) {
        target.classList.add('active');
      }
    });
  });

  checkAuth();
</script>
</body>
</html>
