
let PRODUCTS_CATEGORIES = [];
let FILTER_BRANDS = [];
let EDIT_BRANDS = [];
let CATEGORY_BRANDS_CACHE = {};
let PRODUCTS_ROWS = [];
let CURRENT_PRODUCT = null;
let CURRENT_EDIT_STOCK_REVIEW = {
  productId: null,
  devicesCount: 0,
  linked: [],
  missing: []
};

function productsSetStatus(type, message) {
  const box = document.getElementById('productsStatus');
  if (!box) return;
  box.className = `pm-status-box show ${type}`;
  box.textContent = message;
}

function productsClearStatus() {
  const box = document.getElementById('productsStatus');
  if (!box) return;
  box.className = 'pm-status-box';
  box.textContent = '';
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function normalizeBrandComparable(value) {
  return String(value ?? '')
    .trim()
    .toLowerCase()
    .replace(/[_.]+/g, ' ')
    .replace(/\s+/g, ' ');
}

async function fetchJson(url, options = {}) {
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
    throw new Error(raw || 'Unexpected server response');
  }

  return { res, data };
}

function getCategoryLabel(cat) {
  return String(cat?.display_name || cat?.name_en || cat?.slug || '').trim();
}

function getBrandLabel(brand) {
  return String(brand?.display_name || brand?.name || brand?.slug || '').trim();
}

function buildCategoryOptions(selectedValue = '', allowEmpty = true) {
  const rows = [];
  if (allowEmpty) {
    rows.push('<option value="">Select Category</option>');
  }

  PRODUCTS_CATEGORIES.forEach(cat => {
    const selected = String(selectedValue) === String(cat.id) ? ' selected' : '';
    rows.push(`<option value="${cat.id}"${selected}>${escapeHtml(getCategoryLabel(cat))}</option>`);
  });

  return rows.join('');
}

function buildBrandOptions(brands = [], selectedValue = '', allowEmpty = true) {
  const rows = [];
  if (allowEmpty) {
    rows.push('<option value="">Select Brand</option>');
  }

  brands.forEach(brand => {
    const selected = String(selectedValue) === String(brand.id) ? ' selected' : '';
    rows.push(`<option value="${brand.id}"${selected}>${escapeHtml(getBrandLabel(brand))}</option>`);
  });

  return rows.join('');
}

async function loadBrandsByCategory(categoryId) {
  const normalizedCategoryId = String(categoryId || '').trim();
  if (!normalizedCategoryId) {
    return [];
  }

  if (CATEGORY_BRANDS_CACHE[normalizedCategoryId]) {
    return CATEGORY_BRANDS_CACHE[normalizedCategoryId];
  }

  const { data } = await fetchJson(`api/get-brands.php?category_id=${encodeURIComponent(normalizedCategoryId)}`);
  const brands = Array.isArray(data.brands) ? data.brands : [];
  CATEGORY_BRANDS_CACHE[normalizedCategoryId] = brands;
  return brands;
}

async function populateFilterBrands(categoryId, selectedValue = '') {
  const brandSelect = document.getElementById('productsBrand');
  if (!brandSelect) return;

  if (!categoryId) {
    FILTER_BRANDS = [];
    brandSelect.innerHTML = buildBrandOptions([], '', true);
    return;
  }

  const brands = await loadBrandsByCategory(categoryId);
  FILTER_BRANDS = Array.isArray(brands) ? brands : [];
  brandSelect.innerHTML = buildBrandOptions(FILTER_BRANDS, selectedValue, true);
}

async function populateEditBrands(categoryId, selectedValue = '') {
  const brandSelect = document.getElementById('editProductBrand');
  if (!brandSelect) return;

  if (!categoryId) {
    EDIT_BRANDS = [];
    brandSelect.innerHTML = buildBrandOptions([], '', true);
    return;
  }

  const brands = await loadBrandsByCategory(categoryId);
  EDIT_BRANDS = Array.isArray(brands) ? brands : [];
  brandSelect.innerHTML = buildBrandOptions(EDIT_BRANDS, selectedValue, true);
}

async function loadBootstrapLists() {
  const categorySelect = document.getElementById('productsCategory');
  const editCategory = document.getElementById('editProductCategory');

  const { data: catData } = await fetchJson('api/get-categories.php');
  PRODUCTS_CATEGORIES = Array.isArray(catData.categories) ? catData.categories : [];

  categorySelect.innerHTML = buildCategoryOptions('', true);
  editCategory.innerHTML = buildCategoryOptions('', true);

  if (PRODUCTS_CATEGORIES.length > 0) {
    const firstCategoryId = String(PRODUCTS_CATEGORIES[0].id);
    categorySelect.value = firstCategoryId;
    await populateFilterBrands(firstCategoryId, '');
  } else {
    await populateFilterBrands('', '');
  }

  categorySelect.addEventListener('change', async function () {
    await populateFilterBrands(this.value || '', '');
  });

  editCategory.addEventListener('change', async function () {
    await populateEditBrands(this.value || '', '');
  });
}

function clearEditForm() {
  CURRENT_PRODUCT = null;
  CURRENT_EDIT_STOCK_REVIEW = {
    productId: null,
    devicesCount: 0,
    linked: [],
    missing: []
  };

  document.getElementById('editProductId').value = '';
  document.getElementById('editProductSlug').value = '';
  document.getElementById('editProductTitle').value = '';
  document.getElementById('editProductCategory').innerHTML = buildCategoryOptions('', true);
  document.getElementById('editProductBrand').innerHTML = buildBrandOptions([], '', true);
  document.getElementById('editProductDevicesCount').value = '1';
  document.getElementById('editProductDuration').value = '1';
  document.getElementById('editProductDownPayment').value = '0';
  document.getElementById('editProductMonthly').value = '0';
  document.getElementById('editProductAvailable').value = '1';
  document.getElementById('editProductHotOffer').value = '0';
  document.getElementById('editProductPreviewImage').src = '';
  document.getElementById('editProductImageInput').value = '';
  renderStockSummaryBox();
  document.getElementById('productStockLinksWrap').innerHTML = `<div class="pm-empty-box">اختر منتجًا أولًا لتحميل مراجعة الأجهزة والربط مع المخزن.</div>`;
}

function getSkuClass(sku) {
  const len = String(sku || '').length;
  if (len > 85) return 'pm-product-sku is-very-long';
  if (len > 55) return 'pm-product-sku is-long';
  return 'pm-product-sku';
}

function buildStockBadgeHtml(product) {
  const devicesCount = Number(product.devices_count || 0);
  const linkedCount = Number(product.stock_linked_count || 0);
  const complete = devicesCount > 0 ? linkedCount >= devicesCount : false;

  if (complete) {
    return `<span class="pm-badge stock-ok">الأصناف مضافة إلى المخزن</span>`;
  }

  return `<span class="pm-badge stock-missing">الأصناف غير مضافة بالكامل</span>`;
}

function renderProductsCards() {
  const container = document.getElementById('productsTableBody');
  if (!container) return;

  if (!Array.isArray(PRODUCTS_ROWS) || PRODUCTS_ROWS.length === 0) {
    container.innerHTML = `<div class="pm-empty-box">لا توجد منتجات مطابقة.</div>`;
    return;
  }

  container.innerHTML = PRODUCTS_ROWS.map((product, index) => {
    const availabilityBadge = product.is_available
      ? '<span class="pm-badge active">Available</span>'
      : '<span class="pm-badge inactive">Out of Stock</span>';

    const hotBadge = product.is_hot_offer
      ? '<span class="pm-badge hot">Hot Offer</span>'
      : '';

    const devicesCount = Number(product.devices_count || 0);
    const linkedCount = Number(product.stock_linked_count || 0);
    const priceLogic = escapeHtml(product.price_logic || '-');

    return `
      <article class="pm-product-card">
        <div class="pm-product-head">
          <div class="pm-product-head-right">
            <div class="pm-product-index">No.${index + 1}</div>
            <div class="${getSkuClass(product.sku)}">SKU&nbsp;&nbsp;${escapeHtml(product.sku || '-')}</div>
            <div class="pm-product-title">${escapeHtml(product.title || '-')}</div>
            <div class="pm-product-price">
              Devices&nbsp;&nbsp;${devicesCount}<br>
              Price&nbsp;&nbsp;${priceLogic}
            </div>
          </div>

          <img class="pm-list-thumb" src="${escapeHtml(product.image_path || '')}" alt="">
        </div>

        <div class="pm-product-meta">
          ${availabilityBadge}
          ${hotBadge}
          ${buildStockBadgeHtml(product)}
          <span class="pm-badge">${linkedCount} / ${devicesCount} Linked</span>
        </div>

        <div class="pm-product-actions">
          <button type="button" class="pm-btn pm-btn-primary" onclick="loadProductForEdit(${product.id})">Edit</button>
        </div>
      </article>
    `;
  }).join('');
}

async function hydrateProductsStockState() {
  const tasks = PRODUCTS_ROWS.map(async product => {
    try {
      const { data } = await fetchJson(`api/load-product.php?id=${encodeURIComponent(product.id)}`);
      const stockLinks = Array.isArray(data?.stock_links) ? data.stock_links : [];
      product.stock_linked_count = stockLinks.length;
      product.stock_complete = Number(product.devices_count || 0) > 0
        ? stockLinks.length >= Number(product.devices_count || 0)
        : false;
    } catch (e) {
      product.stock_linked_count = 0;
      product.stock_complete = false;
    }
  });

  await Promise.all(tasks);
}

async function loadProductsList() {
  const categoryId = Number(document.getElementById('productsCategory')?.value || 0);
  const brandId = Number(document.getElementById('productsBrand')?.value || 0);

  if (!categoryId || !brandId) {
    productsSetStatus('error', 'اختر Category و Brand أولًا.');
    return;
  }

  productsSetStatus('info', 'جاري تحميل المنتجات...');

  try {
    const { data } = await fetchJson(`api/list-products.php?category_id=${encodeURIComponent(categoryId)}&brand_id=${encodeURIComponent(brandId)}`);

    if (!data.ok) {
      productsSetStatus('error', data.message || 'Failed to load products.');
      return;
    }

    PRODUCTS_ROWS = Array.isArray(data.products) ? data.products : [];
    renderProductsCards();
    await hydrateProductsStockState();
    renderProductsCards();
    productsSetStatus('success', 'تم تحميل المنتجات بنجاح.');
  } catch (e) {
    productsSetStatus('error', e.message || 'حدث خطأ أثناء تحميل المنتجات.');
  }
}

function buildMissingCategoryOptions(selectedValue = '') {
  return [
    '<option value="">Select Category</option>',
    ...PRODUCTS_CATEGORIES.map(cat => {
      const selected = String(selectedValue) === String(cat.id) ? ' selected' : '';
      return `<option value="${cat.id}"${selected}>${escapeHtml(getCategoryLabel(cat))}</option>`;
    })
  ].join('');
}

function renderStockSummaryBox(review = null, product = null) {
  const box = document.getElementById('editProductStockSummary');
  if (!box) return;

  if (!review || !product) {
    box.className = 'pm-stock-summary-box';
    box.innerHTML = `
      <strong>حالة المخزن</strong>
      <span>اختر منتجًا من القائمة لمعرفة هل جميع أجهزة العرض مضافة إلى المخزن أم لا.</span>
    `;
    return;
  }

  const devicesCount = Number(review.devices_count || product.devices_count || 0);
  const linkedCount = Array.isArray(review.linked) ? review.linked.length : 0;
  const missingCount = Array.isArray(review.missing) && review.missing.length
    ? review.missing.length
    : Math.max(devicesCount - linkedCount, 0);

  const complete = devicesCount > 0 && missingCount === 0;

  box.className = `pm-stock-summary-box ${complete ? 'good' : 'bad'}`;
  box.innerHTML = complete
    ? `
      <strong>الأصناف مضافة إلى المخزن</strong>
      <span>تم ربط ${linkedCount} من أصل ${devicesCount} جهازًا داخل هذا العرض بالمخزن، ولا توجد عناصر ناقصة.</span>
    `
    : `
      <strong>الأصناف غير مضافة بالكامل إلى المخزن</strong>
      <span>تم ربط ${linkedCount} من أصل ${devicesCount} جهازًا. يوجد ${missingCount} جهاز/أجهزة غير مضافة بالكامل إلى المخزن.</span>
    `;
}

function renderStockReview(review, product = null) {
  const wrap = document.getElementById('productStockLinksWrap');
  if (!wrap) return;

  const linked = Array.isArray(review?.linked) ? [...review.linked] : [];
  const missing = Array.isArray(review?.missing) ? [...review.missing] : [];

  linked.sort((a, b) => Number(a.device_index || 0) - Number(b.device_index || 0));
  missing.sort((a, b) => Number(a.device_index || 0) - Number(b.device_index || 0));

  CURRENT_EDIT_STOCK_REVIEW = {
    productId: Number(review?.product_id || CURRENT_PRODUCT?.id || 0),
    devicesCount: Number(review?.devices_count || (product?.devices_count || 0) || (linked.length + missing.length) || 0),
    linked,
    missing
  };

  renderStockSummaryBox(CURRENT_EDIT_STOCK_REVIEW, product || CURRENT_PRODUCT);

  const rows = [];

  linked.forEach(item => {
    const relationLabel = item.product_linked === false
      ? 'Exists in stock / not linked yet'
      : 'Linked to product';

    rows.push(`
      <div class="pm-link-card linked">
        <div class="pm-link-title">
          <strong>${escapeHtml(item.raw_title || item.stock_title || 'Linked Device')}</strong>
          <span class="pm-badge active">Added</span>
        </div>

        <div class="pm-link-meta">
          <div class="pm-meta-box">
            <small>Brand</small>
            <span>${escapeHtml(item.brand_name || item.expected_brand_name || '-')}</span>
          </div>
          <div class="pm-meta-box">
            <small>Category</small>
            <span>${escapeHtml(item.category_name || '-')}</span>
          </div>
          <div class="pm-meta-box">
            <small>Storage</small>
            <span>${escapeHtml(item.storage_value || '-')}</span>
          </div>
          <div class="pm-meta-box">
            <small>RAM / Network</small>
            <span>${escapeHtml(item.ram_value || '-')} / ${escapeHtml(item.network_value || '-')}</span>
          </div>
          <div class="pm-meta-box">
            <small>Product Relation</small>
            <span>${escapeHtml(relationLabel)}</span>
          </div>
          <div class="pm-meta-box">
            <small>Source</small>
            <span>${escapeHtml(String(item.source_type || 'LINKED').toUpperCase())}</span>
          </div>
        </div>
      </div>
    `);
  });

  missing.forEach(item => {
    const selectId = `editMissingCategory_${Number(item.device_index || 0)}`;
    const expectedCategoryId = String(item.expected_category_id || '').trim();
    const brandGuess = item.expected_brand_name || item.brand_guess || '-';

    rows.push(`
      <div class="pm-link-card missing">
        <div class="pm-link-title">
          <strong>${escapeHtml(item.raw_title || 'Missing Device')}</strong>
          <span class="pm-badge inactive">Not Added</span>
        </div>

        <div class="pm-link-meta">
          <div class="pm-meta-box">
            <small>Brand Guess</small>
            <span>${escapeHtml(brandGuess)}</span>
          </div>
          <div class="pm-meta-box">
            <small>Storage</small>
            <span>${escapeHtml(item.storage_value || '-')}</span>
          </div>
          <div class="pm-meta-box">
            <small>RAM</small>
            <span>${escapeHtml(item.ram_value || '-')}</span>
          </div>
          <div class="pm-meta-box">
            <small>Network</small>
            <span>${escapeHtml(item.network_value || '-')}</span>
          </div>
        </div>

        <div class="pm-link-actions">
          <div class="pm-form-group">
            <label for="${selectId}">Choose Category</label>
            <select id="${selectId}">
              ${buildMissingCategoryOptions(expectedCategoryId)}
            </select>
          </div>

          <button type="button" class="pm-btn pm-btn-success" onclick="addMissingStockItemFromEdit(${Number(item.device_index || 0)})">Add To Stock</button>
        </div>
      </div>
    `);
  });

  const devicesCount = Number(CURRENT_EDIT_STOCK_REVIEW.devicesCount || 0);
  const knownCount = linked.length + missing.length;
  const silentMissingCount = Math.max(devicesCount - knownCount, 0);

  for (let i = 0; i < silentMissingCount; i++) {
    rows.push(`
      <div class="pm-link-card missing">
        <div class="pm-link-title">
          <strong>جهاز ناقص بالمخزن</strong>
          <span class="pm-badge inactive">Not Added</span>
        </div>
        <div class="pm-helper-note">
          هذا المنتج ليس مربوطًا بالكامل بالمخزن. إذا أردت تحديد اسم الجهاز الناقص بدقة، استبدل الصورة الحالية بنفس اسم الملف ليتم تحليل الأجهزة وإظهار العناصر الناقصة بالاسم.
        </div>
      </div>
    `);
  }

  wrap.innerHTML = rows.length
    ? rows.join('')
    : `<div class="pm-empty-box">لا توجد أجهزة قابلة للمراجعة لهذا المنتج.</div>`;
}

async function loadProductForEdit(productId) {
  productsSetStatus('info', 'جاري تحميل بيانات المنتج...');

  try {
    const { data } = await fetchJson(`api/load-product.php?id=${encodeURIComponent(productId)}`);

    if (!data.ok) {
      productsSetStatus('error', data.message || 'Failed to load product.');
      return;
    }

    CURRENT_PRODUCT = data.product || null;
    const product = data.product || {};
    const matchingListItem = PRODUCTS_ROWS.find(item => Number(item.id) === Number(product.id));
    const linked = Array.isArray(data.stock_links) ? data.stock_links : [];
    const review = {
      product_id: product.id || 0,
      devices_count: product.devices_count || 0,
      linked,
      missing: []
    };

    document.getElementById('editProductId').value = product.id || '';
    document.getElementById('editProductSlug').value = product.slug || '';
    document.getElementById('editProductTitle').value = product.title || '';
    document.getElementById('editProductDevicesCount').value = product.devices_count || 1;
    document.getElementById('editProductDuration').value = product.duration_months || 1;
    document.getElementById('editProductDownPayment').value = product.down_payment || 0;
    document.getElementById('editProductMonthly').value = product.monthly_amount || 0;
    document.getElementById('editProductAvailable').value = String(Number(product.is_available || 0));
    document.getElementById('editProductHotOffer').value = String(Number(product.is_hot_offer || 0));
    document.getElementById('editProductPreviewImage').src = product.image_path || '';
    document.getElementById('editProductImageInput').value = '';

    document.getElementById('editProductCategory').innerHTML = buildCategoryOptions(product.category_id || '', true);
    await populateEditBrands(product.category_id || '', product.brand_id || '');

    if (matchingListItem) {
      matchingListItem.stock_linked_count = linked.length;
      matchingListItem.stock_complete = Number(product.devices_count || 0) > 0
        ? linked.length >= Number(product.devices_count || 0)
        : false;
      renderProductsCards();
    }

    renderStockReview(review, product);

    productsSetStatus('success', 'تم تحميل بيانات المنتج بنجاح.');
  } catch (e) {
    productsSetStatus('error', e.message || 'حدث خطأ أثناء تحميل بيانات المنتج.');
  }
}

async function reviewImageStockFromFilename(file) {
  if (!file) return;

  const preferredBrandId = Number(document.getElementById('editProductBrand')?.value || 0);
  const preferredCategoryId = Number(document.getElementById('editProductCategory')?.value || 0);

  try {
    const { data } = await fetchJson('api/check-stock-from-filename.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        filename: file.name,
        preferred_brand_id: preferredBrandId,
        preferred_category_id: preferredCategoryId
      })
    });

    if (!data.ok) return;

    renderStockReview({
      product_id: Number(document.getElementById('editProductId')?.value || 0),
      devices_count: data.devices_count || 0,
      linked: data.linked || [],
      missing: data.missing || []
    }, CURRENT_PRODUCT);
  } catch (e) {}
}

function readImagePreview(file) {
  if (!file) return;

  const reader = new FileReader();
  reader.onload = function (e) {
    document.getElementById('editProductPreviewImage').src = e.target?.result || '';
  };
  reader.readAsDataURL(file);
}

async function saveProductChanges() {
  const productId = Number(document.getElementById('editProductId')?.value || 0);

  if (!productId) {
    productsSetStatus('error', 'اختر منتجًا أولًا.');
    return;
  }

  const fd = new FormData();
  fd.append('id', String(productId));
  fd.append('title', document.getElementById('editProductTitle')?.value.trim() || '');
  fd.append('category_id', document.getElementById('editProductCategory')?.value || '');
  fd.append('brand_id', document.getElementById('editProductBrand')?.value || '');
  fd.append('devices_count', document.getElementById('editProductDevicesCount')?.value || '1');
  fd.append('duration_months', document.getElementById('editProductDuration')?.value || '1');
  fd.append('down_payment', document.getElementById('editProductDownPayment')?.value || '0');
  fd.append('monthly_amount', document.getElementById('editProductMonthly')?.value || '0');
  fd.append('is_available', document.getElementById('editProductAvailable')?.value || '1');
  fd.append('is_hot_offer', document.getElementById('editProductHotOffer')?.value || '0');

  const imageFile = document.getElementById('editProductImageInput')?.files?.[0] || null;
  if (imageFile) {
    fd.append('image', imageFile);
  }

  productsSetStatus('info', 'جاري حفظ التعديلات...');

  try {
    const res = await fetch('api/update-product.php', {
      method: 'POST',
      credentials: 'same-origin',
      body: fd
    });

    const raw = await res.text();
    let data = null;

    try {
      data = JSON.parse(raw);
    } catch (e) {
      throw new Error(raw || 'Unexpected server response');
    }

    if (!data.ok) {
      productsSetStatus('error', data.message || 'Failed to update product.');
      return;
    }

    document.getElementById('editProductImageInput').value = '';

    if (data.image_path) {
      document.getElementById('editProductPreviewImage').src = data.image_path;
    }

    if (data.stock_review) {
      renderStockReview({
        product_id: productId,
        devices_count: data.stock_review.devices_count || 0,
        linked: data.stock_review.linked || [],
        missing: data.stock_review.missing || []
      }, CURRENT_PRODUCT);
    }

    productsSetStatus('success', data.message || 'تم تحديث المنتج بنجاح.');
    await loadProductsList();
    await loadProductForEdit(productId);
  } catch (e) {
    productsSetStatus('error', e.message || 'حدث خطأ أثناء تحديث المنتج.');
  }
}

async function deleteCurrentProduct() {
  const productId = Number(document.getElementById('editProductId')?.value || 0);

  if (!productId) {
    productsSetStatus('error', 'اختر منتجًا أولًا.');
    return;
  }

  if (!window.confirm('هل أنت متأكد من حذف هذا المنتج؟')) {
    return;
  }

  productsSetStatus('info', 'جاري حذف المنتج...');

  try {
    const { data } = await fetchJson('api/delete-product.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: productId })
    });

    if (!data.ok) {
      productsSetStatus('error', data.message || 'Failed to delete product.');
      return;
    }

    clearEditForm();
    productsSetStatus('success', data.message || 'تم حذف المنتج بنجاح.');
    await loadProductsList();
  } catch (e) {
    productsSetStatus('error', e.message || 'حدث خطأ أثناء حذف المنتج.');
  }
}

async function resolveBrandIdForMissingItem(item, selectedCategoryId) {
  const directBrandId = Number(item.expected_brand_id || item.brand_id || 0);
  const expectedCategoryId = Number(item.expected_category_id || 0);

  if (directBrandId > 0 && expectedCategoryId > 0 && Number(selectedCategoryId) === expectedCategoryId) {
    return directBrandId;
  }

  const brands = await loadBrandsByCategory(selectedCategoryId);
  const targetNames = [
    String(item.expected_brand_name || '').trim(),
    String(item.brand_guess || '').trim()
  ].filter(Boolean).map(normalizeBrandComparable);

  if (!targetNames.length) {
    return directBrandId > 0 ? directBrandId : 0;
  }

  const matched = brands.find(brand => {
    const candidates = [
      normalizeBrandComparable(brand.name || ''),
      normalizeBrandComparable(brand.display_name || ''),
      normalizeBrandComparable(brand.slug || '')
    ];

    return targetNames.some(name => candidates.includes(name));
  });

  return matched ? Number(brand.id || 0) : (directBrandId > 0 ? directBrandId : 0);
}

async function addMissingStockItemFromEdit(deviceIndex) {
  const item = CURRENT_EDIT_STOCK_REVIEW.missing.find(entry => Number(entry.device_index) === Number(deviceIndex));

  if (!item) {
    productsSetStatus('error', 'لم يتم العثور على الجهاز المطلوب إضافته.');
    return;
  }

  const categorySelect = document.getElementById(`editMissingCategory_${deviceIndex}`);
  const selectedCategoryId = String(categorySelect?.value || '').trim();

  if (!selectedCategoryId) {
    productsSetStatus('error', 'اختر الفئة أولًا قبل الإضافة.');
    return;
  }

  const resolvedBrandId = await resolveBrandIdForMissingItem(item, selectedCategoryId);

  if (resolvedBrandId <= 0) {
    productsSetStatus('error', 'هذا البراند غير مسجل داخل قاعدة البيانات. أضف البراند أولًا من تبويب Categories / Brands.');
    return;
  }

  productsSetStatus('info', 'جاري إضافة الجهاز إلى المخزن...');

  try {
    const { data } = await fetchJson('api/add-missing-stock-item.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        raw_title: item.raw_title || '',
        normalized_title: item.normalized_title || '',
        category_id: Number(selectedCategoryId),
        brand_id: Number(resolvedBrandId),
        storage_value: item.storage_value || null,
        ram_value: item.ram_value || null,
        network_value: item.network_value || null,
        product_id: Number(CURRENT_EDIT_STOCK_REVIEW.productId || 0),
        device_index: Number(item.device_index || deviceIndex || 0),
        extracted_name: item.raw_title || ''
      })
    });

    if (!data.ok) {
      productsSetStatus('error', data.message || 'فشل إضافة الجهاز إلى المخزن.');
      return;
    }

    const stockItem = data.stock_item || {};
    const selectedCategoryText = categorySelect?.options?.[categorySelect.selectedIndex]?.textContent || '';

    CURRENT_EDIT_STOCK_REVIEW.missing = CURRENT_EDIT_STOCK_REVIEW.missing.filter(entry => Number(entry.device_index) !== Number(deviceIndex));
    CURRENT_EDIT_STOCK_REVIEW.linked.push({
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
      brand_name: stockItem.brand_name || item.expected_brand_name || item.brand_guess || '',
      expected_brand_name: item.expected_brand_name || item.brand_guess || '',
      product_linked: true,
      source_type: 'MANUAL'
    });

    renderStockReview({
      product_id: CURRENT_EDIT_STOCK_REVIEW.productId,
      devices_count: CURRENT_EDIT_STOCK_REVIEW.devicesCount,
      linked: CURRENT_EDIT_STOCK_REVIEW.linked,
      missing: CURRENT_EDIT_STOCK_REVIEW.missing
    }, CURRENT_PRODUCT);

    const matchingListItem = PRODUCTS_ROWS.find(entry => Number(entry.id) === Number(CURRENT_EDIT_STOCK_REVIEW.productId));
    if (matchingListItem) {
      matchingListItem.stock_linked_count = CURRENT_EDIT_STOCK_REVIEW.linked.length;
      matchingListItem.stock_complete = CURRENT_EDIT_STOCK_REVIEW.linked.length >= Number(matchingListItem.devices_count || 0);
      renderProductsCards();
    }

    productsSetStatus('success', data.message || 'تمت إضافة الجهاز إلى المخزن بنجاح.');
  } catch (e) {
    productsSetStatus('error', e.message || 'حدث خطأ أثناء إضافة الجهاز إلى المخزن.');
  }
}

window.loadProductForEdit = loadProductForEdit;
window.addMissingStockItemFromEdit = addMissingStockItemFromEdit;

document.addEventListener('DOMContentLoaded', async function () {
  await loadBootstrapLists();
  clearEditForm();

  document.getElementById('loadProductsBtn')?.addEventListener('click', loadProductsList);
  document.getElementById('saveProductChangesBtn')?.addEventListener('click', saveProductChanges);
  document.getElementById('deleteProductBtn')?.addEventListener('click', deleteCurrentProduct);

  document.getElementById('editProductImageInput')?.addEventListener('change', async function () {
    const file = this.files && this.files[0] ? this.files[0] : null;

    if (file) {
      readImagePreview(file);
      await reviewImageStockFromFilename(file);
    }
  });
});
