<?php
session_start();
require_once __DIR__ . "/../php/db_connect.php";
/** @var PDO $pdo */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

$sessionRole = isset($_SESSION["role"]) ? $_SESSION["role"] : (isset($_SESSION["role_name"]) ? $_SESSION["role_name"] : "");
if ($sessionRole !== "exam_admin") {
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

function examTypeLabel($type)
{
    if ($type === "first_time") {
        return "أول مرة";
    }

    if ($type === "retake") {
        return "إعادة";
    }

    if ($type === "revision") {
        return "تثبيت";
    }

    return "لا توجد بيانات";
}

$emptyText = "لا توجد بيانات";
$unassignedText = "غير محدد";
$userId = $_SESSION["user_id"];

$examAdminStmt = $pdo->prepare("
    SELECT
        exam_admins.exam_admin_id,
        exam_admins.full_name,
        exam_admins.college_id,
        colleges.college_name
    FROM exam_admins
    JOIN colleges ON exam_admins.college_id = colleges.college_id
    WHERE exam_admins.user_id = ?
    LIMIT 1
");
$examAdminStmt->execute(array($userId));
$examAdmin = $examAdminStmt->fetch(PDO::FETCH_ASSOC);

if (!$examAdmin) {
    header("Location: ../login.php");
    exit;
}

$collegeId = $examAdmin["college_id"];
$examAdminName = fallbackValue($examAdmin["full_name"], isset($_SESSION["username"]) ? $_SESSION["username"] : $emptyText);
$collegeName = fallbackValue($examAdmin["college_name"], $emptyText);

$weeksStmt = $pdo->prepare("
    SELECT week_id, week_number
    FROM weeks
    ORDER BY week_number ASC, week_id ASC
");
$weeksStmt->execute();
$weeks = $weeksStmt->fetchAll(PDO::FETCH_ASSOC);

$latestWeekStmt = $pdo->prepare("
    SELECT week_id
    FROM weeks
    ORDER BY week_number DESC, week_id DESC
    LIMIT 1
");
$latestWeekStmt->execute();
$latestWeekRow = $latestWeekStmt->fetch(PDO::FETCH_ASSOC);
$latestWeekId = $latestWeekRow && $latestWeekRow["week_id"] ? numberValue($latestWeekRow["week_id"]) : 0;

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

$requestStatsStmt = $pdo->prepare("
    SELECT COUNT(exam_requests.request_id) AS uncompleted_requests
    FROM exam_requests
    JOIN students ON exam_requests.student_id = students.student_id
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    LEFT JOIN exams ON exams.request_id = exam_requests.request_id
    WHERE halqas.college_id = ? AND exams.exam_id IS NULL
");
$requestStatsStmt->execute(array($collegeId));
$requestStats = $requestStatsStmt->fetch(PDO::FETCH_ASSOC);

$examStatsStmt = $pdo->prepare("
    SELECT
        COALESCE(AVG(exams.score), 0) AS average_score,
        COUNT(exams.exam_id) AS total_completed_exams,
        COALESCE(SUM(CASE WHEN exams.score >= 90 THEN 1 ELSE 0 END), 0) AS passed_exams,
        COALESCE(MAX(exams.score), 0) AS highest_score,
        COALESCE(MIN(exams.score), 0) AS lowest_score,
        COUNT(DISTINCT exams.student_id) AS tested_students
    FROM exams
    JOIN students ON exams.student_id = students.student_id
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    WHERE halqas.college_id = ?
");
$examStatsStmt->execute(array($collegeId));
$examStats = $examStatsStmt->fetch(PDO::FETCH_ASSOC);

$totalCompletedExams = $examStats ? numberValue($examStats["total_completed_exams"]) : 0;
$passedExams = $examStats ? numberValue($examStats["passed_exams"]) : 0;
$successRate = $totalCompletedExams > 0 ? round(($passedExams / $totalCompletedExams) * 100, 1) : 0;
$highestScore = $examStats ? $examStats["highest_score"] : 0;
$lowestScore = $examStats ? $examStats["lowest_score"] : 0;
$testedStudents = $examStats ? numberValue($examStats["tested_students"]) : 0;

$examRequestsStmt = $pdo->prepare("
    SELECT
        exam_requests.request_id,
        exam_requests.requested_part,
        exam_requests.exam_type,
        exam_requests.request_date,
        exam_requests.week_id,
        students.student_id,
        students.name AS student_name,
        halqas.name AS halqa_name,
        weeks.week_number
    FROM exam_requests
    JOIN students ON exam_requests.student_id = students.student_id
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    LEFT JOIN weeks ON exam_requests.week_id = weeks.week_id
    LEFT JOIN exams ON exams.request_id = exam_requests.request_id
    WHERE halqas.college_id = ? AND exams.exam_id IS NULL
    ORDER BY exam_requests.request_date DESC, exam_requests.request_id DESC
");
$examRequestsStmt->execute(array($collegeId));
$examRequests = $examRequestsStmt->fetchAll(PDO::FETCH_ASSOC);

$completedExamsStmt = $pdo->prepare("
    SELECT
        exams.exam_id,
        exams.part_name,
        exams.score,
        exams.grade,
        exams.exam_date,
        exams.notes,
        exams.examiner_name,
        students.name AS student_name,
        halqas.name AS halqa_name,
        weeks.week_number
    FROM exams
    JOIN students ON exams.student_id = students.student_id
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    LEFT JOIN weeks ON exams.week_id = weeks.week_id
    WHERE halqas.college_id = ?
    ORDER BY exams.exam_date DESC, exams.exam_id DESC
");
$completedExamsStmt->execute(array($collegeId));
$completedExams = $completedExamsStmt->fetchAll(PDO::FETCH_ASSOC);

$weeklyReportStmt = $pdo->prepare("
    SELECT
        halqas.name AS halqa_name,
        COUNT(exams.exam_id) AS exams_count,
        COALESCE(AVG(exams.score), 0) AS average_score,
        COALESCE(SUM(CASE WHEN exams.score >= 90 THEN 1 ELSE 0 END), 0) AS passed_count
    FROM exams
    JOIN students ON exams.student_id = students.student_id
    JOIN halqas ON students.halqa_id = halqas.halqa_id
    WHERE halqas.college_id = ? AND exams.week_id = ?
    GROUP BY halqas.halqa_id, halqas.name
    ORDER BY halqas.name ASC
");
$weeklyReportStmt->execute(array($collegeId, $selectedWeekId));
$weeklyReport = $weeklyReportStmt->fetchAll(PDO::FETCH_ASSOC);

$noticeText = "";
$noticeClass = "";
if (isset($_GET["success"]) && $_GET["success"] === "exam_completed") {
    $noticeText = "تم حفظ نتيجة الامتحان بنجاح";
    $noticeClass = "notice-success";
} elseif (isset($_GET["error"]) && $_GET["error"] === "exam_complete_failed") {
    $noticeText = "تعذر حفظ نتيجة الامتحان";
    $noticeClass = "notice-error";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة مسؤول الامتحانات - ملتقى القرآن</title>

    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=exam-admin-20260603">
    <link rel="stylesheet" href="../assets/css/exams.css?v=exam-admin-20260603">
    <link rel="stylesheet" href="../assets/css/exam-admin-dashboard.css?v=20260603">
</head>
<body>

<div class="app-layout">

    <aside class="sidebar">
        <div class="sidebar-top">
            <div class="sidebar-brand">
                <h2>ملتقى القرآن</h2>
            </div>

            <nav class="sidebar-menu">
                <a href="exam-admin-dashboard.php" class="active">الرئيسية</a>
            </nav>
        </div>

        <div class="sidebar-bottom">
            <div class="sidebar-user">
                <h4><?php echo e($examAdminName); ?></h4>
                <p>مسؤول الامتحانات</p>
                <p><?php echo e($collegeName); ?></p>
            </div>
        </div>
    </aside>

    <main class="main-area">
        <header class="topbar">
            <div class="topbar-right">
                <h1>لوحة مسؤول الامتحانات</h1>
                <p>متابعة طلبات الامتحانات وتوثيق النتائج</p>
            </div>

            <div class="topbar-left">
                <a class="topbar-home-link" href="../index.php">
                    <span aria-hidden="true">↩</span>
                    <span>العودة للصفحة الرئيسية</span>
                </a>
                <form method="GET" action="exam-admin-dashboard.php" class="admin-week-filter">
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

        <section class="page-content exam-admin-page">

            <?php if ($noticeText !== ""): ?>
                <div class="exam-admin-notice <?php echo e($noticeClass); ?>">
                    <?php echo e($noticeText); ?>
                </div>
            <?php endif; ?>

            <section class="stats-grid exam-stats-grid">
                <div class="stat-card card">
                    <div class="stat-info">
                        <h3><?php echo e(formatNumberValue($requestStats["uncompleted_requests"])); ?></h3>
                        <p>طلبات بدون نتيجة</p>
                    </div>
                </div>

                <div class="stat-card card">
                    <div class="stat-info">
                        <h3><?php echo e(formatNumberValue($totalCompletedExams)); ?></h3>
                        <p>امتحانات مكتملة</p>
                    </div>
                </div>

                <div class="stat-card card">
                    <div class="stat-info">
                        <h3><?php echo e(formatNumberValue($examStats["average_score"])); ?></h3>
                        <p>متوسط العلامات</p>
                    </div>
                </div>

                <div class="stat-card card">
                    <div class="stat-info">
                        <h3><?php echo e(formatPercentValue($successRate)); ?></h3>
                        <p>نسبة النجاح</p>
                    </div>
                </div>

                <div class="stat-card card">
                    <div class="stat-info">
                        <h3><?php echo e(formatNumberValue($highestScore)); ?></h3>
                        <p>أعلى علامة</p>
                    </div>
                </div>

                <div class="stat-card card">
                    <div class="stat-info">
                        <h3><?php echo e(formatNumberValue($lowestScore)); ?></h3>
                        <p>أقل علامة</p>
                    </div>
                </div>

                <div class="stat-card card">
                    <div class="stat-info">
                        <h3><?php echo e(formatNumberValue($testedStudents)); ?></h3>
                        <p>عدد الطلاب المختبرين</p>
                    </div>
                </div>
            </section>

            <section class="card panel dashboard-table-panel">
                <div class="panel-header">
                    <h2>طلبات الامتحانات</h2>
                    <span><?php echo e(formatNumberValue(count($examRequests))); ?> طلب بدون نتيجة</span>
                </div>

                <div class="table-wrap table-scroll-wrapper exam-admin-table-wrap">
                    <table class="data-table exam-admin-table exam-requests-table">
                        <thead>
                            <tr>
                                <th>اسم الطالب</th>
                                <th>رقم الطالب</th>
                                <th>الحلقة</th>
                                <th>الجزء المطلوب</th>
                                <th>نوع الامتحان</th>
                                <th>الأسبوع</th>
                                <th>تاريخ الطلب</th>
                                <th>إدخال النتيجة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($examRequests) === 0): ?>
                                <tr>
                                    <td colspan="8" class="empty-cell"><?php echo e($emptyText); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($examRequests as $request): ?>
                                    <tr>
                                        <td><?php echo e(fallbackValue($request["student_name"], $emptyText)); ?></td>
                                        <td><?php echo e($request["student_id"]); ?></td>
                                        <td><?php echo e(fallbackValue($request["halqa_name"], $emptyText)); ?></td>
                                        <td><?php echo e(fallbackValue($request["requested_part"], $emptyText)); ?></td>
                                        <td><?php echo e(examTypeLabel($request["exam_type"])); ?></td>
                                        <td><?php echo $request["week_number"] !== null && $request["week_number"] !== "" ? e("الأسبوع " . $request["week_number"]) : e($emptyText); ?></td>
                                        <td><?php echo e(fallbackValue($request["request_date"], $emptyText)); ?></td>
                                        <td class="exam-actions-cell">
                                            <form method="POST" action="../php/complete_exam_request.php" class="exam-action-form result-entry-form">
                                                <input type="hidden" name="request_id" value="<?php echo e($request["request_id"]); ?>">
                                                <input type="text" name="examiner_name" class="exam-action-input examiner-name-input" placeholder="اسم الممتحن" required>
                                                <input type="number" name="score" class="exam-action-input score-input" min="0" max="100" step="0.01" placeholder="العلامة" required>
                                                <button type="submit" class="exam-action-button complete-button">حفظ النتيجة</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card panel dashboard-table-panel">
                <div class="panel-header">
                    <h2>الامتحانات المكتملة</h2>
                    <span><?php echo e(formatNumberValue(count($completedExams))); ?> امتحان</span>
                </div>

                <div class="table-wrap table-scroll-wrapper exam-admin-table-wrap">
                    <table class="data-table exam-admin-table completed-exams-table">
                        <thead>
                            <tr>
                                <th>اسم الطالب</th>
                                <th>الحلقة</th>
                                <th>الجزء</th>
                                <th>العلامة</th>
                                <th>التقدير</th>
                                <th>تاريخ الامتحان</th>
                                <th>الأسبوع</th>
                                <th>اسم الممتحن</th>
                                <th>الملاحظات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($completedExams) === 0): ?>
                                <tr>
                                    <td colspan="9" class="empty-cell"><?php echo e($emptyText); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($completedExams as $exam): ?>
                                    <tr>
                                        <td><?php echo e(fallbackValue($exam["student_name"], $emptyText)); ?></td>
                                        <td><?php echo e(fallbackValue($exam["halqa_name"], $emptyText)); ?></td>
                                        <td><?php echo e(fallbackValue($exam["part_name"], $emptyText)); ?></td>
                                        <td><?php echo e(formatNumberValue($exam["score"])); ?></td>
                                        <td><?php echo e(fallbackValue($exam["grade"], $emptyText)); ?></td>
                                        <td><?php echo e(fallbackValue($exam["exam_date"], $emptyText)); ?></td>
                                        <td><?php echo $exam["week_number"] !== null && $exam["week_number"] !== "" ? e("الأسبوع " . $exam["week_number"]) : e($emptyText); ?></td>
                                        <td><?php echo e(fallbackValue($exam["examiner_name"], $unassignedText)); ?></td>
                                        <td><?php echo e(fallbackValue($exam["notes"], $emptyText)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="exam-admin-stacked-sections">
                <div class="dashboard-panel card exam-admin-full-section">
                    <div class="panel-header">
                        <h2>تقرير الامتحانات الأسبوعي</h2>
                        <span><?php echo e($selectedWeekLabel); ?></span>
                    </div>

                    <div class="table-wrap table-scroll-wrapper compact-table-wrap">
                        <table class="data-table compact-data-table">
                            <thead>
                                <tr>
                                    <th>اسم الحلقة</th>
                                    <th>عدد الامتحانات</th>
                                    <th>متوسط العلامات</th>
                                    <th>عدد الناجحين</th>
                                    <th>نسبة النجاح</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($weeklyReport) === 0): ?>
                                    <tr>
                                        <td colspan="5" class="empty-cell"><?php echo e($emptyText); ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($weeklyReport as $reportRow): ?>
                                        <?php
                                        $weeklyExamsCount = numberValue($reportRow["exams_count"]);
                                        $weeklyPassedCount = numberValue($reportRow["passed_count"]);
                                        $weeklySuccessRate = $weeklyExamsCount > 0 ? round(($weeklyPassedCount / $weeklyExamsCount) * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td><?php echo e(fallbackValue($reportRow["halqa_name"], $emptyText)); ?></td>
                                            <td><?php echo e(formatNumberValue($weeklyExamsCount)); ?></td>
                                            <td><?php echo e(formatNumberValue($reportRow["average_score"])); ?></td>
                                            <td><?php echo e(formatNumberValue($weeklyPassedCount)); ?></td>
                                            <td><?php echo e(formatPercentValue($weeklySuccessRate)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        </section>
    </main>

</div>

</body>
</html>
