/* ================================
   CART SYSTEM - CLICK COMPANY (FIXED MOBILE VERSION) 🚀
================================ */

let cart = JSON.parse(localStorage.getItem("cart")) || [];
let whatsAppSettings = {}; // تخزين الإعدادات هنا لسرعة الوصول

// ===== 1. تحميل الإعدادات مسبقاً عند فتح الصفحة =====
async function loadWhatsAppSettings() {
  try {
    const res = await fetch("/settings/whatsapp.md");
    const text = await res.text();
    text.split("\n").forEach(line => {
      if (line.includes(":")) {
        const [key, ...rest] = line.split(":");
        whatsAppSettings[key.trim()] = rest.join(":").trim().replace(/"/g, "");
      }
    });
  } catch (e) {
    console.error("WhatsApp settings failed to load", e);
    // إعدادات افتراضية في حال فشل التحميل
    whatsAppSettings = { phone: "67680877", greeting: "Hello 👋" };
  }
}

// ===== 2. تحديث الواجهة =====
function updateCartUI() {
  const count = cart.reduce((total, item) => total + (parseInt(item.quantity) || 1), 0);
  
  const ids = ["cart-count-top", "count", "cart-count-floating"];
  ids.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.innerText = count;
  });

  const cartItems = document.getElementById("cartItems");
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
  localStorage.setItem("cart", JSON.stringify(cart));
}

// ===== 3. إضافة وحذف من السلة =====
function addToCart(newItem) {
  const existingItem = cart.find(item => item.title === newItem.title);
  if (existingItem) {
    existingItem.quantity = (parseInt(existingItem.quantity) || 1) + 1;
  } else {
    cart.push({ ...newItem, quantity: 1 });
  }
  updateCartUI();
}

function removeFromCart(index) {
  if (cart[index].quantity > 1) {
    cart[index].quantity--;
  } else {
    cart.splice(index, 1);
  }
  updateCartUI();
}

// ===== 4. فتح وإغلاق السلة =====
function openCart() {
  document.getElementById("cartPanel")?.classList.add("open");
  document.getElementById("cartOverlay")?.classList.add("open");
}

function closeCart() {
  document.getElementById("cartPanel")?.classList.remove("remove"); // للإحتياط
  document.getElementById("cartPanel")?.classList.remove("open");
  document.getElementById("cartOverlay")?.classList.remove("open");
}

// ===== 5. إرسال الطلب (تم الإصلاح للموبايل) =====
function sendOrderWhatsApp() {
  if (!cart.length) return;

  const baseURL = window.location.origin;
  const lines = cart.map((item, i) => {
    let img = item.image || "";
    if (img.startsWith("/")) img = baseURL + img;
    return `🔹 المنتج ${i + 1}\n📱 ${item.title} × ${item.quantity}\n💰 ${item.price}\n📆 ${item.months}\n📸 ${img}`;
  });

  let greeting = (whatsAppSettings.greeting || "Welcome 👋").replace("{{name}}", whatsAppSettings.employee_name || "Sales");
  const msg = `${greeting}\n\n🛒 New Order:\n\n${lines.join("\n\n")}`;
  const phone = "965" + (whatsAppSettings.phone || "67680877");

  // الفتح المباشر دون انتظار (يحل مشكلة الموبايل)
  window.open(`https://wa.me{phone}?text=${encodeURIComponent(msg)}`, "_blank");
}

// ===== 6. التواصل المباشر (تم الإصلاح للموبايل) =====
function openWhatsAppDirect() {
  let greeting = (whatsAppSettings.greeting || "Hello 👋").replace("{{name}}", whatsAppSettings.employee_name || "Sales");
  const phone = "965" + (whatsAppSettings.phone || "67680877");

  window.open(`https://wa.me{phone}?text=${encodeURIComponent(greeting)}`, "_blank");
}

// ===== 7. تشغيل النظام عند التحميل =====
document.addEventListener("DOMContentLoaded", () => {
  updateCartUI();
  loadWhatsAppSettings(); // تحميل البيانات فوراً لتكون جاهزة عند الضغط
});
