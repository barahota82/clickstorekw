let BRAND_ORDERING_CATEGORIES = [];
let BRAND_ORDERING_ROWS = [];

function brandOrderingSetStatus(type, message) {
  const box = document.getElementById('brandOrderingStatus');
  if (!box) return;

  box.className = `status-box show ${type}`;
  box.textContent = message;
}

function brandOrderingClearStatus() {
  const box = document.getElementById('brandOrderingStatus');
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

async function loadCategoriesForBrandOrdering() {
  const select = document.getElementById('brandOrderingCategory');
  if (!select) return;

  select.innerHTML = '<option value="">Loading...</option>';

  try {
    const { data } = await fetchJson('api/get-categories.php');

    if (!data.ok || !Array.isArray(data.categories)) {
      select.innerHTML = '<option value="">No categories found</option>';
      return;
    }

    BRAND_ORDERING_CATEGORIES = data.categories;

    select.innerHTML = '<option value="">Select Category</option>' +
      data.categories.map(cat => {
        return `<option value="${cat.id}">${escapeHtml(cat.name_en || cat.slug)}</option>`;
      }).join('');
  } catch (e) {
    select.innerHTML = '<option value="">Failed to load categories</option>';
  }
}

function renderBrandOrderingRows(rows) {
  const tbody = document.getElementById('brandOrderingTableBody');
  if (!tbody) return;

  if (!Array.isArray(rows) || rows.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="5">
          <div class="empty-box">لا توجد براندات داخل هذه الفئة.</div>
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = rows.map((row, index) => {
    return `
      <tr>
        <td>${index + 1}</td>
        <td>${escapeHtml(row.display_name || row.name)}</td>
        <td>${escapeHtml(row.slug)}</td>
        <td>
          <span class="badge ${row.is_active ? 'active' : 'inactive'}">
            ${row.is_active ? 'Active' : 'Inactive'}
          </span>
        </td>
        <td>
          <input type="number" min="1" value="${Number(row.sort_order || 9999)}" data-brand-id="${row.id}" class="brand-sort-input">
        </td>
      </tr>
    `;
  }).join('');
}

async function loadBrandOrdering() {
  const categoryId = Number(document.getElementById('brandOrderingCategory')?.value || 0);

  if (categoryId <= 0) {
    brandOrderingSetStatus('error', 'اختر Category أولًا.');
    return;
  }

  brandOrderingSetStatus('info', 'جاري تحميل ترتيب البراندات...');

  try {
    const { data } = await fetchJson(`api/get-brand-ordering.php?category_id=${encodeURIComponent(categoryId)}`);

    if (!data.ok) {
      brandOrderingSetStatus('error', data.message || 'Failed to load brand ordering.');
      return;
    }

    BRAND_ORDERING_ROWS = Array.isArray(data.brands) ? data.brands : [];
    renderBrandOrderingRows(BRAND_ORDERING_ROWS);
    brandOrderingSetStatus('success', 'تم تحميل البراندات بنجاح.');
  } catch (e) {
    brandOrderingSetStatus('error', e.message || 'حدث خطأ أثناء تحميل البراندات.');
  }
}

async function saveBrandOrdering() {
  const categoryId = Number(document.getElementById('brandOrderingCategory')?.value || 0);

  if (categoryId <= 0) {
    brandOrderingSetStatus('error', 'اختر Category أولًا.');
    return;
  }

  const inputs = Array.from(document.querySelectorAll('.brand-sort-input'));
  if (!inputs.length) {
    brandOrderingSetStatus('error', 'لا توجد براندات للحفظ.');
    return;
  }

  const items = inputs.map(input => ({
    id: Number(input.dataset.brandId || 0),
    sort_order: Number(input.value || 9999)
  }));

  brandOrderingSetStatus('info', 'جاري حفظ ترتيب البراندات...');

  try {
    const { data } = await fetchJson('api/save-brand-ordering.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        category_id: categoryId,
        items
      })
    });

    if (!data.ok) {
      brandOrderingSetStatus('error', data.message || 'Failed to save brand ordering.');
      return;
    }

    brandOrderingSetStatus('success', data.message || 'تم حفظ ترتيب البراندات بنجاح.');
    await loadBrandOrdering();
  } catch (e) {
    brandOrderingSetStatus('error', e.message || 'حدث خطأ أثناء حفظ ترتيب البراندات.');
  }
}

document.addEventListener('DOMContentLoaded', function () {
  document.getElementById('loadBrandOrderingBtn')?.addEventListener('click', loadBrandOrdering);
  document.getElementById('saveBrandOrderingBtn')?.addEventListener('click', saveBrandOrdering);
  loadCategoriesForBrandOrdering();
});
