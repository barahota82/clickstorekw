function loadNavbar() {
  const navbar = `
    <nav class="top-nav">
      <div class="nav-left">
        <a href="/index.html" class="logo">CLICK COMPANY</a>
      </div>

      <div class="nav-center">
        <a href="/index.html">Home</a>
        <a href="/phones.html">Phones</a>
        <a href="/tablets.html">Tablets</a>
        <a href="/laptops.html">Laptops</a>
        <a href="/accessories.html">Accessories</a>
        <a href="/all-products.html">Hot Offers</a>
      </div>

      <div class="nav-right">
        <button class="registration-top-btn desktop-only" onclick="openAuthModal()">
          <span id="desktopAuthLabel">Login</span>
        </button>

        <a href="#" class="cart-btn">
          Cart <span id="cart-count">0</span>
        </a>
      </div>
    </nav>
  `;

  document.getElementById("navbar-container").innerHTML = navbar;
}

document.addEventListener("DOMContentLoaded", loadNavbar);
