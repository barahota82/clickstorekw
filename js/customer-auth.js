(function () {
  "use strict";

  async function postForm(url, data) {
    const body = new URLSearchParams(data);
    const res = await fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
      },
      body
    });

    const json = await res.json();
    if (!res.ok) {
      throw new Error(json.message || "Request failed");
    }
    return json;
  }

  async function getStatus() {
    const res = await fetch("/auth/status.php", { cache: "no-store" });
    return await res.json();
  }

  function setLabel(name) {
    const mobileLabel = document.getElementById("mobileAuthLabel");
    const authBoxName = document.getElementById("authUserBoxName");
    const userBox = document.getElementById("authUserBoxGlobal");
    const phoneBox = document.getElementById("authPhoneBoxGlobal");
    const customBox = document.getElementById("realCustomerAuthBox");

    if (mobileLabel) mobileLabel.textContent = name || "Registration";
    if (authBoxName) authBoxName.textContent = name || "";

    if (userBox && name) userBox.style.display = "";
    if (phoneBox) phoneBox.style.display = "none";
    if (customBox && name) customBox.style.display = "none";
  }

  async function refreshCustomerStatus() {
    try {
      const data = await getStatus();
      if (data.logged_in && data.customer) {
        setLabel(data.customer.full_name || "Customer");
      } else {
        setLabel("");
      }
    } catch (e) {
      console.error(e);
    }
  }

  function ensureRealAuthUI() {
    const authBox = document.querySelector(".auth-box-global");
    if (!authBox || document.getElementById("realCustomerAuthBox")) return;

    const wrapper = document.createElement("div");
    wrapper.id = "realCustomerAuthBox";
    wrapper.style.display = "none";
    wrapper.innerHTML = `
      <div style="margin-top:12px;">
        <input type="text" id="realAuthName" placeholder="Full name" style="width:100%;height:46px;border-radius:12px;border:1px solid #e5e7eb;padding:0 12px;margin-top:10px;">
        <input type="text" id="realAuthPhone" placeholder="Phone number" style="width:100%;height:46px;border-radius:12px;border:1px solid #e5e7eb;padding:0 12px;margin-top:10px;">
        <input type="email" id="realAuthEmail" placeholder="Email address" style="width:100%;height:46px;border-radius:12px;border:1px solid #e5e7eb;padding:0 12px;margin-top:10px;">
        <button type="button" id="sendOtpBtn" style="width:100%;min-height:48px;border-radius:14px;border:0;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;font-weight:800;cursor:pointer;margin-top:10px;">Send Verification Code</button>

        <div id="otpSection" style="display:none;">
          <input type="text" id="realAuthOtp" placeholder="Enter OTP code" style="width:100%;height:46px;border-radius:12px;border:1px solid #e5e7eb;padding:0 12px;margin-top:10px;">
          <button type="button" id="verifyOtpBtn" style="width:100%;min-height:48px;border-radius:14px;border:0;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;font-weight:800;cursor:pointer;margin-top:10px;">Verify Code</button>
        </div>

        <div id="realAuthMsg" style="margin-top:10px;font-size:14px;color:#475569;"></div>
      </div>
    `;

    const subtitle = authBox.querySelector(".auth-subtitle-global");
    if (subtitle) {
      subtitle.insertAdjacentElement("afterend", wrapper);
    } else {
      authBox.appendChild(wrapper);
    }

    const sendBtn = document.getElementById("sendOtpBtn");
    const verifyBtn = document.getElementById("verifyOtpBtn");

    sendBtn.addEventListener("click", async function () {
      const full_name = document.getElementById("realAuthName").value.trim();
      const phone = document.getElementById("realAuthPhone").value.trim();
      const email = document.getElementById("realAuthEmail").value.trim();
      const msg = document.getElementById("realAuthMsg");
      const otpSection = document.getElementById("otpSection");

      msg.textContent = "Sending verification code...";

      try {
        const data = await postForm("/auth/send-otp.php", { full_name, phone, email });
        msg.textContent = data.message;
        otpSection.style.display = "block";
      } catch (e) {
        msg.textContent = e.message;
      }
    });

    verifyBtn.addEventListener("click", async function () {
      const email = document.getElementById("realAuthEmail").value.trim();
      const otp = document.getElementById("realAuthOtp").value.trim();
      const msg = document.getElementById("realAuthMsg");

      msg.textContent = "Verifying code...";

      try {
        const data = await postForm("/auth/verify-otp.php", { email, otp });
        msg.textContent = data.message;
        setLabel(data.customer.full_name || "Customer");

        if (typeof window.showToast === "function") {
          window.showToast("Registration completed");
        }

        setTimeout(function () {
          if (typeof window.closeAuthModal === "function") {
            window.closeAuthModal();
          }
        }, 700);
      } catch (e) {
        msg.textContent = e.message;
      }
    });
  }

  function openRealAuthForm() {
    ensureRealAuthUI();

    const customBox = document.getElementById("realCustomerAuthBox");
    const phoneBox = document.getElementById("authPhoneBoxGlobal");
    const userBox = document.getElementById("authUserBoxGlobal");

    if (phoneBox) phoneBox.style.display = "none";
    if (userBox) userBox.style.display = "none";
    if (customBox) customBox.style.display = "block";
  }

  window.authWithGmail = function () {
    openRealAuthForm();
  };

  window.showPhoneRegister = function () {
    openRealAuthForm();
  };

  window.submitPhoneRegister = function () {
    openRealAuthForm();
  };

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
        ensureRealAuthUI();
        clearInterval(timer);
      }
    }, 300);

    refreshCustomerStatus();
  });
})();
