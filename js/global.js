document.addEventListener("DOMContentLoaded", function () {

  // إنشاء container لو مش موجود
  let container = document.getElementById("authModalContainer");

  if (!container) {
    container = document.createElement("div");
    container.id = "authModalContainer";
    document.body.appendChild(container);
  }

  // تحميل country codes أولًا
  const countryScript = document.createElement("script");
  countryScript.src = "/js/country-codes.js";

  countryScript.onload = function () {

    // بعده تحميل auth
    const authScript = document.createElement("script");
    authScript.src = "/js/customer-auth.js";

    authScript.onload = function () {
      if (window.initAuthModal) {
        window.initAuthModal();
      }
    };

    document.body.appendChild(authScript);
  };

  document.body.appendChild(countryScript);

});
