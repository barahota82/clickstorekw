<?php require_once __DIR__ . '/check-auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Categories & Brands</title>
<link rel="stylesheet" href="assets/admin.css">
<style>
body{
    margin:0;
    padding:20px;
    background:#0f172a;
    color:#fff;
    font-family:Arial,sans-serif;
}
.page-title{
    margin:0 0 10px;
    font-size:28px;
    font-weight:800;
}
.page-desc{
    margin:0 0 20px;
    color:#c8d4ea;
    line-height:1.8;
    font-size:14px;
}
.panel{
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.08);
    border-radius:18px;
    padding:18px;
    margin-bottom:18px;
}
.panel-title{
    margin:0 0 14px;
    font-size:18px;
    font-weight:800;
    color:#fff;
}
.grid-2{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
}
.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
}
.form-group{
    display:flex;
    flex-direction:column;
    gap:6px;
}
.form-group.full{
    grid-column:1 / -1;
}
label{
    font-size:13px;
    color:#c8d4ea;
}
input, select{
    width:100%;
    box-sizing:border-box;
    padding:12px 14px;
    border-radius:12px;
   
