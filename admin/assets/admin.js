let currentOCRFile = null;
let ocrBoxes = [];
let selectedBoxId = null;
let dragState = null;

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
  ocr_view: [
    'ocr_view',
    'view_ocr',
    'manage_ocr',
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

function slugToTitle(text) {
  return String(text || '')
    .replace(/\.[^.]+$/, '')
    .replace(/[_\-]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function normalizeStockText(text) {
  return String(text || '')
    .toLowerCase()
    .replace(/\.[^.]+$/, '')
    .replace(/[_\-]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function toSlug(text) {
  return String(text || '')
    .toLowerCase()
    .replace(/\.[^.]+$/, '')
    .replace(/[+_]/g, ' ')
    .replace(/[^a-z0-9.\-\s]/g, ' ')
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
    .filter(Boolean);
}

function detectBrandFromFilename(text) {
  const source = normalizeStockText(text);

  const knownBrands = [
    { keys: ['samsung'], value: 'Samsung' },
    { keys: ['apple', 'iphone'], value: 'Apple' },
    { keys: ['honor'], value: 'Honor' },
    { keys: ['xiaomi'], value: 'Xiaomi' },
    { keys: ['redmi'], value: 'Redmi' },
    { keys: ['oppo'], value: 'Oppo' },
    { keys: ['vivo'], value: 'Vivo' },
    { keys: ['realme'], value: 'Realme' },
    { keys: ['huawei'], value: 'Huawei' },
    { keys: ['oneplus'], value: 'OnePlus' },
    { keys: ['nokia'], value: 'Nokia' },
    { keys: ['google', 'pixel'], value: 'Google' },
    { keys: ['motorola'], value: 'Motorola' },
    { keys: ['tecno'], value: 'Tecno' },
    { keys: ['infinix'], value: 'Infinix' },
    { keys: ['lenovo'], value: 'Lenovo' },
    { keys: ['asus'], value: 'Asus' },
    { keys: ['acer'], value: 'Acer' },
    { keys: ['hp'], value: 'HP' },
    { keys: ['dell'], value: 'Dell' }
  ];

  for (const brand of knownBrands) {
    if (brand.keys.some(key => source.includes(key))) {
      return brand.value;
    }
  }

  return '';
}

function buildDisplayNameFromFilename(part) {
  const cleaned = String(part || '')
    .replace(/\.[^.]+$/, '')
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

function analyzeFilenameForOCR(filename) {
  const devices = splitDevicesFromFilename(filename);
  const firstDevice = devices[0] || '';
  const brand = detectBrandFromFilename(firstDevice);

  const fullOfferTitle = devices
    .map(device => buildDisplayNameFromFilename(device))
    .filter(Boolean)
    .join(' + ');

  const firstStockName = buildDisplayNameFromFilename(firstDevice);

  return {
    fileName: filename || '',
    devicesCount: devices.length || 1,
    brandFromFilename: brand,
    title: fullOfferTitle || firstStockName,
    stockDisplayName: firstStockName,
    categorySlugGuess: ''
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

function bindOcrCategoryOnly(selectId) {
  const category = getEl(selectId);
  if (!category) return;

  populateCategorySelect(selectId);

  category.addEventListener('change', function () {
    updateProductJsonPreview();
  });
}

/* =========================
   OCR VALUE PARSING
========================= */

function normalizeOcrText(text) {
  return String(text || '')
    .replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d))
    .replace(/\s+/g, ' ')
    .trim();
}

function parseOcrFinancials(text) {
  const normalized = normalizeOcrText(text).toLowerCase();

  const result = {
    downPayment: '',
    monthlyAmount: '',
    durationMonths: ''
  };

  const downPatterns = [
    /zero\s*down\s*payment/i,
    /بدون\s*مقدم/i,
    /مقدم\s*0/i,
    /0\s*(?:kd|kwd)\s*(?:down|down payment|مقدم)/i
  ];

  if (downPatterns.some(rx => rx.test(normalized))) {
    result.downPayment = '0';
  }

  const monthlyPatterns = [
    /(\d+(?:\.\d+)?)\s*(?:kd|kwd)\s*monthly/i,
    /monthly\s*(\d+(?:\.\d+)?)/i,
    /القسط(?:\s*الشهري)?\s*(\d+(?:\.\d+)?)/i
  ];

  for (const rx of monthlyPatterns) {
    const m = normalized.match(rx);
    if (m && m[1]) {
      result.monthlyAmount = m[1];
      break;
    }
  }

  const durationPatterns = [
    /(\d+)\s*months?/i,
    /(\d+)\s*month\s*to\s*pay/i,
    /(\d+)\s*months\s*to\s*pay/i,
    /(\d+)\s*شهر/i
  ];

  for (const rx of durationPatterns) {
    const m = normalized.match(rx);
    if (m && m[1]) {
      result.durationMonths = m[1];
      break;
    }
  }

  const downPayPatterns = [
    /down\s*payment\s*(\d+(?:\.\d+)?)/i,
    /(\d+(?:\.\d+)?)\s*(?:kd|kwd)\s*down\s*payment/i,
    /مقدم\s*(\d+(?:\.\d+)?)/i
  ];

  if (!result.downPayment) {
    for (const rx of downPayPatterns) {
      const m = normalized.match(rx);
      if (m && m[1]) {
        result.downPayment = m[1];
        break;
      }
    }
  }

  return result;
}

/* =========================
   OCR API
========================= */

async function runServerSideOcr(file, boxes = []) {
  const formData = new FormData();
  formData.append('image', file);

  try {
    formData.append('boxes_json', JSON.stringify(boxes || []));
  } catch (e) {
    formData.append('boxes_json', '[]');
  }

  const res = await fetch('/admin/api/ocr-read.php', {
    method: 'POST',
    credentials: 'same-origin',
    body: formData
  });

  const raw = await res.text();
  let data = null;

  try {
    data = JSON.parse(raw);
  } catch (e) {
    throw new Error(raw || 'OCR response is not valid JSON.');
  }

  return data;
}

/* =========================
   JSON PREVIEW / OCR HELPERS
========================= */

function resolveSelectedCategoryRow() {
  const categoryId = String(getEl('ocrCategory')?.value || '').trim();
  return getBootstrapCategories().find(cat => String(cat.id) === categoryId) || null;
}

function resolveBrandRowForSelectedCategory() {
  const categoryId = String(getEl('ocrCategory')?.value || '').trim();
  const brandName = String(getEl('ocrBrandFromFilename')?.value || '').trim().toLowerCase();

  if (!categoryId || !brandName) return null;

  return getBootstrapBrands().find(brand =>
    String(brand.category_id) === categoryId &&
    String(brand.name || '').trim().toLowerCase() === brandName
  ) || null;
}

function buildPreviewImagePath() {
  const categoryRow = resolveSelectedCategoryRow();
  const brandRow = resolveBrandRowForSelectedCategory();
  if (!categoryRow || !brandRow || !currentOCRFile) return '';

  const ext = String(currentOCRFile.name || '').split('.').pop().toLowerCase();
  const slug = toSlug(currentOCRFile.name);
  if (!slug || !ext) return '';

  return `/images/products/${String(categoryRow.slug || '').toLowerCase()}/${String(brandRow.slug || '').toLowerCase()}/${slug}.${ext}`;
}

function buildProductJsonPreviewObject() {
  const categoryRow = resolveSelectedCategoryRow();
  const previewImage = buildPreviewImagePath();

  return {
    title: String(getEl('ocrTitle')?.value || '').trim(),
    category: categoryRow ? String(categoryRow.slug || '').toLowerCase() : '',
    brand: String(getEl('ocrBrandFromFilename')?.value || '').trim(),
    devices_count: safeNum(getEl('ocrDevicesCount')?.value || 1, 1),
    image: previewImage,
    down_payment: safeNum(getEl('ocrDownPayment')?.value || 0, 0),
    monthly: safeNum(getEl('ocrMonthlyAmount')?.value || 0, 0),
    duration: safeNum(getEl('ocrDurationMonths')?.value || 0, 0),
    available: true,
    hot_offer: String(getEl('ocrHotOffer')?.value || '0') === '1',
    brand_priority: 1,
    priority: 1
  };
}

function updateProductJsonPreview() {
  const preview = getEl('productJsonPreview');
  if (!preview) return;

  const data = buildProductJsonPreviewObject();
  preview.value = JSON.stringify(data, null, 2);
}

function applyOcrFinancialsToMainFields(fields) {
  if (!fields) return;

  if (fields.downPayment !== '') {
    setInputValue('ocrDownPayment', fields.downPayment);
  }
  if (fields.monthlyAmount !== '') {
    setInputValue('ocrMonthlyAmount', fields.monthlyAmount);
  }
  if (fields.durationMonths !== '') {
    setInputValue('ocrDurationMonths', fields.durationMonths);
  }

  updateProductJsonPreview();
}

function fillOCRFieldsFromAnalysis(analysis) {
  setInputValue('ocrFileName', analysis.fileName || '');
  setInputValue('ocrTitle', analysis.title || '');
  setInputValue('ocrStockDisplayName', analysis.stockDisplayName || '');
  setInputValue('ocrDevicesCount', analysis.devicesCount || 1);
  setInputValue('ocrBrandFromFilename', analysis.brandFromFilename || '');

  updateProductJsonPreview();
}

function bindOcrManualConfirmButton() {
  const btn = getEl('ocrConfirmManualEditBtn');
  if (!btn) return;

  btn.addEventListener('click', function () {
    if (!requireFrontendPermissionOrWarn('ocr_view', 'ليس لديك صلاحية استخدام OCR.')) return;

    updateProductJsonPreview();
    adminSetStatus('dashboardStatus', 'success', 'تم تأكيد التعديل اليدوي وسيتم الاعتماد على القيم الحالية في JSON.');
  });
}

function bindOcrPreviewAutoUpdate() {
  [
    'ocrCategory',
    'ocrTitle',
    'ocrStockDisplayName',
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

/* =========================
   OCR CORE
========================= */

function clearOCRData(fullReset = false) {
  currentOCRFile = fullReset ? null : currentOCRFile;

  const ids = [
    'ocrFileName',
    'ocrTitle',
    'ocrStockDisplayName',
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
    currentOCRFile = null;
  }

  updateProductJsonPreview();
  adminClearStatus('dashboardStatus');
}

function bindOCRUploadButton() {
  const uploadBtn = getEl('ocrUploadBtn');
  const input = getEl('ocrImageInput');
  const image = getEl('ocrPreviewImage');
  const placeholder = getEl('ocrPreviewPlaceholder');
  const fileNameField = getEl('ocrFileName');

  if (!uploadBtn || !input || !image) return;

  uploadBtn.onclick = null;
  uploadBtn.addEventListener('click', function () {
    if (!requireFrontendPermissionOrWarn('ocr_view', 'ليس لديك صلاحية للوصول إلى OCR.')) return;

    input.value = '';
    input.click();
  });

  input.onchange = null;
  input.addEventListener('change', function () {
    const file = this.files && this.files[0];
    if (!file) return;

    clearOCRData(false);
    currentOCRFile = file;

    if (fileNameField) fileNameField.value = file.name;

    const analysis = analyzeFilenameForOCR(file.name);
    fillOCRFieldsFromAnalysis(analysis);

    const reader = new FileReader();
    reader.onload = function (e) {
      image.src = e.target.result;
      image.classList.remove('hidden');
      image.style.display = 'block';
      if (placeholder) placeholder.classList.add('hidden');
    };
    reader.readAsDataURL(file);

    updateProductJsonPreview();
    adminSetStatus('dashboardStatus', 'info', 'تم رفع الصورة. اختر الفئة يدويًا ثم راجع الاسم قبل الحفظ.');
  });
}

function bindOCRAnalyzeButton() {
  const analyzeBtn = getEl('ocrAnalyzeBtn');
  if (!analyzeBtn) return;

  analyzeBtn.onclick = null;
  analyzeBtn.addEventListener('click', async function () {
    if (!requireFrontendPermissionOrWarn('ocr_view', 'ليس لديك صلاحية لاستخدام OCR.')) return;

    if (!currentOCRFile) {
      adminSetStatus('dashboardStatus', 'error', 'ارفع صورة أولًا قبل التحليل.');
      return;
    }

    const analysis = analyzeFilenameForOCR(currentOCRFile.name);
    fillOCRFieldsFromAnalysis(analysis);

    adminSetStatus('dashboardStatus', 'info', 'تم تحليل اسم الملف. الـ OCR المالي اختياري ويمكنك إدخال القيم يدويًا.');

    try {
      const ocrData = await runServerSideOcr(currentOCRFile, ocrBoxes);

      if (!ocrData?.ok) {
        updateProductJsonPreview();
        return;
      }

      const rawText = [
        ocrData.text || '',
        ...(Array.isArray(ocrData.lines) ? ocrData.lines : [])
      ].join('\n');

      let fields = {
        downPayment: '',
        monthlyAmount: '',
        durationMonths: ''
      };

      if (ocrData.fields && typeof ocrData.fields === 'object') {
        fields.downPayment = String(ocrData.fields.down_payment ?? '');
        fields.monthlyAmount = String(ocrData.fields.monthly_amount ?? '');
        fields.durationMonths = String(ocrData.fields.duration_months ?? '');
      }

      const parsed = parseOcrFinancials(rawText);

      if (!fields.downPayment) fields.downPayment = parsed.downPayment || '';
      if (!fields.monthlyAmount) fields.monthlyAmount = parsed.monthlyAmount || '';
      if (!fields.durationMonths) fields.durationMonths = parsed.durationMonths || '';

      applyOcrFinancialsToMainFields(fields);
    } catch (e) {
      updateProductJsonPreview();
    }
  });
}

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
    });
  });
}

/* =========================
   BOXES
========================= */

function renderBoxes() {
  const layer = getEl('ocrBoxesLayer');
  const list = getEl('ocrBoxesList');
  if (!layer || !list) return;

  layer.innerHTML = '';
  list.innerHTML = '';

  ocrBoxes.forEach(box => {
    const boxEl = document.createElement('div');
    boxEl.className = 'ocr-box' + (selectedBoxId === box.id ? ' selected' : '');
    boxEl.style.left = box.x + '%';
    boxEl.style.top = box.y + '%';
    boxEl.style.width = box.w + '%';
    boxEl.style.height = box.h + '%';
    boxEl.dataset.id = String(box.id);

    const label = document.createElement('div');
    label.className = 'ocr-box-label';
    label.textContent = box.label;
    boxEl.appendChild(label);

    const handle = document.createElement('div');
    handle.className = 'ocr-box-handle';
    boxEl.appendChild(handle);

    boxEl.addEventListener('pointerdown', function (e) {
      if (e.target === handle) {
        startResizeBox(e, box.id);
      } else {
        startDragBox(e, box.id);
      }
    });

    layer.appendChild(boxEl);

    const item = document.createElement('div');
    item.className = 'box-item';

    item.innerHTML = `
      <div class="box-item-grid">
        <div>
          <label>Box</label>
          <input type="text" value="${escapeHtml(box.label)}">
        </div>
        <div>
          <label>Type</label>
          <select>
            <option value="custom"${box.type === 'custom' ? ' selected' : ''}>Custom</option>
            <option value="amounts"${box.type === 'amounts' ? ' selected' : ''}>Amounts</option>
            <option value="device_1_name"${box.type === 'device_1_name' ? ' selected' : ''}>Device 1 Name</option>
            <option value="device_2_name"${box.type === 'device_2_name' ? ' selected' : ''}>Device 2 Name</option>
            <option value="months"${box.type === 'months' ? ' selected' : ''}>Months</option>
          </select>
        </div>
        <div>
          <small>X: ${box.x.toFixed(1)}% | Y: ${box.y.toFixed(1)}% | W: ${box.w.toFixed(1)}% | H: ${box.h.toFixed(1)}%</small>
        </div>
        <div>
          <button class="btn danger-btn" type="button">Delete</button>
        </div>
      </div>
    `;

    const [labelInput, typeSelect, deleteBtn] = item.querySelectorAll('input, select, button');

    labelInput.addEventListener('input', function () {
      box.label = this.value || ('Box ' + box.id);
      renderBoxes();
    });

    typeSelect.addEventListener('change', function () {
      box.type = this.value;
    });

    deleteBtn.addEventListener('click', function () {
      ocrBoxes = ocrBoxes.filter(b => b.id !== box.id);
      if (selectedBoxId === box.id) selectedBoxId = null;
      renderBoxes();
    });

    list.appendChild(item);
  });
}

function addNewBox() {
  const nextId = ocrBoxes.length ? Math.max(...ocrBoxes.map(b => b.id)) + 1 : 1;

  ocrBoxes.push({
    id: nextId,
    label: 'Box ' + nextId,
    type: 'custom',
    x: 12,
    y: 12,
    w: 28,
    h: 16
  });

  selectedBoxId = nextId;
  renderBoxes();
}

function clamp(value, min, max) {
  return Math.max(min, Math.min(max, value));
}

function getLayerRect() {
  const layer = getEl('ocrBoxesLayer');
  return layer ? layer.getBoundingClientRect() : null;
}

function startDragBox(e, boxId) {
  const rect = getLayerRect();
  const box = ocrBoxes.find(b => b.id === boxId);
  if (!rect || !box) return;

  selectedBoxId = boxId;
  dragState = {
    mode: 'drag',
    boxId,
    startX: e.clientX,
    startY: e.clientY,
    boxX: box.x,
    boxY: box.y,
    boxW: box.w,
    boxH: box.h,
    rect
  };

  renderBoxes();
}

function startResizeBox(e, boxId) {
  e.stopPropagation();
  const rect = getLayerRect();
  const box = ocrBoxes.find(b => b.id === boxId);
  if (!rect || !box) return;

  selectedBoxId = boxId;
  dragState = {
    mode: 'resize',
    boxId,
    startX: e.clientX,
    startY: e.clientY,
    boxX: box.x,
    boxY: box.y,
    boxW: box.w,
    boxH: box.h,
    rect
  };

  renderBoxes();
}

function bindBoxInteractions() {
  document.addEventListener('pointermove', function (e) {
    if (!dragState) return;

    const box = ocrBoxes.find(b => b.id === dragState.boxId);
    if (!box) return;

    const dxPercent = ((e.clientX - dragState.startX) / dragState.rect.width) * 100;
    const dyPercent = ((e.clientY - dragState.startY) / dragState.rect.height) * 100;

    if (dragState.mode === 'drag') {
      box.x = clamp(dragState.boxX + dxPercent, 0, 100 - box.w);
      box.y = clamp(dragState.boxY + dyPercent, 0, 100 - box.h);
    }

    if (dragState.mode === 'resize') {
      box.w = clamp(dragState.boxW + dxPercent, 4, 100 - box.x);
      box.h = clamp(dragState.boxH + dyPercent, 2, 100 - box.y);
    }

    renderBoxes();
  });

  document.addEventListener('pointerup', function () {
    dragState = null;
  });
}

function bindBoxButtons() {
  const insertBtn = getEl('ocrInsertBoxBtn');
  const clearBoxesBtn = getEl('ocrClearBoxesBtn');
  const clearDataBtn = getEl('ocrClearDataBtn');

  if (insertBtn) {
    insertBtn.addEventListener('click', function () {
      if (!requireFrontendPermissionOrWarn('ocr_view', 'ليس لديك صلاحية استخدام OCR.')) return;
      addNewBox();
      adminSetStatus('dashboardStatus', 'info', 'تمت إضافة مربع جديد.');
    });
  }

  if (clearBoxesBtn) {
    clearBoxesBtn.addEventListener('click', function () {
      if (!requireFrontendPermissionOrWarn('ocr_view', 'ليس لديك صلاحية استخدام OCR.')) return;
      ocrBoxes = [];
      selectedBoxId = null;
      renderBoxes();
      adminSetStatus('dashboardStatus', 'info', 'تم حذف جميع المربعات.');
    });
  }

  if (clearDataBtn) {
    clearDataBtn.addEventListener('click', function () {
      if (!requireFrontendPermissionOrWarn('ocr_view', 'ليس لديك صلاحية استخدام OCR.')) return;
      clearOCRData(true);
      adminSetStatus('dashboardStatus', 'info', 'تم تفريغ البيانات مع الإبقاء على أماكن المربعات.');
    });
  }
}

/* =========================
   SAVE OCR PRODUCT
========================= */

async function saveOcrProduct() {
  if (!requireFrontendPermissionOrWarn('ocr_view', 'ليس لديك صلاحية إضافة منتج عبر OCR.')) return;

  if (!currentOCRFile) {
    adminSetStatus('dashboardStatus', 'error', 'ارفع صورة أولًا.');
    return;
  }

  const title = String(getEl('ocrTitle')?.value || '').trim();
  const stockDisplayName = String(getEl('ocrStockDisplayName')?.value || '').trim();
  const categoryId = String(getEl('ocrCategory')?.value || '').trim();
  const downPayment = String(getEl('ocrDownPayment')?.value || '0').trim();
  const monthlyAmount = String(getEl('ocrMonthlyAmount')?.value || '0').trim();
  const durationMonths = String(getEl('ocrDurationMonths')?.value || '12').trim();
  const hotOffer = String(getEl('ocrHotOffer')?.value || '0').trim();
  const devicesCount = String(getEl('ocrDevicesCount')?.value || '1').trim();
  const brandName = String(getEl('ocrBrandFromFilename')?.value || '').trim();

  if (!title) {
    adminSetStatus('dashboardStatus', 'error', 'حقل Title مطلوب.');
    return;
  }

  if (!stockDisplayName) {
    adminSetStatus('dashboardStatus', 'error', 'حقل Stock Display Name مطلوب.');
    return;
  }

  if (!categoryId) {
    adminSetStatus('dashboardStatus', 'error', 'اختر Category أولًا.');
    return;
  }

  if (!brandName) {
    adminSetStatus('dashboardStatus', 'error', 'Brand لم يتم استخراجه من اسم الصورة.');
    return;
  }

  const brandRecord = getBootstrapBrands().find(
    brand =>
      String(brand.category_id) === String(categoryId) &&
      String(brand.name || '').trim().toLowerCase() === brandName.toLowerCase()
  );

  if (!brandRecord) {
    adminSetStatus('dashboardStatus', 'error', 'لا يوجد Brand مطابق داخل قاعدة البيانات لهذه الفئة.');
    return;
  }

  const fd = new FormData();
  fd.append('title', title);
  fd.append('stock_display_name', stockDisplayName);
  fd.append('category_id', categoryId);
  fd.append('brand_id', String(brandRecord.id));
  fd.append('devices_count', devicesCount);
  fd.append('duration_months', durationMonths);
  fd.append('down_payment', downPayment);
  fd.append('monthly_amount', monthlyAmount);
  fd.append('is_hot_offer', hotOffer === '1' ? '1' : '0');
  fd.append('is_available', '1');
  fd.append('image', currentOCRFile);

  adminSetStatus('dashboardStatus', 'info', 'جاري حفظ المنتج...');

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
      adminSetStatus('dashboardStatus', 'error', data.message || 'فشل حفظ المنتج.');
      return;
    }

    updateProductJsonPreview();
    adminSetStatus(
      'dashboardStatus',
      'success',
      (data.message || 'تم حفظ المنتج بنجاح.') +
      (data.slug ? ` | slug: ${data.slug}` : '')
    );
  } catch (e) {
    adminSetStatus('dashboardStatus', 'error', e.message || 'حدث خطأ أثناء حفظ المنتج.');
  }
}

function bindOcrSaveButton() {
  const btn = getEl('ocrSaveBtn');
  if (!btn) return;
  btn.addEventListener('click', saveOcrProduct);
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

function renderAdminOrdersTable(orders, apiPermissions = null) {
  const tbody = getEl('adminOrdersTableBody');
  const emptyBox = getEl('ordersEmptyBox');
  const perms = getOrderActionPermissions(apiPermissions);

  if (!tbody) return;

  if (!perms.canViewOrders) {
    tbody.innerHTML = `
      <tr>
        <td colspan="9" style="text-align:center; color:#c8d4ea;">ليس لديك صلاحية عرض الطلبات.</td>
      </tr>
    `;
    if (emptyBox) emptyBox.style.display = 'block';
    return;
  }

  if (!Array.isArray(orders) || orders.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="9" style="text-align:center; color:#c8d4ea;">لا توجد طلبات مطابقة للفلاتر الحالية.</td>
      </tr>
    `;
    if (emptyBox) emptyBox.style.display = 'block';
    return;
  }

  if (emptyBox) emptyBox.style.display = 'none';

  tbody.innerHTML = orders.map(order => {
    const groupedItems = groupOrderItems(order.items || []);

    const itemsHtml = groupedItems.map(item => {
      const details = [
        item.down_payment,
        item.monthly,
        item.duration
      ].filter(Boolean).join(' / ');

      return `
        <span>
          • ${escapeHtml(item.title)} × ${item.quantity}
          ${details ? `<br><small style="color:#8fa6c9;">${escapeHtml(details)}</small>` : ''}
        </span>
      `;
    }).join('');

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
      <tr>
        <td>${escapeHtml(order.order_number || '')}</td>
        <td>${escapeHtml(order.customer_name || '')}</td>
        <td>${escapeHtml(order.customer_email || '')}</td>
        <td>${escapeHtml(order.customer_whatsapp || '')}</td>
        <td>${escapeHtml(order.created_at || '')}</td>
        <td>
          <span class="status-chip ${statusClass}">
            ${escapeHtml(statusLabel)}
          </span>
        </td>
        <td>
          <div class="order-items-preview">
            ${itemsHtml || '<span>-</span>'}
          </div>
        </td>
        <td>${Number(order.total_amount || 0).toFixed(3)} ${escapeHtml(order.currency_code || 'KWD')}</td>
        <td>
          <div class="order-actions-cell">
            ${canApprove ? `<button class="btn btn-primary secondary-btn" type="button" onclick="approveAdminOrder('${String(order.order_number || '').replace(/'/g, "\\'")}')">Approve</button>` : ''}
            ${canOnTheWay ? `<button class="btn btn-primary secondary-btn" type="button" onclick="markOrderOnTheWay('${String(order.order_number || '').replace(/'/g, "\\'")}')">On The Way</button>` : ''}
            ${canPending ? `<button class="btn btn-primary secondary-btn" type="button" onclick="setOrderPending('${String(order.order_number || '').replace(/'/g, "\\'")}')">Pending</button>` : ''}
            ${canDeliver ? `<button class="btn success-btn" type="button" onclick="markOrderDelivered('${String(order.order_number || '').replace(/'/g, "\\'")}')">Delivered</button>` : ''}
            ${canReject ? `<button class="btn warning-btn" type="button" onclick="rejectAdminOrder('${String(order.order_number || '').replace(/'/g, "\\'")}')">Reject</button>` : ''}
            ${canHistory ? `<button class="btn btn-primary secondary-btn" type="button" onclick="openOrderHistory('${String(order.order_number || '').replace(/'/g, "\\'")}')">History</button>` : ''}
          </div>
        </td>
      </tr>
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
    const tbody = getEl('adminOrdersTableBody');
    if (tbody) {
      tbody.innerHTML = `
        <tr>
          <td colspan="9" style="text-align:center; color:#c8d4ea;">ليس لديك صلاحية عرض الطلبات.</td>
        </tr>
      `;
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
      bindOcrCategoryOnly('ocrCategory');
      bindEditCategoryBrandFilter('editCategory', 'editBrand');
      updateProductJsonPreview();
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

  bindOCRUploadButton();
  bindOCRAnalyzeButton();
  bindOcrManualConfirmButton();
  bindOcrPreviewAutoUpdate();
  bindOcrSaveButton();
  bindEditImageButton();

  bindBoxButtons();
  bindBoxInteractions();
  bindOrdersManagementButtons();
  bindOrderHistoryModal();

  renderBoxes();
  updateProductJsonPreview();
  checkAuth();
}

document.addEventListener('DOMContentLoaded', initializeAdminUI);
