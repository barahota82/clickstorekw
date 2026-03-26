let cart = JSON.parse(localStorage.getItem("cart")) || [];

// تصحيح أي منتجات قديمة أو ناقصة quantity
cart = cart.map(item => ({
  ...item,
  quantity: parseInt(item.quantity) > 0 ? parseInt(item.quantity) : 1
}));

function saveCart() {
  localStorage.setItem("cart", JSON.stringify(cart));
}

function updateCartUI() {
  const count = cart.reduce((total, item) => {
    return total + (parseInt(item.quantity) || 1);
  }, 0);

  const topCount = document.getElementById("cart-count-top");
  const normalCount = document.getElementById("count");
  const floatingCount = document.getElementById("cart-count-floating");
  const cartItems = document.getElementById("cartItems");

  if (topCount) topCount.innerText = count;
  if (normalCount) normalCount.innerText = count;
  if (floatingCount) floatingCount.innerText = count;

  if (cartItems) {
    if (!cart.length) {
      cartItems.innerHTML = '<div class="cart-empty-text">Your cart is empty.</div>';
    } else {
      cartItems.innerHTML = cart.map((item, index) => `
        <div class="cart-item">
          <img src="${item.image}" alt="${item.title || "Product"}">
          <div>
            <div class="cart-item-title">${item.title || "Product"} × ${parseInt(item.quantity) || 1}</div>
            <div class="cart-item-meta">${item.price || ""}</div>
            <div class="cart-item-meta">${item.months || ""}</div>
          </div>
          <button class="cart-remove" onclick="removeFromCart(${index})">X</button>
        </div>
      `).join("");
    }
  }

  saveCart();
}

function addToCart(newItem) {
  const existingItem = cart.find(item => item.title === newItem.title);

  if (existingItem) {
    existingItem.quantity = (parseInt(existingItem.quantity) || 1) + 1;
  } else {
    cart.push({
      ...newItem,
      quantity: 1
    });
  }

  updateCartUI();
}

function removeFromCart(index) {
  if (!cart[index]) return;

  if ((parseInt(cart[index].quantity) || 1) > 1) {
    cart[index].quantity = (parseInt(cart[index].quantity) || 1) - 1;
  } else {
    cart.splice(index, 1);
  }

  updateCartUI();
}

function openCart() {
  const cartPanel = document.getElementById("cartPanel");
  const cartOverlay = document.getElementById("cartOverlay");

  if (cartPanel) cartPanel.classList.add("open");
  if (cartOverlay) cartOverlay.classList.add("open");
}

function closeCart() {
  const cartPanel = document.getElementById("cartPanel");
  const cartOverlay = document.getElementById("cartOverlay");

  if (cartPanel) cartPanel.classList.remove("open");
  if (cartOverlay) cartOverlay.classList.remove("open");
}

function clearCart() {
  cart = [];
  updateCartUI();
}

function sendOrderWhatsApp() {
  if (!cart.length) return;

  const baseURL = window.location.origin;

  const lines = cart.map((item, i) => {
    let imageURL = item.image || "";

    if (imageURL.startsWith("/")) {
      imageURL = baseURL + imageURL;
    }

    return `🔹 Product ${i + 1}
📱 ${item.title || "Product"} × ${parseInt(item.quantity) || 1}
💰 ${item.price || ""}
📆 ${item.months || ""}
📸 View Product Image:
${imageURL}`;
  });

  const msg = `Welcome to Click Company 👋

👤 Shella - Sales Representative

🛒 New Order

${lines.join("\n\n")}

📍 Please confirm availability & total price.`;

  const encoded = encodeURIComponent(msg);

  fetch("/settings/whatsapp.md")
    .then(res => res.text())
    .then(text => {
      const data = {};
      text.split("\n").forEach(line => {
        if (line.includes(":")) {
          const [key, ...rest] = line.split(":");
          data[key.trim()] = rest.join(":").trim().replace(/"/g, "");
        }
      });

      const phone = "965" + (data.phone || "");
      window.open(`https://wa.me/${phone}?text=${encoded}`, "_blank");
    })
    .catch(() => {
      window.open(`https://wa.me/96567680877?text=${encoded}`, "_blank");
    });
}

// تشغيل السلة عند فتح الصفحة
document.addEventListener("DOMContentLoaded", () => {
  updateCartUI();
});
