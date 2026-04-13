let PRODUCTS_CATEGORIES = [];
let FILTER_BRANDS = [];
let EDIT_BRANDS = [];
let CATEGORY_BRANDS_CACHE = {};
let PRODUCTS_ROWS = [];
let CURRENT_PRODUCT = null;
let PRODUCT_STOCK_STATUS_CACHE = {};
let CURRENT_EDIT_STOCK_REVIEW = {
  productId: null,
  devicesCount: 0,
  linked: [],
  missing: []
};

function productsSetStatus(type, message) {
  const box = document.getElementById('productsStatus');
  if (!box) return;
  box.className = `status-box show ${type}`;
  box.textContent = message;
}

function productsClearStatus() {
  const box = document.getElementById('productsStatus');
  if (!box) return;
  box.className = 'status-box';
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

function setProductsCount() {
  const el = document.getElementById('productsListCount');
  if (!el) return;
  el.textContent = String(Array.isArray(PRODUCTS_ROWS) ? PRODUCTS_ROWS.length : 0);
}

function setEditStockStateBadge(state = 'neutral', text = '—') {
  const badge = document.getElementById('editStockStateBadge');
  if (!badge) return;
  badge.className = `stock-state-chip ${state}`;
  badge.textContent = text;
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
  document.getElementById('productStockLinksWrap').innerHTML = `<div class="stock-placeholder"></div>`;
  setEditStockStateBadge('neutral', '—');
}

function getSkuSizeClass(sku) {
  const len = String(sku || '').length;
  if (len > 90) return 'sku-xs';
  if (len > 60) return 'sku-sm';
  return '';
}

function getProductStockBadgeHtml(product) {
  const state = product.stock_state || 'unknown';
  const text = product.stock_label || 'جاري فحص المخزن';
  const css = state === 'complete'
    ? 'stock-complete'
    : state === 'incomplete'
      ? 'stock-incomplete'
      : 'stock-unknown';

  return `<span class="badge ${css}">${escapeHtml(text)}</span>`;
}

function renderProductsCards() {
  const wrap = document.getElementById('productsCardsWrap');
  if (!wrap) return;

  setProductsCount();

  if (!Array.isArray(PRODUCTS_ROWS) || PRODUCTS_ROWS.length === 0) {
    wrap.innerHTML = `<div class="empty-box">لا توجد منتجات مطابقة.</div>`;
    return;
  }

  wrap.innerHTML = PRODUCTS_ROWS.map((product, index) => {
    const availabilityBadge = product.is_available
      ? '<span class="badge active">Available</span>'
      : '<span class="badge inactive">Out of Stock</span>';

    const hotBadge = product.is_hot_offer
      ? '<span class="badge hot">Hot Offer</span>'
      : '';

    const skuClass = getSkuSizeClass(product.sku || '');

    return `
      <div class="product-card-item" id="productCard_${product.id}">
        <img class="product-card-thumb" src="${escapeHtml(product.image_path || '')}" alt="">

        <div class="product-card-body">
          <div class="product-card-top">
            <h3 class="product-card-title">${index + 1}. ${escapeHtml(product.title || '')}</h3>
          </div>

          <div class="sku-line ${skuClass}">${escapeHtml(product.sku || '-')}</div>

          <div class="product-meta-line">
            ${Number(product.devices_count || 1)} Devices &nbsp;/&nbsp; ${escapeHtml(product.price_logic || '-')}
          </div>

          <div class="product-badges">
            ${availabilityBadge}
            ${hotBadge}
            ${getProductStockBadgeHtml(product)}
          </div>
        </div>

        <div class="product-card-actions">
          <button type="button" class="btn-primary" onclick="loadProductForEdit(${product.id})">Edit</button>
        </div>
      </div>
    `;
  }).join('');
}

function computeStockStateFromResponse(response, fallbackProduct) {
  const devicesCount = Number(response?.stock_review?.devices_count || fallbackProduct?.devices_count || 0);
  const linkedCount = Array.isArray(response?.stock_review?.linked)
    ? response.stock_review.linked.length
    : Array.isArray(response?.stock_links)
      ? response.stock_links.length
      : 0;

  const missingCount = Array.isArray(response?.stock_review?.missing)
    ? response.stock_review.missing.length
    : Math.max(devicesCount - linkedCount, 0);

  const complete = devicesCount > 0 && linkedCount >= devicesCount && missingCount === 0;

  return {
    state: complete ? 'complete' : 'incomplete',
    label: complete ? 'الأصناف مضافة إلى المخزن' : 'الأصناف غير مضافة بالكامل',
    linkedCount,
    missingCount,
    devicesCount
  };
}

async function hydrateProductsStockStates() {
  if (!Array.isArray(PRODUCTS_ROWS) || PRODUCTS_ROWS.length === 0) return;

  await Promise.allSettled(PRODUCTS_ROWS.map(async (product) => {
    try {
      const { data } = await fetchJson(`api/load-product.php?id=${encodeURIComponent(product.id)}`);
      if (!data?.ok) return;

      const summary = computeStockStateFromResponse(data, product);
      PRODUCT_STOCK_STATUS_CACHE[String(product.id)] = summary;
      product.stock_state = summary.state;
      product.stock_label = summary.label;
    } catch (e) {}
  }));

  renderProductsCards();
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

    PRODUCTS_ROWS = Array.isArray(data.products) ? data.products.map(product => ({
      ...product,
      stock_state: PRODUCT_STOCK_STATUS_CACHE[String(product.id)]?.state || 'unknown',
      stock_label: PRODUCT_STOCK_STATUS_CACHE[String(product.id)]?.label || 'جاري فحص المخزن'
    })) : [];

    renderProductsCards();
    productsSetStatus('success', 'تم تحميل المنتجات بنجاح.');
    await hydrateProductsStockStates();
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

function updateEditStockStateFromReview(review, fallbackProduct = null) {
  const summary = computeStockStateFromResponse({ stock_review: review }, fallbackProduct || CURRENT_PRODUCT || {});
  setEditStockStateBadge(summary.state, summary.label);

  if (fallbackProduct?.id) {
    PRODUCT_STOCK_STATUS_CACHE[String(fallbackProduct.id)] = summary;
  }

  if (CURRENT_PRODUCT?.id) {
    PRODUCT_STOCK_STATUS_CACHE[String(CURRENT_PRODUCT.id)] = summary;
  }

  PRODUCTS_ROWS = PRODUCTS_ROWS.map(row => {
    if (Number(row.id) !== Number(fallbackProduct?.id || CURRENT_PRODUCT?.id || 0)) return row;
    return {
      ...row,
      stock_state: summary.state,
      stock_label: summary.label
    };
  });

  renderProductsCards();
}

function renderStockReview(review) {
  const wrap = document.getElementById('productStockLinksWrap');
  if (!wrap) return;

  const linked = Array.isArray(review?.linked) ? [...review.linked] : [];
  const missing = Array.isArray(review?.missing) ? [...review.missing] : [];

  linked.sort((a, b) => Number(a.device_index || 0) - Number(b.device_index || 0));
  missing.sort((a, b) => Number(a.device_index || 0) - Number(b.device_index || 0));

  CURRENT_EDIT_STOCK_REVIEW = {
    productId: Number(review?.product_id || CURRENT_PRODUCT?.id || 0),
    devicesCount: Number(review?.devices_count || (linked.length + missing.length) || CURRENT_PRODUCT?.devices_count || 0),
    linked,
    missing
  };

  updateEditStockStateFromReview(CURRENT_EDIT_STOCK_REVIEW, CURRENT_PRODUCT || null);

  if (!linked.length && !missing.length) {
    wrap.innerHTML = `<div class="stock-placeholder"></div>`;
    return;
  }

  const rows = [];

  linked.forEach(item => {
    rows.push(`
      <div class="link-card linked">
        <div class="link-title">
          <strong>${escapeHtml(item.raw_title || item.stock_title || 'Linked Device')}</strong>
          <span class="badge active">Added</span>
        </div>

        <div class="link-meta">
          <div class="meta-box">
            <small>Category</small>
            <span>${escapeHtml(item.category_name || '-')}</span>
          </div>
          <div class="meta-box">
            <small>Brand</small>
            <span>${escapeHtml(item.brand_name || item.expected_brand_name || '-')}</span>
          </div>
          <div class="meta-box">
            <small>Storage</small>
            <span>${escapeHtml(item.storage_value || '-')}</span>
          </div>
          <div class="meta-box">
            <small>RAM / Network</small>
            <span>${escapeHtml(item.ram_value || '-')} / ${escapeHtml(item.network_value || '-')}</span>
          </div>
        </div>
      </div>
    `);
  });

  missing.forEach(item => {
    const selectId = `editMissingCategory_${Number(item.device_index || 0)}`;
    const expectedCategoryId = String(item.expected_category_id || '').trim();

    rows.push(`
      <div class="link-card missing">
        <div class="link-title">
          <strong>${escapeHtml(item.raw_title || 'Missing Device')}</strong>
          <span class="badge inactive">Not Added</span>
        </div>

        <div class="link-meta">
          <div class="meta-box">
            <small>Brand</small>
            <span>${escapeHtml(item.expected_brand_name || item.brand_guess || '-')}</span>
          </div>
          <div class="meta-box">
            <small>Storage</small>
            <span>${escapeHtml(item.storage_value || '-')}</span>
          </div>
          <div class="meta-box">
            <small>RAM</small>
            <span>${escapeHtml(item.ram_value || '-')}</span>
          </div>
          <div class="meta-box">
            <small>Network</small>
            <span>${escapeHtml(item.network_value || '-')}</span>
          </div>
        </div>

        <div class="link-actions">
          <div class="form-group" style="min-width:220px; margin:0;">
            <label for="${selectId}">Choose Category</label>
            <select id="${selectId}">
              ${buildMissingCategoryOptions(expectedCategoryId)}
            </select>
          </div>

          <button type="button" class="btn-success" onclick="addMissingStockItemFromEdit(${Number(item.device_index || 0)})">Add To Stock</button>
        </div>
      </div>
    `);
  });

  wrap.innerHTML = rows.join('');
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

    const fallbackReview = {
      product_id: product.id || 0,
      devices_count: product.devices_count || 0,
      linked: data.stock_links || [],
      missing: []
    };

    renderStockReview(data.stock_review || fallbackReview);
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
    });
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

    if (CURRENT_PRODUCT) {
      CURRENT_PRODUCT.title = document.getElementById('editProductTitle')?.value.trim() || CURRENT_PRODUCT.title;
      CURRENT_PRODUCT.category_id = Number(document.getElementById('editProductCategory')?.value || CURRENT_PRODUCT.category_id || 0);
      CURRENT_PRODUCT.brand_id = Number(document.getElementById('editProductBrand')?.value || CURRENT_PRODUCT.brand_id || 0);
      CURRENT_PRODUCT.devices_count = Number(document.getElementById('editProductDevicesCount')?.value || CURRENT_PRODUCT.devices_count || 1);
      CURRENT_PRODUCT.image_path = data.image_path || CURRENT_PRODUCT.image_path || '';
    }

    if (data.stock_review) {
      renderStockReview({
        product_id: productId,
        devices_count: data.stock_review.devices_count || 0,
        linked: data.stock_review.linked || [],
        missing: data.stock_review.missing || []
      });
    }

    productsSetStatus('success', data.message || 'تم تحديث المنتج بنجاح.');
    await loadProductsList();
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

    delete PRODUCT_STOCK_STATUS_CACHE[String(productId)];
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

  return matched ? Number(matched.id || 0) : (directBrandId > 0 ? directBrandId : 0);
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
    });

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
