<?php require_once __DIR__ . '/check-auth.php'; ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Hot Offers</title>
<link rel="stylesheet" href="assets/admin.css">
<style>
body { font-family: Arial, sans-serif; padding: 20px; }
.panel { margin-bottom: 25px; }
.row { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; flex-wrap: wrap; }
.card {
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 12px;
    margin-bottom: 10px;
    background: #fff;
}
.card img {
    width: 70px;
    height: 70px;
    object-fit: contain;
    border: 1px solid #eee;
    border-radius: 8px;
    background: #fafafa;
}
.card-top {
    display: flex;
    gap: 12px;
    align-items: flex-start;
}
.card-body {
    flex: 1;
}
.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 999px;
    font-size: 12px;
    background: #eee;
    margin-right: 6px;
}
.badge.active { background: #d1fae5; color: #065f46; }
.badge.off { background: #fee2e2; color: #991b1b; }
input[type="number"] {
    width: 90px;
    padding: 6px;
}
button {
    padding: 8px 12px;
    cursor: pointer;
}
</style>
</head>
<body>

<h2>Hot Offers Control Panel</h2>

<div class="panel row">
    <label for="category_select"><b>Category:</b></label>
    <select id="category_select" onchange="loadHotOffers()"></select>
    <button type="button" onclick="saveHotOrder()">Save Hot Order</button>
</div>

<div id="hotOffersList"></div>

<script src="hot-offers.js"></script>
</body>
</html>
