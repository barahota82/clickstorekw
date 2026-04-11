<?php require_once __DIR__ . '/check-auth.php'; ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Products</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>

<h2>Products</h2>

<select id="category_select" onchange="loadProducts()"></select>

<select id="brand_select"></select>

<input id="title" placeholder="Title">
<button onclick="addProduct()">Add Product</button>

<hr>

<div id="products"></div>

<script src="products.js"></script>

</body>
</html>
