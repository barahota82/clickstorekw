(function () {
  "use strict";

  const STORAGE_KEYS = {
    cart: "click_company_cart_v5",
    pending: "click_company_pending_v5",
    orders: "click_company_orders_v5",
    user: "click_company_user_v2"
  };

  const DEFAULT_WHATSAPP = {
    phone: "67680877",
    employee_name: "Sales",
    greeting: "Hello 👋"
  };

  const UI_TRANSLATIONS = {
    en: {
      home: "Home",
      cart: "Cart",
      whatsapp: "WhatsApp",
      login: "Login",
      pending: "Pending",
      my_orders: "My Orders",
      your_cart_empty: "Your cart is empty.",
      no_pending_offers: "No pending offers",
      add_offers_start_order: "Add offers to start your order.",
      saved_pending_here: "Saved pending offers will appear here.",
      empty_cart: "Empty Cart",
      empty_pending_cart: "Empty Pending Cart",
      send_order: "Send Order",
      save_as_pending: "Save as Pending",
      select_all: "Select All",
      selected: "selected",
      no_orders_yet: "No orders yet",
      sent_orders_appear: "Your sent orders will appear here.",
      track_my_order: "Track My Order",
      cancel_order: "Cancel Order",
      order_cancelled: "Order Cancelled",
      order_rejected: "Order Rejected",
      order_delivered: "Order Delivered",
      cancel_by_company_only: "Cancel By Company Only",
      order_locked: "Order Locked",
      remove_item: "Remove Item",
      are_you_sure_remove_item: "Are you sure you want to remove this item?",
      remove: "Remove",
      confirm: "Confirm",
      cancel: "Cancel",
      are_you_sure: "Are you sure?",
      cart_emptied: "Cart emptied",
      pending_cart_emptied: "Pending cart emptied",
      item_removed: "Item removed",
      please_select_offers_first: "Please select offers first",
      selected_offers_moved: "Selected offers moved to pending",
      added_to_cart: "Added to cart",
      signed_out: "Signed out",
      failed_create_order: "Failed to create order",
      order_created_successfully: "Order created successfully",
      order_prepared_successfully: "Order prepared successfully",
      failed_send_order: "Failed to send order",
      failed_cancel_order: "Failed to cancel order",
      order_cancelled_successfully: "Order cancelled successfully",
      cancel_order_title: "Cancel Order",
      cancel_order_confirm_text: "Are you sure you want to cancel this order?",
      cancel_order_confirm_btn: "Cancel Order",
      empty_cart_title: "Empty Cart",
      empty_cart_confirm_text: "Are you sure you want to empty the cart?",
      empty_pending_title: "Empty Pending Cart",
      empty_pending_confirm_text: "Are you sure you want to empty the pending cart?"
    },
    ph: {
      home: "Home",
      cart: "Cart",
      whatsapp: "WhatsApp",
      login: "Login",
      pending: "Pending",
      my_orders: "Aking Orders",
      your_cart_empty: "Walang laman ang cart mo.",
      no_pending_offers: "Walang pending offers",
      add_offers_start_order: "Magdagdag ng offers para magsimula ng order.",
      saved_pending_here: "Lalabas dito ang saved pending offers.",
      empty_cart: "I-empty ang Cart",
      empty_pending_cart: "I-empty ang Pending",
      send_order: "Ipadala ang Order",
      save_as_pending: "I-save bilang Pending",
      select_all: "Piliin Lahat",
      selected: "napili",
      no_orders_yet: "Wala pang orders",
      sent_orders_appear: "Lalabas dito ang mga naipadalang order mo.",
      track_my_order: "I-track ang Order",
      cancel_order: "Kanselahin ang Order",
      order_cancelled: "Nakansela ang Order",
      order_rejected: "Tinanggihan ang Order",
      order_delivered: "Na-deliver ang Order",
      cancel_by_company_only: "Company lang ang puwedeng magkansela",
      order_locked: "Naka-lock ang Order",
      remove_item: "Tanggalin ang Item",
      are_you_sure_remove_item: "Sigurado ka bang gusto mong tanggalin ang item na ito?",
      remove: "Tanggalin",
      confirm: "Kumpirmahin",
      cancel: "Kanselahin",
      are_you_sure: "Sigurado ka ba?",
      cart_emptied: "Na-empty ang cart",
      pending_cart_emptied: "Na-empty ang pending cart",
      item_removed: "Natanggal ang item",
      please_select_offers_first: "Pumili muna ng offers",
      selected_offers_moved: "Nalipat sa pending ang napiling offers",
      added_to_cart: "Naidagdag sa cart",
      signed_out: "Nakapag-sign out",
      failed_create_order: "Hindi nagawa ang order",
      order_created_successfully: "Matagumpay na nagawa ang order",
      order_prepared_successfully: "Handa na ang order",
      failed_send_order: "Hindi naipadala ang order",
      failed_cancel_order: "Hindi nakansela ang order",
      order_cancelled_successfully: "Matagumpay na nakansela ang order",
      cancel_order_title: "Kanselahin ang Order",
      cancel_order_confirm_text: "Sigurado ka bang gusto mong kanselahin ang order na ito?",
      cancel_order_confirm_btn: "Kanselahin ang Order",
      empty_cart_title: "I-empty ang Cart",
      empty_cart_confirm_text: "Sigurado ka bang gusto mong i-empty ang cart?",
      empty_pending_title: "I-empty ang Pending Cart",
      empty_pending_confirm_text: "Sigurado ka bang gusto mong i-empty ang pending cart?"
    },
    hi: {
      home: "Home",
      cart: "Cart",
      whatsapp: "WhatsApp",
      login: "Login",
      pending: "Pending",
      my_orders: "मेरे ऑर्डर",
      your_cart_empty: "आपकी कार्ट खाली है।",
      no_pending_offers: "कोई पेंडिंग ऑफर नहीं है",
      add_offers_start_order: "ऑर्डर शुरू करने के लिए ऑफर जोड़ें।",
      saved_pending_here: "सेव किए गए पेंडिंग ऑफर यहाँ दिखेंगे।",
      empty_cart: "कार्ट खाली करें",
      empty_pending_cart: "पेंडिंग खाली करें",
      send_order: "ऑर्डर भेजें",
      save_as_pending: "पेंडिंग में सेव करें",
      select_all: "सभी चुनें",
      selected: "चयनित",
      no_orders_yet: "अभी तक कोई ऑर्डर नहीं",
      sent_orders_appear: "आपके भेजे गए ऑर्डर यहाँ दिखेंगे।",
      track_my_order: "मेरा ऑर्डर ट्रैक करें",
      cancel_order: "ऑर्डर रद्द करें",
      order_cancelled: "ऑर्डर रद्द हो गया",
      order_rejected: "ऑर्डर अस्वीकृत",
      order_delivered: "ऑर्डर डिलीवर हो गया",
      cancel_by_company_only: "केवल कंपनी रद्द कर सकती है",
      order_locked: "ऑर्डर लॉक है",
      remove_item: "आइटम हटाएँ",
      are_you_sure_remove_item: "क्या आप वाकई इस आइटम को हटाना चाहते हैं?",
      remove: "हटाएँ",
      confirm: "पुष्टि करें",
      cancel: "रद्द करें",
      are_you_sure: "क्या आप सुनिश्चित हैं?",
      cart_emptied: "कार्ट खाली कर दी गई",
      pending_cart_emptied: "पेंडिंग कार्ट खाली कर दी गई",
      item_removed: "आइटम हटा दिया गया",
      please_select_offers_first: "पहले ऑफर चुनें",
      selected_offers_moved: "चयनित ऑफर पेंडिंग में भेजे गए",
      added_to_cart: "कार्ट में जोड़ा गया",
      signed_out: "साइन आउट हो गया",
      failed_create_order: "ऑर्डर नहीं बन सका",
      order_created_successfully: "ऑर्डर सफलतापूर्वक बन गया",
      order_prepared_successfully: "ऑर्डर तैयार हो गया",
      failed_send_order: "ऑर्डर भेजा नहीं जा सका",
      failed_cancel_order: "ऑर्डर रद्द नहीं हुआ",
      order_cancelled_successfully: "ऑर्डर सफलतापूर्वक रद्द हुआ",
      cancel_order_title: "ऑर्डर रद्द करें",
      cancel_order_confirm_text: "क्या आप वाकई इस ऑर्डर को रद्द करना चाहते हैं?",
      cancel_order_confirm_btn: "ऑर्डर रद्द करें",
      empty_cart_title: "कार्ट खाली करें",
      empty_cart_confirm_text: "क्या आप वाकई कार्ट खाली करना चाहते हैं?",
      empty_pending_title: "पेंडिंग कार्ट खाली करें",
      empty_pending_confirm_text: "क्या आप वाकई पेंडिंग कार्ट खाली करना चाहते हैं?"
    }
  };

  let whatsappSettings = { ...DEFAULT_WHATSAPP };
  let activeTab = "cart";
  let customerSession = { logged_in: false, customer: null };

  let cart = safeParse(localStorage.getItem(STORAGE_KEYS.cart), []);
  let pendingCart = safeParse(localStorage.getItem(STORAGE_KEYS.pending), []);
  let orders = safeParse(localStorage.getItem(STORAGE_KEYS.orders), []);

  document.addEventListener("DOMContentLoaded", async function () {
    await loadWhatsAppSettings();
    await syncCustomerSession();

    cart = cart.map(normalizeItem);
    pendingCart = pendingCart.map(normalizeItem);
    orders = orders.map(normalizeOrder);

    ensureGlobalSystems();
    normalizeFloatingCartButton();
    bindGlobalEvents();
    bindHeaderScrollEffect();

    saveAll();
    await syncOrdersFromServer();
    updateAllBadges();
    updateAuthLabel();
    updateLanguageDependentUI();
    renderCartSystem();
  });

  function currentLang() {
    return localStorage.getItem("site_language") || "en";
  }

  function tr(key) {
    const lang = currentLang();
    return (UI_TRANSLATIONS[lang] && UI_TRANSLATIONS[lang][key]) || UI_TRANSLATIONS.en[key] || key;
  }

  window.updateLanguageDependentUI = function () {
    const mobileLanguageSelect = document.getElementById("mobileLanguageSelect");
    if (mobileLanguageSelect) {
      mobileLanguageSelect.value = currentLang();
    }

    const floatingCartLabel = document.querySelector(".floating-cart-label");
    if (floatingCartLabel) floatingCartLabel.textContent = tr("cart");

    const mobileItems = document.querySelectorAll(".mobile-app-item");
    mobileItems.forEach(item => {
      const nav = item.getAttribute("data-mobile-nav");
      const label = item.querySelector(".mobile-app-label");
      if (!label) return;

      if (nav === "home") label.textContent = tr("home");
      if (nav === "cart") label.textContent = tr("cart");
      if (nav === "whatsapp") label.textContent = tr("whatsapp");
      if (nav === "auth") {
        const user = getUserData();
        label.textContent = user && user.email ? (user.full_name || user.email) : tr("login");
      }
    });

    const cartLabel = document.getElementById("cartLabel");
    if (cartLabel) cartLabel.textContent = tr("cart");

    const yourCartTitle = document.getElementById("yourCartTitle");
    if (yourCartTitle) yourCartTitle.textContent = typeof window.t === "function" ? window.t("your_cart") : "Your Cart";

    const sendOrderBtn = document.getElementById("sendOrderBtn");
    if (sendOrderBtn && typeof window.t === "function") {
      sendOrderBtn.textContent = window.t("send_order_whatsapp");
    }

    renderCartSystem();
  };

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

  function normalizeOrder(order) {
    return {
      id: String(order.id || buildOrderId()).trim(),
      db_id: order.db_id ? Number(order.db_id) : null,
      date: String(order.date || new Date().toLocaleString()).trim(),
      status: String(order.status || "Pending Delivery").trim(),
      rejection_reason: String(order.rejection_reason || "").trim(),
      server_order: !!order.server_order,
      is_first_order: !!order.is_first_order,
      has_promotional_gift: !!order.has_promotional_gift,
      gift_label: String(order.gift_label || "").trim(),
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
      devices_count: String(item.devices_count || item.offer_devices_count || "").trim()
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

  function isMobile() {
    return /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
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

  async function fetchJson(url, options = {}) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 10000);

    try {
      const res = await fetch(url, {
        cache: "no-store",
        credentials: "same-origin",
        signal: controller.signal,
        ...options
      });

      const raw = await res.text();

      let data;
      try {
        data = JSON.parse(raw);
      } catch (e) {
        throw new Error(raw || "Unexpected server response.");
      }

      return { res, data };
    } catch (err) {
      if (err.name === "AbortError") {
        throw new Error("Request timeout. Please try again.");
      }
      throw err;
    } finally {
      clearTimeout(timeout);
    }
  }

  async function fetchCustomerSession() {
    try {
      const { data } = await fetchJson("/auth/status.php");
      return data;
    } catch {
      return { logged_in: false, customer: null };
    }
  }

  async function syncCustomerSession() {
    customerSession = await fetchCustomerSession();

    if (customerSession.logged_in && customerSession.customer) {
      setUserData({
        name: customerSession.customer.full_name || customerSession.customer.email || "Customer",
        email: customerSession.customer.email || "",
        full_name: customerSession.customer.full_name || "",
        id: customerSession.customer.id || null,
        method: "email_otp"
      });
    } else {
      clearUserData();
    }
  }

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

  function ensureGlobalSystems() {
    ensureCartTabs();
    ensureImageViewer();
    ensureAuthModal();
    ensureMobileAppBar();
    ensureConfirmModal();
    ensureToast();
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
          <button type="button" class="cart-tab-btn active" data-tab="cart" onclick="switchCartTab('cart')">${tr("cart")}</button>
          <button type="button" class="cart-tab-btn" data-tab="pending" onclick="switchCartTab('pending')">${tr("pending")}</button>
          <button type="button" class="cart-tab-btn" data-tab="orders" onclick="switchCartTab('orders')">${tr("my_orders")}</button>
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
        closeImageViewer();
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
        <span class="mobile-app-label">${tr("home")}</span>
      </button>

      <button type="button" class="mobile-app-item" data-mobile-nav="cart" onclick="openCart()">
        <span class="mobile-app-icon">🛒</span>
        <span class="mobile-app-label">${tr("cart")}</span>
        <span class="mobile-app-badge" id="cart-count-floating">0</span>
      </button>

      <button type="button" class="mobile-app-item" data-mobile-nav="whatsapp" onclick="openWhatsAppDirect()">
        <span class="mobile-app-icon">💬</span>
        <span class="mobile-app-label">${tr("whatsapp")}</span>
      </button>

      <div class="mobile-lang-switcher">
        <select id="mobileLanguageSelect" class="mobile-lang-select" aria-label="Language">
          <option value="en">EN</option>
          <option value="ph">PH</option>
          <option value="hi">HI</option>
        </select>
      </div>

      <button type="button" class="mobile-app-item" data-mobile-nav="auth" onclick="openAuthModal()">
        <span class="mobile-app-icon">👤</span>
        <span class="mobile-app-label" id="mobileAuthLabel">${tr("login")}</span>
      </button>
    `;
    document.body.appendChild(bar);

    const styleId = "mobile-lang-switcher-style";
    if (!document.getElementById(styleId)) {
      const style = document.createElement("style");
      style.id = styleId;
      style.textContent = `
        .mobile-lang-switcher {
          display: flex;
          align-items: center;
          justify-content: center;
          flex: 0 0 auto;
          margin-right: 2px;
        }

        .mobile-lang-select {
          height: 34px;
          min-width: 58px;
          border-radius: 11px;
          border: 1px solid rgba(255,255,255,0.14);
          background: rgba(255,255,255,0.08);
          color: #fff;
          padding: 0 9px;
          font-size: 13px;
          font-weight: 800;
          outline: none;
          cursor: pointer;
        }

        .mobile-lang-select option {
          color: #111827;
          background: #fff;
        }
      `;
      document.head.appendChild(style);
    }

    const mobileLang = document.getElementById("mobileLanguageSelect");
    if (mobileLang) {
      mobileLang.value = currentLang();
      mobileLang.addEventListener("change", function () {
        const lang = this.value || "en";
        localStorage.setItem("site_language", lang);
        updateLanguageDependentUI();
        window.location.reload();
      });
    }
  }

  function ensureConfirmModal() {
    if (document.getElementById("globalConfirmModal")) return;

    const modal = document.createElement("div");
    modal.id = "globalConfirmModal";
    modal.className = "global-confirm-modal";
    modal.innerHTML = `
      <div class="global-confirm-box">
        <div class="global-confirm-title" id="globalConfirmTitle">${tr("confirm")}</div>
        <div class="global-confirm-text" id="globalConfirmText">${tr("are_you_sure")}</div>
        <div class="global-confirm-actions">
          <button type="button" class="confirm-btn confirm-cancel-btn" id="globalConfirmCancel">${tr("cancel")}</button>
          <button type="button" class="confirm-btn confirm-ok-btn" id="globalConfirmOk">${tr("confirm")}</button>
        </div>
      </div>
    `;
    document.body.appendChild(modal);
  }

  function ensureToast() {
    if (document.getElementById("globalToast")) return;

    const toast = document.createElement("div");
    toast.id = "globalToast";
    toast.className = "global-toast";
    document.body.appendChild(toast);
  }

  function normalizeFloatingCartButton() {
    const floatingButtons = document.querySelectorAll(".floating-cart");
    floatingButtons.forEach(button => {
      button.innerHTML = `
        <span class="floating-cart-icon">🛒</span>
        <span class="floating-cart-label">${tr("cart")}</span>
        <span id="count">0</span>
      `;
    });
  }

  function bindGlobalEvents() {
    document.addEventListener("click", function (e) {
      const productImage = e.target.closest(".product-image img");
      if (productImage) {
        openImageViewer(productImage.getAttribute("src") || "");
        return;
      }
    });

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        closeCart();
        closeImageViewer();
        closeAuthModal();
      }
    });

    document.addEventListener("customer-auth-updated", async function () {
      await syncCustomerSession();
      await syncOrdersFromServer();
      renderCartSystem();
      updateAuthLabel();
      updateLanguageDependentUI();
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

  function updateAuthLabel() {
    const user = getUserData();
    const value = user && user.email ? (user.full_name || user.email) : tr("login");

    if (typeof window.setTopAuthLabel === "function") {
      window.setTopAuthLabel(value);
    } else {
      const desktopLabel = document.getElementById("desktopAuthLabel");
      if (desktopLabel) desktopLabel.textContent = value;
    }

    const mobileLabel = document.getElementById("mobileAuthLabel");
    if (mobileLabel) mobileLabel.textContent = value;
  }

  window.openAuthModal = function () {
    const modal = document.getElementById("authModalGlobal");
    if (!modal) return;

    modal.classList.add("active");

    if (typeof window.initAuthModal === "function") {
      window.initAuthModal();
    }

    document.dispatchEvent(new CustomEvent("customer-auth-opened"));
  };

  window.closeAuthModal = function () {
    const modal = document.getElementById("authModalGlobal");
    if (modal) modal.classList.remove("active");
  };

  window.openImageViewer = function (src) {
    const viewer = document.getElementById("globalImageViewer");
    const img = viewer ? viewer.querySelector(".global-image-viewer-img") : null;
    if (!viewer || !img) return;

    img.src = src || "";
    viewer.classList.add("active");
    document.body.classList.add("image-open");
  };

  window.closeImageViewer = function () {
    const viewer = document.getElementById("globalImageViewer");
    if (viewer) viewer.classList.remove("active");
    document.body.classList.remove("image-open");
  };

  window.goHomePage = function () {
    window.location.href = "/index.html";
  };

  window.openWhatsAppDirect = function () {
    const message = `${getGreeting()}`;
    openWhatsApp(message);
  };

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

  window.switchCartTab = function (tab) {
    activeTab = tab;

    document.querySelectorAll(".cart-tab-btn").forEach(btn => {
      btn.classList.toggle("active", btn.dataset.tab === tab);
    });

    renderCartSystem();
  };

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
    showToast(tr("added_to_cart"));
  };

  function renderCartSystem() {
    const itemsWrap = document.getElementById("cartItems");
    const footer = document.getElementById("cartFooterDynamic");
    if (!itemsWrap || !footer) return;

    const cartTab = document.querySelector('.cart-tab-btn[data-tab="cart"]');
    const pendingTab = document.querySelector('.cart-tab-btn[data-tab="pending"]');
    const ordersTab = document.querySelector('.cart-tab-btn[data-tab="orders"]');

    if (cartTab) cartTab.textContent = tr("cart");
    if (pendingTab) pendingTab.textContent = tr("pending");
    if (ordersTab) ordersTab.textContent = tr("my_orders");

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
          <div class="cart-empty-icon">🛒</div>
          <div class="cart-empty-title">${type === "cart" ? tr("your_cart_empty") : tr("no_pending_offers")}</div>
          <div class="cart-empty-text">${type === "cart" ? tr("add_offers_start_order") : tr("saved_pending_here")}</div>
        </div>
      `;

      footer.innerHTML = `
        <div class="cart-footer-actions">
          <button type="button" class="cart-action-btn cart-action-btn-danger" onclick="confirmEmptySection('${type}')">
            ${type === "cart" ? tr("empty_cart") : tr("empty_pending_cart")}
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
          <span>${tr("select_all")}</span>
        </label>

        <div class="selected-counter">${selectedCount} ${tr("selected")}</div>
      </div>

      <div class="cart-list-grid">
        ${list.map((item, index) => renderSelectableRow(item, index, type)).join("")}
      </div>
    `;

    footer.innerHTML = type === "cart"
      ? `
        <div class="cart-footer-actions">
          <button type="button" class="cart-action-btn cart-action-btn-primary" onclick="sendSelectedOrder('${type}')">${tr("send_order")}</button>
          <button type="button" class="cart-action-btn cart-action-btn-secondary" onclick="saveSelectedAsPending()">${tr("save_as_pending")}</button>
          <button type="button" class="cart-action-btn cart-action-btn-danger" onclick="confirmEmptySection('${type}')">${tr("empty_cart")}</button>
        </div>
      `
      : `
        <div class="cart-footer-actions">
          <button type="button" class="cart-action-btn cart-action-btn-primary" onclick="sendSelectedOrder('${type}')">${tr("send_order")}</button>
          <button type="button" class="cart-action-btn cart-action-btn-danger" onclick="confirmEmptySection('${type}')">${tr("empty_pending_cart")}</button>
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
          <div class="cart-empty-title">${tr("no_orders_yet")}</div>
          <div class="cart-empty-text">${tr("sent_orders_appear")}</div>
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
    const rawStatus = String(order.status || "").trim().toLowerCase();
    const isCancelled = rawStatus === "cancelled";
    const isRejected = rawStatus === "rejected";
    const isDelivered = rawStatus === "delivered";
    const isPending = rawStatus === "pending delivery";
    const isApproved = rawStatus === "approved";
    const isOnTheWay = rawStatus === "on the way";
    const canTrack = isPending || isApproved || isOnTheWay;
    const canCancel = isPending || isApproved;
    const giftHtml = order.has_promotional_gift && order.gift_label
      ? `<div class="order-offer-name">🎁 ${escapeHTML(order.gift_label)}</div>`
      : "";

    return `
      <div class="order-card-item">
        <div class="order-card-top">
          <div class="order-date">${escapeHTML(order.date)}</div>
          <div class="order-status ${statusClass}">${escapeHTML(formatStatusLabel(order.status, order.rejection_reason))}</div>
        </div>

        <div class="order-offers-list">
          ${giftHtml}
          ${itemNames}
        </div>

        <div class="order-card-actions">
          ${canTrack
            ? `<button type="button" class="order-small-btn order-track-btn" onclick="trackOrder(${index})">${tr("track_my_order")}</button>`
            : `<button type="button" class="order-small-btn order-track-btn order-disabled-btn" disabled>${tr("track_my_order")}</button>`
          }

          ${canCancel
            ? `<button type="button" class="order-small-btn order-cancel-btn" onclick="cancelOrderRequest(${index})">${tr("cancel_order")}</button>`
            : isCancelled
              ? `<button type="button" class="order-small-btn order-cancelled-btn" disabled>${tr("order_cancelled")}</button>`
              : isRejected
                ? `<button type="button" class="order-small-btn order-cancelled-btn" disabled>${tr("order_rejected")}</button>`
                : isDelivered
                  ? `<button type="button" class="order-small-btn order-cancelled-btn" disabled>${tr("order_delivered")}</button>`
                  : isOnTheWay
                    ? `<button type="button" class="order-small-btn order-cancelled-btn" disabled>${tr("cancel_by_company_only")}</button>`
                    : `<button type="button" class="order-small-btn order-cancelled-btn" disabled>${tr("order_locked")}</button>`
          }
        </div>
      </div>
    `;
  }

  function getStatusClass(status) {
    const s = String(status || "").toLowerCase();
    if (s.includes("delivered")) return "status-delivered";
    if (s.includes("completed")) return "status-delivered";
    if (s.includes("cancelled")) return "status-cancelled";
    if (s.includes("rejected")) return "status-cancelled";
    if (s.includes("on the way")) return "status-pending";
    if (s.includes("approved")) return "status-pending";
    return "status-pending";
  }

  function formatStatusLabel(status, rejectionReason = "") {
    const s = String(status || "").trim().toLowerCase();

    if (!s) return "Pending Delivery";
    if (s === "cancelled") return "Cancelled";
    if (s === "rejected") return rejectionReason ? `Rejected - ${rejectionReason}` : "Rejected - Not matching conditions";
    if (s === "completed" || s === "delivered") return "Delivered";
    if (s === "approved") return "Approved";
    if (s === "on the way" || s === "on_the_way") return "On The Way";
    if (s === "pending" || s === "sent" || s === "pending delivery") return "Pending Delivery";

    return String(status || "").trim();
  }

  window.toggleItemSelection = function (type, index) {
    const list = type === "cart" ? cart : pendingCart;
    if (!list[index]) return;

    list[index].checked = !list[index].checked;
    saveAll();
    renderCartSystem();
  };

  window.toggleSelectAll = function (type, checked) {
    const list = type === "cart" ? cart : pendingCart;
    list.forEach(item => {
      item.checked = checked;
    });
    saveAll();
    renderCartSystem();
  };

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
        title: tr("remove_item"),
        text: tr("are_you_sure_remove_item"),
        confirmText: tr("remove"),
        danger: true,
        onConfirm: function () {
          list.splice(index, 1);
          saveAll();
          renderCartSystem();
          showToast(tr("item_removed"));
        }
      });
      return;
    }

    list[index].quantity = current - 1;
    hydrateOfferFields(list[index]);
    saveAll();
    renderCartSystem();
  };

  window.confirmEmptySection = function (type) {
    openConfirmModal({
      title: type === "cart" ? tr("empty_cart_title") : tr("empty_pending_title"),
      text: type === "cart" ? tr("empty_cart_confirm_text") : tr("empty_pending_confirm_text"),
      confirmText: type === "cart" ? tr("empty_cart") : tr("empty_pending_cart"),
      danger: true,
      onConfirm: function () {
        if (type === "cart") cart = [];
        if (type === "pending") pendingCart = [];
        saveAll();
        renderCartSystem();
        showToast(type === "cart" ? tr("cart_emptied") : tr("pending_cart_emptied"));
      }
    });
  };

  function mergeIntoPending(item) {
    const existing = pendingCart.find(x =>
      x.title === item.title &&
      x.price === item.price &&
      x.months === item.months
    );

    if (existing) {
      existing.quantity += Number(item.quantity) || 1;
      hydrateOfferFields(existing);
    } else {
      pendingCart.push({
        ...normalizeItem(item),
        id: buildRandomId(),
        checked: true
      });
    }
  }

  window.saveSelectedAsPending = function () {
    const selected = cart.filter(item => item.checked);

    if (!selected.length) {
      showToast(tr("please_select_offers_first"));
      return;
    }

    selected.forEach(item => mergeIntoPending(item));
    cart = cart.filter(item => !item.checked);

    saveAll();
    renderCartSystem();
    showToast(tr("selected_offers_moved"));
  };

  async function syncOrdersFromServer() {
    const user = getUserData();
    if (!user || !user.email) {
      const guestOnly = orders.filter(order => !order.server_order);
      orders = guestOnly.map(normalizeOrder);
      saveAll();
      return;
    }

    try {
      const { data } = await fetchJson("/orders/list.php");
      if (!data.ok || !Array.isArray(data.orders)) {
        return;
      }

      const guestOnly = orders.filter(order => !order.server_order).map(normalizeOrder);
      const serverOrders = data.orders.map(normalizeOrder);

      const merged = [...serverOrders];
      guestOnly.forEach(guestOrder => {
        if (!merged.find(order => order.id === guestOrder.id)) {
          merged.push(guestOrder);
        }
      });

      orders = merged;
      saveAll();
      renderCartSystem();
    } catch (e) {
      console.error("Order sync error:", e);
    }
  }

  window.syncOrdersFromServer = syncOrdersFromServer;

  function buildGuestOrderMessage(order) {
    const giftLine = order.has_promotional_gift
      ? `🎁 Gift: Free gift for first order`
      : "";

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

    return `${getGreeting()}

#ORDER
Order Reference: ${order.id}
Order Date: ${order.date}
${giftLine ? giftLine + "\n" : ""}

${lines}

Please confirm this order and proceed with processing.`;
  }

  window.sendSelectedOrder = async function (type) {
    const source = type === "cart" ? cart : pendingCart;
    const selected = source.filter(item => item.checked);

    if (!selected.length) {
      showToast(tr("please_select_offers_first"));
      return;
    }

    const user = getUserData();
    const orderId = buildOrderId();
    const orderDate = new Date().toLocaleString();

    if (user && user.email) {
      try {
        const payload = {
          order_number: orderId,
          items: selected.map(item => ({
            title: item.title,
            image: item.image,
            quantity: item.quantity,
            monthly: item.monthly,
            down_payment: item.down_payment,
            duration: item.duration,
            total_price: item.total_price,
            devices_count: item.devices_count
          }))
        };

        const { res, data } = await fetchJson("/orders/create.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json"
          },
          body: JSON.stringify(payload)
        });

        if (!res.ok || !data.ok) {
          if (data.message && /auth|login|sign in|unauth/i.test(data.message)) {
            clearUserData();
            customerSession = { logged_in: false, customer: null };
            if (typeof window.openAuthModal === "function") {
              window.openAuthModal();
            }
          }
          showToast(data.message || tr("failed_create_order"));
          return;
        }

        const serverOrder = normalizeOrder(data.order);

        orders = [serverOrder, ...orders.filter(order => order.id !== serverOrder.id)];

        if (type === "cart") {
          cart = cart.filter(item => !item.checked);
        } else {
          pendingCart = pendingCart.filter(item => !item.checked);
        }

        saveAll();
        renderCartSystem();

        openWhatsApp(data.whatsapp_message || buildGuestOrderMessage(serverOrder));
        await syncOrdersFromServer();
        showToast(tr("order_created_successfully"));
        return;
      } catch (e) {
        console.error(e);
        showToast(e.message || tr("failed_send_order"));
        return;
      }
    }

    const guestOrder = normalizeOrder({
      id: orderId,
      date: orderDate,
      status: "Pending Delivery",
      rejection_reason: "",
      server_order: false,
      is_first_order: false,
      has_promotional_gift: false,
      gift_label: "",
      items: selected.map(item => ({
        ...item,
        checked: false
      }))
    });

    orders.unshift(guestOrder);

    if (type === "cart") {
      cart = cart.filter(item => !item.checked);
    } else {
      pendingCart = pendingCart.filter(item => !item.checked);
    }

    saveAll();
    renderCartSystem();

    const message = buildGuestOrderMessage(guestOrder);
    openWhatsApp(message);
    showToast(tr("order_prepared_successfully"));
  };

  window.trackOrder = function (index) {
    const order = orders[index];
    if (!order) return;

    const currentStatus = String(order.status || "").trim().toLowerCase();
    const canTrack = currentStatus === "pending delivery" || currentStatus === "approved" || currentStatus === "on the way";

    if (!canTrack) return;

    const offers = (order.items || []).map(item => `- ${item.title}`).join("\n");

    const text = `${getGreeting()}

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

    const currentStatus = String(order.status || "").trim().toLowerCase();
    const canCancel = currentStatus === "pending delivery" || currentStatus === "approved";

    if (!canCancel) return;

    openConfirmModal({
      title: tr("cancel_order_title"),
      text: tr("cancel_order_confirm_text"),
      confirmText: tr("cancel_order_confirm_btn"),
      danger: true,
      onConfirm: async function () {
        if (order.server_order) {
          try {
            const { res, data } = await fetchJson("/orders/cancel.php", {
              method: "POST",
              headers: {
                "Content-Type": "application/json"
              },
              body: JSON.stringify({
                order_number: order.id
              })
            });

            if (!res.ok || !data.ok) {
              showToast(data.message || tr("failed_cancel_order"));
              return;
            }

            order.status = "Cancelled";
            saveAll();
            renderCartSystem();
            await syncOrdersFromServer();
          } catch (e) {
            console.error(e);
            showToast(e.message || tr("failed_cancel_order"));
            return;
          }
        } else {
          order.status = "Cancelled";
          saveAll();
          renderCartSystem();
        }

        const offers = (order.items || []).map(item => `- ${item.title}`).join("\n");

        const text = `${getGreeting()}

#CANCEL_ORDER
Order Reference: ${order.id}
Order Date: ${order.date}

Offers:
${offers}

I want to cancel this order.
Please confirm the cancellation.`;

        openWhatsApp(text);
        showToast(tr("order_cancelled_successfully"));
      }
    });
  };

  function openConfirmModal(config) {
    ensureConfirmModal();

    const modal = document.getElementById("globalConfirmModal");
    const title = document.getElementById("globalConfirmTitle");
    const text = document.getElementById("globalConfirmText");
    const cancel = document.getElementById("globalConfirmCancel");
    const ok = document.getElementById("globalConfirmOk");

    if (!modal || !title || !text || !cancel || !ok) return;

    title.textContent = config.title || tr("confirm");
    text.textContent = config.text || tr("are_you_sure");
    cancel.textContent = tr("cancel");
    ok.textContent = config.confirmText || tr("confirm");

    ok.classList.toggle("danger", !!config.danger);

    const close = function () {
      modal.classList.remove("active");
      cancel.onclick = null;
      ok.onclick = null;
      modal.onclick = null;
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

  function showToast(message) {
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

  window.showToast = showToast;

  window.logoutUser = async function () {
    try {
      const controller = new AbortController();
      const timeout = setTimeout(() => controller.abort(), 10000);

      try {
        await fetch("/auth/logout.php", {
          cache: "no-store",
          signal: controller.signal
        });
      } finally {
        clearTimeout(timeout);
      }

      clearUserData();
      updateAuthLabel();
      updateLanguageDependentUI();

      if (typeof window.showToast === "function") {
        window.showToast(tr("signed_out"));
      }

      if (typeof window.syncOrdersFromServer === "function") {
        await window.syncOrdersFromServer();
      }

      if (typeof window.closeAuthModal === "function") {
        window.closeAuthModal();
      }
    } catch (e) {
      console.error(e);
    }
  };
})();
