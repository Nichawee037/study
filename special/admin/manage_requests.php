<?php
/**
 * Admin — หน้ารายการคำร้องขอเปิดกลุ่มเรียนทั้งหมด
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Logger.php';
require_once __DIR__ . '/../classes/Request.php';
require_once __DIR__ . '/../includes/functions.php';

checkSession();

$filterCriteria = [
    'status_id'     => !empty($_GET['status_id'])     ? (int)$_GET['status_id']     : null,
    'term_semester' => !empty($_GET['term_semester']) ? (int)$_GET['term_semester'] : null,
    'term_year'     => !empty($_GET['term_year'])     ? (int)$_GET['term_year']     : null,
];

// กรองเอาเฉพาะตัวแปรที่มีการส่งค่าเข้ามา
$activeFilters = array_filter($filterCriteria);

$requestModel = new Request();
$requestList  = $requestModel->getAll($activeFilters);

$pageTitle = 'จัดการรายการคำร้อง';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold"><i class="fas fa-list-alt me-2 text-primary"></i>รายการคำร้องทั้งหมด</h4>
    <a href="/admin/dashboard.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>กลับหน้า Dashboard
    </a>
</div>

<!-- ฟอร์มกรองค้นหาข้อมูล -->
<form method="GET" class="row g-2 mb-4 bg-white p-3 rounded-3 shadow-sm border">
    <div class="col-sm-3">
        <select name="status_id" class="form-select form-select-sm">
            <option value="">-- แสดงทุกสถานะ --</option>
            <option value="1" <?= ($activeFilters['status_id'] ?? '') == 1 ? 'selected' : '' ?>>รอดำเนินการ</option>
            <option value="2" <?= ($activeFilters['status_id'] ?? '') == 2 ? 'selected' : '' ?>>กำลังตรวจสอบ</option>
            <option value="3" <?= ($activeFilters['status_id'] ?? '') == 3 ? 'selected' : '' ?>>อนุมัติแล้ว</option>
            <option value="4" <?= ($activeFilters['status_id'] ?? '') == 4 ? 'selected' : '' ?>>ไม่อนุมัติ</option>
        </select>
    </div>
    <div class="col-sm-2">
        <select name="term_semester" class="form-select form-select-sm">
            <option value="">-- ทุกภาคเรียน --</option>
            <option value="1" <?= ($activeFilters['term_semester'] ?? '') == 1 ? 'selected' : '' ?>>ภาคเรียนที่ 1</option>
            <option value="2" <?= ($activeFilters['term_semester'] ?? '') == 2 ? 'selected' : '' ?>>ภาคเรียนที่ 2</option>
            <option value="3" <?= ($activeFilters['term_semester'] ?? '') == 3 ? 'selected' : '' ?>>ภาคฤดูร้อน</option>
        </select>
    </div>
    <div class="col-sm-3">
        <input type="text" name="term_year" class="form-control form-control-sm"
               placeholder="ปีการศึกษา เช่น 2567"
               value="<?= htmlspecialchars($_GET['term_year'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-sm-4 text-end">
        <button type="submit" class="btn btn-primary btn-sm px-3">
            <i class="fas fa-filter me-1"></i>กรองข้อมูล
        </button>
        <a href="/admin/manage_requests.php" class="btn btn-light btn-sm border ms-1">ล้างตัวกรอง</a>
    </div>
</form>

<!-- ตารางแสดงผลรายการคำร้อง -->
<div class="table-responsive bg-white rounded-3 shadow-sm border">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>รหัสคำร้อง</th>
                <th>ผู้ยื่นคำร้อง</th>
                <th>รหัสวิชา</th>
                <th>ภาค/ปี</th>
                <th>วันที่ยื่นคำร้อง</th>
                <th>สถานะ</th>
                <th class="text-center">การจัดการ</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($requestList)): ?>
            <tr>
                <td colspan="7" class="text-center text-muted py-4">ไม่พบรายการคำร้องที่ตรงตามเงื่อนไข</td>
            </tr>
        <?php else: ?>
            <?php foreach ($requestList as $row): ?>
            <tr>
                <td class="fw-bold text-primary small"><?= htmlspecialchars($row['tracking_code']) ?></td>
                <td><?= htmlspecialchars($row['learner_fullname']) ?></td>
                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['subject_code']) ?></span></td>
                <td><?= (int)$row['term_semester'] ?> / <?= (int)$row['term_year'] ?></td>
                <td><?= formatDateThai($row['submission_date']) ?></td>
                <td><?= getStatusBadge($row['status_key'], $row['status_label_th']) ?></td>
                <td class="text-center">
                    <a href="/admin/approve_request.php?id=<?= (int)$row['request_id'] ?>"
                       class="btn btn-sm btn-outline-primary" title="พิจารณาคำร้อง">
                        <i class="fas fa-edit me-1"></i>ตรวจคำร้อง
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>