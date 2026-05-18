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

function performanceLabel($rate)
{
    if ($rate >= 85) {
        return "ممتاز";
    }

    if ($rate >= 70) {
        return "جيد جدا";
    }

    return "جيد";
}

function suggestedAction($daysAbsent, $lastScore)
{
    if ($lastScore !== null && $lastScore !== "" && (float) $lastScore < 50) {
        return "إعادة اختبار تحفيزي";
    }

    if ($daysAbsent >= 14) {
        return "جلسة متابعة مع المشرف";
    }

    return "خطة مراجعة أسبوعية";
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

$selectedHalqaId = isset($_GET["halqa_id"]) ? (int) $_GET["halqa_id"] : 0;
$selectedSupervisorId = isset($_GET["supervisor_id"]) ? (int) $_GET["supervisor_id"] : 0;
$selectedExamStatus = isset($_GET["exam_status"]) ? trim($_GET["exam_status"]) : "";
if ($selectedExamStatus !== "pending" && $selectedExamStatus !== "approved" && $selectedExamStatus !== "rejected" && $selectedExamStatus !== "completed") {
    $selectedExamStatus = "";
}

$halqasStmt = $pdo->prepare("
    SELECT halqa_id, name
    FROM halqas
    WHERE college_id = ?
    ORDER BY name ASC
");
$halqasStmt->execute(array($collegeId));
$halqas = $halqasStmt->fetchAll(PDO::FETCH_ASSOC);

$supervisorsStmt = $pdo->prepare("
    SELECT DISTINCT supervisors.supervisor_id, supervisors.name
    FROM supervisors
    JOIN halqas ON supervisors.supervisor_id = halqas.supervisor_id
    WHERE halqas.college_id = ?
    ORDER BY supervisors.name ASC
");
$supervisorsStmt->execute(array($collegeId));
$supervisors = $supervisorsStmt->fetchAll(PDO::FETCH_ASSOC);

$scopeWhere = array("halqas.college_id = ?");
$scopeParams = array($collegeId);
if ($selectedHalqaId > 0) {
    $scopeWhere[] = "halqas.halqa_id = ?";
    $scopeParams[] = $selectedHalqaId;
}
if ($selectedSupervisorId > 0) {
    $scopeWhere[] = "halqas.supervisor_id = ?";
    $scopeParams[] = $selectedSupervisorId;
}
$scopeSql = implode(" AND ", $scopeWhere);

$studentStatsStmt = $pdo->prepare("
    SELECT
        COUNT(DISTINCT students.student_id) AS total_students,
        COUNT(DISTINCT weekly_recitations.student_id) AS heard_students,
        COUNT(DISTINCT CASE
            WHEN weekly_recitations.student_id IS NULL OR COALESCE(weekly_recitations.weekly_pages, 0) < 5
            THEN students.student_id
        END) AS followup_students
    FROM students
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    LEFT JOIN (
        SELECT student_id, SUM(pages_count) AS weekly_pages
        FROM recitations
        WHERE week_id = ?
        GROUP BY student_id
    ) weekly_recitations ON students.student_id = weekly_recitations.student_id
    WHERE " . $scopeSql . "
");
$studentStatsParams = array_merge(array($selectedWeekId), $scopeParams);
$studentStatsStmt->execute($studentStatsParams);
$studentStats = $studentStatsStmt->fetch(PDO::FETCH_ASSOC);

$examSuccessWhere = $scopeWhere;
$examSuccessParams = $scopeParams;
if ($selectedExamStatus !== "") {
    $examSuccessWhere[] = "EXISTS (
        SELECT 1
        FROM exam_requests
        WHERE exam_requests.student_id = students.student_id
          AND exam_requests.status = ?
    )";
    $examSuccessParams[] = $selectedExamStatus;
}
$examSuccessSql = implode(" AND ", $examSuccessWhere);

$examSuccessStmt = $pdo->prepare("
    SELECT
        COUNT(exams.exam_id) AS exams_count,
        COALESCE(SUM(CASE WHEN exams.score >= 50 THEN 1 ELSE 0 END), 0) AS passed_exams
    FROM exams
    JOIN students ON exams.student_id = students.student_id
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    WHERE " . $examSuccessSql . "
");
$examSuccessStmt->execute($examSuccessParams);
$examSuccess = $examSuccessStmt->fetch(PDO::FETCH_ASSOC);

$activeMonthStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT recitations.student_id) AS active_students
    FROM recitations
    JOIN students ON recitations.student_id = students.student_id
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    WHERE " . $scopeSql . " AND recitations.week_id = ?
");
$activeMonthParams = array_merge($scopeParams, array($selectedWeekId));
$activeMonthStmt->execute($activeMonthParams);
$activeMonth = $activeMonthStmt->fetch(PDO::FETCH_ASSOC);

$totalStudents = $studentStats ? numberValue($studentStats["total_students"]) : 0;
$heardStudents = $studentStats ? numberValue($studentStats["heard_students"]) : 0;
$followupStudents = $studentStats ? numberValue($studentStats["followup_students"]) : 0;
$averageRecitationRate = $totalStudents > 0 ? round(($heardStudents / $totalStudents) * 100, 1) : 0;
$examsCount = $examSuccess ? numberValue($examSuccess["exams_count"]) : 0;
$passedExams = $examSuccess ? numberValue($examSuccess["passed_exams"]) : 0;
$examSuccessRate = $examsCount > 0 ? round(($passedExams / $examsCount) * 100, 1) : 0;
$activeStudents = $activeMonth ? numberValue($activeMonth["active_students"]) : 0;

$halqaComparisonStmt = $pdo->prepare("
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
    WHERE " . $scopeSql . "
    GROUP BY halqas.halqa_id, halqas.name, supervisors.name
    ORDER BY halqas.name ASC
");
$halqaComparisonParams = array_merge(array($selectedWeekId), $scopeParams);
$halqaComparisonStmt->execute($halqaComparisonParams);
$halqaComparisonRows = $halqaComparisonStmt->fetchAll(PDO::FETCH_ASSOC);

$weeklyTrendStmt = $pdo->prepare("
    SELECT
        weeks.week_id,
        weeks.week_number,
        COALESCE(week_totals.total_pages, 0) AS total_pages
    FROM weeks
    LEFT JOIN (
        SELECT recitations.week_id, SUM(recitations.pages_count) AS total_pages
        FROM recitations
        JOIN students ON recitations.student_id = students.student_id
        JOIN halqas ON students.halqa_id = halqas.halqa_id
        WHERE " . $scopeSql . "
        GROUP BY recitations.week_id
    ) week_totals ON weeks.week_id = week_totals.week_id
    WHERE weeks.week_id <= ?
    ORDER BY weeks.week_number DESC
    LIMIT 6
");
$weeklyTrendParams = array_merge($scopeParams, array($selectedWeekId));
$weeklyTrendStmt->execute($weeklyTrendParams);
$weeklyTrendRows = array_reverse($weeklyTrendStmt->fetchAll(PDO::FETCH_ASSOC));

$maxTrendPages = 0;
foreach ($weeklyTrendRows as $trendRow) {
    $pages = numberValue($trendRow["total_pages"]);
    if ($pages > $maxTrendPages) {
        $maxTrendPages = $pages;
    }
}

$performanceStmt = $pdo->prepare("
    SELECT
        halqas.halqa_id,
        halqas.name AS halqa_name,
        supervisors.name AS supervisor_name,
        COUNT(DISTINCT students.student_id) AS total_students,
        COUNT(DISTINCT weekly_recitations.student_id) AS heard_students,
        COALESCE(AVG(weekly_recitations.hifz_pages), 0) AS average_hifz,
        COUNT(DISTINCT completed_requests.request_id) AS completed_exams
    FROM halqas
    LEFT JOIN supervisors ON halqas.supervisor_id = supervisors.supervisor_id
    LEFT JOIN students ON halqas.halqa_id = students.halqa_id
    LEFT JOIN (
        SELECT
            student_id,
            SUM(CASE WHEN type = 'حفظ' THEN pages_count ELSE 0 END) AS hifz_pages,
            SUM(pages_count) AS total_pages
        FROM recitations
        WHERE week_id = ?
        GROUP BY student_id
    ) weekly_recitations ON students.student_id = weekly_recitations.student_id
    LEFT JOIN exam_requests completed_requests ON students.student_id = completed_requests.student_id
        AND completed_requests.status = 'completed'
    WHERE " . $scopeSql . "
    GROUP BY halqas.halqa_id, halqas.name, supervisors.name
    ORDER BY halqas.name ASC
");
$performanceParams = array_merge(array($selectedWeekId), $scopeParams);
$performanceStmt->execute($performanceParams);
$performanceRows = $performanceStmt->fetchAll(PDO::FETCH_ASSOC);

$riskStmt = $pdo->prepare("
    SELECT
        students.student_id,
        students.name AS student_name,
        halqas.name AS halqa_name,
        latest_recitations.last_week_number,
        latest_recitations.last_week_id,
        latest_exams.last_score,
        COALESCE(weekly_recitations.weekly_pages, 0) AS weekly_pages
    FROM students
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    LEFT JOIN (
        SELECT
            recitations.student_id,
            MAX(weeks.week_number) AS last_week_number,
            MAX(weeks.week_id) AS last_week_id
        FROM recitations
        JOIN weeks ON recitations.week_id = weeks.week_id
        GROUP BY recitations.student_id
    ) latest_recitations ON students.student_id = latest_recitations.student_id
    LEFT JOIN (
        SELECT exams.student_id, exams.score AS last_score
        FROM exams
        JOIN (
            SELECT student_id, MAX(exam_date) AS last_exam_date
            FROM exams
            GROUP BY student_id
        ) latest_dates ON exams.student_id = latest_dates.student_id AND exams.exam_date = latest_dates.last_exam_date
    ) latest_exams ON students.student_id = latest_exams.student_id
    LEFT JOIN (
        SELECT student_id, SUM(pages_count) AS weekly_pages
        FROM recitations
        WHERE week_id = ?
        GROUP BY student_id
    ) weekly_recitations ON students.student_id = weekly_recitations.student_id
    WHERE " . $scopeSql . "
      AND (
          weekly_recitations.student_id IS NULL
          OR COALESCE(weekly_recitations.weekly_pages, 0) < 5
          OR latest_exams.last_score < 50
          OR latest_recitations.last_week_number IS NULL
          OR (? - latest_recitations.last_week_number) >= 2
      )
    ORDER BY students.name ASC
");
$riskParams = array_merge(array($selectedWeekId), $scopeParams, array(numberValue($selectedWeekNumber)));
$riskStmt->execute($riskParams);
$riskRows = $riskStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقارير - ملتقى القرآن</title>

    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/reports.css">
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
                <a href="exams.php">الامتحانات</a>
                <a href="reports.php" class="active">التقارير</a>
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
                <h1>التقارير</h1>
                <p>تحليل شامل لأداء الطلاب والحلقات والامتحانات</p>
            </div>

            <div class="topbar-left">
                <a class="topbar-home-link" href="../index.php">
                    <span aria-hidden="true">↩</span>
                    <span>العودة للصفحة الرئيسية</span>
                </a>
                <form method="GET" action="reports.php" class="admin-week-filter">
                    <?php if ($selectedHalqaId > 0): ?><input type="hidden" name="halqa_id" value="<?php echo e($selectedHalqaId); ?>"><?php endif; ?>
                    <?php if ($selectedSupervisorId > 0): ?><input type="hidden" name="supervisor_id" value="<?php echo e($selectedSupervisorId); ?>"><?php endif; ?>
                    <?php if ($selectedExamStatus !== ""): ?><input type="hidden" name="exam_status" value="<?php echo e($selectedExamStatus); ?>"><?php endif; ?>
                    <label class="week-filter-label" for="week_id_top">الأسبوع</label>
                    <select class="week-select" id="week_id_top" name="week_id" onchange="this.form.submit()" aria-label="<?php echo e($selectedWeekLabel); ?>">
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
            <section class="card panel filters-panel">
                <div class="panel-header">
                    <h2>فلاتر التقرير</h2>
                    <span><?php echo e($selectedWeekLabel); ?></span>
                </div>

                <form class="filters-grid" method="GET" action="reports.php">
                    <div class="form-field">
                        <label for="report-range">الفترة</label>
                        <select id="report-range" name="week_id" onchange="this.form.submit()">
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
                    </div>

                    <div class="form-field">
                        <label for="report-halaqa">الحلقة</label>
                        <select id="report-halaqa" name="halqa_id" onchange="this.form.submit()">
                            <option value="">كل الحلقات</option>
                            <?php foreach ($halqas as $halqa): ?>
                                <option value="<?php echo e($halqa["halqa_id"]); ?>"<?php echo (int) $halqa["halqa_id"] === (int) $selectedHalqaId ? " selected" : ""; ?>>
                                    <?php echo e(fallbackValue($halqa["name"], "لا يوجد بيانات")); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="report-supervisor">المشرف</label>
                        <select id="report-supervisor" name="supervisor_id" onchange="this.form.submit()">
                            <option value="">كل المشرفين</option>
                            <?php foreach ($supervisors as $supervisor): ?>
                                <option value="<?php echo e($supervisor["supervisor_id"]); ?>"<?php echo (int) $supervisor["supervisor_id"] === (int) $selectedSupervisorId ? " selected" : ""; ?>>
                                    <?php echo e(fallbackValue($supervisor["name"], "لا يوجد بيانات")); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="report-exam-status">حالة الامتحان</label>
                        <select id="report-exam-status" name="exam_status" onchange="this.form.submit()">
                            <option value="">الكل</option>
                            <option value="pending"<?php echo $selectedExamStatus === "pending" ? " selected" : ""; ?>>قيد الانتظار</option>
                            <option value="approved"<?php echo $selectedExamStatus === "approved" ? " selected" : ""; ?>>مقبول</option>
                            <option value="rejected"<?php echo $selectedExamStatus === "rejected" ? " selected" : ""; ?>>مرفوض</option>
                            <option value="completed"<?php echo $selectedExamStatus === "completed" ? " selected" : ""; ?>>مكتمل</option>
                        </select>
                    </div>
                </form>
            </section>

            <section class="stats-grid">
                <div class="stat-card card">
                    <h3><?php echo e(formatPercentValue($averageRecitationRate)); ?></h3>
                    <p>متوسط نسبة التسميع</p>
                </div>
                <div class="stat-card card">
                    <h3><?php echo e(formatNumberValue($activeStudents)); ?></h3>
                    <p>طلاب نشطون هذا الشهر</p>
                </div>
                <div class="stat-card card">
                    <h3><?php echo e(formatPercentValue($examSuccessRate)); ?></h3>
                    <p>نسبة نجاح الامتحانات</p>
                </div>
                <div class="stat-card card">
                    <h3><?php echo e(formatNumberValue($followupStudents)); ?></h3>
                    <p>طلاب بحاجة متابعة</p>
                </div>
            </section>

            <section class="chart-grid">
                <article class="card panel chart-card">
                    <div class="panel-header">
                        <h2>اتجاه التسميع الأسبوعي</h2>
                        <span>آخر 6 أسابيع</span>
                    </div>
                    <div class="chart-placeholder line-chart">
                        <?php if (count($weeklyTrendRows) === 0): ?>
                            <span>لا توجد تسميعات</span>
                        <?php else: ?>
                            <?php foreach ($weeklyTrendRows as $index => $trendRow): ?>
                                <?php
                                $right = count($weeklyTrendRows) > 1 ? 6 + (($index / (count($weeklyTrendRows) - 1)) * 88) : 50;
                                $bottom = $maxTrendPages > 0 ? 20 + ((numberValue($trendRow["total_pages"]) / $maxTrendPages) * 60) : 20;
                                ?>
                                <div class="line-point" title="الأسبوع <?php echo e($trendRow["week_number"]); ?>: <?php echo e(formatNumberValue($trendRow["total_pages"])); ?>" style="right: <?php echo e(formatNumberValue($right)); ?>%; bottom: <?php echo e(formatNumberValue($bottom)); ?>%;"></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="card panel chart-card">
                    <div class="panel-header">
                        <h2>مقارنة الحلقات</h2>
                        <span>نسبة الأداء</span>
                    </div>
                    <div class="bar-list">
                        <?php if (count($halqaComparisonRows) === 0): ?>
                            <div class="bar-row"><span>لا توجد بيانات</span><div><i style="width: 0%;"></i></div><strong>0%</strong></div>
                        <?php else: ?>
                            <?php foreach ($halqaComparisonRows as $halqa): ?>
                                <?php
                                $halqaTotal = numberValue($halqa["total_students"]);
                                $halqaHeard = numberValue($halqa["heard_students"]);
                                $halqaRate = $halqaTotal > 0 ? round(($halqaHeard / $halqaTotal) * 100, 1) : 0;
                                ?>
                                <div class="bar-row"><span><?php echo e(fallbackValue($halqa["halqa_name"], "لا توجد بيانات")); ?></span><div><i style="width: <?php echo e(formatNumberValue($halqaRate)); ?>%;"></i></div><strong><?php echo e(formatPercentValue($halqaRate)); ?></strong></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </article>
            </section>

            <section class="card panel">
                <div class="panel-header">
                    <h2>أداء الحلقات</h2>
                    <span>تحليل تفصيلي</span>
                </div>

                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>الحلقة</th>
                                <th>المشرف</th>
                                <th>نسبة التسميع</th>
                                <th>متوسط الحفظ</th>
                                <th>الامتحانات المكتملة</th>
                                <th>التقييم العام</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($performanceRows) === 0): ?>
                                <tr>
                                    <td colspan="6">لا توجد بيانات</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($performanceRows as $row): ?>
                                    <?php
                                    $rowTotal = numberValue($row["total_students"]);
                                    $rowHeard = numberValue($row["heard_students"]);
                                    $rowRate = $rowTotal > 0 ? round(($rowHeard / $rowTotal) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo e(fallbackValue($row["halqa_name"], "لا توجد بيانات")); ?></td>
                                        <td><?php echo e(fallbackValue($row["supervisor_name"], "لا توجد بيانات")); ?></td>
                                        <td><?php echo e(formatPercentValue($rowRate)); ?></td>
                                        <td><?php echo e(formatNumberValue($row["average_hifz"])); ?> صفحة</td>
                                        <td><?php echo e(formatNumberValue($row["completed_exams"])); ?></td>
                                        <td><?php echo e(performanceLabel($rowRate)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card panel">
                <div class="panel-header">
                    <h2>الطلاب المعرّضون للتراجع</h2>
                    <span>متابعة مبكرة</span>
                </div>

                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>الطالب</th>
                                <th>الحلقة</th>
                                <th>آخر تسميع</th>
                                <th>عدد أيام الانقطاع</th>
                                <th>آخر درجة امتحان</th>
                                <th>إجراء مقترح</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($riskRows) === 0): ?>
                                <tr>
                                    <td colspan="6">لا توجد بيانات</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($riskRows as $student): ?>
                                    <?php
                                    $lastWeekNumber = $student["last_week_number"] !== null ? numberValue($student["last_week_number"]) : null;
                                    $daysAbsent = $lastWeekNumber !== null ? max(0, (numberValue($selectedWeekNumber) - $lastWeekNumber) * 7) : 0;
                                    $lastRecitation = $lastWeekNumber !== null ? "الأسبوع " . $lastWeekNumber : "لا توجد تسميعات";
                                    ?>
                                    <tr>
                                        <td><?php echo e(fallbackValue($student["student_name"], "لا توجد بيانات")); ?></td>
                                        <td><?php echo e(fallbackValue($student["halqa_name"], "لا توجد بيانات")); ?></td>
                                        <td><?php echo e($lastRecitation); ?></td>
                                        <td><?php echo e(formatNumberValue($daysAbsent)); ?></td>
                                        <td><?php echo e($student["last_score"] !== null ? formatNumberValue($student["last_score"]) : "لا توجد نتائج امتحانات"); ?></td>
                                        <td><?php echo e(suggestedAction($daysAbsent, $student["last_score"])); ?></td>
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
