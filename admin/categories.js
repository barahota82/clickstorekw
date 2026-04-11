async function loadCategories() {
    const res = await fetch('api/get-categories.php');
    const data = await res.json();

    const container = document.getElementById('categories');
    const select = document.getElementById('category_select');

    container.innerHTML = '';
    select.innerHTML = '';

    data.categories.forEach(cat => {

        const div = document.createElement('div');

        div.innerHTML = `
            <b>${cat.slug}</b><br>
            EN: <input id="en_${cat.id}" value="${cat.name_en}">
            <button onclick="saveCategory(${cat.id})">Save</button>
            <hr>
        `;

        container.appendChild(div);

        const option = document.createElement('option');
        option.value = cat.id;
        option.textContent = cat.name_en;
        select.appendChild(option);
    });

    loadBrands();
}

async function addCategory() {
    const name = document.getElementById('cat_name').value;

    await fetch('api/add-category.php', {
        method: 'POST',
        body: JSON.stringify({ name_en: name })
    });

    loadCategories();
}

async function saveCategory(id) {
    const name = document.getElementById(`en_${id}`).value;

    await fetch('api/update-category.php', {
        method: 'POST',
        body: JSON.stringify({
            id: id,
            name_en: name,
            name_ph: name,
            name_hi: name,
            visible: 1,
            nav_order: 1
        })
    });

    alert('Saved');
}

async function loadBrands() {
    const category_id = document.getElementById('category_select').value;

    const res = await fetch(`api/get-brands.php?category_id=${category_id}`);
    const data = await res.json();

    const container = document.getElementById('brands');
    container.innerHTML = '';

    data.brands.forEach(b => {
        const div = document.createElement('div');

        div.innerHTML = `
            ${b.name}
            <button onclick="updateBrand(${b.id})">Edit</button>
        `;

        container.appendChild(div);
    });
}

async function addBrand() {
    const name = document.getElementById('brand_name').value;
    const category_id = document.getElementById('category_select').value;

    await fetch('api/add-brand.php', {
        method: 'POST',
        body: JSON.stringify({
            name: name,
            category_id: category_id
        })
    });

    loadBrands();
}

loadCategories();
