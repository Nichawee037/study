<?php
/**
 * Admin — ประเมินและอนุมัติคำร้องขอเปิดกลุ่มเรียน
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Logger.php';
require_once __DIR__ . '/../classes/Request.php';
require_once __DIR__ . '/../includes/functions.php';

checkSession();

$reqId = (int)($_GET['id'] ?? 0);
if ($reqId <= 0) {
    redirect('/admin/manage_requests.php', 'ไม่พบรายการคำร้องที่ระบุ', 'error');
}

$dbConnection = Database::getInstance()->getConnection();
$queryStmt    =$dbConnection->prepare('SELECT * FROM vw_section_request_details WHERE request_id = :id LIMIT 1');
$queryStmt->execute([':id' =>$reqId]);
$requestDetail =$queryStmt->fetch();

if (!$requestDetail) {
    redirect('/admin/manage_requests.php', 'ไม่พบรหัสคำร้องนี้ในระบบ', 'error');
}

// ─── ประมวลผลเมื่อมีการส่งฟอร์ม (Approve / Reject) ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $newStatusId      = (int)($_POST['status_id'] ?? 0);$evaluationRemark = cleanInput($_POST['review_notes'] ?? '');$assignedStaffId  = !empty($_POST['instructor_id']) ? (int)$_POST['instructor_id'] : null;
    $evaluatorName    =$_SESSION['user_name'] ?? 'System Admin';

    if (!in_array($newStatusId, [3, 4], true)) {
        redirect("/admin/approve_request.php?id={$reqId}", 'โปรดเลือกผลการประเมินคำร้อง', 'warning');
    }

    $reqModel = new Request();$isUpdated = $reqModel->updateStatus($reqId, $newStatusId,$evaluationRemark, $evaluatorName,$assignedStaffId);

    if ($isUpdated) {
        redirect('/admin/manage_requests.php', 'บันทึกผลการพิจารณาคำร้องเรียบร้อยแล้ว', 'success');
    } else {
        redirect("/admin/approve_request.php?id={$reqId}", 'เกิดข้อผิดพลาดในการบันทึก โปรดลองอีกครั้ง', 'error');
    }
}

// ─── ดึงรายชื่ออาจารย์ผู้สอนสำหรับ Dropdown ───────────────────────────────
$staffList =$dbConnection->query(
    "SELECT staff_id,
            CONCAT(title_prefix, ' ', first_name, ' ', last_name) AS full_name
       FROM academic_staff WHERE is_active = 1 ORDER BY first_name ASC"
)->fetchAll();

$pageTitle = 'พิจารณาคำร้องขอเปิดกลุ่มเรียน';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-warning text-dark py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-gavel me-2"></i>พิจารณาอนุมัติคำร้อง</h5>
            </div>
            <div class="card-body p-4">

                <!-- ข้อมูลสรุปของคำร้อง -->
                <table class="table table-sm table-borderless mb-4">
                    <tbody>
                        <tr>
                            <th class="text-secondary w-35