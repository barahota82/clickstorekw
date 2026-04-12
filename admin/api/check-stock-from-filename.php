<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once dirname(__DIR__) . '/helpers/filename_parser.php';
require_once dirname(__DIR__) . '/helpers/stock_helper.php';
require_once dirname(__DIR__) . '/helpers/permissions_helper.php';
require_once __DIR__ . '/link-product-stock.php';

require_post();
require_admin_auth_json();
admin_require_permission_json('stock_manage', 'ليس لديك صلاحية لفحص المخزن');

$data = get_request_json();
$filename = trim((string)($data['filename'] ?? ''));
$preferredBrandId = (int)($data['preferred_brand_id'] ?? 0);
$preferredCategoryId = (int)($data['preferred_category_id'] ?? 0);

if ($filename === '') {
    json_response(false, ['message' => 'Filename is required'], 422);
}

$pdo = db();
$review = review_stock_from_filename(
    $pdo,
    $filename,
    $preferredBrandId > 0 ? $preferredBrandId : null,
    $preferredCategoryId > 0 ? $preferredCategoryId : null
);

json_response(true, $review);
