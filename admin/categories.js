let CATEGORY_ROWS = [];
let BRAND_ROWS = [];
let SELECTED_CATEGORY_ID = '';

function setStatus(boxId, type, message) {
  const box = document.getElementById(boxId);
  if (!box) return;

  box.className = `status-box show ${type}`;
  box.textContent = message;
}

function clearStatus(boxId) {
  const box = document.getElementById(boxId);
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
  } catch (error) {
    throw new Error(raw || 'Unexpected server response');
  }

  if (!res.ok || !data.ok) {
    throw new Error(data?.message || 'Request failed');
  }

  return data;
}

function buildCategoryOptions(selectedValue = '', includePlaceholder = true) {
  const rows = [];

  if (includePlaceholder) {
    rows.push('<option value="">اختر الفئة</option>');
  }

  CATEGORY_ROWS.forEach(cat => {
    const selected = String(selectedValue) === String(cat.id) ? ' selected' : '';
    const label = cat.name_en || cat.display_name || cat.slug;
    rows.push(`<option value="${cat.id}"${selected}>${escapeHtml(label)}</option>`);
  });

  return rows.join('');
}

function syncSelectedCategorySelect() {
  const select = document.getElementById('brandsCategoryId');
  if (!select) return;

  select.innerHTML = buildCategoryOptions(SELECTED_CATEGORY_ID, true);

  if (SELECTED_CATEGORY_ID) {
    select.value = String(SELECTED_CATEGORY_ID);
  }
}

function renderCategoriesTable() {
  const tbody = document.getElementById('categoriesTableBody');
  if (!tbody) return;

  if (!Array.isArray(CATEGORY_ROWS) || CATEGORY_ROWS.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="10"><div class="empty-box">لا توجد فئات حتى الآن.</div></td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = CATEGORY_ROWS.map((cat, index) => {
    return `
      <tr>
        <td>${index + 1}</td>
        <td class="slug-cell">${escapeHtml(cat.slug)}</td>
        <td><input class="mini-input" id="cat_name_en_${cat.id}" value="${escapeHtml(cat.name_en || '')}"></td>
        <td><input class="mini-input" id="cat_name_ph_${cat.id}" value="${escapeHtml(cat.name_ph || '')}"></td>
        <td><input class="mini-input" id="cat_name_hi_${cat.id}" value="${escapeHtml(cat.name_hi || '')}"></td>
        <td><input class="mini-input" id="cat_sort_order_${cat.id}" type="number" min="1" value="${Number(cat.sort_order || 9999)}"></td>
        <td><input class="mini-input" id="cat_nav_order_${cat.id}" type="number" min="1" value="${Number(cat.nav_order || 9999)}"></td>
        <td>
          <label class="checkbox-wrap">
            <input id="cat_visible_${cat.id}" type="checkbox" ${cat.visible ? 'checked' : ''}>
            <span>${cat.visible ? 'ظاهر' : 'مخفي'}</span>
          </label>
        </td>
        <td>
          <label class="checkbox-wrap">
            <input id="cat_active_${cat.id}" type="checkbox" ${cat.is_active ? 'checked' : ''}>
            <span class="badge ${cat.is_active ? 'active' : 'inactive'}">${cat.is_active ? 'Active' : 'Inactive'}</span>
          </label>
        </td>
        <td>
          <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <button type="button" class="btn-primary" onclick="saveCategory(${cat.id})">حفظ</button>
            <button type="button" class="btn-secondary" onclick="focusCategoryBrands(${cat.id})">البراندات</button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

function renderBrandsTable() {
  const tbody = document.getElementById('brandsTableBody');
  if (!tbody) return;

  if (!SELECTED_CATEGORY_ID) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7"><div class="empty-box">اختر فئة أولًا لعرض البراندات.</div></td>
      </tr>
    `;
    return;
  }

  if (!Array.isArray(BRAND_ROWS) || BRAND_ROWS.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7"><div class="empty-box">لا توجد براندات داخل هذه الفئة حتى الآن.</div></td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = BRAND_ROWS.map((brand, index) => {
    return `
      <tr>
        <td>${index + 1}</td>
        <td><input class="mini-input" id="brand_name_${brand.id}" value="${escapeHtml(brand.name || '')}"></td>
        <td><input class="mini-input" id="brand_display_name_${brand.id}" value="${escapeHtml(brand.display_name || '')}"></td>
        <td class="slug-cell">${escapeHtml(brand.slug || '')}</td>
        <td><input class="mini-input" id="brand_sort_order_${brand.id}" type="number" min="1" value="${Number(brand.sort_order || 9999)}"></td>
        <td>
          <label class="checkbox-wrap">
            <input id="brand_active_${brand.id}" type="checkbox" ${brand.is_active ? 'checked' : ''}>
            <span class="badge ${brand.is_active ? 'active' : 'inactive'}">${brand.is_active ? 'Active' : 'Inactive'}</span>
          </label>
        </td>
        <td>
          <button type="button" class="btn-primary" onclick="saveBrand(${brand.id})">حفظ</button>
        </td>
      </tr>
    `;
  }).join('');
}

async function loadCategories(options = {}) {
  const preserveSelected = options.preserveSelected !== false;

  setStatus('categoriesStatus', 'info', 'جاري تحميل الفئات...');

  try {
    const data = await fetchJson('api/get-categories.php');

    CATEGORY_ROWS = Array.isArray(data.categories) ? data.categories : [];

    if (!preserveSelected || !CATEGORY_ROWS.some(cat => String(cat.id) === String(SELECTED_CATEGORY_ID))) {
      SELECTED_CATEGORY_ID = CATEGORY_ROWS.length ? String(CATEGORY_ROWS[0].id) : '';
    }

    renderCategoriesTable();
    syncSelectedCategorySelect();
    clearStatus('categoriesStatus');

    if (SELECTED_CATEGORY_ID) {
      await loadBrands();
    } else {
      BRAND_ROWS = [];
      renderBrandsTable();
    }
  } catch (error) {
    setStatus('categoriesStatus', 'error', error.message || 'حدث خطأ أثناء تحميل الفئات.');
  }
}

async function loadBrands() {
  const categoryId = String(document.getElementById('brandsCategoryId')?.value || SELECTED_CATEGORY_ID || '').trim();
  SELECTED_CATEGORY_ID = categoryId;
  syncSelectedCategorySelect();

  if (!categoryId) {
    BRAND_ROWS = [];
    renderBrandsTable();
    clearStatus('brandsStatus');
    return;
  }

  setStatus('brandsStatus', 'info', 'جاري تحميل البراندات...');

  try {
    const data = await fetchJson(`api/get-brands.php?category_id=${encodeURIComponent(categoryId)}`);
    BRAND_ROWS = Array.isArray(data.brands) ? data.brands : [];
    renderBrandsTable();
    clearStatus('brandsStatus');
  } catch (error) {
    setStatus('brandsStatus', 'error', error.message || 'حدث خطأ أثناء تحميل البراندات.');
  }
}

function focusCategoryBrands(categoryId) {
  SELECTED_CATEGORY_ID = String(categoryId || '').trim();
  syncSelectedCategorySelect();
  loadBrands();
}

async function addCategory() {
  const nameEn = document.getElementById('addCategoryNameEn')?.value.trim() || '';
  const namePh = document.getElementById('addCategoryNamePh')?.value.trim() || '';
  const nameHi = document.getElementById('addCategoryNameHi')?.value.trim() || '';
  const sortOrder = Number(document.getElementById('addCategorySortOrder')?.value || 9999);
  const navOrder = Number(document.getElementById('addCategoryNavOrder')?.value || 9999);
  const visible = !!document.getElementById('addCategoryVisible')?.checked;
  const isActive = !!document.getElementById('addCategoryActive')?.checked;

  if (!nameEn) {
    setStatus('categoriesStatus', 'error', 'اسم الفئة EN مطلوب.');
    return;
  }

  setStatus('categoriesStatus', 'info', 'جاري إضافة الفئة...');

  try {
    const data = await fetchJson('api/add-category.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        name_en: nameEn,
        name_ph: namePh,
        name_hi: nameHi,
        sort_order: sortOrder,
        nav_order: navOrder,
        visible,
        is_active: isActive
      })
    });

    document.getElementById('addCategoryNameEn').value = '';
    document.getElementById('addCategoryNamePh').value = '';
    document.getElementById('addCategoryNameHi').value = '';
    document.getElementById('addCategorySortOrder').value = '9999';
    document.getElementById('addCategoryNavOrder').value = '9999';
    document.getElementById('addCategoryVisible').checked = true;
    document.getElementById('addCategoryActive').checked = true;

    SELECTED_CATEGORY_ID = String(data.category?.id || '');
    await loadCategories({ preserveSelected: true });
    setStatus('categoriesStatus', 'success', data.message || 'تمت إضافة الفئة بنجاح.');
  } catch (error) {
    setStatus('categoriesStatus', 'error', error.message || 'فشل في إضافة الفئة.');
  }
}

async function saveCategory(id) {
  const payload = {
    id,
    name_en: document.getElementById(`cat_name_en_${id}`)?.value.trim() || '',
    name_ph: document.getElementById(`cat_name_ph_${id}`)?.value.trim() || '',
    name_hi: document.getElementById(`cat_name_hi_${id}`)?.value.trim() || '',
    sort_order: Number(document.getElementById(`cat_sort_order_${id}`)?.value || 9999),
    nav_order: Number(document.getElementById(`cat_nav_order_${id}`)?.value || 9999),
    visible: !!document.getElementById(`cat_visible_${id}`)?.checked,
    is_active: !!document.getElementById(`cat_active_${id}`)?.checked
  };

  if (!payload.name_en) {
    setStatus('categoriesStatus', 'error', 'اسم الفئة EN مطلوب.');
    return;
  }

  setStatus('categoriesStatus', 'info', 'جاري حفظ بيانات الفئة...');

  try {
    const data = await fetchJson('api/update-category.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    SELECTED_CATEGORY_ID = String(id);
    await loadCategories({ preserveSelected: true });
    setStatus('categoriesStatus', 'success', data.message || 'تم حفظ الفئة بنجاح.');
  } catch (error) {
    setStatus('categoriesStatus', 'error', error.message || 'فشل حفظ الفئة.');
  }
}

async function addBrand() {
  const categoryId = String(document.getElementById('brandsCategoryId')?.value || '').trim();
  const name = document.getElementById('addBrandName')?.value.trim() || '';
  const displayName = document.getElementById('addBrandDisplayName')?.value.trim() || '';
  const sortOrder = Number(document.getElementById('addBrandSortOrder')?.value || 9999);
  const isActive = !!document.getElementById('addBrandActive')?.checked;

  if (!categoryId) {
    setStatus('brandsStatus', 'error', 'اختر فئة أولًا.');
    return;
  }

  if (!name) {
    setStatus('brandsStatus', 'error', 'اسم البراند مطلوب.');
    return;
  }

  setStatus('brandsStatus', 'info', 'جاري إضافة البراند...');

  try {
    const data = await fetchJson('api/add-brand.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        category_id: Number(categoryId),
        name,
        display_name: displayName,
        sort_order: sortOrder,
        is_active: isActive
      })
    });

    document.getElementById('addBrandName').value = '';
    document.getElementById('addBrandDisplayName').value = '';
    document.getElementById('addBrandSortOrder').value = '9999';
    document.getElementById('addBrandActive').checked = true;

    SELECTED_CATEGORY_ID = String(categoryId);
    await loadBrands();
    setStatus('brandsStatus', 'success', data.message || 'تمت إضافة البراند بنجاح.');
  } catch (error) {
    setStatus('brandsStatus', 'error', error.message || 'فشل في إضافة البراند.');
  }
}

async function saveBrand(id) {
  const payload = {
    id,
    name: document.getElementById(`brand_name_${id}`)?.value.trim() || '',
    display_name: document.getElementById(`brand_display_name_${id}`)?.value.trim() || '',
    sort_order: Number(document.getElementById(`brand_sort_order_${id}`)?.value || 9999),
    is_active: !!document.getElementById(`brand_active_${id}`)?.checked
  };

  if (!payload.name) {
    setStatus('brandsStatus', 'error', 'اسم البراند مطلوب.');
    return;
  }

  setStatus('brandsStatus', 'info', 'جاري حفظ بيانات البراند...');

  try {
    const data = await fetchJson('api/update-brand.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    await loadBrands();
    setStatus('brandsStatus', 'success', data.message || 'تم حفظ البراند بنجاح.');
  } catch (error) {
    setStatus('brandsStatus', 'error', error.message || 'فشل حفظ البراند.');
  }
}

function bindPageEvents() {
  document.getElementById('reloadCategoriesBtn')?.addEventListener('click', () => {
    loadCategories({ preserveSelected: true });
  });

  document.getElementById('reloadBrandsBtn')?.addEventListener('click', () => {
    loadBrands();
  });

  document.getElementById('addCategoryBtn')?.addEventListener('click', addCategory);
  document.getElementById('addBrandBtn')?.addEventListener('click', addBrand);

  document.getElementById('brandsCategoryId')?.addEventListener('change', function () {
    SELECTED_CATEGORY_ID = String(this.value || '').trim();
    loadBrands();
  });
}

window.saveCategory = saveCategory;
window.focusCategoryBrands = focusCategoryBrands;
window.saveBrand = saveBrand;

window.addEventListener('DOMContentLoaded', async () => {
  bindPageEvents();
  await loadCategories({ preserveSelected: false });
});
