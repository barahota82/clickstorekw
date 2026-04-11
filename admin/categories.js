async function loadCategories() {
    const res = await fetch('/admin/api/get-categories.php');
    const data = await res.json();

    const container = document.getElementById('categories');
    container.innerHTML = '';

    data.forEach(cat => {
        const div = document.createElement('div');
        div.className = 'category';

        div.innerHTML = `
            <input value="${cat.slug}" disabled><br>

            EN: <input id="en_${cat.slug}" value="${cat.name.en}">
            PH: <input id="ph_${cat.slug}" value="${cat.name.ph}">
            HI: <input id="hi_${cat.slug}" value="${cat.name.hi}">

            <br><br>

            <button onclick="updateCategory('${cat.slug}')">Save</button>
        `;

        container.appendChild(div);
    });
}

async function addCategory() {
    const name_en = document.getElementById('name_en').value;
    const name_ph = document.getElementById('name_ph').value;
    const name_hi = document.getElementById('name_hi').value;

    const res = await fetch('/admin/api/add-category.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name_en, name_ph, name_hi })
    });

    await loadCategories();
}

async function updateCategory(slug) {
    const name_en = document.getElementById(`en_${slug}`).value;
    const name_ph = document.getElementById(`ph_${slug}`).value;
    const name_hi = document.getElementById(`hi_${slug}`).value;

    await fetch('/admin/api/update-category.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ slug, name_en, name_ph, name_hi })
    });

    alert('Saved');
}

loadCategories();
