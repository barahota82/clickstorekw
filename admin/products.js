async function loadCategories() {
    const res = await fetch('api/get-categories.php');
    const data = await res.json();

    const select = document.getElementById('category_select');
    select.innerHTML = '';

    data.categories.forEach(c => {
        const option = document.createElement('option');
        option.value = c.id;
        option.textContent = c.name_en;
        select.appendChild(option);
    });

    loadBrands();
    loadProducts();
}

async function loadBrands() {
    const category_id = document.getElementById('category_select').value;

    const res = await fetch(`api/get-brands.php?category_id=${category_id}`);
    const data = await res.json();

    const select = document.getElementById('brand_select');
    select.innerHTML = '';

    data.brands.forEach(b => {
        const option = document.createElement('option');
        option.value = b.id;
        option.textContent = b.name;
        select.appendChild(option);
    });
}

async function loadProducts() {
    const category_id = document.getElementById('category_select').value;

    const res = await fetch(`api/get-products.php?category_id=${category_id}`);
    const data = await res.json();

    const container = document.getElementById('products');
    container.innerHTML = '';

    data.products.forEach(p => {
        const div = document.createElement('div');

        div.innerHTML = `
            <b>${p.title}</b><br>
            ${p.monthly_amount} KD<br>
            <button onclick="editProduct(${p.id})">Edit</button>
            <hr>
        `;

        container.appendChild(div);
    });
}

async function addProduct() {
    const title = document.getElementById('title').value;
    const category_id = document.getElementById('category_select').value;
    const brand_id = document.getElementById('brand_select').value;

    await fetch('api/add-product.php', {
        method: 'POST',
        body: JSON.stringify({
            title,
            category_id,
            brand_id
        })
    });

    loadProducts();
}

loadCategories();
