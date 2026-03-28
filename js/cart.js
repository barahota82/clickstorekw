/* ==========================================
   COMMUNICATION & CART SYSTEM - CLICK COMPANY
   Final Stable Version ✅
   Fix: Server not found & Mobile Blocking
========================================== */

// 1. إعدادات احتياطية فورية (تعمل إذا فشل السيرفر في الرد)
let whatsappSystem = {
    config: { phone: "67680877", employee_name: "Sales", greeting: "Welcome 👋" },
    isLoaded: true 
};

// 2. نظام السلة
let cart = JSON.parse(localStorage.getItem("cart")) || [];
cart = cart.map(item => ({
    ...item,
    quantity: parseInt(item.quantity) > 0 ? parseInt(item.quantity) : 1
}));

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

// 3. محرك الواتساب المطور (بدون توقف)
async function initWhatsAppSystem() {
    try {
        // محاولة جلب الملف من السيرفر
        const response = await fetch("/settings/whatsapp.md?v=" + Date.now());
        if (response.ok) {
            const text = await response.text();
            const data = {};
            text.split("\n").forEach(line => {
                if (line.includes(":")) {
                    const [key, ...rest] = line.split(":");
                    data[key.trim()] = rest.join(":").trim().replace(/"/g, "");
                }
            });
            whatsappSystem.config = data;
            console.log("Settings loaded from server ✅");
        }
    } catch (e) {
        console.warn("Server file not found, using defaults.");
    }
}

function execWhatsApp(message) {
    const phone = "965" + (whatsappSystem.config.phone || "67680877");
    const encoded = encodeURIComponent(message);
    const url = `https://api.whatsapp.com{phone}&text=${encoded}`;
    
    if (/Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
        window.location.href = url;
    } else {
        window.open(url, "_blank");
    }
}

function sendOrderWhatsApp() {
    if (!cart.length) return;
    const baseURL = window.location.origin;
    const productLines = cart.map((item, i) => {
        let img = item.image.startsWith("/") ? baseURL + item.image : item.image;
        return `🔹 *Item ${i + 1}*\n📱 *${item.title}* × ${item.quantity}\n💰 ${item.price}\n📆 ${item.months}\n📸 ${img}`;
    });
    let msg = whatsappSystem.config.greeting || "Welcome 👋";
    msg = msg.replace("{{name}}", whatsappSystem.config.employee_name || "Sales");
    const finalMessage = `${msg}\n\n🛒 *New Order Details:*\n\n${productLines.join("\n\n")}\n\n📍 *Please confirm order status.*`;
    execWhatsApp(finalMessage);
}

function openWhatsAppDirect() {
    let msg = whatsappSystem.config.greeting || "Hello!";
    msg = msg.replace("{{name}}", whatsappSystem.config.employee_name || "Sales");
    execWhatsApp(msg);
}

document.addEventListener("DOMContentLoaded", () => {
    updateCartUI();
    initWhatsAppSystem(); 
});
