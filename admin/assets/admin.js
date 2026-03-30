function adminSetStatus(id, type, message) {
  const box = document.getElementById(id);
  if (!box) return;

  box.className = 'status-box show ' + type;
  box.textContent = message;
}

function adminClearStatus(id) {
  const box = document.getElementById(id);
  if (!box) return;

  box.className = 'status-box';
  box.textContent = '';
}

function slugToTitle(text) {
  return (text || '')
    .replace(/[_\-]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function normalizeStockText(text) {
  return (text || '')
    .toLowerCase()
    .replace(/[_\-]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function detectStorage(text) {
  const source = (text || '').toLowerCase();
  const match = source.match(/\b(64|128|256|512)\s*gb\b|\b1\s*tb\b|\b1tb\b/);
  if (!match) return '';

  const raw = match[0].replace(/\s+/g, '').toUpperCase();
  if (raw === '1TB' || raw === '1TB') return '1TB';
  return raw;
}

function detectRam(text) {
  const source = (text || '').toLowerCase();
  const match = source.match(/\b(2|3|4|6|8|12|16)\s*gb\s*ram\b|\b(2|3|4|6|8|12|16)\s*ram\b|\b(2|3|4|6|8|12|16)\s*gb\b/);
  if (!match) return '';

  const numberMatch = match[0].match(/\b(2|3|4|6|8|12|16)\b/);
  if (!numberMatch) return '';

  return numberMatch[1] + 'GB RAM';
}

function detectNetwork(text) {
  const source = (text || '').toLowerCase();
  if (/\b5g\b/.test(source)) return '5G';
  if (/\b4g\b/.test(source)) return '4G';
  return '';
}

function detectBrandFromFilename(text) {
  const source = normalizeStockText(text);

  const knownBrands = [
    'apple',
    'iphone',
    'samsung',
    'honor',
    'xiaomi',
    'redmi',
    'oppo',
    'vivo',
    'realme',
    'huawei',
    'oneplus',
    'nokia',
    'google',
    'pixel',
    'motorola',
    'tecno',
    'infinix',
    'lenovo',
    'asus',
    'acer',
    'hp',
    'dell'
  ];

  const found = knownBrands.find(brand => source.includes(brand));
  if (!found) return '';

  if (found === 'iphone') return 'Apple';
  if (found === 'pixel') return 'Google';
  if (found === 'redmi') return 'Xiaomi';

  return found.charAt(0).toUpperCase() + found.slice(1);
}

function splitDevicesFromFilename(filename) {
  const noExt = (filename || '').replace(/\.[^.]+$/, '');
  return noExt
    .split('+')
    .map(part => part.trim())
    .filter(Boolean);
}

function buildDisplayNameFromFilename(part) {
  const cleaned = slugToTitle(part);
  return cleaned
    .split(' ')
    .filter(Boolean)
    .map(word => {
      if (/^\d+(gb|tb)$/i.test(word)) return word.toUpperCase();
      if (/^(4g|5g)$/i.test(word)) return word.toUpperCase();
      if (/^ram$/i.test(word)) return 'RAM';
      return word.charAt(0).toUpperCase() + word.slice(1);
    })
    .join(' ');
}

function analyzeFilenameForOCR(filename) {
  const devices = splitDevicesFromFilename(filename);

  const first = devices[0] || '';
  const firstNormalized = normalizeStockText(first);

  return {
    fileName: filename || '',
    devicesCount: devices.length || 1,
    brandFromFilename: detectBrandFromFilename(first),
    normalizedTitle: firstNormalized,
    storageValue: detectStorage(first),
    ramValue: detectRam(first),
    networkValue: detectNetwork(first),
    stockDisplayName: buildDisplayNameFromFilename(first),
    title: buildDisplayNameFromFilename(first)
  };
}

function bindCategoryBrandFilter(categoryId, brandId) {
  const category = document.getElementById(categoryId);
  const brand = document.getElementById(brandId);

  if (!category || !brand) return;

  function applyFilter() {
    const selectedCategory = category.value;

    brand.value = '';
    brand.querySelectorAll('option').forEach(opt => {
      if (!opt.value) {
        opt.hidden = false;
        return;
      }

      opt.hidden = opt.dataset.categoryId !== selectedCategory;
    });
  }

  category.addEventListener('change', applyFilter);
}

function bindImagePreview(inputId, imageId, placeholderId = null, fileNameFieldId = null) {
  const input = document.getElementById(inputId);
  const image = document.getElementById(imageId);
  const placeholder = placeholderId ? document.getElementById(placeholderId) : null;
  const fileNameField = fileNameFieldId ? document.getElementById(fileNameFieldId) : null;

  if (!input || !image) return;

  input.addEventListener('change', function () {
    const file = this.files && this.files[0];
    if (!file) return;

    if (fileNameField) {
      fileNameField.value = file.name;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
      image.src = e.target.result;
      image.classList.remove('hidden');
      image.style.display = 'block';

      if (placeholder) {
        placeholder.classList.add('hidden');
      }
    };
    reader.readAsDataURL(file);
  });
}

function fillOCRFieldsFromAnalysis(analysis) {
  const fileName = document.getElementById('ocrFileName');
  const title = document.getElementById('ocrTitle');
  const stockDisplayName = document.getElementById('ocrStockDisplayName');
  const normalizedTitle = document.getElementById('ocrNormalizedTitle');
  const storageValue = document.getElementById('ocrStorageValue');
  const ramValue = document.getElementById('ocrRamValue');
  const networkValue = document.getElementById('ocrNetworkValue');
  const devicesCount = document.getElementById('ocrDevicesCount');
  const brandFromFilename = document.getElementById('ocrBrandFromFilename');

  if (fileName) fileName.value = analysis.fileName || '';
  if (devicesCount) devicesCount.value = analysis.devicesCount || 1;
  if (brandFromFilename) brandFromFilename.value = analysis.brandFromFilename || '';
  if (normalizedTitle) normalizedTitle.value = analysis.normalizedTitle || '';
  if (storageValue && !storageValue.value) storageValue.value = analysis.storageValue || '';
  if (ramValue && !ramValue.value) ramValue.value = analysis.ramValue || '';
  if (networkValue && !networkValue.value) networkValue.value = analysis.networkValue || '';
  if (stockDisplayName && !stockDisplayName.value) stockDisplayName.value = analysis.stockDisplayName || '';
  if (title && !title.value) title.value = analysis.title || '';
}

function bindOCRAnalyzeButton() {
  const analyzeBtn = document.getElementById('ocrAnalyzeBtn');
  const imageInput = document.getElementById('ocrImageInput');

  if (!analyzeBtn || !imageInput) return;

  analyzeBtn.addEventListener('click', function () {
    const file = imageInput.files && imageInput.files[0];

    if (!file) {
      adminSetStatus('dashboardStatus', 'error', 'ارفع صورة أولًا قبل التحليل.');
      return;
    }

    const analysis = analyzeFilenameForOCR(file.name);
    fillOCRFieldsFromAnalysis(analysis);

    adminSetStatus('dashboardStatus', 'success', 'تم التحليل الأولي من اسم الملف. يمكنك الآن مراجعة البيانات قبل الحفظ.');
  });
}

function bindOCRUploadButton() {
  const uploadBtn = document.getElementById('ocrUploadBtn');
  const input = document.getElementById('ocrImageInput');

  if (!uploadBtn || !input) return;

  uploadBtn.addEventListener('click', function () {
    input.click();
  });
}

function bindEditImageButton() {
  const btn = document.getElementById('editChangeImageBtn');
  const input = document.getElementById('editImageInput');

  if (!btn || !input) return;

  btn.addEventListener('click', function () {
    input.click();
  });
}

function bindTabSwitching() {
  document.querySelectorAll('.admin-tab-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.admin-tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.admin-panel').forEach(panel => panel.classList.remove('active'));

      this.classList.add('active');

      const tabId = this.dataset.tab;
      const target = document.getElementById(tabId);
      if (target) {
        target.classList.add('active');
      }
    });
  });
}

function initializeAdminUI() {
  bindTabSwitching();

  bindCategoryBrandFilter('ocrCategory', 'ocrBrand');
  bindCategoryBrandFilter('editCategory', 'editBrand');

  bindImagePreview('ocrImageInput', 'ocrPreviewImage', 'ocrPreviewPlaceholder', 'ocrFileName');
  bindImagePreview('editImageInput', 'editPreviewImage');

  bindOCRUploadButton();
  bindOCRAnalyzeButton();
  bindEditImageButton();
}

document.addEventListener('DOMContentLoaded', initializeAdminUI);
