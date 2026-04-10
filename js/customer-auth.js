(function () {
  "use strict";

  const USER_STORAGE_KEY = "click_company_user_v2";
  const LANGUAGE_STORAGE_KEY = "site_language";

  const AUTH_I18N = {
    en: {
      login: "Login",
      sign_in: "Sign In",
      sign_up: "Sign Up",
      welcome_back: "Welcome back",
      welcome_note: "Enter your email, then check your inbox for the verification code.",
      create_account: "Create your account",
      create_note: "Full name and WhatsApp number are required during registration.",
      full_name: "Full name",
      full_name_placeholder: "Full name",
      email: "Email",
      email_placeholder: "Email address",
      country_code: "Country code",
      whatsapp_number: "WhatsApp number",
      whatsapp_placeholder: "WhatsApp number",
      send_verification_code: "Send Verification Code",
      sending: "Sending...",
      verify_code_title: "Verify code",
      verify_note_prefix: "We sent a verification code to:",
      verification_code: "Verification code",
      verification_code_placeholder: "Enter verification code",
      verify_code: "Verify Code",
      verifying: "Verifying...",
      back: "Back",
      signed_in_successfully: "Signed in successfully",
      sign_out: "Sign Out",
      please_wait: "Please wait...",
      enter_email_first: "Enter your email first.",
      sending_verification_code: "Sending verification code...",
      verification_code_sent: "Verification code sent to your email.",
      signup_required: "Full name, WhatsApp number and email are required.",
      enter_email_and_code: "Enter email and verification code.",
      verifying_code: "Verifying code...",
      verification_completed: "Verification completed successfully.",
      verification_failed: "Verification failed.",
      signed_out: "Signed out",
      customer: "Customer",
      request_timeout: "Request timeout. Please try again.",
      status_timeout: "Status request timeout.",
      unexpected_response: "Unexpected server response.",
      invalid_status_response: "Invalid status response.",
      request_failed: "Request failed"
    },
    ph: {
      login: "Login",
      sign_in: "Sign In",
      sign_up: "Sign Up",
      welcome_back: "Welcome back",
      welcome_note: "Ilagay ang iyong email, pagkatapos tingnan ang iyong inbox para sa verification code.",
      create_account: "Gumawa ng account",
      create_note: "Kinakailangan ang buong pangalan at WhatsApp number sa pagrehistro.",
      full_name: "Buong pangalan",
      full_name_placeholder: "Buong pangalan",
      email: "Email",
      email_placeholder: "Email address",
      country_code: "Country code",
      whatsapp_number: "WhatsApp number",
      whatsapp_placeholder: "WhatsApp number",
      send_verification_code: "Magpadala ng Verification Code",
      sending: "Nagpapadala...",
      verify_code_title: "I-verify ang code",
      verify_note_prefix: "Nagpadala kami ng verification code sa:",
      verification_code: "Verification code",
      verification_code_placeholder: "Ilagay ang verification code",
      verify_code: "I-verify ang Code",
      verifying: "Sinusuri...",
      back: "Bumalik",
      signed_in_successfully: "Matagumpay na nakapag-sign in",
      sign_out: "Sign Out",
      please_wait: "Maghintay sandali...",
      enter_email_first: "Ilagay muna ang iyong email.",
      sending_verification_code: "Nagpapadala ng verification code...",
      verification_code_sent: "Naipadala na ang verification code sa iyong email.",
      signup_required: "Kinakailangan ang buong pangalan, WhatsApp number at email.",
      enter_email_and_code: "Ilagay ang email at verification code.",
      verifying_code: "Sinusuri ang code...",
      verification_completed: "Matagumpay ang verification.",
      verification_failed: "Nabigo ang verification.",
      signed_out: "Nakapag-sign out na",
      customer: "Customer",
      request_timeout: "Nag-timeout ang request. Pakisubukang muli.",
      status_timeout: "Nag-timeout ang status request.",
      unexpected_response: "Hindi inaasahang tugon ng server.",
      invalid_status_response: "Hindi valid na status response.",
      request_failed: "Nabigo ang request"
    },
    hi: {
      login: "लॉगिन",
      sign_in: "साइन इन",
      sign_up: "साइन अप",
      welcome_back: "वापसी पर स्वागत है",
      welcome_note: "अपना ईमेल दर्ज करें, फिर verification code के लिए अपना inbox देखें।",
      create_account: "अपना अकाउंट बनाएं",
      create_note: "रजिस्ट्रेशन के समय पूरा नाम और WhatsApp नंबर आवश्यक है।",
      full_name: "पूरा नाम",
      full_name_placeholder: "पूरा नाम",
      email: "ईमेल",
      email_placeholder: "ईमेल पता",
      country_code: "कंट्री कोड",
      whatsapp_number: "व्हाट्सऐप नंबर",
      whatsapp_placeholder: "व्हाट्सऐप नंबर",
      send_verification_code: "Verification Code भेजें",
      sending: "भेजा जा रहा है...",
      verify_code_title: "कोड सत्यापित करें",
      verify_note_prefix: "हमने verification code भेजा है:",
      verification_code: "Verification code",
      verification_code_placeholder: "Verification code दर्ज करें",
      verify_code: "कोड सत्यापित करें",
      verifying: "सत्यापित हो रहा है...",
      back: "वापस",
      signed_in_successfully: "सफलतापूर्वक साइन इन हुआ",
      sign_out: "साइन आउट",
      please_wait: "कृपया प्रतीक्षा करें...",
      enter_email_first: "पहले अपना ईमेल दर्ज करें।",
      sending_verification_code: "Verification code भेजा जा रहा है...",
      verification_code_sent: "Verification code आपके ईमेल पर भेज दिया गया है।",
      signup_required: "पूरा नाम, WhatsApp नंबर और ईमेल आवश्यक हैं।",
      enter_email_and_code: "ईमेल और verification code दर्ज करें।",
      verifying_code: "कोड सत्यापित किया जा रहा है...",
      verification_completed: "सत्यापन सफलतापूर्वक पूरा हुआ।",
      verification_failed: "सत्यापन विफल हुआ।",
      signed_out: "साइन आउट हो गया",
      customer: "ग्राहक",
      request_timeout: "अनुरोध का समय समाप्त हो गया। कृपया पुनः प्रयास करें।",
      status_timeout: "स्थिति अनुरोध का समय समाप्त हो गया।",
      unexpected_response: "अनपेक्षित सर्वर प्रतिक्रिया।",
      invalid_status_response: "अमान्य स्थिति प्रतिक्रिया।",
      request_failed: "अनुरोध विफल हुआ"
    }
  };

  let currentMode = "signin";
  let verifyMode = "";
  let verifyEmail = "";

  function getUiLanguage() {
    const value = String(localStorage.getItem(LANGUAGE_STORAGE_KEY) || "en").trim().toLowerCase();
    if (AUTH_I18N[value]) return value;
    return "en";
  }

  function tr(key) {
    const lang = getUiLanguage();
    return AUTH_I18N[lang]?.[key] || AUTH_I18N.en?.[key] || key;
  }

  function setLocalUser(user) {
    localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(user));
    document.dispatchEvent(new CustomEvent("customer-auth-updated", { detail: user }));
  }

  function clearLocalUser() {
    localStorage.removeItem(USER_STORAGE_KEY);
    document.dispatchEvent(new CustomEvent("customer-auth-updated", { detail: null }));
  }

  function getCurrentLocalUser() {
    try {
      return JSON.parse(localStorage.getItem(USER_STORAGE_KEY) || "null");
    } catch {
      return null;
    }
  }

  async function postForm(url, data) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 10000);

    try {
      const body = new URLSearchParams(data);

      const res = await fetch(url, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
        },
        body,
        cache: "no-store",
        signal: controller.signal
      });

      const raw = await res.text();

      let json;
      try {
        json = JSON.parse(raw);
      } catch (e) {
        throw new Error(raw || tr("unexpected_response"));
      }

      if (!res.ok || !json.ok) {
        throw new Error(json.message || tr("request_failed"));
      }

      return json;
    } catch (err) {
      if (err.name === "AbortError") {
        throw new Error(tr("request_timeout"));
      }
      throw err;
    } finally {
      clearTimeout(timeout);
    }
  }

  async function getStatus() {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 10000);

    try {
      const res = await fetch("/auth/status.php", {
        cache: "no-store",
        signal: controller.signal
      });

      const raw = await res.text();

      try {
        return JSON.parse(raw);
      } catch (e) {
        throw new Error(raw || tr("invalid_status_response"));
      }
    } catch (err) {
      if (err.name === "AbortError") {
        throw new Error(tr("status_timeout"));
      }
      throw err;
    } finally {
      clearTimeout(timeout);
    }
  }

  function setTopAuthLabel(value) {
    const desktopLabel = document.getElementById("desktopAuthLabel");
    const mobileLabel = document.getElementById("mobileAuthLabel");
    const finalValue = value || tr("login");

    if (desktopLabel) desktopLabel.textContent = finalValue;
    if (mobileLabel) mobileLabel.textContent = finalValue;
  }

  window.setTopAuthLabel = setTopAuthLabel;

  function buildCountryOptions(select) {
    if (!select || !window.COUNTRY_CODES || !Array.isArray(window.COUNTRY_CODES)) {
      return;
    }

    select.innerHTML = "";

    window.COUNTRY_CODES.forEach(function (item) {
      const option = document.createElement("option");
      option.value = item.code;
      option.textContent = `${item.flag} ${item.country} (${item.code})`;

      if (item.code === "+965") {
        option.selected = true;
      }

      select.appendChild(option);
    });
  }

  function setMessage(text, type) {
    const msg = document.getElementById("customerAuthMessage");
    if (!msg) return;

    msg.className = "customer-auth-message show";
    if (type) {
      msg.classList.add(type);
    }
    msg.textContent = text || "";
  }

  function clearMessage() {
    const msg = document.getElementById("customerAuthMessage");
    if (!msg) return;

    msg.className = "customer-auth-message";
    msg.textContent = "";
  }

  function setButtonLoading(button, isLoading, loadingText, normalText) {
    if (!button) return;

    if (!button.dataset.originalText) {
      button.dataset.originalText = normalText || button.textContent || "";
    }

    if (isLoading) {
      button.disabled = true;
      button.textContent = loadingText || tr("please_wait");
    } else {
      button.disabled = false;
      button.textContent = normalText || button.dataset.originalText || "";
    }
  }

  function switchAuthTab(mode) {
    currentMode = mode === "signup" ? "signup" : "signin";

    const signinBtn = document.getElementById("authTabSignin");
    const signupBtn = document.getElementById("authTabSignup");
    const signinView = document.getElementById("authSigninView");
    const signupView = document.getElementById("authSignupView");
    const verifyView = document.getElementById("authVerifyView");
    const loggedView = document.getElementById("authLoggedView");

    if (signinBtn) signinBtn.classList.toggle("active", currentMode === "signin");
    if (signupBtn) signupBtn.classList.toggle("active", currentMode === "signup");

    if (signinView) signinView.style.display = currentMode === "signin" ? "" : "none";
    if (signupView) signupView.style.display = currentMode === "signup" ? "" : "none";
    if (verifyView) verifyView.style.display = "none";
    if (loggedView) loggedView.style.display = "none";

    clearMessage();
  }

  function showVerifyView(mode, email) {
    verifyMode = mode;
    verifyEmail = email || "";

    const signinView = document.getElementById("authSigninView");
    const signupView = document.getElementById("authSignupView");
    const verifyView = document.getElementById("authVerifyView");
    const loggedView = document.getElementById("authLoggedView");
    const verifyEmailText = document.getElementById("verifyEmailText");
    const verifyEmailInput = document.getElementById("authVerifyEmail");
    const verifyOtpInput = document.getElementById("authVerifyOtp");

    if (signinView) signinView.style.display = "none";
    if (signupView) signupView.style.display = "none";
    if (loggedView) loggedView.style.display = "none";
    if (verifyView) verifyView.style.display = "";

    if (verifyEmailText) {
      verifyEmailText.textContent = verifyEmail;
    }

    if (verifyEmailInput) {
      verifyEmailInput.value = verifyEmail;
    }

    if (verifyOtpInput) {
      verifyOtpInput.value = "";
      verifyOtpInput.focus();
    }
  }

  function showLoggedView(user) {
    const signinView = document.getElementById("authSigninView");
    const signupView = document.getElementById("authSignupView");
    const verifyView = document.getElementById("authVerifyView");
    const loggedView = document.getElementById("authLoggedView");

    const fullNameEl = document.getElementById("loggedCustomerName");
    const emailEl = document.getElementById("loggedCustomerEmail");

    if (signinView) signinView.style.display = "none";
    if (signupView) signupView.style.display = "none";
    if (verifyView) verifyView.style.display = "none";
    if (loggedView) loggedView.style.display = "";

    if (fullNameEl) {
      fullNameEl.textContent = user?.full_name || "-";
    }

    if (emailEl) {
      emailEl.textContent = user?.email || "-";
    }

    clearMessage();
  }

  function backToTabs() {
    const user = getCurrentLocalUser();
    if (user && user.email) {
      showLoggedView(user);
      return;
    }

    switchAuthTab(currentMode);
  }

  function applyAuthTranslations() {
    const localUser = getCurrentLocalUser();

    const authTabSignin = document.getElementById("authTabSignin");
    const authTabSignup = document.getElementById("authTabSignup");

    const signinHeadline = document.getElementById("authSigninHeadline");
    const signinNote = document.getElementById("authSigninNote");
    const signinEmailLabel = document.getElementById("authSigninEmailLabel");
    const signinEmailInput = document.getElementById("authSigninEmail");
    const sendSigninOtpBtn = document.getElementById("sendSigninOtpBtn");

    const signupHeadline = document.getElementById("authSignupHeadline");
    const signupNote = document.getElementById("authSignupNote");
    const signupNameLabel = document.getElementById("authSignupNameLabel");
    const signupNameInput = document.getElementById("authSignupName");
    const signupCountryCodeLabel = document.getElementById("authSignupCountryCodeLabel");
    const signupWhatsappLabel = document.getElementById("authSignupWhatsappLabel");
    const signupWhatsappInput = document.getElementById("authSignupWhatsapp");
    const signupEmailLabel = document.getElementById("authSignupEmailLabel");
    const signupEmailInput = document.getElementById("authSignupEmail");
    const sendSignupOtpBtn = document.getElementById("sendSignupOtpBtn");

    const verifyHeadline = document.getElementById("authVerifyHeadline");
    const verifyNotePrefix = document.getElementById("verifyNotePrefix");
    const verifyOtpLabel = document.getElementById("authVerifyOtpLabel");
    const verifyOtpInput = document.getElementById("authVerifyOtp");
    const verifyOtpBtn = document.getElementById("verifyOtpBtn");
    const backAuthBtn = document.getElementById("backAuthBtn");

    const loggedHeadline = document.getElementById("authLoggedHeadline");
    const loggedFullNameLabel = document.getElementById("loggedFullNameLabel");
    const loggedEmailLabel = document.getElementById("loggedEmailLabel");
    const logoutUserBtn = document.getElementById("logoutUserBtn");

    if (authTabSignin) authTabSignin.textContent = tr("sign_in");
    if (authTabSignup) authTabSignup.textContent = tr("sign_up");

    if (signinHeadline) signinHeadline.textContent = tr("welcome_back");
    if (signinNote) signinNote.textContent = tr("welcome_note");
    if (signinEmailLabel) signinEmailLabel.textContent = tr("email");
    if (signinEmailInput) signinEmailInput.placeholder = tr("email_placeholder");
    if (sendSigninOtpBtn) {
      sendSigninOtpBtn.textContent = tr("send_verification_code");
      sendSigninOtpBtn.dataset.originalText = tr("send_verification_code");
    }

    if (signupHeadline) signupHeadline.textContent = tr("create_account");
    if (signupNote) signupNote.textContent = tr("create_note");
    if (signupNameLabel) signupNameLabel.textContent = tr("full_name");
    if (signupNameInput) signupNameInput.placeholder = tr("full_name_placeholder");
    if (signupCountryCodeLabel) signupCountryCodeLabel.textContent = tr("country_code");
    if (signupWhatsappLabel) signupWhatsappLabel.textContent = tr("whatsapp_number");
    if (signupWhatsappInput) signupWhatsappInput.placeholder = tr("whatsapp_placeholder");
    if (signupEmailLabel) signupEmailLabel.textContent = tr("email");
    if (signupEmailInput) signupEmailInput.placeholder = tr("email_placeholder");
    if (sendSignupOtpBtn) {
      sendSignupOtpBtn.textContent = tr("send_verification_code");
      sendSignupOtpBtn.dataset.originalText = tr("send_verification_code");
    }

    if (verifyHeadline) verifyHeadline.textContent = tr("verify_code_title");
    if (verifyNotePrefix) verifyNotePrefix.textContent = tr("verify_note_prefix");
    if (verifyOtpLabel) verifyOtpLabel.textContent = tr("verification_code");
    if (verifyOtpInput) verifyOtpInput.placeholder = tr("verification_code_placeholder");
    if (verifyOtpBtn) {
      verifyOtpBtn.textContent = tr("verify_code");
      verifyOtpBtn.dataset.originalText = tr("verify_code");
    }
    if (backAuthBtn) {
      backAuthBtn.textContent = tr("back");
      backAuthBtn.dataset.originalText = tr("back");
    }

    if (loggedHeadline) loggedHeadline.textContent = tr("signed_in_successfully");
    if (loggedFullNameLabel) loggedFullNameLabel.textContent = tr("full_name");
    if (loggedEmailLabel) loggedEmailLabel.textContent = tr("email");
    if (logoutUserBtn) {
      logoutUserBtn.textContent = tr("sign_out");
      logoutUserBtn.dataset.originalText = tr("sign_out");
    }

    setTopAuthLabel(localUser && localUser.email ? (localUser.full_name || localUser.email || tr("customer")) : tr("login"));
  }

  async function refreshCustomerStatus() {
    try {
      const data = await getStatus();

      if (data.logged_in && data.customer) {
        const user = {
          name: data.customer.full_name || data.customer.email || tr("customer"),
          email: data.customer.email || "",
          full_name: data.customer.full_name || "",
          id: data.customer.id || null,
          method: "email_otp"
        };

        setLocalUser(user);
        setTopAuthLabel(user.full_name || user.email || tr("customer"));
        showLoggedView(user);

        if (typeof window.syncOrdersFromServer === "function") {
          await window.syncOrdersFromServer();
        }
      } else {
        clearLocalUser();
        setTopAuthLabel(tr("login"));
        switchAuthTab("signin");

        if (typeof window.syncOrdersFromServer === "function") {
          await window.syncOrdersFromServer();
        }
      }

      applyAuthTranslations();
    } catch (e) {
      console.error("Status error:", e);
    }
  }

  async function sendSigninCode() {
    const btn = document.getElementById("sendSigninOtpBtn");
    const email = document.getElementById("authSigninEmail")?.value.trim() || "";

    if (!email) {
      setMessage(tr("enter_email_first"), "error");
      return;
    }

    setButtonLoading(btn, true, tr("sending"), tr("send_verification_code"));
    setMessage(tr("sending_verification_code"), "info");

    try {
      const data = await postForm("/auth/send-otp.php", {
        mode: "signin",
        email
      });

      showVerifyView("signin", email);
      setMessage(data.message || tr("verification_code_sent"), "success");
    } catch (e) {
      setMessage(e.message || tr("request_failed"), "error");
    } finally {
      setButtonLoading(btn, false, tr("sending"), tr("send_verification_code"));
    }
  }

  async function sendSignupCode() {
    const btn = document.getElementById("sendSignupOtpBtn");
    const full_name = document.getElementById("authSignupName")?.value.trim() || "";
    const country_code = document.getElementById("authSignupCountryCode")?.value.trim() || "";
    const whatsapp = document.getElementById("authSignupWhatsapp")?.value.trim() || "";
    const email = document.getElementById("authSignupEmail")?.value.trim() || "";

    if (!full_name || !country_code || !whatsapp || !email) {
      setMessage(tr("signup_required"), "error");
      return;
    }

    setButtonLoading(btn, true, tr("sending"), tr("send_verification_code"));
    setMessage(tr("sending_verification_code"), "info");

    try {
      const data = await postForm("/auth/send-otp.php", {
        mode: "signup",
        full_name,
        country_code,
        whatsapp,
        email
      });

      showVerifyView("signup", email);
      setMessage(data.message || tr("verification_code_sent"), "success");
    } catch (e) {
      setMessage(e.message || tr("request_failed"), "error");
    } finally {
      setButtonLoading(btn, false, tr("sending"), tr("send_verification_code"));
    }
  }

  async function verifyCode() {
    const btn = document.getElementById("verifyOtpBtn");
    const email = document.getElementById("authVerifyEmail")?.value.trim() || "";
    const otp = document.getElementById("authVerifyOtp")?.value.trim() || "";

    if (!email || !otp) {
      setMessage(tr("enter_email_and_code"), "error");
      return;
    }

    setButtonLoading(btn, true, tr("verifying"), tr("verify_code"));
    setMessage(tr("verifying_code"), "info");

    try {
      const data = await postForm("/auth/verify-otp.php", {
        email,
        otp
      });

      if (data.customer) {
        const user = {
          name: data.customer.full_name || data.customer.email || tr("customer"),
          email: data.customer.email || "",
          full_name: data.customer.full_name || "",
          id: data.customer.id || null,
          method: verifyMode || "email_otp"
        };

        setLocalUser(user);
        setTopAuthLabel(user.full_name || user.email || tr("customer"));
        showLoggedView(user);
      }

      setMessage(data.message || tr("verification_completed"), "success");

      if (typeof window.showToast === "function") {
        window.showToast(verifyMode === "signin" ? tr("signed_in_successfully") : tr("verification_completed"));
      }

      if (typeof window.syncOrdersFromServer === "function") {
        await window.syncOrdersFromServer();
      }

      setTimeout(function () {
        if (typeof window.closeAuthModal === "function") {
          window.closeAuthModal();
        }
      }, 700);
    } catch (e) {
      setMessage(e.message || tr("verification_failed"), "error");
    } finally {
      setButtonLoading(btn, false, tr("verifying"), tr("verify_code"));
    }
  }

  function ensureRealAuthUI() {
    const authBox = document.querySelector(".auth-box-global");
    if (!authBox) return;

    let wrapper = document.getElementById("realCustomerAuthBox");

    if (!wrapper) {
      wrapper = document.createElement("div");
      wrapper.id = "realCustomerAuthBox";
      wrapper.className = "customer-auth-shell";
      wrapper.innerHTML = `
        <div class="customer-auth-tabs">
          <button type="button" id="authTabSignin" class="customer-auth-tab active">${tr("sign_in")}</button>
          <button type="button" id="authTabSignup" class="customer-auth-tab">${tr("sign_up")}</button>
        </div>

        <div id="authSigninView" class="customer-auth-view">
          <div class="customer-auth-headline" id="authSigninHeadline">${tr("welcome_back")}</div>
          <div class="customer-auth-note" id="authSigninNote">
            ${tr("welcome_note")}
          </div>

          <div class="customer-auth-field">
            <label for="authSigninEmail" id="authSigninEmailLabel">${tr("email")}</label>
            <input
              type="email"
              id="authSigninEmail"
              class="customer-auth-input"
              placeholder="${tr("email_placeholder")}"
              autocomplete="email"
              inputmode="email"
            >
          </div>

          <button type="button" id="sendSigninOtpBtn" class="customer-auth-btn customer-auth-btn-primary">
            ${tr("send_verification_code")}
          </button>
        </div>

        <div id="authSignupView" class="customer-auth-view" style="display:none;">
          <div class="customer-auth-headline" id="authSignupHeadline">${tr("create_account")}</div>
          <div class="customer-auth-note" id="authSignupNote">
            ${tr("create_note")}
          </div>

          <div class="customer-auth-field">
            <label for="authSignupName" id="authSignupNameLabel">${tr("full_name")}</label>
            <input
              type="text"
              id="authSignupName"
              class="customer-auth-input"
              placeholder="${tr("full_name_placeholder")}"
              autocomplete="name"
            >
          </div>

          <div class="customer-auth-row">
            <div class="customer-auth-field customer-auth-country">
              <label for="authSignupCountryCode" id="authSignupCountryCodeLabel">${tr("country_code")}</label>
              <select id="authSignupCountryCode" class="customer-auth-select"></select>
            </div>

            <div class="customer-auth-field customer-auth-phone">
              <label for="authSignupWhatsapp" id="authSignupWhatsappLabel">${tr("whatsapp_number")}</label>
              <input
                type="text"
                id="authSignupWhatsapp"
                class="customer-auth-input"
                placeholder="${tr("whatsapp_placeholder")}"
                inputmode="tel"
                autocomplete="tel"
              >
            </div>
          </div>

          <div class="customer-auth-field">
            <label for="authSignupEmail" id="authSignupEmailLabel">${tr("email")}</label>
            <input
              type="email"
              id="authSignupEmail"
              class="customer-auth-input"
              placeholder="${tr("email_placeholder")}"
              autocomplete="email"
              inputmode="email"
            >
          </div>

          <button type="button" id="sendSignupOtpBtn" class="customer-auth-btn customer-auth-btn-primary">
            ${tr("send_verification_code")}
          </button>
        </div>

        <div id="authVerifyView" class="customer-auth-view" style="display:none;">
          <div class="customer-auth-headline" id="authVerifyHeadline">${tr("verify_code_title")}</div>
          <div class="customer-auth-note">
            <span id="verifyNotePrefix">${tr("verify_note_prefix")}</span>
            <strong id="verifyEmailText"></strong>
          </div>

          <input type="hidden" id="authVerifyEmail">

          <div class="customer-auth-field">
            <label for="authVerifyOtp" id="authVerifyOtpLabel">${tr("verification_code")}</label>
            <input
              type="text"
              id="authVerifyOtp"
              class="customer-auth-input"
              placeholder="${tr("verification_code_placeholder")}"
              inputmode="numeric"
              autocomplete="one-time-code"
            >
          </div>

          <div class="customer-auth-verify-actions">
            <button type="button" id="verifyOtpBtn" class="customer-auth-btn customer-auth-btn-success">
              ${tr("verify_code")}
            </button>

            <button type="button" id="backAuthBtn" class="customer-auth-btn customer-auth-btn-secondary">
              ${tr("back")}
            </button>
          </div>
        </div>

        <div id="authLoggedView" class="customer-auth-view" style="display:none;">
          <div class="customer-auth-headline" id="authLoggedHeadline">${tr("signed_in_successfully")}</div>

          <div class="customer-auth-user-card">
            <div class="customer-auth-user-line">
              <span class="customer-auth-user-label" id="loggedFullNameLabel">${tr("full_name")}</span>
              <strong id="loggedCustomerName">-</strong>
            </div>

            <div class="customer-auth-user-line">
              <span class="customer-auth-user-label" id="loggedEmailLabel">${tr("email")}</span>
              <strong id="loggedCustomerEmail">-</strong>
            </div>
          </div>

          <button type="button" id="logoutUserBtn" class="customer-auth-btn customer-auth-btn-danger">
            ${tr("sign_out")}
          </button>
        </div>

        <div id="customerAuthMessage" class="customer-auth-message"></div>
      `;
      authBox.appendChild(wrapper);
    }

    buildCountryOptions(document.getElementById("authSignupCountryCode"));

    const signinBtn = document.getElementById("authTabSignin");
    const signupBtn = document.getElementById("authTabSignup");
    const sendSigninOtpBtn = document.getElementById("sendSigninOtpBtn");
    const sendSignupOtpBtn = document.getElementById("sendSignupOtpBtn");
    const verifyOtpBtn = document.getElementById("verifyOtpBtn");
    const backAuthBtn = document.getElementById("backAuthBtn");
    const logoutUserBtn = document.getElementById("logoutUserBtn");

    if (signinBtn && !signinBtn.dataset.bound) {
      signinBtn.dataset.bound = "1";
      signinBtn.addEventListener("click", function () {
        switchAuthTab("signin");
      });
    }

    if (signupBtn && !signupBtn.dataset.bound) {
      signupBtn.dataset.bound = "1";
      signupBtn.addEventListener("click", function () {
        switchAuthTab("signup");
      });
    }

    if (sendSigninOtpBtn && !sendSigninOtpBtn.dataset.bound) {
      sendSigninOtpBtn.dataset.bound = "1";
      sendSigninOtpBtn.addEventListener("click", sendSigninCode);
    }

    if (sendSignupOtpBtn && !sendSignupOtpBtn.dataset.bound) {
      sendSignupOtpBtn.dataset.bound = "1";
      sendSignupOtpBtn.addEventListener("click", sendSignupCode);
    }

    if (verifyOtpBtn && !verifyOtpBtn.dataset.bound) {
      verifyOtpBtn.dataset.bound = "1";
      verifyOtpBtn.addEventListener("click", verifyCode);
    }

    if (backAuthBtn && !backAuthBtn.dataset.bound) {
      backAuthBtn.dataset.bound = "1";
      backAuthBtn.addEventListener("click", backToTabs);
    }

    if (logoutUserBtn && !logoutUserBtn.dataset.bound) {
      logoutUserBtn.dataset.bound = "1";
      logoutUserBtn.addEventListener("click", window.logoutUser);
    }

    const localUser = getCurrentLocalUser();
    setTopAuthLabel(localUser && localUser.email ? (localUser.full_name || localUser.email) : tr("login"));

    const signinEmailInput = document.getElementById("authSigninEmail");
    if (signinEmailInput && localUser && localUser.email && !signinEmailInput.value) {
      signinEmailInput.value = localUser.email;
    }

    applyAuthTranslations();

    if (localUser && localUser.email) {
      showLoggedView(localUser);
    } else {
      switchAuthTab(currentMode);
    }
  }

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

      clearLocalUser();
      setTopAuthLabel(tr("login"));
      switchAuthTab("signin");
      applyAuthTranslations();

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

  window.initAuthModal = async function () {
    ensureRealAuthUI();
    applyAuthTranslations();
    await refreshCustomerStatus();
  };

  document.addEventListener("DOMContentLoaded", function () {
    const timer = setInterval(async function () {
      const modal = document.getElementById("authModalGlobal");
      if (modal) {
        ensureRealAuthUI();
        applyAuthTranslations();
        await refreshCustomerStatus();
        clearInterval(timer);
      }
    }, 250);
  });

  document.addEventListener("customer-auth-opened", function () {
    ensureRealAuthUI();
    applyAuthTranslations();
  });

  document.addEventListener("site-language-changed", function () {
    ensureRealAuthUI();
    applyAuthTranslations();
  });
})();
