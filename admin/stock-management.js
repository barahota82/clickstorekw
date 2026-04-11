let STOCK_CATEGORIES = [];
let STOCK_BRANDS = [];
let STOCK_PRODUCTS = [];

function stockSetStatus(type, message) {
  const box = document.getElementById('stockManagementStatus');
  if (!box) return;

  box.className = `status-box show ${type}`;
  box.textContent = message;
}

function stockClearStatus() {
  const box = document.getElementById('stockManagementStatus');
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

function buildCategoryOptions(selectedValue = '', withAll = false) {
  const rows = [];
  rows.push(withAll ? '<option value="">All Categories</option>' : '<option value="">Select Category</option>');

  STOCK_CATEGORIES.forEach(cat => {
    const selected = String(selectedValue) === String(cat.id) ? ' selected' : '';
    rows.push(`<option value="${cat.id}"${selected}>${escapeHtml(cat.name_en || cat.slug)}</option>`);
  });

  return rows.join('');
}

function buildBrandOptions(categoryId = '', selectedValue = '', withAll = false) {
  const rows = [];
  rows.push(withAll ? '<option value="">All Brands</option>' : '<option value="">Select Brand</option>');

  STOCK_BRANDS
    .filter(brand => !categoryId || String(brand.category_id) === String(categoryId))
    .forEach(brand => {
      const selected = String(selectedValue) === String(brand.id) ? ' selected' : '';
      rows.push(`<option value="${brand.id}"${selected}>${escapeHtml(brand.display_name || brand.name)}</option>`);
    });

  return rows.join('');
}

function renderSummary(summary) {
  document.getElementById('stockSummaryCatalog').textContent = String(summary?.catalog_total ?? 0);
  document.getElementById('stockSummaryProducts').textContent = String(summary?.products_total ?? 0);
  document.getElementById('stockSummaryMovements').textContent = String(summary?.movements_total ?? 0);
  document.getElementById('stockSummaryLow').textContent = String(summary?.low_stock_count ?? 0);
}

function renderCatalog(rows) {
  const tbody = document.getElementById('stockCatalogTableBody');
  if (!tbody) return;

  if (!Array.isArray(rows) || rows.length === 0) {
    tbody.innerHTML = `<tr><td colspan="9"><div class="empty-box">لا توجد عناصر مطابقة في stock catalog.</div></td></tr>`;
    return;
  }

  tbody.innerHTML = rows.map((row, index) => `
    <tr>
      <td>${index + 1}</td>
      <td>${escapeHtml(row.title)}</td>
      <td>${escapeHtml(row.category_name || '-')}</td>
      <td>${escapeHtml(row.brand_name || '-')}</td>
      <td>${escapeHtml(row.storage_value || '-')}</td>
      <td>${escapeHtml(row.ram_value || '-')}</td>
      <td>${escapeHtml(row.network_value || '-')}</td>
      <td><span class="badge ${row.is_active ? 'active' : 'inactive'}">${row.is_active ? 'Active' : 'Inactive'}</span></td>
      <td>${escapeHtml(row.updated_at || '-')}</td>
    </tr>
  `).join('');
}

function renderProducts(rows) {
  const tbody = document.getElementById('stockItemsTableBody');
  const productSelect = document.getElementById('stockMovementProduct');
  if (!tbody || !productSelect) return;

  STOCK_PRODUCTS = Array.isArray(rows) ? rows : [];

  productSelect.innerHTML = '<option value="">Select Product</option>' + STOCK_PRODUCTS.map(row => {
    return `<option value="${row.id}" data-reorder="${row.reorder_level}">${escapeHtml(row.title)} — ${escapeHtml(row.sku)}</option>`;
  }).join('');

  if (!STOCK_PRODUCTS.length) {
    tbody.innerHTML = `<tr><td colspan="10"><div class="empty-box">لا توجد منتجات مطابقة للفلاتر الحالية.</div></td></tr>`;
    return;
  }

  tbody.innerHTML = STOCK_PRODUCTS.map((row, index) => {
    const lowStock = Number(row.available_qty || 0) <= Number(row.reorder_level || 0);

    return `
      <tr>
        <td>${index + 1}</td>
        <td><img class="thumb" src="${escapeHtml(row.image_path || '')}" alt=""></td>
        <td>${escapeHtml(row.title)}</td>
        <td>${escapeHtml(row.brand_name || '-')}</td>
        <td>${escapeHtml(row.sku)}</td>
        <td>${Number(row.qty_on_hand || 0)}</td>
        <td>${Number(row.reserved_qty || 0)}</td>
        <td>${Number(row.available_qty || 0)}</td>
        <td>${Number(row.reorder_level || 0)}</td>
        <td>
          ${
            lowStock
              ? '<span class="badge low">Low Stock</span>'
              : `<span class="badge ${row.stock_item_active ? 'active' : 'inactive'}">${row.stock_item_active ? 'OK' : 'No Stock Item'}</span>`
          }
        </td>
      </tr>
    `;
  }).join('');
}

function renderMovements(rows) {
  const tbody = document.getElementById('stockMovementsTableBody');
  if (!tbody) return;

  if (!Array.isArray(rows) || rows.length === 0) {
    tbody.innerHTML = `<tr><td colspan="8"><div class="empty-box">لا توجد حركات مخزن مطابقة.</div></td></tr>`;
    return;
  }

  tbody.innerHTML = rows.map((row, index) => `
    <tr>
      <td>${index + 1}</td>
      <td>${escapeHtml(row.product_title)}</td>
      <td>${escapeHtml(row.product_sku)}</td>
      <td>${escapeHtml(String(row.movement_type || '').toUpperCase())}</td>
      <td>${Number(row.qty || 0)}</td>
      <td>${escapeHtml(row.notes || '-')}</td>
      <td>${escapeHtml(row.created_by_name || '-')}</td>
      <td>${escapeHtml(row.created_at || '-')}</td>
    </tr>
  `).join('');
}

async function loadBootstrapLists() {
  const filterCategory = document.getElementById('stockFilterCategory');
  const filterBrand = document.getElementById('stockFilterBrand');
  const catalogCategory = document.getElementById('stockCatalogCategory');
  const catalogBrand = document.getElementById('stockCatalogBrand');

  const { data: catData } = await fetchJson('api/get-categories.php');
  STOCK_CATEGORIES = Array.isArray(catData.categories) ? catData.categories : [];

  filterCategory.innerHTML = buildCategoryOptions('', true);
  catalogCategory.innerHTML = buildCategoryOptions('', false);

  filterBrand.innerHTML = buildBrandOptions('', '', true);
  catalogBrand.innerHTML = buildBrandOptions('', '', false);

  filterCategory.addEventListener('change', async function () {
    const categoryId = this.value || '';
    filterBrand.innerHTML = buildBrandOptions(categoryId, '', true);
  });

  catalogCategory.addEventListener('change', async function () {
    const categoryId = this.value || '';
    if (!categoryId) {
      catalogBrand.innerHTML = buildBrandOptions('', '', false);
      return;
    }

    const { data } = await fetchJson(`api/get-brands.php?category_id=${encodeURIComponent(categoryId)}`);
    STOCK_BRANDS = Array.isArray(data.brands) ? data.brands : [];
    catalogBrand.innerHTML = buildBrandOptions(categoryId, '', false);
  });

  const firstCategoryId = filterCategory.value || '';
  if (firstCategoryId) {
    filterBrand.innerHTML = buildBrandOptions(firstCategoryId, '', true);
  }
}

async function loadDashboard() {
  const categoryId = document.getElementById('stockFilterCategory')?.value || '';
  const brandId = document.getElementById('stockFilterBrand')?.value || '';
  const search = document.getElementById('stockFilterSearch')?.value.trim() || '';

  const query = new URLSearchParams();
  if (categoryId) query.set('category_id', categoryId);
  if (brandId) query.set('brand_id', brandId);
  if (search) query.set('search', search);

  stockSetStatus('info', 'جاري تحميل شاشة المخزن...');

  try {
    const { data } = await fetchJson(`api/get-stock-dashboard.php?${query.toString()}`);

    if (!data.ok) {
      stockSetStatus('error', data.message || 'Failed to load stock dashboard.');
      return;
    }

    renderSummary(data.summary || {});
    renderCatalog(data.catalog || []);
    renderProducts(data.products || []);
    renderMovements(data.movements || []);
    stockSetStatus('success', 'تم تحميل بيانات المخزن بنجاح.');
  } catch (e) {
    stockSetStatus('error', e.message || 'حدث خطأ أثناء تحميل شاشة المخزن.');
  }
}

async function saveStockCatalogItem() {
  const categoryId = Number(document.getElementById('stockCatalogCategory')?.value || 0);
  const brandId = Number(document.getElementById('stockCatalogBrand')?.value || 0);
  const title = document.getElementById('stockCatalogTitle')?.value.trim() || '';
  const storageValue = document.getElementById('stockCatalogStorage')?.value.trim() || '';
  const ramValue = document.getElementById('stockCatalogRam')?.value.trim() || '';
  const networkValue = document.getElementById('stockCatalogNetwork')?.value.trim() || '';

  if (!categoryId || !brandId || !title) {
    stockSetStatus('error', 'Category و Brand و Title مطلوبة.');
    return;
  }

  stockSetStatus('info', 'جاري حفظ عنصر stock catalog...');

  try {
    const { data } = await fetchJson('api/save-stock-catalog-item.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        category_id: categoryId,
        brand_id: brandId,
        title: title,
        storage_value: storageValue,
        ram_value: ramValue,
        network_value: networkValue
      })
    });

    if (!data.ok) {
      stockSetStatus('error', data.message || 'Failed to save stock catalog item.');
      return;
    }

    stockSetStatus('success', data.message || 'تم حفظ عنصر stock catalog بنجاح.');
    document.getElementById('stockCatalogTitle').value = '';
    document.getElementById('stockCatalogStorage').value = '';
    document.getElementById('stockCatalogRam').value = '';
    document.getElementById('stockCatalogNetwork').value = '';
    await loadDashboard();
  } catch (e) {
    stockSetStatus('error', e.message || 'حدث خطأ أثناء حفظ عنصر stock catalog.');
  }
}

async function saveStockMovement() {
  const productId = Number(document.getElementById('stockMovementProduct')?.value || 0);
  const movementType = document.getElementById('stockMovementType')?.value || 'in';
  const qty = Number(document.getElementById('stockMovementQty')?.value || 0);
  const reorderLevel = Number(document.getElementById('stockMovementReorder')?.value || 0);
  const notes = document.getElementById('stockMovementNotes')?.value.trim() || '';

  if (!productId) {
    stockSetStatus('error', 'اختر Product أولًا.');
    return;
  }

  if (qty < 0) {
    stockSetStatus('error', 'Quantity must be zero or greater.');
    return;
  }

  stockSetStatus('info', 'جاري حفظ حركة المخزن...');

  try {
    const { data } = await fetchJson('api/save-stock-movement.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        product_id: productId,
        movement_type: movementType,
        qty: qty,
        reorder_level: reorderLevel,
        notes: notes
      })
    });

    if (!data.ok) {
      stockSetStatus('error', data.message || 'Failed to save stock movement.');
      return;
    }

    stockSetStatus('success', data.message || 'تم حفظ حركة المخزن بنجاح.');
    document.getElementById('stockMovementQty').value = '0';
    document.getElementById('stockMovementNotes').value = '';
    await loadDashboard();
  } catch (e) {
    stockSetStatus('error', e.message || 'حدث خطأ أثناء حفظ حركة المخزن.');
  }
}

document.addEventListener('DOMContentLoaded', async function () {
  await loadBootstrapLists();
  document.getElementById('loadStockDashboardBtn')?.addEventListener('click', loadDashboard);
  document.getElementById('saveStockCatalogBtn')?.addEventListener('click', saveStockCatalogItem);
  document.getElementById('saveStockMovementBtn')?.addEventListener('click', saveStockMovement);
  await loadDashboard();
});
