let PRODUCT_ORDERING_CATEGORIES = [];
let PRODUCT_ORDERING_BRANDS = [];
let PRODUCT_ORDERING_ROWS = [];

function productOrderingSetStatus(type, message) {
  const box = document.getElementById('productOrderingStatus');
  if (!box) return;

  box.className = `status-box show ${type}`;
  box.textContent = message;
}

function productOrderingClearStatus() {
  const box = document.getElementById('productOrderingStatus');
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

async function loadCategoriesForProductOrdering() {
  const categorySelect = document.getElementById('productOrderingCategory');
  if (!categorySelect) return;

  categorySelect.innerHTML = '<option value="">Loading...</option>';

  try {
    const { data } = await fetchJson('api/get-categories.php');

    if (!data.ok || !Array.isArray(data.categories)) {
      categorySelect.innerHTML = '<option value="">No categories found</option>';
      return;
    }

    PRODUCT_ORDERING_CATEGORIES = data.categories;

    categorySelect.innerHTML = '<option value="">Select Category</option>' +
      data.categories.map(cat => `<option value="${cat.id}">${escapeHtml(cat.name_en || cat.slug)}</option>`).join('');

    await loadBrandsForSelectedCategory();
  } catch (e) {
    categorySelect.innerHTML = '<option value="">Failed to load categories</option>';
  }
}

async function loadBrandsForSelectedCategory() {
  const categoryId = Number(document.getElementById('productOrderingCategory')?.value || 0);
  const brandSelect = document.getElementById('productOrderingBrand');

  if (!brandSelect) return;

  brandSelect.innerHTML = '<option value="">All Brands</option>';

  if (categoryId <= 0) {
    return;
  }

  try {
    const { data } = await fetchJson(`api/get-brands.php?category_id=${encodeURIComponent(categoryId)}`);

    if (!data.ok || !Array.isArray(data.brands)) {
      return;
    }

    PRODUCT_ORDERING_BRANDS = data.brands;

    brandSelect.innerHTML = '<option value="">All Brands</option>' +
      data.brands.map(brand => `<option value="${brand.id}">${escapeHtml(brand.display_name || brand.name)}</option>`).join('');
  } catch (e) {}
}

function renderProductOrderingRows(rows) {
  const tbody = document.getElementById('productOrderingTableBody');
  if (!tbody) return;

  if (!Array.isArray(rows) || rows.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="8">
          <div class="empty-box">لا توجد منتجات مطابقة للفلاتر الحالية.</div>
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = rows.map((row, index) => {
    return `
      <tr>
        <td>${index + 1}</td>
        <td>
          <img class="thumb" src="${escapeHtml(row.image_path || '')}" alt="">
        </td>
        <td>${escapeHtml(row.title)}</td>
        <td>${escapeHtml(row.brand_name)}</td>
        <td>${escapeHtml(row.sku)}</td>
        <td>
          <span class="badge ${row.is_available ? 'active' : 'inactive'}">
            ${row.is_available ? 'Available' : 'Out of Stock'}
          </span>
        </td>
        <td>
          ${row.is_hot_offer ? '<span class="badge hot">Hot Offer</span>' : '-'}
        </td>
        <td>
          <input type="number" min="1" value="${Number(row.product_order || 9999)}" data-product-id="${row.id}" class="product-sort-input">
        </td>
      </tr>
    `;
  }).join('');
}

async function loadProductOrdering() {
  const categoryId = Number(document.getElementById('productOrderingCategory')?.value || 0);
  const brandId = Number(document.getElementById('productOrderingBrand')?.value || 0);

  if (categoryId <= 0) {
    productOrderingSetStatus('error', 'اختر Category أولًا.');
    return;
  }

  productOrderingSetStatus('info', 'جاري تحميل ترتيب المنتجات...');

  try {
    const query = new URLSearchParams();
    query.set('category_id', String(categoryId));
    if (brandId > 0) {
      query.set('brand_id', String(brandId));
    }

    const { data } = await fetchJson(`api/get-product-ordering.php?${query.toString()}`);

    if (!data.ok) {
      productOrderingSetStatus('error', data.message || 'Failed to load product ordering.');
      return;
    }

    PRODUCT_ORDERING_ROWS = Array.isArray(data.products) ? data.products : [];
    renderProductOrderingRows(PRODUCT_ORDERING_ROWS);
    productOrderingSetStatus('success', 'تم تحميل المنتجات بنجاح.');
  } catch (e) {
    productOrderingSetStatus('error', e.message || 'حدث خطأ أثناء تحميل المنتجات.');
  }
}

async function saveProductOrdering() {
  const categoryId = Number(document.getElementById('productOrderingCategory')?.value || 0);
  const brandId = Number(document.getElementById('productOrderingBrand')?.value || 0);

  if (categoryId <= 0) {
    productOrderingSetStatus('error', 'اختر Category أولًا.');
    return;
  }

  const inputs = Array.from(document.querySelectorAll('.product-sort-input'));
  if (!inputs.length) {
    productOrderingSetStatus('error', 'لا توجد منتجات للحفظ.');
    return;
  }

  const items = inputs.map(input => ({
    id: Number(input.dataset.productId || 0),
    product_order: Number(input.value || 9999)
  }));

  productOrderingSetStatus('info', 'جاري حفظ ترتيب المنتجات وتحديث JSON...');

  try {
    const { data } = await fetchJson('api/save-product-ordering.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        category_id: categoryId,
        brand_id: brandId,
        items
      })
    });

    if (!data.ok) {
      productOrderingSetStatus('error', data.message || 'Failed to save product ordering.');
      return;
    }

    productOrderingSetStatus('success', data.message || 'تم حفظ ترتيب المنتجات بنجاح.');
    await loadProductOrdering();
  } catch (e) {
    productOrderingSetStatus('error', e.message || 'حدث خطأ أثناء حفظ ترتيب المنتجات.');
  }
}

document.addEventListener('DOMContentLoaded', function () {
  document.getElementById('productOrderingCategory')?.addEventListener('change', loadBrandsForSelectedCategory);
  document.getElementById('loadProductOrderingBtn')?.addEventListener('click', loadProductOrdering);
  document.getElementById('saveProductOrderingBtn')?.addEventListener('click', saveProductOrdering);
  loadCategoriesForProductOrdering();
});
