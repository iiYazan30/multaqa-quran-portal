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
        return (string) (int) $number;
    }

    return rtrim(rtrim(number_format($number, 1, ".", ""), "0"), ".");
}

function formatPercentValue($value)
{
    return formatNumberValue($value) . "%";
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

$weeksStmt = $pdo->prepare("
    SELECT week_id, week_number
    FROM weeks
    ORDER BY week_number ASC
");
$weeksStmt->execute();
$weeks = $weeksStmt->fetchAll(PDO::FETCH_ASSOC);

$latestWeekStmt = $pdo->prepare("
    SELECT MAX(week_id) AS latest_week_id
    FROM weeks
");
$latestWeekStmt->execute();
$latestWeekRow = $latestWeekStmt->fetch(PDO::FETCH_ASSOC);
$latestWeekId = $latestWeekRow && $latestWeekRow["latest_week_id"] ? numberValue($latestWeekRow["latest_week_id"]) : 0;

$selectedWeekId = $latestWeekId;
if (isset($_GET["week_id"]) && $_GET["week_id"] !== "") {
    $requestedWeekId = numberValue($_GET["week_id"]);
    foreach ($weeks as $weekOption) {
        if ((int) $weekOption["week_id"] === $requestedWeekId) {
            $selectedWeekId = $requestedWeekId;
            break;
        }
    }
}

$selectedWeekNumber = "";
foreach ($weeks as $weekOption) {
    if ((int) $weekOption["week_id"] === (int) $selectedWeekId) {
        $selectedWeekNumber = $weekOption["week_number"];
        break;
    }
}
$selectedWeekLabel = $selectedWeekNumber !== "" ? "الأسبوع " . $selectedWeekNumber : "لا يوجد أسبوع مسجل";

$recitationStatsStmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN recitations.type = 'حفظ' THEN recitations.pages_count ELSE 0 END), 0) AS memorization_pages,
        COALESCE(SUM(CASE WHEN recitations.type = 'مراجعة' THEN recitations.pages_count ELSE 0 END), 0) AS revision_pages
    FROM recitations
    JOIN students ON recitations.student_id = students.student_id
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    WHERE halqas.college_id = ? AND recitations.week_id = ?
");
$recitationStatsStmt->execute(array($collegeId, $selectedWeekId));
$recitationStats = $recitationStatsStmt->fetch(PDO::FETCH_ASSOC);
$memorizationPages = $recitationStats ? numberValue($recitationStats["memorization_pages"]) : 0;
$revisionPages = $recitationStats ? numberValue($recitationStats["revision_pages"]) : 0;
$totalPages = $memorizationPages + $revisionPages;

$totalStudentsStmt = $pdo->prepare("
    SELECT COUNT(students.student_id) AS total_students
    FROM students
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    WHERE halqas.college_id = ?
");
$totalStudentsStmt->execute(array($collegeId));
$totalStudentsRow = $totalStudentsStmt->fetch(PDO::FETCH_ASSOC);
$totalStudents = $totalStudentsRow ? numberValue($totalStudentsRow["total_students"]) : 0;

$heardStudentsStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT recitations.student_id) AS heard_students
    FROM recitations
    JOIN students ON recitations.student_id = students.student_id
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    WHERE halqas.college_id = ? AND recitations.week_id = ?
");
$heardStudentsStmt->execute(array($collegeId, $selectedWeekId));
$heardStudentsRow = $heardStudentsStmt->fetch(PDO::FETCH_ASSOC);
$heardStudents = $heardStudentsRow ? numberValue($heardStudentsRow["heard_students"]) : 0;
$recitationRate = $totalStudents > 0 ? round(($heardStudents / $totalStudents) * 100, 1) : 0;

$supervisorsStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT supervisors.supervisor_id) AS supervisors_count
    FROM supervisors
    JOIN halqas ON supervisors.supervisor_id = halqas.supervisor_id
    WHERE halqas.college_id = ?
");
$supervisorsStmt->execute(array($collegeId));
$supervisorsRow = $supervisorsStmt->fetch(PDO::FETCH_ASSOC);
$supervisorsCount = $supervisorsRow ? numberValue($supervisorsRow["supervisors_count"]) : 0;

$topStudentsStmt = $pdo->prepare("
    SELECT
        students.name AS student_name,
        halqas.name AS halqa_name,
        COALESCE(SUM(recitations.pages_count), 0) AS weekly_pages
    FROM recitations
    JOIN students ON recitations.student_id = students.student_id
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    WHERE halqas.college_id = ? AND recitations.week_id = ?
    GROUP BY students.student_id, students.name, halqas.name
    HAVING weekly_pages > 0
    ORDER BY weekly_pages DESC, students.student_id ASC
    LIMIT 3
");
$topStudentsStmt->execute(array($collegeId, $selectedWeekId));
$topStudents = $topStudentsStmt->fetchAll(PDO::FETCH_ASSOC);

$featuredHalqasStmt = $pdo->prepare("
    SELECT
        halqas.halqa_id,
        halqas.name AS halqa_name,
        supervisors.name AS supervisor_name,
        COUNT(DISTINCT students.student_id) AS total_students,
        COUNT(DISTINCT recitations.student_id) AS heard_students
    FROM halqas
    LEFT JOIN supervisors ON halqas.supervisor_id = supervisors.supervisor_id
    LEFT JOIN students ON halqas.halqa_id = students.halqa_id
    LEFT JOIN recitations ON students.student_id = recitations.student_id AND recitations.week_id = ?
    WHERE halqas.college_id = ?
    GROUP BY halqas.halqa_id, halqas.name, supervisors.name
    ORDER BY
        CASE
            WHEN COUNT(DISTINCT students.student_id) > 0
            THEN COUNT(DISTINCT recitations.student_id) / COUNT(DISTINCT students.student_id)
            ELSE 0
        END DESC,
        halqas.halqa_id ASC
    LIMIT 3
");
$featuredHalqasStmt->execute(array($selectedWeekId, $collegeId));
$featuredHalqas = $featuredHalqasStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - ملتقى القرآن</title>

    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=week-filter-20260518">

</head>
<body>

<div class="app-layout">

    <aside class="sidebar">
        <div class="sidebar-top">
            <div class="sidebar-brand">
                <h2>ملتقى القرآن</h2>
            </div>

            <nav class="sidebar-menu">
                <a href="college-admin-dashboard.php" class="active">الرئيسية</a>
                <a href="students.php">الطلاب</a>
                <a href="halqas.php">الحلقات</a>
                <a href="weekly-recitation.php">التسميع الأسبوعي</a>
                <a href="exams.php">الامتحانات</a>
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
                <h1>لوحة التحكم</h1>
                <p>نظرة عامة على الأداء والإحصائيات</p>
            </div>

            <div class="topbar-left">
                <a class="topbar-home-link" href="../index.php">
                    <span aria-hidden="true">↩</span>
                    <span>العودة للصفحة الرئيسية</span>
                </a>
                <form method="GET" action="college-admin-dashboard.php" class="admin-week-filter">
                    <label class="week-filter-label" for="week_id">الأسبوع</label>
                    <select class="week-select" id="week_id" name="week_id" onchange="this.form.submit()" aria-label="<?php echo e($selectedWeekLabel); ?>">
                        <?php if (count($weeks) === 0): ?>
                            <option value="0">لا يوجد أسبوع مسجل</option>
                        <?php else: ?>
                            <?php foreach ($weeks as $week): ?>
                                <option value="<?php echo e($week["week_id"]); ?>"<?php echo (int) $week["week_id"] === (int) $selectedWeekId ? " selected" : ""; ?>>
                                    الأسبوع <?php echo e($week["week_number"]); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </form>
            </div>
        </header>

        <section class="page-content">

            <!-- Statistics Cards -->
            <section class="stats-grid">
                <div class="stat-card card">
                    <div class="stat-info">
                        <h3><?php echo e(formatNumberValue($memorizationPages)); ?></h3>
                        <p>عدد صفحات الحفظ</p>
                    </div>
                </div>

                <div class="stat-card card">
                    <div class="stat-info">
                        <h3><?php echo e(formatNumberValue($revisionPages)); ?></h3>
                        <p>عدد صفحات المراجعة</p>
                    </div>
                </div>

                <div class="stat-card card">
                    <div class="stat-info">
                        <h3><?php echo e(formatNumberValue($totalPages)); ?></h3>
                        <p>المجموع</p>
                    </div>
                </div>

                <div class="stat-card card">
                    <div class="stat-info">
                        <h3><?php echo e(formatPercentValue($recitationRate)); ?></h3>
                        <p>نسبة التسميع</p>
                    </div>
                </div>

                <div class="stat-card card">
                    <div class="stat-info">
                        <h3><?php echo e(formatNumberValue($totalStudents)); ?></h3>
                        <p>عدد الطلاب الكلي</p>
                    </div>
                </div>

                <div class="stat-card card">
                    <div class="stat-info">
                        <h3><?php echo e(formatNumberValue($heardStudents)); ?></h3>
                        <p>عدد الطلاب المسمعين</p>
                    </div>
                </div>

                <div class="stat-card card">
                    <div class="stat-info">
                        <h3><?php echo e(formatNumberValue($supervisorsCount)); ?></h3>
                        <p>عدد المشرفين</p>
                    </div>
                </div>
            </section>

            <!-- Main Dashboard Sections -->
            <section class="dashboard-grid">

                <div class="dashboard-panel card">
                    <div class="panel-header">
                        <h2>الحلقات المميزة</h2>
                        <span><?php echo e($selectedWeekLabel); ?></span>
                    </div>

                    <div class="featured-list">
                        <?php if (count($featuredHalqas) === 0): ?>
                            <div class="featured-item">
                                <div>
                                    <h4>لا يوجد بيانات</h4>
                                    <p>المشرف: لا يوجد بيانات</p>
                                </div>
                                <strong>0%</strong>
                            </div>
                        <?php else: ?>
                            <?php foreach ($featuredHalqas as $halqa): ?>
                                <?php
                                $halqaTotalStudents = numberValue($halqa["total_students"]);
                                $halqaHeardStudents = numberValue($halqa["heard_students"]);
                                $halqaRate = $halqaTotalStudents > 0 ? round(($halqaHeardStudents / $halqaTotalStudents) * 100, 1) : 0;
                                ?>
                                <div class="featured-item">
                                    <div>
                                        <h4><?php echo e(fallbackValue($halqa["halqa_name"], "لا يوجد بيانات")); ?></h4>
                                        <p>المشرف: <?php echo e(fallbackValue($halqa["supervisor_name"], "لا يوجد بيانات")); ?> - الطلاب: <?php echo e($halqaTotalStudents); ?></p>
                                    </div>
                                    <strong><?php echo e(formatPercentValue($halqaRate)); ?></strong>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dashboard-panel card">
                    <div class="panel-header">
                        <h2>الطلاب الأعلى تسميعًا</h2>
                        <span><?php echo e($selectedWeekLabel); ?></span>
                    </div>

                    <div class="featured-list">
                        <?php if (count($topStudents) === 0): ?>
                            <div class="featured-item">
                                <div>
                                    <h4>لا يوجد بيانات</h4>
                                    <p>لا يوجد تسميع في هذا الأسبوع</p>
                                </div>
                                <strong>0</strong>
                            </div>
                        <?php else: ?>
                            <?php foreach ($topStudents as $student): ?>
                                <div class="featured-item">
                                    <div>
                                        <h4><?php echo e(fallbackValue($student["student_name"], "لا يوجد بيانات")); ?></h4>
                                        <p><?php echo e(fallbackValue($student["halqa_name"], "لا يوجد بيانات")); ?></p>
                                    </div>
                                    <strong><?php echo e(formatNumberValue($student["weekly_pages"])); ?></strong>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </section>

        </section>
    </main>

</div>

</body>
</html>
