(function () {
  "use strict";

  const USER_STORAGE_KEY = "click_company_user_v2";

  let currentMode = "signin";
  let verifyMode = "";
  let verifyEmail = "";

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
    const body = new URLSearchParams(data);

    const res = await fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
      },
      body,
      cache: "no-store"
    });

    const raw = await res.text();

    let json;
    try {
      json = JSON.parse(raw);
    } catch (e) {
      throw new Error(raw || "Unexpected server response.");
    }

    if (!res.ok || !json.ok) {
      throw new Error(json.message || "Request failed");
    }

    return json;
  }

  async function getStatus() {
    const res = await fetch("/auth/status.php", { cache: "no-store" });
    const raw = await res.text();

    try {
      return JSON.parse(raw);
    } catch (e) {
      throw new Error(raw || "Invalid status response.");
    }
  }

  function setTopAuthLabel(value) {
    const mobileLabel = document.getElementById("mobileAuthLabel");
    const authUserName = document.getElementById("authUserBoxName");
    const userBox = document.getElementById("authUserBoxGlobal");

    const finalValue = value || "Registration";

    if (mobileLabel) {
      mobileLabel.textContent = finalValue;
    }

    if (authUserName) {
      authUserName.textContent = value || "";
    }

    if (userBox) {
      userBox.style.display = value ? "" : "none";
    }
  }

  function hideOldAuthChoices() {
    const authBox = document.querySelector(".auth-box-global");
    if (!authBox) return;

    const subtitle = authBox.querySelector(".auth-subtitle-global");
    if (subtitle) {
      subtitle.style.display = "none";
    }

    authBox.querySelectorAll(".auth-option-global").forEach(function (el) {
      el.style.display = "none";
    });

    const phoneBox = document.getElementById("authPhoneBoxGlobal");
    if (phoneBox) {
      phoneBox.style.display = "none";
    }
  }

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

  async function refreshCustomerStatus() {
    try {
      const data = await getStatus();

      if (data.logged_in && data.customer) {
        const user = {
          name: data.customer.email || "Customer",
          email: data.customer.email || "",
          full_name: data.customer.full_name || "",
          id: data.customer.id || null,
          method: "email_otp"
        };

        setLocalUser(user);
        setTopAuthLabel(user.email || "Customer");
        showLoggedView(user);

        if (typeof window.syncOrdersFromServer === "function") {
          await window.syncOrdersFromServer();
        }
      } else {
        clearLocalUser();
        setTopAuthLabel("");
        switchAuthTab("signin");

        if (typeof window.syncOrdersFromServer === "function") {
          await window.syncOrdersFromServer();
        }
      }
    } catch (e) {
      console.error("Status error:", e);
    }
  }

  async function sendSigninCode() {
    const email = document.getElementById("authSigninEmail")?.value.trim() || "";

    if (!email) {
      setMessage("Enter your email first.", "error");
      return;
    }

    setMessage("Sending verification code...", "info");

    try {
      const data = await postForm("/auth/send-otp.php", {
        mode: "signin",
        email
      });

      showVerifyView("signin", email);
      setMessage(data.message || "Verification code sent to your email.", "success");
    } catch (e) {
      setMessage(e.message || "Failed to send verification code.", "error");
    }
  }

  async function sendSignupCode() {
    const full_name = document.getElementById("authSignupName")?.value.trim() || "";
    const country_code = document.getElementById("authSignupCountryCode")?.value.trim() || "";
    const whatsapp = document.getElementById("authSignupWhatsapp")?.value.trim() || "";
    const email = document.getElementById("authSignupEmail")?.value.trim() || "";

    if (!full_name || !country_code || !whatsapp || !email) {
      setMessage("Full name, WhatsApp number and email are required.", "error");
      return;
    }

    setMessage("Sending verification code...", "info");

    try {
      const data = await postForm("/auth/send-otp.php", {
        mode: "signup",
        full_name,
        country_code,
        whatsapp,
        email
      });

      showVerifyView("signup", email);
      setMessage(data.message || "Verification code sent to your email.", "success");
    } catch (e) {
      setMessage(e.message || "Failed to send verification code.", "error");
    }
  }

  async function verifyCode() {
    const email = document.getElementById("authVerifyEmail")?.value.trim() || "";
    const otp = document.getElementById("authVerifyOtp")?.value.trim() || "";

    if (!email || !otp) {
      setMessage("Enter email and verification code.", "error");
      return;
    }

    setMessage("Verifying code...", "info");

    try {
      const data = await postForm("/auth/verify-otp.php", {
        email,
        otp
      });

      if (data.customer) {
        const user = {
          name: data.customer.email || "Customer",
          email: data.customer.email || "",
          full_name: data.customer.full_name || "",
          id: data.customer.id || null,
          method: verifyMode || "email_otp"
        };

        setLocalUser(user);
        setTopAuthLabel(user.email || "Customer");
        showLoggedView(user);
      }

      setMessage(data.message || "Verification completed successfully.", "success");

      if (typeof window.showToast === "function") {
        window.showToast(verifyMode === "signin" ? "Signed in successfully" : "Registration completed");
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
      setMessage(e.message || "Verification failed.", "error");
    }
  }

  function ensureRealAuthUI() {
    const authBox = document.querySelector(".auth-box-global");
    if (!authBox) return;

    hideOldAuthChoices();

    let wrapper = document.getElementById("realCustomerAuthBox");

    if (!wrapper) {
      wrapper = document.createElement("div");
      wrapper.id = "realCustomerAuthBox";
      wrapper.className = "customer-auth-shell";
      wrapper.innerHTML = `
        <div class="customer-auth-tabs">
          <button type="button" id="authTabSignin" class="customer-auth-tab active">Sign In</button>
          <button type="button" id="authTabSignup" class="customer-auth-tab">Sign Up</button>
        </div>

        <div id="authSigninView" class="customer-auth-view">
          <div class="customer-auth-headline">Welcome back</div>
          <div class="customer-auth-note">
            Enter your email, then check your inbox for the verification code.
          </div>

          <div class="customer-auth-field">
            <label for="authSigninEmail">Email</label>
            <input
              type="email"
              id="authSigninEmail"
              class="customer-auth-input"
              placeholder="Email address"
              autocomplete="email"
              inputmode="email"
            >
          </div>

          <button type="button" id="sendSigninOtpBtn" class="customer-auth-btn customer-auth-btn-primary">
            Send Verification Code
          </button>
        </div>

        <div id="authSignupView" class="customer-auth-view" style="display:none;">
          <div class="customer-auth-headline">Create your account</div>
          <div class="customer-auth-note">
            Full name and WhatsApp number are required during registration.
          </div>

          <div class="customer-auth-field">
            <label for="authSignupName">Full name</label>
            <input
              type="text"
              id="authSignupName"
              class="customer-auth-input"
              placeholder="Full name"
              autocomplete="name"
            >
          </div>

          <div class="customer-auth-row">
            <div class="customer-auth-field customer-auth-country">
              <label for="authSignupCountryCode">Country code</label>
              <select id="authSignupCountryCode" class="customer-auth-select"></select>
            </div>

            <div class="customer-auth-field customer-auth-phone">
              <label for="authSignupWhatsapp">WhatsApp number</label>
              <input
                type="text"
                id="authSignupWhatsapp"
                class="customer-auth-input"
                placeholder="WhatsApp number"
                inputmode="tel"
                autocomplete="tel"
              >
            </div>
          </div>

          <div class="customer-auth-field">
            <label for="authSignupEmail">Email</label>
            <input
              type="email"
              id="authSignupEmail"
              class="customer-auth-input"
              placeholder="Email address"
              autocomplete="email"
              inputmode="email"
            >
          </div>

          <button type="button" id="sendSignupOtpBtn" class="customer-auth-btn customer-auth-btn-primary">
            Send Verification Code
          </button>
        </div>

        <div id="authVerifyView" class="customer-auth-view" style="display:none;">
          <div class="customer-auth-headline">Verify code</div>
          <div class="customer-auth-note">
            We sent a verification code to:
            <strong id="verifyEmailText"></strong>
          </div>

          <input type="hidden" id="authVerifyEmail">

          <div class="customer-auth-field">
            <label for="authVerifyOtp">Verification code</label>
            <input
              type="text"
              id="authVerifyOtp"
              class="customer-auth-input"
              placeholder="Enter verification code"
              inputmode="numeric"
              autocomplete="one-time-code"
            >
          </div>

          <div class="customer-auth-verify-actions">
            <button type="button" id="verifyOtpBtn" class="customer-auth-btn customer-auth-btn-success">
              Verify Code
            </button>

            <button type="button" id="backAuthBtn" class="customer-auth-btn customer-auth-btn-secondary">
              Back
            </button>
          </div>
        </div>

        <div id="authLoggedView" class="customer-auth-view" style="display:none;">
          <div class="customer-auth-headline">Signed in successfully</div>

          <div class="customer-auth-user-card">
            <div class="customer-auth-user-line">
              <span class="customer-auth-user-label">Full name</span>
              <strong id="loggedCustomerName">-</strong>
            </div>

            <div class="customer-auth-user-line">
              <span class="customer-auth-user-label">Email</span>
              <strong id="loggedCustomerEmail">-</strong>
            </div>
          </div>

          <button type="button" id="logoutUserBtn" class="customer-auth-btn customer-auth-btn-danger">
            Sign Out
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

    const user = getCurrentLocalUser();
    setTopAuthLabel(user && user.email ? user.email : "");

    if (user && user.email) {
      showLoggedView(user);
    } else {
      switchAuthTab(currentMode);
    }
  }

  window.logoutUser = async function () {
    try {
      await fetch("/auth/logout.php", { cache: "no-store" });
      clearLocalUser();
      setTopAuthLabel("");
      switchAuthTab("signin");

      if (typeof window.showToast === "function") {
        window.showToast("Signed out");
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

  window.initAuthModal = function () {
    ensureRealAuthUI();
    refreshCustomerStatus();
  };

  document.addEventListener("DOMContentLoaded", function () {
    const timer = setInterval(function () {
      const modal = document.getElementById("authModalGlobal");
      if (modal) {
        ensureRealAuthUI();
        clearInterval(timer);
      }
    }, 250);

    refreshCustomerStatus();
  });

  document.addEventListener("customer-auth-opened", ensureRealAuthUI);
})();
