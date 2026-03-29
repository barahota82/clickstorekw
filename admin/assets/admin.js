function setStatus(id, type, message) {
  const box = document.getElementById(id);
  if (!box) return;
  box.className = `status show ${type}`;
  box.textContent = message;
}

function clearStatus(id) {
  const box = document.getElementById(id);
  if (!box) return;
  box.className = "status";
  box.textContent = "";
}

async function login() {
  clearStatus("loginStatus");

  const username = document.getElementById("loginUsername")?.value.trim() || "";
  const password = document.getElementById("loginPassword")?.value.trim() || "";

  if (!username || !password) {
    setStatus("loginStatus", "err", "اكتب اسم المستخدم وكلمة المرور.");
    return;
  }

  try {
    const res = await fetch("api/login.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({ username, password })
    });

    const data = await res.json();

    if (!data.ok) {
      setStatus("loginStatus", "err", data.message || "فشل تسجيل الدخول.");
      return;
    }

    location.reload();
  } catch (e) {
    setStatus("loginStatus", "err", "حدث خطأ أثناء تسجيل الدخول.");
  }
}

async function logout() {
  try {
    await fetch("api/logout.php", {
      method: "POST"
    });
  } catch (e) {}

  location.reload();
}

function bootApp() {
  const loggedIn = window.ADMIN_IS_LOGGED_IN === true;

  if (loggedIn) {
    document.getElementById("loginView")?.classList.add("hidden");
    document.getElementById("appView")?.classList.remove("hidden");
  } else {
    document.getElementById("appView")?.classList.add("hidden");
    document.getElementById("loginView")?.classList.remove("hidden");
  }
}

function openTab(tabId, btn) {
  document.querySelectorAll(".tab-panel").forEach(el => el.classList.remove("active"));
  document.querySelectorAll(".tab-btn").forEach(el => el.classList.remove("active"));

  document.getElementById(`tab-${tabId}`)?.classList.add("active");
  if (btn) btn.classList.add("active");
}

let currentProductContext = null;

function extractNumberOnly(value) {
  const text = String(value || "").trim();
  const match = text.match(/\d+/);
  return match ? match[0] : "";
}

async function authFetch(url, options = {}) {
  const res = await fetch(url, options);

  if (res.status === 401) {
    location.reload();
    throw new Error("Unauthorized");
  }

  return res;
}

async function loadProductFileList() {
  const category = document.getElementById("editCategory")?.value;
  const select = document.getElementById("editFile");

  if (!category || !select) return;

  select.innerHTML = `<option value="">جاري تحميل المنتجات...</option>`;

  try {
    const res = await fetch(`/products/${category}/index.json`);
    const files = await res.json();

    select.innerHTML = `<option value="">اختر المنتج</option>`;

    files.forEach(file => {
      const option = document.createElement("option");
      option.value = file;
      option.textContent = file
        .replace(".json", "")
        .replace(/\//g, " / ")
        .replace(/-/g, " ");
      select.appendChild(option);
    });
  } catch (e) {
    select.innerHTML = `<option value="">فشل تحميل المنتجات</option>`;
  }
}

async function loadProduct() {
  clearStatus("editStatus");

  const category = document.getElementById("editCategory")?.value || "";
  const file = document.getElementById("editFile")?.value.trim() || "";

  if (!file) {
    setStatus("editStatus", "err", "اختر ملف المنتج أولًا.");
    return;
  }

  try {
    const res = await authFetch(`api/load-product.php?category=${encodeURIComponent(category)}&file=${encodeURIComponent(file)}`);
    const data = await res.json();

    if (!data.ok) {
      setStatus("editStatus", "err", data.message || "فشل تحميل المنتج.");
      return;
    }

    const p = data.product || {};
    currentProductContext = {
      category: data.category,
      file: data.file
    };

    document.getElementById("editTitle").value = p.title || "";
    document.getElementById("editBrand").value = p.brand || "";
    document.getElementById("editMonthly").value = extractNumberOnly(p.monthly);
    document.getElementById("editDuration").value = extractNumberOnly(p.duration);
    document.getElementById("editDown").value = extractNumberOnly(p.down_payment);
    document.getElementById("editDevices").value = p.devices_count || 1;
    document.getElementById("editImage").value = p.image || "";
    document.getElementById("editAvailable").checked = !!p.available;
    document.getElementById("editHot").checked = !!p.hot_offer;
    document.getElementById("editBrandPriority").value = p.brand_priority ?? 9999;
    document.getElementById("editPriority").value = p.priority ?? 9999;

    setStatus("editStatus", "ok", "تم تحميل المنتج بنجاح.");
  } catch (e) {
    if (e.message !== "Unauthorized") {
      setStatus("editStatus", "err", "حدث خطأ أثناء تحميل المنتج.");
    }
  }
}

async function saveProduct() {
  clearStatus("editStatus");

  if (!currentProductContext) {
    setStatus("editStatus", "err", "يجب استدعاء المنتج أولًا.");
    return;
  }

  const category = currentProductContext.category;
  const file = currentProductContext.file;

  const product = {
    title: document.getElementById("editTitle").value.trim(),
    category,
    brand: document.getElementById("editBrand").value.trim(),
    devices_count: Number(document.getElementById("editDevices").value || 1),
    image: document.getElementById("editImage").value.trim(),
    down_payment: document.getElementById("editDown").value.trim(),
    monthly: document.getElementById("editMonthly").value.trim(),
    duration: document.getElementById("editDuration").value.trim(),
    available: document.getElementById("editAvailable").checked,
    hot_offer: document.getElementById("editHot").checked,
    brand_priority: Number(document.getElementById("editBrandPriority").value || 9999),
    priority: Number(document.getElementById("editPriority").value || 9999)
  };

  try {
    const res = await authFetch("api/save-product.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        category,
        file,
        product
      })
    });

    const data = await res.json();

    if (!data.ok) {
      setStatus("editStatus", "err", data.message || "فشل حفظ المنتج.");
      return;
    }

    setStatus("editStatus", "ok", "تم حفظ التعديلات بنجاح.");
  } catch (e) {
    if (e.message !== "Unauthorized") {
      setStatus("editStatus", "err", "حدث خطأ أثناء حفظ المنتج.");
    }
  }
}

function ocrSetStatus(type, message) {
  setStatus("ocrStatus", type, message);
}

function ocrClearStatus() {
  clearStatus("ocrStatus");
}

function slugify(text) {
  return String(text || "")
    .trim()
    .toLowerCase()
    .replace(/\s+/g, "-")
    .replace(/_/g, "-")
    .replace(/-+/g, "-")
    .replace(/\s*\+\s*/g, "+")
    .replace(/[^a-z0-9\-+]/g, "")
    .replace(/-+/g, "-")
    .replace(/^\-+|\-+$/g, "");
}

function titleCaseWord(word) {
  if (!word) return "";
  if (/^\d+[a-z]+$/i.test(word)) return word.toUpperCase();
  return word.charAt(0).toUpperCase() + word.slice(1);
}

function titleFromDeviceSlug(slug) {
  return slug
    .split("-")
    .filter(Boolean)
    .map(titleCaseWord)
    .join(" ");
}

function previewOCRImageFile(file) {
  const img = document.getElementById("ocrPreviewImage");
  if (!img) return;

  if (!file) {
    img.style.display = "none";
    img.removeAttribute("src");
    return;
  }

  const reader = new FileReader();
  reader.onload = e => {
    img.src = e.target.result;
    img.style.display = "block";
  };
  reader.readAsDataURL(file);
}

function loadImageFromFile(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();

    reader.onload = e => {
      const img = new Image();
      img.onload = () => resolve(img);
      img.onerror = reject;
      img.src = e.target.result;
    };

    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}

async function preprocessImageForOCR(file) {
  const img = await loadImageFromFile(file);

  const scale = 2.5;
  const canvas = document.createElement("canvas");
  canvas.width = Math.floor(img.width * scale);
  canvas.height = Math.floor(img.height * scale);

  const ctx = canvas.getContext("2d");

  const sourceX = img.width * 0.55;
  const sourceY = img.height * 0.55;
  const sourceWidth = img.width * 0.45;
  const sourceHeight = img.height * 0.45;

  ctx.drawImage(
    img,
    sourceX, sourceY, sourceWidth, sourceHeight,
    0, 0, canvas.width, canvas.height
  );

  const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
  const data = imageData.data;

  for (let i = 0; i < data.length; i += 4) {
    const r = data[i];
    const g = data[i + 1];
    const b = data[i + 2];

    let gray = 0.299 * r + 0.587 * g + 0.114 * b;
    gray = gray > 145 ? 255 : gray < 110 ? 0 : gray;

    data[i] = gray;
    data[i + 1] = gray;
    data[i + 2] = gray;
  }

  ctx.putImageData(imageData, 0, 0);
  return canvas;
}

function splitOfferPartsFromFilename(fileName) {
  const raw = String(fileName || "").replace(/\.[^/.]+$/, "");
  return raw
    .split("+")
    .map(part => slugify(part))
    .map(part => part.replace(/-+/g, "-").replace(/^-|-$/g, ""))
    .filter(Boolean);
}

function extractBrandFromDeviceSlug(deviceSlug) {
  const words = deviceSlug.split("-").filter(Boolean);
  if (!words.length) return "";

  const first = words[0];
  const second = words[1] || "";

  if (/\d/.test(second)) return first;

  if (first.length === 1 && second && /^[a-z]+$/i.test(second)) {
    return `${first}-${second}`;
  }

  return first;
}

function detectFilenameData(fileName) {
  const parts = splitOfferPartsFromFilename(fileName);
  const devicesCount = parts.length || 1;
  const titles = parts.map(part => titleFromDeviceSlug(part));
  const brands = parts.map(part => titleFromDeviceSlug(extractBrandFromDeviceSlug(part)));

  return {
    devicesCount,
    title: titles.join(" + "),
    brand: devicesCount === 1 ? (brands[0] || "") : brands.join(" / ")
  };
}

function extractFinancialsFromText(text) {
  const clean = String(text || "")
    .replace(/\r/g, "")
    .replace(/O/g, "0")
    .replace(/o/g, "0")
    .replace(/I/g, "1")
    .replace(/l/g, "1")
    .replace(/S(?=\s*MONTH)/gi, "5")
    .trim();

  const lines = clean
    .split("\n")
    .map(line => line.trim())
    .filter(Boolean);

  let downPayment = "";
  let monthly = "";
  let duration = "";

  function firstNumber(str) {
    const match = String(str || "").match(/\d+/);
    return match ? match[0] : "";
  }

  function hasMonthly(str) {
    const s = String(str || "").toLowerCase();
    return (
      s.includes("monthly") ||
      s.includes("month1y") ||
      s.includes("monthiy") ||
      s.includes("m0nthly")
    );
  }

  function hasDownPayment(str) {
    const s = String(str || "").toLowerCase();
    return (
      s.includes("down") ||
      s.includes("payment") ||
      s.includes("paynent") ||
      s.includes("payrnent")
    );
  }

  function hasZero(str) {
    const s = String(str || "").toLowerCase();
    return s.includes("zero") || s.includes("zer0");
  }

  function hasDuration(str) {
    const s = String(str || "").toLowerCase();
    return (
      s.includes("months") ||
      s.includes("month") ||
      s.includes("m0nths") ||
      s.includes("to pay") ||
      s.includes("topay") ||
      s.includes("pay")
    );
  }

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];

    if (hasMonthly(line)) {
      monthly = firstNumber(line);

      if (i > 0) {
        const prevLine = lines[i - 1];

        if (hasZero(prevLine)) {
          downPayment = "0";
        } else {
          const prevNumber = firstNumber(prevLine);
          downPayment = prevNumber || "0";
        }
      } else {
        downPayment = "0";
      }

      if (i < lines.length - 1) {
        const nextLine = lines[i + 1];
        const nextNumber = firstNumber(nextLine);
        if (nextNumber) duration = nextNumber;
      }

      break;
    }
  }

  if (!monthly) {
    for (let i = 0; i < lines.length; i++) {
      if (hasMonthly(lines[i])) {
        monthly = firstNumber(lines[i]);
        break;
      }
    }
  }

  if (!duration) {
    for (let i = 0; i < lines.length; i++) {
      if (hasDuration(lines[i])) {
        const n = firstNumber(lines[i]);
        if (n && n !== monthly) {
          duration = n;
          break;
        }
      }
    }
  }

  if (!downPayment) {
    for (let i = 0; i < lines.length; i++) {
      if (hasDownPayment(lines[i])) {
        if (hasZero(lines[i])) {
          downPayment = "0";
        } else {
          const n = firstNumber(lines[i]);
          downPayment = n || "0";
        }
        break;
      }
    }
  }

  return {
    downPayment,
    monthly,
    duration,
    rawText: clean
  };
}

async function runOCRFromImage(file) {
  const processedCanvas = await preprocessImageForOCR(file);

  const croppedResult = await Tesseract.recognize(processedCanvas, "eng", {
    logger: m => {
      if (m.status === "recognizing text") {
        const pct = Math.round((m.progress || 0) * 100);
        ocrSetStatus("info", `جاري قراءة الجزء المقصوص... ${pct}%`);
      }
    }
  });

  const fullResult = await Tesseract.recognize(file, "eng", {
    logger: m => {
      if (m.status === "recognizing text") {
        const pct = Math.round((m.progress || 0) * 100);
        ocrSetStatus("info", `جاري قراءة الصورة كاملة... ${pct}%`);
      }
    }
  });

  const croppedData = extractFinancialsFromText(croppedResult.data.text || "");
  const fullData = extractFinancialsFromText(fullResult.data.text || "");

  return {
    downPayment: croppedData.downPayment || fullData.downPayment || "",
    monthly: croppedData.monthly || fullData.monthly || "",
    duration: croppedData.duration || fullData.duration || "",
    rawText: `--- CROPPED OCR ---\n${croppedData.rawText || ""}\n\n--- FULL OCR ---\n${fullData.rawText || ""}`
  };
}

async function ocrSmartAutoFill() {
  ocrClearStatus();

  const fileInput = document.getElementById("ocrProductImage");
  const file = fileInput?.files?.[0];

  if (!file) {
    ocrSetStatus("err", "اختر صورة أولًا.");
    return;
  }

  previewOCRImageFile(file);

  const fromName = detectFilenameData(file.name);

  if (!document.getElementById("ocrTitle").value.trim()) {
    document.getElementById("ocrTitle").value = fromName.title;
  }

  if (!document.getElementById("ocrBrand").value.trim()) {
    document.getElementById("ocrBrand").value = fromName.brand;
  }

  document.getElementById("ocrDevicesCount").value = fromName.devicesCount || 1;

  try {
    const fromOCR = await runOCRFromImage(file);

    if (fromOCR.downPayment) {
      document.getElementById("ocrDownPayment").value = fromOCR.downPayment;
    }

    if (fromOCR.monthly) {
      document.getElementById("ocrMonthly").value = fromOCR.monthly;
    }

    if (fromOCR.duration) {
      document.getElementById("ocrDuration").value = fromOCR.duration;
    }

    document.getElementById("ocrRawText").value = fromOCR.rawText || "";

    ocrSetStatus(
      "ok",
      `تم استخراج البيانات بنجاح.
المقدم: ${document.getElementById("ocrDownPayment").value || "-"}
القسط: ${document.getElementById("ocrMonthly").value || "-"}
المدة: ${document.getElementById("ocrDuration").value || "-"}`
    );
  } catch (err) {
    ocrSetStatus("err", `فشل OCR\n${err.message}`);
  }
}

function copyOcrToEditTab() {
  document.getElementById("editCategory").value = document.getElementById("ocrCategory").value;
  document.getElementById("editTitle").value = document.getElementById("ocrTitle").value;
  document.getElementById("editBrand").value = document.getElementById("ocrBrand").value;
  document.getElementById("editDevices").value = document.getElementById("ocrDevicesCount").value || 1;
  document.getElementById("editDown").value = document.getElementById("ocrDownPayment").value || 0;
  document.getElementById("editMonthly").value = document.getElementById("ocrMonthly").value || "";
  document.getElementById("editDuration").value = document.getElementById("ocrDuration").value || "";
  document.getElementById("editAvailable").checked = document.getElementById("ocrAvailable").checked;
  document.getElementById("editHot").checked = document.getElementById("ocrHotOffer").checked;

  const editBtn = document.querySelector(".tab-btn");
  openTab("edit-product", editBtn);
  setStatus("editStatus", "ok", "تم نسخ بيانات OCR إلى تبويب تعديل المنتج.");
}

function resetOCRForm() {
  document.getElementById("ocrCategory").value = "phones";
  document.getElementById("ocrBrand").value = "";
  document.getElementById("ocrTitle").value = "";
  document.getElementById("ocrDevicesCount").value = "1";
  document.getElementById("ocrDownPayment").value = "0";
  document.getElementById("ocrMonthly").value = "";
  document.getElementById("ocrDuration").value = "";
  document.getElementById("ocrAvailable").checked = true;
  document.getElementById("ocrHotOffer").checked = false;
  document.getElementById("ocrProductImage").value = "";
  document.getElementById("ocrRawText").value = "";
  previewOCRImageFile(null);
  ocrClearStatus();
}

document.addEventListener("change", function(e) {
  if (e.target && e.target.id === "ocrProductImage") {
    const file = e.target.files[0];
    previewOCRImageFile(file);
  }
});

bootApp();

if (window.ADMIN_IS_LOGGED_IN === true) {
  loadProductFileList();
}
