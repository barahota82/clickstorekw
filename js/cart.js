/* ================================
   CART SYSTEM - CLICK COMPANY
   Stable & Final Mobile Fix ✅
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

// ===== ADD / REMOVE / OPEN / CLOSE =====
function addToCart(newItem) {
  const existingItem = cart.find(item => item.title === newItem.title);
  if (existingItem) { existingItem.quantity++; } else { cart.push({ ...newItem, quantity: 1 }); }
  updateCartUI();
}
function removeFromCart(index) {
  if (!cart[index]) return;
  if (cart[index].quantity > 1) { cart[index].quantity--; } else { cart.splice(index, 1); }
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

// ===== SETTINGS LOADER (تمت إضافة حماية لمنع خطأ السيرفر) =====
async function loadWhatsAppSettings() {
  try {
    const res = await fetch("/settings/whatsapp.md?v=" + Date.now());
    if (!res.ok) throw new Error(); // إذا لم يجد الملف لا يكمل
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
    // بيانات احتياطية لضمان عمل الزر دائماً
    return { phone: "67680877", employee_name: "Sales", greeting: "Welcome 👋" };
  }
}

// ===== BUILD MESSAGE =====
function buildOrderMessage(data, lines) {
  let greeting = data.greeting || "Welcome 👋";
  greeting = greeting.replace("{{name}}", data.employee_name || "Sales");
  return `${greeting}\n\n🛒 New Order\n\n${lines.join("\n\n")}\n\n📍 Please confirm status.`;
}

// ===== SEND ORDER (الإصلاح الجوهري للموبايل واللاب) =====
async function sendOrderWhatsApp() {
  if (!cart.length) return;
  const baseURL = window.location.origin;
  const lines = cart.map((item, i) => {
    let img = item.image.startsWith("/") ? baseURL + item.image : item.image;
    return `🔹 Product ${i + 1}\n📱 ${item.title} × ${item.quantity}\n💰 ${item.price}\n📆 ${item.months}\n📸 ${img}`;
  });

  const data = await loadWhatsAppSettings();
  const msg = buildOrderMessage(data, lines);
  const phone = "965" + (data.phone || "67680877");
  const finalURL = `https://api.whatsapp.com{phone}&text=${encodeURIComponent(msg)}`;
  
  // الفتح الذكي: نافذة جديدة للكمبيوتر وتحويل مباشر للموبايل
  if (/Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
      window.location.href = finalURL;
  } else {
      window.open(finalURL, "_blank");
  }
}

// ===== DIRECT WHATSAPP =====
async function openWhatsAppDirect() {
  const data = await loadWhatsAppSettings();
  let greeting = data.greeting || "Hello 👋";
  greeting = greeting.replace("{{name}}", data.employee_name || "Sales");
  const phone = "965" + (data.phone || "67680877");
  const finalURL = `https://api.whatsapp.com{phone}&text=${encodeURIComponent(greeting)}`;
  
  if (/Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
      window.location.href = finalURL;
  } else {
      window.open(finalURL, "_blank");
  }
}

// ===== INIT =====
document.addEventListener("DOMContentLoaded", () => {
  updateCartUI();
});
