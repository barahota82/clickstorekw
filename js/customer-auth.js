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

  function setLabel(value) {
    const mobileLabel = document.getElementById("mobileAuthLabel");
    const authBoxName = document.getElementById("authUserBoxName");
    const userBox = document.getElementById("authUserBoxGlobal");
    const phoneBox = document.getElementById("authPhoneBoxGlobal");

    if (mobileLabel) mobileLabel.textContent = value || "Registration";
    if (authBoxName) authBoxName.textContent = value || "";

    if (userBox && value) userBox.style.display = "";
    if (phoneBox) phoneBox.style.display = "none";
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
      console.error(e);
    }
  }

  function ensureRealAuthUI() {
    const authBox = document.querySelector(".auth-box-global");
    if (!authBox || document.getElementById("realCustomerAuthBox")) return;

    const wrapper = document.createElement("div");
    wrapper.id = "realCustomerAuthBox";

    wrapper.innerHTML = `
      <div style="margin-top:12px;">

        <input type="text" id="realAuthName" placeholder="Full name" style="width:100%;height:46px;border-radius:12px;border:1px solid #e5e7eb;padding:0 12px;margin-top:10px;">

        <div style="display:flex;gap:8px;margin-top:10px;">
          <select id="realAuthCountryCode" style="width:35%;height:46px;border-radius:12px;border:1px solid #e5e7eb;padding:0 8px;"></select>

          <input type="text" id="realAuthWhatsapp" placeholder="WhatsApp number" style="width:65%;height:46px;border-radius:12px;border:1px solid #e5e7eb;padding:0 12px;">
        </div>

        <input type="email" id="realAuthEmail" placeholder="Email address" style="width:100%;height:46px;border-radius:12px;border:1px solid #e5e7eb;padding:0 12px;margin-top:10px;">

        <button type="button" id="sendOtpBtn" style="width:100%;min-height:48px;border-radius:14px;border:0;background:#2563eb;color:#fff;font-weight:800;margin-top:10px;">Send Verification Code</button>

        <div id="otpSection" style="display:none;">
          <input type="text" id="realAuthOtp" placeholder="Enter OTP code" style="width:100%;height:46px;border-radius:12px;border:1px solid #e5e7eb;padding:0 12px;margin-top:10px;">
          <button type="button" id="verifyOtpBtn" style="width:100%;min-height:48px;border-radius:14px;border:0;background:#22c55e;color:#fff;font-weight:800;margin-top:10px;">Verify Code</button>
        </div>

        <div id="realAuthMsg" style="margin-top:10px;font-size:14px;color:#475569;"></div>
      </div>
    `;

    authBox.appendChild(wrapper);

    // تحميل الدول
    const select = document.getElementById("realAuthCountryCode");
    window.COUNTRY_CODES.forEach(c => {
      const opt = document.createElement("option");
      opt.value = c.code;
      opt.textContent = `${c.flag} ${c.country} (${c.code})`;
      if (c.code === "+965") opt.selected = true;
      select.appendChild(opt);
    });

    document.getElementById("sendOtpBtn").addEventListener("click", async function () {
      const full_name = document.getElementById("realAuthName").value.trim();
      const country_code = document.getElementById("realAuthCountryCode").value;
      const whatsapp = document.getElementById("realAuthWhatsapp").value.trim();
      const email = document.getElementById("realAuthEmail").value.trim();

      const msg = document.getElementById("realAuthMsg");

      msg.textContent = "Sending...";

      try {
        const data = await postForm("/auth/send-otp.php", {
          full_name,
          email,
          country_code,
          whatsapp
        });

        msg.textContent = data.message;
        document.getElementById("otpSection").style.display = "block";

      } catch (e) {
        msg.textContent = e.message;
      }
    });

    document.getElementById("verifyOtpBtn").addEventListener("click", async function () {
      const email = document.getElementById("realAuthEmail").value.trim();
      const otp = document.getElementById("realAuthOtp").value.trim();
      const msg = document.getElementById("realAuthMsg");

      msg.textContent = "Verifying...";

      try {
        const data = await postForm("/auth/verify-otp.php", { email, otp });

        msg.textContent = data.message;
        setLabel(data.customer.email);

      } catch (e) {
        msg.textContent = e.message;
      }
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    ensureRealAuthUI();
    refreshCustomerStatus();
  });

})();
