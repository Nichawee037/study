<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Logger.php';
require_once __DIR__ . '/classes/Request.php';
require_once __DIR__ . '/includes/functions.php';

checkSession();

$requestDetails = null;
$errorMessage   = '';

if (!empty($_GET['request_number'])) {
    $searchCode     = cleanInput($_GET['request_number']);
    $requestModel   = new Request();
    $requestDetails = $requestModel->trackByNumber($searchCode);

    if (!$requestDetails) {
        $errorMessage = 'ไม่พบข้อมูลคำร้องรหัส: ' . htmlspecialchars($searchCode, ENT_QUOTES, 'UTF-8');
    }
}

function getProgressClass(int $currentStatusId, int $targetStep, bool $rejectedFlag = false): string {
    if ($rejectedFlag && $targetStep >= 3) return '';
    if ($currentStatusId > $targetStep) return 'step-completed';
    if ($currentStatusId === $targetStep) return 'step-active';
    return '';
}

$pageTitle = 'IT Run — ติดตามสถานะคำร้อง';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-success text-white py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-search me-2"></i>ติดตามสถานะคำร้อง
                </h5>
            </div>
            <div class="card-body p-4">

                <form method="GET" action="" class="row g-2 mb-4">
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="request_number"
                               placeholder="กรอกรหัสคำร้อง เช่น SCR-671-0001"
                               value="<?= htmlspecialchars($_GET['request_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               required>
                    </div>
                    <div class="col-sm-4">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-search me-1"></i>ค้นหาคำร้อง
                        </button>
                    </div>
                </form>

                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-1"></i><?= $errorMessage ?>
                    </div>

                <?php elseif ($requestDetails): ?>
                <?php
                    $statusId   = (int)$requestDetails['status_id'];
                    $isRejected = $requestDetails['status_key'] === 'REJECTED';
                ?>

                <div class="alert alert-light border mb-4 d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-hashtag me-1 text-secondary"></i>
                        รหัสคำร้อง: <strong><?= htmlspecialchars($requestDetails['tracking_code'], ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div>
                        <?= getStatusBadge($requestDetails['status_key'], $requestDetails['status_label_th']) ?>
                    </div>
                </div>

                <div class="timeline mb-4">
                    <div class="timeline-item <?= getProgressClass($statusId, 1) ?>">
                        <div class="timeline-icon"><i class="fas fa-paper-plane"></i></div>
                        <div>
                            <h6 class="mb-0 fw-bold">ยื่นคำร้อง</h6>
                            <small class="text-muted"><?= formatDateThai($requestDetails['created_at']) ?></small>
                        </div>
                    </div>

                    <div class="timeline-item <?= getProgressClass($statusId, 2) ?>">
                        <div class="timeline-icon"><i class="fas fa-user-check"></i></div>
                        <div>
                            <h6 class="mb-0 fw-bold">อยู่ระหว่างการพิจารณา</h6>
                            <small class="text-muted">
                                <?= $requestDetails['evaluated_at']
                                    ? 'ประเมินเมื่อ ' . formatDateThai($requestDetails['evaluated_at'])
                                    : 'รอภาควิชาตรวจสอบ (1–2 สัปดาห์)' ?>
                            </small>
                        </div>
                    </div>

                    <div class="timeline-item <?= getProgressClass($statusId, 3, $isRejected) ?>">
                        <div class="timeline-icon">
                            <i class="fas <?= $isRejected ? 'fa-times-circle' : 'fa-check-circle' ?>"></i>
                        </div>
                        <div>
                            <?php if ($isRejected): ?>
                                <h6 class="mb-0 fw-bold text-danger">ไม่อนุมัติ</h6>
                                <small class="text-muted">
                                    <?= $requestDetails['evaluation_remark']
                                        ? 'เหตุผล: ' . htmlspecialchars($requestDetails['evaluation_remark'], ENT_QUOTES, 'UTF-8')
                                        : 'ไม่ผ่านการพิจารณา' ?>
                                </small>
                            <?php else: ?>
                                <h6 class="mb-0 fw-bold">อนุมัติเรียบร้อย</h6>
                                <small class="text-muted">
                                    <?= $requestDetails['approved_at']
                                        ? 'อนุมัติเมื่อ ' . formatDateThai($requestDetails['approved_at'])
                                        : 'รอผลอนุมัติขั้นสุดท้าย' ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-sm align-middle">
                        <tbody>
                            <tr>
                                <th class="text-secondary w-35">ผู้ยื่นคำร้อง</th>
                                <td><?= htmlspecialchars($requestDetails['learner_fullname'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                            <tr>
                                <th class="text-secondary">รายวิชา</th>
                                <td>
                                    <?= htmlspecialchars($requestDetails['subject_code'], ENT_QUOTES, 'UTF-8') ?>
                                    <?= htmlspecialchars($requestDetails['subject_title_th'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-secondary">ภาคเรียน / ปีการศึกษา</th>
                                <td>
                                    <?= (int)$requestDetails['term_semester'] ?> /
                                    <?= (int)$requestDetails['term_year'] ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-secondary">เหตุผลความจำเป็น</th>
                                <td><?= nl2br(htmlspecialchars($requestDetails['request_reason'], ENT_QUOTES, 'UTF-8')) ?></td>
                            </tr>
                            <?php if (!empty($requestDetails['evaluation_remark'])): ?>
                            <tr>
                                <th class="text-secondary">หมายเหตุจากเจ้าหน้าที่</th>
                                <td><?= nl2br(htmlspecialchars($requestDetails['evaluation_remark'], ENT_QUOTES, 'UTF-8')) ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($requestDetails['staff_fullname'])): ?>
                            <tr>
                                <th class="text-secondary">อาจารย์ผู้สอนประจำกลุ่ม</th>
                                <td><?= htmlspecialchars($requestDetails['staff_fullname'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <a href="/track_request.php" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-undo me-1"></i>ค้นหาคำร้องอื่น
                    </a>
                    <a href="/submit_request.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>สร้างคำร้องใหม่
                    </a>
                </div>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>