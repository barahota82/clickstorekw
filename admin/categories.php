<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Categories & Brands</title>
<link rel="stylesheet" href="assets/admin.css">
<style>
body{
  background:#0f172a;
  color:#fff;
  font-family:Arial;
  padding:20px;
}
.panel{
  background:rgba(255,255,255,0.05);
  padding:20px;
  border-radius:15px;
  margin-bottom:20px;
}
input,select{
  padding:10px;
  width:100%;
  border-radius:10px;
  border:none;
  margin-top:5px;
}
button{
  padding:10px;
  border:none;
  border-radius:10px;
  cursor:pointer;
  margin-top:10px;
}
.btn{
  background:#2563eb;
  color:#fff;
}
.list{
  margin-top:15px;
}
.item{
  background:rgba(255,255,255,0.06);
  padding:10px;
  border-radius:10px;
  margin-bottom:8px;
}
</style>
</head>
<body>

<h2>Categories & Brands</h2>

<div class="panel">
  <h3>Add Category</h3>
  <input id="catNameEn" placeholder="English Name">
  <input id="catNamePh" placeholder="Filipino Name">
  <input id="catNameHi" placeholder="Hindi Name">
  <button class="btn" onclick="addCategory()">Add Category</button>
</div>

<div class="panel">
  <h3>Add Brand</h3>
  <select id="brandCategory"></select>
  <input id="brandName" placeholder="Brand Name">
  <button class="btn" onclick="addBrand()">Add Brand</button>
</div>

<div class="panel">
  <h3>Categories List</h3>
  <div id="categoriesList"></div>
</div>

<script src="categories.js"></script>
</body>
</html>
