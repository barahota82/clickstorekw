async function fetchJson(url, options = {}) {
  const res = await fetch(url, {
    credentials: 'same-origin',
    ...options
  });

  return res.json();
}

async function loadCategories() {
  const data = await fetchJson('api/get-categories.php');

  if (!data.ok) return;

  const list = document.getElementById('categoriesList');
  const select = document.getElementById('brandCategory');

  list.innerHTML = '';
  select.innerHTML = '<option value="">Select Category</option>';

  data.categories.forEach(cat => {
    // عرض
    list.innerHTML += `
      <div class="item">
        <strong>${cat.name_en}</strong><br>
        <small>${cat.slug}</small>
      </div>
    `;

    // dropdown
    select.innerHTML += `
      <option value="${cat.id}">
        ${cat.name_en}
      </option>
    `;
  });
}

async function addCategory() {
  const name_en = document.getElementById('catNameEn').value.trim();
  const name_ph = document.getElementById('catNamePh').value.trim();
  const name_hi = document.getElementById('catNameHi').value.trim();

  if (!name_en) {
    alert("Name required");
    return;
  }

  const res = await fetchJson('api/add-category.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({name_en,name_ph,name_hi})
  });

  if (res.ok) {
    alert("Added");
    loadCategories();
  } else {
    alert(res.message);
  }
}

async function addBrand() {
  const category_id = document.getElementById('brandCategory').value;
  const name = document.getElementById('brandName').value.trim();

  if (!category_id || !name) {
    alert("Missing data");
    return;
  }

  const res = await fetchJson('api/add-brand.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({category_id,name})
  });

  if (res.ok) {
    alert("Brand added");
  } else {
    alert(res.message);
  }
}

document.addEventListener('DOMContentLoaded', loadCategories);
