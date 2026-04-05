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

      if (tabId === 'tab-stats') {
        loadOrdersManagement();
      }
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

function renderAdminOrdersTable(orders) {
  const tbody = document.getElementById('adminOrdersTableBody');
  const emptyBox = document.getElementById('ordersEmptyBox');

  if (!tbody) return;

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
          • ${item.title} × ${item.quantity}
          ${details ? `<br><small style="color:#8fa6c9;">${details}</small>` : ''}
        </span>
      `;
    }).join('');

    const rawStatus = String(order.raw_status || '').toLowerCase();
    const statusClass = getAdminOrderStatusClass(rawStatus);
    const statusLabel = formatAdminOrderStatus(rawStatus, order.rejection_reason || '');

    const canApprove = !['approved', 'on_the_way', 'completed', 'cancelled'].includes(rawStatus);
    const canOnTheWay = ['pending', 'approved'].includes(rawStatus);
    const canDeliver = ['approved', 'on_the_way'].includes(rawStatus);
    const canReject = !['rejected', 'completed', 'cancelled', 'on_the_way'].includes(rawStatus);
    const canPending = !['pending', 'cancelled', 'completed'].includes(rawStatus);

    return `
      <tr>
        <td>${order.order_number || ''}</td>
        <td>${order.customer_name || ''}</td>
        <td>${order.customer_email || ''}</td>
        <td>${order.customer_whatsapp || ''}</td>
        <td>${order.created_at || ''}</td>
        <td>
          <span class="status-chip ${statusClass}">
            ${statusLabel}
          </span>
        </td>
        <td>
          <div class="order-items-preview">
            ${itemsHtml || '<span>-</span>'}
          </div>
        </td>
        <td>${Number(order.total_amount || 0).toFixed(3)} ${order.currency_code || 'KWD'}</td>
        <td>
          <div class="order-actions-cell">
            ${canApprove ? `<button class="btn btn-primary secondary-btn" type="button" onclick="approveAdminOrder('${order.order_number}')">Approve</button>` : ''}
            ${canOnTheWay ? `<button class="btn btn-primary secondary-btn" type="button" onclick="markOrderOnTheWay('${order.order_number}')">On The Way</button>` : ''}
            ${canPending ? `<button class="btn btn-primary secondary-btn" type="button" onclick="setOrderPending('${order.order_number}')">Pending</button>` : ''}
            ${canDeliver ? `<button class="btn success-btn" type="button" onclick="markOrderDelivered('${order.order_number}')">Delivered</button>` : ''}
            ${canReject ? `<button class="btn warning-btn" type="button" onclick="rejectAdminOrder('${order.order_number}')">Reject</button>` : ''}
            <button class="btn btn-primary secondary-btn" type="button" onclick="openOrderHistory('${order.order_number}')">History</button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

function renderOrdersSummary(summary) {
  const all = document.getElementById('ordersCountAll');
  const pending = document.getElementById('ordersCountPending');
  const delivered = document.getElementById('ordersCountDelivered');
  const rejected = document.getElementById('ordersCountRejected');

  if (all) all.textContent = String(summary?.all ?? 0);
  if (pending) pending.textContent = String(summary?.pending ?? 0);
  if (delivered) delivered.textContent = String(summary?.delivered ?? 0);
  if (rejected) rejected.textContent = String(summary?.rejected_cancelled ?? 0);
}

async function loadOrdersManagement() {
  const search = document.getElementById('ordersSearchInput')?.value.trim() || '';
  const status = document.getElementById('ordersStatusFilter')?.value.trim() || '';
  const date = document.getElementById('ordersDateFilter')?.value.trim() || '';

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

    renderAdminOrdersTable(data.orders || []);
    renderOrdersSummary(data.summary || {});
    adminSetStatus('dashboardStatus', 'success', 'تم تحميل الطلبات بنجاح.');
  } catch (e) {
    adminSetStatus('dashboardStatus', 'error', e.message || 'حدث خطأ أثناء تحميل الطلبات.');
  }
}

window.loadOrdersManagement = loadOrdersManagement;

async function updateOrderStatus(orderNumber, endpoint, successMessage) {
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
  await updateOrderStatus(orderNumber, '/admin/api/approve-order.php', 'تم اعتماد الطلب بنجاح.');
};

window.markOrderOnTheWay = async function (orderNumber) {
  if (!confirm('هل أنت متأكد من تحويل الطلب إلى On The Way؟')) return;
  await updateOrderStatus(orderNumber, '/admin/api/mark-order-on-the-way.php', 'تم تحويل الطلب إلى On The Way بنجاح.');
};

window.rejectAdminOrder = async function (orderNumber) {
  if (!confirm('هل أنت متأكد من رفض هذا الطلب؟')) return;
  await updateOrderStatus(orderNumber, '/admin/api/reject-order.php', 'تم رفض الطلب بنجاح.');
};

window.markOrderDelivered = async function (orderNumber) {
  if (!confirm('هل أنت متأكد من تحويل الطلب إلى Delivered؟')) return;
  await updateOrderStatus(orderNumber, '/admin/api/mark-order-delivered.php', 'تم تحويل الطلب إلى Delivered بنجاح.');
};

window.setOrderPending = async function (orderNumber) {
  if (!confirm('هل أنت متأكد من إعادة الطلب إلى Pending؟')) return;
  await updateOrderStatus(orderNumber, '/admin/api/mark-order-pending.php', 'تم تحويل الطلب إلى Pending بنجاح.');
};

function renderOrderHistory(history) {
  const content = document.getElementById('orderHistoryContent');
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

    return `
      <div class="history-item">
        <div class="history-row">
          <strong>From:</strong> <span>${oldStatus}</span>
          <strong>To:</strong> <span>${newStatus}</span>
        </div>
        <div class="history-meta">
          <div><strong>By:</strong> ${actor}</div>
          <div><strong>Notes:</strong> ${notes}</div>
          <div><strong>Date:</strong> ${createdAt}</div>
        </div>
      </div>
    `;
  }).join('');
}

window.openOrderHistory = async function (orderNumber) {
  const modal = document.getElementById('orderHistoryModal');
  const title = document.getElementById('orderHistoryTitle');
  const content = document.getElementById('orderHistoryContent');

  if (!modal || !title || !content) return;

  title.textContent = `Order History - ${orderNumber}`;
  content.innerHTML = `<div class="empty-box">جاري تحميل السجل...</div>`;
  modal.classList.add('active');

  try {
    const params = new URLSearchParams({ order_number: orderNumber });
    const { data } = await adminFetchJson(`/admin/api/get-order-history.php?${params.toString()}`);

    if (!data.ok) {
      content.innerHTML = `<div class="empty-box">${data.message || 'فشل تحميل السجل.'}</div>`;
      return;
    }

    renderOrderHistory(data.history || []);
  } catch (e) {
    content.innerHTML = `<div class="empty-box">${e.message || 'حدث خطأ أثناء تحميل السجل.'}</div>`;
  }
};

window.closeOrderHistory = function () {
  const modal = document.getElementById('orderHistoryModal');
  if (modal) modal.classList.remove('active');
};

function bindOrderHistoryModal() {
  const modal = document.getElementById('orderHistoryModal');
  const closeBtn = document.getElementById('orderHistoryCloseBtn');

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
  const loadBtn = document.getElementById('loadOrdersBtn');
  const refreshBtn = document.getElementById('refreshOrdersBtn');
  const searchInput = document.getElementById('ordersSearchInput');
  const statusFilter = document.getElementById('ordersStatusFilter');
  const dateFilter = document.getElementById('ordersDateFilter');

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
  bindOrdersManagementButtons();
  bindOrderHistoryModal();
  renderBoxes();

  checkAuth();
}

document.addEventListener('DOMContentLoaded', initializeAdminUI);
