<?php
/**
 * API: ค้นหาและตรวจสอบรายวิชา (AJAX endpoint)
 *
 * GET /api/check_course.php?code=CS101
 * Response: {"found": true, "subject_title_th": "...", "credit_structure": "3-0-6"}
 *
 * GET /api/check_course.php?search=คำนวณ
 * Response: [{"subject_code": "...", "title_th": "..."}]
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Course.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

// รองรับเฉพาะ Request แบบ GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit();
}

$courseModel = new Course();

// ─── 1. ค้นหาแบบระบุรหัสวิชาตรงๆ (Exact Match) ─────────────────────────────
if (!empty($_GET['code'])) {
    $subjectCode = cleanInput($_GET['code']);
    $subjectData = $courseModel->findByCode($subjectCode);

    if ($subjectData) {
        echo json_encode([
            'found'            => true,
            'subject_code'     => $subjectData['subject_code'],
            'subject_title_th' => $subjectData['title_th'],
            'subject_title_en' => $subjectData['title_en'],
            'credit_structure' => $subjectData['lec_credit'] . '-' . $subjectData['lab_credit'] . '-' . $subjectData['self_credit'],
        ]);
    } else {
        echo json_encode(['found' => false]);
    }
    exit();
}

// ─── 2. ค้นหาตามคำค้นหา/ชื่อวิชา (Keyword Search) ───────────────────────────
if (!empty($_GET['search'])) {
    $searchKeyword = cleanInput($_GET['search']);

    if (mb_strlen($searchKeyword) < 2) {
        http_response_code(400);
        echo json_encode(['error' => 'คำค้นหาต้องมีความยาวอย่างน้อย 2 ตัวอักษร']);
        exit();
    }

    $searchResults = $courseModel->searchByName($searchKeyword);
    echo json_encode($searchResults);
    exit();
}

http_response_code(400);
echo json_encode(['error' => 'โปรดระบุพารามิเตอร์ ?code=... หรือ ?search=...']);