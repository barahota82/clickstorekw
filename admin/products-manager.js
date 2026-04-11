let PRODUCTS_CATEGORIES = [];
let PRODUCTS_BRANDS = [];
let PRODUCTS_ROWS = [];
let CURRENT_PRODUCT = null;

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

function buildCategoryOptions(selectedValue = '', allowEmpty = true) {
  const rows = [];
  if (allowEmpty) {
    rows.push('<option value="">Select Category</option>');
  }

  PRODUCTS_CATEGORIES.forEach(cat => {
    const selected = String(selectedValue) === String(cat.id) ? ' selected' : '';
    rows.push(`<option value="${cat.id}"${selected}>${escapeHtml(cat.name_en || cat.slug)}</option>`);
  });

  return rows.join('');
}

function buildBrandOptions(categoryId = '', selectedValue = '', allowEmpty = true) {
  const rows = [];
  if (allowEmpty) {
    rows.push('<option value="">Select Brand</option>');
  }

  PRODUCTS_BRANDS
    .filter(brand => !categoryId || String(brand.category_id) === String(categoryId))
    .forEach(brand => {
      const selected = String(selectedValue) === String(brand.id) ? ' selected' : '';
      rows.push(`<option value="${brand.id}"${selected}>${escapeHtml(brand.display_name || brand.name)}</option>`);
    });

  return rows.join('');
}

async function loadBootstrapLists() {
  const categorySelect = document.getElementById('productsCategory');
  const brandSelect = document.getElementById('productsBrand');
  const editCategory = document.getElementById('editProductCategory');
  const editBrand = document.getElementById('editProductBrand');

  const { data: catData } = await fetchJson('api/get-categories.php');
  PRODUCTS_CATEGORIES = Array.isArray(catData.categories) ? catData.categories : [];

  categorySelect.innerHTML = buildCategoryOptions('', true);
  editCategory.innerHTML = buildCategoryOptions('', true);

  categorySelect.addEventListener('change', async function () {
    const categoryId = this.value || '';
    const { data } = await fetchJson(`api/get-brands.php?category_id=${encodeURIComponent(categoryId)}`);
    PRODUCTS_BRANDS = Array.isArray(data.brands) ? data.brands : [];
    brandSelect.innerHTML = buildBrandOptions(categoryId, '', true);
  });

  editCategory.addEventListener('change', async function () {
    const categoryId = this.value || '';
    const { data } = await fetchJson(`api/get-brands.php?category_id=${encodeURIComponent(categoryId)}`);
    PRODUCTS_BRANDS = Array.isArray(data.brands) ? data.brands : [];
    editBrand.innerHTML = buildBrandOptions(categoryId, '', true);
  });
}

function renderProductsTable() {
  const tbody = document.getElementById('productsTableBody');
  if (!tbody) return;

  if (!Array.isArray(PRODUCTS_ROWS) || PRODUCTS_ROWS.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="8"><div class="empty-box">لا توجد منتجات مطابقة.</div></td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = PRODUCTS_ROWS.map((product, index) => {
    return `
      <tr>
        <td>${index + 1}</td>
        <td><img class="thumb" src="${escapeHtml(product.image_path || '')}" alt=""></td>
        <td>${escapeHtml(product.title)}</td>
        <td>${escapeHtml(product.sku)}</td>
        <td>${escapeHtml(product.price_logic)}</td>
        <td><span class="badge ${product.is_available ? 'active' : 'inactive'}">${product.is_available ? 'Available' : 'Out of Stock'}</span></td>
        <td>${product.is_hot_offer ? '<span class="badge hot">Hot Offer</span>' : '-'}</td>
        <td><button type="button" class="btn-primary" onclick="loadProductForEdit(${product.id})">Edit</button></td>
      </tr>
    `;
  }).join('');
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
    renderProductsTable();
    productsSetStatus('success', 'تم تحميل المنتجات بنجاح.');
  } catch (e) {
    productsSetStatus('error', e.message || 'حدث خطأ أثناء تحميل المنتجات.');
  }
}

function renderStockLinks(data) {
  const wrap = document.getElementById('productStockLinksWrap');
  if (!wrap) return;

  const linked = Array.isArray(data?.linked) ? data.linked : [];
  const missing = Array.isArray(data?.missing) ? data.missing : [];

  if (!linked.length && !missing.length) {
    wrap.innerHTML = `<div class="empty-box">لا توجد روابط مخزن لهذا المنتج.</div>`;
    return;
  }

  const html = [];

  linked.forEach(item => {
    html.push(`
      <div class="link-card linked">
        <strong>Linked Device ${Number(item.device_index || 0)}</strong><br>
        <div>Raw: ${escapeHtml(item.raw_title || item.extracted_name || '-')}</div>
        <div>Stock: ${escapeHtml(item.stock_title || '-')}</div>
        <div>Storage: ${escapeHtml(item.storage_value || '-')}</div>
        <div>RAM: ${escapeHtml(item.ram_value || '-')}</div>
        <div>Network: ${escapeHtml(item.network_value || '-')}</div>
      </div>
    `);
  });

  missing.forEach(item => {
    html.push(`
      <div class="link-card missing">
        <strong>Missing Device ${Number(item.device_index || 0)}</strong><br>
        <div>Raw: ${escapeHtml(item.raw_title || '-')}</div>
        <div>Storage: ${escapeHtml(item.storage_value || '-')}</div>
        <div>RAM: ${escapeHtml(item.ram_value || '-')}</div>
        <div>Network: ${escapeHtml(item.network_value || '-')}</div>
      </div>
    `);
  });

  wrap.innerHTML = html.join('');
}

window.loadProductForEdit = async function (productId) {
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

    document.getElementById('editProductCategory').innerHTML = buildCategoryOptions(product.category_id || '', true);

    const { data: brandData } = await fetchJson(`api/get-brands.php?category_id=${encodeURIComponent(product.category_id || 0)}`);
    PRODUCTS_BRANDS = Array.isArray(brandData.brands) ? brandData.brands : [];
    document.getElementById('editProductBrand').innerHTML = buildBrandOptions(product.category_id || '', product.brand_id || '', true);

    renderStockLinks({
      linked: data.stock_links || [],
      missing: []
    });

    productsSetStatus('success', 'تم تحميل بيانات المنتج بنجاح.');
  } catch (e) {
    productsSetStatus('error', e.message || 'حدث خطأ أثناء تحميل بيانات المنتج.');
  }
};

async function reviewImageStockFromFilename(file) {
  if (!file) return;

  try {
    const { data } = await fetchJson('api/check-stock-from-filename.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ filename: file.name })
    });

    if (!data.ok) return;

    renderStockLinks({
      linked: data.linked || [],
      missing: data.missing || []
    });
  } catch (e) {}
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

    if (data.image_path) {
      document.getElementById('editProductPreviewImage').src = data.image_path;
    }

    if (data.stock_review) {
      renderStockLinks({
        linked: data.stock_review.linked || [],
        missing: data.stock_review.missing || []
      });
    }

    document.getElementById('editProductImageInput').value = '';
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

    document.getElementById('editProductId').value = '';
    document.getElementById('editProductSlug').value = '';
    document.getElementById('editProductTitle').value = '';
    document.getElementById('editProductDevicesCount').value = '1';
    document.getElementById('editProductDuration').value = '1';
    document.getElementById('editProductDownPayment').value = '0';
    document.getElementById('editProductMonthly').value = '0';
    document.getElementById('editProductAvailable').value = '1';
    document.getElementById('editProductHotOffer').value = '0';
    document.getElementById('editProductPreviewImage').src = '';
    document.getElementById('editProductImageInput').value = '';
    document.getElementById('productStockLinksWrap').innerHTML = `<div class="empty-box">اختر منتجًا أولًا لتحميل الروابط.</div>`;

    productsSetStatus('success', data.message || 'تم حذف المنتج بنجاح.');
    await loadProductsList();
  } catch (e) {
    productsSetStatus('error', e.message || 'حدث خطأ أثناء حذف المنتج.');
  }
}

document.addEventListener('DOMContentLoaded', async function () {
  await loadBootstrapLists();

  document.getElementById('loadProductsBtn')?.addEventListener('click', loadProductsList);
  document.getElementById('saveProductChangesBtn')?.addEventListener('click', saveProductChanges);
  document.getElementById('deleteProductBtn')?.addEventListener('click', deleteCurrentProduct);

  document.getElementById('editProductImageInput')?.addEventListener('change', async function () {
    const file = this.files && this.files[0] ? this.files[0] : null;

    if (file) {
      const reader = new FileReader();
      reader.onload = function (e) {
        document.getElementById('editProductPreviewImage').src = e.target?.result || '';
      };
      reader.readAsDataURL(file);

      await reviewImageStockFromFilename(file);
    }
  });
});
