<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

$pdo = db();

$categoriesStmt = $pdo->query("
    SELECT id, display_name, slug
    FROM categories
    WHERE (is_active = 1 OR is_active IS NULL)
    ORDER BY sort_order ASC, id ASC
");
$categories = $categoriesStmt ? $categoriesStmt->fetchAll(PDO::FETCH_ASSOC) : [];

$brandsStmt = $pdo->query("
    SELECT id, category_id, name, slug
    FROM brands
    WHERE (is_active = 1 OR is_active IS NULL)
    ORDER BY sort_order ASC, id ASC
");
$brands = $brandsStmt ? $brandsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>لوحة التحكم - Click Company</title>
  <link rel="stylesheet" href="/admin/assets/admin.css?v=20260407-1">
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
      grid-template-columns: 1.05fr 0.95fr;
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
      min-height: 470px;
      border: 1px dashed rgba(255,255,255,0.14);
      border-radius: 20px;
      background: rgba(0,0,0,0.16);
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      padding: 16px;
      position: relative;
    }

    .image-stage img {
      width: 100%;
      height: 100%;
      max-height: 560px;
      object-fit: contain;
      display: block;
      user-select: none;
      pointer-events: none;
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
      border: none;
      color: #fff;
    }

    .success-btn {
      background: linear-gradient(135deg, #22c55e, #16a34a);
      box-shadow: 0 12px 24px rgba(34,197,94,0.22);
      border: none;
      color: #fff;
    }

    .warning-btn {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      box-shadow: 0 12px 24px rgba(245,158,11,0.22);
      border: none;
      color: #fff;
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

    .orders-toolbar {
      display: grid;
      grid-template-columns: 1fr 180px 180px auto auto;
      gap: 12px;
      align-items: end;
      margin-bottom: 18px;
    }

    .status-chip {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 120px;
      padding: 8px 12px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 800;
      color: #fff;
      white-space: nowrap;
    }

    .status-pending {
      background: rgba(59,130,246,0.16);
      border: 1px solid rgba(59,130,246,0.28);
    }

    .status-delivered {
      background: rgba(34,197,94,0.16);
      border: 1px solid rgba(34,197,94,0.28);
    }

    .status-cancelled {
      background: rgba(239,68,68,0.16);
      border: 1px solid rgba(239,68,68,0.28);
    }

    .status-rejected {
      background: rgba(245,158,11,0.16);
      border: 1px solid rgba(245,158,11,0.28);
    }

    .order-actions-cell {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .order-items-preview {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .order-items-preview span {
      color: #c8d4ea;
      font-size: 13px;
      line-height: 1.7;
    }

    .summary-cards {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin-bottom: 18px;
    }

    .summary-card {
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 18px;
      padding: 18px;
    }

    .summary-card strong {
      display: block;
      font-size: 14px;
      color: #c8d4ea;
      margin-bottom: 8px;
    }

    .summary-card span {
      font-size: 26px;
      font-weight: 800;
      color: #fff;
    }

    .history-modal {
      position: fixed;
      inset: 0;
      background: rgba(2,6,23,0.82);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      padding: 20px;
    }

    .history-modal.active {
      display: flex;
    }

    .history-box {
      width: min(900px, 100%);
      max-height: 85vh;
      overflow: auto;
      background: #0f172a;
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 20px;
      padding: 20px;
    }

    .history-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 16px;
    }

    .history-title {
      color: #fff;
      font-size: 20px;
      font-weight: 800;
      margin: 0;
    }

    .history-close {
      border: none;
      background: rgba(255,255,255,0.08);
      color: #fff;
      width: 42px;
      height: 42px;
      border-radius: 12px;
      cursor: pointer;
      font-size: 22px;
    }

    .history-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .history-item {
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 16px;
      padding: 14px;
    }

    .history-row {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 8px;
      color: #fff;
      font-size: 14px;
    }

    .history-meta {
      color: #c8d4ea;
      font-size: 13px;
      line-height: 1.8;
    }

    .json-preview-box {
      width: 100%;
      min-height: 220px;
      background: #0a1120;
      color: #22c55e;
      border: 1px solid rgba(255,255,255,0.10);
      border-radius: 14px;
      padding: 14px;
      font-size: 13px;
      font-family: Consolas, Monaco, monospace;
      line-height: 1.8;
      resize: vertical;
      direction: ltr;
      text-align: left;
    }

    .stock-review-wrap {
      margin-top: 18px;
      display: grid;
      gap: 12px;
    }

    .stock-review-head {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      align-items: center;
      justify-content: space-between;
    }

    .stock-review-legend {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items: center;
    }

    .legend-chip {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 800;
      color: #fff;
      border: 1px solid rgba(255,255,255,0.10);
      background: rgba(255,255,255,0.05);
    }

    .legend-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      display: inline-block;
    }

    .legend-dot.green {
      background: #22c55e;
      box-shadow: 0 0 0 4px rgba(34,197,94,0.18);
    }

    .legend-dot.red {
      background: #ef4444;
      box-shadow: 0 0 0 4px rgba(239,68,68,0.18);
    }

    .stock-review-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 14px;
    }

    .stock-review-card {
      border-radius: 18px;
      padding: 16px;
      border: 1px solid rgba(255,255,255,0.08);
      background: rgba(255,255,255,0.04);
    }

    .stock-review-card.is-linked {
      border-color: rgba(34,197,94,0.35);
      background: rgba(34,197,94,0.08);
    }

    .stock-review-card.is-missing {
      border-color: rgba(239,68,68,0.35);
      background: rgba(239,68,68,0.08);
    }

    .stock-review-card-head {
      display: flex;
      gap: 10px;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 10px;
    }

    .stock-review-title {
      color: #fff;
      font-size: 15px;
      font-weight: 800;
      margin: 0;
      line-height: 1.7;
    }

    .stock-state-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 90px;
      padding: 8px 12px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 800;
      color: #fff;
      white-space: nowrap;
    }

    .stock-state-badge.linked {
      background: rgba(34,197,94,0.18);
      border: 1px solid rgba(34,197,94,0.30);
    }

    .stock-state-badge.missing {
      background: rgba(239,68,68,0.18);
      border: 1px solid rgba(239,68,68,0.30);
    }

    .stock-review-meta {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-top: 10px;
    }

    .stock-review-meta .mini-box {
      border-radius: 14px;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.08);
      padding: 10px 12px;
    }

    .stock-review-meta .mini-box strong {
      display: block;
      color: #fff;
      font-size: 12px;
      margin-bottom: 4px;
    }

    .stock-review-meta .mini-box span {
      color: #c8d4ea;
      font-size: 13px;
      line-height: 1.7;
    }

    .stock-review-actions {
      margin-top: 14px;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items: end;
    }

    .stock-review-select {
      min-width: 180px;
    }

    .hidden {
      display: none !important;
    }

    @media (max-width: 1200px) {
      .admin-main-tabs {
        grid-template-columns: repeat(2, minmax(180px, 1fr));
      }

      .top-grid,
      .edit-layout,
      .placeholder-panels,
      .summary-cards,
      .stock-review-grid {
        grid-template-columns: 1fr;
      }

      .orders-toolbar {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 820px) {
      .form-grid-2,
      .filter-row,
      .stock-review-meta {
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
          إدارة الصلاحيات، وإدارة المنتجات والمخزون والطلبات داخل لوحة واحدة.
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
            <span>ربط المنتجات بالمخزون والتحكم في الطلبات من نفس اللوحة.</span>
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
            الواجهة الحالية مركزة على: إضافة المنتج من الصورة واسم الملف، ومراجعة ربط أجهزة الصورة بالمخزن، وإدارة الطلبات.
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
        <button class="admin-tab-btn active" data-tab="tab-add-product" data-permission="products_create" id="tabBtnAddProduct" type="button">Add Product</button>
        <button class="admin-tab-btn" data-tab="tab-edit-delete" data-permission="products_edit" id="tabBtnEditDelete" type="button">Edit / Delete Product</button>
        <button class="admin-tab-btn" data-tab="tab-hot-offers" data-permission="hot_offers_order" id="tabBtnHotOffers" type="button">Hot Offers</button>
        <button class="admin-tab-btn" data-tab="tab-brand-order" data-permission="brands_order" id="tabBtnBrandOrder" type="button">Brand Ordering</button>
        <button class="admin-tab-btn" data-tab="tab-product-order" data-permission="products_order" id="tabBtnProductOrder" type="button">Product Ordering</button>
        <button class="admin-tab-btn" data-tab="tab-stock" data-permission="stock_manage" id="tabBtnStock" type="button">Stock Management</button>
        <button class="admin-tab-btn" data-tab="tab-users" data-permission="users_view" id="tabBtnUsers" type="button">User Permissions</button>
        <button class="admin-tab-btn" data-tab="tab-stats" data-permission="orders_view" id="tabBtnStats" type="button">Statistics / Orders</button>
      </div>

      <div class="admin-tab-panels">

        <div id="tab-add-product" class="admin-panel active" data-panel-permission="products_create">
          <h3 class="panel-title">Add Product</h3>
          <p class="panel-desc">
            هذا القسم لإضافة منتج جديد . واضافة اسماء الاصناف الي المخزن .
          </p>

          <div class="top-grid">
            <div class="sub-card">
              <h4 class="sub-title">Display Image</h4>

              <div id="ocrImageStage" class="image-stage">
                <img id="ocrPreviewImage" src="" alt="" class="hidden">
                <div id="ocrPreviewPlaceholder" class="image-placeholder">
                  ارفع صورة للمنتج لتظهر هنا كاملة وواضحة داخل المربع.
                </div>
              </div>

              <input id="ocrImageInput" type="file" accept=".jpg,.jpeg,.png,.webp" class="hidden">

              <div class="action-row">
                <button class="btn btn-primary" type="button" id="ocrUploadBtn" data-permission="products_create">Upload Image</button>
                <button class="btn btn-primary secondary-btn" type="button" id="ocrClearDataBtn" data-permission="products_create">Clear Form</button>
              </div>

              <div class="stock-review-wrap">
                <div class="stock-review-head">
                  <h4 class="sub-title" style="margin:0;">Stock Review From File Name</h4>

                  <div class="stock-review-legend">
                    <span class="legend-chip">
                      <span class="legend-dot green"></span>
                      Added To Stock
                    </span>
                    <span class="legend-chip">
                      <span class="legend-dot red"></span>
                      Not Added
                    </span>
                  </div>
                </div>

                <div id="stockReviewGrid" class="stock-review-grid">
                  <div class="stock-review-card is-linked">
                    <div class="stock-review-card-head">
                      <h5 class="stock-review-title">Sample Device 1 256GB 8GB RAM</h5>
                      <span class="stock-state-badge linked">Added</span>
                    </div>

                    <div class="stock-review-meta">
                      <div class="mini-box">
                        <strong>Brand</strong>
                        <span>Samsung</span>
                      </div>
                      <div class="mini-box">
                        <strong>Category</strong>
                        <span>Phones</span>
                      </div>
                    </div>
                  </div>

                  <div class="stock-review-card is-missing">
                    <div class="stock-review-card-head">
                      <h5 class="stock-review-title">Sample Device 2 512GB 12GB RAM</h5>
                      <span class="stock-state-badge missing">Not Added</span>
                    </div>

                    <div class="stock-review-meta">
                      <div class="mini-box">
                        <strong>Brand</strong>
                        <span>Unknown</span>
                      </div>
                      <div class="mini-box">
                        <strong>Status</strong>
                        <span>Needs category selection</span>
                      </div>
                    </div>

                    <div class="stock-review-actions">
                      <div class="form-group stock-review-select">
                        <label>Choose Category</label>
                        <select>
                          <option value="">Select Category</option>
                          <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars((string)$cat['display_name'], ENT_QUOTES, 'UTF-8') ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>

                      <button class="btn success-btn" type="button" data-permission="stock_manage">Add To Stock</button>
                    </div>
                  </div>

                  <div class="stock-review-card is-missing">
                    <div class="stock-review-card-head">
                      <h5 class="stock-review-title">Slot 3</h5>
                      <span class="stock-state-badge missing">Empty</span>
                    </div>

                    <div class="mini-note" style="margin-top:0;">
                      سيتم استخدام هذا المكان تلقائيًا إذا احتوى اسم الملف على جهاز ثالث.
                    </div>
                  </div>

                  <div class="stock-review-card is-missing">
                    <div class="stock-review-card-head">
                      <h5 class="stock-review-title">Slot 4</h5>
                      <span class="stock-state-badge missing">Empty</span>
                    </div>

                    <div class="mini-note" style="margin-top:0;">
                      هذا هو آخر حد مدعوم داخل الصورة الواحدة.
                    </div>
                  </div>
                </div>
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
                    </select>
                  </div>

                  <div class="form-group">
                    <label for="ocrBrandFromFilename">Brand</label>
                    <input id="ocrBrandFromFilename" type="text" class="readonly-input" readonly placeholder="Auto from first device in file name">
                  </div>

                  <div class="form-group full-col">
                    <label for="ocrFileName">File Name</label>
                    <input id="ocrFileName" type="text" class="readonly-input" readonly placeholder="Auto from uploaded image">
                  </div>

                  <div class="form-group full-col">
                    <label for="ocrTitle">Title</label>
                    <input id="ocrTitle" type="text" placeholder="Product title">
                  </div>

                  <div class="form-group full-col">
                    <label for="ocrStockDisplayName">Stock Display Name</label>
                    <input id="ocrStockDisplayName" type="text" placeholder="Final stock-facing product name">
                  </div>

                  <div class="form-group">
                    <label for="ocrDevicesCount">Device Count</label>
                    <input id="ocrDevicesCount" type="number" min="1" max="4" class="readonly-input" readonly placeholder="Auto">
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

                <div class="action-row">
                  <button class="btn btn-primary secondary-btn" type="button" id="ocrConfirmManualEditBtn" data-permission="products_create">Confirm Manual Edit</button>
                </div>
              </div>

              <div class="sub-card">
                <h4 class="sub-title">Product JSON Preview</h4>

                <textarea id="productJsonPreview" class="json-preview-box" readonly placeholder="سيظهر هنا JSON المنتج للمراجعة قبل الحفظ..."></textarea>
              </div>

              <div class="action-row">
                <button class="btn btn-primary" type="button" id="ocrSaveBtn" data-permission="products_create">Save Product</button>
              </div>
            </div>
          </div>
        </div>

        <div id="tab-edit-delete" class="admin-panel" data-panel-permission="products_edit">
          <h3 class="panel-title">Edit / Delete Product</h3>
          <p class="panel-desc">
            هذا القسم مخصص لاستدعاء منتج موجود حسب الفئة والبراند، ثم تعديله أو حذفه. تغيير الصورة هنا يتم يدويًا فقط.
          </p>

          <div class="sub-card">
            <h4 class="sub-title">Filters</h4>

            <div class="filter-row filters-buffer">
              <div class="form-group">
                <label for="editCategory">Category</label>
                <select id="editCategory">
                  <option value="">Select Category</option>
                </select>
              </div>

              <div class="form-group">
                <label for="editBrand">Brand</label>
                <select id="editBrand">
                  <option value="">Select Brand</option>
                </select>
              </div>

              <div class="form-group">
                <button class="btn btn-primary" type="button" id="editLoadProductsBtn" data-permission="products_edit">Load Products</button>
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
                        <button class="btn btn-primary secondary-btn" type="button" data-permission="products_edit">Edit</button>
                        <button class="btn danger-btn" type="button" data-permission="products_delete">Delete</button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="empty-box">
              سيتم هنا تحميل المنتجات الفعلية بناءً على Category + Brand.
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
                  <label for="editHotOffer">Hot Offer</label>
                  <select id="editHotOffer">
                    <option value="0" selected>No</option>
                    <option value="1">Yes</option>
                  </select>
                </div>
              </div>

              <div class="action-row">
                <button class="btn btn-primary" type="button" id="editSaveChangesBtn" data-permission="products_edit">Save Changes</button>
                <button class="btn danger-btn" type="button" id="editDeleteProductBtn" data-permission="products_delete">Delete Product</button>
              </div>
            </div>

            <div class="sub-card">
              <h4 class="sub-title">Current Image</h4>

              <div class="image-stage">
                <img id="editPreviewImage" src="/images/products/sample-product.webp" alt="" style="display:block;">
              </div>

              <input id="editImageInput" type="file" accept=".jpg,.jpeg,.png,.webp" class="hidden">

              <div class="action-row">
                <button class="btn btn-primary secondary-btn" type="button" id="editChangeImageBtn" data-permission="products_edit">Change Image</button>
              </div>

              <div class="mini-note">
                تعديل الصورة هنا يتم يدويًا فقط، والصورة تظهر كاملة داخل المربع بدون قص.
              </div>
            </div>
          </div>
        </div>

        <div id="tab-hot-offers" class="admin-panel" data-panel-permission="hot_offers_order">
          <h3 class="panel-title">Hot Offers</h3>
          <div class="placeholder-panels">
            <div class="placeholder-card"><strong>Hot Offers</strong><span>سيتم ترتيب وعرض المنتجات المميزة هنا.</span></div>
            <div class="placeholder-card"><strong>Visibility</strong><span>التحكم في إظهار وإخفاء العروض الساخنة.</span></div>
            <div class="placeholder-card"><strong>Ordering</strong><span>ترتيب مستقل للعروض الساخنة.</span></div>
          </div>
        </div>

        <div id="tab-brand-order" class="admin-panel" data-panel-permission="brands_order">
          <h3 class="panel-title">Brand Ordering</h3>
          <div class="placeholder-panels">
            <div class="placeholder-card"><strong>Ordering by Category</strong><span>كل فئة لها ترتيب مستقل للبراندات.</span></div>
            <div class="placeholder-card"><strong>Drag & Drop</strong><span>سيتم تجهيز الترتيب المرئي لاحقًا.</span></div>
            <div class="placeholder-card"><strong>Brand Priority</strong><span>حفظ ترتيب البراند بطريقة منظمة.</span></div>
          </div>
        </div>

        <div id="tab-product-order" class="admin-panel" data-panel-permission="products_order">
          <h3 class="panel-title">Product Ordering</h3>
          <div class="placeholder-panels">
            <div class="placeholder-card"><strong>Manual Product Order</strong><span>ترتيب المنتجات داخل البراند أو القسم.</span></div>
            <div class="placeholder-card"><strong>Priority</strong><span>المنتجات الأهم أولاً.</span></div>
            <div class="placeholder-card"><strong>Display Logic</strong><span>تجهيز منطق العرض للواجهة لاحقًا.</span></div>
          </div>
        </div>

        <div id="tab-stock" class="admin-panel" data-panel-permission="stock_manage">
          <h3 class="panel-title">Stock Management</h3>
          <div class="placeholder-panels">
            <div class="placeholder-card"><strong>stock_catalog</strong><span>تعريف الأصناف الفعلية في المخزن.</span></div>
            <div class="placeholder-card"><strong>product_stock_links</strong><span>ربط المنتج المعروض بالمخزن.</span></div>
            <div class="placeholder-card"><strong>Review Logic</strong><span>إدارة المراجعة والتأكيد للأصناف المستخرجة.</span></div>
          </div>
        </div>

        <div id="tab-users" class="admin-panel" data-panel-permission="users_view">
          <h3 class="panel-title">User Permissions</h3>
          <div class="placeholder-panels">
            <div class="placeholder-card"><strong>Roles</strong><span>إدارة الأدوار مثل Super Admin و Viewer.</span></div>
            <div class="placeholder-card"><strong>Permissions</strong><span>تحديد الصلاحيات لكل مستخدم أو دور.</span></div>
            <div class="placeholder-card"><strong>Users API</strong><span>تمهيد لربط users-list و user-save و user-delete.</span></div>
          </div>
        </div>

        <div id="tab-stats" class="admin-panel" data-panel-permission="orders_view">
          <h3 class="panel-title">Statistics / Orders</h3>
          <p class="panel-desc">
            هذا القسم مخصص لإدارة الطلبات المرسلة من العملاء، ومراجعتها وتغيير حالتها إلى Pending أو Approved أو On The Way أو Delivered أو Rejected.
          </p>

          <div class="summary-cards">
            <div class="summary-card">
              <strong>All Orders</strong>
              <span id="ordersCountAll">0</span>
            </div>
            <div class="summary-card">
              <strong>Pending / Active</strong>
              <span id="ordersCountPending">0</span>
            </div>
            <div class="summary-card">
              <strong>Delivered</strong>
              <span id="ordersCountDelivered">0</span>
            </div>
            <div class="summary-card">
              <strong>Rejected / Cancelled</strong>
              <span id="ordersCountRejected">0</span>
            </div>
          </div>

          <div class="sub-card">
            <h4 class="sub-title">Orders Management</h4>

            <div class="orders-toolbar">
              <div class="form-group">
                <label for="ordersSearchInput">بحث</label>
                <input id="ordersSearchInput" type="text" placeholder="ابحث برقم الطلب أو اسم العميل أو الإيميل" data-permission="orders_view">
              </div>

              <div class="form-group">
                <label for="ordersStatusFilter">حالة الطلب</label>
                <select id="ordersStatusFilter" data-permission="orders_view">
                  <option value="">الكل</option>
                  <option value="pending">Pending</option>
                  <option value="approved">Approved</option>
                  <option value="on_the_way">On The Way</option>
                  <option value="completed">Delivered</option>
                  <option value="rejected">Rejected</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </div>

              <div class="form-group">
                <label for="ordersDateFilter">التاريخ</label>
                <input id="ordersDateFilter" type="date" data-permission="orders_view">
              </div>

              <div class="form-group">
                <button id="loadOrdersBtn" class="btn btn-primary" type="button" data-permission="orders_view">Load Orders</button>
              </div>

              <div class="form-group">
                <button id="refreshOrdersBtn" class="btn btn-primary secondary-btn" type="button" data-permission="orders_view">Refresh</button>
              </div>
            </div>

            <div class="data-table-wrap">
              <table class="data-table" id="adminOrdersTable">
                <thead>
                  <tr>
                    <th>Order No.</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>WhatsApp</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Products</th>
                    <th>Total</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="adminOrdersTableBody">
                  <tr>
                    <td colspan="9" style="text-align:center; color:#c8d4ea;">لم يتم تحميل الطلبات بعد.</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div id="ordersEmptyBox" class="empty-box" style="display:none;">
              لا توجد طلبات مطابقة للفلاتر الحالية.
            </div>
          </div>

          <div class="mini-note">
            عند رفض الطلب سيتم تسجيل الحالة:
            <strong>Rejected</strong>
            مع السبب الثابت:
            <strong>Not matching conditions</strong>
            وسيظهر ذلك أيضًا داخل سجل التغييرات History.
          </div>
        </div>

      </div>

      <div id="dashboardStatus" class="status-box mt-20"></div>
    </section>
  </div>
</div>

<div id="orderHistoryModal" class="history-modal">
  <div class="history-box">
    <div class="history-head">
      <h3 class="history-title" id="orderHistoryTitle">Order History</h3>
      <button type="button" class="history-close" id="orderHistoryCloseBtn">×</button>
    </div>
    <div id="orderHistoryContent" class="history-list">
      <div class="empty-box">لم يتم تحميل السجل بعد.</div>
    </div>
  </div>
</div>

<script>
  window.ADMIN_BOOTSTRAP = {
    categories: <?= json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    brands: <?= json_encode($brands, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
  };
</script>
<script src="/admin/assets/admin.js?v=20260408-2"></script>
</body>
</html>
