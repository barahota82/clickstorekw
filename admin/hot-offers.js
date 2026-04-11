let HOT_PRODUCTS = [];

async function loadCategories() {
    const res = await fetch('api/get-categories.php');
    const data = await res.json();

    const select = document.getElementById('category_select');
    select.innerHTML = '';

    if (!data.ok || !Array.isArray(data.categories)) {
        return;
    }

    data.categories.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat.id;
        option.textContent = cat.name_en;
        select.appendChild(option);
    });

    loadHotOffers();
}

async function loadHotOffers() {
    const category_id = document.getElementById('category_select').value || '';

    const res = await fetch(`api/get-hot-offers.php?category_id=${encodeURIComponent(category_id)}`);
    const data = await res.json();

    HOT_PRODUCTS = data.ok && Array.isArray(data.products) ? data.products : [];

    const container = document.getElementById('hotOffersList');
    container.innerHTML = '';

    HOT_PRODUCTS.forEach(product => {
        const card = document.createElement('div');
        card.className = 'card';

        const checked = product.is_hot_offer ? 'checked' : '';
        const statusBadge = product.is_hot_offer
            ? '<span class="badge active">Hot Offer Active</span>'
            : '<span class="badge off">Not Hot</span>';

        card.innerHTML = `
            <div class="card-top">
                <img src="${product.image_path || ''}" alt="">
                <div class="card-body">
                    <div><b>${escapeHtml(product.title)}</b></div>
                    <div style="margin:6px 0;">
                        <span class="badge">${escapeHtml(product.brand_name)}</span>
                        <span class="badge">${product.monthly_amount} KD / ${product.duration_months} Months</span>
                        ${statusBadge}
                    </div>

                    <div class="row">
                        <label>
                            <input type="checkbox" ${checked} onchange="toggleHotOffer(${product.id}, this.checked)">
                            Hot Offer
                        </label>

                        <label>
                            Sort Order:
                            <input type="number" id="hot_sort_${product.id}" value="${product.hot_sort_order}">
                        </label>
                    </div>
                </div>
            </div>
        `;

        container.appendChild(card);
    });
}

async function toggleHotOffer(productId, enabled) {
    await fetch('api/toggle-hot-offer.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            product_id: productId,
            enabled: enabled
        })
    });

    loadHotOffers();
}

async function saveHotOrder() {
    const activeProducts = HOT_PRODUCTS.filter(product => {
        const checkbox = document.querySelector(`input[onchange="toggleHotOffer(${product.id}, this.checked)"]`);
        return checkbox ? checkbox.checked : product.is_hot_offer;
    });

    const items = HOT_PRODUCTS
        .filter(product => {
            const checkbox = document.querySelector(`input[onchange="toggleHotOffer(${product.id}, this.checked)"]`);
            return checkbox ? checkbox.checked : product.is_hot_offer;
        })
        .map(product => ({
            product_id: product.id,
            sort_order: parseInt(document.getElementById(`hot_sort_${product.id}`).value || '9999', 10)
        }));

    if (!items.length) {
        alert('No active hot offers selected.');
        return;
    }

    const res = await fetch('api/update-hot-offer-order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ items })
    });

    const data = await res.json();

    if (data.ok) {
        alert('Hot offer order saved.');
        loadHotOffers();
    } else {
        alert(data.message || 'Failed to save hot offer order.');
    }
}

function escapeHtml(value) {
    return String(value || '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

loadCategories();
