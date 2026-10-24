<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Logger.php';
require_once __DIR__ . '/classes/Request.php';
require_once __DIR__ . '/includes/functions.php';

checkSession();
$pageTitle = 'IT Run — หน้าหลัก';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row g-4 justify-content-center">
    <div class="col-12 text-center mb-3">
        <h1 class="display-6 fw-bold text-primary"><i class="fas fa-running me-2"></i>IT Run System</h1>
        <p class="text-secondary">ระบบยื่นคำร้องขอเปิดกลุ่มเรียนพิเศษ สำหรับนักศึกษาสาขาเทคโนโลยีสารสนเทศ</p>
    </div>

    <div class="col-md-5">
        <div class="card h-100 text-center p-4 shadow-sm border-0">
            <div class="card-body">
                <i class="fas fa-file-signature fa-3x text-primary mb-3"></i>
                <h4 class="card-title fw-bold">สร้างคำร้องใหม่</h4>
                <p class="card-text text-muted">ยื่นความประสงค์ขอเปิดกลุ่มเรียนในรายวิชาที่ไม่ได้อยู่ในตารางเรียนปกติ</p>
                <a href="/submit_request.php" class="btn btn-primary mt-3 px-4">
                    <i class="fas fa-plus-circle me-1"></i>ลงทะเบียนคำร้อง
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card h-100 text-center p-4 shadow-sm border-0">
            <div class="card-body">
                <i class="fas fa-search-location fa-3x text-success mb-3"></i>
                <h4 class="card-title fw-bold">เช็กสถานะคำร้อง</h4>
                <p class="card-text text-muted">ติดตามความคืบหน้าการอนุมัติคำร้องด้วยรหัส Tracking Code</p>
                <a href="/track_request.php" class="btn btn-success mt-3 px-4">
                    <i class="fas fa-search me-1"></i>ค้นหาคำร้อง
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>