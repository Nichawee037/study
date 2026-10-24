<?php
/**
 * Admin — แดชบอร์ดสรุปสถิติตามสถานะคำร้อง
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Logger.php';
require_once __DIR__ . '/../classes/Request.php';
require_once __DIR__ . '/../includes/functions.php';

checkSession();

$dbConnection  = Database::getInstance()->getConnection();
$statusMetrics = $dbConnection->query(
    'SELECT ws.status_key, ws.label_th, ws.badge_color, COUNT(req.request_id) AS total_count
       FROM workflow_statuses ws
  LEFT JOIN section_requests req ON req.status_id = ws.status_id
      GROUP BY ws.status_id
      ORDER BY ws.display_order ASC'
)->fetchAll();

$pageTitle = 'แผงควบคุมระบบ';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold"><i class="fas fa-tachometer-alt me-2 text-primary"></i>ระบบการจัดการและภาพรวมคำร้อง</h4>
</div>

<div class="row g-3 mb-4">
<?php foreach ($statusMetrics as $metric): ?>
    <div class="col-sm-6 col-lg-3">
        <div class="card text-center border-0 shadow-sm" style="border-left: 5px solid <?= htmlspecialchars($metric['badge_color']) ?> !important;">
            <div class="card-body py-3">
                <div class="display-6 fw-bold text-dark"><?= (int)$metric['total_count'] ?></div>
                <div class="text-secondary small fw-bold"><?= htmlspecialchars($metric['label_th']) ?></div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<div class="p-3 bg-white rounded-3 shadow-sm border d-flex justify-content-between align-items-center">
    <div>
        <h6 class="mb-1 fw-bold">จัดการข้อมูลคำร้อง</h6>
        <p class="text-muted small mb-0">เข้าสู่หน้ารายการคำร้องทั้งหมดเพื่อกรองข้อมูลและประเมินอนุมัติ</p>
    </div>
    <a href="/admin/manage_requests.php" class="btn btn-primary px-4">
        <i class="fas fa-tasks me-1"></i>เข้าสู่หน้าจัดการคำร้อง
    </a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>