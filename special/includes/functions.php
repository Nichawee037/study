<?php
function cleanInput(string $data): string {
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}

function validateEmail(string $email): bool {
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(): bool {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $postedToken)) {
        http_response_code(403);
        exit('คำขอไม่ถูกต้องหรือหมดอายุ (CSRF Token Invalid)');
    }
    unset($_SESSION['csrf_token']);
    return true;
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
}

function checkSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function redirect(string $redirectUrl, ?string $flashMessage = null, string $messageType = 'info'): never {
    if ($flashMessage !== null) {
        $_SESSION['flash_message'] = $flashMessage;
        $_SESSION['flash_type']    = $messageType;
    }
    header("Location: {$redirectUrl}");
    exit();
}

function showMessage(string $messageText, string $alertType = 'info'): string {
    $typeMap = [
        'success' => ['alert-success', '✓'],
        'error'   => ['alert-danger',  '✗'],
        'warning' => ['alert-warning', '⚠'],
        'info'    => ['alert-info',    'ℹ'],
    ];
    [$cssClass, $iconSymbol] = $typeMap[$alertType] ?? $typeMap['info'];

    return <<<HTML
    <div class="alert {$cssClass} alert-dismissible fade show" role="alert">
        <strong>{$iconSymbol}</strong> {$messageText}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    HTML;
}

function showFlash(): string {
    if (empty($_SESSION['flash_message'])) {
        return '';
    }
    $renderedAlert = showMessage($_SESSION['flash_message'], $_SESSION['flash_type'] ?? 'info');
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    return $renderedAlert;
}

function getStatusBadge(string $statusKey, string $statusLabel): string {
    $badgeStyleMap = [
        'PENDING'   => 'bg-warning text-dark',
        'REVIEWING' => 'bg-info text-dark',
        'APPROVED'  => 'bg-success',
        'REJECTED'  => 'bg-danger',
        'CANCELLED' => 'bg-secondary',
    ];
    $badgeClass = $badgeStyleMap[$statusKey] ?? 'bg-secondary';
    $label = htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8');

    return "<span class=\"badge {$badgeClass} px-2 py-1\">{$label}</span>";
}

function formatDateThai(string $dateString): string {
    if (empty($dateString) || $dateString === '0000-00-00') return '-';
    $thaiMonthList = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
    ];
    $timestamp  = strtotime($dateString);
    $dayNum     = (int)date('j', $timestamp);
    $monthName  = $thaiMonthList[(int)date('n', $timestamp)];
    $buddhistYr = (int)date('Y', $timestamp) + 543;

    return "{$dayNum} {$monthName} {$buddhistYr}";
}

function getCurrentSemester(): int {
    $currentMonth = (int)date('n');
    if ($currentMonth >= 6 && $currentMonth <= 10) return 1;
    if ($currentMonth >= 11 || $currentMonth <= 3) return 2;
    return 3;
}

function validateRequestForm(array $formData): array {
    $detectedErrors = [];
    if (empty($formData['student_code'])) {
        $detectedErrors[] = 'กรุณาระบุรหัสนักศึกษา';
    } elseif (!preg_match('/^\d{8,13}$/', $formData['student_code'])) {
        $detectedErrors[] = 'รหัสนักศึกษาต้องเป็นตัวเลขความยาว 8–13 หลัก';
    }
    if (empty($formData['subject_code'])) {
        $detectedErrors[] = 'กรุณาระบุรหัสวิชา';
    }
    if (empty($formData['term_semester']) || !in_array((int)$formData['term_semester'], [1, 2, 3], true)) {
        $detectedErrors[] = 'กรุณาเลือกภาคการศึกษาให้ถูกต้อง';
    }
    if (empty($formData['term_year']) || !preg_match('/^\d{4}$/', $formData['term_year'])) {
        $detectedErrors[] = 'กรุณาระบุปีการศึกษาให้ถูกต้อง (ตัวเลข 4 หลัก)';
    }
    if (empty($formData['request_reason'])) {
        $detectedErrors[] = 'กรุณาระบุเหตุผลและความจำเป็น';
    } elseif (mb_strlen($formData['request_reason']) < 20) {
        $detectedErrors[] = 'รายละเอียดเหตุผลควรมีความยาวอย่างน้อย 20 ตัวอักษร';
    }
    return $detectedErrors;
}