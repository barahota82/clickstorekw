/* ================================
   CART SYSTEM - CLICK COMPANY
   Stable & Final Fix ✅
================================ */

// 1. الإعدادات الافتراضية لضمان عمل الزر حتى لو فشل السيرفر
let config = { phone: "67680877", employee_name: "Sales", greeting: "Welcome 👋" };

// ===== INIT CART =====
let cart = JSON.parse(localStorage.getItem("cart")) || [];
cart = cart.map(item => ({
  ...item,
  quantity: parseInt(item.quantity) > 0 ? parseInt(item.quantity) : 1
}));

// ===== SAVE & UI =====
function saveCart() {
  localStorage.setItem("cart", JSON.stringify(cart));
}

function updateCartUI() {
  const count = cart.reduce((total, item) => total + (parseInt(item.quantity) || 1), 0);
  const elements = {
    top: document.getElementById("cart-count-top"),
    normal: document.getElementById("count"),
    floating: document.getElementById("cart-count-floating"),
    items: document.getElementById("cartItems")
  };

  if (elements.top) elements.top.innerText = count;
  if (elements.normal) elements.normal.innerText = count;
  if (elements.floating) elements.floating.innerText = count;

  if (elements.items) {
    if (!cart.length) {
      elements.items.innerHTML = '<div class="cart-empty-text">Your cart is empty.</div>';
    } else {
      elements.items.innerHTML = cart.map((item, index) => `
        <div class="cart-item">
          <img src="${item.image}" alt="${item.title || "Product"}">
          <div>
            <div class="cart-item-title">${item.title || "Product"} × ${item.quantity}</div>
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

// ===== ACTIONS =====
function addToCart(newItem) {
  const existing = cart.find(item => item.title === newItem.title);
  existing ? existing.quantity++ : cart.push({ ...newItem, quantity: 1 });
  updateCartUI();
}

function removeFromCart(index) {
  if (!cart[index]) return;
  cart[index].quantity > 1 ? cart[index].quantity-- : cart.splice(index, 1);
  updateCartUI();
}

function openCart() {
  document.getElementById("cartPanel")?.classList.add("open");
  document.getElementById("cartOverlay")?.classList.add("open");
}

function closeCart() {
  document.getElementById("cartPanel")?.classList.remove("open");
  document.getElementById("cartOverlay")?.classList.remove("open");
}

// ===== SETTINGS LOADER (صامت ولا يسبب أعطال) =====
async function loadSettings() {
  try {
    const res = await fetch("/settings/whatsapp.md?v=" + Date.now());
    if (res.ok) {
        const text = await res.text();
        const data = {};
        text.split("\n").forEach(line => {
          if (line.includes(":")) {
            const [key, ...rest] = line.split(":");
            data[key.trim()] = rest.join(":").trim().replace(/"/g, "");
          }
        });
        config = data;
    }
  } catch (e) { console.log("Using defaults"); }
}

// ===== WHATSAPP TRIGGER (حل الموبايل + اللاب) =====
function openWA(message) {
    const phone = "965" + (config.phone || "67680877");
    const encoded = encodeURIComponent(message);
    const url = `https://api.whatsapp.com{phone}&text=${encoded}`;
    
    // الفتح المباشر للموبايل والنافذة الجديدة للكمبيوتر
    if (/Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
        window.location.href = url;
    } else {
        window.open(url, "_blank");
    }
}

// ===== SEND ORDER =====
function sendOrderWhatsApp() {
  if (!cart.length) return;
  const baseURL = window.location.origin;
  const lines = cart.map((item, i) => {
    let img = item.image.startsWith("/") ? baseURL + item.image : item.image;
    return `🔹 Product ${i + 1}\n📱 ${item.title} × ${item.quantity}\n💰 ${item.price}\n📆 ${item.months}\n📸 ${img}`;
  });

  let greeting = config.greeting || "Welcome 👋";
  greeting = greeting.replace("{{name}}", config.employee_name || "Sales");

  openWA(`${greeting}\n\n🛒 New Order\n\n${lines.join("\n\n")}\n\n📍 Please confirm status.`);
}

function openWhatsAppDirect() {
  let greeting = config.greeting || "Hello 👋";
  greeting = greeting.replace("{{name}}", config.employee_name || "Sales");
  openWA(greeting);
}

// ===== INIT =====
document.addEventListener("DOMContentLoaded", () => {
  updateCartUI();
  loadSettings(); // جلب الإعدادات في الخلفية
});
