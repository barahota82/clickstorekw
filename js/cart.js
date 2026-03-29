/* ================================
   CART SYSTEM - CLICK COMPANY
   FINAL PRO VERSION + UX 🔥
================================ */

// ===== INIT CART =====
let cart = JSON.parse(localStorage.getItem("cart")) || [];

cart = cart.map(item => ({
  ...item,
  quantity: parseInt(item.quantity) > 0 ? parseInt(item.quantity) : 1
}));

// ===== WHATSAPP SETTINGS =====
let whatsappSettings = {
  phone: "67680877",
  employee_name: "Sales",
  greeting: "Hello 👋"
};

// ===== PREVENT DOUBLE CLICK =====
let isSending = false;

// ===== DEVICE DETECTION =====
function isMobile() {
  return /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
}

// ===== NUMBER HELPERS =====
function extractNumber(text) {
  if (!text) return 0;
  const match = String(text).match(/[\d.]+/);
  return match ? parseFloat(match[0]) : 0;
}

function calculateItemTotal(item) {
  const monthly = extractNumber(item.price);
  const months = extractNumber(item.months);
  const downPayment = extractNumber(item.months);
  const quantity = parseInt(item.quantity) || 1;

  return (monthly * months * quantity) + (downPayment * quantity);
}

// ===== LOADING EFFECT =====
function showLoading(btnId, text = "Opening...") {
  const btn = document.getElementById(btnId);
  if (!btn) return;

  if (!btn.dataset.originalText) {
    btn.dataset.originalText = btn.innerText;
  }

  btn.innerText = text;
  btn.style.opacity = "0.7";
  btn.style.pointerEvents = "none";
}

function resetLoading(btnId) {
  const btn = document.getElementById(btnId);
  if (!btn) return;

  if (btn.dataset.originalText) {
    btn.innerText = btn.dataset.originalText;
  }

  btn.style.opacity = "";
  btn.style.pointerEvents = "";
}

// ===== TOAST =====
function ensureToastContainer() {
  let toast = document.getElementById("globalToast");

  if (!toast) {
    toast = document.createElement("div");
    toast.id = "globalToast";
    toast.className = "global-toast";
    document.body.appendChild(toast);
  }

  return toast;
}

function showToast(message) {
  const toast = ensureToastContainer();

  toast.textContent = message;
  toast.classList.remove("show");

  requestAnimationFrame(() => {
    toast.classList.add("show");
  });

  clearTimeout(window.__toastTimer);
  window.__toastTimer = setTimeout(() => {
    toast.classList.remove("show");
  }, 1800);
}

// ===== CART ANIMATION =====
function animateCartBadge() {
  const targets = [
    document.getElementById("cart-count-top"),
    document.getElementById("count"),
    document.getElementById("cart-count-floating")
  ].filter(Boolean);

  targets.forEach(el => {
    el.classList.remove("cart-bump");
    void el.offsetWidth;
    el.classList.add("cart-bump");
  });

  const floatingCart =
    document.querySelector(".floating-cart") ||
    document.querySelector(".cart-btn");

  if (floatingCart) {
    floatingCart.classList.remove("cart-pulse");
    void floatingCart.offsetWidth;
    floatingCart.classList.add("cart-pulse");
  }
}

// ===== OPEN WHATSAPP =====
function openWhatsAppURL(url) {
  if (isMobile()) {
    window.location.href = url;
  } else {
    window.open(url, "_blank");
  }
}

// ===== INIT =====
document.addEventListener("DOMContentLoaded", async () => {
  updateCartUI();
  ensureToastContainer();

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

    whatsappSettings = {
      ...whatsappSettings,
      ...data
    };

  } catch {
    console.log("⚠️ Failed to load WhatsApp settings");
  }
});

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
      let totalCart = 0;

      cartItems.innerHTML = cart.map((item, index) => {
        const itemTotal = calculateItemTotal(item);
        totalCart += itemTotal;

        return `
          <div class="cart-item">
            <img src="${item.image}" alt="${item.title || "Product"}">
            <div>
              <div class="cart-item-title">${item.title || "Product"} × ${parseInt(item.quantity) || 1}</div>
              <div class="cart-item-meta">${item.price || ""}</div>
              <div class="cart-item-meta">${item.months || ""}</div>
              <div class="cart-item-total">Total: ${itemTotal.toFixed(0)} KD</div>
            </div>
            <button class="cart-remove" onclick="removeFromCart(${index})">X</button>
          </div>
        `;
      }).join("") + `
        <div class="cart-summary">
          <span>Total Cart</span>
          <strong>${totalCart.toFixed(0)} KD</strong>
        </div>
      `;
    }
  }

  saveCart();
}

// ===== ADD =====
function addToCart(newItem) {
  const existingItem = cart.find(item =>
    item.title === newItem.title && item.price === newItem.price
  );

  if (existingItem) {
    existingItem.quantity = (parseInt(existingItem.quantity) || 1) + 1;
    showToast("Quantity updated in cart");
  } else {
    cart.push({
      ...newItem,
      quantity: 1
    });
    showToast("Added to cart");
  }

  updateCartUI();
  animateCartBadge();

  if (navigator.vibrate) {
    navigator.vibrate(35);
  }
}

// ===== REMOVE =====
function removeFromCart(index) {
  if (!cart[index]) return;

  if ((parseInt(cart[index].quantity) || 1) > 1) {
    cart[index].quantity--;
    showToast("Quantity decreased");
  } else {
    cart.splice(index, 1);
    showToast("Removed from cart");
  }

  updateCartUI();
}

// ===== CLEAR =====
function clearCart() {
  cart = [];
  updateCartUI();
  showToast("Cart cleared");
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

// ===== BUILD MESSAGE =====
function buildOrderMessage(data, lines) {
  let greeting = data.greeting || "Welcome 👋";

  greeting = greeting.replace("{{name}}", data.employee_name || "Sales");

  return `${greeting}

🛒 New Order - Click Company

${lines.join("\n\n")}

----------------------

📍 Please confirm availability
🚚 Delivery in Kuwait`;
}

// ===== SEND ORDER =====
function sendOrderWhatsApp() {
  if (!cart.length) {
    showToast("Your cart is empty");
    return;
  }

  if (isSending) return;

  isSending = true;
  showLoading("whatsappBtn");

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

  const msg = buildOrderMessage(whatsappSettings, lines);
  const encoded = encodeURIComponent(msg);
  const phone = "965" + (whatsappSettings.phone || "67680877");
  const url = `https://wa.me/${phone}?text=${encoded}`;

  setTimeout(() => {
    openWhatsAppURL(url);
    isSending = false;
    resetLoading("whatsappBtn");
  }, 200);
}

// ===== DIRECT WHATSAPP =====
function openWhatsAppDirect() {
  if (isSending) return;

  isSending = true;
  showLoading("whatsappBtn");

  const phone = "965" + (whatsappSettings.phone || "67680877");

  let message = whatsappSettings.greeting || "Hello 👋";

  message = message.replace(
    "{{name}}",
    whatsappSettings.employee_name || "Sales"
  );

  const encoded = encodeURIComponent(message);
  const url = `https://wa.me/${phone}?text=${encoded}`;

  setTimeout(() => {
    openWhatsAppURL(url);
    isSending = false;
    resetLoading("whatsappBtn");
  }, 200);
}
