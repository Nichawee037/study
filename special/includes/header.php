<?php
checkSession();
$pageTitle = $pageTitle ?? 'IT Run — ระบบคำร้องขอเปิดกลุ่มเรียนกรณีพิเศษ';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/index.php">
            <i class="fas fa-running me-2"></i>IT Run System
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/submit_request.php">
                    <i class="fas fa-plus-circle me-1"></i> ยื่นคำร้อง
                </a>
                <a class="nav-link" href="/track_request.php">
                    <i class="fas fa-search me-1"></i> ติดตามสถานะ
                </a>
            </div>
        </div>
    </div>
</nav>
<main class="py-4">
<div class="container">
<?= showFlash() ?>