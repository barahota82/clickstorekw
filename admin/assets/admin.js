let currentProductImageFile = null;
let CURRENT_STOCK_REVIEW = {
  productId: null,
  devicesCount: 0,
  linked: [],
  missing: []
};

/* =========================
   PERMISSIONS STATE
========================= */

const ADMIN_STATE = {
  user: null,
  permissions: new Set()
};

const ADMIN_PERMISSION_ALIASES = {
  orders_view: [
    'orders_view',
    'orders.view',
    'view_orders',
    'manage_orders',
    'orders_manage',
    'admin.full_access',
    '*'
  ],
  orders_history_view: [
    'orders_history_view',
    'orders.history.view',
    'view_order_history',
    'orders_view',
    'orders.view',
    'view_orders',
    'manage_orders',
    'admin.full_access',
    '*'
  ],
  orders_approve: [
    'orders_approve',
    'order.approve',
    'orders.approve',
    'approve_orders',
    'change_order_status',
    'manage_orders',
    'admin.full_access',
    '*'
  ],
  orders_reject: [
    'orders_reject',
    'order.reject',
    'orders.reject',
    'reject_orders',
    'change_order_status',
    'manage_orders',
    'admin.full_access',
    '*'
  ],
  orders_mark_on_the_way: [
    'orders_mark_on_the_way',
    'order.on_the_way',
    'orders.on_the_way',
    'mark_orders_on_the_way',
    'change_order_status',
    'manage_orders',
    'admin.full_access',
    '*'
  ],
  orders_mark_delivered: [
    'orders_mark_delivered',
    'order.deliver',
    'orders.deliver',
    'mark_orders_delivered',
    'change_order_status',
    'manage_orders',
    'admin.full_access',
    '*'
  ],
  orders_mark_pending: [
    'orders_mark_pending',
    'order.return_to_pending',
    'order.pending',
    'orders.pending',
    'return_orders_to_pending',
    'change_order_status',
    'manage_orders',
    'admin.full_access',
    '*'
  ],
  products_create: [
    'products_create',
    'create_products',
    'edit_products',
    'manage_products',
    'admin.full_access',
    '*'
  ],
  products_edit: [
    'products_edit',
    'edit_products',
    'create_products',
    'manage_products',
    'admin.full_access',
    '*'
  ],
  products_delete: [
    'products_delete',
    'delete_products',
    'manage_products',
    'admin.full_access',
    '*'
  ],
  brands_order: [
    'brands_order',
    'view_brand_ordering',
    'manage_brand_ordering',
    'manage_products',
    'admin.full_access',
    '*'
  ],
  products_order: [
    'products_order',
    'view_product_ordering',
    'manage_product_ordering',
    'manage_products',
    'admin.full_access',
    '*'
  ],
  hot_offers_order: [
    'hot_offers_order',
    'view_hot_offers',
    'manage_hot_offers',
    'manage_products',
    'admin.full_access',
    '*'
  ],
  stock_manage: [
    'stock_manage',
    'view_stock',
    'manage_stock',
    'manage_stock_movements',
    'admin.full_access',
    '*'
  ],
  users_view: [
    'users_view',
    'view_users',
    'manage_users',
    'view_roles',
    'manage_roles',
    'view_permissions',
    'manage_permissions',
    'admin.full_access',
    '*'
  ],
  users_manage: [
    'users_manage',
    'manage_users',
    'manage_roles',
    'manage_permissions',
    'admin.full_access',
    '*'
  ],
  reports_view: [
    'reports_view',
    'view_analytics',
    'admin.full_access',
    '*'
  ],
  settings_view: [
    'settings_view',
    'view_settings',
    'manage_settings',
    'admin.full_access',
    '*'
  ]
};

function normalizePermissionKey(value) {
  return String(value || '')
    .trim()
    .toLowerCase()
    .replace(/\s+/g, '_');
}

function normalizePermissionList(permissions) {
  const result = [];

  if (Array.isArray(permissions)) {
    permissions.forEach(item => {
      const key = normalizePermissionKey(item);
      if (key) result.push(key);
    });
    return result;
  }

  if (permissions && typeof permissions === 'object') {
    Object.keys(permissions).forEach(key => {
      if (permissions[key]) {
        const normalized = normalizePermissionKey(key);
        if (normalized) result.push(normalized);
      }
    });
  }

  return result;
}

function setAdminAuthState(user = null, permissions = []) {
  ADMIN_STATE.user = user || null;
  ADMIN_STATE.permissions = new Set(normalizePermissionList(permissions));
}

function hasAdminPermission(key) {
  const aliases = ADMIN_PERMISSION_ALIASES[key] || [key];
  return aliases.some(alias => ADMIN_STATE.permissions.has(normalizePermissionKey(alias)));
}

function requireFrontendPermissionOrWarn(key, message) {
  if (hasAdminPermission(key)) return true;
  adminSetStatus('dashboardStatus', 'error', message || 'ليس لديك صلاحية لتنفيذ هذا الإجراء.');
  return false;
}

function setElementVisible(el, visible) {
  if (!el) return;
  el.style.display = visible ? '' : 'none';
}

function setElementDisabled(el, disabled) {
  if (!el) return;
  el.disabled = !!disabled;
  if (disabled) {
    el.classList.add('disabled');
  } else {
    el.classList.remove('disabled');
  }
}

function getPermissionValueFromElement(el, attrName) {
  if (!el) return '';
  return String(el.getAttribute(attrName) || '').trim();
}

function isElementAllowedByPermissionAttr(el, attrName = 'data-permission') {
  const permissionKey = getPermissionValueFromElement(el, attrName);
  if (!permissionKey) return true;
  return hasAdminPermission(permissionKey);
}

function getFirstAllowedTabButton() {
  const tabButtons = Array.from(document.querySelectorAll('.admin-tab-btn'));
  return tabButtons.find(btn => isElementAllowedByPermissionAttr(btn, 'data-permission')) || null;
}

function activateAdminTab(tabId) {
  if (!tabId) return;

  const targetBtn = document.querySelector(`.admin-tab-btn[data-tab="${tabId}"]`);
  const targetPanel = document.getElementById(tabId);

  if (!targetBtn || !targetPanel) return;
  if (!isElementAllowedByPermissionAttr(targetBtn, 'data-permission')) return;
  if (!isElementAllowedByPermissionAttr(targetPanel, 'data-panel-permission')) return;

  document.querySelectorAll('.admin-tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.admin-panel').forEach(panel => panel.classList.remove('active'));

  targetBtn.classList.add('active');
  targetPanel.classList.add('active');

  if (tabId === 'tab-stats') {
    loadOrdersManagement();
  }
}

function applyPermissionDrivenUI() {
  document.querySelectorAll('.admin-tab-btn[data-permission]').forEach(btn => {
    const allowed = isElementAllowedByPermissionAttr(btn, 'data-permission');
    setElementVisible(btn, allowed);
  });

  document.querySelectorAll('.admin-panel[data-panel-permission]').forEach(panel => {
    const allowed = isElementAllowedByPermissionAttr(panel, 'data-panel-permission');

    if (!allowed) {
      panel.classList.remove('active');
      panel.style.display = 'none';
    } else {
      panel.style.display = '';
    }
  });

  document.querySelectorAll('[data-permission]').forEach(el => {
    const allowed = isElementAllowedByPermissionAttr(el, 'data-permission');

    if (el.classList.contains('admin-tab-btn')) return;

    if (
      el.tagName === 'BUTTON' ||
      el.tagName === 'INPUT' ||
      el.tagName === 'SELECT' ||
      el.tagName === 'TEXTAREA'
    ) {
      setElementDisabled(el, !allowed);
      if (!allowed && el.tagName === 'BUTTON') {
        setElementVisible(el, false);
      }
    } else {
      setElementVisible(el, allowed);
    }
  });

  const activeTabBtn = document.querySelector('.admin-tab-btn.active');
  const activeTabId = activeTabBtn?.dataset?.tab || '';
  const activeBtnAllowed = activeTabBtn ? isElementAllowedByPermissionAttr(activeTabBtn, 'data-permission') : false;
  const activePanelAllowed = activeTabId
    ? isElementAllowedByPermissionAttr(document.getElementById(activeTabId), 'data-panel-permission')
    : false;

  if (!activeBtnAllowed || !activePanelAllowed) {
    const firstAllowedBtn = getFirstAllowedTabButton();
    if (firstAllowedBtn?.dataset?.tab) {
      activateAdminTab(firstAllowedBtn.dataset.tab);
    }
  }

  const ordersTabBtn = document.querySelector('.admin-tab-btn[data-tab="tab-stats"]');
  const canViewOrders = hasAdminPermission('orders_view');

  setElementVisible(ordersTabBtn, canViewOrders);

  const loadBtn = document.getElementById('loadOrdersBtn');
  const refreshBtn = document.getElementById('refreshOrdersBtn');
  const searchInput = document.getElementById('ordersSearchInput');
  const statusFilter = document.getElementById('ordersStatusFilter');
  const dateFilter = document.getElementById('ordersDateFilter');

  setElementDisabled(loadBtn, !canViewOrders);
  setElementDisabled(refreshBtn, !canViewOrders);
  setElementDisabled(searchInput, !canViewOrders);
  setElementDisabled(statusFilter, !canViewOrders);
  setElementDisabled(dateFilter, !canViewOrders);
}

/* =========================
   UTILITIES
========================= */

function adminSetStatus(id, type, message) {
  const box = document.getElementById(id);
  if (!box) return;

  box.className = 'status-box show ' + type;
  box.textContent = message;
}

function adminClearStatus(id) {
  const box = document.getElementById(id);
  if (!box) return;

  box.className = 'status-box';
  box.textContent = '';
}


function getAddProductStatusId() {
  return getEl('addProductStatus') ? 'addProductStatus' : 'dashboardStatus';
}

function addProductSetStatus(type, message) {
  adminSetStatus(getAddProductStatusId(), type, message);
}

function addProductClearStatus() {
  adminClearStatus(getAddProductStatusId());
}


function normalizeStockText(text) {
  return String(text || '')
    .toLowerCase()
    .replace(/[_.]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function normalizeBrandComparable(text) {
  return String(text || '')
    .toLowerCase()
    .replace(/[_.]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function toSlug(text) {
  const withoutExt = String(text || '').replace(/\.[^.]+$/, '');
  return withoutExt
    .toLowerCase()
    .replace(/[+_]/g, ' ')
    .replace(/\./g, ' ')
    .replace(/[^a-z0-9\-\s]/g, ' ')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-+|-+$/g, '');
}

function safeNum(value, fallback = 0) {
  const n = Number(value);
  return Number.isFinite(n) ? n : fallback;
}

function splitDevicesFromFilename(filename) {
  const noExt = String(filename || '').replace(/\.[^.]+$/, '');
  return noExt
    .split('+')
    .map(part => part.trim())
    .filter(Boolean)
    .slice(0, 4);
}

function extractBrandToken(rawText) {
  const value = String(rawText || '').trim();
  if (!value) return '';

  const firstWord = value.split(/\s+/)[0] || '';
  return firstWord.trim();
}

function normalizeBrandToken(rawText) {
  return extractBrandToken(rawText)
    .toLowerCase()
    .replace(/[_.]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function findBootstrapBrandByNameAndCategory(brandName, categoryId = '') {
  const normalizedTarget = normalizeBrandComparable(brandName);
  const normalizedTargetToken = normalizeBrandToken(brandName);

  if (!normalizedTarget && !normalizedTargetToken) return null;

  return getBootstrapBrands().find(brand => {
    const sameCategory = categoryId ? String(brand.category_id) === String(categoryId) : true;
    if (!sameCategory) return false;

    const brandNameRaw = String(brand.name || '').trim();
    const brandSlugRaw = String(brand.slug || '').trim();

    const nameValue = normalizeBrandComparable(brandNameRaw);
    const slugValue = normalizeBrandComparable(brandSlugRaw);
    const nameToken = normalizeBrandToken(brandNameRaw);
    const slugToken = normalizeBrandToken(brandSlugRaw);

    return (
      normalizedTarget === nameValue ||
      normalizedTarget === slugValue ||
      normalizedTargetToken === nameToken ||
      normalizedTargetToken === slugToken
    );
  }) || null;
}

function detectBrandFromFilename(text) {
  const firstTokenRaw = extractBrandToken(text);
  if (!firstTokenRaw) return '';

  const exactBrand = getBootstrapBrands().find(brand => {
    const brandNameRaw = String(brand.name || '').trim();
    const brandSlugRaw = String(brand.slug || '').trim();

    return (
      firstTokenRaw.toLowerCase() === brandNameRaw.toLowerCase() ||
      firstTokenRaw.toLowerCase() === brandSlugRaw.toLowerCase()
    );
  });

  if (exactBrand) {
    return exactBrand.name;
  }

  const normalizedToken = normalizeBrandToken(firstTokenRaw);

  const tokenBrand = getBootstrapBrands().find(brand => {
    const brandNameToken = normalizeBrandToken(String(brand.name || ''));
    const brandSlugToken = normalizeBrandToken(String(brand.slug || ''));

    return normalizedToken === brandNameToken || normalizedToken === brandSlugToken;
  });

  if (tokenBrand) {
    return tokenBrand.name;
  }

  return firstTokenRaw;
}

function buildDisplayNameFromFilename(part) {
  const cleaned = String(part || '')
    .replace(/[_\-]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();

  return cleaned
    .split(' ')
    .filter(Boolean)
    .map(word => {
      if (/^\d+(\.\d+)?$/.test(word)) return word;
      if (/^\d+(\.\d+)?(gb|tb)$/i.test(word)) return word.toUpperCase();
      if (/^(4g|5g)$/i.test(word)) return word.toUpperCase();
      if (/^ram$/i.test(word)) return 'RAM';
      if (/^tb$/i.test(word)) return 'TB';
      if (/^gb$/i.test(word)) return 'GB';
      return word.charAt(0).toUpperCase() + word.slice(1);
    })
    .join(' ');
}

function analyzeFilename(filename) {
  const devices = splitDevicesFromFilename(filename);
  const firstDevice = devices[0] || '';
  const brand = detectBrandFromFilename(firstDevice);

  const fullOfferTitle = devices
    .map(device => buildDisplayNameFromFilename(device))
    .filter(Boolean)
    .join(' + ');

  return {
    fileName: filename || '',
    devicesCount: Math.min(devices.length || 1, 4),
    brandFromFilename: brand,
    title: fullOfferTitle || buildDisplayNameFromFilename(firstDevice)
  };
}

function getEl(id) {
  return document.getElementById(id);
}

function setInputValue(id, value) {
  const el = getEl(id);
  if (el) el.value = value ?? '';
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

/* =========================
   BOOTSTRAP DATA
========================= */

function getBootstrapCategories() {
  return Array.isArray(window.ADMIN_BOOTSTRAP?.categories) ? window.ADMIN_BOOTSTRAP.categories : [];
}

function getBootstrapBrands() {
  return Array.isArray(window.ADMIN_BOOTSTRAP?.brands) ? window.ADMIN_BOOTSTRAP.brands : [];
}

async function refreshBootstrapCatalogData() {
  try {
    const { data: categoriesData } = await adminFetchJson('/admin/api/get-categories.php');
    const categories = Array.isArray(categoriesData?.categories) ? categoriesData.categories : [];

    const allBrands = [];
    const brandSeen = new Set();

    for (const category of categories) {
      const categoryId = Number(category?.id || 0);
      if (!categoryId) continue;

      try {
        const { data: brandsData } = await adminFetchJson(`/admin/api/get-brands.php?category_id=${encodeURIComponent(categoryId)}`);
        const brands = Array.isArray(brandsData?.brands) ? brandsData.brands : [];

        brands.forEach(brand => {
          const brandId = String(brand?.id || '');
          if (!brandId || brandSeen.has(brandId)) return;
          brandSeen.add(brandId);
          allBrands.push(brand);
        });
      } catch (e) {}
    }

    window.ADMIN_BOOTSTRAP = window.ADMIN_BOOTSTRAP || {};
    window.ADMIN_BOOTSTRAP.categories = categories.map(cat => ({
      id: Number(cat.id || 0),
      display_name: String(cat.display_name || cat.name_en || cat.slug || ''),
      slug: String(cat.slug || ''),
      name_en: String(cat.name_en || cat.display_name || cat.slug || '')
    }));
    window.ADMIN_BOOTSTRAP.brands = allBrands.map(brand => ({
      id: Number(brand.id || 0),
      category_id: Number(brand.category_id || 0),
      name: String(brand.name || brand.display_name || ''),
      display_name: String(brand.display_name || brand.name || ''),
      slug: String(brand.slug || '')
    }));

    populateCategorySelect('ocrCategory');
    populateCategorySelect('editCategory');

    const editCategoryValue = String(getEl('editCategory')?.value || '').trim();
    populateBrandSelect('editBrand', editCategoryValue);

    syncDetectedBrandAndPreview();
  } catch (e) {}
}

function populateCategorySelect(selectId) {
  const select = getEl(selectId);
  if (!select) return;

  const currentValue = String(select.value || '');
  select.innerHTML = '<option value="">Select Category</option>';

  getBootstrapCategories().forEach(cat => {
    const opt = document.createElement('option');
    opt.value = String(cat.id);
    opt.textContent = cat.display_name;
    opt.dataset.slug = String(cat.slug || '').toLowerCase();
    select.appendChild(opt);
  });

  if (currentValue) {
    select.value = currentValue;
  }
}

function populateBrandSelect(selectId, categoryId = '') {
  const select = getEl(selectId);
  if (!select) return;

  const currentValue = String(select.value || '');
  select.innerHTML = '<option value="">Select Brand</option>';

  getBootstrapBrands().forEach(brand => {
    if (categoryId && String(brand.category_id) !== String(categoryId)) return;

    const opt = document.createElement('option');
    opt.value = String(brand.id);
    opt.textContent = brand.name;
    opt.dataset.categoryId = String(brand.category_id);
    opt.dataset.slug = String(brand.slug || '').toLowerCase();
    select.appendChild(opt);
  });

  if (currentValue) {
    select.value = currentValue;
  }
}

function bindEditCategoryBrandFilter(categoryId, brandId) {
  const category = getEl(categoryId);
  const brand = getEl(brandId);

  if (!category || !brand) return;

  populateCategorySelect(categoryId);
  populateBrandSelect(brandId, category.value || '');

  category.addEventListener('change', function () {
    populateBrandSelect(brandId, this.value);
  });
}

function syncDetectedBrandAndPreview() {
  if (!currentProductImageFile) {
    updateProductJsonPreview();
    return;
  }

  const analysis = analyzeFilename(currentProductImageFile.name);
  setInputValue('ocrBrandFromFilename', analysis.brandFromFilename || '');
  updateProductJsonPreview();
}

function bindAddProductCategoryOnly(selectId) {
  const category = getEl(selectId);
  if (!category) return;

  populateCategorySelect(selectId);

  category.addEventListener('change', async function () {
    syncDetectedBrandAndPreview();

    if (currentProductImageFile?.name) {
      updateProductJsonPreview();
      await refreshStockReviewFromFilename(currentProductImageFile.name);
    } else {
      updateProductJsonPreview();
    }
  });
}

/* =========================
   ADD PRODUCT HELPERS
========================= */

function resolveSelectedCategoryRow() {
  const categoryId = String(getEl('ocrCategory')?.value || '').trim();
  return getBootstrapCategories().find(cat => String(cat.id) === categoryId) || null;
}

function buildPreviewSlug() {
  if (!currentProductImageFile?.name) return '';
  return toSlug(currentProductImageFile.name || '');
}

function buildPreviewImagePath() {
  const categoryRow = resolveSelectedCategoryRow();
  const brandName = String(getEl('ocrBrandFromFilename')?.value || '').trim();
  const slug = buildPreviewSlug();

  if (!categoryRow || !brandName || !slug) return '';

  const brandSlug = toSlug(brandName);

  if (!brandSlug) return '';

  return `/images/${String(categoryRow.slug || '').toLowerCase()}/${brandSlug}/${slug}.webp`;
}

function buildProductJsonPreviewObject() {
  const categoryRow = resolveSelectedCategoryRow();
  const previewImage = buildPreviewImagePath();
  const previewSlug = buildPreviewSlug();

  return {
    slug: previewSlug,
    title: String(getEl('ocrTitle')?.value || '').trim(),
    category: categoryRow ? String(categoryRow.slug || '').toLowerCase() : '',
    brand: String(getEl('ocrBrandFromFilename')?.value || '').trim(),
    devices_count: safeNum(getEl('ocrDevicesCount')?.value || 1, 1),
    image: previewImage,
    down_payment: safeNum(getEl('ocrDownPayment')?.value || 0, 0),
    monthly: safeNum(getEl('ocrMonthlyAmount')?.value || 0, 0),
    duration: safeNum(getEl('ocrDurationMonths')?.value || 0, 0),
    available: true,
    hot_offer: String(getEl('ocrHotOffer')?.value || '0') === '1'
  };
}

function updateProductJsonPreview() {
  const preview = getEl('productJsonPreview');
  if (!preview) return;

  const data = buildProductJsonPreviewObject();
  preview.value = JSON.stringify(data, null, 2);
}

function fillProductFieldsFromFilenameAnalysis(analysis) {
  setInputValue('ocrFileName', analysis.fileName || '');
  setInputValue('ocrTitle', analysis.title || '');
  setInputValue('ocrDevicesCount', Math.min(Number(analysis.devicesCount || 1), 4));
  setInputValue('ocrBrandFromFilename', analysis.brandFromFilename || '');
  updateProductJsonPreview();
}

function bindProductPreviewAutoUpdate() {
  [
    'ocrCategory',
    'ocrTitle',
    'ocrDownPayment',
    'ocrMonthlyAmount',
    'ocrDurationMonths',
    'ocrHotOffer'
  ].forEach(id => {
    const el = getEl(id);
    if (!el) return;

    el.addEventListener('input', updateProductJsonPreview);
    el.addEventListener('change', updateProductJsonPreview);
  });
}

function clearStockReview() {
  CURRENT_STOCK_REVIEW = {
    productId: null,
    devicesCount: 0,
    linked: [],
    missing: []
  };

  const grid = getEl('stockReviewGrid');
  if (!grid) return;

  grid.innerHTML = `
    <div class="stock-review-card is-missing">
      <div class="stock-review-card-head">
        <h5 class="stock-review-title">Slot 1</h5>
        <span class="stock-state-badge missing">Empty</span>
      </div>
      <div class="mini-note" style="margin-top:0;">
        ارفع صورة أولًا ليتم تحليل اسم الملف وعرض الأجهزة.
      </div>
    </div>
    <div class="stock-review-card is-missing">
      <div class="stock-review-card-head">
        <h5 class="stock-review-title">Slot 2</h5>
        <span class="stock-state-badge missing">Empty</span>
      </div>
      <div class="mini-note" style="margin-top:0;">
        الحد الأقصى داخل الصورة الواحدة هو 4 أجهزة.
      </div>
    </div>
    <div class="stock-review-card is-missing">
      <div class="stock-review-card-head">
        <h5 class="stock-review-title">Slot 3</h5>
        <span class="stock-state-badge missing">Empty</span>
      </div>
      <div class="mini-note" style="margin-top:0;">
        سيتم تعبئة هذه الخانة تلقائيًا إذا وُجد جهاز ثالث.
      </div>
    </div>
    <div class="stock-review-card is-missing">
      <div class="stock-review-card-head">
        <h5 class="stock-review-title">Slot 4</h5>
        <span class="stock-state-badge missing">Empty</span>
      </div>
      <div class="mini-note" style="margin-top:0;">
        هذه آخر خانة مدعومة.
      </div>
    </div>
  `;
}

function buildCategoryOptionsHtml(selectedValue = '') {
  return [
    '<option value="">Select Category</option>',
    ...getBootstrapCategories().map(cat => {
      const selected = String(selectedValue) === String(cat.id) ? ' selected' : '';
      return `<option value="${String(cat.id)}"${selected}>${escapeHtml(cat.display_name)}</option>`;
    })
  ].join('');
}

function buildMissingCardBrandGuess(item) {
  const explicit = String(item.expected_brand_name || item.brand_guess || '').trim();
  if (explicit) return explicit;

  const detected = detectBrandFromFilename(String(item.raw_title || '')) ||
                   detectBrandFromFilename(String(item.stock_title || ''));

  return detected || '-';
}

function updateMissingCardStatus(card, selectedText = '') {
  if (!card) return;

  const statusValue = card.querySelector('.js-status-value');
  if (!statusValue) return;

  if (selectedText && selectedText !== 'Select Category') {
    statusValue.innerHTML = `Category Selected<br>${escapeHtml(selectedText)}`;
  } else {
    statusValue.innerHTML = 'Needs<br>category<br>selection';
  }
}

function bindStockReviewSelects() {
  document.querySelectorAll('.stock-review-select select').forEach(select => {
    select.addEventListener('change', function () {
      const card = this.closest('.stock-review-card');
      const selectedText = this.options[this.selectedIndex]?.textContent || 'Select Category';
      updateMissingCardStatus(card, selectedText);
    });
  });
}

function renderStockReview(review) {
  const grid = getEl('stockReviewGrid');
  if (!grid) return;

  const linked = Array.isArray(review?.linked) ? review.linked : [];
  const missing = Array.isArray(review?.missing) ? review.missing : [];
  const devicesCount = Math.min(Number(review?.devices_count || linked.length + missing.length || 0), 4);

  CURRENT_STOCK_REVIEW = {
    productId: review?.product_id ?? CURRENT_STOCK_REVIEW.productId,
    devicesCount,
    linked,
    missing
  };

  const cards = [];

  linked.forEach(item => {
    const linkedBrand =
      String(item.brand_name || '').trim() ||
      String(item.expected_brand_name || '').trim() ||
      String(item.brand_guess || '').trim() ||
      detectBrandFromFilename(String(item.raw_title || '')) ||
      detectBrandFromFilename(String(item.stock_title || '')) ||
      '-';

    cards.push(`
      <div class="stock-review-card is-linked">
        <div class="stock-review-card-head">
          <h5 class="stock-review-title">${escapeHtml(item.raw_title || item.stock_title || 'Linked Device')}</h5>
          <span class="stock-state-badge linked">Added</span>
        </div>

        <div class="stock-review-meta">
          <div class="mini-box">
            <strong>Brand</strong>
            <span>${escapeHtml(linkedBrand)}</span>
          </div>
          <div class="mini-box">
            <strong>Category</strong>
            <span>${escapeHtml(item.category_name || item.category_id || '-')}</span>
          </div>
          <div class="mini-box">
            <strong>Storage</strong>
            <span>${escapeHtml(item.storage_value || '-')}</span>
          </div>
          <div class="mini-box">
            <strong>RAM</strong>
            <span>${escapeHtml(item.ram_value || '-')}</span>
          </div>
        </div>
      </div>
    `);
  });

  missing.forEach(item => {
    const selectId = `missingCategory_${item.device_index}`;
    const effectiveCategoryId = String(item.expected_category_id || '').trim();
    const effectiveBrandGuess = buildMissingCardBrandGuess(item);
    const effectiveCategoryText =
      getBootstrapCategories().find(cat => String(cat.id) === effectiveCategoryId)?.display_name || '';

    cards.push(`
      <div class="stock-review-card is-missing">
        <div class="stock-review-card-head">
          <h5 class="stock-review-title">${escapeHtml(item.raw_title || 'Missing Device')}</h5>
          <span class="stock-state-badge missing">Not Added</span>
        </div>

        <div class="stock-review-meta">
          <div class="mini-box">
            <strong>Status</strong>
            <span class="js-status-value">${
              effectiveCategoryText
                ? `Category Selected<br>${escapeHtml(effectiveCategoryText)}`
                : 'Needs<br>category<br>selection'
            }</span>
          </div>
          <div class="mini-box">
            <strong>Brand Guess</strong>
            <span>${escapeHtml(effectiveBrandGuess)}</span>
          </div>
          <div class="mini-box">
            <strong>Storage</strong>
            <span>${escapeHtml(item.storage_value || '-')}</span>
          </div>
          <div class="mini-box">
            <strong>RAM</strong>
            <span>${escapeHtml(item.ram_value || '-')}</span>
          </div>
        </div>

        <div class="stock-review-actions">
          <div class="form-group stock-review-select">
            <label for="${selectId}">Choose Category</label>
            <select id="${selectId}">
              ${buildCategoryOptionsHtml(effectiveCategoryId)}
            </select>
          </div>

          <button
            class="btn success-btn"
            type="button"
            data-permission="stock_manage"
            onclick="addMissingStockItem(${Number(item.device_index || 0)})"
          >
            Add To Stock
          </button>
        </div>
      </div>
    `);
  });

  while (cards.length < 4) {
    const slotNumber = cards.length + 1;
    cards.push(`
      <div class="stock-review-card is-missing">
        <div class="stock-review-card-head">
          <h5 class="stock-review-title">Slot ${slotNumber}</h5>
          <span class="stock-state-badge missing">Empty</span>
        </div>
        <div class="mini-note" style="margin-top:0;">
          ${slotNumber <= devicesCount ? 'هذه الخانة غير مستخدمة حاليًا.' : 'لا يوجد جهاز في هذه الخانة.'}
        </div>
      </div>
    `);
  }

  grid.innerHTML = cards.join('');
  bindStockReviewSelects();
  applyPermissionDrivenUI();
}

async function refreshStockReviewFromFilename(filename) {
  if (!filename) {
    clearStockReview();
    return;
  }

  const selectedCategoryId = String(getEl('ocrCategory')?.value || '').trim();
  const devices = splitDevicesFromFilename(filename);
  const firstDevice = devices[0] || filename;
  const detectedBrandName = detectBrandFromFilename(firstDevice);
  const preferredBrand = selectedCategoryId && detectedBrandName
    ? findBootstrapBrandByNameAndCategory(detectedBrandName, selectedCategoryId)
    : null;

  try {
    const { data } = await adminFetchJson('/admin/api/check-stock-from-filename.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        filename,
        preferred_category_id: selectedCategoryId ? Number(selectedCategoryId) : null,
        preferred_brand_id: preferredBrand?.id ? Number(preferredBrand.id) : null
      })
    });

    if (!data.ok) {
      clearStockReview();
      return;
    }

    renderStockReview({
      product_id: null,
      devices_count: data.devices_count || 0,
      linked: data.linked || [],
      missing: data.missing || []
    });
  } catch (e) {
    clearStockReview();
  }
}

function clearAddProductData(fullReset = false) {
  currentProductImageFile = fullReset ? null : currentProductImageFile;

  const ids = [
    'ocrFileName',
    'ocrTitle',
    'ocrDevicesCount',
    'ocrBrandFromFilename',
    'ocrDownPayment',
    'ocrMonthlyAmount',
    'ocrDurationMonths',
    'productJsonPreview'
  ];

  ids.forEach(id => {
    const el = getEl(id);
    if (el) el.value = '';
  });

  const hotOffer = getEl('ocrHotOffer');
  if (hotOffer) hotOffer.value = '0';

  if (fullReset) {
    const category = getEl('ocrCategory');
    if (category) category.value = '';
  }

  const image = getEl('ocrPreviewImage');
  const placeholder = getEl('ocrPreviewPlaceholder');
  const input = getEl('ocrImageInput');

  if (fullReset) {
    if (image) {
      image.src = '';
      image.classList.add('hidden');
      image.style.display = 'none';
    }
    if (placeholder) placeholder.classList.remove('hidden');
    if (input) input.value = '';
    currentProductImageFile = null;
  }

  clearStockReview();
  updateProductJsonPreview();
  addProductClearStatus();
}

function bindProductUploadButton() {
  const uploadBtn = getEl('ocrUploadBtn');
  const input = getEl('ocrImageInput');
  const image = getEl('ocrPreviewImage');
  const placeholder = getEl('ocrPreviewPlaceholder');
  const fileNameField = getEl('ocrFileName');

  if (!uploadBtn || !input || !image) return;

  uploadBtn.onclick = null;
  uploadBtn.addEventListener('click', function () {
    if (!requireFrontendPermissionOrWarn('products_create', 'ليس لديك صلاحية إضافة منتج.')) return;

    input.value = '';
    input.click();
  });

  input.onchange = null;
  input.addEventListener('change', async function () {
    const file = this.files && this.files[0];
    if (!file) return;

    clearAddProductData(false);
    currentProductImageFile = file;

    if (fileNameField) fileNameField.value = file.name;

    const analysis = analyzeFilename(file.name);
    fillProductFieldsFromFilenameAnalysis(analysis);

    const reader = new FileReader();
    reader.onload = function (e) {
      image.src = e.target.result;
      image.classList.remove('hidden');
      image.style.display = 'block';
      if (placeholder) placeholder.classList.add('hidden');
    };
    reader.readAsDataURL(file);

    updateProductJsonPreview();
    await refreshStockReviewFromFilename(file.name);

    addProductSetStatus('info', 'تم رفع الصورة وتحليل اسم الملف ومراجعة المخزن. اختر الفئة يدويًا ثم راجع البيانات قبل الحفظ.');
  });
}

async function saveProduct() {
  if (!requireFrontendPermissionOrWarn('products_create', 'ليس لديك صلاحية إضافة منتج.')) return;

  if (!currentProductImageFile) {
    addProductSetStatus('error', 'ارفع صورة أولًا.');
    return;
  }

  const title = String(getEl('ocrTitle')?.value || '').trim();
  const categoryId = String(getEl('ocrCategory')?.value || '').trim();
  const downPayment = String(getEl('ocrDownPayment')?.value || '0').trim();
  const monthlyAmount = String(getEl('ocrMonthlyAmount')?.value || '0').trim();
  const durationMonths = String(getEl('ocrDurationMonths')?.value || '12').trim();
  const hotOffer = String(getEl('ocrHotOffer')?.value || '0').trim();
  const devicesCount = String(Math.min(Number(getEl('ocrDevicesCount')?.value || '1'), 4));
  const brandName = String(getEl('ocrBrandFromFilename')?.value || '').trim();

  if (!title) {
    addProductSetStatus('error', 'حقل Title مطلوب.');
    return;
  }

  if (!categoryId) {
    addProductSetStatus('error', 'اختر Category أولًا.');
    return;
  }

  if (!brandName) {
    addProductSetStatus('error', 'لم يتم التعرف على البراند من اسم الملف.');
    return;
  }

  const brandRecord = findBootstrapBrandByNameAndCategory(brandName, categoryId);

  if (!brandRecord) {
    addProductSetStatus('error', 'لا يوجد Brand مطابق داخل قاعدة البيانات لهذه الفئة.');
    return;
  }

  const fd = new FormData();
  fd.append('title', title);
  fd.append('category_id', categoryId);
  fd.append('brand_id', String(brandRecord.id));
  fd.append('devices_count', devicesCount);
  fd.append('duration_months', durationMonths);
  fd.append('down_payment', downPayment);
  fd.append('monthly_amount', monthlyAmount);
  fd.append('is_hot_offer', hotOffer === '1' ? '1' : '0');
  fd.append('is_available', '1');
  fd.append('image', currentProductImageFile);

  addProductSetStatus('info', 'جاري حفظ المنتج...');

  try {
    const res = await fetch('/admin/api/save-product.php', {
      method: 'POST',
      credentials: 'same-origin',
      body: fd
    });

    const raw = await res.text();
    let data = null;

    try {
      data = JSON.parse(raw);
    } catch (e) {
      throw new Error(raw || 'Unexpected server response.');
    }

    if (!data.ok) {
      addProductSetStatus('error', data.message || 'فشل حفظ المنتج.');
      return;
    }

    if (data.stock_review) {
      renderStockReview({
        product_id: data.product_id,
        devices_count: data.stock_review.devices_count || 0,
        linked: data.stock_review.linked || [],
        missing: data.stock_review.missing || []
      });
    } else if (currentProductImageFile?.name) {
      await refreshStockReviewFromFilename(currentProductImageFile.name);
    } else {
      clearStockReview();
    }

    if (data.saved_json && typeof data.saved_json === 'object') {
      const preview = getEl('productJsonPreview');
      if (preview) {
        preview.value = JSON.stringify(data.saved_json, null, 2);
      }
    } else {
      updateProductJsonPreview();
    }

    addProductSetStatus(
      'success',
      (data.message || 'تم حفظ المنتج بنجاح.') +
      (data.slug ? ` | slug: ${data.slug}` : '')
    );
  } catch (e) {
    addProductSetStatus('error', e.message || 'حدث خطأ أثناء حفظ المنتج.');
  }
}

function bindProductSaveButton() {
  const btn = getEl('ocrSaveBtn');
  if (!btn) return;
  btn.addEventListener('click', saveProduct);
}

function bindProductClearButton() {
  const btn = getEl('ocrClearDataBtn');
  if (!btn) return;

  btn.addEventListener('click', function () {
    if (!requireFrontendPermissionOrWarn('products_create', 'ليس لديك صلاحية إضافة منتج.')) return;
    clearAddProductData(true);
    addProductSetStatus('info', 'تم تفريغ النموذج.');
  });
}

window.addMissingStockItem = async function (deviceIndex) {
  if (!requireFrontendPermissionOrWarn('stock_manage', 'ليس لديك صلاحية إضافة عنصر إلى المخزن.')) return;

  const item = CURRENT_STOCK_REVIEW.missing.find(entry => Number(entry.device_index) === Number(deviceIndex));

  if (!item) {
    addProductSetStatus('error', 'لم يتم العثور على الجهاز المطلوب إضافته.');
    return;
  }

  const categorySelect = getEl(`missingCategory_${deviceIndex}`);
  const selectedCategoryId = String(categorySelect?.value || '').trim();
  const resolvedBrandId = Number(item.expected_brand_id || item.brand_id || 0);

  if (!selectedCategoryId) {
    addProductSetStatus('error', 'اختر الفئة أولًا قبل الإضافة.');
    return;
  }

  if (resolvedBrandId <= 0) {
    addProductSetStatus('error', 'هذا البراند غير مسجل داخل قاعدة البيانات. أضف البراند أولًا من تبويب Categories / Brands.');
    return;
  }

  addProductSetStatus('info', 'جاري إضافة الجهاز إلى المخزن...');

  try {
    const { data } = await adminFetchJson('/admin/api/add-missing-stock-item.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        raw_title: item.raw_title || '',
        normalized_title: item.normalized_title || '',
        category_id: Number(selectedCategoryId),
        brand_id: resolvedBrandId,
        storage_value: item.storage_value || null,
        ram_value: item.ram_value || null,
        network_value: item.network_value || null,
        product_id: Number(CURRENT_STOCK_REVIEW.productId || 0),
        device_index: Number(item.device_index || deviceIndex || 0),
        extracted_name: item.raw_title || ''
      })
    });

    if (!data.ok) {
      addProductSetStatus('error', data.message || 'فشل إضافة الجهاز إلى المخزن.');
      return;
    }

    const stockItem = data.stock_item || {};
    const selectedCategoryText = categorySelect?.options?.[categorySelect.selectedIndex]?.textContent || '';

    CURRENT_STOCK_REVIEW.missing = CURRENT_STOCK_REVIEW.missing.filter(entry => Number(entry.device_index) !== Number(deviceIndex));
    CURRENT_STOCK_REVIEW.linked.push({
      device_index: item.device_index,
      raw_title: item.raw_title,
      normalized_title: item.normalized_title,
      storage_value: item.storage_value || null,
      ram_value: item.ram_value || null,
      network_value: item.network_value || null,
      stock_catalog_id: Number(stockItem.id || 0),
      stock_title: stockItem.title || item.raw_title || '',
      category_id: Number(stockItem.category_id || selectedCategoryId || 0),
      category_name: stockItem.category_name || selectedCategoryText || '',
      brand_id: Number(stockItem.brand_id || resolvedBrandId || 0),
      brand_name: stockItem.brand_name || item.expected_brand_name || buildMissingCardBrandGuess(item) || '',
      expected_brand_name: item.expected_brand_name || buildMissingCardBrandGuess(item) || '',
      is_added: true
    });

    renderStockReview({
      product_id: CURRENT_STOCK_REVIEW.productId,
      devices_count: CURRENT_STOCK_REVIEW.devicesCount,
      linked: CURRENT_STOCK_REVIEW.linked,
      missing: CURRENT_STOCK_REVIEW.missing
    });

    addProductSetStatus('success', data.message || 'تمت إضافة الجهاز إلى المخزن بنجاح.');
  } catch (e) {
    addProductSetStatus('error', e.message || 'حدث خطأ أثناء إضافة الجهاز إلى المخزن.');
  }
};

/* =========================
   EDIT IMAGE
========================= */

function bindEditImageButton() {
  const btn = getEl('editChangeImageBtn');
  const input = getEl('editImageInput');
  const image = getEl('editPreviewImage');

  if (!btn || !input || !image) return;

  btn.onclick = null;
  btn.addEventListener('click', function () {
    if (!requireFrontendPermissionOrWarn('products_edit', 'ليس لديك صلاحية تعديل المنتجات.')) return;

    input.value = '';
    input.click();
  });

  input.onchange = null;
  input.addEventListener('change', function () {
    const file = this.files && this.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (e) {
      image.src = e.target.result;
      image.style.display = 'block';
    };
    reader.readAsDataURL(file);
  });
}

/* =========================
   TABS
========================= */

function bindTabSwitching() {
  document.querySelectorAll('.admin-tab-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const tabId = this.dataset.tab;

      if (!isElementAllowedByPermissionAttr(this, 'data-permission')) {
        adminSetStatus('dashboardStatus', 'error', 'ليس لديك صلاحية لفتح هذا القسم.');
        return;
      }

      const target = getEl(tabId);
      if (target && !isElementAllowedByPermissionAttr(target, 'data-panel-permission')) {
        adminSetStatus('dashboardStatus', 'error', 'ليس لديك صلاحية لعرض هذا القسم.');
        return;
      }

      document.querySelectorAll('.admin-tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.admin-panel').forEach(panel => panel.classList.remove('active'));

      this.classList.add('active');

      if (target) target.classList.add('active');

      if (tabId === 'tab-stats') {
        loadOrdersManagement();
      }

      if (tabId === 'tab-add-product') {
        refreshBootstrapCatalogData().then(() => {
          updateProductJsonPreview();

          if (currentProductImageFile?.name && tabId === 'tab-add-product') {
            refreshStockReviewFromFilename(currentProductImageFile.name);
          }
        });
      }
    });
  });
}

/* =========================
   ORDERS MANAGEMENT
========================= */

async function adminFetchJson(url, options = {}) {
  const res = await fetch(url, {
    credentials: 'same-origin',
    cache: 'no-store',
    ...options
  });

  const raw = await res.text();
  let data = null;

  try {
    data = JSON.parse(raw);
  } catch (e) {
    throw new Error(raw || 'Unexpected server response.');
  }

  return { res, data };
}

function getOrderActionPermissions(apiPermissions = null) {
  return {
    canViewOrders: hasAdminPermission('orders_view'),
    canViewHistory: hasAdminPermission('orders_history_view'),
    canApprove: apiPermissions && typeof apiPermissions.approve !== 'undefined'
      ? !!apiPermissions.approve
      : hasAdminPermission('orders_approve'),
    canReject: apiPermissions && typeof apiPermissions.reject !== 'undefined'
      ? !!apiPermissions.reject
      : hasAdminPermission('orders_reject'),
    canOnTheWay: apiPermissions && typeof apiPermissions.on_the_way !== 'undefined'
      ? !!apiPermissions.on_the_way
      : hasAdminPermission('orders_mark_on_the_way'),
    canDeliver: apiPermissions && typeof apiPermissions.deliver !== 'undefined'
      ? !!apiPermissions.deliver
      : hasAdminPermission('orders_mark_delivered'),
    canPending: apiPermissions && typeof apiPermissions.return_to_pending !== 'undefined'
      ? !!apiPermissions.return_to_pending
      : hasAdminPermission('orders_mark_pending')
  };
}

function formatAdminOrderStatus(rawStatus, rejectionReason = '') {
  const status = String(rawStatus || '').toLowerCase();

  if (status === 'approved') return 'Approved';
  if (status === 'on_the_way') return 'On The Way';
  if (status === 'completed') return 'Delivered';
  if (status === 'cancelled') return 'Cancelled';
  if (status === 'rejected') return rejectionReason ? `Rejected - ${rejectionReason}` : 'Rejected';
  if (status === 'pending') return 'Pending';

  return 'Pending';
}

function getAdminOrderStatusClass(rawStatus) {
  const status = String(rawStatus || '').toLowerCase();

  if (status === 'completed') return 'status-delivered';
  if (status === 'cancelled') return 'status-cancelled';
  if (status === 'rejected') return 'status-rejected';

  return 'status-pending';
}

function groupOrderItems(items) {
  const map = new Map();

  (items || []).forEach(item => {
    const key = [
      String(item.title || '').trim(),
      String(item.down_payment || '').trim(),
      String(item.monthly || '').trim(),
      String(item.duration || '').trim()
    ].join('||');

    if (!map.has(key)) {
      map.set(key, {
        title: String(item.title || '').trim(),
        quantity: Number(item.quantity || 0),
        down_payment: String(item.down_payment || '').trim(),
        monthly: String(item.monthly || '').trim(),
        duration: String(item.duration || '').trim()
      });
    } else {
      const existing = map.get(key);
      existing.quantity += Number(item.quantity || 0);
    }
  });

  return Array.from(map.values());
}

function ensureCustomerProfileModal() {
  if (document.getElementById('customerProfileModal')) return;

  const style = document.createElement('style');
  style.textContent = `
    .customer-link-btn{
      background:none;
      border:none;
      color:#fff;
      font-weight:800;
      cursor:pointer;
      padding:0;
      text-align:right;
      font-size:14px;
    }
    .customer-link-btn:hover{
      color:#93c5fd;
      text-decoration:underline;
    }
    .customer-profile-modal{
      position:fixed;
      inset:0;
      background:rgba(2,6,23,0.82);
      display:none;
      align-items:center;
      justify-content:center;
      z-index:10000;
      padding:20px;
    }
    .customer-profile-modal.active{
      display:flex;
    }
    .customer-profile-box{
      width:min(760px, 100%);
      max-height:85vh;
      overflow:auto;
      background:#0f172a;
      border:1px solid rgba(255,255,255,0.08);
      border-radius:20px;
      padding:20px;
    }
    .customer-profile-head{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      margin-bottom:16px;
    }
    .customer-profile-title{
      color:#fff;
      font-size:20px;
      font-weight:800;
      margin:0;
    }
    .customer-profile-close{
      border:none;
      background:rgba(255,255,255,0.08);
      color:#fff;
      width:42px;
      height:42px;
      border-radius:12px;
      cursor:pointer;
      font-size:22px;
    }
    .customer-profile-grid{
      display:grid;
      grid-template-columns:repeat(2, 1fr);
      gap:14px;
      margin-top:10px;
    }
    .customer-profile-card{
      background:rgba(255,255,255,0.04);
      border:1px solid rgba(255,255,255,0.08);
      border-radius:16px;
      padding:14px;
    }
    .customer-profile-card strong{
      display:block;
      color:#c8d4ea;
      margin-bottom:6px;
      font-size:13px;
    }
    .customer-profile-card span{
      color:#fff;
      font-size:14px;
      line-height:1.8;
      word-break:break-word;
    }
    .customer-profile-badges{
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      margin:12px 0 16px;
    }
    @media (max-width: 700px){
      .customer-profile-grid{
        grid-template-columns:1fr;
      }
    }
  `;
  document.head.appendChild(style);

  const modal = document.createElement('div');
  modal.id = 'customerProfileModal';
  modal.className = 'customer-profile-modal';
  modal.innerHTML = `
    <div class="customer-profile-box">
      <div class="customer-profile-head">
        <h3 class="customer-profile-title" id="customerProfileTitle">Customer Profile</h3>
        <button type="button" class="customer-profile-close" id="customerProfileCloseBtn">×</button>
      </div>
      <div id="customerProfileContent">
        <div class="empty-box">لم يتم تحميل بيانات العميل بعد.</div>
      </div>
    </div>
  `;
  document.body.appendChild(modal);

  const closeBtn = document.getElementById('customerProfileCloseBtn');
  if (closeBtn) {
    closeBtn.addEventListener('click', closeCustomerProfile);
  }

  modal.addEventListener('click', function (e) {
    if (e.target === modal) {
      closeCustomerProfile();
    }
  });
}

function renderCustomerTypeBadge(order) {
  const isGuest = !!order.is_guest;
  const label = isGuest ? 'Guest' : 'Registered';
  const cssClass = isGuest ? 'status-chip status-cancelled' : 'status-chip status-delivered';

  return `<span class="${cssClass}" style="min-width:auto; padding:6px 10px; font-size:11px;">${escapeHtml(label)}</span>`;
}

function renderCustomerCell(order) {
  const name = escapeHtml(order.customer_name || '-');
  const email = escapeHtml(order.customer_email || '-');
  const whatsapp = escapeHtml(order.customer_whatsapp || '-');
  const isGuest = !!order.is_guest;
  const shortTypeText = isGuest ? 'طلب ضيف من الموقع' : 'عميل مسجل';

  const safePayload = encodeURIComponent(JSON.stringify({
    order_number: order.order_number || '',
    customer_name: order.customer_name || '',
    customer_email: order.customer_email || '',
    customer_whatsapp: order.customer_whatsapp || '',
    customer_id: order.customer_id ?? null,
    is_guest: !!order.is_guest,
    customer_type_label: order.customer_type_label || (isGuest ? 'Guest' : 'Registered'),
    created_at: order.created_at || '',
    status: order.status || '',
    raw_status: order.raw_status || '',
    is_first_order: !!order.is_first_order,
    has_promotional_gift: !!order.has_promotional_gift,
    gift_label: order.gift_label || ''
  }));

  return `
    <div style="display:flex; flex-direction:column; gap:6px;">
      <button type="button" class="customer-link-btn" onclick="openCustomerProfileFromEncoded('${safePayload}')">${name}</button>
      <div style="font-size:12px; color:#8fa6c9;">${shortTypeText}</div>
      <div style="margin-top:2px;">${renderCustomerTypeBadge(order)}</div>
      <div style="font-size:12px; color:#8fa6c9; line-height:1.7;">
        <div><strong>Email:</strong> ${email}</div>
        <div><strong>WhatsApp:</strong> ${whatsapp}</div>
      </div>
    </div>
  `;
}

function renderCustomerProfile(order) {
  const content = getEl('customerProfileContent');
  const title = getEl('customerProfileTitle');
  if (!content || !title) return;

  title.textContent = `Customer Profile - ${order.customer_name || '-'}`;

  const giftText = order.has_promotional_gift
    ? (order.gift_label || 'Free gift for first order')
    : 'No';

  content.innerHTML = `
    <div class="customer-profile-badges">
      ${renderCustomerTypeBadge(order)}
      <span class="status-chip ${getAdminOrderStatusClass(order.raw_status || '')}" style="min-width:auto; padding:6px 10px; font-size:11px;">
        ${escapeHtml(formatAdminOrderStatus(order.raw_status || '', ''))}
      </span>
    </div>

    <div class="customer-profile-grid">
      <div class="customer-profile-card">
        <strong>Customer Name</strong>
        <span>${escapeHtml(order.customer_name || '-')}</span>
      </div>

      <div class="customer-profile-card">
        <strong>Customer Type</strong>
        <span>${escapeHtml(order.customer_type_label || (order.is_guest ? 'Guest' : 'Registered'))}</span>
      </div>

      <div class="customer-profile-card">
        <strong>Email</strong>
        <span>${escapeHtml(order.customer_email || '-')}</span>
      </div>

      <div class="customer-profile-card">
        <strong>WhatsApp</strong>
        <span>${escapeHtml(order.customer_whatsapp || '-')}</span>
      </div>

      <div class="customer-profile-card">
        <strong>Order Number</strong>
        <span>${escapeHtml(order.order_number || '-')}</span>
      </div>

      <div class="customer-profile-card">
        <strong>Order Date</strong>
        <span>${escapeHtml(order.created_at || '-')}</span>
      </div>

      <div class="customer-profile-card">
        <strong>Current Status</strong>
        <span>${escapeHtml(formatAdminOrderStatus(order.raw_status || '', ''))}</span>
      </div>

      <div class="customer-profile-card">
        <strong>Customer ID</strong>
        <span>${order.customer_id ? escapeHtml(String(order.customer_id)) : '-'}</span>
      </div>

      <div class="customer-profile-card">
        <strong>First Order</strong>
        <span>${order.is_first_order ? 'Yes' : 'No'}</span>
      </div>

      <div class="customer-profile-card">
        <strong>Promotional Gift</strong>
        <span>${escapeHtml(giftText)}</span>
      </div>
    </div>
  `;
}

window.openCustomerProfileFromEncoded = function (encodedPayload) {
  ensureCustomerProfileModal();

  let order = null;

  try {
    order = JSON.parse(decodeURIComponent(encodedPayload || ''));
  } catch (e) {
    adminSetStatus('dashboardStatus', 'error', 'تعذر فتح بيانات العميل.');
    return;
  }

  const modal = getEl('customerProfileModal');
  if (!modal || !order) return;

  renderCustomerProfile(order);
  modal.classList.add('active');
};

window.closeCustomerProfile = function () {
  const modal = getEl('customerProfileModal');
  if (modal) modal.classList.remove('active');
};

function renderAdminOrdersTable(orders, apiPermissions = null) {
  const listWrap = getEl('adminOrdersCards');
  const emptyBox = getEl('ordersEmptyBox');
  const perms = getOrderActionPermissions(apiPermissions);

  if (!listWrap) return;

  if (!perms.canViewOrders) {
    listWrap.innerHTML = `<div class="empty-box">ليس لديك صلاحية عرض الطلبات.</div>`;
    if (emptyBox) emptyBox.style.display = 'block';
    return;
  }

  if (!Array.isArray(orders) || orders.length === 0) {
    listWrap.innerHTML = `<div class="empty-box">لا توجد طلبات مطابقة للفلاتر الحالية.</div>`;
    if (emptyBox) emptyBox.style.display = 'block';
    return;
  }

  if (emptyBox) emptyBox.style.display = 'none';

  listWrap.innerHTML = orders.map(order => {
    const groupedItems = groupOrderItems(order.items || []);
    const titles = groupedItems.map(item => `${escapeHtml(item.title)}${item.quantity > 1 ? ` × ${item.quantity}` : ''}`).join(' + ');
    const plans = groupedItems.map(item => {
      return [item.down_payment, item.monthly, item.duration].filter(Boolean).join(' / ');
    }).filter(Boolean).join(' | ');
    const totalQty = groupedItems.reduce((sum, item) => sum + Number(item.quantity || 0), 0);

    const rawStatus = String(order.raw_status || '').toLowerCase();
    const statusClass = getAdminOrderStatusClass(rawStatus);
    const statusLabel = formatAdminOrderStatus(rawStatus, order.rejection_reason || '');

    const canApprove = perms.canApprove && !['approved', 'on_the_way', 'completed', 'cancelled'].includes(rawStatus);
    const canOnTheWay = perms.canOnTheWay && ['pending', 'approved'].includes(rawStatus);
    const canDeliver = perms.canDeliver && ['approved', 'on_the_way'].includes(rawStatus);
    const canReject = perms.canReject && !['rejected', 'completed', 'cancelled', 'on_the_way'].includes(rawStatus);
    const canPending = perms.canPending && !['pending', 'cancelled', 'completed'].includes(rawStatus);
    const canHistory = perms.canViewHistory;

    return `
      <div class="order-line-card">
        <div class="order-line-order-number">${escapeHtml(order.order_number || '')}</div>

        <div class="order-line-customer">
          ${renderCustomerCell(order)}
        </div>

        <div class="order-line-products">
          <div class="order-line-products-title">${titles || '-'}</div>
          <div class="order-line-products-plan">${plans || '-'}</div>
        </div>

        <div class="order-line-qty">${totalQty || 0}</div>

        <div class="order-line-total">${Number(order.total_amount || 0).toFixed(3)} ${escapeHtml(order.currency_code || 'KWD')}</div>

        <div class="order-line-date">${escapeHtml(order.created_at || '')}</div>

        <div class="order-line-status">
          <span class="status-chip ${statusClass}">${escapeHtml(statusLabel)}</span>
        </div>

        <div class="order-line-actions">
          ${canApprove ? `<button class="btn btn-primary secondary-btn" type="button" onclick="approveAdminOrder('${String(order.order_number || '').replace(/'/g, "\'")}')">Approve</button>` : ''}
          ${canOnTheWay ? `<button class="btn btn-primary secondary-btn" type="button" onclick="markOrderOnTheWay('${String(order.order_number || '').replace(/'/g, "\'")}')">On The Way</button>` : ''}
          ${canPending ? `<button class="btn btn-primary secondary-btn" type="button" onclick="setOrderPending('${String(order.order_number || '').replace(/'/g, "\'")}')">Pending</button>` : ''}
          ${canDeliver ? `<button class="btn success-btn" type="button" onclick="markOrderDelivered('${String(order.order_number || '').replace(/'/g, "\'")}')">Delivered</button>` : ''}
          ${canReject ? `<button class="btn warning-btn" type="button" onclick="rejectAdminOrder('${String(order.order_number || '').replace(/'/g, "\'")}')">Reject</button>` : ''}
          ${canHistory ? `<button class="btn btn-primary secondary-btn" type="button" onclick="openOrderHistory('${String(order.order_number || '').replace(/'/g, "\'")}')">History</button>` : ''}
        </div>
      </div>
    `;
  }).join('');
}

function renderOrdersSummary(summary) {
  const all = getEl('ordersCountAll');
  const pending = getEl('ordersCountPending');
  const delivered = getEl('ordersCountDelivered');
  const rejected = getEl('ordersCountRejected');

  if (all) all.textContent = String(summary?.all ?? 0);
  if (pending) pending.textContent = String(summary?.pending ?? 0);
  if (delivered) delivered.textContent = String(summary?.delivered ?? 0);
  if (rejected) rejected.textContent = String(summary?.rejected_cancelled ?? 0);
}

async function loadOrdersManagement() {
  if (!requireFrontendPermissionOrWarn('orders_view', 'ليس لديك صلاحية لعرض الطلبات.')) {
    const listWrap = getEl('adminOrdersCards');
    if (listWrap) {
      listWrap.innerHTML = `<div class="empty-box">ليس لديك صلاحية عرض الطلبات.</div>`;
    }

    renderOrdersSummary({ all: 0, pending: 0, delivered: 0, rejected_cancelled: 0 });
    return;
  }

  const search = getEl('ordersSearchInput')?.value.trim() || '';
  const status = getEl('ordersStatusFilter')?.value.trim() || '';
  const date = getEl('ordersDateFilter')?.value.trim() || '';

  const params = new URLSearchParams();

  if (search) params.set('search', search);
  if (status) params.set('status', status);
  if (date) params.set('date', date);

  adminSetStatus('dashboardStatus', 'info', 'جاري تحميل الطلبات...');

  try {
    const query = params.toString() ? `?${params.toString()}` : '';
    const { data } = await adminFetchJson(`/admin/api/get-orders.php${query}`);

    if (!data.ok) {
      adminSetStatus('dashboardStatus', 'error', data.message || 'فشل تحميل الطلبات.');
      return;
    }

    renderAdminOrdersTable(data.orders || [], data.permissions || null);
    renderOrdersSummary(data.summary || {});
    adminSetStatus('dashboardStatus', 'success', 'تم تحميل الطلبات بنجاح.');
  } catch (e) {
    adminSetStatus('dashboardStatus', 'error', e.message || 'حدث خطأ أثناء تحميل الطلبات.');
  }
}

window.loadOrdersManagement = loadOrdersManagement;

async function updateOrderStatus(orderNumber, endpoint, permissionKey, successMessage) {
  if (!requireFrontendPermissionOrWarn(permissionKey, 'ليس لديك صلاحية لتنفيذ هذا الإجراء.')) return;

  adminSetStatus('dashboardStatus', 'info', 'جاري تحديث حالة الطلب...');

  try {
    const { data } = await adminFetchJson(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ order_number: orderNumber })
    });

    if (!data.ok) {
      adminSetStatus('dashboardStatus', 'error', data.message || 'فشل تحديث حالة الطلب.');
      return;
    }

    adminSetStatus('dashboardStatus', 'success', successMessage);
    await loadOrdersManagement();
  } catch (e) {
    adminSetStatus('dashboardStatus', 'error', e.message || 'حدث خطأ أثناء تحديث حالة الطلب.');
  }
}

window.approveAdminOrder = async function (orderNumber) {
  if (!confirm('هل أنت متأكد من اعتماد هذا الطلب؟')) return;
  await updateOrderStatus(orderNumber, '/admin/api/approve-order.php', 'orders_approve', 'تم اعتماد الطلب بنجاح.');
};

window.markOrderOnTheWay = async function (orderNumber) {
  if (!confirm('هل أنت متأكد من تحويل الطلب إلى On The Way؟')) return;
  await updateOrderStatus(orderNumber, '/admin/api/mark-order-on-the-way.php', 'orders_mark_on_the_way', 'تم تحويل الطلب إلى On The Way بنجاح.');
};

window.rejectAdminOrder = async function (orderNumber) {
  if (!confirm('هل أنت متأكد من رفض هذا الطلب؟')) return;
  await updateOrderStatus(orderNumber, '/admin/api/reject-order.php', 'orders_reject', 'تم رفض الطلب بنجاح.');
};

window.markOrderDelivered = async function (orderNumber) {
  if (!confirm('هل أنت متأكد من تحويل الطلب إلى Delivered؟')) return;
  await updateOrderStatus(orderNumber, '/admin/api/mark-order-delivered.php', 'orders_mark_delivered', 'تم تحويل الطلب إلى Delivered بنجاح.');
};

window.setOrderPending = async function (orderNumber) {
  if (!confirm('هل أنت متأكد من إعادة الطلب إلى Pending؟')) return;
  await updateOrderStatus(orderNumber, '/admin/api/mark-order-pending.php', 'orders_mark_pending', 'تم تحويل الطلب إلى Pending بنجاح.');
};

/* =========================
   ORDER HISTORY
========================= */

function renderOrderHistory(history) {
  const content = getEl('orderHistoryContent');
  if (!content) return;

  if (!Array.isArray(history) || history.length === 0) {
    content.innerHTML = `<div class="empty-box">لا يوجد سجل تغييرات لهذا الطلب حتى الآن.</div>`;
    return;
  }

  content.innerHTML = history.map(item => {
    const oldStatus = String(item.old_status || '').trim() || '-';
    const newStatus = String(item.new_status || '').trim() || '-';
    const actor = String(item.changed_by_label || 'System').trim();
    const notes = String(item.notes || '').trim() || '-';
    const createdAt = String(item.created_at || '').trim() || '-';
    const isAdminOverride = /override|exception|admin/i.test(notes) || Boolean(item.is_admin_override);

    return `
      <div class="history-item ${isAdminOverride ? 'admin-override-item' : ''}">
        <div class="history-row">
          <strong>From:</strong> <span>${escapeHtml(oldStatus)}</span>
          <strong>To:</strong> <span>${escapeHtml(newStatus)}</span>
        </div>
        <div class="history-meta">
          <div><strong>By:</strong> ${escapeHtml(actor)}</div>
          <div><strong>Notes:</strong> ${escapeHtml(notes)}</div>
          <div><strong>Date:</strong> ${escapeHtml(createdAt)}</div>
          ${isAdminOverride ? `<div><strong>Override:</strong> Admin Override</div>` : ''}
        </div>
      </div>
    `;
  }).join('');
}

window.openOrderHistory = async function (orderNumber) {
  if (!requireFrontendPermissionOrWarn('orders_history_view', 'ليس لديك صلاحية لعرض سجل الطلب.')) return;

  const modal = getEl('orderHistoryModal');
  const title = getEl('orderHistoryTitle');
  const content = getEl('orderHistoryContent');

  if (!modal || !title || !content) return;

  title.textContent = `Order History - ${orderNumber}`;
  content.innerHTML = `<div class="empty-box">جاري تحميل السجل...</div>`;
  modal.classList.add('active');

  try {
    const params = new URLSearchParams({ order_number: orderNumber });
    const { data } = await adminFetchJson(`/admin/api/get-order-history.php?${params.toString()}`);

    if (!data.ok) {
      content.innerHTML = `<div class="empty-box">${escapeHtml(data.message || 'فشل تحميل السجل.')}</div>`;
      return;
    }

    renderOrderHistory(data.history || []);
  } catch (e) {
    content.innerHTML = `<div class="empty-box">${escapeHtml(e.message || 'حدث خطأ أثناء تحميل السجل.')}</div>`;
  }
};

window.closeOrderHistory = function () {
  const modal = getEl('orderHistoryModal');
  if (modal) modal.classList.remove('active');
};

function bindOrderHistoryModal() {
  const modal = getEl('orderHistoryModal');
  const closeBtn = getEl('orderHistoryCloseBtn');

  if (closeBtn) {
    closeBtn.addEventListener('click', closeOrderHistory);
  }

  if (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target === modal) {
        closeOrderHistory();
      }
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeOrderHistory();
      closeCustomerProfile();
    }
  });
}

function bindOrdersManagementButtons() {
  const loadBtn = getEl('loadOrdersBtn');
  const refreshBtn = getEl('refreshOrdersBtn');
  const searchInput = getEl('ordersSearchInput');
  const statusFilter = getEl('ordersStatusFilter');
  const dateFilter = getEl('ordersDateFilter');

  if (loadBtn) {
    loadBtn.addEventListener('click', loadOrdersManagement);
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', loadOrdersManagement);
  }

  if (searchInput) {
    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        loadOrdersManagement();
      }
    });
  }

  if (statusFilter) {
    statusFilter.addEventListener('change', loadOrdersManagement);
  }

  if (dateFilter) {
    dateFilter.addEventListener('change', loadOrdersManagement);
  }
}

/* =========================
   AUTH
========================= */

async function loadAdminProfileAndPermissions() {
  try {
    const { data } = await adminFetchJson('/admin/api/me.php');

    if (data?.ok) {
      setAdminAuthState(data.user || null, data.permissions || data.permission_codes || []);
      return data.user || null;
    }
  } catch (e) {}

  return null;
}

async function checkAuth() {
  try {
    const res = await fetch('/admin/api/check-auth.php', {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-store'
    });
    const data = await res.json();

    if (data.ok) {
      const meUser = await loadAdminProfileAndPermissions();

      if (!meUser) {
        setAdminAuthState(data.user || null, data.permissions || []);
      }

      showDashboard(ADMIN_STATE.user || data.user);
      applyPermissionDrivenUI();
      await refreshBootstrapCatalogData();
      bindAddProductCategoryOnly('ocrCategory');
      bindEditCategoryBrandFilter('editCategory', 'editBrand');
      updateProductJsonPreview();
      clearStockReview();
    } else {
      setAdminAuthState(null, []);
      showLogin();
    }
  } catch (e) {
    setAdminAuthState(null, []);
    showLogin();
  }
}

function showDashboard(user) {
  const loginPage = getEl('loginPage');
  const dashboardPage = getEl('dashboardPage');

  if (loginPage) loginPage.classList.add('hidden');
  if (dashboardPage) dashboardPage.classList.remove('hidden');

  const fullName = getEl('viewerFullName');
  const username = getEl('viewerUsername');
  const role = getEl('viewerRole');

  if (fullName) fullName.textContent = user?.full_name || '-';
  if (username) username.textContent = user?.username || '-';
  if (role) role.textContent = user?.role_name || '-';
}

function showLogin() {
  const loginPage = getEl('loginPage');
  const dashboardPage = getEl('dashboardPage');

  if (dashboardPage) dashboardPage.classList.add('hidden');
  if (loginPage) loginPage.classList.remove('hidden');
}

async function doLogin() {
  adminSetStatus('loginStatus', 'info', 'جاري تسجيل الدخول...');

  const username = getEl('username')?.value.trim() || '';
  const password = getEl('password')?.value || '';

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

    const password = getEl('password');
    if (password) password.value = '';

    setAdminAuthState(null, []);
    adminSetStatus('loginStatus', 'success', 'تم تسجيل الخروج بنجاح.');
    showLogin();
  } catch (e) {
    adminSetStatus('dashboardStatus', 'error', 'حدث خطأ أثناء تسجيل الخروج.');
  }
}

function bindAuthButtons() {
  const loginBtn = getEl('loginBtn');
  const logoutBtn = getEl('logoutBtn');
  const passwordInput = getEl('password');

  if (loginBtn) loginBtn.addEventListener('click', doLogin);
  if (logoutBtn) logoutBtn.addEventListener('click', doLogout);

  if (passwordInput) {
    passwordInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        doLogin();
      }
    });
  }
}

/* =========================
   INIT
========================= */

function initializeAdminUI() {
  bindAuthButtons();
  bindTabSwitching();

  bindProductUploadButton();
  bindProductPreviewAutoUpdate();
  bindProductSaveButton();
  bindProductClearButton();
  bindEditImageButton();

  bindOrdersManagementButtons();
  bindOrderHistoryModal();
  ensureCustomerProfileModal();

  updateProductJsonPreview();
  clearStockReview();
  checkAuth();
}

document.addEventListener('DOMContentLoaded', initializeAdminUI);
