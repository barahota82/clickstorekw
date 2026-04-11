<?php require_once __DIR__ . '/check-auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Categories Management</title>
<style>
body {
    font-family: Arial;
    background: #111;
    color: #fff;
    padding: 20px;
}
h1 {
    margin-bottom: 20px;
}
input {
    padding: 8px;
    margin: 5px;
    width: 200px;
}
button {
    padding: 8px 15px;
    cursor: pointer;
}
.category {
    background: #222;
    padding: 15px;
    margin-bottom: 10px;
}
</style>
</head>

<body>

<h1>📂 Categories Management</h1>

<div>
    <input id="name_en" placeholder="Name EN">
    <input id="name_ph" placeholder="Name PH">
    <input id="name_hi" placeholder="Name HI">
    <button onclick="addCategory()">Add Category</button>
</div>

<hr>

<div id="categories"></div>

<script src="categories.js"></script>

</body>
</html>
