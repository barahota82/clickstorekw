/* ==========================================
   COMMUNICATION & CART SYSTEM - CLICK COMPANY
   Integrated Admin-Ready Version 🚀
   Optimized for Mobile (Pre-loading Fix)
========================================== */

// ===== 1. الذاكرة المؤقتة للإعدادات (Global Cache) =====
// هذا المتغير سيخزن بيانات الموظف والرسائل فور فتح الصفحة
let whatsappSystem = {
    config: null,
    isLoaded: false
};

// ===== 2. نظام السلة (Cart Logic) =====
let cart = JSON.parse(localStorage.getItem("cart")) || [];

// تنظيف وتدقيق البيانات لضمان عدم وجود أخطاء في الكميات
cart = cart.map(item => ({
    ...item,
    quantity: parseInt(item.quantity) > 0 ? parseInt(item.quantity) : 1
}));

function saveCart() {
    localStorage.setItem("cart", JSON.stringify(cart));
}

// ===== 3. تحديث الواجهة (UI Updates) =====
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

// ===== 4. وظائف السلة الأساسية =====
function addToCart(newItem) {
    const existing = cart.find(item => item.title === newItem.title);
    existing ? existing.quantity++ : cart.push({ ...newItem, quantity: 1 });
    updateCartUI();
}

function removeFromCart(index) {
    if (!cart[index]) return;
    if (cart[index].quantity > 1) {
        cart[index].quantity--;
    } else {
        cart.splice(index, 1);
    }
    updateCartUI();
}

function openCart() {
    document.getElementById("cartPanel")?.classList.add("open");
    document.getElementById("cartOverlay")?.classList.add("open");
}

function closeCart() {
    document.getElementById("cartPanel")?.classList.remove("remove"); // تأكد من مطابقة الـ CSS لديك (غالباً .classList.remove("open"))
    document.getElementById("cartPanel")?.classList.remove("open");
    document.getElementById("cartOverlay")?.classList.remove("open");
}

// ===== 5. محرك نظام الواتساب الذكي (The Engine) =====

/**
 * التحميل المسبق للإعدادات فور فتح الصفحة
 * يحل مشكلة الحظر في الموبايل بجعل البيانات جاهزة قبل النقر
 */
async function initWhatsAppSystem() {
    try {
        // جلب الإعدادات مع إضافة تيم-ستامب لمنع "كاش" المتصفح
        const res = await fetch("/settings/whatsapp.md?v=" + Date.now());
        const text = await res.text();
        
        const data = {};
        text.split("\n").forEach(line => {
            if (line.includes(":")) {
                const [key, ...rest] = line.split(":");
                data[key.trim()] = rest.join(":").trim().replace(/"/g, "");
            }
        });

        whatsappSystem.config = data;
        whatsappSystem.isLoaded = true;
        
        console.log("WhatsApp System Ready ✅ Connected to: " + data.employee_name);
    } catch (error) {
        console.error("System Error: Using default fallback.");
        // بيانات احتياطية في حال فشل الاتصال بالسيرفر
        whatsappSystem.config = { phone: "67680877", employee_name: "Sales", greeting: "Welcome 👋" };
        whatsappSystem.isLoaded = true;
    }
}

/**
 * معالج فتح الرابط - يضمن تخطي حظر النوافذ المنبثقة
 */
function execWhatsApp(message) {
    if (!whatsappSystem.isLoaded) {
        alert("Loading settings, please try again in a second...");
        return;
    }
    
    const phone = "965" + (whatsappSystem.config.phone || "67680877");
    const encoded = encodeURIComponent(message);
    const url = `https://api.whatsapp.com{phone}&text=${encoded}`;
    
    // في الموبايل نستخدم الفتح المباشر في نفس النافذة لضمان العمل 100%
    if (/Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
        window.location.href = url;
    } else {
        window.open(url, "_blank");
    }
}

// ===== 6. بناء الرسائل والطلبات =====

function sendOrderWhatsApp() {
    if (!cart.length) return;

    const config = whatsappSystem.config;
    const baseURL = window.location.origin;

    const productLines = cart.map((item, i) => {
        let img = item.image.startsWith("/") ? baseURL + item.image : item.image;
        return `🔹 *Item ${i + 1}*\n📱 *${item.title}* × ${item.quantity}\n💰 ${item.price}\n📆 ${item.months}\n📸 ${img}`;
    });

    // الرد الترحيبي الذكي باستخدام البيانات المحملة مسبقاً
    let msg = config.greeting || "Welcome 👋";
    msg = msg.replace("{{name}}", config.employee_name || "Sales");

    const finalMessage = `${msg}\n\n🛒 *New Order Details:*\n\n${productLines.join("\n\n")}\n\n📍 *Please confirm order status.*`;

    execWhatsApp(finalMessage);
}

function openWhatsAppDirect() {
    if (!whatsappSystem.isLoaded) return;
    
    const config = whatsappSystem.config;
    let msg = config.greeting || "Hello!";
    msg = msg.replace("{{name}}", config.employee_name || "Sales");
    
    execWhatsApp(msg);
}

// ===== 7. التشغيل عند تحميل الصفحة =====
document.addEventListener("DOMContentLoaded", () => {
    updateCartUI();
    initWhatsAppSystem(); // جلب رقم الهاتف والرسائل فوراً ليكون الزر جاهزاً
});
