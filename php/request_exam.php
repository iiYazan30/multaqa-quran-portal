<?php
session_start();
require_once __DIR__ . "/db_connect.php";
/** @var PDO $pdo */

function redirectToDashboard($statusType, $statusCode)
{
    header("Location: ../pages/student-dashboard.php?" . $statusType . "=" . urlencode($statusCode) . "#exam-request-section");
    exit;
}

function quranPartName($partNumber)
{
    $parts = array(
        1 => "الجزء الأول",
        2 => "الجزء الثاني",
        3 => "الجزء الثالث",
        4 => "الجزء الرابع",
        5 => "الجزء الخامس",
        6 => "الجزء السادس",
        7 => "الجزء السابع",
        8 => "الجزء الثامن",
        9 => "الجزء التاسع",
        10 => "الجزء العاشر",
        11 => "الجزء الحادي عشر",
        12 => "الجزء الثاني عشر",
        13 => "الجزء الثالث عشر",
        14 => "الجزء الرابع عشر",
        15 => "الجزء الخامس عشر",
        16 => "الجزء السادس عشر",
        17 => "الجزء السابع عشر",
        18 => "الجزء الثامن عشر",
        19 => "الجزء التاسع عشر",
        20 => "الجزء العشرون",
        21 => "الجزء الحادي والعشرون",
        22 => "الجزء الثاني والعشرون",
        23 => "الجزء الثالث والعشرون",
        24 => "الجزء الرابع والعشرون",
        25 => "الجزء الخامس والعشرون",
        26 => "الجزء السادس والعشرون",
        27 => "الجزء السابع والعشرون",
        28 => "الجزء الثامن والعشرون",
        29 => "الجزء التاسع والعشرون",
        30 => "الجزء الثلاثون"
    );

    return isset($parts[$partNumber]) ? $parts[$partNumber] : "";
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirectToDashboard("error", "exam_request_failed");
}

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

$sessionRole = isset($_SESSION["role"]) ? $_SESSION["role"] : (isset($_SESSION["role_name"]) ? $_SESSION["role_name"] : "");
if ($sessionRole !== "student") {
    header("Location: ../login.php");
    exit;
}

$partNumberInput = isset($_POST["part_number"]) ? trim($_POST["part_number"]) : "";
if ($partNumberInput === "" || !ctype_digit($partNumberInput)) {
    redirectToDashboard("error", "exam_request_failed");
}

$partNumber = (int) $partNumberInput;
if ($partNumber < 1 || $partNumber > 30) {
    redirectToDashboard("error", "exam_request_failed");
}

$requestedPart = quranPartName($partNumber);
if ($requestedPart === "") {
    redirectToDashboard("error", "exam_request_failed");
}

try {
    $studentStmt = $pdo->prepare("
        SELECT
            students.student_id,
            students.student_type,
            halqas.college_id
        FROM students
        JOIN halqas ON students.halqa_id = halqas.halqa_id
        WHERE students.user_id = ?
        LIMIT 1
    ");
    $studentStmt->execute(array($_SESSION["user_id"]));
    $student = $studentStmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        redirectToDashboard("error", "exam_request_failed");
    }

    $duplicateStmt = $pdo->prepare("
        SELECT exam_requests.request_id
        FROM exam_requests
        LEFT JOIN exams ON exams.request_id = exam_requests.request_id
        WHERE exam_requests.student_id = ?
            AND exam_requests.requested_part = ?
            AND exams.exam_id IS NULL
        LIMIT 1
    ");
    $duplicateStmt->execute(array($student["student_id"], $requestedPart));
    $duplicate = $duplicateStmt->fetch(PDO::FETCH_ASSOC);

    if ($duplicate) {
        redirectToDashboard("error", "duplicate_exam_request");
    }

    $completedExamStmt = $pdo->prepare("
        SELECT exam_id
        FROM exams
        WHERE student_id = ? AND part_name = ?
        LIMIT 1
    ");
    $completedExamStmt->execute(array($student["student_id"], $requestedPart));
    $completedExam = $completedExamStmt->fetch(PDO::FETCH_ASSOC);

    if ($completedExam) {
        $examType = "retake";
    } elseif ($student["student_type"] === "تثبيت") {
        $examType = "revision";
    } else {
        $examType = "first_time";
    }

    $latestWeekStmt = $pdo->prepare("SELECT MAX(week_id) AS latest_week_id FROM weeks");
    $latestWeekStmt->execute();
    $latestWeekRow = $latestWeekStmt->fetch(PDO::FETCH_ASSOC);
    $latestWeekId = $latestWeekRow && $latestWeekRow["latest_week_id"] ? (int) $latestWeekRow["latest_week_id"] : null;

    $examAdminStmt = $pdo->prepare("
        SELECT exam_admin_id
        FROM exam_admins
        WHERE college_id = ?
        ORDER BY exam_admin_id ASC
        LIMIT 1
    ");
    $examAdminStmt->execute(array($student["college_id"]));
    $examAdmin = $examAdminStmt->fetch(PDO::FETCH_ASSOC);
    $examAdminId = $examAdmin && $examAdmin["exam_admin_id"] ? (int) $examAdmin["exam_admin_id"] : null;

    $insertStmt = $pdo->prepare("
        INSERT INTO exam_requests (
            student_id,
            requested_part,
            exam_type,
            status,
            request_date,
            week_id,
            exam_admin_id,
            notes
        ) VALUES (?, ?, ?, 'pending', ?, ?, ?, ?)
    ");
    $insertStmt->execute(array(
        $student["student_id"],
        $requestedPart,
        $examType,
        date("Y-m-d"),
        $latestWeekId,
        $examAdminId,
        ""
    ));

    redirectToDashboard("success", "exam_request_sent");
} catch (PDOException $e) {
    redirectToDashboard("error", "exam_request_failed");
}
?>
