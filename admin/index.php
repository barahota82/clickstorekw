<?php
session_start();
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>لوحة التحكم - Click Company</title>
  <link rel="stylesheet" href="assets/admin.css">
  <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
</head>
<body>

  <div id="loginView" class="login-wrap <?php echo $isLoggedIn ? 'hidden' : ''; ?>">
    <div class="login-card">
      <h1>تسجيل دخول الأدمن</h1>
      <p class="muted">دخول لوحة التحكم</p>

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

  <div id="appView" class="<?php echo $isLoggedIn ? '' : 'hidden'; ?>">
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

        <div class="edit-load-row">
          <div class="edit-category-box">
            <label>الفئة</label>
            <select id="editCategory" onchange="loadProductFileList()">
              <option>phones</option>
              <option>tablets</option>
              <option>laptops</option>
              <option>accessories</option>
            </select>
          </div>

          <div class="edit-file-box">
            <label>اسم ملف المنتج</label>
            <select id="editFile">
              <option value="">اختر المنتج</option>
            </select>
          </div>

          <div class="edit-load-btn-box">
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
            <label>المقدم (KD)</label>
            <input id="editDown" type="number" min="0" placeholder="مثال: 10" />
          </div>

          <div>
            <label>عدد الأجهزة</label>
            <input id="editDevices" type="number" min="1" />
          </div>

          <div>
            <label>القسط الشهري (KD)</label>
            <input id="editMonthly" type="number" min="1" placeholder="مثال: 5" />
          </div>

          <div>
            <label>ترتيب البراند</label>
            <input id="editBrandPriority" type="number" min="1" />
          </div>

          <div>
            <label>عدد الشهور</label>
            <input id="editDuration" type="number" min="1" placeholder="مثال: 12" />
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

      <div id="tab-ocr" class="tab-panel card">
        <h2>OCR</h2>
        <div class="muted" style="margin-bottom:18px;">
          رفع صورة المنتج ثم استخراج: المقدم / القسط / مدة السداد
        </div>

        <div class="form-grid">
          <div>
            <label for="ocrCategory">الفئة</label>
            <select id="ocrCategory">
              <option value="phones">phones</option>
              <option value="tablets">tablets</option>
              <option value="laptops">laptops</option>
              <option value="accessories">accessories</option>
            </select>
          </div>

          <div>
            <label for="ocrBrand">البراند</label>
            <input id="ocrBrand" type="text" placeholder="مثال: Samsung / Honor / Apple" />
          </div>

          <div class="full">
            <label for="ocrTitle">العنوان</label>
            <input id="ocrTitle" type="text" placeholder="عنوان المنتج أو العرض" />
          </div>

          <div>
            <label for="ocrDevicesCount">عدد الأجهزة</label>
            <input id="ocrDevicesCount" type="number" min="1" value="1" />
          </div>

          <div>
            <label for="ocrDownPayment">المقدم (KD)</label>
            <input id="ocrDownPayment" type="number" min="0" step="1" value="0" />
          </div>

          <div>
            <label for="ocrMonthly">القسط الشهري (KD)</label>
            <input id="ocrMonthly" type="number" min="0" step="1" placeholder="مثال: 10" />
          </div>

          <div>
            <label for="ocrDuration">عدد الشهور</label>
            <input id="ocrDuration" type="number" min="1" step="1" placeholder="مثال: 12" />
          </div>

          <div class="checkline">
            <input id="ocrAvailable" type="checkbox" checked />
            <label for="ocrAvailable" style="margin:0;">متاح</label>
          </div>

          <div class="checkline">
            <input id="ocrHotOffer" type="checkbox" />
            <label for="ocrHotOffer" style="margin:0;">Hot Offer</label>
          </div>

          <div class="full">
            <label for="ocrProductImage">صورة المنتج</label>
            <input id="ocrProductImage" type="file" accept=".webp,.png,.jpg,.jpeg" />
          </div>
        </div>

        <div class="btns">
          <button class="btn-secondary" type="button" onclick="ocrSmartAutoFill()">Smart Auto Fill</button>
          <button class="btn-dark" type="button" onclick="copyOcrToEditTab()">نسخ إلى تبويب تعديل المنتج</button>
          <button class="btn-danger" type="button" onclick="resetOCRForm()">إعادة تعيين</button>
        </div>

        <div id="ocrStatus" class="status"></div>

        <div style="margin-top:18px;" class="form-grid">
          <div class="full">
            <label>نص OCR المستخرج</label>
            <textarea id="ocrRawText" readonly style="min-height:180px;"></textarea>
          </div>
        </div>

        <div style="margin-top:18px;">
          <img id="ocrPreviewImage" style="display:none;width:100%;max-height:380px;object-fit:contain;border-radius:14px;background:#fff;" alt="OCR Preview" />
        </div>
      </div>

      <div id="tab-brand-order" class="tab-panel card"><h2>ترتيب البراندات</h2><div class="muted">سيتم ربطه لاحقًا.</div></div>
      <div id="tab-product-order" class="tab-panel card"><h2>ترتيب المنتجات داخل البراند</h2><div class="muted">سيتم ربطه لاحقًا.</div></div>
      <div id="tab-all-products-order" class="tab-panel card"><h2>ترتيب All Products</h2><div class="muted">سيتم ربطه لاحقًا.</div></div>
      <div id="tab-hot-offers-order" class="tab-panel card"><h2>ترتيب Hot Offers</h2><div class="muted">سيتم ربطه لاحقًا.</div></div>
      <div id="tab-delete-product" class="tab-panel card"><h2>حذف منتج</h2><div class="muted">سيتم ربطه لاحقًا.</div></div>
    </div>
  </div>

  <script>
    window.ADMIN_IS_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
  </script>
  <script src="assets/admin.js"></script>
</body>
</html>
