/* ================================
   CART SYSTEM - CLICK COMPANY
   Professional Version 🔥
================================ */

// ===== INIT CART =====
let cart = JSON.parse(localStorage.getItem("cart")) || [];

// إصلاح البيانات القديمة
cart = cart.map(item => ({
  ...item,
  quantity: parseInt(item.quantity) > 0 ? parseInt(item.quantity) : 1
}));

// ===== SAVE =====
function saveCart() {
  localStorage.setItem("cart", JSON.stringify(cart));
}

// ===== UPDATE UI =====
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

// ===== ADD =====
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

// ===== REMOVE =====
function removeFromCart(index) {
  if (!cart[index]) return;

  if ((parseInt(cart[index].quantity) || 1) > 1) {
    cart[index].quantity--;
  } else {
    cart.splice(index, 1);
  }

  updateCartUI();
}

// ===== CLEAR =====
function clearCart() {
  cart = [];
  updateCartUI();
}

// ===== OPEN / CLOSE =====
function openCart() {
  document.getElementById("cartPanel")?.classList.add("open");
  document.getElementById("cartOverlay")?.classList.add("open");
}

function closeCart() {
  document.getElementById("cartPanel")?.classList.remove("open");
  document.getElementById("cartOverlay")?.classList.remove("open");
}

// ===== SETTINGS LOADER (AI READY) =====
async function loadWhatsAppSettings() {
  try {
    const res = await fetch("/settings/whatsapp.md");
    const text = await res.text();

    const data = {};

    text.split("\n").forEach(line => {
      if (line.includes(":")) {
        const [key, ...rest] = line.split(":");
        data[key.trim()] = rest.join(":").trim().replace(/"/g, "");
      }
    });

    return data;
  } catch {
    return {};
  }
}

// ===== BUILD MESSAGE (SMART AI STYLE) =====
function buildOrderMessage(data, lines) {
  let greeting = data.greeting || "Welcome 👋";

  greeting = greeting.replace("{{name}}", data.employee_name || "Sales");

  return `${greeting}

🛒 New Order

${lines.join("\n\n")}

📍 Please confirm availability & total price.`;
}

// ===== SEND ORDER =====
async function sendOrderWhatsApp() {
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
📸 ${imageURL}`;
  });

  const data = await loadWhatsAppSettings();

  const msg = buildOrderMessage(data, lines);

  const encoded = encodeURIComponent(msg);

  const phone = "965" + (data.phone || "67680877");

  window.open(`https://wa.me/${phone}?text=${encoded}`, "_blank");
}

// ===== DIRECT WHATSAPP =====
async function openWhatsAppDirect() {
  const data = await loadWhatsAppSettings();

  let greeting = data.greeting || "Hello 👋";

  greeting = greeting.replace("{{name}}", data.employee_name || "Sales");

  const encoded = encodeURIComponent(greeting);

  const phone = "965" + (data.phone || "67680877");

  window.open(`https://wa.me/${phone}?text=${encoded}`, "_blank");
}

// ===== INIT =====
document.addEventListener("DOMContentLoaded", () => {
  updateCartUI();
});
