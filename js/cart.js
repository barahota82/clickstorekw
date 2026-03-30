/* ================================
   CART SYSTEM - CLICK COMPANY
   FINAL PRO VERSION + UX 🔥
================================ */
/* ================================
   CLICK COMPANY - CART SYSTEM
   FINAL PREMIUM SYSTEM
================================ */

// ===== STORAGE KEYS =====
const CART_STORAGE_KEY = "click_company_cart";
const PENDING_STORAGE_KEY = "click_company_pending_orders";

// ===== CART STATE =====
let cart = JSON.parse(localStorage.getItem(CART_STORAGE_KEY) || "[]");

cart = Array.isArray(cart)
  ? cart.map(item => ({
      ...item,
      quantity: parseInt(item.quantity, 10) > 0 ? parseInt(item.quantity, 10) : 1
    }))
  : [];

// ===== WHATSAPP SETTINGS =====
let whatsappSettings = {
  phone: "67680877",
  employee_name: "Sales",
  greeting: "Hello 👋"
};

// ===== SEND LOCK =====
let isSending = false;

// ===== HELPERS =====
function isMobile() {
  return /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
}

function safeString(value) {
  return String(value || "").trim();
}

function extractNumber(value) {
  const raw = safeString(value);
  const match = raw.match(/[\d.]+/);
  return match ? parseFloat(match[0]) : 0;
}

function formatMoneyKD(value) {
  const raw = safeString(value);
  if (!raw) return "";

  if (/kd/i.test(raw)) return raw;

  const number = extractNumber(raw);
  return number ? `${number} KD` : raw;
}

function formatDuration(value) {
  const raw = safeString(value);
  if (!raw) return "";

  if (/month/i.test(raw)) return raw;

  const number = extractNumber(raw);
  return number ? `${number} Months` : raw;
}

function formatDownPayment(value) {
  const raw = safeString(value);
  if (!raw) return "0 KD Down Payment";

  if (/down payment/i.test(raw)) return raw;
  if (/kd/i.test(raw)) return `${raw} Down Payment`;

  const number = extractNumber(raw);
  return number ? `${number} KD Down Payment` : "0 KD Down Payment";
}

function getBaseURL() {
  return window.location.origin;
}

function normalizeImageURL(imagePath) {
  const path = safeString(imagePath);
  if (!path) return "";

  if (path.startsWith("http://") || path.startsWith("https://")) {
    return path;
  }

  if (path.startsWith("/")) {
    return `${getBaseURL()}${path}`;
  }

  return `${getBaseURL()}/${path}`;
}

function escapeHTML(value) {
  return safeString(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function buildOrderReference() {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, "0");
  const day = String(now.getDate()).padStart(2, "0");
  const time = String(now.getHours()).padStart(2, "0") +
               String(now.getMinutes()).padStart(2, "0") +
               String(now.getSeconds()).padStart(2, "0");

  return `CLK-${year}${month}${day}-${time}`;
}

function calculateItemTotal(item) {
  const monthly = extractNumber(item.price);
  const quantity = parseInt(item.quantity, 10) || 1;

  const monthsText = safeString(item.months);
  const monthsMatch = monthsText.match(/(\d+)\s*Months/i);
  const downMatch = monthsText.match(/(\d+(\.\d+)?)\s*KD\s*Down\s*Payment/i);

  const months = monthsMatch ? parseFloat(monthsMatch[1]) : 0;
  const downPayment = downMatch ? parseFloat(downMatch[1]) : 0;

  return (monthly * months * quantity) + (downPayment * quantity);
}

function calculateCartTotal() {
  return cart.reduce((sum, item) => sum + calculateItemTotal(item), 0);
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

  clearTimeout(window.__clickToastTimer);
  window.__clickToastTimer = setTimeout(() => {
    toast.classList.remove("show");
  }, 2200);
}

// ===== BUTTON LOADING =====
function setButtonLoadingState(button, label) {
  if (!button) return;

  if (!button.dataset.originalText) {
    button.dataset.originalText = button.innerHTML;
  }

  button.innerHTML = label;
  button.disabled = true;
  button.style.pointerEvents = "none";
  button.style.opacity = "0.7";
}

function resetButtonLoadingState(button) {
  if (!button) return;

  if (button.dataset.originalText) {
    button.innerHTML = button.dataset.originalText;
  }

  button.disabled = false;
  button.style.pointerEvents = "";
  button.style.opacity = "";
}

function setPrimaryFloatingWhatsAppLoading(label = "Opening...") {
  const button = document.getElementById("whatsappBtn");
  setButtonLoadingState(button, label);
}

function resetPrimaryFloatingWhatsAppLoading() {
  const button = document.getElementById("whatsappBtn");
  resetButtonLoadingState(button);
}

// ===== CART BADGE ANIMATION =====
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

// ===== LOCAL STORAGE =====
function saveCart() {
  localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
}

function getPendingOrders() {
  try {
    const raw = JSON.parse(localStorage.getItem(PENDING_STORAGE_KEY) || "[]");
    return Array.isArray(raw) ? raw : [];
  } catch {
    return [];
  }
}

function savePendingOrders(orders) {
  localStorage.setItem(PENDING_STORAGE_KEY, JSON.stringify(orders));
}

// ===== WHATSAPP SETTINGS LOAD =====
async function loadWhatsAppSettings() {
  try {
    const response = await fetch("/settings/whatsapp.md", { cache: "no-store" });
    if (!response.ok) return;

    const text = await response.text();
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
    console.log("WhatsApp settings file not loaded");
  }
}

// ===== WHATSAPP URL =====
function openWhatsAppURL(url) {
  if (isMobile()) {
    window.location.href = url;
  } else {
    window.open(url, "_blank");
  }
}

// ===== CART OPEN / CLOSE =====
function openCart() {
  const panel = document.getElementById("cartPanel");
  const overlay = document.getElementById("cartOverlay");
  const body = document.body;

  if (panel) panel.classList.add("open");
  if (overlay) overlay.classList.add("open");
  if (body) body.classList.add("cart-open");

  renderPendingNotice();
}

function closeCart() {
  const panel = document.getElementById("cartPanel");
  const overlay = document.getElementById("cartOverlay");
  const body = document.body;

  if (panel) panel.classList.remove("open");
  if (overlay) overlay.classList.remove("open");
  if (body) body.classList.remove("cart-open");
}

// ===== PENDING ORDERS =====
function saveAsPending() {
  if (!cart.length) {
    showToast("Your cart is empty");
    return;
  }

  const pendingOrders = getPendingOrders();
  const pendingOrder = {
    id: buildOrderReference(),
    saved_at: new Date().toISOString(),
    items: cart.map(item => ({ ...item })),
    total: calculateCartTotal()
  };

  pendingOrders.unshift(pendingOrder);
  savePendingOrders(pendingOrders);

  showToast("Order saved as pending");
  renderPendingNotice();
}

function loadLatestPendingOrder() {
  const pendingOrders = getPendingOrders();

  if (!pendingOrders.length) {
    showToast("No pending order found");
    return;
  }

  const latest = pendingOrders[0];
  if (!latest.items || !Array.isArray(latest.items) || !latest.items.length) {
    showToast("Pending order is empty");
    return;
  }

  cart = latest.items.map(item => ({
    ...item,
    quantity: parseInt(item.quantity, 10) > 0 ? parseInt(item.quantity, 10) : 1
  }));

  updateCartUI();
  openCart();
  showToast("Pending order loaded");
}

function clearAllPendingOrders() {
  savePendingOrders([]);
  renderPendingNotice();
  showToast("Pending orders cleared");
}

function renderPendingNotice() {
  const cartFooter = document.querySelector(".cart-footer");
  if (!cartFooter) return;

  let pendingBox = document.getElementById("pendingOrdersNotice");
  const pendingOrders = getPendingOrders();

  if (!pendingBox) {
    pendingBox = document.createElement("div");
    pendingBox.id = "pendingOrdersNotice";
    pendingBox.className = "pending-orders-notice";
    cartFooter.prepend(pendingBox);
  }

  if (!pendingOrders.length) {
    pendingBox.style.display = "none";
    pendingBox.innerHTML = "";
    return;
  }

  const latest = pendingOrders[0];
  const itemCount = Array.isArray(latest.items) ? latest.items.length : 0;

  pendingBox.style.display = "";
  pendingBox.innerHTML = `
    <div class="pending-orders-notice-inner">
      <div class="pending-orders-notice-text">
        You have ${pendingOrders.length} saved pending order${pendingOrders.length > 1 ? "s" : ""}.
      </div>
      <div class="pending-orders-notice-subtext">
        Latest pending contains ${itemCount} item${itemCount > 1 ? "s" : ""}.
      </div>
      <div class="pending-orders-notice-actions">
        <button type="button" class="cart-action-btn cart-action-btn-secondary" onclick="loadLatestPendingOrder()">
          Load Pending
        </button>
        <button type="button" class="cart-action-btn cart-action-btn-ghost" onclick="clearAllPendingOrders()">
          Clear Pending
        </button>
      </div>
    </div>
  `;
}

// ===== MESSAGE BUILDERS =====
function resolveGreeting() {
  let greeting = whatsappSettings.greeting || "Hello 👋";
  greeting = greeting.replace("{{name}}", whatsappSettings.employee_name || "Sales");
  return greeting;
}

function buildProductLines(items) {
  return items.map((item, index) => {
    const title = safeString(item.title) || "Product";
    const quantity = parseInt(item.quantity, 10) || 1;
    const price = safeString(item.price);
    const months = safeString(item.months);
    const imageURL = normalizeImageURL(item.image);

    return [
      `🔹 Product ${index + 1}`,
      `📦 ${title} × ${quantity}`,
      price ? `💰 ${price}` : "",
      months ? `📆 ${months}` : "",
      imageURL ? `📸 ${imageURL}` : ""
    ].filter(Boolean).join("\n");
  });
}

function buildOrderMessage(items) {
  const greeting = resolveGreeting();
  const reference = buildOrderReference();
  const lines = buildProductLines(items);

  return `${greeting}

#ORDER
Order Reference: ${reference}

I want to place a new order:

${lines.join("\n\n")}

----------------------

Please confirm:
- availability
- final total
- delivery time in Kuwait`;
}

function buildInquiryMessage(items) {
  const greeting = resolveGreeting();
  const reference = buildOrderReference();
  const lines = buildProductLines(items);

  return `${greeting}

#INQUIRY
Inquiry Reference: ${reference}

I want to ask about these product(s):

${lines.join("\n\n")}

Please send more details about:
- availability
- specifications
- installment details`;
}

function buildOrderStatusMessage() {
  const greeting = resolveGreeting();

  return `${greeting}

#ORDER_STATUS

I want to check the status of my order.

Please update me about:
- order confirmation
- preparation status
- delivery status`;
}

function buildDirectWhatsAppMessage() {
  return resolveGreeting();
}

// ===== SEND ACTIONS =====
function startSendFlow() {
  if (isSending) return false;
  isSending = true;
  return true;
}

function finishSendFlow() {
  isSending = false;
  resetPrimaryFloatingWhatsAppLoading();

  const footerButtons = document.querySelectorAll(".cart-footer [data-cart-action]");
  footerButtons.forEach(btn => resetButtonLoadingState(btn));
}

function sendMessageToWhatsApp(message) {
  const phone = `965${safeString(whatsappSettings.phone || "67680877")}`;
  const encoded = encodeURIComponent(message);
  const url = `https://wa.me/${phone}?text=${encoded}`;
  openWhatsAppURL(url);
}

function sendOrderWhatsApp(buttonElement = null) {
  if (!cart.length) {
    showToast("Your cart is empty");
    return;
  }

  if (!startSendFlow()) return;

  if (buttonElement) {
    setButtonLoadingState(buttonElement, "Opening...");
  } else {
    setPrimaryFloatingWhatsAppLoading("Opening...");
  }

  const message = buildOrderMessage(cart);

  setTimeout(() => {
    sendMessageToWhatsApp(message);
    finishSendFlow();
  }, 180);
}

function sendInquiryWhatsApp(buttonElement = null) {
  if (!cart.length) {
    showToast("Your cart is empty");
    return;
  }

  if (!startSendFlow()) return;

  if (buttonElement) {
    setButtonLoadingState(buttonElement, "Opening...");
  } else {
    setPrimaryFloatingWhatsAppLoading("Opening...");
  }

  saveAsPending();
  const message = buildInquiryMessage(cart);

  setTimeout(() => {
    sendMessageToWhatsApp(message);
    finishSendFlow();
  }, 180);
}

function trackOrderWhatsApp(buttonElement = null) {
  if (!startSendFlow()) return;

  if (buttonElement) {
    setButtonLoadingState(buttonElement, "Opening...");
  } else {
    setPrimaryFloatingWhatsAppLoading("Opening...");
  }

  const message = buildOrderStatusMessage();

  setTimeout(() => {
    sendMessageToWhatsApp(message);
    finishSendFlow();
  }, 180);
}

function openWhatsAppDirect() {
  if (!startSendFlow()) return;

  setPrimaryFloatingWhatsAppLoading("Opening...");
  const message = buildDirectWhatsAppMessage();

  setTimeout(() => {
    sendMessageToWhatsApp(message);
    finishSendFlow();
  }, 180);
}

// ===== CART ITEMS =====
function addToCart(newItem) {
  const normalizedItem = {
    title: safeString(newItem.title) || "Product",
    price: formatMoneyKD(newItem.price),
    months: safeString(newItem.months),
    image: safeString(newItem.image),
    quantity: 1
  };

  const existingItem = cart.find(item =>
    safeString(item.title) === normalizedItem.title &&
    safeString(item.price) === normalizedItem.price &&
    safeString(item.months) === normalizedItem.months
  );

  if (existingItem) {
    existingItem.quantity = (parseInt(existingItem.quantity, 10) || 1) + 1;
    showToast("Quantity updated in cart");
  } else {
    cart.push(normalizedItem);
    showToast("Added to cart");
  }

  updateCartUI();
  animateCartBadge();

  if (navigator.vibrate) {
    navigator.vibrate(35);
  }
}

function removeFromCart(index) {
  if (!cart[index]) return;

  const quantity = parseInt(cart[index].quantity, 10) || 1;

  if (quantity > 1) {
    cart[index].quantity = quantity - 1;
    showToast("Quantity decreased");
  } else {
    cart.splice(index, 1);
    showToast("Removed from cart");
  }

  updateCartUI();
}

function clearCart() {
  cart = [];
  updateCartUI();
  showToast("Cart cleared");
}

// ===== UI RENDER =====
function getCartItemsCount() {
  return cart.reduce((total, item) => total + (parseInt(item.quantity, 10) || 1), 0);
}

function renderCartActions() {
  const cartFooter = document.querySelector(".cart-footer");
  if (!cartFooter) return;

  let actions = document.getElementById("cartActionsWrap");

  if (!actions) {
    actions = document.createElement("div");
    actions.id = "cartActionsWrap";
    actions.className = "cart-actions-wrap";
    cartFooter.appendChild(actions);
  }

  actions.innerHTML = `
    <div class="cart-actions">
      <button
        type="button"
        class="cart-action-btn cart-action-btn-secondary"
        data-cart-action="pending"
        onclick="saveAsPending()"
      >
        Save as Pending
      </button>

      <button
        type="button"
        class="cart-action-btn cart-action-btn-outline"
        data-cart-action="inquiry"
        onclick="sendInquiryWhatsApp(this)"
      >
        Ask on WhatsApp
      </button>

      <button
        type="button"
        class="cart-action-btn cart-action-btn-primary"
        data-cart-action="order"
        onclick="sendOrderWhatsApp(this)"
      >
        Send Order via WhatsApp
      </button>

      <button
        type="button"
        class="cart-action-btn cart-action-btn-ghost"
        data-cart-action="status"
        onclick="trackOrderWhatsApp(this)"
      >
        Track My Order
      </button>
    </div>
  `;
}

function updateCartUI() {
  const count = getCartItemsCount();
  const totalCart = calculateCartTotal();

  const topCount = document.getElementById("cart-count-top");
  const normalCount = document.getElementById("count");
  const floatingCount = document.getElementById("cart-count-floating");
  const cartItems = document.getElementById("cartItems");

  if (topCount) topCount.innerText = count;
  if (normalCount) normalCount.innerText = count;
  if (floatingCount) floatingCount.innerText = count;

  if (cartItems) {
    if (!cart.length) {
      cartItems.innerHTML = `
        <div class="cart-empty-block">
          <div class="cart-empty-icon">🛒</div>
          <div class="cart-empty-title">Your cart is empty</div>
          <div class="cart-empty-text">Add products to start your order.</div>
        </div>
      `;
    } else {
      cartItems.innerHTML = cart.map((item, index) => {
        const quantity = parseInt(item.quantity, 10) || 1;
        const itemTotal = calculateItemTotal(item);
        const title = escapeHTML(item.title || "Product");
        const price = escapeHTML(item.price || "");
        const months = escapeHTML(item.months || "");
        const image = escapeHTML(item.image || "/images/logo.png");

        return `
          <div class="cart-item">
            <div class="cart-item-image-wrap">
              <img src="${image}" alt="${title}" class="cart-item-image">
            </div>

            <div class="cart-item-content">
              <div class="cart-item-head">
                <div class="cart-item-title">${title}</div>
                <button
                  type="button"
                  class="cart-remove"
                  aria-label="Remove item"
                  onclick="removeFromCart(${index})"
                >
                  ×
                </button>
              </div>

              <div class="cart-item-meta-wrap">
                ${price ? `<div class="cart-item-meta-row">${price}</div>` : ""}
                ${months ? `<div class="cart-item-meta-row">${months}</div>` : ""}
              </div>

              <div class="cart-item-bottom">
                <div class="cart-item-qty">Qty: ${quantity}</div>
                <div class="cart-item-total">Total: ${itemTotal.toFixed(0)} KD</div>
              </div>
            </div>
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
  renderCartActions();
  renderPendingNotice();
}

// ===== KEYBOARD / OVERLAY HELPERS =====
document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    closeCart();
  }
});

// ===== INIT =====
document.addEventListener("DOMContentLoaded", async () => {
  ensureToastContainer();
  await loadWhatsAppSettings();
  updateCartUI();
});
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
  const quantity = parseInt(item.quantity) || 1;

  const monthsMatch = String(item.months || "").match(/(\d+)\s*Months/i);
  const downMatch = String(item.months || "").match(/(\d+)\s*KD\s*Down\s*Payment/i);

  const months = monthsMatch ? parseFloat(monthsMatch[1]) : 0;
  const downPayment = downMatch ? parseFloat(downMatch[1]) : 0;

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
            <div class="cart-item-image-wrap">
              <img src="${item.image}" alt="${item.title || "Product"}">
            </div>

            <div class="cart-item-content">
              <div class="cart-item-top">
                <div class="cart-item-title">${item.title || "Product"}</div>
                <button class="cart-remove" onclick="removeFromCart(${index})" aria-label="Remove item">×</button>
              </div>

              <div class="cart-item-qty">Qty: ${parseInt(item.quantity) || 1}</div>

              <div class="cart-item-meta-row">${item.price || ""}</div>
              <div class="cart-item-meta-row">${item.months || ""}</div>

              <div class="cart-item-total">Total: ${itemTotal.toFixed(0)} KD</div>
            </div>
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
