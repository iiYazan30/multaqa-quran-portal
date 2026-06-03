<?php
session_start();
require_once __DIR__ . "/db_connect.php";
/** @var PDO $pdo */

function redirectBack($statusType, $statusCode)
{
    header("Location: ../pages/exam-admin-dashboard.php?" . $statusType . "=" . urlencode($statusCode));
    exit;
}

function gradeFromScore($score)
{
    if ($score >= 95) {
        return "ممتاز";
    }

    if ($score >= 90) {
        return "جيد جداً";
    }

    if ($score >= 80) {
        return "جيد";
    }

    if ($score >= 70) {
        return "مقبول";
    }

    return "ضعيف";
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirectBack("error", "exam_complete_failed");
}

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

$sessionRole = isset($_SESSION["role"]) ? $_SESSION["role"] : (isset($_SESSION["role_name"]) ? $_SESSION["role_name"] : "");
if ($sessionRole !== "exam_admin") {
    header("Location: ../login.php");
    exit;
}

$requestId = isset($_POST["request_id"]) ? (int) $_POST["request_id"] : 0;
$examinerName = isset($_POST["examiner_name"]) ? trim($_POST["examiner_name"]) : "";
$scoreInput = isset($_POST["score"]) ? trim($_POST["score"]) : "";

if ($requestId <= 0 || $examinerName === "" || $scoreInput === "" || !is_numeric($scoreInput)) {
    redirectBack("error", "exam_complete_failed");
}

$score = (float) $scoreInput;
if ($score < 0 || $score > 100) {
    redirectBack("error", "exam_complete_failed");
}

try {
    $examAdminStmt = $pdo->prepare("
        SELECT exam_admin_id, college_id
        FROM exam_admins
        WHERE user_id = ?
        LIMIT 1
    ");
    $examAdminStmt->execute(array($_SESSION["user_id"]));
    $examAdmin = $examAdminStmt->fetch(PDO::FETCH_ASSOC);

    if (!$examAdmin) {
        redirectBack("error", "exam_complete_failed");
    }

    $requestStmt = $pdo->prepare("
        SELECT
            exam_requests.request_id,
            exam_requests.student_id,
            exam_requests.requested_part,
            exam_requests.week_id,
            exam_requests.request_date
        FROM exam_requests
        JOIN students ON exam_requests.student_id = students.student_id
        JOIN halqas ON students.halqa_id = halqas.halqa_id
        WHERE exam_requests.request_id = ? AND halqas.college_id = ?
        LIMIT 1
    ");
    $requestStmt->execute(array($requestId, $examAdmin["college_id"]));
    $request = $requestStmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        redirectBack("error", "exam_complete_failed");
    }

    $duplicateStmt = $pdo->prepare("
        SELECT exam_id
        FROM exams
        WHERE request_id = ?
        LIMIT 1
    ");
    $duplicateStmt->execute(array($requestId));
    $duplicate = $duplicateStmt->fetch(PDO::FETCH_ASSOC);

    if ($duplicate) {
        redirectBack("error", "exam_complete_failed");
    }

    $weekId = $request["week_id"] ? (int) $request["week_id"] : 0;
    if ($weekId <= 0) {
        $latestWeekStmt = $pdo->prepare("
            SELECT week_id
            FROM weeks
            ORDER BY week_number DESC, week_id DESC
            LIMIT 1
        ");
        $latestWeekStmt->execute();
        $latestWeek = $latestWeekStmt->fetch(PDO::FETCH_ASSOC);
        $weekId = $latestWeek && $latestWeek["week_id"] ? (int) $latestWeek["week_id"] : null;
    }

    $pdo->beginTransaction();

    $insertStmt = $pdo->prepare("
        INSERT INTO exams (
            request_id,
            student_id,
            part_name,
            score,
            grade,
            exam_date,
            week_id,
            exam_admin_id,
            examiner_name,
            notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insertStmt->execute(array(
        $requestId,
        $request["student_id"],
        $request["requested_part"],
        $score,
        gradeFromScore($score),
        date("Y-m-d"),
        $weekId,
        $examAdmin["exam_admin_id"],
        $examinerName,
        ""
    ));

    $updateStmt = $pdo->prepare("
        UPDATE exam_requests
        SET status = 'completed',
            exam_admin_id = ?
        WHERE request_id = ?
    ");
    $updateStmt->execute(array($examAdmin["exam_admin_id"], $requestId));

    $pdo->commit();

    redirectBack("success", "exam_completed");
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    redirectBack("error", "exam_complete_failed");
}
?>
