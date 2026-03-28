/* ================================
   CART SYSTEM - CLICK COMPANY
   Professional Version 🔥
   (Mobile-Optimized & WhatsApp Fix)
================================ */

// ===== INIT CART =====
let cart = JSON.parse(localStorage.getItem("cart")) || [];

// إصلاح البيانات القديمة وضمان صحة الكميات
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
    // إضافة تيم-ستامب لمنع الكاش عند التحديث من الأدمن
    const res = await fetch("/settings/whatsapp.md?v=" + new Date().getTime());
    const text = await res.text();

    const data = {};

    text.split("\n").forEach(line => {
      if (line.includes(":")) {
        const [key, ...rest] = line.split(":");
        data[key.trim()] = rest.join(":").trim().replace(/"/g, "");
      }
    });

    return data;
  } catch (error) {
    console.error("Error loading WhatsApp settings:", error);
    return {};
  }
}

// ===== BUILD MESSAGE (SMART AI STYLE) =====
function buildOrderMessage(data, lines) {
  let greeting = data.greeting || "Welcome 👋";
  greeting = greeting.replace("{{name}}", data.employee_name || "Sales");

  return `${greeting}

🛒 *New Order*

${lines.join("\n\n")}

📍 *Please confirm availability & total price.*`;
}

/**
 * دالة ذكية لفتح واتساب تدعم الموبايل والكمبيوتر
 * تحل مشكلة حظر النوافذ المنبثقة في متصفحات الموبايل
 */
function triggerWhatsApp(phone, message) {
    const encoded = encodeURIComponent(message);
    const whatsappURL = `https://api.whatsapp.com{phone}&text=${encoded}`;
    
    // استخدام window.open للكمبيوتر و window.location للموبايل
    if (/Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
        window.location.href = whatsappURL;
    } else {
        window.open(whatsappURL, "_blank");
    }
}

// ===== SEND ORDER =====
async function sendOrderWhatsApp() {
  if (!cart.length) return;

  const baseURL = window.location.origin;

  // جلب الإعدادات
  const data = await loadWhatsAppSettings();
  const phone = "965" + (data.phone || "67680877");

  const lines = cart.map((item, i) => {
    let imageURL = item.image || "";

    if (imageURL.startsWith("/")) {
      imageURL = baseURL + imageURL;
    }

    return `🔹 *Product ${i + 1}*
📱 *${item.title || "Product"}* × ${parseInt(item.quantity) || 1}
💰 ${item.price || ""}
📆 ${item.months || ""}
📸 ${imageURL}`;
  });

  const msg = buildOrderMessage(data, lines);

  // تنفيذ الفتح باستخدام الدالة الذكية
  triggerWhatsApp(phone, msg);
}

// ===== DIRECT WHATSAPP =====
async function openWhatsAppDirect() {
  const data = await loadWhatsAppSettings();
  const phone = "965" + (data.phone || "67680877");

  let greeting = data.greeting || "Hello 👋";
  greeting = greeting.replace("{{name}}", data.employee_name || "Sales");

  // تنفيذ الفتح باستخدام الدالة الذكية
  triggerWhatsApp(phone, greeting);
}

// ===== INIT =====
document.addEventListener("DOMContentLoaded", () => {
  updateCartUI();
});
