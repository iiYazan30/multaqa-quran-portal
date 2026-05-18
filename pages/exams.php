<?php
session_start();
require_once __DIR__ . "/../php/db_connect.php";
/** @var PDO $pdo */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

$sessionRole = isset($_SESSION["role"]) ? $_SESSION["role"] : (isset($_SESSION["role_name"]) ? $_SESSION["role_name"] : "");
if ($sessionRole !== "manager") {
    header("Location: ../login.php");
    exit;
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function fallbackValue($value, $fallback)
{
    return ($value === null || $value === "") ? $fallback : $value;
}

function numberValue($value)
{
    if ($value === null || $value === "") {
        return 0;
    }

    return (int) $value;
}

function formatNumberValue($value)
{
    if ($value === null || $value === "") {
        return "0";
    }

    $number = (float) $value;
    if (floor($number) == $number) {
        return number_format((int) $number);
    }

    return rtrim(rtrim(number_format($number, 1, ".", ","), "0"), ".");
}

function statusLabel($status)
{
    if ($status === "pending") {
        return "قيد الانتظار";
    }

    if ($status === "approved") {
        return "مقبول";
    }

    if ($status === "rejected") {
        return "مرفوض";
    }

    if ($status === "completed") {
        return "مكتمل";
    }

    return "لا يوجد بيانات";
}

function statusClass($status)
{
    if ($status === "completed" || $status === "approved") {
        return "status-completed";
    }

    return "status-pending";
}

$userId = $_SESSION["user_id"];

$managerStmt = $pdo->prepare("
    SELECT
        managers.manager_id,
        managers.full_name AS manager_name,
        managers.college_id,
        colleges.college_name
    FROM managers
    JOIN colleges ON managers.college_id = colleges.college_id
    WHERE managers.user_id = ?
    LIMIT 1
");
$managerStmt->execute(array($userId));
$manager = $managerStmt->fetch(PDO::FETCH_ASSOC);

if (!$manager) {
    header("Location: ../login.php");
    exit;
}

$collegeId = $manager["college_id"];
$managerName = fallbackValue($manager["manager_name"], isset($_SESSION["username"]) ? $_SESSION["username"] : "لا يوجد بيانات");
$collegeName = fallbackValue($manager["college_name"], "لا يوجد بيانات");

$summaryStmt = $pdo->prepare("
    SELECT
        COUNT(exam_requests.request_id) AS total_requests,
        COALESCE(SUM(CASE WHEN exam_requests.status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_requests,
        COALESCE(SUM(CASE WHEN exam_requests.status = 'completed' THEN 1 ELSE 0 END), 0) AS completed_requests
    FROM exam_requests
    JOIN students ON exam_requests.student_id = students.student_id
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    WHERE halqas.college_id = ?
");
$summaryStmt->execute(array($collegeId));
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

$averageScoreStmt = $pdo->prepare("
    SELECT COALESCE(AVG(exams.score), 0) AS average_score
    FROM exams
    JOIN students ON exams.student_id = students.student_id
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    WHERE halqas.college_id = ?
");
$averageScoreStmt->execute(array($collegeId));
$averageScoreRow = $averageScoreStmt->fetch(PDO::FETCH_ASSOC);

$requestsStmt = $pdo->prepare("
    SELECT
        exam_requests.request_id,
        exam_requests.requested_part,
        exam_requests.request_date,
        exam_requests.status,
        students.student_id,
        students.name AS student_name,
        halqas.name AS halqa_name
    FROM exam_requests
    JOIN students ON exam_requests.student_id = students.student_id
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    WHERE halqas.college_id = ?
    ORDER BY exam_requests.request_date DESC, exam_requests.request_id DESC
");
$requestsStmt->execute(array($collegeId));
$examRequests = $requestsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الامتحانات - ملتقى القرآن</title>

    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/exams.css">
</head>
<body>

<div class="app-layout">

    <aside class="sidebar">
        <div class="sidebar-top">
            <div class="sidebar-brand">
                <h2>ملتقى القرآن</h2>
            </div>

            <nav class="sidebar-menu">
                <a href="college-admin-dashboard.php">الرئيسية</a>
                <a href="students.php">الطلاب</a>
                <a href="halqas.php">الحلقات</a>
                <a href="weekly-recitation.php">التسميع الأسبوعي</a>
                <a href="exams.php" class="active">الامتحانات</a>
                <a href="reports.php">التقارير</a>
            </nav>
        </div>

        <div class="sidebar-bottom">
            <div class="sidebar-user">
                <h4><?php echo e($managerName); ?></h4>
                <p><?php echo e($collegeName); ?></p>
            </div>
        </div>
    </aside>

    <main class="main-area">
        <header class="topbar">
            <div class="topbar-right">
                <h1>الامتحانات</h1>
                <p>متابعة طلبات الامتحان وتوثيق النتائج</p>
            </div>

            <div class="topbar-left">
                <a class="topbar-home-link" href="../index.php">
                    <span aria-hidden="true">↩</span>
                    <span>العودة للصفحة الرئيسية</span>
                </a>
                <div class="topbar-badge">آخر تحديث اليوم</div>
            </div>
        </header>

        <section class="page-content">
            <section class="stats-grid">
                <div class="stat-card card">
                    <h3><?php echo e(formatNumberValue($summary["total_requests"])); ?></h3>
                    <p>طلبات الامتحان</p>
                </div>
                <div class="stat-card card">
                    <h3><?php echo e(formatNumberValue($summary["pending_requests"])); ?></h3>
                    <p>قيد الانتظار</p>
                </div>
                <div class="stat-card card">
                    <h3><?php echo e(formatNumberValue($summary["completed_requests"])); ?></h3>
                    <p>تم إكمالها</p>
                </div>
                <div class="stat-card card">
                    <h3><?php echo e(formatNumberValue($averageScoreRow["average_score"])); ?></h3>
                    <p>متوسط أعلى الدرجات</p>
                </div>
            </section>

            <section class="card panel">
                <div class="panel-header">
                    <h2>طلبات الامتحان</h2>
                    <span><?php echo e(formatNumberValue($summary["total_requests"])); ?> طلب</span>
                </div>

                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>اسم الطالب</th>
                                <th>رقم التسجيل</th>
                                <th>الحلقة</th>
                                <th>الجزء المطلوب</th>
                                <th>تاريخ الطلب</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($examRequests) === 0): ?>
                                <tr>
                                    <td colspan="6">لا يوجد طلبات امتحان</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($examRequests as $request): ?>
                                    <tr>
                                        <td><?php echo e(fallbackValue($request["student_name"], "لا يوجد بيانات")); ?></td>
                                        <td><?php echo e($request["student_id"]); ?></td>
                                        <td><?php echo e(fallbackValue($request["halqa_name"], "لا يوجد بيانات")); ?></td>
                                        <td><?php echo e(fallbackValue($request["requested_part"], "لا يوجد بيانات")); ?></td>
                                        <td><?php echo e(fallbackValue($request["request_date"], "لا يوجد بيانات")); ?></td>
                                        <td><span class="status-badge <?php echo e(statusClass($request["status"])); ?>"><?php echo e(statusLabel($request["status"])); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </section>
    </main>

</div>

</body>
</html>
