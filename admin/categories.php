<?php require_once __DIR__ . '/check-auth.php'; ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Categories</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>

<h2>Categories</h2>

<div>
    <input id="cat_name" placeholder="Category Name">
    <button onclick="addCategory()">Add</button>
</div>

<hr>

<div id="categories"></div>

<hr>

<h3>Brands</h3>

<select id="category_select" onchange="loadBrands()"></select>

<div>
    <input id="brand_name" placeholder="Brand Name">
    <button onclick="addBrand()">Add Brand</button>
</div>

<div id="brands"></div>

<script src="categories.js"></script>

</body>
</html>
