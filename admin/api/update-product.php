<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/filename_parser.php';
require_once dirname(__DIR__) . '/helpers/stock_helper.php';
require_once dirname(__DIR__) . '/helpers/permissions_helper.php';
require_once dirname(__DIR__) . '/helpers/products_sync.php';
require_once dirname(__DIR__) . '/helpers/product_storage_helper.php';
require_once __DIR__ . '/link-product-stock.php';

if (!function_exists('update_product_json_payload')) {
    function update_product_json_payload(array $data): array
    {
        return [
            'slug' => (string)$data['slug'],
            'title' => (string)$data['title'],
            'category' => (string)$data['category_slug'],
            'brand' => (string)$data['brand_name'],
            'devices_count' => (int)$data['devices_count'],
            'image' => (string)$data['image_path'],
            'down_payment' => (float)$data['down_payment'],
            'monthly' => (float)$data['monthly_amount'],
            'duration' => (int)$data['duration_months'],
            'available' => (bool)$data['is_available'],
            'hot_offer' => (bool)$data['is_hot_offer']
        ];
    }
}

if (!function_exists('update_product_build_stock_filename')) {
    function update_product_build_stock_filename(PDO $pdo, array $product, ?string $uploadedImageName = null): string
    {
        if ($uploadedImageName !== null && trim($uploadedImageName) !== '') {
            return trim($uploadedImageName);
        }

        $linksStmt = $pdo->prepare("
            SELECT extracted_name
            FROM product_stock_links
            WHERE product_id = :product_id
            ORDER BY device_index ASC, id ASC
        ");
        $linksStmt->execute([
            'product_id' => (int)$product['id']
        ]);
        $names = $linksStmt->fetchAll(PDO::FETCH_COLUMN);

        $names = array_values(array_filter(array_map(static function ($item): string {
            return trim((string)$item);
        }, is_array($names) ? $names : [])));

        if (!empty($names)) {
            return implode(' + ', $names) . '.jpg';
        }

        $existingImagePath = trim((string)($product['image_path'] ?? ''));
        if ($existingImagePath !== '') {
            return basename($existingImagePath);
        }

        return trim((string)($product['title'] ?? 'product')) . '.jpg';
    }
}

require_post();
require_admin_auth_json();
admin_require_permission_json('products_edit', 'ليس لديك صلاحية لتعديل المنتج');

$productId = (int)($_POST['id'] ?? 0);
$title = trim((string)($_POST['title'] ?? ''));
$categoryId = (int)($_POST['category_id'] ?? 0);
$brandId = (int)($_POST['brand_id'] ?? 0);
$devicesCount = max(1, min(4, (int)($_POST['devices_count'] ?? 1)));
$durationMonths = max(1, (int)($_POST['duration_months'] ?? 1));
$downPayment = (float)($_POST['down_payment'] ?? 0);
$monthlyAmount = (float)($_POST['monthly_amount'] ?? 0);
$isAvailable = isset($_POST['is_available']) ? (int)((string)$_POST['is_available'] === '1') : 1;
$isHotOffer = isset($_POST['is_hot_offer']) ? (int)((string)$_POST['is_hot_offer'] === '1') : 0;

if ($productId <= 0 || $title === '' || $categoryId <= 0 || $brandId <= 0) {
    json_response(false, ['message' => 'Missing required fields'], 422);
}

$pdo = db();

$productStmt = $pdo->prepare("
    SELECT *
    FROM products
    WHERE id = :id
    LIMIT 1
");
$productStmt->execute(['id' => $productId]);
$product = $productStmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    json_response(false, ['message' => 'Product not found'], 404);
}

$oldCategoryId = (int)$product['category_id'];
$oldImagePath = trim((string)($product['image_path'] ?? ''));
$oldJsonPath = trim((string)($product['json_file_path'] ?? ''));

$uploadedImageName = null;
$newAbsoluteImagePath = '';
$newRelativeImagePath = $oldImagePath;
$newDataJsonRel = $oldJsonPath !== '' ? $oldJsonPath : '';
$oldAbsoluteImagePath = $oldImagePath !== '' ? dirname(__DIR__, 2) . $oldImagePath : '';
$oldImageShouldBeDeletedAfterCommit = false;
$newImageCreated = false;

try {
    $categoryBrand = product_storage_fetch_category_brand($pdo, $categoryId, $brandId);

    $categorySlug = product_storage_slugify((string)$categoryBrand['category_slug']);
    $brandSlug = product_storage_slugify((string)$categoryBrand['brand_slug']);
    $brandName = trim((string)($categoryBrand['brand_name'] ?? ''));

    if ($categorySlug === '' || $brandSlug === '' || $brandName === '') {
        throw new RuntimeException('Invalid category or brand data');
    }

    $slug = trim((string)($product['slug'] ?? ''));
    if ($slug === '') {
        $slug = product_storage_slugify((string)($product['title'] ?? ''));
    }
    if ($slug === '') {
        $slug = product_storage_slugify($title);
    }
    if ($slug === '') {
        $slug = 'product-' . $productId;
    }

    $paths = product_storage_build_paths($categorySlug, $brandSlug, $slug);
    $targetAbsoluteImagePath = (string)$paths['image_abs'];
    $targetRelativeImagePath = (string)$paths['image_rel'];
    $targetDataJsonRel = (string)$paths['category_data_json_rel'];

    if ($oldImagePath !== '' && $oldImagePath === $targetRelativeImagePath && is_file($oldAbsoluteImagePath)) {
        $newAbsoluteImagePath = $oldAbsoluteImagePath;
        $newRelativeImagePath = $oldImagePath;
    } else {
        if (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
            $image = $_FILES['image'];

            if (($image['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                json_response(false, ['message' => 'Image upload failed'], 422);
            }

            $uploadedImageName = trim((string)($image['name'] ?? ''));
            $uploadedExt = strtolower((string)pathinfo($uploadedImageName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($uploadedExt, $allowedExtensions, true)) {
                json_response(false, ['message' => 'Unsupported image type'], 422);
            }

            product_storage_convert_to_webp((string)$image['tmp_name'], $targetAbsoluteImagePath, true, 85);
            $newAbsoluteImagePath = $targetAbsoluteImagePath;
            $newRelativeImagePath = $targetRelativeImagePath;
            $newImageCreated = true;
        } elseif ($oldAbsoluteImagePath !== '' && is_file($oldAbsoluteImagePath)) {
            if (
                realpath($oldAbsoluteImagePath) !== realpath($targetAbsoluteImagePath) ||
                strtolower((string)pathinfo($oldAbsoluteImagePath, PATHINFO_EXTENSION)) !== 'webp'
            ) {
                if (file_exists($targetAbsoluteImagePath)) {
                    product_storage_remove_file_if_exists($targetAbsoluteImagePath);
                }

                product_storage_convert_to_webp($oldAbsoluteImagePath, $targetAbsoluteImagePath, false, 85);
                $newAbsoluteImagePath = $targetAbsoluteImagePath;
                $newRelativeImagePath = $targetRelativeImagePath;
                $newImageCreated = true;
                $oldImageShouldBeDeletedAfterCommit = $oldAbsoluteImagePath !== '' && realpath($oldAbsoluteImagePath) !== realpath($targetAbsoluteImagePath);
            } else {
                $newAbsoluteImagePath = $oldAbsoluteImagePath;
                $newRelativeImagePath = $oldImagePath;
            }
        } else {
            if (
                $oldImagePath !== '' &&
                $oldCategoryId === $categoryId &&
                (int)$product['brand_id'] === $brandId
            ) {
                $newRelativeImagePath = $oldImagePath;
                $newAbsoluteImagePath = '';
            } else {
                throw new RuntimeException('Current image file is missing on the server. Please upload the image again.');
            }
        }
    }

    $newDataJsonRel = $targetDataJsonRel;
    $stockFilename = update_product_build_stock_filename($pdo, $product, $uploadedImageName);

    $pdo->beginTransaction();

    $adminUserId = function_exists('admin_current_user_id') && admin_current_user_id() > 0
        ? admin_current_user_id()
        : ((int)($_SESSION['admin_user_id'] ?? 0) ?: null);

    $updateStmt = $pdo->prepare("
        UPDATE products
        SET
            category_id = :category_id,
            brand_id = :brand_id,
            title = :title,
            devices_count = :devices_count,
            image_path = :image_path,
            down_payment = :down_payment,
            monthly_amount = :monthly_amount,
            duration_months = :duration_months,
            is_available = :is_available,
            is_hot_offer = :is_hot_offer,
            json_file_path = :json_file_path,
            updated_by = :updated_by,
            updated_at = NOW()
        WHERE id = :id
        LIMIT 1
    ");

    $updateStmt->execute([
        'category_id' => $categoryId,
        'brand_id' => $brandId,
        'title' => $title,
        'devices_count' => $devicesCount,
        'image_path' => $newRelativeImagePath,
        'down_payment' => $downPayment,
        'monthly_amount' => $monthlyAmount,
        'duration_months' => $durationMonths,
        'is_available' => $isAvailable,
        'is_hot_offer' => $isHotOffer,
        'json_file_path' => $newDataJsonRel,
        'updated_by' => $adminUserId,
        'id' => $productId
    ]);

    $updateMedia = $pdo->prepare("
        UPDATE product_media
        SET file_path = :file_path
        WHERE product_id = :product_id
          AND is_primary = 1
    ");
    $updateMedia->execute([
        'file_path' => $newRelativeImagePath,
        'product_id' => $productId
    ]);

    if ($updateMedia->rowCount() === 0) {
        $insertMedia = $pdo->prepare("
            INSERT INTO product_media (
                product_id,
                file_path,
                sort_order,
                is_primary,
                created_at
            ) VALUES (
                :product_id,
                :file_path,
                1,
                1,
                NOW()
            )
        ");
        $insertMedia->execute([
            'product_id' => $productId,
            'file_path' => $newRelativeImagePath
        ]);
    }

    if ($isHotOffer === 1) {
        $checkHot = $pdo->prepare("SELECT id FROM hot_offers WHERE product_id = :product_id LIMIT 1");
        $checkHot->execute(['product_id' => $productId]);
        $existingHot = $checkHot->fetch(PDO::FETCH_ASSOC);

        if ($existingHot) {
            $enableHot = $pdo->prepare("
                UPDATE hot_offers
                SET is_active = 1, updated_at = NOW()
                WHERE product_id = :product_id
            ");
            $enableHot->execute(['product_id' => $productId]);
        } else {
            $insertHot = $pdo->prepare("
                INSERT INTO hot_offers (
                    product_id,
                    sort_order,
                    is_active,
                    created_at,
                    updated_at
                ) VALUES (
                    :product_id,
                    9999,
                    1,
                    NOW(),
                    NOW()
                )
            ");
            $insertHot->execute(['product_id' => $productId]);
        }
    } else {
        $disableHot = $pdo->prepare("
            UPDATE hot_offers
            SET is_active = 0, updated_at = NOW()
            WHERE product_id = :product_id
        ");
        $disableHot->execute(['product_id' => $productId]);
    }

    $stockLinkResult = link_product_to_stock($pdo, $productId, $brandId, $categoryId, $stockFilename);
    $inventoryStockItem = ensure_stock_item_for_product($pdo, $productId, (string)($product['sku'] ?? ''), null);

    generate_products_json_for_category($categoryId);
    if ($oldCategoryId !== $categoryId) {
        generate_products_json_for_category($oldCategoryId);
    }

    $pdo->commit();

    if ($oldImageShouldBeDeletedAfterCommit && $oldAbsoluteImagePath !== '') {
        product_storage_remove_file_if_exists($oldAbsoluteImagePath);
    }

    if ($oldJsonPath !== '' && $oldJsonPath !== $newDataJsonRel) {
        $oldJsonAbsolute = dirname(__DIR__, 2) . $oldJsonPath;
        if (is_file($oldJsonAbsolute) && basename($oldJsonAbsolute) !== 'data.json') {
            @unlink($oldJsonAbsolute);
        }
    }

    if (function_exists('admin_activity_log')) {
        admin_activity_log(
            'update_product',
            'products',
            'product',
            $productId,
            'Updated product | title: ' . $title . ' | id: ' . $productId
        );
    }

    $savedJson = update_product_json_payload([
        'slug' => $slug,
        'title' => $title,
        'category_slug' => $categorySlug,
        'brand_name' => $brandName,
        'devices_count' => $devicesCount,
        'image_path' => $newRelativeImagePath,
        'down_payment' => $downPayment,
        'monthly_amount' => $monthlyAmount,
        'duration_months' => $durationMonths,
        'is_available' => $isAvailable,
        'is_hot_offer' => $isHotOffer,
    ]);

    $linkedCount = is_array($stockLinkResult['linked'] ?? null) ? count($stockLinkResult['linked']) : 0;
    $missingCount = is_array($stockLinkResult['missing'] ?? null) ? count($stockLinkResult['missing']) : 0;

    $message = 'تم تحديث المنتج بنجاح';

    if ($linkedCount > 0 && $missingCount === 0) {
        $message = 'تم تحديث المنتج وربط جميع الأجهزة الموجودة بالمخزن';
    } elseif ($linkedCount > 0 && $missingCount > 0) {
        $message = 'تم تحديث المنتج وربط الأجهزة الموجودة، ويوجد أجهزة تحتاج إضافة يدوية للمخزن';
    } elseif ($linkedCount === 0 && $missingCount > 0) {
        $message = 'تم تحديث المنتج، لكن ما زالت هناك أجهزة غير مضافة بالمخزن';
    }

    json_response(true, [
        'message' => $message,
        'product_id' => $productId,
        'slug' => $slug,
        'image_path' => $newRelativeImagePath,
        'json_file_path' => $newDataJsonRel,
        'saved_json' => $savedJson,
        'inventory_stock_item' => $inventoryStockItem,
        'stock_review' => [
            'devices_count' => (int)($stockLinkResult['devices_count'] ?? $devicesCount),
            'linked' => $stockLinkResult['linked'] ?? [],
            'missing' => $stockLinkResult['missing'] ?? [],
            'linked_count' => $linkedCount,
            'missing_count' => $missingCount,
        ]
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($newImageCreated && $newAbsoluteImagePath !== '' && $newAbsoluteImagePath !== $oldAbsoluteImagePath) {
        product_storage_remove_file_if_exists($newAbsoluteImagePath);
    }

    try {
        if ($categoryId > 0) {
            generate_products_json_for_category($categoryId);
        }
        if ($oldCategoryId > 0 && $oldCategoryId !== $categoryId) {
            generate_products_json_for_category($oldCategoryId);
        }
    } catch (Throwable $restoreError) {
    }

    json_response(false, [
        'message' => 'فشل تحديث المنتج',
        'error' => $e->getMessage()
    ], 500);
}
