(function () {
  "use strict";

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

    if (!res.ok) {
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

  function setLabel(value) {
    const mobileLabel = document.getElementById("mobileAuthLabel");
    const authBoxName = document.getElementById("authUserBoxName");
    const userBox = document.getElementById("authUserBoxGlobal");
    const phoneBox = document.getElementById("authPhoneBoxGlobal");
    const customBox = document.getElementById("realCustomerAuthBox");

    const finalValue = value || "Registration";

    if (mobileLabel) mobileLabel.textContent = finalValue;
    if (authBoxName) authBoxName.textContent = value || "";

    if (userBox && value) userBox.style.display = "";
    if (phoneBox) phoneBox.style.display = "none";
    if (customBox && value) customBox.style.display = "none";
  }

  async function refreshCustomerStatus() {
    try {
      const data = await getStatus();

      if (data.logged_in && data.customer) {
        setLabel(data.customer.email || "Customer");
      } else {
        setLabel("");
      }
    } catch (e) {
      console.error("Status error:", e);
    }
  }

  function hideOldAuthChoices() {
    const authBox = document.querySelector(".auth-box-global");
    if (!authBox) return;

    const subtitle = authBox.querySelector(".auth-subtitle-global");
    if (subtitle) {
      subtitle.style.display = "none";
    }

    const optionButtons = authBox.querySelectorAll(".auth-option-global");
    optionButtons.forEach(function (btn) {
      btn.style.display = "none";
    });

    const phoneBox = document.getElementById("authPhoneBoxGlobal");
    if (phoneBox) {
      phoneBox.style.display = "none";
    }

    const userBox = document.getElementById("authUserBoxGlobal");
    if (userBox) {
      userBox.style.display = "none";
    }

    const allButtons = authBox.querySelectorAll("button");
    allButtons.forEach(function (btn) {
      const text = (btn.textContent || "").trim().toLowerCase();

      if (
        text.includes("continue with gmail") ||
        text.includes("continue with phone number")
      ) {
        btn.style.display = "none";
      }
    });

    const allDivs = authBox.querySelectorAll("div");
    allDivs.forEach(function (el) {
      const text = (el.textContent || "").trim().toLowerCase();

      if (
        text === "continue with gmail" ||
        text === "continue with phone number" ||
        text.includes("choose how you want to register")
      ) {
        el.style.display = "none";
      }
    });
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

  function ensureRealAuthUI() {
    const authBox = document.querySelector(".auth-box-global");
    if (!authBox) return;

    hideOldAuthChoices();

    let wrapper = document.getElementById("realCustomerAuthBox");

    if (!wrapper) {
      wrapper = document.createElement("div");
      wrapper.id = "realCustomerAuthBox";
      wrapper.style.display = "block";
      wrapper.innerHTML = `
        <div style="margin-top:12px;">
          <input
            type="text"
            id="realAuthName"
            placeholder="Full name"
            style="width:100%;height:46px;border-radius:12px;border:1px solid #e5e7eb;padding:0 12px;margin-top:10px;"
          >

          <div style="display:flex;gap:8px;margin-top:10px;">
            <select
              id="realAuthCountryCode"
              style="width:38%;height:46px;border-radius:12px;border:1px solid #e5e7eb;padding:0 10px;background:#fff;"
            ></select>

            <input
              type="text"
              id="realAuthWhatsappNumber"
              placeholder="WhatsApp number"
              style="width:62%;height:46px;border-radius:12px;border:1px solid #e5e7eb;padding:0 12px;"
            >
          </div>

          <input
            type="email"
            id="realAuthEmail"
            placeholder="Email address"
            style="width:100%;height:46px;border-radius:12px;border:1px solid #e5e7eb;padding:0 12px;margin-top:10px;"
          >

          <button
            type="button"
            id="sendOtpBtn"
            style="width:100%;min-height:48px;border-radius:14px;border:0;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;font-weight:800;cursor:pointer;margin-top:10px;"
          >
            Send Verification Code
          </button>

          <div id="otpSection" style="display:none;">
            <input
              type="text"
              id="realAuthOtp"
              placeholder="Enter OTP code"
              style="width:100%;height:46px;border-radius:12px;border:1px solid #e5e7eb;padding:0 12px;margin-top:10px;"
            >

            <button
              type="button"
              id="verifyOtpBtn"
              style="width:100%;min-height:48px;border-radius:14px;border:0;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;font-weight:800;cursor:pointer;margin-top:10px;"
            >
              Verify Code
            </button>
          </div>

          <div id="realAuthMsg" style="margin-top:10px;font-size:14px;color:#475569;"></div>
        </div>
      `;

      authBox.appendChild(wrapper);
    }

    buildCountryOptions(document.getElementById("realAuthCountryCode"));

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

        if (msg) {
          msg.style.color = "#475569";
          msg.textContent = "Verifying code...";
        }

        try {
          const data = await postForm("/auth/verify-otp.php", { email, otp });

          if (msg) {
            msg.style.color = "#16a34a";
            msg.textContent = data.message || "Verification completed successfully.";
          }

          await refreshCustomerStatus();
          setLabel((data.customer && data.customer.email) || "Customer");

          if (typeof window.showToast === "function") {
            window.showToast("Registration completed");
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
      setLabel("");

      if (typeof window.showToast === "function") {
        window.showToast("Signed out");
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
        hideOldAuthChoices();
        ensureRealAuthUI();
        clearInterval(timer);
      }
    }, 300);

    refreshCustomerStatus();
  });
})();
