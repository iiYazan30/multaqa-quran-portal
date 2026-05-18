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
    SELECT week_id, week_number
    FROM weeks
    ORDER BY week_number DESC
    LIMIT 1
");
$latestWeekStmt->execute();
$latestWeek = $latestWeekStmt->fetch(PDO::FETCH_ASSOC);
$latestWeekId = $latestWeek && $latestWeek["week_id"] ? numberValue($latestWeek["week_id"]) : 0;

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

$halqasStmt = $pdo->prepare("
    SELECT
        halqas.halqa_id,
        halqas.name AS halqa_name,
        supervisors.name AS supervisor_name,
        COUNT(DISTINCT students.student_id) AS total_students,
        COUNT(DISTINCT CASE WHEN students.student_type = 'حفظ' THEN students.student_id END) AS hifz_students,
        COUNT(DISTINCT CASE WHEN students.student_type = 'تثبيت' THEN students.student_id END) AS tathbit_students,
        COUNT(DISTINCT recitations.student_id) AS heard_students
    FROM halqas
    LEFT JOIN supervisors ON halqas.supervisor_id = supervisors.supervisor_id
    LEFT JOIN students ON halqas.halqa_id = students.halqa_id
    LEFT JOIN recitations ON students.student_id = recitations.student_id AND recitations.week_id = ?
    WHERE halqas.college_id = ?
    GROUP BY halqas.halqa_id, halqas.name, supervisors.name
    ORDER BY halqas.name ASC
");
$halqasStmt->execute(array($selectedWeekId, $collegeId));
$halqas = $halqasStmt->fetchAll(PDO::FETCH_ASSOC);

$topHalqas = $halqas;
usort($topHalqas, function ($a, $b) {
    $aTotal = numberValue($a["total_students"]);
    $bTotal = numberValue($b["total_students"]);
    $aRate = $aTotal > 0 ? numberValue($a["heard_students"]) / $aTotal : 0;
    $bRate = $bTotal > 0 ? numberValue($b["heard_students"]) / $bTotal : 0;

    if ($aRate == $bRate) {
        return (int) $a["halqa_id"] - (int) $b["halqa_id"];
    }

    return $aRate < $bRate ? 1 : -1;
});
$topHalqas = array_slice($topHalqas, 0, 3);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الحلقات - ملتقى القرآن</title>

    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/halqas.css">
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
                <a href="halqas.php" class="active">الحلقات</a>
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
                <h1>الحلقات</h1>
                <p>متابعة أداء الحلقات وعدد الطلاب لكل حلقة</p>
            </div>

            <div class="topbar-left">
                <a class="topbar-home-link" href="../index.php">
                    <span aria-hidden="true">↩</span>
                    <span>العودة للصفحة الرئيسية</span>
                </a>
                <form method="GET" action="halqas.php" class="admin-week-filter">
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
            <section class="halqa-grid">
                <?php foreach ($topHalqas as $halqa): ?>
                    <?php
                    $totalStudents = numberValue($halqa["total_students"]);
                    $heardStudents = numberValue($halqa["heard_students"]);
                    $recitationRate = $totalStudents > 0 ? round(($heardStudents / $totalStudents) * 100, 1) : 0;
                    ?>
                    <article class="card halqa-card">
                        <h3><?php echo e(fallbackValue($halqa["halqa_name"], "لا يوجد بيانات")); ?></h3>
                        <p>المشرف: <?php echo e(fallbackValue($halqa["supervisor_name"], "لا يوجد بيانات")); ?></p>
                        <div class="meta-row">
                            <span>الطلاب: <?php echo e(formatNumberValue($totalStudents)); ?></span>
                            <strong><?php echo e(formatNumberValue($recitationRate)); ?>%</strong>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="card panel">
                <div class="panel-header">
                    <h2>قائمة الحلقات</h2>
                    <span><?php echo e($selectedWeekLabel); ?></span>
                </div>

                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>اسم الحلقة</th>
                                <th>المشرف</th>
                                <th>عدد الطلاب</th>
                                <th>طلاب الحفظ</th>
                                <th>طلاب التثبيت</th>
                                <th>نسبة التسميع</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($halqas) === 0): ?>
                                <tr>
                                    <td colspan="6">لا يوجد حلقات مسجلة</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($halqas as $halqa): ?>
                                    <?php
                                    $totalStudents = numberValue($halqa["total_students"]);
                                    $heardStudents = numberValue($halqa["heard_students"]);
                                    $recitationRate = $totalStudents > 0 ? round(($heardStudents / $totalStudents) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo e(fallbackValue($halqa["halqa_name"], "لا يوجد بيانات")); ?></td>
                                        <td><?php echo e(fallbackValue($halqa["supervisor_name"], "لا يوجد بيانات")); ?></td>
                                        <td><?php echo e(formatNumberValue($totalStudents)); ?></td>
                                        <td><?php echo e(formatNumberValue($halqa["hifz_students"])); ?></td>
                                        <td><?php echo e(formatNumberValue($halqa["tathbit_students"])); ?></td>
                                        <td><?php echo e(formatNumberValue($recitationRate)); ?>%</td>
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
