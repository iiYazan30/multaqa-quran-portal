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

function formatPercentValue($value)
{
    return formatNumberValue($value) . "%";
}

function formatHalqaHeaderName($name)
{
    $name = fallbackValue($name, "لا يوجد بيانات");
    return strpos($name, "حلقة") === 0 ? $name : "حلقة " . $name;
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

$statsStmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN recitations.type = 'حفظ' THEN recitations.pages_count ELSE 0 END), 0) AS memorization_pages,
        COALESCE(SUM(CASE WHEN recitations.type = 'مراجعة' THEN recitations.pages_count ELSE 0 END), 0) AS revision_pages,
        COUNT(DISTINCT recitations.student_id) AS heard_students
    FROM recitations
    JOIN students ON recitations.student_id = students.student_id
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    WHERE halqas.college_id = ? AND recitations.week_id = ?
");
$statsStmt->execute(array($collegeId, $selectedWeekId));
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$totalStudentsStmt = $pdo->prepare("
    SELECT COUNT(students.student_id) AS total_students
    FROM students
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    WHERE halqas.college_id = ?
");
$totalStudentsStmt->execute(array($collegeId));
$totalStudentsRow = $totalStudentsStmt->fetch(PDO::FETCH_ASSOC);

$supervisorsStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT supervisors.supervisor_id) AS supervisors_count
    FROM supervisors
    JOIN halqas ON supervisors.supervisor_id = halqas.supervisor_id
    WHERE halqas.college_id = ?
");
$supervisorsStmt->execute(array($collegeId));
$supervisorsRow = $supervisorsStmt->fetch(PDO::FETCH_ASSOC);

$memorizationPages = $stats ? numberValue($stats["memorization_pages"]) : 0;
$revisionPages = $stats ? numberValue($stats["revision_pages"]) : 0;
$totalPages = $memorizationPages + $revisionPages;
$heardStudents = $stats ? numberValue($stats["heard_students"]) : 0;
$totalStudents = $totalStudentsRow ? numberValue($totalStudentsRow["total_students"]) : 0;
$supervisorsCount = $supervisorsRow ? numberValue($supervisorsRow["supervisors_count"]) : 0;
$recitationRate = $totalStudents > 0 ? round(($heardStudents / $totalStudents) * 100, 1) : 0;

$reportStmt = $pdo->prepare("
    SELECT
        halqas.halqa_id,
        halqas.name AS halqa_name,
        supervisors.name AS supervisor_name,
        students.student_id,
        students.name AS student_name,
        students.student_type,
        students.phone,
        COALESCE(weekly_recitations.memorization_pages, 0) AS memorization_pages,
        COALESCE(weekly_recitations.revision_pages, 0) AS revision_pages,
        COALESCE(weekly_recitations.recitation_count, 0) AS recitation_count,
        COALESCE(exam_counts.exams_count, 0) AS exams_count
    FROM halqas
    LEFT JOIN supervisors ON halqas.supervisor_id = supervisors.supervisor_id
    LEFT JOIN students ON halqas.halqa_id = students.halqa_id
    LEFT JOIN (
        SELECT
            student_id,
            SUM(CASE WHEN type = 'حفظ' THEN pages_count ELSE 0 END) AS memorization_pages,
            SUM(CASE WHEN type = 'مراجعة' THEN pages_count ELSE 0 END) AS revision_pages,
            COUNT(recitation_id) AS recitation_count
        FROM recitations
        WHERE week_id = ?
        GROUP BY student_id
    ) weekly_recitations ON students.student_id = weekly_recitations.student_id
    LEFT JOIN (
        SELECT student_id, COUNT(exam_id) AS exams_count
        FROM exams
        GROUP BY student_id
    ) exam_counts ON students.student_id = exam_counts.student_id
    WHERE halqas.college_id = ?
    ORDER BY halqas.name ASC, students.name ASC
");
$reportStmt->execute(array($selectedWeekId, $collegeId));
$reportRows = $reportStmt->fetchAll(PDO::FETCH_ASSOC);

$groupedRows = array();
foreach ($reportRows as $row) {
    $halqaId = $row["halqa_id"];
    if (!isset($groupedRows[$halqaId])) {
        $groupedRows[$halqaId] = array(
            "halqa_name" => $row["halqa_name"],
            "supervisor_name" => $row["supervisor_name"],
            "students" => array()
        );
    }

    if ($row["student_id"] !== null && $row["student_id"] !== "") {
        $groupedRows[$halqaId]["students"][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التسميع الأسبوعي - ملتقى القرآن</title>

    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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
                <a href="weekly-recitation.php" class="active">التسميع الأسبوعي</a>
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
                <h1>التسميع الأسبوعي</h1>
                <p>عرض جميع تسميعات الطلاب حسب الحلقات</p>
            </div>

            <div class="topbar-left">
                <a class="topbar-home-link" href="../index.php">
                    <span aria-hidden="true">↩</span>
                    <span>العودة للصفحة الرئيسية</span>
                </a>
                <form method="GET" action="weekly-recitation.php" class="admin-week-filter">
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

            <!-- Weekly Recitation Report by Halaqa -->
            <section class="weekly-report-section">
                <div class="dashboard-panel card weekly-report-panel">
                    <div class="panel-header">
                        <h2>تقرير التسميع الأسبوعي حسب الحلقات</h2>
                        <span><?php echo e($selectedWeekLabel); ?></span>
                    </div>

                    <div class="weekly-report-table-wrap">
                        <table class="weekly-report-table grouped-report-table">
                            <thead>
                                <tr>
                                    <th>اسم الطالب</th>
                                    <th>تصنيف الطالب</th>
                                    <th>رقم التسجيل</th>
                                    <th>رقم الجوال</th>
                                    <th>اسم الحلقة</th>
                                    <th>حفظ</th>
                                    <th>تثبيت</th>
                                    <th>امتحانات</th>
                                    <th>الملاحظات</th>
                                </tr>
                            </thead>

                            <?php if (count($groupedRows) === 0): ?>
                                <tbody class="halaqa-group">
                                    <tr>
                                        <td colspan="9">لا يوجد بيانات لهذا الأسبوع</td>
                                    </tr>
                                </tbody>
                            <?php else: ?>
                                <?php foreach ($groupedRows as $group): ?>
                                    <tbody class="halaqa-group">
                                        <tr class="halaqa-group-row">
                                            <th colspan="9"><?php echo e(formatHalqaHeaderName($group["halqa_name"])); ?> - المشرف: <?php echo e(fallbackValue($group["supervisor_name"], "لا يوجد بيانات")); ?></th>
                                        </tr>
                                        <?php if (count($group["students"]) === 0): ?>
                                            <tr>
                                                <td colspan="9">لا يوجد بيانات لهذا الأسبوع</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($group["students"] as $student): ?>
                                                <?php
                                                $recitationCount = numberValue($student["recitation_count"]);
                                                $studentStatus = $recitationCount > 0 ? "-" : "لم يسمع";
                                                ?>
                                                <tr>
                                                    <td><?php echo e(fallbackValue($student["student_name"], "لا يوجد بيانات")); ?></td>
                                                    <td><?php echo e(fallbackValue($student["student_type"], "لا يوجد بيانات")); ?></td>
                                                    <td><?php echo e($student["student_id"]); ?></td>
                                                    <td><?php echo e(fallbackValue($student["phone"], "لا يوجد بيانات")); ?></td>
                                                    <td><?php echo e(fallbackValue($student["halqa_name"], "لا يوجد بيانات")); ?></td>
                                                    <td><?php echo e(formatNumberValue($student["memorization_pages"])); ?></td>
                                                    <td><?php echo e(formatNumberValue($student["revision_pages"])); ?></td>
                                                    <td><?php echo e(formatNumberValue($student["exams_count"])); ?></td>
                                                    <td><?php echo e($studentStatus); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </section>
        </section>
    </main>

</div>

<script src="../assets/js/main.js"></script>

</body>
</html>
