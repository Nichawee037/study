<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Logger.php';
require_once __DIR__ . '/classes/Request.php';
require_once __DIR__ . '/classes/Course.php';
require_once __DIR__ . '/classes/Student.php';
require_once __DIR__ . '/includes/functions.php';

checkSession();

$formErrors   = [];
$isSaved      = false;
$trackingCode = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $inputData = [
        'student_code'          => cleanInput($_POST['student_code'] ?? ''),
        'subject_code'          => cleanInput($_POST['subject_code'] ?? ''),
        'term_semester'         => cleanInput($_POST['term_semester'] ?? ''),
        'term_year'             => cleanInput($_POST['term_year'] ?? ''),
        'request_reason'        => cleanInput($_POST['request_reason'] ?? ''),
        'headcount_estimate'    => max(1, (int)($_POST['headcount_estimate'] ?? 1)),
        'has_conflict_schedule' => isset($_POST['has_conflict_schedule']) ? 1 : 0,
    ];

    $formErrors = validateRequestForm($inputData);

    if (empty($formErrors)) {
        $courseObj  = new Course();
        $studentObj = new Student();

        if (!$studentObj->isEligibleForSpecialCourse($inputData['student_code'])) {
            $formErrors[] = 'เงื่อนไขไม่ตรงตามเกณฑ์: ต้องเป็นนักศึกษาชั้นปีที่ 4 ขึ้นไป หรือกลุ่มเรียนนอกเวลา';
        }

        if (empty($formErrors)) {
            $isConflict = $courseObj->isInRegularSchedule(
                $inputData['student_code'],
                $inputData['subject_code'],
                (int)$inputData['term_semester'],
                (int)$inputData['term_year']
            );

            if ($isConflict) {
                $formErrors[] = 'รายวิชานี้ถูกจัดอยู่ในตารางเรียนปกติแล้ว ไม่สามารถขอเปิดกลุ่มพิเศษได้';
            }
        }

        if (empty($formErrors)) {
            $requestObj = new Request();
            $response   = $requestObj->create($inputData);

            if ($response['success']) {
                $isSaved      = true;
                $trackingCode = $response['request_number'];
            } else {
                $formErrors[] = $response['message'];
            }
        }
    }
}

$defaultYear = (int)date('Y') + 543;
$defaultTerm = getCurrentSemester();

$pageTitle = 'IT Run — ยื่นคำร้องขอเปิดกลุ่มเรียน';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-edit me-2"></i>ยื่นคำร้องขอเปิดกลุ่มเรียนกรณีพิเศษ
                </h5>
            </div>
            <div class="card-body p-4">

                <?php if ($isSaved): ?>
                <div class="text-center py-4">
                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                    <h3 class="fw-bold">บันทึกคำร้องสำเร็จ!</h3>
                    <p class="text-muted mb-2">รหัสสำหรับติดตามคำร้องของคุณคือ</p>
                    <div class="fs-3 fw-bold text-primary border border-2 border-primary rounded-3 d-inline-block px-4 py-2 mb-3 bg-light">
                        <?= htmlspecialchars($trackingCode, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <p class="small text-muted">
                        กรุณาคัดลอกหรือจดจำรหัสนี้ไว้เพื่อเช็กสถานะการอนุมัติ<br>
                        ระยะเวลาในการพิจารณาโดยประมาณ 1–2 สัปดาห์
                    </p>
                    <div class="d-flex gap-2 justify-content-center mt-4">
                        <a href="/track_request.php?request_number=<?= urlencode($trackingCode) ?>" class="btn btn-success">
                            <i class="fas fa-search me-1"></i>ตรวจสอบสถานะ
                        </a>
                        <a href="/submit_request.php" class="btn btn-outline-secondary">
                            <i class="fas fa-file-alt me-1"></i>ยื่นคำร้องเพิ่ม
                        </a>
                    </div>
                </div>

                <?php else: ?>

                <?php if (!empty($formErrors)): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <strong class="d-block mb-1"><i class="fas fa-exclamation-triangle me-1"></i>ไม่สามารถส่งข้อมูลได้:</strong>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($formErrors as $err): ?>
                            <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="bg-light p-3 rounded-3 mb-4 border">
                    <h6 class="fw-bold text-dark mb-2"><i class="fas fa-book-reader me-1"></i>ข้อควรรู้ในการยื่นคำร้อง</h6>
                    <ul class="mb-0 small text-secondary ps-3">
                        <li>ต้องเป็นวิชาที่ไม่ปรากฏในตารางเรียนปกติของภาคการศึกษานั้นๆ</li>
                        <li>สิทธิ์สำหรับนักศึกษาชั้นปีที่ 4 ขึ้นไป หรือภาคค่ำ / เสาร์–อาทิตย์</li>
                        <li>ต้องระบุเหตุผลความจำเป็นโดยละเอียด (ความยาวอย่างน้อย 20 ตัวอักษร)</li>
                    </ul>
                </div>

                <form method="POST" action="" id="requestForm" class="needs-validation" novalidate>
                    <?= csrfField() ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="student_code" class="form-label required">รหัสนักศึกษา</label>
                            <input type="text" class="form-control" id="student_code" name="student_code"
                                   placeholder="เช่น 64010001" required pattern="\d{8,13}"
                                   value="<?= htmlspecialchars($_POST['student_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="invalid-feedback">โปรดระบุรหัสนักศึกษาให้ถูกต้อง (8–13 หลัก)</div>
                        </div>

                        <div class="col-md-6">
                            <label for="subject_code" class="form-label required">รหัสวิชา</label>
                            <input type="text" class="form-control" id="subject_code" name="subject_code"
                                   placeholder="เช่น CS101" required
                                   value="<?= htmlspecialchars($_POST['subject_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="invalid-feedback">โปรดระบุรหัสวิชา</div>
                        </div>

                        <div class="col-md-4">
                            <label for="term_semester" class="form-label required">ภาคการศึกษา</label>
                            <select class="form-select" id="term_semester" name="term_semester" required>
                                <option value="">-- เลือกภาคเรียน --</option>
                                <?php foreach ([1 => 'ภาคการศึกษาที่ 1', 2 => 'ภาคการศึกษาที่ 2', 3 => 'ภาคฤดูร้อน'] as $val => $label): ?>
                                    <option value="<?= $val ?>"
                                        <?= (int)($_POST['term_semester'] ?? $defaultTerm) === $val ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">โปรดเลือกภาคการศึกษา</div>
                        </div>

                        <div class="col-md-4">
                            <label for="term_year" class="form-label required">ปีการศึกษา (พ.ศ.)</label>
                            <input type="text" class="form-control" id="term_year" name="term_year"
                                   placeholder="เช่น 2567" required pattern="\d{4}"
                                   value="<?= htmlspecialchars($_POST['term_year'] ?? $defaultYear, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="invalid-feedback">โปรดระบุปีการศึกษา 4 หลัก</div>
                        </div>

                        <div class="col-md-4">
                            <label for="headcount_estimate" class="form-label">จำนวนผู้เรียนโดยประมาณ</label>
                            <input type="number" class="form-control" id="headcount_estimate"
                                   name="headcount_estimate" min="1" max="200"
                                   value="<?= (int)($_POST['headcount_estimate'] ?? 1) ?>">
                        </div>

                        <div class="col-12">
                            <label for="request_reason" class="form-label required">
                                เหตุผลและความจำเป็น
                                (<span id="char-count">0</span> ตัวอักษร / อย่างน้อย 20 ตัวอักษร)
                            </label>
                            <textarea class="form-control" id="request_reason" name="request_reason"
                                      rows="4" required minlength="20"
                                      placeholder="ระบุรายละเอียดความจำเป็นที่ต้องเปิดกลุ่มเรียน..."
                            ><?= htmlspecialchars($_POST['request_reason'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="invalid-feedback">กรุณากรอกเหตุผลอย่างน้อย 20 ตัวอักษร</div>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                       id="has_conflict_schedule" name="has_conflict_schedule" value="1"
                                    <?= isset($_POST['has_conflict_schedule']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="has_conflict_schedule">
                                    วิชานี้เปิดสอนในตารางปกติ แต่ติดเงื่อนไขวิชาซ้อน/ไม่สามารถลงเรียนได้
                                </label>
                            </div>
                        </div>

                        <div class="col-12 text-center pt-3">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-paper-plane me-1"></i>ส่งคำร้อง
                            </button>
                            <button type="reset" class="btn btn-light btn-lg ms-2 border">
                                <i class="fas fa-undo me-1"></i>รีเซ็ต
                            </button>
                        </div>
                    </div>
                </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>