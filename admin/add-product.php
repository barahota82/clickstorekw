<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';

if (!is_admin_logged_in()) {
    header('Location: /admin/');
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
  <link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body>
  <div class="page-standalone">
    <div class="standalone-wrap">
      <div class="topbar">
        <div>
          <h1 class="section-title no-margin">Add Product</h1>
          <p class="section-desc no-margin">إضافة منتج جديد مع رفع الصورة والربط التلقائي بالمخزن.</p>
        </div>
        <a class="btn btn-primary" href="/admin/">العودة للوحة التحكم</a>
      </div>

      <section class="panel">
        <form id="productForm" enctype="multipart/form-data">
          <div class="form-grid">
            <div class="form-group">
              <label for="title">اسم المنتج</label>
              <input id="title" name="title" type="text" placeholder="مثال: Samsung A55 128GB 8GB RAM 5G" required>
            </div>

            <div class="form-group">
              <label for="sku">SKU</label>
              <input id="sku" name="sku" type="text" placeholder="مثال: SAM-A55-128-8-5G" required>
            </div>

            <div class="form-group">
              <label for="category_id">الفئة</label>
              <select id="category_id" name="category_id" required>
                <option value="">اختر الفئة</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= (int)$cat['id'] ?>"><?= esc($cat['display_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="brand_id">البراند</label>
              <select id="brand_id" name="brand_id" required>
                <option value="">اختر البراند</option>
                <?php foreach ($brands as $brand): ?>
                  <option value="<?= (int)$brand['id'] ?>" data-category-id="<?= (int)$brand['category_id'] ?>">
                    <?= esc($brand['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="devices_count">عدد الأجهزة</label>
              <input id="devices_count" name="devices_count" type="number" min="1" value="1" required>
            </div>

            <div class="form-group">
              <label for="duration_months">مدة السداد (بالشهور)</label>
              <input id="duration_months" name="duration_months" type="number" min="1" value="12" required>
            </div>

            <div class="form-group">
              <label for="down_payment">المقدم</label>
              <input id="down_payment" name="down_payment" type="number" step="0.001" min="0" value="0">
            </div>

            <div class="form-group">
              <label for="monthly_amount">القسط الشهري</label>
              <input id="monthly_amount" name="monthly_amount" type="number" step="0.001" min="0" value="0">
            </div>

            <div class="form-group full-col">
              <label for="image">صورة المنتج</label>
              <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp" required>
              <div id="previewBox" class="preview-box hidden">
                <img id="previewImg" src="" alt="Preview">
              </div>
            </div>

            <div class="form-group full-col">
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

          <div class="button-row">
            <button type="submit" class="btn btn-primary">حفظ المنتج</button>
            <button type="reset" class="btn btn-muted">تفريغ النموذج</button>
          </div>

          <div id="statusBox" class="status-box"></div>
        </form>

        <div class="tip-box">
          مهم: يفضل تسمية ملف الصورة بصيغة واضحة مثل:
          <br>
          <strong>samsung-a55-128gb-8gb-ram-5g.webp</strong>
          <br>
          وإذا كانت الصورة فيها أكثر من جهاز:
          <br>
          <strong>iphone-15-128gb+airpods-pro-2.webp</strong>
        </div>
      </section>
    </div>
  </div>

  <script src="/admin/assets/admin.js"></script>
  <script>
    const categorySelect = document.getElementById('category_id');
    const brandSelect = document.getElementById('brand_id');
    const imageInput = document.getElementById('image');
    const previewBox = document.getElementById('previewBox');
    const previewImg = document.getElementById('previewImg');
    const form = document.getElementById('productForm');

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
        previewBox.classList.add('hidden');
        previewImg.src = '';
        return;
      }

      const reader = new FileReader();
      reader.onload = function (e) {
        previewImg.src = e.target.result;
        previewBox.classList.remove('hidden');
      };
      reader.readAsDataURL(file);
    });

    form.addEventListener('submit', async function (e) {
      e.preventDefault();

      adminSetStatus('statusBox', 'info', 'جاري حفظ المنتج...');

      const formData = new FormData(form);

      try {
        const res = await fetch('/admin/api/save-product.php', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        });

        const data = await res.json();

        if (!data.ok) {
          adminSetStatus('statusBox', 'error', data.message || 'فشل حفظ المنتج');
          return;
        }

        adminSetStatus('statusBox', 'success', data.message || 'تم حفظ المنتج بنجاح');
        form.reset();
        previewBox.classList.add('hidden');
        previewImg.src = '';
      } catch (err) {
        adminSetStatus('statusBox', 'error', 'حدث خطأ أثناء حفظ المنتج');
      }
    });
  </script>
</body>
</html>
