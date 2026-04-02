(function () {
  "use strict";

  const USER_STORAGE_KEY = "click_company_user_v2";
  const VERIFY_TOKEN_STORAGE_KEY = "click_company_verify_token_v1";

  function injectAuthStyles() {
    if (document.getElementById("customer-auth-injected-style")) return;

    const style = document.createElement("style");
    style.id = "customer-auth-injected-style";
    style.textContent = `
      #realCustomerAuthBox,
      #realCustomerAuthBox * {
        box-sizing: border-box !important;
      }

      #realCustomerAuthBox {
        width: 100% !important;
        display: block !important;
        margin-top: 12px !important;
      }

      #realCustomerAuthBox .auth-real-input,
      #realCustomerAuthBox .auth-real-select,
      #realCustomerAuthBox .auth-real-button {
        width: 100% !important;
        min-width: 0 !important;
        font-family: inherit !important;
        border-radius: 14px !important;
        border: 1px solid #e5e7eb !important;
        outline: none !important;
        box-shadow: none !important;
        box-sizing: border-box !important;
      }

      #realCustomerAuthBox .auth-real-input,
      #realCustomerAuthBox .auth-real-select {
        height: 52px !important;
        padding: 0 14px !important;
        font-size: 16px !important;
        color: #111827 !important;
        background: #fff !important;
      }

      #realCustomerAuthBox .auth-real-row {
        display: flex !important;
        gap: 8px !important;
        align-items: stretch !important;
        width: 100% !important;
        margin-top: 10px !important;
      }

      #realCustomerAuthBox .auth-real-select-wrap {
        flex: 0 0 42% !important;
        min-width: 0 !important;
      }

      #realCustomerAuthBox .auth-real-number-wrap {
        flex: 1 1 auto !important;
        min-width: 0 !important;
      }

      #realCustomerAuthBox .auth-real-button {
        min-height: 50px !important;
        border: 0 !important;
        color: #fff !important;
        font-size: 16px !important;
        font-weight: 800 !important;
        cursor: pointer !important;
      }

      #realCustomerAuthBox .auth-real-send {
        background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
      }

      #realCustomerAuthBox .auth-real-verify {
        background: linear-gradient(135deg, #22c55e, #16a34a) !important;
      }

      #realCustomerAuthBox .auth-real-msg {
        margin-top: 10px !important;
        font-size: 14px !important;
        line-height: 1.6 !important;
      }

      @media (max-width: 640px) {
        #realCustomerAuthBox .auth-real-row {
          flex-direction: row !important;
        }

        #realCustomerAuthBox .auth-real-select-wrap {
          flex-basis: 44% !important;
        }
      }
    `;
    document.head.appendChild(style);
  }

  function setLocalUser(user) {
    localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(user));
    document.dispatchEvent(new CustomEvent("customer-auth-updated", { detail: user }));
  }

  function clearLocalUser() {
    localStorage.removeItem(USER_STORAGE_KEY);
    document.dispatchEvent(new CustomEvent("customer-auth-updated", { detail: null }));
  }

  async function postForm(url, data) {
    const body = new URLSearchParams(data);

    const res = await fetch(url, {
  method: "POST",
  headers: {
    "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
  },
  body,
  cache: "no-store",
  credentials: "same-origin"
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
    const res = await fetch("/auth/status.php", {
  cache: "no-store",
  credentials: "same-origin"
});
    const raw = await res.text();

    try {
      return JSON.parse(raw);
    } catch (e) {
      throw new Error(raw || "Invalid status response.");
    }
  }

  function setLabel(value) {
    const mobileLabel = document.getElementById("mobileAuthLabel");
    const authBoxName = document.getElementById("authUserBoxName");
    const userBox = document.getElementById("authUserBoxGlobal");
    const customBox = document.getElementById("realCustomerAuthBox");

    const finalValue = value || "Registration";

    if (mobileLabel) mobileLabel.textContent = finalValue;
    if (authBoxName) authBoxName.textContent = value || "";

    if (userBox) userBox.style.display = value ? "" : "none";
    if (customBox) customBox.style.display = value ? "none" : "";
  }

  function hideOldAuthChoices() {
    const authBox = document.querySelector(".auth-box-global");
    if (!authBox) return;

    const subtitle = authBox.querySelector(".auth-subtitle-global");
    if (subtitle) {
      subtitle.textContent = "Register with your email and WhatsApp number.";
      subtitle.style.display = "block";
    }

    authBox.querySelectorAll(".auth-option-global").forEach(el => {
      el.style.display = "none";
    });

    const phoneBox = document.getElementById("authPhoneBoxGlobal");
    if (phoneBox) phoneBox.style.display = "none";
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

  function getCurrentLocalUser() {
    try {
      return JSON.parse(localStorage.getItem(USER_STORAGE_KEY) || "null");
    } catch {
      return null;
    }
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
        setLabel(user.email || "Customer");

        if (typeof window.syncOrdersFromServer === "function") {
          await window.syncOrdersFromServer();
        }
      } else {
        clearLocalUser();
        setLabel("");

        if (typeof window.syncOrdersFromServer === "function") {
          await window.syncOrdersFromServer();
        }
      }
    } catch (e) {
      console.error("Status error:", e);
    }
  }

  function ensureRealAuthUI() {
    injectAuthStyles();

    const authBox = document.querySelector(".auth-box-global");
    if (!authBox) return;

    hideOldAuthChoices();

    let wrapper = document.getElementById("realCustomerAuthBox");

    if (!wrapper) {
      wrapper = document.createElement("div");
      wrapper.id = "realCustomerAuthBox";
      wrapper.innerHTML = `
        <input
          type="text"
          id="realAuthName"
          class="auth-real-input"
          placeholder="Full name"
        >

        <div class="auth-real-row">
          <div class="auth-real-select-wrap">
            <select
              id="realAuthCountryCode"
              class="auth-real-select"
            ></select>
          </div>

          <div class="auth-real-number-wrap">
            <input
              type="text"
              id="realAuthWhatsappNumber"
              class="auth-real-input"
              placeholder="WhatsApp number"
            >
          </div>
        </div>

        <input
          type="email"
          id="realAuthEmail"
          class="auth-real-input"
          placeholder="Email address"
          style="margin-top:10px;"
        >

        <button
          type="button"
          id="sendOtpBtn"
          class="auth-real-button auth-real-send"
          style="margin-top:10px;"
        >
          Send Verification Code
        </button>

        <div id="otpSection" style="display:none;">
          <input
            type="text"
            id="realAuthOtp"
            class="auth-real-input"
            placeholder="Enter OTP code"
            style="margin-top:10px;"
          >

          <button
            type="button"
            id="verifyOtpBtn"
            class="auth-real-button auth-real-verify"
            style="margin-top:10px;"
          >
            Verify Code
          </button>
        </div>

        <div id="realAuthMsg" class="auth-real-msg"></div>
      `;
      authBox.appendChild(wrapper);
    }

    buildCountryOptions(document.getElementById("realAuthCountryCode"));

    const user = getCurrentLocalUser();
    setLabel(user && user.email ? user.email : "");

    const sendBtn = document.getElementById("sendOtpBtn");
    const verifyBtn = document.getElementById("verifyOtpBtn");

    if (sendBtn && !sendBtn.dataset.bound) {
      sendBtn.dataset.bound = "1";

      sendBtn.addEventListener("click", async function () {
        const full_name = document.getElementById("realAuthName")?.value.trim() || "";
        const country_code = document.getElementById("realAuthCountryCode")?.value.trim() || "";
        const whatsapp_number = document.getElementById("realAuthWhatsappNumber")?.value.trim() || "";
        const email = document.getElementById("realAuthEmail")?.value.trim() || "";
        const msg = document.getElementById("realAuthMsg");
        const otpSection = document.getElementById("otpSection");

        if (!full_name || !country_code || !whatsapp_number || !email) {
          if (msg) {
            msg.style.color = "#dc2626";
            msg.textContent = "Fill all fields first.";
          }
          return;
        }

        if (msg) {
          msg.style.color = "#475569";
          msg.textContent = "Sending verification code...";
        }

        try {
          const data = await postForm("/auth/send-otp.php", {
  full_name,
  email,
  country_code,
  whatsapp: whatsapp_number
});

if (data.verification_token) {
  localStorage.setItem(VERIFY_TOKEN_STORAGE_KEY, data.verification_token);
}

if (msg) {
  msg.style.color = "#16a34a";
  msg.textContent = data.message || "Verification code sent.";
}

if (otpSection) {
  otpSection.style.display = "block";
}
          
        } catch (e) {
          if (msg) {
            msg.style.color = "#dc2626";
            msg.textContent = e.message || "Failed to send verification code.";
          }
        }
      });
    }

    if (verifyBtn && !verifyBtn.dataset.bound) {
      verifyBtn.dataset.bound = "1";

      verifyBtn.addEventListener("click", async function () {
        const email = document.getElementById("realAuthEmail")?.value.trim() || "";
        const otp = document.getElementById("realAuthOtp")?.value.trim() || "";
        const msg = document.getElementById("realAuthMsg");

        if (!email || !otp) {
          if (msg) {
            msg.style.color = "#dc2626";
            msg.textContent = "Enter email and OTP code.";
          }
          return;
        }

        if (msg) {
          msg.style.color = "#475569";
          msg.textContent = "Verifying code...";
        }

        try {
          const verification_token = localStorage.getItem(VERIFY_TOKEN_STORAGE_KEY) || "";

          const data = await postForm("/auth/verify-otp.php", {
           email,
           otp,
           verification_token
        });

          if (msg) {
            msg.style.color = "#16a34a";
            msg.textContent = data.message || "Verification completed successfully.";
          }

          if (data.customer) {
          localStorage.removeItem(VERIFY_TOKEN_STORAGE_KEY);

       setLocalUser({
        name: data.customer.email || "Customer",
        email: data.customer.email || "",
        full_name: data.customer.full_name || "",
        id: data.customer.id || null,
        method: "email_otp"
     });

  setLabel(data.customer.email || "Customer");
}

          if (typeof window.showToast === "function") {
            window.showToast("Registration completed");
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
          if (msg) {
            msg.style.color = "#dc2626";
            msg.textContent = e.message || "Verification failed.";
          }
        }
      });
    }
  }

  window.logoutUser = async function () {
  try {
    await fetch("/auth/logout.php", { cache: "no-store" });
    clearLocalUser();
    localStorage.removeItem(VERIFY_TOKEN_STORAGE_KEY);
    setLabel("");

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
