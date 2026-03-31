/* =========================================
   CLICK COMPANY - FINAL CART SYSTEM
========================================= */

(function () {
  "use strict";

  const STORAGE_KEYS = {
    cart: "click_company_cart_v4",
    pending: "click_company_pending_v4",
    orders: "click_company_orders_v4",
    user: "click_company_user_v1"
  };

  const DEFAULT_WHATSAPP = {
    phone: "67680877",
    employee_name: "Sales",
    greeting: "Hello 👋"
  };

  let whatsappSettings = { ...DEFAULT_WHATSAPP };
  let activeTab = "cart";

  let cart = safeParse(localStorage.getItem(STORAGE_KEYS.cart), []);
  let pendingCart = safeParse(localStorage.getItem(STORAGE_KEYS.pending), []);
  let orders = safeParse(localStorage.getItem(STORAGE_KEYS.orders), []);

  document.addEventListener("DOMContentLoaded", async function () {
    await loadWhatsAppSettings();
    normalizeAllData();
    ensureGlobalSystems();
    bindGlobalEvents();
    updateAllBadges();
    renderCartSystem();
    updateAuthLabel();
    bindHeaderScrollEffect();
  });

  /* =========================
     BASIC HELPERS
  ========================= */
  function safeParse(value, fallback) {
    try {
      const parsed = JSON.parse(value);
      return Array.isArray(parsed) ? parsed : fallback;
    } catch {
      return fallback;
    }
  }

  function saveAll() {
    localStorage.setItem(STORAGE_KEYS.cart, JSON.stringify(cart));
    localStorage.setItem(STORAGE_KEYS.pending, JSON.stringify(pendingCart));
    localStorage.setItem(STORAGE_KEYS.orders, JSON.stringify(orders));
    updateAllBadges();
    updateAuthLabel();
  }

  function normalizeAllData() {
    cart = cart.map(normalizeItem);
    pendingCart = pendingCart.map(normalizeItem);
    orders = orders.map(normalizeOrder);
    saveAll();
  }

  function normalizeOrder(order) {
    return {
      id: String(order.id || buildOrderId()).trim(),
      date: String(order.date || new Date().toLocaleString()).trim(),
      status: String(order.status || "pending delivery").trim(),
      items: Array.isArray(order.items) ? order.items.map(normalizeItem) : []
    };
  }

  function normalizeItem(item) {
    const normalized = {
      id: String(item.id || buildRandomId()).trim(),
      title: String(item.title || "Offer").trim(),
      image: String(item.image || "/images/logo.png").trim(),
      quantity: Number(item.quantity) > 0 ? Number(item.quantity) : 1,
      checked: typeof item.checked === "boolean" ? item.checked : true,

      price: String(item.price || "").trim(),
      months: String(item.months || "").trim(),

      monthly: String(item.monthly || "").trim(),
      down_payment: String(item.down_payment || "").trim(),
      duration: String(item.duration || "").trim(),
      total_price: String(item.total_price || "").trim(),
      devices_count: String(item.devices_count || "").trim()
    };

    hydrateOfferFields(normalized);
    return normalized;
  }

  function hydrateOfferFields(item) {
    const monthly = item.monthly || extractMonthly(item.price);
    const duration = item.duration || extractDuration(item.months);
    const downPayment = item.down_payment || extractDownPayment(item.months);
    const devicesCount = item.devices_count || inferDevicesCount(item.title);
    const total = item.total_price || calculateOfferTotal(monthly, duration, downPayment, item.quantity);

    item.monthly = normalizeKD(monthly);
    item.duration = normalizeMonths(duration);
    item.down_payment = normalizeDownPayment(downPayment);
    item.devices_count = devicesCount;
    item.total_price = normalizeKD(total);
  }

  function buildRandomId() {
    return "itm_" + Math.random().toString(36).slice(2, 10) + "_" + Date.now().toString(36);
  }

  function buildOrderId() {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, "0");
    const d = String(now.getDate()).padStart(2, "0");
    const h = String(now.getHours()).padStart(2, "0");
    const min = String(now.getMinutes()).padStart(2, "0");
    const s = String(now.getSeconds()).padStart(2, "0");
    return `CLK-${y}${m}${d}-${h}${min}${s}`;
  }

  function getUserData() {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEYS.user) || "null");
    } catch {
      return null;
    }
  }

  function setUserData(data) {
    localStorage.setItem(STORAGE_KEYS.user, JSON.stringify(data));
    updateAuthLabel();
  }

  function clearUserData() {
    localStorage.removeItem(STORAGE_KEYS.user);
    updateAuthLabel();
  }

  function extractNumber(text) {
    const match = String(text || "").match(/[\d.]+/);
    return match ? parseFloat(match[0]) : 0;
  }

  function normalizeKD(value) {
    const raw = String(value || "").trim();
    if (!raw) return "";
    if (/kd/i.test(raw)) return raw;
    const num = extractNumber(raw);
    return num ? `${num} KD` : raw;
  }

  function normalizeMonths(value) {
    const raw = String(value || "").trim();
    if (!raw) return "";
    if (/month/i.test(raw)) return raw;
    const num = extractNumber(raw);
    return num ? `${num} Months` : raw;
  }

  function normalizeDownPayment(value) {
    const raw = String(value || "").trim();
    if (!raw) return "0 KD Down Payment";
    if (/down payment/i.test(raw)) return raw;
    if (/kd/i.test(raw)) return `${raw} Down Payment`;
    const num = extractNumber(raw);
    return num ? `${num} KD Down Payment` : "0 KD Down Payment";
  }

  function extractMonthly(priceText) {
    const raw = String(priceText || "").trim();
    if (!raw) return "";
    const num = extractNumber(raw);
    return num ? `${num} KD` : raw;
  }

  function extractDuration(monthsText) {
    const raw = String(monthsText || "").trim();
    if (!raw) return "";
    const match = raw.match(/(\d+)\s*Months/i);
    if (match) return `${match[1]} Months`;
    const num = extractNumber(raw);
    return num ? `${num} Months` : "";
  }

  function extractDownPayment(monthsText) {
    const raw = String(monthsText || "").trim();
    if (!raw) return "0 KD Down Payment";
    const match = raw.match(/(\d+(\.\d+)?)\s*KD\s*Down\s*Payment/i);
    if (match) return `${match[1]} KD Down Payment`;
    return "0 KD Down Payment";
  }

  function inferDevicesCount(title) {
    const raw = String(title || "").trim();
    if (!raw) return "1";
    const parts = raw.split(/\s*\+\s*|\s*\/\s*/).filter(Boolean);
    return String(parts.length || 1);
  }

  function calculateOfferTotal(monthly, duration, downPayment, quantity) {
    const m = extractNumber(monthly);
    const d = extractNumber(duration);
    const down = extractNumber(downPayment);
    const q = Number(quantity) > 0 ? Number(quantity) : 1;
    const total = (m * d + down) * q;
    return total ? `${total} KD` : "";
  }

  function getOriginImage(src) {
    const path = String(src || "").trim();
    if (!path) return "";
    if (path.startsWith("http://") || path.startsWith("https://")) return path;
    if (path.startsWith("/")) return window.location.origin + path;
    return window.location.origin + "/" + path;
  }

  function escapeHTML(value) {
    return String(value || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  /* =========================
     WHATSAPP SETTINGS
  ========================= */
  async function loadWhatsAppSettings() {
    try {
      const res = await fetch("/settings/whatsapp.md", { cache: "no-store" });
      if (!res.ok) return;

      const text = await res.text();
      const data = {};

      text.split("\n").forEach(line => {
        if (!line.includes(":")) return;
        const [key, ...rest] = line.split(":");
        data[key.trim()] = rest.join(":").trim().replace(/"/g, "");
      });

      whatsappSettings = {
        ...DEFAULT_WHATSAPP,
        ...data
      };
    } catch {
      whatsappSettings = { ...DEFAULT_WHATSAPP };
    }
  }

  function getWhatsappPhone() {
    return "965" + String(whatsappSettings.phone || DEFAULT_WHATSAPP.phone).replace(/\D/g, "");
  }

  function getGreeting() {
    let greeting = whatsappSettings.greeting || DEFAULT_WHATSAPP.greeting;
    greeting = greeting.replace("{{name}}", whatsappSettings.employee_name || DEFAULT_WHATSAPP.employee_name);
    return greeting;
  }

  function openWhatsApp(text) {
    const url = `https://wa.me/${getWhatsappPhone()}?text=${encodeURIComponent(text)}`;
    if (isMobile()) {
      window.location.href = url;
    } else {
      window.open(url, "_blank");
    }
  }

  function isMobile() {
    return /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
  }

  /* =========================
     GLOBAL SYSTEMS
  ========================= */
  function ensureGlobalSystems() {
    ensureCartTabs();
    ensureImageViewer();
    ensureAuthModal();
    ensureMobileAppBar();
  }

  function ensureCartTabs() {
    const panel = document.getElementById("cartPanel");
    const header = panel ? panel.querySelector(".cart-header") : null;
    if (!panel || !header) return;

    if (!document.getElementById("cartTabsWrap")) {
      const tabs = document.createElement("div");
      tabs.id = "cartTabsWrap";
      tabs.className = "cart-tabs-wrap";
      tabs.innerHTML = `
        <div class="cart-tabs">
          <button type="button" class="cart-tab-btn active" data-tab="cart" onclick="switchCartTab('cart')">Cart</button>
          <button type="button" class="cart-tab-btn" data-tab="pending" onclick="switchCartTab('pending')">Pending</button>
          <button type="button" class="cart-tab-btn" data-tab="orders" onclick="switchCartTab('orders')">My Orders</button>
        </div>
      `;
      header.insertAdjacentElement("afterend", tabs);
    }

    const footer = panel.querySelector(".cart-footer");
    if (footer && !document.getElementById("cartFooterDynamic")) {
      footer.innerHTML = `<div id="cartFooterDynamic"></div>`;
    }
  }

  function ensureImageViewer() {
    if (document.getElementById("globalImageViewer")) return;

    const viewer = document.createElement("div");
    viewer.id = "globalImageViewer";
    viewer.className = "global-image-viewer";
    viewer.innerHTML = `
      <button type="button" class="global-image-close" aria-label="Close image">×</button>
      <img src="" alt="Preview" class="global-image-viewer-img">
    `;
    document.body.appendChild(viewer);

    viewer.addEventListener("click", function (e) {
      if (
        e.target === viewer ||
        e.target.classList.contains("global-image-close") ||
        e.target.classList.contains("global-image-viewer-img")
      ) {
        viewer.classList.remove("active");
      }
    });
  }

  function ensureAuthModal() {
    if (document.getElementById("authModalGlobal")) return;

    const modal = document.createElement("div");
    modal.id = "authModalGlobal";
    modal.className = "auth-modal-global";
    modal.innerHTML = `
      <div class="auth-box-global">
        <button type="button" class="auth-close-global" aria-label="Close" onclick="closeAuthModal()">×</button>
        <h3>Welcome 👋</h3>
        <p class="auth-subtitle-global">Choose how you want to register.</p>

        <button type="button" class="auth-option-global" onclick="authWithGmail()">
          Continue with Gmail
        </button>

        <button type="button" class="auth-option-global" onclick="showPhoneRegister()">
          Continue with Phone Number
        </button>

        <div class="auth-phone-box-global" id="authPhoneBoxGlobal">
          <input type="text" id="authPhoneName" placeholder="Your name">
          <input type="text" id="authPhoneNumber" placeholder="Phone number">
          <button type="button" class="auth-submit-global" onclick="submitPhoneRegister()">Continue</button>
        </div>

        <div class="auth-user-box-global" id="authUserBoxGlobal" style="display:none;">
          <div class="auth-user-box-title">You are signed in</div>
          <div class="auth-user-box-name" id="authUserBoxName"></div>
          <button type="button" class="auth-signout-global" onclick="logoutUser()">Sign Out</button>
        </div>
      </div>
    `;
    document.body.appendChild(modal);
  }

  function ensureMobileAppBar() {
    if (document.getElementById("mobileAppBarGlobal")) return;

    const bar = document.createElement("div");
    bar.id = "mobileAppBarGlobal";
    bar.className = "mobile-app-bar-global";
    bar.innerHTML = `
      <button type="button" class="mobile-app-item active" data-mobile-nav="home" onclick="goHomePage()">
        <span class="mobile-app-icon">🏠</span>
        <span class="mobile-app-label">Home</span>
      </button>

      <button type="button" class="mobile-app-item" data-mobile-nav="cart" onclick="openCart()">
        <span class="mobile-app-icon">🛒</span>
        <span class="mobile-app-label">Cart</span>
        <span class="mobile-app-badge" id="cart-count-floating">0</span>
      </button>

      <button type="button" class="mobile-app-item" data-mobile-nav="whatsapp" onclick="openWhatsAppDirect()">
        <span class="mobile-app-icon">💬</span>
        <span class="mobile-app-label">WhatsApp</span>
      </button>

      <button type="button" class="mobile-app-item" data-mobile-nav="auth" onclick="openAuthModal()">
        <span class="mobile-app-icon">👤</span>
        <span class="mobile-app-label" id="mobileAuthLabel">Registration</span>
      </button>
    `;
    document.body.appendChild(bar);
  }

  function bindGlobalEvents() {
    document.addEventListener("click", function (e) {
      const productImage = e.target.closest(".product-image img");
      if (productImage) {
        openImageViewer(productImage.getAttribute("src") || "");
        return;
      }
    });
  }

  function bindHeaderScrollEffect() {
    const onScroll = function () {
      const header = document.querySelector("header");
      if (!header) return;
      if (window.scrollY > 10) {
        header.classList.add("scrolled");
      } else {
        header.classList.remove("scrolled");
      }
    };

    window.addEventListener("scroll", onScroll);
    onScroll();
  }

  /* =========================
     AUTH
  ========================= */
  function updateAuthLabel() {
    const user = getUserData();
    const label = document.getElementById("mobileAuthLabel");

    if (label) {
      label.textContent = user && user.name ? user.name : "Registration";
    }

    const authUserName = document.getElementById("authUserBoxName");
    const authUserBox = document.getElementById("authUserBoxGlobal");
    const phoneBox = document.getElementById("authPhoneBoxGlobal");

    if (authUserBox && phoneBox) {
      if (user && user.name) {
        authUserBox.style.display = "";
        phoneBox.style.display = "none";
        if (authUserName) authUserName.textContent = user.name;
      } else {
        authUserBox.style.display = "none";
      }
    }
  }

  window.openAuthModal = function () {
    const modal = document.getElementById("authModalGlobal");
    const user = getUserData();
    const userBox = document.getElementById("authUserBoxGlobal");
    const phoneBox = document.getElementById("authPhoneBoxGlobal");

    if (!modal) return;

    modal.classList.add("active");

    if (user && user.name) {
      if (userBox) userBox.style.display = "";
      if (phoneBox) phoneBox.style.display = "none";
      updateAuthLabel();
    } else {
      if (userBox) userBox.style.display = "none";
      if (phoneBox) phoneBox.style.display = "none";
    }
  };

  window.closeAuthModal = function () {
    const modal = document.getElementById("authModalGlobal");
    if (modal) modal.classList.remove("active");
  };

  window.showPhoneRegister = function () {
    const box = document.getElementById("authPhoneBoxGlobal");
    const userBox = document.getElementById("authUserBoxGlobal");
    if (userBox) userBox.style.display = "none";
    if (box) box.style.display = "block";
  };

  window.authWithGmail = function () {
    const name = prompt("Enter your name");
    if (!name || !name.trim()) return;

    const email = prompt("Enter your Gmail");
    if (!email || !email.trim()) return;

    setUserData({
      method: "gmail",
      name: name.trim(),
      email: email.trim()
    });

    showToast("Registration completed");
    closeAuthModal();
  };

  window.submitPhoneRegister = function () {
    const nameInput = document.getElementById("authPhoneName");
    const phoneInput = document.getElementById("authPhoneNumber");

    const name = nameInput ? nameInput.value.trim() : "";
    const phone = phoneInput ? phoneInput.value.trim() : "";

    if (!name || !phone) {
      showToast("Please enter name and phone number");
      return;
    }

    setUserData({
      method: "phone",
      name,
      phone
    });

    if (nameInput) nameInput.value = "";
    if (phoneInput) phoneInput.value = "";

    showToast("Registration completed");
    closeAuthModal();
  };

  window.logoutUser = function () {
    clearUserData();
    showToast("Signed out");
    closeAuthModal();
  };

  /* =========================
     IMAGE VIEWER
  ========================= */
  window.openImageViewer = function (src) {
    const viewer = document.getElementById("globalImageViewer");
    const img = viewer ? viewer.querySelector(".global-image-viewer-img") : null;
    if (!viewer || !img) return;

    img.src = src || "";
    viewer.classList.add("active");
  };

  /* =========================
     MOBILE BAR ACTIONS
  ========================= */
  window.goHomePage = function () {
    window.location.href = "index.html";
  };

  window.openWhatsAppDirect = function () {
    const message = `${getGreeting()}`;
    openWhatsApp(message);
  };

  /* =========================
     HEADER / CART BADGES
  ========================= */
  function updateAllBadges() {
    const count = cart.reduce((sum, item) => sum + (Number(item.quantity) || 1), 0);

    const ids = ["cart-count-top", "count", "cart-count-floating"];
    ids.forEach(id => {
      const el = document.getElementById(id);
      if (el) el.textContent = count;
    });
  }

  function animateCartPulse() {
    const buttons = document.querySelectorAll(".floating-cart, .cart-btn");
    buttons.forEach(btn => {
      btn.classList.remove("cart-pulse");
      void btn.offsetWidth;
      btn.classList.add("cart-pulse");
    });
  }

  /* =========================
     OPEN / CLOSE CART
  ========================= */
  window.openCart = function () {
    const panel = document.getElementById("cartPanel");
    const overlay = document.getElementById("cartOverlay");
    if (panel) panel.classList.add("open");
    if (overlay) overlay.classList.add("open");
    document.body.classList.add("cart-open");
    renderCartSystem();
  };

  window.closeCart = function () {
    const panel = document.getElementById("cartPanel");
    const overlay = document.getElementById("cartOverlay");
    if (panel) panel.classList.remove("open");
    if (overlay) overlay.classList.remove("open");
    document.body.classList.remove("cart-open");
  };

  /* =========================
     TAB SWITCH
  ========================= */
  window.switchCartTab = function (tab) {
    activeTab = tab;
    document.querySelectorAll(".cart-tab-btn").forEach(btn => {
      btn.classList.toggle("active", btn.dataset.tab === tab);
    });
    renderCartSystem();
  };

  /* =========================
     ADD TO CART
  ========================= */
  window.addToCart = function (item) {
    const normalized = normalizeItem(item);

    const existing = cart.find(x =>
      x.title === normalized.title &&
      x.image === normalized.image &&
      x.price === normalized.price &&
      x.months === normalized.months
    );

    if (existing) {
      existing.quantity += 1;
      hydrateOfferFields(existing);
    } else {
      normalized.checked = true;
      cart.push(normalized);
    }

    saveAll();
    animateCartPulse();
    renderCartSystem();
    showToast("Added to cart");
  };

  /* =========================
     RENDER SYSTEM
  ========================= */
  function renderCartSystem() {
    const itemsWrap = document.getElementById("cartItems");
    const footer = document.getElementById("cartFooterDynamic");
    if (!itemsWrap || !footer) return;

    if (activeTab === "cart") {
      renderSelectableList(itemsWrap, footer, cart, "cart");
      return;
    }

    if (activeTab === "pending") {
      renderSelectableList(itemsWrap, footer, pendingCart, "pending");
      return;
    }

    renderOrdersList(itemsWrap, footer);
  }

  function renderSelectableList(itemsWrap, footer, list, type) {
    if (!list.length) {
      itemsWrap.innerHTML = `
        <div class="cart-empty-block">
          <div class="cart-empty-icon"></div>
          <div class="cart-empty-title">${type === "cart" ? "Your cart is empty" : "No pending offers"}</div>
          <div class="cart-empty-text">${type === "cart" ? "Add offers to start your order." : "Saved pending offers will appear here."}</div>
        </div>
      `;

      footer.innerHTML = `
        <div class="cart-footer-actions">
          <button type="button" class="cart-action-btn cart-action-btn-danger" onclick="confirmEmptySection('${type}')">
            ${type === "cart" ? "Empty Cart" : "Empty Pending Cart"}
          </button>
        </div>
      `;
      return;
    }

    const allChecked = list.every(item => item.checked);
    const selectedCount = list.filter(item => item.checked).length;

    itemsWrap.innerHTML = `
      <div class="cart-list-tools">
        <label class="select-all-line">
          <input type="checkbox" ${allChecked ? "checked" : ""} onchange="toggleSelectAll('${type}', this.checked)">
          <span>Select All</span>
        </label>

        <div class="selected-counter">${selectedCount} selected</div>
      </div>

      <div class="cart-list-grid">
        ${list.map((item, index) => renderSelectableRow(item, index, type)).join("")}
      </div>
    `;

    footer.innerHTML = type === "cart"
      ? `
        <div class="cart-footer-actions">
          <button type="button" class="cart-action-btn cart-action-btn-primary" onclick="sendSelectedOrder('${type}')">Send Order</button>
          <button type="button" class="cart-action-btn cart-action-btn-secondary" onclick="saveSelectedAsPending()">Save as Pending</button>
          <button type="button" class="cart-action-btn cart-action-btn-danger" onclick="confirmEmptySection('${type}')">Empty Cart</button>
        </div>
      `
      : `
        <div class="cart-footer-actions">
          <button type="button" class="cart-action-btn cart-action-btn-primary" onclick="sendSelectedOrder('${type}')">Send Order</button>
          <button type="button" class="cart-action-btn cart-action-btn-danger" onclick="confirmEmptySection('${type}')">Empty Pending Cart</button>
        </div>
      `;
  }

  function renderSelectableRow(item, index, type) {
    const image = escapeHTML(item.image || "/images/logo.png");
    const title = escapeHTML(item.title || "Offer");
    const quantity = Number(item.quantity) || 1;

    return `
      <div class="cart-item-line">
        <label class="line-check-wrap">
          <input type="checkbox" ${item.checked ? "checked" : ""} onchange="toggleItemSelection('${type}', ${index})">
          <span class="line-check-ui"></span>
        </label>

        <button type="button" class="line-image-btn" onclick="openImageViewer('${image}')">
          <img src="${image}" alt="${title}" class="line-image">
        </button>

        <div class="line-main">
          <div class="line-title">${title}</div>

          <div class="line-qty">
            <button type="button" class="qty-arrow" onclick="decreaseItemQty('${type}', ${index})">−</button>
            <span class="qty-number">${quantity}</span>
            <button type="button" class="qty-arrow" onclick="increaseItemQty('${type}', ${index})">+</button>
          </div>
        </div>
      </div>
    `;
  }

  function renderOrdersList(itemsWrap, footer) {
    if (!orders.length) {
      itemsWrap.innerHTML = `
        <div class="cart-empty-block">
          <div class="cart-empty-icon">📦</div>
          <div class="cart-empty-title">No orders yet</div>
          <div class="cart-empty-text">Your sent orders will appear here.</div>
        </div>
      `;
      footer.innerHTML = "";
      return;
    }

    itemsWrap.innerHTML = `
      <div class="orders-list-wrap">
        ${orders.map((order, index) => renderOrderCard(order, index)).join("")}
      </div>
    `;

    footer.innerHTML = "";
  }

  function renderOrderCard(order, index) {
    const itemNames = (order.items || []).map(item => `<div class="order-offer-name">• ${escapeHTML(item.title)}</div>`).join("");
    const statusClass = getStatusClass(order.status);

    return `
      <div class="order-card-item">
        <div class="order-card-top">
          <div class="order-date">${escapeHTML(order.date)}</div>
          <div class="order-status ${statusClass}">${escapeHTML(formatStatusLabel(order.status))}</div>
        </div>

        <div class="order-offers-list">
          ${itemNames}
        </div>

        <div class="order-card-actions">
          <button type="button" class="order-small-btn order-track-btn" onclick="trackOrder(${index})">Track My Order</button>
          ${String(order.status).toLowerCase() === "cancelled"
            ? `<button type="button" class="order-small-btn order-cancelled-btn" disabled>Order Cancelled</button>`
            : `<button type="button" class="order-small-btn order-cancel-btn" onclick="cancelOrderRequest(${index})">Cancel Order</button>`
          }
        </div>
      </div>
    `;
  }

  function getStatusClass(status) {
    const s = String(status || "").toLowerCase();
    if (s.includes("delivered")) return "status-delivered";
    if (s.includes("cancelled")) return "status-cancelled";
    return "status-pending";
  }

  function formatStatusLabel(status) {
    const s = String(status || "").trim();
    if (!s) return "Pending Delivery";
    return s;
  }

  /* =========================
     CHECKBOX / SELECT
  ========================= */
  window.toggleItemSelection = function (type, index) {
    const list = type === "cart" ? cart : pendingCart;
    if (!list[index]) return;
    list[index].checked = !list[index].checked;
    saveAll();
    renderCartSystem();
  };

  window.toggleSelectAll = function (type, checked) {
    const list = type === "cart" ? cart : pendingCart;
    list.forEach(item => { item.checked = checked; });
    saveAll();
    renderCartSystem();
  };

  /* =========================
     QUANTITY
  ========================= */
  window.increaseItemQty = function (type, index) {
    const list = type === "cart" ? cart : pendingCart;
    if (!list[index]) return;

    list[index].quantity = (Number(list[index].quantity) || 1) + 1;
    hydrateOfferFields(list[index]);
    saveAll();
    renderCartSystem();
  };

  window.decreaseItemQty = function (type, index) {
    const list = type === "cart" ? cart : pendingCart;
    if (!list[index]) return;

    const current = Number(list[index].quantity) || 1;

    if (current <= 1) {
      openConfirmModal({
        title: "Remove Item",
        text: "Are you sure you want to remove this item?",
        confirmText: "Remove",
        danger: true,
        onConfirm: function () {
          list.splice(index, 1);
          saveAll();
          renderCartSystem();
          showToast("Item removed");
        }
      });
      return;
    }

    list[index].quantity = current - 1;
    hydrateOfferFields(list[index]);
    saveAll();
    renderCartSystem();
  };

  /* =========================
     EMPTY
  ========================= */
  window.confirmEmptySection = function (type) {
    openConfirmModal({
      title: type === "cart" ? "Empty Cart" : "Empty Pending Cart",
      text: type === "cart"
        ? "Are you sure you want to empty the cart?"
        : "Are you sure you want to empty the pending cart?",
      confirmText: "Empty",
      danger: true,
      onConfirm: function () {
        if (type === "cart") cart = [];
        if (type === "pending") pendingCart = [];
        saveAll();
        renderCartSystem();
        showToast(type === "cart" ? "Cart emptied" : "Pending cart emptied");
      }
    });
  };

  /* =========================
     PENDING
  ========================= */
  window.saveSelectedAsPending = function () {
    const selected = cart.filter(item => item.checked);

    if (!selected.length) {
      showToast("Please select offers first");
      return;
    }

    selected.forEach(item => {
      pendingCart.push({
        ...item,
        id: buildRandomId(),
        checked: true
      });
    });

    cart = cart.filter(item => !item.checked);

    saveAll();
    renderCartSystem();
    showToast("Selected offers moved to pending");
  };

  /* =========================
     SEND ORDER
  ========================= */
  window.sendSelectedOrder = function (type) {
    const source = type === "cart" ? cart : pendingCart;
    const selected = source.filter(item => item.checked);

    if (!selected.length) {
      showToast("Please select offers first");
      return;
    }

    const orderId = buildOrderId();
    const orderDate = new Date().toLocaleString();

    const order = {
      id: orderId,
      date: orderDate,
      status: "pending delivery",
      items: selected.map(item => ({
        ...item,
        checked: false
      }))
    };

    orders.unshift(order);

    if (type === "cart") {
      cart = cart.filter(item => !item.checked);
    } else {
      pendingCart = pendingCart.filter(item => !item.checked);
    }

    saveAll();
    renderCartSystem();

    const message = buildOrderWhatsappMessage(order);
    openWhatsApp(message);
  };

  function buildOrderWhatsappMessage(order) {
    const greeting = getGreeting();

    const lines = (order.items || []).map((item, idx) => {
      const imageUrl = getOriginImage(item.image);
      return [
        `🔹 Offer ${idx + 1}`,
        `Offer Name: ${item.title}`,
        `Devices in Offer: ${item.devices_count || "1"}`,
        `Quantity: ${item.quantity}`,
        `Down Payment: ${item.down_payment || "0 KD Down Payment"}`,
        `Monthly Installment: ${item.monthly || item.price || ""}`,
        `Months: ${item.duration || ""}`,
        `Total Price: ${item.total_price || ""}`,
        `Image: ${imageUrl}`
      ].join("\n");
    }).join("\n\n");

    return `${greeting}

#ORDER
Order Reference: ${order.id}
Order Date: ${order.date}

${lines}

Please confirm this order and proceed with processing.`;
  }

  /* =========================
     MY ORDERS ACTIONS
  ========================= */
  window.trackOrder = function (index) {
    const order = orders[index];
    if (!order) return;

    const greeting = getGreeting();
    const offers = (order.items || []).map(item => `- ${item.title}`).join("\n");

    const text = `${greeting}

#ORDER_STATUS
Order Reference: ${order.id}
Order Date: ${order.date}

Offers:
${offers}

Please update me with the current status of this order.`;

    openWhatsApp(text);
  };

  window.cancelOrderRequest = function (index) {
    const order = orders[index];
    if (!order) return;

    openConfirmModal({
      title: "Cancel Order",
      text: "Are you sure you want to cancel this order?",
      confirmText: "Cancel Order",
      danger: true,
      onConfirm: function () {
        order.status = "cancelled";
        saveAll();
        renderCartSystem();

        const greeting = getGreeting();
        const offers = (order.items || []).map(item => `- ${item.title}`).join("\n");

        const text = `${greeting}

#CANCEL_ORDER
Order Reference: ${order.id}
Order Date: ${order.date}

Offers:
${offers}

I want to cancel this order.
Please confirm the cancellation.`;

        openWhatsApp(text);
      }
    });
  };

  /* =========================
     CONFIRM MODAL
  ========================= */
  function ensureConfirmModal() {
    if (document.getElementById("globalConfirmModal")) return;

    const modal = document.createElement("div");
    modal.id = "globalConfirmModal";
    modal.className = "global-confirm-modal";
    modal.innerHTML = `
      <div class="global-confirm-box">
        <div class="global-confirm-title" id="globalConfirmTitle">Confirm</div>
        <div class="global-confirm-text" id="globalConfirmText">Are you sure?</div>
        <div class="global-confirm-actions">
          <button type="button" class="confirm-btn confirm-cancel-btn" id="globalConfirmCancel">Cancel</button>
          <button type="button" class="confirm-btn confirm-ok-btn" id="globalConfirmOk">Confirm</button>
        </div>
      </div>
    `;
    document.body.appendChild(modal);
  }

  function openConfirmModal(config) {
    ensureConfirmModal();

    const modal = document.getElementById("globalConfirmModal");
    const title = document.getElementById("globalConfirmTitle");
    const text = document.getElementById("globalConfirmText");
    const cancel = document.getElementById("globalConfirmCancel");
    const ok = document.getElementById("globalConfirmOk");

    if (!modal || !title || !text || !cancel || !ok) return;

    title.textContent = config.title || "Confirm";
    text.textContent = config.text || "Are you sure?";
    ok.textContent = config.confirmText || "Confirm";

    ok.classList.toggle("danger", !!config.danger);

    const close = function () {
      modal.classList.remove("active");
      cancel.onclick = null;
      ok.onclick = null;
    };

    cancel.onclick = close;
    ok.onclick = function () {
      close();
      if (typeof config.onConfirm === "function") {
        config.onConfirm();
      }
    };

    modal.onclick = function (e) {
      if (e.target === modal) close();
    };

    modal.classList.add("active");
  }

  /* =========================
     TOAST
  ========================= */
  function ensureToast() {
    if (document.getElementById("globalToast")) return;
    const toast = document.createElement("div");
    toast.id = "globalToast";
    toast.className = "global-toast";
    document.body.appendChild(toast);
  }

  function showToast(message) {
    ensureToast();
    const toast = document.getElementById("globalToast");
    if (!toast) return;

    toast.textContent = message;
    toast.classList.remove("show");
    void toast.offsetWidth;
    toast.classList.add("show");

    clearTimeout(window.__clickToastTimer);
    window.__clickToastTimer = setTimeout(() => {
      toast.classList.remove("show");
    }, 2200);
  }

  /* =========================
     MISC
  ========================= */
  function renderAuthUserName() {
    const user = getUserData();
    const target = document.getElementById("authUserBoxName");
    if (target) {
      target.textContent = user && user.name ? user.name : "";
    }
  }

  function ensureGlobalConfirm() {
    ensureConfirmModal();
  }

  function renderHelpers() {
    renderAuthUserName();
    ensureGlobalConfirm();
  }

  renderHelpers();

  /* =========================
     PUBLIC OPTIONAL HELPERS
  ========================= */
  window.clearCart = function () {
    cart = [];
    saveAll();
    renderCartSystem();
  };

})();
