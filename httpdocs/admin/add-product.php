<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

if (!is_admin_logged_in()) {
    header('Location: /admin/index.php');
    exit;
}

$pdo = db();

$categories = $pdo->query("
    SELECT id, display_name, slug
    FROM categories
    WHERE is_active = 1
    ORDER BY sort_order ASC, id ASC
")->fetchAll();

$brands = $pdo->query("
    SELECT id, category_id, name, slug
    FROM brands
    WHERE is_active = 1
    ORDER BY sort_order ASC, id ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Add Product - Click Company</title>
  <style>
    * { box-sizing: border-box; }

    :root{
      --bg:#081120;
      --card:#111c34;
      --line:rgba(255,255,255,0.09);
      --text:#fff;
      --muted:#c8d4ea;
      --primary:#2563eb;
      --primary-2:#1d4ed8;
      --ok:#22c55e;
      --err:#ef4444;
      --shadow:0 18px 40px rgba(0,0,0,0.35);
      --radius:22px;
    }

    body{
      margin:0;
      font-family:Arial, sans-serif;
      background:
        radial-gradient(circle at top, rgba(37,99,235,0.22), transparent 35%),
        linear-gradient(180deg, #081120 0%, #0b1220 100%);
      color:var(--text);
      min-height:100vh;
    }

    .wrap{
      width:min(1150px, 94%);
      margin:26px auto 40px;
    }

    .topbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      flex-wrap:wrap;
      margin-bottom:20px;
    }

    .title{
      font-size:32px;
      font-weight:800;
      margin:0;
    }

    .muted{
      color:var(--muted);
      margin-top:8px;
      line-height:1.8;
      font-size:14px;
    }

    .back-btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      padding:12px 18px;
      border-radius:14px;
      text-decoration:none;
      color:#fff;
      background:linear-gradient(135deg, var(--primary), var(--primary-2));
      box-shadow:0 12px 24px rgba(37,99,235,0.22);
      font-weight:700;
    }

    .card{
      background:linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
      border:1px solid var(--line);
      border-radius:var(--radius);
      box-shadow:var(--shadow);
      padding:24px;
    }

    .grid{
      display:grid;
      grid-template-columns:repeat(2, 1fr);
      gap:16px;
    }

    .full{
      grid-column:1 / -1;
    }

    label{
      display:block;
      margin-bottom:8px;
      font-size:14px;
      font-weight:700;
      color:#dbe7fb;
    }

    input, select{
      width:100%;
      border:1px solid rgba(255,255,255,0.12);
      background:#0b1326;
      color:#fff;
      border-radius:15px;
      padding:14px 15px;
      font-size:15px;
      outline:none;
    }

    input:focus, select:focus{
      border-color:rgba(37,99,235,0.7);
      box-shadow:0 0 0 4px rgba(37,99,235,0.12);
    }

    .check-row{
      display:flex;
      gap:18px;
      flex-wrap:wrap;
      align-items:center;
      margin-top:8px;
    }

    .check-item{
      display:flex;
      align-items:center;
      gap:8px;
      color:#dce7fa;
      font-size:14px;
    }

    .check-item input{
      width:auto;
      transform:scale(1.1);
    }

    .preview{
      margin-top:12px;
      display:none;
      border:1px solid var(--line);
      border-radius:18px;
      overflow:hidden;
      max-width:340px;
      background:#091225;
    }

    .preview img{
      display:block;
      width:100%;
      height:auto;
    }

    .actions{
      display:flex;
      gap:12px;
      margin-top:20px;
      flex-wrap:wrap;
    }

    .btn{
      border:0;
      border-radius:16px;
      padding:14px 20px;
      font-size:15px;
      font-weight:800;
      color:#fff;
      cursor:pointer;
    }

    .btn-save{
      background:linear-gradient(135deg, var(--primary), var(--primary-2));
      box-shadow:0 14px 28px rgba(37,99,235,0.22);
    }

    .btn-reset{
      background:rgba(255,255,255,0.08);
      border:1px solid var(--line);
    }

    .status{
      margin-top:18px;
      padding:14px 16px;
      border-radius:16px;
      font-size:14px;
      line-height:1.8;
      display:none;
      white-space:pre-wrap;
    }

    .status.show{ display:block; }
    .status.ok{
      background:rgba(34,197,94,0.12);
      border:1px solid rgba(34,197,94,0.26);
      color:#cbf6d8;
    }
    .status.err{
      background:rgba(239,68,68,0.12);
      border:1px solid rgba(239,68,68,0.26);
      color:#ffd2d2;
    }
    .status.info{
      background:rgba(37,99,235,0.12);
      border:1px solid rgba(37,99,235,0.26);
      color:#d9e6ff;
    }

    .tip{
      margin-top:18px;
      background:rgba(255,255,255,0.04);
      border:1px solid var(--line);
      border-radius:18px;
      padding:16px;
      color:var(--muted);
      line-height:1.9;
      font-size:14px;
    }

    @media (max-width: 800px){
      .grid{
        grid-template-columns:1fr;
      }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div>
        <h1 class="title">Add Product</h1>
        <div class="muted">إضافة منتج جديد مع رفع الصورة والربط التلقائي بالمخزن.</div>
      </div>
      <a class="back-btn" href="/admin/">العودة للوحة التحكم</a>
    </div>

    <div class="card">
      <form id="productForm" enctype="multipart/form-data">
        <div class="grid">
          <div>
            <label for="title">اسم المنتج</label>
            <input id="title" name="title" type="text" placeholder="مثال: Samsung A55 128GB 8GB RAM 5G" required>
          </div>

          <div>
            <label for="sku">SKU</label>
            <input id="sku" name="sku" type="text" placeholder="مثال: SAM-A55-128-8-5G" required>
          </div>

          <div>
            <label for="category_id">الفئة</label>
            <select id="category_id" name="category_id" required>
              <option value="">اختر الفئة</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>">
                  <?= htmlspecialchars($cat['display_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label for="brand_id">البراند</label>
            <select id="brand_id" name="brand_id" required>
              <option value="">اختر البراند</option>
              <?php foreach ($brands as $brand): ?>
                <option value="<?= (int)$brand['id'] ?>" data-category-id="<?= (int)$brand['category_id'] ?>">
                  <?= htmlspecialchars($brand['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label for="devices_count">عدد الأجهزة</label>
            <input id="devices_count" name="devices_count" type="number" min="1" value="1" required>
          </div>

          <div>
            <label for="duration_months">مدة السداد (بالشهور)</label>
            <input id="duration_months" name="duration_months" type="number" min="1" value="12" required>
          </div>

          <div>
            <label for="down_payment">المقدم</label>
            <input id="down_payment" name="down_payment" type="number" step="0.001" min="0" value="0">
          </div>

          <div>
            <label for="monthly_amount">القسط الشهري</label>
            <input id="monthly_amount" name="monthly_amount" type="number" step="0.001" min="0" value="0">
          </div>

          <div class="full">
            <label for="image">صورة المنتج</label>
            <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp" required>
            <div id="previewBox" class="preview">
              <img id="previewImg" src="" alt="Preview">
            </div>
          </div>

          <div class="full">
            <div class="check-row">
              <label class="check-item">
                <input type="checkbox" id="is_available" name="is_available" checked>
                المنتج متاح
              </label>

              <label class="check-item">
                <input type="checkbox" id="is_hot_offer" name="is_hot_offer">
                Hot Offer
              </label>
            </div>
          </div>
        </div>

        <div class="actions">
          <button type="submit" class="btn btn-save">حفظ المنتج</button>
          <button type="reset" class="btn btn-reset">تفريغ النموذج</button>
        </div>

        <div id="statusBox" class="status"></div>
      </form>

      <div class="tip">
        مهم: يفضل تسمية ملف الصورة بصيغة واضحة مثل:
        <br>
        <strong>samsung-a55-128gb-8gb-ram-5g.webp</strong>
        <br>
        وإذا كانت الصورة فيها أكثر من جهاز:
        <br>
        <strong>iphone-15-128gb+airpods-pro-2.webp</strong>
      </div>
    </div>
  </div>

  <script>
    const categorySelect = document.getElementById('category_id');
    const brandSelect = document.getElementById('brand_id');
    const imageInput = document.getElementById('image');
    const previewBox = document.getElementById('previewBox');
    const previewImg = document.getElementById('previewImg');
    const form = document.getElementById('productForm');
    const statusBox = document.getElementById('statusBox');

    function setStatus(type, message) {
      statusBox.className = `status show ${type}`;
      statusBox.textContent = message;
    }

    function clearStatus() {
      statusBox.className = 'status';
      statusBox.textContent = '';
    }

    categorySelect.addEventListener('change', function () {
      const categoryId = this.value;
      const options = brandSelect.querySelectorAll('option');

      brandSelect.value = '';

      options.forEach(option => {
        if (!option.value) {
          option.hidden = false;
          return;
        }

        option.hidden = option.dataset.categoryId !== categoryId;
      });
    });

    imageInput.addEventListener('change', function () {
      const file = this.files[0];
      if (!file) {
        previewBox.style.display = 'none';
        previewImg.src = '';
        return;
      }

      const reader = new FileReader();
      reader.onload = function (e) {
        previewImg.src = e.target.result;
        previewBox.style.display = 'block';
      };
      reader.readAsDataURL(file);
    });

    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      clearStatus();

      const formData = new FormData(form);

      try {
        setStatus('info', 'جاري حفظ المنتج...');

        const res = await fetch('/admin/api/save-product.php', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        });

        const data = await res.json();

        if (!data.ok) {
          setStatus('err', data.message || 'فشل حفظ المنتج');
          return;
        }

        setStatus('ok', data.message || 'تم حفظ المنتج بنجاح');
        form.reset();
        previewBox.style.display = 'none';
        previewImg.src = '';
      } catch (err) {
        setStatus('err', 'حدث خطأ أثناء حفظ المنتج');
      }
    });
  </script>
</body>
</html>
