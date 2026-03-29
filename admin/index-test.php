<?php
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>لوحة التحكم - Click Company</title>
  <style>
    * { box-sizing: border-box; }
    :root {
      --bg: #0b1220;
      --panel: #121b2d;
      --panel-2: #0d1525;
      --text: #ffffff;
      --muted: #b9c5da;
      --line: rgba(255,255,255,0.10);
      --blue: #2563eb;
      --green: #22c55e;
      --red: #dc2626;
    }

    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: var(--bg);
      color: var(--text);
    }

    .hidden { display: none !important; }

    .wrap {
      width: min(1200px, 95%);
      margin: 20px auto 40px;
    }

    .card {
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: 18px;
      padding: 18px;
      box-shadow: 0 10px 24px rgba(0,0,0,0.25);
    }

    .login-wrap {
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 20px;
    }

    .login-card {
      width: min(460px, 100%);
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: 22px;
      padding: 24px;
      box-shadow: 0 18px 40px rgba(0,0,0,0.30);
    }

    h1, h2, h3 {
      margin-top: 0;
    }

    .muted {
      color: var(--muted);
      line-height: 1.6;
      font-size: 14px;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 14px;
    }

    .grid-3 {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 14px;
    }

    .full { grid-column: 1 / -1; }

    label {
      display: block;
      margin-bottom: 6px;
      font-size: 13px;
      color: #d7e2f3;
    }

    input, select, textarea {
      width: 100%;
      border: 1px solid rgba(255,255,255,0.12);
      background: var(--panel-2);
      color: #fff;
      border-radius: 12px;
      padding: 12px 13px;
      font-size: 14px;
      outline: none;
    }

    textarea {
      min-height: 120px;
      resize: vertical;
      line-height: 1.5;
      font-family: inherit;
    }

    .btns {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 14px;
    }

    button {
      border: 0;
      border-radius: 12px;
      padding: 12px 16px;
      font-weight: bold;
      cursor: pointer;
      font-size: 14px;
      color: #fff;
    }

    .btn-primary { background: var(--green); }
    .btn-secondary { background: var(--blue); }
    .btn-danger { background: var(--red); }
    .btn-dark { background: #1f2937; }

    .status {
      margin-top: 12px;
      padding: 12px 14px;
      border-radius: 12px;
      font-size: 14px;
      white-space: pre-wrap;
      display: none;
    }

    .status.show { display: block; }
    .status.ok {
      background: rgba(34,197,94,0.12);
      border: 1px solid rgba(34,197,94,0.28);
      color: #bbf7d0;
    }
    .status.err {
      background: rgba(239,68,68,0.12);
      border: 1px solid rgba(239,68,68,0.28);
      color: #fecaca;
    }
    .status.info {
      background: rgba(37,99,235,0.12);
      border: 1px solid rgba(37,99,235,0.28);
      color: #bfdbfe;
    }

    .tabs {
      display: flex;
      gap: 10px;
      overflow-x: auto;
      padding-bottom: 6px;
      margin-bottom: 16px;
    }

    .tab-btn {
      white-space: nowrap;
      background: rgba(255,255,255,0.05);
      border: 1px solid var(--line);
      color: #fff;
      padding: 12px 16px;
      border-radius: 14px;
      cursor: pointer;
      font-weight: bold;
      flex: 0 0 auto;
    }

    .tab-btn.active {
      background: linear-gradient(135deg, #37a0ff, #2f6bff);
      border-color: transparent;
    }

    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    .checkline {
      display: flex;
      align-items: center;
      gap: 8px;
      min-height: 40px;
    }

    .checkline input[type="checkbox"] {
      width: auto;
      transform: scale(1.05);
    }

    @media (max-width: 960px) {
      .form-grid, .grid-3 {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

  <div id="loginView" class="login-wrap">
    <div class="login-card">
      <h1>تسجيل دخول الأدمن</h1>
      <p class="muted">هذا دخول داخلي سريع. الحماية الأساسية عندك تبقى من .htaccess + .htpasswd + HTTPS.</p>

      <div class="form-grid" style="margin-top:16px;">
        <div class="full">
          <label for="loginUsername">اسم المستخدم</label>
          <input id="loginUsername" type="text" placeholder="admin" />
        </div>

        <div class="full">
          <label for="loginPassword">كلمة المرور</label>
          <input id="loginPassword" type="password" placeholder="اكتب كلمة المرور" />
        </div>
      </div>

      <div class="btns">
        <button class="btn-primary" type="button" onclick="login()">دخول</button>
      </div>

      <div id="loginStatus" class="status"></div>
    </div>
  </div>

  <div id="appView" class="hidden">
    <div class="wrap">
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
          <div>
            <h1 style="margin-bottom:6px;">لوحة التحكم</h1>
            <div class="muted">النسخة العملية الأولى - تعديل المنتج فعليًا على السيرفر</div>
          </div>
          <button class="btn-danger" onclick="logout()">تسجيل خروج</button>
        </div>
      </div>

      <div class="tabs" style="margin-top:16px;">
        <button class="tab-btn active" onclick="openTab('edit-product', this)">تعديل منتج</button>
        <button class="tab-btn" onclick="openTab('ocr', this)">OCR</button>
        <button class="tab-btn" onclick="openTab('brand-order', this)">ترتيب البراندات</button>
        <button class="tab-btn" onclick="openTab('product-order', this)">ترتيب المنتجات</button>
        <button class="tab-btn" onclick="openTab('all-products-order', this)">ترتيب All Products</button>
        <button class="tab-btn" onclick="openTab('hot-offers-order', this)">ترتيب Hot Offers</button>
        <button class="tab-btn" onclick="openTab('delete-product', this)">حذف منتج</button>
      </div>

      <div id="tab-edit-product" class="tab-panel active card">
        <h2>استدعاء منتج وتعديله</h2>

        <div class="grid-3">
          <div>
            <label>الفئة</label>
            <select id="editCategory" onchange="loadProductFileList()">
              <option>phones</option>
              <option>tablets</option>
              <option>laptops</option>
              <option>accessories</option>
            </select>
          </div>

          <div>
            <label>اسم ملف المنتج</label>
            <select id="editFile">
            <option value="">اختر المنتج</option>
            </select>
          </div>

          <div style="display:flex;align-items:end;">
            <button class="btn-secondary" type="button" onclick="loadProduct()">استدعاء المنتج</button>
          </div>
        </div>

        <div class="form-grid" style="margin-top:18px;">
          <div>
            <label>العنوان</label>
            <input id="editTitle" type="text" />
          </div>

          <div>
            <label>البراند</label>
            <input id="editBrand" type="text" />
          </div>

          <div>
            <label>القسط الشهري</label>
            <input id="editMonthly" type="text" />
          </div>

          <div>
            <label>المدة</label>
            <input id="editDuration" type="text" />
          </div>

          <div>
            <label>المقدم</label>
            <input id="editDown" type="text" />
          </div>

          <div>
            <label>عدد الأجهزة</label>
            <input id="editDevices" type="number" min="1" />
          </div>

          <div>
            <label>ترتيب البراند</label>
            <input id="editBrandPriority" type="number" min="1" />
          </div>

          <div>
            <label>ترتيب المنتج</label>
            <input id="editPriority" type="number" min="1" />
          </div>

          <div class="full">
            <label>رابط الصورة</label>
            <input id="editImage" type="text" />
          </div>

          <div class="checkline full">
            <input id="editAvailable" type="checkbox" />
            <label for="editAvailable" style="margin:0;">المنتج متاح</label>
          </div>

          <div class="checkline full">
            <input id="editHot" type="checkbox" />
            <label for="editHot" style="margin:0;">المنتج داخل Hot Offers</label>
          </div>
        </div>

        <div class="btns">
          <button class="btn-primary" type="button" onclick="saveProduct()">حفظ التعديلات</button>
        </div>

        <div id="editStatus" class="status"></div>
      </div>

      <div id="tab-ocr" class="tab-panel card"><h2>OCR</h2><div class="muted">سيتم ربطه لاحقًا.</div></div>
      <div id="tab-brand-order" class="tab-panel card"><h2>ترتيب البراندات</h2><div class="muted">سيتم ربطه لاحقًا.</div></div>
      <div id="tab-product-order" class="tab-panel card"><h2>ترتيب المنتجات داخل البراند</h2><div class="muted">سيتم ربطه لاحقًا.</div></div>
      <div id="tab-all-products-order" class="tab-panel card"><h2>ترتيب All Products</h2><div class="muted">سيتم ربطه لاحقًا.</div></div>
      <div id="tab-hot-offers-order" class="tab-panel card"><h2>ترتيب Hot Offers</h2><div class="muted">سيتم ربطه لاحقًا.</div></div>
      <div id="tab-delete-product" class="tab-panel card"><h2>حذف منتج</h2><div class="muted">سيتم ربطه لاحقًا.</div></div>
    </div>
  </div>

  <script>
    const STORAGE_SESSION_KEY = "click_admin_session";
    const DEFAULT_ADMIN_USER = "admin";
    const DEFAULT_ADMIN_PASS = "Admin@12345";

    function setStatus(id, type, message) {
      const box = document.getElementById(id);
      if (!box) return;
      box.className = `status show ${type}`;
      box.textContent = message;
    }

    function clearStatus(id) {
      const box = document.getElementById(id);
      if (!box) return;
      box.className = "status";
      box.textContent = "";
    }

    function login() {
      clearStatus("loginStatus");

      const username = document.getElementById("loginUsername").value.trim();
      const password = document.getElementById("loginPassword").value.trim();

      if (username === DEFAULT_ADMIN_USER && password === DEFAULT_ADMIN_PASS) {
        localStorage.setItem(STORAGE_SESSION_KEY, "1");
        bootApp();
        return;
      }

      setStatus("loginStatus", "err", "بيانات الدخول غير صحيحة.");
    }

    function logout() {
      localStorage.removeItem(STORAGE_SESSION_KEY);
      document.getElementById("appView").classList.add("hidden");
      document.getElementById("loginView").classList.remove("hidden");
    }

    function bootApp() {
      const loggedIn = localStorage.getItem(STORAGE_SESSION_KEY) === "1";

      if (loggedIn) {
        document.getElementById("loginView").classList.add("hidden");
        document.getElementById("appView").classList.remove("hidden");
      } else {
        document.getElementById("appView").classList.add("hidden");
        document.getElementById("loginView").classList.remove("hidden");
      }
    }

    function openTab(tabId, btn) {
      document.querySelectorAll(".tab-panel").forEach(el => el.classList.remove("active"));
      document.querySelectorAll(".tab-btn").forEach(el => el.classList.remove("active"));

      document.getElementById(`tab-${tabId}`)?.classList.add("active");
      if (btn) btn.classList.add("active");
    }

    let currentProductContext = null;
    async function loadProductFileList() {
  const category = document.getElementById("editCategory").value;
  const select = document.getElementById("editFile");

  select.innerHTML = `<option value="">جاري تحميل المنتجات...</option>`;

  try {
    const res = await fetch(`/products/${category}/index.json`);
    const files = await res.json();

    select.innerHTML = `<option value="">اختر المنتج</option>`;

    files.forEach(file => {
      const option = document.createElement("option");
      option.value = file;
      option.textContent = file
        .replace(".json", "")
        .replace(/\//g, " / ")
        .replace(/-/g, " ");
      select.appendChild(option);
    });
  } catch (e) {
    select.innerHTML = `<option value="">فشل تحميل المنتجات</option>`;
  }
}

    async function loadProduct() {
      clearStatus("editStatus");

      const category = document.getElementById("editCategory").value;
      const file = document.getElementById("editFile").value.trim();

      if (!file) {
        setStatus("editStatus", "err", "اكتب اسم ملف المنتج أولًا.");
        return;
      }

      try {
        const res = await fetch(`api/load-product.php?category=${encodeURIComponent(category)}&file=${encodeURIComponent(file)}`);
        const data = await res.json();

        if (!data.ok) {
          setStatus("editStatus", "err", data.message || "فشل تحميل المنتج.");
          return;
        }

        const p = data.product;
        currentProductContext = { category: data.category, file: data.file };

        document.getElementById("editTitle").value = p.title || "";
        document.getElementById("editBrand").value = p.brand || "";
        document.getElementById("editMonthly").value = p.monthly || "";
        document.getElementById("editDuration").value = p.duration || "";
        document.getElementById("editDown").value = p.down_payment || "";
        document.getElementById("editDevices").value = p.devices_count || 1;
        document.getElementById("editImage").value = p.image || "";
        document.getElementById("editAvailable").checked = !!p.available;
        document.getElementById("editHot").checked = !!p.hot_offer;
        document.getElementById("editBrandPriority").value = p.brand_priority ?? 9999;
        document.getElementById("editPriority").value = p.priority ?? 9999;

        setStatus("editStatus", "ok", "تم تحميل المنتج بنجاح.");
      } catch (e) {
        setStatus("editStatus", "err", "حدث خطأ أثناء تحميل المنتج.");
      }
    }

    async function saveProduct() {
      clearStatus("editStatus");

      if (!currentProductContext) {
        setStatus("editStatus", "err", "يجب استدعاء المنتج أولًا.");
        return;
      }

      const category = currentProductContext.category;
      const file = currentProductContext.file;

      const product = {
        title: document.getElementById("editTitle").value.trim(),
        category,
        brand: document.getElementById("editBrand").value.trim(),
        devices_count: Number(document.getElementById("editDevices").value || 1),
        image: document.getElementById("editImage").value.trim(),
        down_payment: document.getElementById("editDown").value.trim(),
        monthly: document.getElementById("editMonthly").value.trim(),
        duration: document.getElementById("editDuration").value.trim(),
        available: document.getElementById("editAvailable").checked,
        hot_offer: document.getElementById("editHot").checked,
        brand_priority: Number(document.getElementById("editBrandPriority").value || 9999),
        priority: Number(document.getElementById("editPriority").value || 9999)
      };

      try {
        const res = await fetch("api/save-product.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json"
          },
          body: JSON.stringify({
            category,
            file,
            product
          })
        });

        const data = await res.json();

        if (!data.ok) {
          setStatus("editStatus", "err", data.message || "فشل حفظ المنتج.");
          return;
        }

        setStatus("editStatus", "ok", "تم حفظ التعديلات بنجاح.");
      } catch (e) {
        setStatus("editStatus", "err", "حدث خطأ أثناء حفظ المنتج.");
      }
    }

    bootApp();
   loadProductFileList();
  </script>
</body>
</html>
