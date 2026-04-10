(function () {
  "use strict";

  const STORAGE_KEYS = {
    cart: "click_company_cart_v5",
    pending: "click_company_pending_v5",
    orders: "click_company_orders_v5",
    user: "click_company_user_v2",
    language: "site_language"
  };

  const DEFAULT_WHATSAPP = {
    phone: "67680877",
    employee_name: "Sales",
    greeting: "Hello 👋"
  };

  const CART_I18N = {
    en: {
      home: "Home",
      cart: "Cart",
      whatsapp: "WhatsApp",
      login: "Login",
      pending: "Pending",
      my_orders: "My Orders",
      your_cart: "Your Cart",
      your_cart_empty: "Your cart is empty",
      your_cart_empty_text: "Add offers to start your order.",
      pending_empty: "No pending offers",
      pending_empty_text: "Saved pending offers will appear here.",
      no_orders_yet: "No orders yet",
      no_orders_text: "Your sent orders will appear here.",
      select_all: "Select All",
      selected: "selected",
      send_order: "Send Order",
      save_as_pending: "Save as Pending",
      empty_cart: "Empty Cart",
      empty_pending_cart: "Empty Pending Cart",
      track_my_order: "Track My Order",
      cancel_order: "Cancel Order",
      order_cancelled: "Order Cancelled",
      order_rejected: "Order Rejected",
      order_delivered: "Order Delivered",
      cancel_by_company_only: "Cancel By Company Only",
      order_locked: "Order Locked",
      remove_item: "Remove Item",
      remove_item_confirm: "Are you sure you want to remove this item?",
      remove: "Remove",
      confirm: "Confirm",
      are_you_sure: "Are you sure?",
      cancel: "Cancel",
      empty_cart_title: "Empty Cart",
      empty_cart_confirm: "Are you sure you want to empty the cart?",
      empty_pending_title: "Empty Pending Cart",
      empty_pending_confirm: "Are you sure you want to empty the pending cart?",
      added_to_cart: "Added to cart",
      item_removed: "Item removed",
      cart_emptied: "Cart emptied",
      pending_emptied: "Pending cart emptied",
      select_offers_first: "Please select offers first",
      moved_to_pending: "Selected offers moved to pending",
      order_created_successfully: "Order created successfully",
      order_prepared_successfully: "Order prepared successfully",
      failed_create_order: "Failed to create order",
      failed_send_order: "Failed to send order",
      cancel_order_title: "Cancel Order",
      cancel_order_confirm: "Are you sure you want to cancel this order?",
      cancel_order_btn: "Cancel Order",
      order_cancelled_successfully: "Order cancelled successfully",
      failed_cancel_order: "Failed to cancel order",
      signed_out: "Signed out",
      customer: "Customer",
      pending_delivery: "Pending Delivery",
      approved: "Approved",
      on_the_way: "On The Way",
      delivered: "Delivered",
      cancelled: "Cancelled",
      rejected: "Rejected",
      rejected_default_reason: "Not matching conditions",
      offer: "Offer",
      offer_name: "Offer Name",
      devices_in_offer: "Devices in Offer",
      quantity: "Quantity",
      down_payment: "Down Payment",
      monthly_installment: "Monthly Installment",
      months: "Months",
      total_price: "Total Price",
      image: "Image",
      order_reference: "Order Reference",
      order_date: "Order Date",
      customer_name: "Customer Name",
      customer_email: "Customer Email",
      customer_whatsapp: "Customer WhatsApp",
      gift: "Gift",
      free_gift_first_order: "Free gift for first order",
      offers_label: "Offers",
      please_confirm_order: "Please confirm this order and proceed with processing.",
      please_update_status: "Please update me with the current status of this order.",
      i_want_cancel: "I want to cancel this order.",
      please_confirm_cancel: "Please confirm the cancellation.",
      request_timeout: "Request timeout. Please try again.",
      unexpected_server_response: "Unexpected server response."
    },
    ph: {
      home: "Home",
      cart: "Cart",
      whatsapp: "WhatsApp",
      login: "Login",
      pending: "Pending",
      my_orders: "My Orders",
      your_cart: "Iyong Cart",
      your_cart_empty: "Walang laman ang iyong cart",
      your_cart_empty_text: "Magdagdag ng mga alok upang simulan ang iyong order.",
      pending_empty: "Walang pending offers",
      pending_empty_text: "Ang mga naka-save na pending offer ay lalabas dito.",
      no_orders_yet: "Wala pang orders",
      no_orders_text: "Ang mga naipadalang order mo ay lalabas dito.",
      select_all: "Piliin Lahat",
      selected: "napili",
      send_order: "Ipadala ang Order",
      save_as_pending: "I-save bilang Pending",
      empty_cart: "I-empty ang Cart",
      empty_pending_cart: "I-empty ang Pending Cart",
      track_my_order: "I-track ang Order Ko",
      cancel_order: "I-cancel ang Order",
      order_cancelled: "Nakansela ang Order",
      order_rejected: "Tinanggihan ang Order",
      order_delivered: "Na-deliver ang Order",
      cancel_by_company_only: "Company Lang ang Puwedeng Mag-cancel",
      order_locked: "Naka-lock ang Order",
      remove_item: "Alisin ang Item",
      remove_item_confirm: "Sigurado ka bang gusto mong alisin ang item na ito?",
      remove: "Alisin",
      confirm: "Kumpirmahin",
      are_you_sure: "Sigurado ka ba?",
      cancel: "Kanselahin",
      empty_cart_title: "I-empty ang Cart",
      empty_cart_confirm: "Sigurado ka bang gusto mong i-empty ang cart?",
      empty_pending_title: "I-empty ang Pending Cart",
      empty_pending_confirm: "Sigurado ka bang gusto mong i-empty ang pending cart?",
      added_to_cart: "Naidagdag sa cart",
      item_removed: "Naalis ang item",
      cart_emptied: "Na-empty ang cart",
      pending_emptied: "Na-empty ang pending cart",
      select_offers_first: "Pumili muna ng offers",
      moved_to_pending: "Ang napiling offers ay nailipat sa pending",
      order_created_successfully: "Matagumpay na nalikha ang order",
      order_prepared_successfully: "Matagumpay na naihanda ang order",
      failed_create_order: "Hindi nagawang likhain ang order",
      failed_send_order: "Hindi naipadala ang order",
      cancel_order_title: "I-cancel ang Order",
      cancel_order_confirm: "Sigurado ka bang gusto mong i-cancel ang order na ito?",
      cancel_order_btn: "I-cancel ang Order",
      order_cancelled_successfully: "Matagumpay na nakansela ang order",
      failed_cancel_order: "Hindi nakansela ang order",
      signed_out: "Nakapag-sign out na",
      customer: "Customer",
      pending_delivery: "Pending Delivery",
      approved: "Approved",
      on_the_way: "On The Way",
      delivered: "Delivered",
      cancelled: "Cancelled",
      rejected: "Rejected",
      rejected_default_reason: "Hindi tugma sa mga kondisyon",
      offer: "Offer",
      offer_name: "Pangalan ng Offer",
      devices_in_offer: "Mga Device sa Offer",
      quantity: "Dami",
      down_payment: "Down Payment",
      monthly_installment: "Buwanang Hulugan",
      months: "Buwan",
      total_price: "Kabuuang Presyo",
      image: "Larawan",
      order_reference: "Order Reference",
      order_date: "Petsa ng Order",
      customer_name: "Pangalan ng Customer",
      customer_email: "Email ng Customer",
      customer_whatsapp: "WhatsApp ng Customer",
      gift: "Regalo",
      free_gift_first_order: "Libreng regalo para sa unang order",
      offers_label: "Mga Offer",
      please_confirm_order: "Pakikumpirma ang order na ito at ipagpatuloy ang proseso.",
      please_update_status: "Pakibigay ang kasalukuyang status ng order na ito.",
      i_want_cancel: "Gusto kong kanselahin ang order na ito.",
      please_confirm_cancel: "Pakikumpirma ang pagkansela.",
      request_timeout: "Nag-timeout ang request. Pakisubukang muli.",
      unexpected_server_response: "Hindi inaasahang tugon ng server."
    },
    hi: {
      home: "होम",
      cart: "कार्ट",
      whatsapp: "व्हाट्सऐप",
      login: "लॉगिन",
      pending: "पेंडिंग",
      my_orders: "मेरे ऑर्डर",
      your_cart: "आपका कार्ट",
      your_cart_empty: "आपका कार्ट खाली है",
      your_cart_empty_text: "अपना ऑर्डर शुरू करने के लिए ऑफर जोड़ें।",
      pending_empty: "कोई pending offers नहीं हैं",
      pending_empty_text: "सहेजे गए pending offers यहाँ दिखाई देंगे।",
      no_orders_yet: "अभी तक कोई ऑर्डर नहीं",
      no_orders_text: "आपके भेजे गए ऑर्डर यहाँ दिखाई देंगे।",
      select_all: "सभी चुनें",
      selected: "चयनित",
      send_order: "ऑर्डर भेजें",
      save_as_pending: "Pending के रूप में सेव करें",
      empty_cart: "कार्ट खाली करें",
      empty_pending_cart: "Pending Cart खाली करें",
      track_my_order: "मेरा ऑर्डर ट्रैक करें",
      cancel_order: "ऑर्डर रद्द करें",
      order_cancelled: "ऑर्डर रद्द किया गया",
      order_rejected: "ऑर्डर अस्वीकृत",
      order_delivered: "ऑर्डर डिलीवर हो गया",
      cancel_by_company_only: "केवल कंपनी रद्द कर सकती है",
      order_locked: "ऑर्डर लॉक है",
      remove_item: "आइटम हटाएं",
      remove_item_confirm: "क्या आप वाकई इस आइटम को हटाना चाहते हैं?",
      remove: "हटाएं",
      confirm: "पुष्टि करें",
      are_you_sure: "क्या आप सुनिश्चित हैं?",
      cancel: "रद्द करें",
      empty_cart_title: "कार्ट खाली करें",
      empty_cart_confirm: "क्या आप वाकई कार्ट खाली करना चाहते हैं?",
      empty_pending_title: "Pending Cart खाली करें",
      empty_pending_confirm: "क्या आप वाकई pending cart खाली करना चाहते हैं?",
      added_to_cart: "कार्ट में जोड़ दिया गया",
      item_removed: "आइटम हटा दिया गया",
      cart_emptied: "कार्ट खाली कर दिया गया",
      pending_emptied: "Pending cart खाली कर दिया गया",
      select_offers_first: "कृपया पहले ऑफर चुनें",
      moved_to_pending: "चयनित ऑफर pending में भेज दिए गए",
      order_created_successfully: "ऑर्डर सफलतापूर्वक बनाया गया",
      order_prepared_successfully: "ऑर्डर सफलतापूर्वक तैयार किया गया",
      failed_create_order: "ऑर्डर बनाना विफल रहा",
      failed_send_order: "ऑर्डर भेजना विफल रहा",
      cancel_order_title: "ऑर्डर रद्द करें",
      cancel_order_confirm: "क्या आप वाकई इस ऑर्डर को रद्द करना चाहते हैं?",
      cancel_order_btn: "ऑर्डर रद्द करें",
      order_cancelled_successfully: "ऑर्डर सफलतापूर्वक रद्द किया गया",
      failed_cancel_order: "ऑर्डर रद्द नहीं हो सका",
      signed_out: "साइन आउट हो गया",
      customer: "ग्राहक",
      pending_delivery: "Pending Delivery",
      approved: "Approved",
      on_the_way: "On The Way",
      delivered: "Delivered",
      cancelled: "Cancelled",
      rejected: "Rejected",
      rejected_default_reason: "शर्तों से मेल नहीं खाता",
      offer: "ऑफर",
      offer_name: "ऑफर का नाम",
      devices_in_offer: "ऑफर में डिवाइस",
      quantity: "मात्रा",
      down_payment: "डाउन पेमेंट",
      monthly_installment: "मासिक किस्त",
      months: "महीने",
      total_price: "कुल कीमत",
      image: "चित्र",
      order_reference: "ऑर्डर रेफरेंस",
      order_date: "ऑर्डर तिथि",
      customer_name: "ग्राहक का नाम",
      customer_email: "ग्राहक का ईमेल",
      customer_whatsapp: "ग्राहक का व्हाट्सऐप",
      gift: "उपहार",
      free_gift_first_order: "पहले ऑर्डर के लिए मुफ्त गिफ्ट",
      offers_label: "ऑफर",
      please_confirm_order: "कृपया इस ऑर्डर की पुष्टि करें और प्रक्रिया शुरू करें।",
      please_update_status: "कृपया इस ऑर्डर की वर्तमान स्थिति बताएं।",
      i_want_cancel: "मैं इस ऑर्डर को रद्द करना चाहता हूँ।",
      please_confirm_cancel: "कृपया रद्दीकरण की पुष्टि करें।",
      request_timeout: "अनुरोध का समय समाप्त हो गया। कृपया पुनः प्रयास करें।",
      unexpected_server_response: "अनपेक्षित सर्वर प्रतिक्रिया।"
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
    
   if (isMobile()) {
     const langTop = document.getElementById("languageSelect");
     if (langTop) {
       langTop.style.display = "none";

       const parent = langTop.closest(".language-switcher") || langTop.parentElement;
       if (parent) parent.style.display = "none";
     }
   }

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
    applyCartTranslations();
    renderCartSystem();
  });

  function getUiLanguage() {
    const value = String(localStorage.getItem(STORAGE_KEYS.language) || "en").trim().toLowerCase();
    if (CART_I18N[value]) return value;
    return "en";
  }

  function tr(key) {
    const lang = getUiLanguage();
    return CART_I18N[lang]?.[key] || CART_I18N.en?.[key] || key;
  }

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
      status: String(order.status || tr("pending_delivery")).trim(),
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
      title: String(item.title || tr("offer")).trim(),
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
    return num ? `${num} ${tr("months")}` : raw;
  }

  function normalizeDownPayment(value) {
    const raw = String(value || "").trim();
    if (!raw) return `0 KD ${tr("down_payment")}`;
    if (/down payment/i.test(raw)) return raw;
    if (/kd/i.test(raw)) return `${raw} ${tr("down_payment")}`;
    const num = extractNumber(raw);
    return num ? `${num} KD ${tr("down_payment")}` : `0 KD ${tr("down_payment")}`;
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
    if (match) return `${match[1]} ${tr("months")}`;
    const num = extractNumber(raw);
    return num ? `${num} ${tr("months")}` : "";
  }

  function extractDownPayment(monthsText) {
    const raw = String(monthsText || "").trim();
    if (!raw) return `0 KD ${tr("down_payment")}`;
    const match = raw.match(/(\d+(\.\d+)?)\s*KD\s*Down\s*Payment/i);
    if (match) return `${match[1]} KD ${tr("down_payment")}`;
    return `0 KD ${tr("down_payment")}`;
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
        throw new Error(raw || tr("unexpected_server_response"));
      }

      return { res, data };
    } catch (err) {
      if (err.name === "AbortError") {
        throw new Error(tr("request_timeout"));
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
        name: customerSession.customer.full_name || customerSession.customer.email || tr("customer"),
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

    if (!/[👋]/.test(greeting)) {
      greeting = `${greeting} 👋`;
    }

    return greeting;
  }

  function enhanceWhatsAppMessage(text) {
    let message = String(text || "").trim();
    if (!message) return message;

    message = message.replace(/^Welcome to Click Company\s*/im, "Welcome to Click Company 👋\n");
    message = message.replace(/^Gift:/gim, "🎁 Gift:");
    message = message.replace(/^Offer\s+(\d+)/gim, "🔹 Offer $1");

    return message;
  }

  function openWhatsApp(text) {
    const finalText = enhanceWhatsAppMessage(text);
    const url = `https://wa.me/${getWhatsappPhone()}?text=${encodeURIComponent(finalText)}`;
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
    if (!document.getElementById("mobileAppBarGlobal")) {
      const bar = document.createElement("div");
      bar.id = "mobileAppBarGlobal";
      bar.className = "mobile-app-bar-global";
      bar.innerHTML = `
        <div class="mobile-app-lang-wrap">
          <select id="mobileLanguageSelect" class="mobile-language-select" aria-label="Language">
            <option value="en">EN</option>
            <option value="ph">PH</option>
            <option value="hi">HI</option>
          </select>
        </div>

        <button type="button" class="mobile-app-item active" data-mobile-nav="home" onclick="goHomePage()">
          <span class="mobile-app-icon">🏠</span>
          <span class="mobile-app-label" id="mobileNavHomeLabel">${tr("home")}</span>
        </button>

        <button type="button" class="mobile-app-item" data-mobile-nav="cart" onclick="openCart()">
          <span class="mobile-app-icon">🛒</span>
          <span class="mobile-app-label" id="mobileNavCartLabel">${tr("cart")}</span>
          <span class="mobile-app-badge" id="cart-count-floating">0</span>
        </button>

        <button type="button" class="mobile-app-item" data-mobile-nav="whatsapp" onclick="openWhatsAppDirect()">
          <span class="mobile-app-icon">💬</span>
          <span class="mobile-app-label" id="mobileNavWhatsappLabel">${tr("whatsapp")}</span>
        </button>

        <button type="button" class="mobile-app-item" data-mobile-nav="auth" onclick="openAuthModal()">
          <span class="mobile-app-icon">👤</span>
          <span class="mobile-app-label" id="mobileAuthLabel">${tr("login")}</span>
        </button>
      `;
      document.body.appendChild(bar);
    }

    const languageSelect = document.getElementById("mobileLanguageSelect");
    if (languageSelect) {
      languageSelect.value = getUiLanguage();

      if (!languageSelect.dataset.bound) {
        languageSelect.dataset.bound = "1";
        languageSelect.addEventListener("change", function () {
          localStorage.setItem(STORAGE_KEYS.language, this.value || "en");
          document.dispatchEvent(new CustomEvent("site-language-changed", {
            detail: { language: this.value || "en" }
          }));
        });
      }
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
      applyCartTranslations();
    });

    document.addEventListener("site-language-changed", function (e) {
      const lang = e?.detail?.language || getUiLanguage();
      localStorage.setItem(STORAGE_KEYS.language, lang);

      normalizeFloatingCartButton();
      updateAuthLabel();
      
     applyCartTranslations();
      
     setTimeout(() => {
    renderCartSystem();
    }, 0);

    updateAllBadges();
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

  function applyCartTranslations() {
    const cartHeaderTitle = document.querySelector(".cart-header h3");
    if (cartHeaderTitle) cartHeaderTitle.textContent = tr("your_cart");

    const cartTabs = document.querySelectorAll(".cart-tab-btn");
    cartTabs.forEach(btn => {
      if (btn.dataset.tab === "cart") btn.textContent = tr("cart");
      if (btn.dataset.tab === "pending") btn.textContent = tr("pending");
      if (btn.dataset.tab === "orders") btn.textContent = tr("my_orders");
    });

    const mobileNavHomeLabel = document.getElementById("mobileNavHomeLabel");
    const mobileNavCartLabel = document.getElementById("mobileNavCartLabel");
    const mobileNavWhatsappLabel = document.getElementById("mobileNavWhatsappLabel");
    const mobileLanguageSelect = document.getElementById("mobileLanguageSelect");

    if (mobileNavHomeLabel) mobileNavHomeLabel.textContent = tr("home");
    if (mobileNavCartLabel) mobileNavCartLabel.textContent = tr("cart");
    if (mobileNavWhatsappLabel) mobileNavWhatsappLabel.textContent = tr("whatsapp");
    if (mobileLanguageSelect) mobileLanguageSelect.value = getUiLanguage();

    const cartBtns = document.querySelectorAll(".cart-btn");
    cartBtns.forEach(btn => {
      const textNodes = Array.from(btn.childNodes).filter(node => node.nodeType === 3);
      if (textNodes.length) {
        textNodes[0].textContent = `${tr("cart")} `;
      }
    });

    normalizeFloatingCartButton();
    updateAuthLabel();
  }

  function updateAuthLabel() {
    const user = getUserData();
    const value = user && user.email ? (user.full_name || user.email) : tr("login");

    if (typeof window.setTopAuthLabel === "function") {
      window.setTopAuthLabel(value);
    }

    const desktopLabel = document.getElementById("desktopAuthLabel");
    const mobileLabel = document.getElementById("mobileAuthLabel");

    if (desktopLabel) desktopLabel.textContent = value;
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
          <div class="cart-empty-title">${type === "cart" ? tr("your_cart_empty") : tr("pending_empty")}</div>
          <div class="cart-empty-text">${type === "cart" ? tr("your_cart_empty_text") : tr("pending_empty_text")}</div>
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
    const title = escapeHTML(item.title || tr("offer"));
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
          <div class="cart-empty-text">${tr("no_orders_text")}</div>
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

    if (!s) return tr("pending_delivery");
    if (s === "cancelled") return tr("cancelled");
    if (s === "rejected") return rejectionReason ? `${tr("rejected")} - ${rejectionReason}` : `${tr("rejected")} - ${tr("rejected_default_reason")}`;
    if (s === "completed" || s === "delivered") return tr("delivered");
    if (s === "approved") return tr("approved");
    if (s === "on the way" || s === "on_the_way") return tr("on_the_way");
    if (s === "pending" || s === "sent" || s === "pending delivery") return tr("pending_delivery");

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
        text: tr("remove_item_confirm"),
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
      text: type === "cart" ? tr("empty_cart_confirm") : tr("empty_pending_confirm"),
      confirmText: type === "cart" ? tr("empty_cart") : tr("empty_pending_cart"),
      danger: true,
      onConfirm: function () {
        if (type === "cart") cart = [];
        if (type === "pending") pendingCart = [];
        saveAll();
        renderCartSystem();
        showToast(type === "cart" ? tr("cart_emptied") : tr("pending_emptied"));
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
      showToast(tr("select_offers_first"));
      return;
    }

    selected.forEach(item => mergeIntoPending(item));
    cart = cart.filter(item => !item.checked);

    saveAll();
    renderCartSystem();
    showToast(tr("moved_to_pending"));
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

  function buildGiftLine(order) {
    if (!order) return "";

    if (order.has_promotional_gift || order.gift_label) {
      return `🎁 ${tr("gift")}: ${order.gift_label || tr("free_gift_first_order")}`;
    }

    return "";
  }

  function buildGuestOrderMessage(order) {
    const user = getUserData();
    const giftLine = buildGiftLine(order);

    const lines = (order.items || []).map((item, idx) => {
      const imageUrl = getOriginImage(item.image);
      return [
        `🔹 ${tr("offer")} ${idx + 1}`,
        `${tr("offer_name")}: ${item.title}`,
        `${tr("devices_in_offer")}: ${item.devices_count || "1"}`,
        `${tr("quantity")}: ${item.quantity}`,
        `${tr("down_payment")}: ${item.down_payment || `0 KD ${tr("down_payment")}`}`,
        `${tr("monthly_installment")}: ${item.monthly || item.price || ""}`,
        `${tr("months")}: ${item.duration || ""}`,
        `${tr("total_price")}: ${item.total_price || ""}`,
        `${tr("image")}: ${imageUrl}`
      ].join("\n");
    }).join("\n\n");

    const customerBlock = user && user.email
      ? [
          `${tr("customer_name")}: ${user.full_name || user.email || ""}`,
          `${tr("customer_email")}: ${user.email || ""}`
        ].join("\n")
      : "";

    return `${getGreeting()}

#ORDER
${tr("order_reference")}: ${order.id}
${customerBlock ? customerBlock + "\n" : ""}${tr("order_date")}: ${order.date}
${giftLine ? giftLine + "\n" : ""}

${lines}

${tr("please_confirm_order")}`;
  }

  window.sendSelectedOrder = async function (type) {
    const source = type === "cart" ? cart : pendingCart;
    const selected = source.filter(item => item.checked);

    if (!selected.length) {
      showToast(tr("select_offers_first"));
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

        const serverMessage = data.whatsapp_message || buildGuestOrderMessage(serverOrder);
        openWhatsApp(serverMessage);

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
      status: tr("pending_delivery"),
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
${tr("order_reference")}: ${order.id}
${tr("order_date")}: ${order.date}

${tr("offers_label")}:
${offers}

${tr("please_update_status")}`;

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
      text: tr("cancel_order_confirm"),
      confirmText: tr("cancel_order_btn"),
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
${tr("order_reference")}: ${order.id}
${tr("order_date")}: ${order.date}

${tr("offers_label")}:
${offers}

${tr("i_want_cancel")}
${tr("please_confirm_cancel")}`;

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
    ok.textContent = config.confirmText || tr("confirm");
    cancel.textContent = tr("cancel");

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
})();
