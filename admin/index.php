<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';

$pdo = db();

$categories = $pdo->query("
    SELECT id, display_name, slug
    FROM categories
    WHERE is_active = 1
    ORDER BY sort_order ASC, id ASC
")->fetchAll();

$brands = $pdo->query("
    SELECT id, category_id, name, slug
    FROM brands
    WHERE is_active = 1
    ORDER BY sort_order ASC, id ASC
")->fetchAll();
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
      margin: 0 0 22px;
      color: #c8d4ea;
      line-height: 1.9;
      font-size: 14px;
    }

    .top-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      align-items: start;
    }

    .sub-card {
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 18px;
      padding: 18px;
    }

    .sub-title {
      margin: 0 0 14px;
      font-size: 17px;
      color: #fff;
      font-weight: 800;
    }

    .stack-gap {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    .form-grid-2 {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 14px;
    }

    .full-col {
      grid-column: 1 / -1;
    }

    .readonly-input {
      background: #0a1120;
      opacity: 0.92;
    }

    .image-stage {
      width: 100%;
      min-height: 440px;
      border: 1px dashed rgba(255,255,255,0.14);
      border-radius: 20px;
      background: rgba(0,0,0,0.16);
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      padding: 16px;
    }

    .image-stage img {
      width: 100%;
      height: 100%;
      max-height: 520px;
      object-fit: contain;
      display: block;
    }

    .image-placeholder {
      color: #c8d4ea;
      font-size: 14px;
      line-height: 1.9;
      text-align: center;
    }

    .action-row {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      margin-top: 16px;
    }

    .secondary-btn {
      background: rgba(255,255,255,0.08);
      border: 1px solid rgba(255,255,255,0.12);
      box-shadow: none;
    }

    .danger-btn {
      background: linear-gradient(135deg, #ef4444, #dc2626);
      box-shadow: 0 12px 24px rgba(239,68,68,0.22);
    }

    .mini-note {
      margin-top: 14px;
      padding: 14px 16px;
      border-radius: 16px;
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      color: #c8d4ea;
      line-height: 1.8;
      font-size: 14px;
    }

    .filter-row {
      display: grid;
      grid-template-columns: 1fr 1fr auto;
      gap: 14px;
      align-items: end;
      margin-bottom: 20px;
    }

    .filters-buffer {
      padding-bottom: 10px;
    }

    .data-table-wrap {
      width: 100%;
      overflow-x: auto;
      margin-top: 18px;
    }

    .data-table {
      width: 100%;
      border-collapse: collapse;
      min-width: 850px;
    }

    .data-table th,
    .data-table td {
      padding: 12px 14px;
      border-bottom: 1px solid rgba(255,255,255,0.08);
      text-align: right;
      color: #fff;
      font-size: 14px;
      vertical-align: middle;
    }

    .data-table th {
      color: #c8d4ea;
      font-weight: 800;
      background: rgba(255,255,255,0.03);
    }

    .table-thumb {
      width: 66px;
      height: 66px;
      border-radius: 14px;
      object-fit: contain;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.08);
      padding: 6px;
      display: block;
    }

    .empty-box {
      margin-top: 18px;
      padding: 18px;
      border-radius: 18px;
      border: 1px solid rgba(255,255,255,0.08);
      background: rgba(255,255,255,0.03);
      color: #c8d4ea;
      font-size: 14px;
      line-height: 1.9;
    }

    .edit-layout {
      display: grid;
      grid-template-columns: 1fr 0.9fr;
      gap: 20px;
      align-items: start;
      margin-top: 20px;
    }

    .placeholder-panels {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
    }

    .placeholder-card {
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 18px;
      padding: 18px;
    }

    .placeholder-card strong {
      display: block;
      margin-bottom: 8px;
      font-size: 15px;
      color: #fff;
    }

    .placeholder-card span {
      display: block;
      color: #c8d4ea;
      line-height: 1.8;
      font-size: 14px;
    }

    @media (max-width: 1200px) {
      .admin-main-tabs {
        grid-template-columns: repeat(2, minmax(180px, 1fr));
      }

      .top-grid,
      .edit-layout,
      .placeholder-panels {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 820px) {
      .form-grid-2,
      .filter-row {
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
            الواجهة الحالية مركزة أولًا على: إضافة المنتج عبر OCR، واستدعاء المنتج وتعديله أو حذفه بشكل منفصل.
          </p>
        </div>

        <div class="dashboard-actions">
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
        <button class="admin-tab-btn active" data-tab="tab-add-ocr" type="button">Add Product (OCR)</button>
        <button class="admin-tab-btn" data-tab="tab-edit-delete" type="button">Edit / Delete Product</button>
        <button class="admin-tab-btn" data-tab="tab-hot-offers" type="button">Hot Offers</button>
        <button class="admin-tab-btn" data-tab="tab-brand-order" type="button">Brand Ordering</button>
        <button class="admin-tab-btn" data-tab="tab-product-order" type="button">Product Ordering</button>
        <button class="admin-tab-btn" data-tab="tab-stock" type="button">Stock Management</button>
        <button class="admin-tab-btn" data-tab="tab-users" type="button">User Permissions</button>
        <button class="admin-tab-btn" data-tab="tab-stats" type="button">Statistics / Orders</button>
      </div>

      <div class="admin-tab-panels">

        <!-- ADD PRODUCT OCR -->
        <div id="tab-add-ocr" class="admin-panel active">
          <h3 class="panel-title">Add Product (OCR)</h3>
          <p class="panel-desc">
            هذا القسم لإضافة منتج جديد. الـ OCR هنا أداة مساعدة فقط، بينما حفظ وربط المنتج بالمخزن يعتمد أيضًا على اسم الملف والحقول المعيارية للمخزن.
          </p>

          <div class="top-grid">
            <div class="sub-card">
              <h4 class="sub-title">Display Image</h4>

              <div class="image-stage">
                <img id="ocrPreviewImage" src="" alt="" class="hidden">
                <div id="ocrPreviewPlaceholder" class="image-placeholder">
                  ارفع صورة للمنتج لتظهر هنا كاملة وواضحة داخل المربع.
                </div>
              </div>

              <input id="ocrImageInput" type="file" accept=".jpg,.jpeg,.png,.webp" class="hidden">

              <div class="action-row">
                <button class="btn btn-primary" type="button" id="ocrUploadBtn">Upload Image</button>
                <button class="btn btn-primary secondary-btn" type="button" id="ocrAnalyzeBtn">Analyze (OCR)</button>
              </div>

              <div class="mini-note">
                الصورة هي العنصر الأساسي في الإضافة. الـ OCR مجرد مساعد في التحليل، وليس بديلًا عن مراجعة البيانات يدويًا قبل الحفظ.
              </div>
            </div>

            <div class="stack-gap">
              <div class="sub-card">
                <h4 class="sub-title">Basic Product Info</h4>

                <div class="form-grid-2 filters-buffer">
                  <div class="form-group">
                    <label for="ocrCategory">Category</label>
                    <select id="ocrCategory">
                      <option value="">Select Category</option>
                      <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>"><?= esc($cat['display_name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="form-group">
                    <label for="ocrBrand">Brand</label>
                    <select id="ocrBrand">
                      <option value="">Select Brand</option>
                      <?php foreach ($brands as $brand): ?>
                        <option value="<?= (int)$brand['id'] ?>" data-category-id="<?= (int)$brand['category_id'] ?>">
                          <?= esc($brand['name']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="form-group full-col">
                    <label for="ocrFileName">File Name</label>
                    <input id="ocrFileName" type="text" class="readonly-input" readonly placeholder="Auto from uploaded image">
                  </div>

                  <div class="form-group full-col">
                    <label for="ocrTitle">Title</label>
                    <input id="ocrTitle" type="text" placeholder="Product title">
                  </div>
                </div>
              </div>

              <div class="sub-card">
                <h4 class="sub-title">Pricing & Display</h4>

                <div class="form-grid-2">
                  <div class="form-group">
                    <label for="ocrDownPayment">Down Payment</label>
                    <input id="ocrDownPayment" type="number" step="0.001" min="0" placeholder="0">
                  </div>

                  <div class="form-group">
                    <label for="ocrMonthlyAmount">Monthly Amount</label>
                    <input id="ocrMonthlyAmount" type="number" step="0.001" min="0" placeholder="0">
                  </div>

                  <div class="form-group">
                    <label for="ocrDurationMonths">Duration Months</label>
                    <input id="ocrDurationMonths" type="number" min="1" placeholder="12">
                  </div>

                  <div class="form-group">
                    <label for="ocrHotOffer">Hot Offer</label>
                    <select id="ocrHotOffer">
                      <option value="0" selected>No</option>
                      <option value="1">Yes</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="sub-card">
                <h4 class="sub-title">Stock Linking Data</h4>

                <div class="form-grid-2">
                  <div class="form-group full-col">
                    <label for="ocrStockDisplayName">Stock Display Name</label>
                    <input id="ocrStockDisplayName" type="text" placeholder="Final stock-facing product name">
                  </div>

                  <div class="form-group full-col">
                    <label for="ocrNormalizedTitle">Normalized Stock Title</label>
                    <input id="ocrNormalizedTitle" type="text" class="readonly-input" readonly placeholder="Auto normalized value">
                  </div>

                  <div class="form-group">
                    <label for="ocrStorageValue">Storage</label>
                    <input id="ocrStorageValue" type="text" placeholder="128GB / 256GB / 1TB">
                  </div>

                  <div class="form-group">
                    <label for="ocrRamValue">RAM</label>
                    <input id="ocrRamValue" type="text" placeholder="8GB RAM / 12GB RAM">
                  </div>

                  <div class="form-group">
                    <label for="ocrNetworkValue">Network</label>
                    <input id="ocrNetworkValue" type="text" placeholder="4G / 5G">
                  </div>

                  <div class="form-group">
                    <label for="ocrDevicesCount">Device Count</label>
                    <input id="ocrDevicesCount" type="number" min="1" class="readonly-input" readonly placeholder="Auto">
                  </div>

                  <div class="form-group">
                    <label for="ocrBrandFromFilename">Brand from Filename</label>
                    <input id="ocrBrandFromFilename" type="text" class="readonly-input" readonly placeholder="Auto">
                  </div>

                  <div class="form-group">
                    <label for="ocrSourceType">Source Type</label>
                    <select id="ocrSourceType">
                      <option value="filename">filename</option>
                      <option value="ocr">ocr</option>
                      <option value="merged" selected>merged</option>
                      <option value="manual">manual</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label for="ocrNeedsReview">Needs Review</label>
                    <select id="ocrNeedsReview">
                      <option value="0" selected>No</option>
                      <option value="1">Yes</option>
                    </select>
                  </div>
                </div>

                <div class="mini-note">
                  هذه الحقول تساعد في منع التكرار، وتحسين الربط مع `stock_catalog` و `product_stock_links` لاحقًا.
                </div>
              </div>

              <div class="action-row">
                <button class="btn btn-primary" type="button" id="ocrSaveBtn">Save Product</button>
              </div>
            </div>
          </div>
        </div>

        <!-- EDIT DELETE -->
        <div id="tab-edit-delete" class="admin-panel">
          <h3 class="panel-title">Edit / Delete Product</h3>
          <p class="panel-desc">
            هذا القسم مخصص لاستدعاء منتج موجود حسب الفئة والبراند، ثم تعديله أو حذفه. تغيير الصورة هنا يتم يدويًا فقط بدون OCR.
          </p>

          <div class="sub-card">
            <h4 class="sub-title">Filters</h4>

            <div class="filter-row filters-buffer">
              <div class="form-group">
                <label for="editCategory">Category</label>
                <select id="editCategory">
                  <option value="">Select Category</option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>"><?= esc($cat['display_name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="editBrand">Brand</label>
                <select id="editBrand">
                  <option value="">Select Brand</option>
                  <?php foreach ($brands as $brand): ?>
                    <option value="<?= (int)$brand['id'] ?>" data-category-id="<?= (int)$brand['category_id'] ?>">
                      <?= esc($brand['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <button class="btn btn-primary" type="button">Load Products</button>
              </div>
            </div>

            <div class="data-table-wrap">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Image</th>
                    <th>Title</th>
                    <th>SKU</th>
                    <th>Price Logic</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><img src="/images/products/sample-product.webp" alt="" class="table-thumb"></td>
                    <td>Sample Product Title</td>
                    <td>SAMPLE-SKU</td>
                    <td>Down / Monthly / Months</td>
                    <td>
                      <div class="action-row">
                        <button class="btn btn-primary secondary-btn" type="button">Edit</button>
                        <button class="btn danger-btn" type="button">Delete</button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="empty-box">
              سيتم هنا تحميل المنتجات الفعلية بناءً على Category + Brand، ثم اختيار المنتج المطلوب للتعديل أو الحذف.
            </div>
          </div>

          <div class="edit-layout">
            <div class="sub-card">
              <h4 class="sub-title">Edit Product</h4>

              <div class="form-grid-2">
                <div class="form-group full-col">
                  <label for="editTitle">Title</label>
                  <input id="editTitle" type="text" placeholder="Selected product title">
                </div>

                <div class="form-group">
                  <label for="editDownPayment">Down Payment</label>
                  <input id="editDownPayment" type="number" step="0.001" min="0">
                </div>

                <div class="form-group">
                  <label for="editMonthlyAmount">Monthly Amount</label>
                  <input id="editMonthlyAmount" type="number" step="0.001" min="0">
                </div>

                <div class="form-group">
                  <label for="editDurationMonths">Duration Months</label>
                  <input id="editDurationMonths" type="number" min="1">
                </div>

                <div class="form-group">
                  <label for="editStorageValue">Storage</label>
                  <input id="editStorageValue" type="text">
                </div>

                <div class="form-group">
                  <label for="editRamValue">RAM</label>
                  <input id="editRamValue" type="text">
                </div>

                <div class="form-group">
                  <label for="editNetworkValue">Network</label>
                  <input id="editNetworkValue" type="text">
                </div>

                <div class="form-group">
                  <label for="editHotOffer">Hot Offer</label>
                  <select id="editHotOffer">
                    <option value="0" selected>No</option>
                    <option value="1">Yes</option>
                  </select>
                </div>
              </div>

              <div class="action-row">
                <button class="btn btn-primary" type="button">Save Changes</button>
                <button class="btn danger-btn" type="button">Delete Product</button>
              </div>
            </div>

            <div class="sub-card">
              <h4 class="sub-title">Current Image</h4>

              <div class="image-stage">
                <img id="editPreviewImage" src="/images/products/sample-product.webp" alt="" style="display:block;">
              </div>

              <input id="editImageInput" type="file" accept=".jpg,.jpeg,.png,.webp" class="hidden">

              <div class="action-row">
                <button class="btn btn-primary secondary-btn" type="button" id="editChangeImageBtn">Change Image</button>
              </div>

              <div class="mini-note">
                تعديل الصورة هنا يتم يدويًا فقط، والصورة تظهر كاملة داخل المربع بدون قص.
              </div>
            </div>
          </div>
        </div>

        <!-- PLACEHOLDERS -->
        <div id="tab-hot-offers" class="admin-panel">
          <h3 class="panel-title">Hot Offers</h3>
          <div class="placeholder-panels">
            <div class="placeholder-card"><strong>Hot Offers</strong><span>سيتم ترتيب وعرض المنتجات المميزة هنا.</span></div>
            <div class="placeholder-card"><strong>Visibility</strong><span>التحكم في إظهار وإخفاء العروض الساخنة.</span></div>
            <div class="placeholder-card"><strong>Ordering</strong><span>ترتيب مستقل للعروض الساخنة.</span></div>
          </div>
        </div>

        <div id="tab-brand-order" class="admin-panel">
          <h3 class="panel-title">Brand Ordering</h3>
          <div class="placeholder-panels">
            <div class="placeholder-card"><strong>Ordering by Category</strong><span>كل فئة لها ترتيب مستقل للبراندات.</span></div>
            <div class="placeholder-card"><strong>Drag & Drop</strong><span>سيتم تجهيز الترتيب المرئي لاحقًا.</span></div>
            <div class="placeholder-card"><strong>Brand Priority</strong><span>حفظ ترتيب البراند بطريقة منظمة.</span></div>
          </div>
        </div>

        <div id="tab-product-order" class="admin-panel">
          <h3 class="panel-title">Product Ordering</h3>
          <div class="placeholder-panels">
            <div class="placeholder-card"><strong>Manual Product Order</strong><span>ترتيب المنتجات داخل البراند أو القسم.</span></div>
            <div class="placeholder-card"><strong>Priority</strong><span>المنتجات الأهم أولاً.</span></div>
            <div class="placeholder-card"><strong>Display Logic</strong><span>تجهيز منطق العرض للواجهة لاحقًا.</span></div>
          </div>
        </div>

        <div id="tab-stock" class="admin-panel">
          <h3 class="panel-title">Stock Management</h3>
          <div class="placeholder-panels">
            <div class="placeholder-card"><strong>stock_catalog</strong><span>تعريف الأصناف الفعلية في المخزن.</span></div>
            <div class="placeholder-card"><strong>product_stock_links</strong><span>ربط المنتج المعروض بالمخزن.</span></div>
            <div class="placeholder-card"><strong>Review Logic</strong><span>إدارة المراجعة والتأكيد للأصناف المستخرجة.</span></div>
          </div>
        </div>

        <div id="tab-users" class="admin-panel">
          <h3 class="panel-title">User Permissions</h3>
          <div class="placeholder-panels">
            <div class="placeholder-card"><strong>Roles</strong><span>إدارة الأدوار مثل Super Admin و Viewer.</span></div>
            <div class="placeholder-card"><strong>Permissions</strong><span>تحديد الصلاحيات لكل مستخدم أو دور.</span></div>
            <div class="placeholder-card"><strong>Users API</strong><span>تمهيد لربط users-list و user-save و user-delete.</span></div>
          </div>
        </div>

        <div id="tab-stats" class="admin-panel">
          <h3 class="panel-title">Statistics / Orders</h3>
          <div class="placeholder-panels">
            <div class="placeholder-card"><strong>Daily</strong><span>إحصائيات يومية للطلبات.</span></div>
            <div class="placeholder-card"><strong>Monthly</strong><span>إحصائيات شهرية للطلبات.</span></div>
            <div class="placeholder-card"><strong>Custom Range</strong><span>تقارير حسب المدة الزمنية.</span></div>
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

  function bindCategoryBrandFilter(categoryId, brandId) {
    const category = document.getElementById(categoryId);
    const brand = document.getElementById(brandId);

    if (!category || !brand) return;

    category.addEventListener('change', function () {
      const value = this.value;
      brand.value = '';
      brand.querySelectorAll('option').forEach(opt => {
        if (!opt.value) {
          opt.hidden = false;
          return;
        }
        opt.hidden = opt.dataset.categoryId !== value;
      });
    });
  }

  function showImagePreview(inputId, imgId, placeholderId = null) {
    const input = document.getElementById(inputId);
    const img = document.getElementById(imgId);
    const placeholder = placeholderId ? document.getElementById(placeholderId) : null;

    if (!input || !img) return;

    input.addEventListener('change', function () {
      const file = this.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = function (e) {
        img.src = e.target.result;
        img.classList.remove('hidden');
        img.style.display = 'block';
        if (placeholder) {
          placeholder.classList.add('hidden');
        }
      };
      reader.readAsDataURL(file);

      if (inputId === 'ocrImageInput') {
        document.getElementById('ocrFileName').value = file.name;
      }
    });
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

  document.getElementById('ocrUploadBtn').addEventListener('click', function () {
    document.getElementById('ocrImageInput').click();
  });

  document.getElementById('editChangeImageBtn').addEventListener('click', function () {
    document.getElementById('editImageInput').click();
  });

  bindCategoryBrandFilter('ocrCategory', 'ocrBrand');
  bindCategoryBrandFilter('editCategory', 'editBrand');
  showImagePreview('ocrImageInput', 'ocrPreviewImage', 'ocrPreviewPlaceholder');
  showImagePreview('editImageInput', 'editPreviewImage');

  checkAuth();
</script>
</body>
</html>
