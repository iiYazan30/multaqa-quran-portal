<?php
session_start();
require_once "db_connect.php";
/** @var PDO $pdo */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit;
}

$username = isset($_POST["username"]) ? trim($_POST["username"]) : "";
$password = isset($_POST["password"]) ? trim($_POST["password"]) : "";

if ($username === "" || $password === "") {
    header("Location: ../login.php?error=empty");
    exit;
}

$stmt = $pdo->prepare("
    SELECT users.user_id, users.username, users.password, roles.role_name
    FROM users
    JOIN roles ON users.role_id = roles.role_id
    WHERE users.username = ?
    LIMIT 1
");

$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $password !== $user["password"]) {
    header("Location: ../login.php?error=invalid");
    exit;
}

$_SESSION["user_id"] = $user["user_id"];
$_SESSION["username"] = $user["username"];
$_SESSION["role_name"] = $user["role_name"];
$_SESSION["role"] = $user["role_name"];

if ($user["role_name"] === "manager") {
    header("Location: ../pages/college-admin-dashboard.php");
} elseif ($user["role_name"] === "supervisor") {
    header("Location: ../pages/halqa-supervisor-dashboard.php");
} elseif ($user["role_name"] === "student") {
    header("Location: ../pages/student-dashboard.php");
} else {
    header("Location: ../login.php?error=role");
}

exit;
?>
