// ================= CART SYSTEM =================

let cart = JSON.parse(localStorage.getItem("cart")) || [];

function saveCart() {
  localStorage.setItem("cart", JSON.stringify(cart));
}

function updateCartUI() {
  const count = cart.reduce((sum, item) => sum + item.quantity, 0);

  const top = document.getElementById("cart-count-top");
  const floating = document.getElementById("count") || document.getElementById("cart-count-floating");

  if (top) top.innerText = count;
  if (floating) floating.innerText = count;

  const cartItems = document.getElementById("cartItems");

  if (!cart.length) {
    cartItems.innerHTML = '<div class="cart-empty-text">Your cart is empty.</div>';
    return;
  }

  cartItems.innerHTML = cart.map((item, index) => `
    <div class="cart-item">
      <img src="${item.image}" alt="Product">
      <div>
        <div class="cart-item-title">${item.title} × ${item.quantity}</div>
        <div class="cart-item-meta">${item.price}</div>
        <div class="cart-item-meta">${item.months}</div>
      </div>
      <button class="cart-remove" onclick="removeFromCart(${index})">X</button>
    </div>
  `).join("");

  saveCart();
}

function addToCart(newItem) {
  const existing = cart.find(item => item.title === newItem.title);

  if (existing) {
    existing.quantity += 1;
  } else {
    cart.push({ ...newItem, quantity: 1 });
  }

  updateCartUI();
}

function removeFromCart(index) {
  if (cart[index].quantity > 1) {
    cart[index].quantity -= 1;
  } else {
    cart.splice(index, 1);
  }

  updateCartUI();
}

function openCart() {
  document.getElementById("cartPanel").classList.add("open");
  document.getElementById("cartOverlay").classList.add("open");
}

function closeCart() {
  document.getElementById("cartPanel").classList.remove("open");
  document.getElementById("cartOverlay").classList.remove("open");
}

function sendOrderWhatsApp() {
  if (!cart.length) return;

  const baseURL = window.location.origin;

  const lines = cart.map((item, i) => {
    let imageURL = item.image || "";
    if (imageURL.startsWith("/")) imageURL = baseURL + imageURL;

    return `🔹 Product ${i + 1}
📱 ${item.title}
💰 ${item.price}
📆 ${item.months}
📸 ${imageURL}`;
  });

  const msg = `Welcome to Click Company 👋

🛒 New Order

${lines.join("\n\n")}`;

  const encoded = encodeURIComponent(msg);

  window.open(`https://wa.me/96567680877?text=${encoded}`, "_blank");
}

// INIT
updateCartUI();
