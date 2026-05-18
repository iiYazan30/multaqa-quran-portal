<?php
session_start();
require_once "db_connect.php";
/** @var PDO $pdo */

function redirectAddRecitation($query)
{
    header("Location: ../pages/halqa-supervisor-dashboard.php" . $query . "#weekly-report");
    exit;
}

if (!isset($_SESSION["user_id"])) {
    redirectAddRecitation("?error=recitation_failed");
}

$sessionRole = isset($_SESSION["role"]) ? $_SESSION["role"] : (isset($_SESSION["role_name"]) ? $_SESSION["role_name"] : "");
if ($sessionRole !== "supervisor") {
    redirectAddRecitation("?error=recitation_failed");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirectAddRecitation("?error=recitation_failed");
}

$studentId = isset($_POST["student_id"]) ? (int) $_POST["student_id"] : 0;
$weekId = isset($_POST["week_id"]) ? (int) $_POST["week_id"] : 0;
$type = isset($_POST["type"]) ? trim($_POST["type"]) : "";
$fromPage = isset($_POST["from_page"]) ? trim($_POST["from_page"]) : "";
$toPage = isset($_POST["to_page"]) ? trim($_POST["to_page"]) : "";
$pagesCount = isset($_POST["pages_count"]) ? (int) $_POST["pages_count"] : 0;
$notes = isset($_POST["notes"]) ? trim($_POST["notes"]) : "";

if ($studentId <= 0 || $weekId <= 0 || ($type !== "حفظ" && $type !== "مراجعة") || $pagesCount <= 0 || $fromPage === "" || $toPage === "") {
    redirectAddRecitation("?error=recitation_failed");
}

try {
    $supervisorStmt = $pdo->prepare("
        SELECT supervisors.supervisor_id, halqas.halqa_id
        FROM supervisors
        JOIN halqas ON supervisors.supervisor_id = halqas.supervisor_id
        WHERE supervisors.user_id = ?
        LIMIT 1
    ");
    $supervisorStmt->execute(array($_SESSION["user_id"]));
    $supervisor = $supervisorStmt->fetch(PDO::FETCH_ASSOC);

    if (!$supervisor || !$supervisor["halqa_id"]) {
        redirectAddRecitation("?error=recitation_failed");
    }

    $studentCheckStmt = $pdo->prepare("
        SELECT student_id
        FROM students
        WHERE student_id = ? AND halqa_id = ?
        LIMIT 1
    ");
    $studentCheckStmt->execute(array($studentId, $supervisor["halqa_id"]));
    $student = $studentCheckStmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        redirectAddRecitation("?error=recitation_failed");
    }

    $weekCheckStmt = $pdo->prepare("
        SELECT week_id
        FROM weeks
        WHERE week_id = ?
        LIMIT 1
    ");
    $weekCheckStmt->execute(array($weekId));
    $week = $weekCheckStmt->fetch(PDO::FETCH_ASSOC);

    if (!$week) {
        redirectAddRecitation("?error=recitation_failed");
    }

    $insertStmt = $pdo->prepare("
        INSERT INTO recitations (student_id, week_id, type, from_page, to_page, pages_count, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $insertStmt->execute(array($studentId, $weekId, $type, $fromPage, $toPage, $pagesCount, $notes));

    redirectAddRecitation("?success=recitation_added");
} catch (PDOException $e) {
    redirectAddRecitation("?error=recitation_failed");
}
?>
