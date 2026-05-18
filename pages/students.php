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

$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";
$selectedHalqaId = isset($_GET["halqa_id"]) ? (int) $_GET["halqa_id"] : 0;
$selectedStudentType = isset($_GET["student_type"]) ? trim($_GET["student_type"]) : "";
if ($selectedStudentType !== "حفظ" && $selectedStudentType !== "تثبيت") {
    $selectedStudentType = "";
}

$latestWeekStmt = $pdo->prepare("
    SELECT week_id, week_number
    FROM weeks
    ORDER BY week_number DESC
    LIMIT 1
");
$latestWeekStmt->execute();
$latestWeek = $latestWeekStmt->fetch(PDO::FETCH_ASSOC);
$latestWeekId = $latestWeek && $latestWeek["week_id"] ? numberValue($latestWeek["week_id"]) : 0;
$latestWeekLabel = $latestWeek && $latestWeek["week_number"] ? "الأسبوع " . $latestWeek["week_number"] : "لا يوجد أسبوع مسجل";

$summaryStmt = $pdo->prepare("
    SELECT
        COUNT(students.student_id) AS total_students,
        COALESCE(SUM(CASE WHEN students.student_type = 'حفظ' THEN 1 ELSE 0 END), 0) AS hifz_students,
        COALESCE(SUM(CASE WHEN students.student_type = 'تثبيت' THEN 1 ELSE 0 END), 0) AS tathbit_students,
        COALESCE(SUM(students.points), 0) AS total_points
    FROM students
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    WHERE halqas.college_id = ?
");
$summaryStmt->execute(array($collegeId));
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

$halqasStmt = $pdo->prepare("
    SELECT halqa_id, name
    FROM halqas
    WHERE college_id = ?
    ORDER BY name ASC
");
$halqasStmt->execute(array($collegeId));
$halqas = $halqasStmt->fetchAll(PDO::FETCH_ASSOC);

$overviewTotalsStmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN recitations.type = 'حفظ' THEN recitations.pages_count ELSE 0 END), 0) AS memorization_pages,
        COALESCE(SUM(CASE WHEN recitations.type = 'مراجعة' THEN recitations.pages_count ELSE 0 END), 0) AS revision_pages
    FROM recitations
    JOIN students ON recitations.student_id = students.student_id
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    WHERE halqas.college_id = ?
");
$overviewTotalsStmt->execute(array($collegeId));
$overviewTotals = $overviewTotalsStmt->fetch(PDO::FETCH_ASSOC);

$halqaOverviewStmt = $pdo->prepare("
    SELECT
        halqas.halqa_id,
        halqas.name AS halqa_name,
        COALESCE(student_counts.total_students, 0) AS total_students,
        COALESCE(recitation_totals.memorization_pages, 0) AS memorization_pages,
        COALESCE(recitation_totals.revision_pages, 0) AS revision_pages,
        COALESCE(student_points.total_points, 0) AS total_points,
        COALESCE(weekly_heard.heard_students, 0) AS heard_students
    FROM halqas
    LEFT JOIN (
        SELECT halqa_id, COUNT(student_id) AS total_students
        FROM students
        GROUP BY halqa_id
    ) student_counts ON halqas.halqa_id = student_counts.halqa_id
    LEFT JOIN (
        SELECT students.halqa_id,
            SUM(CASE WHEN recitations.type = 'حفظ' THEN recitations.pages_count ELSE 0 END) AS memorization_pages,
            SUM(CASE WHEN recitations.type = 'مراجعة' THEN recitations.pages_count ELSE 0 END) AS revision_pages
        FROM students
        JOIN recitations ON students.student_id = recitations.student_id
        GROUP BY students.halqa_id
    ) recitation_totals ON halqas.halqa_id = recitation_totals.halqa_id
    LEFT JOIN (
        SELECT halqa_id, SUM(points) AS total_points
        FROM students
        GROUP BY halqa_id
    ) student_points ON halqas.halqa_id = student_points.halqa_id
    LEFT JOIN (
        SELECT students.halqa_id, COUNT(DISTINCT recitations.student_id) AS heard_students
        FROM students
        JOIN recitations ON students.student_id = recitations.student_id
        WHERE recitations.week_id = ?
        GROUP BY students.halqa_id
    ) weekly_heard ON halqas.halqa_id = weekly_heard.halqa_id
    WHERE halqas.college_id = ?
    ORDER BY halqas.name ASC
");
$halqaOverviewStmt->execute(array($latestWeekId, $collegeId));
$halqaOverviewRows = $halqaOverviewStmt->fetchAll(PDO::FETCH_ASSOC);

$weeklyReportStmt = $pdo->prepare("
    SELECT
        students.student_id,
        students.name AS student_name,
        halqas.name AS halqa_name,
        COALESCE(SUM(CASE WHEN recitations.type = 'حفظ' THEN recitations.pages_count ELSE 0 END), 0) AS memorization_pages,
        COALESCE(SUM(CASE WHEN recitations.type = 'مراجعة' THEN recitations.pages_count ELSE 0 END), 0) AS revision_pages,
        GROUP_CONCAT(CONCAT(recitations.type, ' ', recitations.from_page, '-', recitations.to_page) ORDER BY recitations.recitation_id SEPARATOR '، ') AS page_ranges
    FROM recitations
    JOIN students ON recitations.student_id = students.student_id
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    JOIN weeks ON recitations.week_id = weeks.week_id
    WHERE halqas.college_id = ? AND recitations.week_id = ?
    GROUP BY students.student_id, students.name, halqas.name
    ORDER BY halqas.name ASC, students.name ASC
");
$weeklyReportStmt->execute(array($collegeId, $latestWeekId));
$weeklyReportRows = $weeklyReportStmt->fetchAll(PDO::FETCH_ASSOC);

$allReportStmt = $pdo->prepare("
    SELECT
        students.student_id,
        students.name AS student_name,
        COALESCE(SUM(CASE WHEN recitations.type = 'حفظ' THEN recitations.pages_count ELSE 0 END), 0) AS memorization_pages,
        COALESCE(SUM(CASE WHEN recitations.type = 'مراجعة' THEN recitations.pages_count ELSE 0 END), 0) AS revision_pages,
        GROUP_CONCAT(CONCAT(recitations.from_page, '-', recitations.to_page) ORDER BY recitations.week_id, recitations.recitation_id SEPARATOR '، ') AS page_ranges
    FROM students
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    LEFT JOIN recitations ON students.student_id = recitations.student_id
    WHERE halqas.college_id = ?
    GROUP BY students.student_id, students.name
    HAVING memorization_pages > 0 OR revision_pages > 0
    ORDER BY students.name ASC
");
$allReportStmt->execute(array($collegeId));
$allReportRows = $allReportStmt->fetchAll(PDO::FETCH_ASSOC);

$studentWhere = array("halqas.college_id = ?");
$studentParams = array($collegeId);

if ($search !== "") {
    $studentWhere[] = "(students.name LIKE ? OR students.student_id = ?)";
    $studentParams[] = "%" . $search . "%";
    $studentParams[] = ctype_digit($search) ? (int) $search : 0;
}

if ($selectedHalqaId > 0) {
    $studentWhere[] = "halqas.halqa_id = ?";
    $studentParams[] = $selectedHalqaId;
}

if ($selectedStudentType !== "") {
    $studentWhere[] = "students.student_type = ?";
    $studentParams[] = $selectedStudentType;
}

$studentsStmt = $pdo->prepare("
    SELECT
        students.student_id,
        students.name AS student_name,
        students.student_type,
        COALESCE(students.points, 0) AS points,
        halqas.name AS halqa_name,
        supervisors.name AS supervisor_name,
        GROUP_CONCAT(DISTINCT exams.part_name ORDER BY exams.exam_date ASC, exams.exam_id ASC SEPARATOR '، ') AS exam_parts
    FROM students
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    LEFT JOIN supervisors ON halqas.supervisor_id = supervisors.supervisor_id
    LEFT JOIN exams ON students.student_id = exams.student_id
    WHERE " . implode(" AND ", $studentWhere) . "
    GROUP BY students.student_id, students.name, students.student_type, students.points, halqas.name, supervisors.name
    ORDER BY students.name ASC
");
$studentsStmt->execute($studentParams);
$studentRows = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الطلاب - ملتقى القرآن</title>

    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/students.css">
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
                <a href="students.php" class="active">الطلاب</a>
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
                <h1>الطلاب</h1>
                <p>متابعة طلاب الكلية حسب الحلقة والمشرف</p>
            </div>

            <div class="topbar-left">
                <a class="topbar-home-link" href="../index.php">
                    <span aria-hidden="true">↩</span>
                    <span>العودة للصفحة الرئيسية</span>
                </a>
                <div class="topbar-badge"><?php echo e($latestWeekLabel); ?></div>
            </div>
        </header>

        <section class="page-content">
            <section class="stats-grid">
                <div class="stat-card card">
                    <h3><?php echo e(formatNumberValue($summary["total_students"])); ?></h3>
                    <p>إجمالي الطلاب</p>
                </div>
                <div class="stat-card card">
                    <h3><?php echo e(formatNumberValue($summary["hifz_students"])); ?></h3>
                    <p>طلاب حفظ</p>
                </div>
                <div class="stat-card card">
                    <h3><?php echo e(formatNumberValue($summary["tathbit_students"])); ?></h3>
                    <p>طلاب تثبيت</p>
                </div>
                <div class="stat-card card">
                    <h3><?php echo e(formatNumberValue($summary["total_points"])); ?></h3>
                    <p>إجمالي النقاط</p>
                </div>
            </section>

            <section class="card panel">
                <div class="panel-header">
                    <h2>نظرة عامة على تسميع الطلاب من بداية الفصل الحالي</h2>
                    <span>حتى <?php echo e($latestWeekLabel); ?></span>
                </div>

                <div class="overview-grid">
                    <div class="overview-card">
                        <h3><?php echo e(formatNumberValue($overviewTotals["memorization_pages"])); ?></h3>
                        <p>إجمالي صفحات الحفظ</p>
                    </div>
                    <div class="overview-card">
                        <h3><?php echo e(formatNumberValue($overviewTotals["revision_pages"])); ?></h3>
                        <p>إجمالي صفحات المراجعة</p>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>اسم الحلقة</th>
                                <th>عدد الطلاب</th>
                                <th>إجمالي الحفظ</th>
                                <th>إجمالي المراجعة</th>
                                <th>إجمالي النقاط</th>
                                <th>نسبة التسميع</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($halqaOverviewRows) === 0): ?>
                                <tr>
                                    <td colspan="6">لا يوجد بيانات</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($halqaOverviewRows as $halqa): ?>
                                    <?php
                                    $halqaStudents = numberValue($halqa["total_students"]);
                                    $halqaHeard = numberValue($halqa["heard_students"]);
                                    $halqaRate = $halqaStudents > 0 ? round(($halqaHeard / $halqaStudents) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo e(fallbackValue($halqa["halqa_name"], "لا يوجد بيانات")); ?></td>
                                        <td><?php echo e(formatNumberValue($halqaStudents)); ?></td>
                                        <td><?php echo e(formatNumberValue($halqa["memorization_pages"])); ?></td>
                                        <td><?php echo e(formatNumberValue($halqa["revision_pages"])); ?></td>
                                        <td><?php echo e(formatNumberValue($halqa["total_points"])); ?></td>
                                        <td><?php echo e(formatNumberValue($halqaRate)); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card panel">
                <div class="panel-header">
                    <h2>تقرير التسميع</h2>
                    <span><?php echo e($latestWeekLabel); ?></span>
                </div>

                <div class="report-range">
                    <button type="button" class="range-btn active" data-range="week" aria-pressed="true">هذا الأسبوع</button>
                    <button type="button" class="range-btn" data-range="all" aria-pressed="false">الإنجاز الكامل</button>
                </div>

                <div class="report-block" data-report="week">
                    <h3>تقرير هذا الأسبوع</h3>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>اسم الطالب</th>
                                    <th>الحلقة</th>
                                    <th>حفظ</th>
                                    <th>مراجعة</th>
                                    <th>مجموع الصفحات</th>
                                    <th>من وين لوين سمع</th>
                                    <th>نقاط الأسبوع</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($weeklyReportRows) === 0): ?>
                                    <tr>
                                        <td colspan="7">لا يوجد بيانات</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($weeklyReportRows as $reportRow): ?>
                                        <?php
                                        $memorizationPages = numberValue($reportRow["memorization_pages"]);
                                        $revisionPages = numberValue($reportRow["revision_pages"]);
                                        $weeklyPoints = ($memorizationPages * 5) + $revisionPages;
                                        ?>
                                        <tr>
                                            <td><?php echo e(fallbackValue($reportRow["student_name"], "لا يوجد بيانات")); ?></td>
                                            <td><?php echo e(fallbackValue($reportRow["halqa_name"], "لا يوجد بيانات")); ?></td>
                                            <td><?php echo e(formatNumberValue($memorizationPages)); ?></td>
                                            <td><?php echo e(formatNumberValue($revisionPages)); ?></td>
                                            <td><?php echo e(formatNumberValue($memorizationPages + $revisionPages)); ?></td>
                                            <td><?php echo e(fallbackValue($reportRow["page_ranges"], "لا يوجد بيانات")); ?></td>
                                            <td><?php echo e(formatNumberValue($weeklyPoints)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="report-block" data-report="all" hidden>
                    <h3>تقرير من بداية الملتقى</h3>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>اسم الطالب</th>
                                    <th>تاريخ الانضمام</th>
                                    <th>إجمالي الحفظ</th>
                                    <th>إجمالي المراجعة</th>
                                    <th>إجمالي الصفحات</th>
                                    <th>الصفحات المسمّعة من البداية</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($allReportRows) === 0): ?>
                                    <tr>
                                        <td colspan="6">لا يوجد بيانات</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($allReportRows as $reportRow): ?>
                                        <?php
                                        $memorizationPages = numberValue($reportRow["memorization_pages"]);
                                        $revisionPages = numberValue($reportRow["revision_pages"]);
                                        ?>
                                        <tr>
                                            <td><?php echo e(fallbackValue($reportRow["student_name"], "لا يوجد بيانات")); ?></td>
                                            <td>لا يوجد بيانات</td>
                                            <td><?php echo e(formatNumberValue($memorizationPages)); ?></td>
                                            <td><?php echo e(formatNumberValue($revisionPages)); ?></td>
                                            <td><?php echo e(formatNumberValue($memorizationPages + $revisionPages)); ?></td>
                                            <td><?php echo e(fallbackValue($reportRow["page_ranges"], "لا يوجد بيانات")); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="card panel">
                <div class="panel-header">
                    <h2>بيانات الطلاب الأساسية</h2>
                    <span>تفاصيل الهوية والتصنيف</span>
                </div>

                <form class="filter-row" method="GET" action="students.php">
                    <label class="sr-only" for="student-search">بحث الطلاب</label>
                    <input id="student-search" name="search" type="text" value="<?php echo e($search); ?>" placeholder="ابحث باسم الطالب أو رقم التسجيل">
                    <label class="sr-only" for="halqa-filter">فلتر الحلقة</label>
                    <select id="halqa-filter" name="halqa_id" onchange="this.form.submit()">
                        <option value="">كل الحلقات</option>
                        <?php foreach ($halqas as $halqa): ?>
                            <option value="<?php echo e($halqa["halqa_id"]); ?>"<?php echo (int) $halqa["halqa_id"] === (int) $selectedHalqaId ? " selected" : ""; ?>>
                                <?php echo e(fallbackValue($halqa["name"], "لا يوجد بيانات")); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label class="sr-only" for="type-filter">فلتر التصنيف</label>
                    <select id="type-filter" name="student_type" onchange="this.form.submit()">
                        <option value="">كل الأنواع</option>
                        <option value="حفظ"<?php echo $selectedStudentType === "حفظ" ? " selected" : ""; ?>>حفظ</option>
                        <option value="تثبيت"<?php echo $selectedStudentType === "تثبيت" ? " selected" : ""; ?>>تثبيت</option>
                    </select>
                </form>

                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>اسم الطالب</th>
                                <th>رقم التسجيل</th>
                                <th>الحلقة</th>
                                <th>المشرف</th>
                                <th>التصنيف</th>
                                <th>الأجزاء الممتحن فيها</th>
                                <th>النقاط</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($studentRows) === 0): ?>
                                <tr>
                                    <td colspan="7">لا يوجد بيانات</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($studentRows as $student): ?>
                                    <?php
                                    $typeClass = $student["student_type"] === "حفظ" ? "tag-hifz" : "tag-tathbit";
                                    ?>
                                    <tr>
                                        <td><?php echo e(fallbackValue($student["student_name"], "لا يوجد بيانات")); ?></td>
                                        <td><?php echo e($student["student_id"]); ?></td>
                                        <td><?php echo e(fallbackValue($student["halqa_name"], "لا يوجد بيانات")); ?></td>
                                        <td><?php echo e(fallbackValue($student["supervisor_name"], "لا يوجد بيانات")); ?></td>
                                        <td><span class="tag <?php echo e($typeClass); ?>"><?php echo e($student["student_type"]); ?></span></td>
                                        <td><?php echo e(fallbackValue($student["exam_parts"], "لا يوجد بيانات")); ?></td>
                                        <td><?php echo e(formatNumberValue($student["points"])); ?></td>
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

<script src="../assets/js/main.js"></script>

</body>
</html>
