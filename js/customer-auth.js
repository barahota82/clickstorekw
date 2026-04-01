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

    if (!res.ok) throw new Error(json.message || "Error");

    return json;
  }

  function hideOldUI() {
    const texts = document.querySelectorAll("button, div");

    texts.forEach(el => {
      if (
        el.innerText &&
        (
          el.innerText.includes("Continue with Gmail") ||
          el.innerText.includes("Continue with Phone Number")
        )
      ) {
        el.style.display = "none";
      }
    });
  }

  function injectNewForm() {
    const authBox = document.querySelector(".auth-box-global");
    if (!authBox || document.getElementById("realCustomerAuthBox")) return;

    const wrapper = document.createElement("div");
    wrapper.id = "realCustomerAuthBox";

    wrapper.innerHTML = `
      <div style="margin-top:12px;">

        <input type="text" id="realAuthName" placeholder="Full name"
        style="width:100%;height:46px;border-radius:12px;border:1px solid #e5e7eb;padding:0 12px;margin-top:10px;">

        <div style="display:flex;gap:8px;margin-top:10px;">
          <select id="realAuthCountryCode"
          style="width:35%;height:46px;border-radius:12px;border:1px solid #e5e7eb;"></select>

          <input type="text" id="realAuthWhatsapp"
          placeholder="WhatsApp number"
          style="width:65%;height:46px;border-radius:12px;border:1px solid #e5e7eb;padding:0 12px;">
        </div>

        <input type="email" id="realAuthEmail"
        placeholder="Email address"
        style="width:100%;height:46px;border-radius:12px;border:1px solid #e5e7eb;padding:0 12px;margin-top:10px;">

        <button id="sendOtpBtn"
        style="width:100%;height:48px;border-radius:14px;background:#2563eb;color:#fff;font-weight:800;margin-top:10px;">
        Send Verification Code
        </button>

        <div id="otpSection" style="display:none;">
          <input type="text" id="realAuthOtp"
          placeholder="Enter OTP code"
          style="width:100%;height:46px;border-radius:12px;border:1px solid #e5e7eb;padding:0 12px;margin-top:10px;">

          <button id="verifyOtpBtn"
          style="width:100%;height:48px;border-radius:14px;background:#22c55e;color:#fff;font-weight:800;margin-top:10px;">
          Verify Code
          </button>
        </div>

        <div id="realAuthMsg" style="margin-top:10px;font-size:14px;"></div>
      </div>
    `;

    authBox.appendChild(wrapper);

    // load countries
    const select = document.getElementById("realAuthCountryCode");

    window.COUNTRY_CODES.forEach(c => {
      const opt = document.createElement("option");
      opt.value = c.code;
      opt.textContent = `${c.flag} ${c.country} (${c.code})`;
      if (c.code === "+965") opt.selected = true;
      select.appendChild(opt);
    });

    // send OTP
    document.getElementById("sendOtpBtn").onclick = async () => {
      const msg = document.getElementById("realAuthMsg");

      try {
        const data = await postForm("/auth/send-otp.php", {
          full_name: document.getElementById("realAuthName").value,
          email: document.getElementById("realAuthEmail").value,
          country_code: document.getElementById("realAuthCountryCode").value,
          whatsapp: document.getElementById("realAuthWhatsapp").value
        });

        msg.textContent = data.message;
        document.getElementById("otpSection").style.display = "block";

      } catch (e) {
        msg.textContent = e.message;
      }
    };

    // verify OTP
    document.getElementById("verifyOtpBtn").onclick = async () => {
      const msg = document.getElementById("realAuthMsg");

      try {
        const data = await postForm("/auth/verify-otp.php", {
          email: document.getElementById("realAuthEmail").value,
          otp: document.getElementById("realAuthOtp").value
        });

        msg.textContent = data.message;

      } catch (e) {
        msg.textContent = e.message;
      }
    };
  }

  document.addEventListener("DOMContentLoaded", function () {
    hideOldUI();      // 👈 يخفي القديم
    injectNewForm();  // 👈 يضيف الجديد
  });

})();
