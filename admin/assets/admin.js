let currentOCRFile = null;
let ocrBoxes = [];
let selectedBoxId = null;
let dragState = null;

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
  return (text || '')
    .replace(/\.[^.]+$/, '')
    .replace(/[_\-]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function normalizeStockText(text) {
  return (text || '')
    .toLowerCase()
    .replace(/\.[^.]+$/, '')
    .replace(/[_\-]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function splitDevicesFromFilename(filename) {
  const noExt = (filename || '').replace(/\.[^.]+$/, '');
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
  const cleaned = slugToTitle(part);

  return cleaned
    .split(' ')
    .filter(Boolean)
    .map(word => {
      if (/^\d+(gb|tb)$/i.test(word)) return word.toUpperCase();
      if (/^(4g|5g)$/i.test(word)) return word.toUpperCase();
      if (/^ram$/i.test(word)) return 'RAM';
      return word.charAt(0).toUpperCase() + word.slice(1);
    })
    .join(' ');
}

function analyzeFilenameForOCR(filename) {
  const devices = splitDevicesFromFilename(filename);
  const firstDevice = devices[0] || '';

  return {
    fileName: filename || '',
    devicesCount: devices.length || 1,
    brandFromFilename: detectBrandFromFilename(firstDevice),
    title: buildDisplayNameFromFilename(firstDevice),
    stockDisplayName: buildDisplayNameFromFilename(firstDevice)
  };
}

function populateCategorySelect(selectId) {
  const select = document.getElementById(selectId);
  if (!select) return;

  select.innerHTML = '<option value="">Select Category</option>';

  (window.ADMIN_BOOTSTRAP?.categories || []).forEach(cat => {
    const opt = document.createElement('option');
    opt.value = String(cat.id);
    opt.textContent = cat.display_name;
    select.appendChild(opt);
  });
}

function populateBrandSelect(selectId, categoryId = '') {
  const select = document.getElementById(selectId);
  if (!select) return;

  select.innerHTML = '<option value="">Select Brand</option>';

  (window.ADMIN_BOOTSTRAP?.brands || []).forEach(brand => {
    if (categoryId && String(brand.category_id) !== String(categoryId)) return;

    const opt = document.createElement('option');
    opt.value = String(brand.id);
    opt.textContent = brand.name;
    opt.dataset.categoryId = String(brand.category_id);
    select.appendChild(opt);
  });
}

function bindCategoryBrandFilter(categoryId, brandId) {
  const category = document.getElementById(categoryId);
  const brand = document.getElementById(brandId);

  if (!category || !brand) return;

  populateCategorySelect(categoryId);
  populateBrandSelect(brandId);

  category.addEventListener('change', function () {
    populateBrandSelect(brandId, this.value);
  });
}

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
    'ocrDurationMonths'
  ];

  ids.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });

  const hotOffer = document.getElementById('ocrHotOffer');
  if (hotOffer) hotOffer.value = '0';

  if (fullReset) {
    const category = document.getElementById('ocrCategory');
    const brand = document.getElementById('ocrBrand');
    if (category) category.value = '';
    if (brand) populateBrandSelect('ocrBrand', '');
  }

  const image = document.getElementById('ocrPreviewImage');
  const placeholder = document.getElementById('ocrPreviewPlaceholder');
  const input = document.getElementById('ocrImageInput');

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

  adminClearStatus('dashboardStatus');
}

function bindOCRUploadButton() {
  const uploadBtn = document.getElementById('ocrUploadBtn');
  const input = document.getElementById('ocrImageInput');
  const image = document.getElementById('ocrPreviewImage');
  const placeholder = document.getElementById('ocrPreviewPlaceholder');
  const fileNameField = document.getElementById('ocrFileName');

  if (!uploadBtn || !input || !image) return;

  uploadBtn.onclick = null;
  uploadBtn.addEventListener('click', function () {
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

    const reader = new FileReader();
    reader.onload = function (e) {
      image.src = e.target.result;
      image.classList.remove('hidden');
      image.style.display = 'block';
      if (placeholder) placeholder.classList.add('hidden');
    };
    reader.readAsDataURL(file);
  });
}

function fillOCRFieldsFromAnalysis(analysis) {
  const fileName = document.getElementById('ocrFileName');
  const title = document.getElementById('ocrTitle');
  const stockDisplayName = document.getElementById('ocrStockDisplayName');
  const devicesCount = document.getElementById('ocrDevicesCount');
  const brandFromFilename = document.getElementById('ocrBrandFromFilename');

  if (fileName) fileName.value = analysis.fileName || '';
  if (title) title.value = analysis.title || '';
  if (stockDisplayName) stockDisplayName.value = analysis.stockDisplayName || '';
  if (devicesCount) devicesCount.value = analysis.devicesCount || 1;
  if (brandFromFilename) brandFromFilename.value = analysis.brandFromFilename || '';
}

function bindOCRAnalyzeButton() {
  const analyzeBtn = document.getElementById('ocrAnalyzeBtn');
  if (!analyzeBtn) return;

  analyzeBtn.onclick = null;
  analyzeBtn.addEventListener('click', function () {
    if (!currentOCRFile) {
      adminSetStatus('dashboardStatus', 'error', 'ارفع صورة أولًا قبل التحليل.');
      return;
    }

    const analysis = analyzeFilenameForOCR(currentOCRFile.name);
    fillOCRFieldsFromAnalysis(analysis);

    adminSetStatus('dashboardStatus', 'success', 'تم التحليل الأولي من اسم الملف.');
  });
}

function bindEditImageButton() {
  const btn = document.getElementById('editChangeImageBtn');
  const input = document.getElementById('editImageInput');
  const image = document.getElementById('editPreviewImage');

  if (!btn || !input || !image) return;

  btn.onclick = null;
  btn.addEventListener('click', function () {
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

function bindTabSwitching() {
  document.querySelectorAll('.admin-tab-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.admin-tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.admin-panel').forEach(panel => panel.classList.remove('active'));

      this.classList.add('active');

      const tabId = this.dataset.tab;
      const target = document.getElementById(tabId);
      if (target) target.classList.add('active');
    });
  });
}

function renderBoxes() {
  const layer = document.getElementById('ocrBoxesLayer');
  const list = document.getElementById('ocrBoxesList');
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
          <input type="text" value="${box.label}">
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
  const layer = document.getElementById('ocrBoxesLayer');
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
      box.w = clamp(dragState.boxW + dxPercent, 8, 100 - box.x);
      box.h = clamp(dragState.boxH + dyPercent, 8, 100 - box.y);
    }

    renderBoxes();
  });

  document.addEventListener('pointerup', function () {
    dragState = null;
  });
}

function bindBoxButtons() {
  const insertBtn = document.getElementById('ocrInsertBoxBtn');
  const clearBoxesBtn = document.getElementById('ocrClearBoxesBtn');
  const clearDataBtn = document.getElementById('ocrClearDataBtn');

  if (insertBtn) {
    insertBtn.addEventListener('click', function () {
      addNewBox();
    });
  }

  if (clearBoxesBtn) {
    clearBoxesBtn.addEventListener('click', function () {
      ocrBoxes = [];
      selectedBoxId = null;
      renderBoxes();
    });
  }

  if (clearDataBtn) {
    clearDataBtn.addEventListener('click', function () {
      clearOCRData(true);
      adminSetStatus('dashboardStatus', 'info', 'تم تفريغ البيانات مع الإبقاء على أماكن المربعات.');
    });
  }
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

function showDashboard(user) {
  const loginPage = document.getElementById('loginPage');
  const dashboardPage = document.getElementById('dashboardPage');

  if (loginPage) loginPage.classList.add('hidden');
  if (dashboardPage) dashboardPage.classList.remove('hidden');

  const fullName = document.getElementById('viewerFullName');
  const username = document.getElementById('viewerUsername');
  const role = document.getElementById('viewerRole');

  if (fullName) fullName.textContent = user?.full_name || '-';
  if (username) username.textContent = user?.username || '-';
  if (role) role.textContent = user?.role_name || '-';
}

function showLogin() {
  const loginPage = document.getElementById('loginPage');
  const dashboardPage = document.getElementById('dashboardPage');

  if (dashboardPage) dashboardPage.classList.add('hidden');
  if (loginPage) loginPage.classList.remove('hidden');
}

async function doLogin() {
  adminSetStatus('loginStatus', 'info', 'جاري تسجيل الدخول...');

  const username = document.getElementById('username')?.value.trim() || '';
  const password = document.getElementById('password')?.value || '';

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

    const password = document.getElementById('password');
    if (password) password.value = '';

    adminSetStatus('loginStatus', 'success', 'تم تسجيل الخروج بنجاح.');
    showLogin();
  } catch (e) {
    adminSetStatus('dashboardStatus', 'error', 'حدث خطأ أثناء تسجيل الخروج.');
  }
}

function bindAuthButtons() {
  const loginBtn = document.getElementById('loginBtn');
  const logoutBtn = document.getElementById('logoutBtn');
  const passwordInput = document.getElementById('password');

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

function initializeAdminUI() {
  bindAuthButtons();
  bindTabSwitching();

  bindCategoryBrandFilter('ocrCategory', 'ocrBrand');
  bindCategoryBrandFilter('editCategory', 'editBrand');

  bindOCRUploadButton();
  bindOCRAnalyzeButton();
  bindEditImageButton();

  bindBoxButtons();
  bindBoxInteractions();
  renderBoxes();

  checkAuth();
}

document.addEventListener('DOMContentLoaded', initializeAdminUI);
