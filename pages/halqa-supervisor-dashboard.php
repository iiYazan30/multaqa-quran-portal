<?php
session_start();
require_once __DIR__ . "/../php/db_connect.php";
/** @var PDO $pdo */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

$sessionRole = isset($_SESSION["role"]) ? $_SESSION["role"] : (isset($_SESSION["role_name"]) ? $_SESSION["role_name"] : "");
if ($sessionRole !== "supervisor") {
    header("Location: ../login.php");
    exit;
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function valueOrFallback($value, $fallback)
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

function makeLevel($hifzPages)
{
    if ($hifzPages >= 40) {
        return "متقدم";
    }

    if ($hifzPages >= 15) {
        return "متوسط";
    }

    return "مبتدئ";
}

function makeAttendance($activeWeeks, $totalWeeks)
{
    if ($totalWeeks <= 0) {
        return "لم يبدأ بعد";
    }

    $rate = ($activeWeeks / $totalWeeks) * 100;
    if ($rate >= 75) {
        return "منتظم";
    }

    if ($rate >= 45) {
        return "متذبذب";
    }

    return "متكرر الغياب";
}

function makeEngagement($latestTotal, $latestHifz)
{
    if ($latestTotal >= 5 && $latestHifz > 0) {
        return "عال";
    }

    if ($latestTotal > 0) {
        return "متوسط";
    }

    return "ضعيف";
}

function makeActionSuggestion($reasons)
{
    if (!count($reasons)) {
        return "استمرار بنفس النسق";
    }

    if (in_array("لم يسمع هذا الأسبوع", $reasons)) {
        return "التواصل مع الطالب وتحديد موعد تسميع قريب";
    }

    if (in_array("عدد صفحات قليل", $reasons)) {
        return "رفع الهدف الأسبوعي تدريجيا";
    }

    if (in_array("يحتاج مراجعة", $reasons)) {
        return "تركيز خطة الأسبوع القادم على تثبيت المراجعة";
    }

    return "جلسة متابعة فردية وخطة استدراك";
}

function makeStatus($reasons, $latestTotal, $averageExamScore)
{
    if (!count($reasons)) {
        return "جيد";
    }

    if ($latestTotal === 0) {
        return "تنبيه";
    }

    return "متابعة";
}

function normalizeBatchType($type)
{
    return $type === "حفظ" ? "hifz" : "review";
}

function batchTypeLabel($type)
{
    return $type === "hifz" ? "حفظ" : "مراجعة";
}

function statusClass($status)
{
    if ($status === "جيد") {
        return "status-good";
    }

    if ($status === "متابعة") {
        return "status-mid";
    }

    return "status-alert";
}

$userId = $_SESSION["user_id"];

$supervisorStmt = $pdo->prepare("
    SELECT
        supervisors.supervisor_id,
        supervisors.name AS supervisor_name,
        halqas.halqa_id,
        halqas.name AS halqa_name
    FROM supervisors
    LEFT JOIN halqas ON supervisors.supervisor_id = halqas.supervisor_id
    WHERE supervisors.user_id = ?
    LIMIT 1
");
$supervisorStmt->execute(array($userId));
$supervisor = $supervisorStmt->fetch(PDO::FETCH_ASSOC);

if (!$supervisor || !$supervisor["halqa_id"]) {
    header("Location: ../login.php");
    exit;
}

$supervisorName = valueOrFallback($supervisor["supervisor_name"], isset($_SESSION["username"]) ? $_SESSION["username"] : "لا يوجد");
$halqaId = $supervisor["halqa_id"];
$halqaName = valueOrFallback($supervisor["halqa_name"], "لا يوجد");

$latestWeekStmt = $pdo->prepare("
    SELECT weeks.week_number AS latest_week_number, weeks.week_id AS latest_week_id
    FROM weeks
    ORDER BY weeks.week_number DESC
    LIMIT 1
");
$latestWeekStmt->execute();
$latestWeek = $latestWeekStmt->fetch(PDO::FETCH_ASSOC);
$latestWeekId = $latestWeek && $latestWeek["latest_week_id"] ? $latestWeek["latest_week_id"] : 0;
$latestWeekNumber = $latestWeek && $latestWeek["latest_week_number"] ? $latestWeek["latest_week_number"] : 0;

$weeksStmt = $pdo->prepare("
    SELECT week_id, week_number
    FROM weeks
    ORDER BY week_number ASC
");
$weeksStmt->execute();
$weeks = $weeksStmt->fetchAll(PDO::FETCH_ASSOC);

$studentsStmt = $pdo->prepare("
    SELECT student_id, name, student_type
    FROM students
    WHERE halqa_id = ?
    ORDER BY student_id ASC
");
$studentsStmt->execute(array($halqaId));
$studentRows = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

$totalStudents = count($studentRows);

$studentStatsStmt = $pdo->prepare("
    SELECT
        students.student_id,
        COALESCE(SUM(CASE WHEN recitations.type + 0 = 1 THEN recitations.pages_count ELSE 0 END), 0) AS cumulative_hifz,
        COALESCE(SUM(CASE WHEN recitations.type + 0 = 2 THEN recitations.pages_count ELSE 0 END), 0) AS cumulative_review,
        COUNT(recitations.recitation_id) AS cumulative_sessions,
        COUNT(DISTINCT recitations.week_id) AS active_weeks,
        MAX(weeks.week_number) AS last_week_number
    FROM students
    LEFT JOIN recitations ON students.student_id = recitations.student_id
    LEFT JOIN weeks ON recitations.week_id = weeks.week_id
    WHERE students.halqa_id = ?
    GROUP BY students.student_id
");
$studentStatsStmt->execute(array($halqaId));
$studentStatsRows = $studentStatsStmt->fetchAll(PDO::FETCH_ASSOC);
$studentStatsById = array();
foreach ($studentStatsRows as $statsRow) {
    $studentStatsById[$statsRow["student_id"]] = $statsRow;
}

$latestRecitationsStmt = $pdo->prepare("
    SELECT recitations.student_id, recitations.type, recitations.from_page, recitations.to_page, recitations.pages_count, recitations.notes
    FROM recitations
    JOIN students ON recitations.student_id = students.student_id
    WHERE students.halqa_id = ? AND recitations.week_id = ?
    ORDER BY recitations.recitation_id ASC
");
$latestRecitationsStmt->execute(array($halqaId, $latestWeekId));
$latestRecitationRows = $latestRecitationsStmt->fetchAll(PDO::FETCH_ASSOC);
$latestBatchesByStudent = array();
foreach ($latestRecitationRows as $recitationRow) {
    $studentIdForBatch = $recitationRow["student_id"];
    if (!isset($latestBatchesByStudent[$studentIdForBatch])) {
        $latestBatchesByStudent[$studentIdForBatch] = array();
    }

    $latestBatchesByStudent[$studentIdForBatch][] = array(
        "type" => normalizeBatchType($recitationRow["type"]),
        "from" => $recitationRow["from_page"],
        "to" => $recitationRow["to_page"],
        "pages" => numberValue($recitationRow["pages_count"]),
        "notes" => valueOrFallback($recitationRow["notes"], "")
    );
}

$examAveragesStmt = $pdo->prepare("
    SELECT exams.student_id, AVG(exams.score) AS average_score
    FROM exams
    JOIN students ON exams.student_id = students.student_id
    WHERE students.halqa_id = ?
    GROUP BY exams.student_id
");
$examAveragesStmt->execute(array($halqaId));
$examAverageRows = $examAveragesStmt->fetchAll(PDO::FETCH_ASSOC);
$examAverageByStudent = array();
foreach ($examAverageRows as $examAverageRow) {
    $examAverageByStudent[$examAverageRow["student_id"]] = $examAverageRow["average_score"];
}

$weeklyPerformanceStmt = $pdo->prepare("
    SELECT
        weeks.week_number,
        COUNT(DISTINCT recitations.student_id) AS heard_students,
        COALESCE(SUM(recitations.pages_count), 0) AS total_pages
    FROM weeks
    LEFT JOIN recitations ON weeks.week_id = recitations.week_id
        AND recitations.student_id IN (
            SELECT student_id FROM students WHERE halqa_id = ?
        )
    GROUP BY weeks.week_id, weeks.week_number
    ORDER BY weeks.week_number ASC
");
$weeklyPerformanceStmt->execute(array($halqaId));
$weeklyPerformanceRows = $weeklyPerformanceStmt->fetchAll(PDO::FETCH_ASSOC);

$weeklyPerformance = array();
foreach ($weeklyPerformanceRows as $weekRow) {
    $heardStudents = numberValue($weekRow["heard_students"]);
    $rate = $totalStudents > 0 ? round(($heardStudents / $totalStudents) * 100) : 0;
    $weeklyPerformance[] = array(
        "week" => "الأسبوع " . $weekRow["week_number"],
        "rate" => $rate,
        "heard" => $heardStudents,
        "pages" => numberValue($weekRow["total_pages"])
    );
}

$students = array();
$studentsWithLatestRecitation = 0;
$followupCount = 0;

foreach ($studentRows as $studentRow) {
    $studentId = $studentRow["student_id"];
    $stats = isset($studentStatsById[$studentId]) ? $studentStatsById[$studentId] : array();
    $batches = isset($latestBatchesByStudent[$studentId]) ? $latestBatchesByStudent[$studentId] : array();

    $latestHifz = 0;
    $latestReview = 0;
    foreach ($batches as $batch) {
        if ($batch["type"] === "hifz") {
            $latestHifz += numberValue($batch["pages"]);
        } else {
            $latestReview += numberValue($batch["pages"]);
        }
    }

    $latestTotal = $latestHifz + $latestReview;
    if ($latestTotal > 0) {
        $studentsWithLatestRecitation++;
    }

    $cumulativeHifz = isset($stats["cumulative_hifz"]) ? numberValue($stats["cumulative_hifz"]) : 0;
    $cumulativeReview = isset($stats["cumulative_review"]) ? numberValue($stats["cumulative_review"]) : 0;
    $cumulativeSessions = isset($stats["cumulative_sessions"]) ? numberValue($stats["cumulative_sessions"]) : 0;
    $activeWeeks = isset($stats["active_weeks"]) ? numberValue($stats["active_weeks"]) : 0;
    $lastWeekNumber = isset($stats["last_week_number"]) ? $stats["last_week_number"] : null;
    $averageExamScore = isset($examAverageByStudent[$studentId]) ? $examAverageByStudent[$studentId] : null;

    $followUpReasons = array();
    if ($latestTotal === 0) {
        $followUpReasons[] = "لم يسمع هذا الأسبوع";
    } elseif ($latestTotal < 5) {
        $followUpReasons[] = "عدد صفحات قليل";
    } elseif ($latestReview > 0 && $latestHifz === 0) {
        $followUpReasons[] = "يحتاج مراجعة";
    }

    if ($averageExamScore !== null && (float) $averageExamScore < 80) {
        $followUpReasons[] = "تراجع في الحفظ";
    }

    if (count($followUpReasons)) {
        $followupCount++;
    }

    $status = makeStatus($followUpReasons, $latestTotal, $averageExamScore);

    $students[] = array(
        "id" => numberValue($studentId),
        "name" => valueOrFallback($studentRow["name"], "لا يوجد"),
        "studentType" => valueOrFallback($studentRow["student_type"], "لا يوجد"),
        "level" => makeLevel($cumulativeHifz),
        "attendance" => makeAttendance($activeWeeks, count($weeklyPerformanceRows)),
        "engagement" => makeEngagement($latestTotal, $latestHifz),
        "status" => $status,
        "currentWeekRecitation" => $latestTotal > 0 ? ($latestWeekNumber ? "الأسبوع " . $latestWeekNumber : "لا يوجد") : "لا يوجد",
        "lastRecitationDate" => $lastWeekNumber ? "الأسبوع " . $lastWeekNumber : "لم يسمع بعد",
        "lastActiveWeek" => $lastWeekNumber ? "الأسبوع " . $lastWeekNumber : "لا يوجد",
        "cumulativeHifz" => $cumulativeHifz,
        "cumulativeReview" => $cumulativeReview,
        "cumulativeSessions" => $cumulativeSessions,
        "activeWeeks" => $activeWeeks,
        "followUpReasons" => $followUpReasons,
        "actionSuggestion" => makeActionSuggestion($followUpReasons),
        "batches" => $batches
    );
}

$recitationRate = $totalStudents > 0 ? round(($studentsWithLatestRecitation / $totalStudents) * 100) : 0;

$dashboardState = array(
    "halqaName" => $halqaName,
    "supervisorName" => $supervisorName,
    "latestWeekLabel" => $latestWeekNumber ? "الأسبوع " . $latestWeekNumber . " - الفصل الحالي" : "لا يوجد أسبوع مسجل",
    "summary" => array(
        "totalStudents" => $totalStudents,
        "studentsWithRecitation" => $studentsWithLatestRecitation,
        "followupCount" => $followupCount,
        "recitationRate" => $recitationRate
    ),
    "weeklyPerformance" => $weeklyPerformance,
    "students" => $students
);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة مشرف الحلقة - ملتقى القرآن</title>

    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/halqa-supervisor-dashboard.css">
</head>
<body>

<div class="app-layout">

    <aside class="sidebar">
        <div class="sidebar-top">
            <div class="sidebar-brand">
                <h2>ملتقى القرآن</h2>
                <p>لوحة مشرف الحلقة</p>
            </div>

            <nav class="sidebar-menu supervisor-menu" aria-label="القائمة الرئيسية">
                <a href="#overview" class="sidebar-link active" data-section-link>الرئيسية</a>
                <a href="#students" class="sidebar-link" data-section-link>طلاب الحلقة</a>
                <a href="#weekly-report" class="sidebar-link is-important" data-section-link>التقرير الأسبوعي</a>
                <a href="#stats" class="sidebar-link" data-section-link>إحصائيات الحلقة</a>
                <a href="#follow-up" class="sidebar-link" data-section-link>يحتاج إلى متابعة</a>
                <a href="#settings" class="sidebar-link" data-section-link>الإعدادات</a>
            </nav>
        </div>

        <div class="sidebar-bottom">
            <div class="sidebar-user">
                <h4><?php echo e($supervisorName); ?></h4>
                <p>مشرف <?php echo e($halqaName); ?></p>
            </div>
        </div>
    </aside>

    <main class="main-area">
        <header class="topbar">
            <div class="topbar-right">
                <h1>لوحة متابعة <?php echo e($halqaName); ?></h1>
                <p>متابعة أسبوعية مركزة لطلاب الحلقة والتسميع</p>
            </div>

            <div class="topbar-left">
                <div class="topbar-context">
                    <span>الحلقة: <strong><?php echo e($halqaName); ?></strong></span>
                    <span>المشرف: <strong><?php echo e($supervisorName); ?></strong></span>
                </div>
                <div class="topbar-badge"><?php echo e($dashboardState["latestWeekLabel"]); ?></div>
                <a class="topbar-home-link" href="../index.php">
                    <span aria-hidden="true">↩</span>
                    <span>العودة للصفحة الرئيسية</span>
                </a>
            </div>
        </header>

        <section class="page-content">
            <section id="overview" class="dashboard-section card section-visible" data-section>
                <div class="panel-header">
                    <h2>ملخص الحلقة</h2>
                    <span>نظرة عملية سريعة</span>
                </div>

                <div id="summary-strip" class="summary-strip" aria-live="polite">
                    <article class="summary-item">
                        <h3><?php echo e($halqaName); ?></h3>
                        <p>اسم الحلقة</p>
                    </article>
                    <article class="summary-item">
                        <h3><?php echo e($totalStudents); ?></h3>
                        <p>عدد الطلاب</p>
                    </article>
                    <article class="summary-item">
                        <h3><?php echo e($recitationRate); ?>%</h3>
                        <p>نسبة التسميع هذا الأسبوع</p>
                    </article>
                    <article class="summary-item">
                        <h3><?php echo e($studentsWithLatestRecitation); ?></h3>
                        <p>عدد الطلاب الذين سمعوا</p>
                    </article>
                    <article class="summary-item">
                        <h3><?php echo e($followupCount); ?></h3>
                        <p>طلاب يحتاجون متابعة</p>
                    </article>
                </div>

                <div class="panel-subheader">
                    <h3>نظرة سريعة على الطلاب هذا الأسبوع</h3>
                </div>

                <div class="table-wrap">
                    <table class="data-table quick-overview-table">
                        <thead>
                        <tr>
                            <th>اسم الطالب</th>
                            <th>آخر تسميع</th>
                            <th>النوع</th>
                            <th>من</th>
                            <th>إلى</th>
                            <th>مجموع صفحات هذا الأسبوع</th>
                            <th>الحالة</th>
                        </tr>
                        </thead>
                        <tbody id="quick-overview-body">
                        <?php foreach ($students as $student): ?>
                            <?php
                            $studentBatches = $student["batches"];
                            $latestBatch = count($studentBatches) ? $studentBatches[count($studentBatches) - 1] : null;
                            $weeklyTotal = 0;
                            foreach ($studentBatches as $studentBatch) {
                                $weeklyTotal += numberValue($studentBatch["pages"]);
                            }
                            ?>
                            <tr>
                                <td><?php echo e($student["name"]); ?></td>
                                <td><?php echo e($latestBatch ? ($latestWeekNumber ? "الأسبوع " . $latestWeekNumber : "لا يوجد") : "لا يوجد"); ?></td>
                                <td><?php echo e($latestBatch ? batchTypeLabel($latestBatch["type"]) : "لا يوجد"); ?></td>
                                <td><?php echo e($latestBatch ? $latestBatch["from"] : "لا يوجد"); ?></td>
                                <td><?php echo e($latestBatch ? $latestBatch["to"] : "لا يوجد"); ?></td>
                                <td><?php echo e($weeklyTotal); ?></td>
                                <td><span class="status-badge <?php echo e(statusClass($student["status"])); ?>"><?php echo e($student["status"]); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="panel-subheader">
                    <h3>طلاب يحتاجون متابعة سريعة</h3>
                </div>

                <div id="followup-preview-list" class="followup-preview-list">
                    <?php foreach ($students as $student): ?>
                        <?php if (count($student["followUpReasons"])): ?>
                            <article class="followup-preview-item">
                                <h4><?php echo e($student["name"]); ?></h4>
                                <p><?php echo e(implode(" - ", $student["followUpReasons"])); ?></p>
                            </article>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </section>

            <section id="weekly-report" class="dashboard-section card" data-section>
                <div class="panel-header">
                    <h2>التقرير الأسبوعي للحلقة</h2>
                    <span>إدخال تسميع الطلاب لهذا الأسبوع</span>
                </div>

                <p class="section-note">يمكن إضافة أكثر من دفعة تسميع لكل طالب، ويتم تحديث المجاميع تلقائيا.</p>
                <?php if (isset($_GET["success"]) && $_GET["success"] === "recitation_added"): ?>
                    <p class="section-note">تم حفظ التسميع بنجاح.</p>
                <?php elseif (isset($_GET["error"]) && $_GET["error"] === "recitation_failed"): ?>
                    <p class="section-note">تعذر حفظ التسميع، يرجى التحقق من البيانات.</p>
                <?php endif; ?>

                <div id="weekly-report-list" class="weekly-report-list">
                    <?php foreach ($students as $student): ?>
                        <?php
                        $hifzTotal = 0;
                        $reviewTotal = 0;
                        foreach ($student["batches"] as $studentBatch) {
                            if ($studentBatch["type"] === "hifz") {
                                $hifzTotal += numberValue($studentBatch["pages"]);
                            } else {
                                $reviewTotal += numberValue($studentBatch["pages"]);
                            }
                        }
                        ?>
                        <form class="weekly-student-card" data-student-card data-student-id="<?php echo e($student["id"]); ?>" action="../php/add_recitation.php" method="POST">
                            <input type="hidden" name="student_id" value="<?php echo e($student["id"]); ?>">
                            <header class="weekly-student-head">
                                <div>
                                    <h3><?php echo e($student["name"]); ?></h3>
                                    <p>نوع الطالب: <?php echo e($student["studentType"]); ?></p>
                                </div>
                                <button type="button" class="btn-secondary add-batch-btn" data-add-batch>+ إضافة دفعة تسميع</button>
                            </header>
                            <div class="weekly-student-body">
                                <div class="table-wrap">
                                    <table class="weekly-table">
                                        <thead><tr><th>الأسبوع</th><th>النوع</th><th>من</th><th>إلى</th><th>عدد الصفحات</th><th>ملاحظات</th><th>إجراء</th></tr></thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <select name="week_id" required>
                                                        <?php foreach ($weeks as $week): ?>
                                                            <option value="<?php echo e($week["week_id"]); ?>"<?php echo (int) $week["week_id"] === (int) $latestWeekId ? " selected" : ""; ?>>الأسبوع <?php echo e($week["week_number"]); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="type" data-field="type" required>
                                                        <option value="حفظ">حفظ</option>
                                                        <option value="مراجعة">مراجعة</option>
                                                    </select>
                                                </td>
                                                <td><input type="text" name="from_page" data-field="from" required></td>
                                                <td><input type="text" name="to_page" data-field="to" required></td>
                                                <td><input type="number" name="pages_count" class="pages-field" min="1" required></td>
                                                <td><textarea rows="1" name="notes" data-field="notes"></textarea></td>
                                                <td><button type="submit" class="btn-secondary">حفظ التسميع</button></td>
                                            </tr>
                                        <?php if (!count($student["batches"])): ?>
                                            <tr><td colspan="7">لا توجد دفعات مسجلة لهذا الأسبوع</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($student["batches"] as $batchIndex => $studentBatch): ?>
                                                <tr data-batch-row data-batch-index="<?php echo e($batchIndex); ?>">
                                                    <td><?php echo e($latestWeekNumber ? "الأسبوع " . $latestWeekNumber : "لا يوجد"); ?></td>
                                                    <td>
                                                        <select data-field="type" disabled>
                                                            <option value="hifz"<?php echo $studentBatch["type"] === "hifz" ? " selected" : ""; ?>>حفظ</option>
                                                            <option value="review"<?php echo $studentBatch["type"] === "review" ? " selected" : ""; ?>>مراجعة</option>
                                                        </select>
                                                    </td>
                                                    <td><input type="text" data-field="from" value="<?php echo e($studentBatch["from"]); ?>" readonly></td>
                                                    <td><input type="text" data-field="to" value="<?php echo e($studentBatch["to"]); ?>" readonly></td>
                                                    <td><input type="number" class="pages-field" value="<?php echo e($studentBatch["pages"]); ?>" readonly></td>
                                                    <td><textarea rows="1" data-field="notes" readonly><?php echo e($studentBatch["notes"]); ?></textarea></td>
                                                    <td>مسجل</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="weekly-totals">
                                    <span class="total-pill">مجموع صفحات الحفظ: <strong data-total-hifz><?php echo e($hifzTotal); ?></strong></span>
                                    <span class="total-pill">مجموع صفحات المراجعة: <strong data-total-review><?php echo e($reviewTotal); ?></strong></span>
                                    <span class="total-pill">المجموع الكلي: <strong data-total-all><?php echo e($hifzTotal + $reviewTotal); ?></strong></span>
                                </div>
                            </div>
                        </form>
                    <?php endforeach; ?>
                </div>
            </section>

            <section id="stats" class="dashboard-section card" data-section>
                <div class="panel-header">
                    <h2>إحصائيات الحلقة من بداية الفصل</h2>
                    <span>متابعة الأداء التراكمي</span>
                </div>

                <div class="stats-layout">
                    <article class="stats-panel">
                        <h3>أداء الحلقة حسب الأسابيع</h3>
                        <div class="table-wrap">
                            <table class="data-table compact-table">
                                <thead>
                                <tr>
                                    <th>الأسبوع</th>
                                    <th>نسبة التسميع</th>
                                    <th>عدد الطلاب المسمعين</th>
                                    <th>مجموع الصفحات</th>
                                </tr>
                                </thead>
                                <tbody id="weekly-performance-body">
                                <?php foreach ($weeklyPerformance as $weekItem): ?>
                                    <tr>
                                        <td><?php echo e($weekItem["week"]); ?></td>
                                        <td><?php echo e($weekItem["rate"]); ?>%</td>
                                        <td><?php echo e($weekItem["heard"]); ?></td>
                                        <td><?php echo e($weekItem["pages"]); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </article>

                    <article class="stats-panel">
                        <h3>مؤشر نسبة التسميع</h3>
                        <div id="weekly-performance-chart" class="simple-bars">
                            <?php foreach ($weeklyPerformance as $weekItem): ?>
                                <div class="simple-bar-row">
                                    <span><?php echo e(str_replace("الأسبوع ", "أ", $weekItem["week"])); ?></span>
                                    <div class="simple-bar-track"><i style="width: <?php echo e($weekItem["rate"]); ?>%;"></i></div>
                                    <strong><?php echo e($weekItem["rate"]); ?>%</strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </article>
                </div>

                <div class="panel-subheader">
                    <h3>الإحصائيات التراكمية لكل طالب</h3>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                        <tr>
                            <th>اسم الطالب</th>
                            <th>إجمالي ما حفظه</th>
                            <th>إجمالي ما راجعه</th>
                            <th>عدد مرات التسميع</th>
                            <th>عدد الأسابيع النشطة</th>
                            <th>آخر أسبوع سمع فيه</th>
                        </tr>
                        </thead>
                        <tbody id="cumulative-stats-body">
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo e($student["name"]); ?></td>
                                <td><?php echo e($student["cumulativeHifz"]); ?></td>
                                <td><?php echo e($student["cumulativeReview"]); ?></td>
                                <td><?php echo e($student["cumulativeSessions"]); ?></td>
                                <td><?php echo e($student["activeWeeks"]); ?></td>
                                <td><?php echo e($student["lastActiveWeek"]); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="students" class="dashboard-section card" data-section>
                <div class="panel-header">
                    <h2>طلاب الحلقة</h2>
                    <span>متابعة المستوى والحضور</span>
                </div>

                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                        <tr>
                            <th>اسم الطالب</th>
                            <th>المستوى الحالي</th>
                            <th>حالة الحضور</th>
                            <th>حالة الالتزام</th>
                            <th>وصول سريع للتقرير الأسبوعي</th>
                            <th>حالة الطالب</th>
                        </tr>
                        </thead>
                        <tbody id="students-page-body">
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo e($student["name"]); ?></td>
                                <td><?php echo e($student["studentType"]); ?></td>
                                <td><?php echo e($student["attendance"]); ?></td>
                                <td><?php echo e($student["engagement"]); ?></td>
                                <td><a href="#weekly-report" class="quick-link">إدخال تسميع</a></td>
                                <td><span class="status-badge <?php echo e(statusClass($student["status"])); ?>"><?php echo e($student["status"]); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="follow-up" class="dashboard-section card" data-section>
                <div class="panel-header">
                    <h2>الطلاب الذين يحتاجون متابعة</h2>
                    <span>حالات تتطلب تدخل المشرف</span>
                </div>

                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                        <tr>
                            <th>اسم الطالب</th>
                            <th>آخر تسميع</th>
                            <th>سبب المتابعة</th>
                            <th>الحالة</th>
                            <th>إجراء مقترح</th>
                        </tr>
                        </thead>
                        <tbody id="followup-table-body">
                        <?php foreach ($students as $student): ?>
                            <?php if (count($student["followUpReasons"])): ?>
                                <tr>
                                    <td><?php echo e($student["name"]); ?></td>
                                    <td><?php echo e($student["lastActiveWeek"] === "لا يوجد" ? "لا يوجد" : $student["lastActiveWeek"]); ?></td>
                                    <td>
                                        <?php foreach ($student["followUpReasons"] as $reason): ?>
                                            <span class="reason-badge status-alert"><?php echo e($reason); ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td><span class="status-badge <?php echo e(statusClass($student["status"])); ?>"><?php echo e($student["status"]); ?></span></td>
                                    <td><?php echo e($student["actionSuggestion"]); ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="settings" class="dashboard-section card" data-section>
                <div class="panel-header">
                    <h2>الإعدادات</h2>
                    <span>تخصيص متابعة الحلقة</span>
                </div>

                <div class="settings-grid">
                    <div class="settings-item">
                        <h3>موعد إغلاق التقرير الأسبوعي</h3>
                        <p>الجمعة - 08:00 مساء</p>
                    </div>
                    <div class="settings-item">
                        <h3>تنبيه الطلاب المتأخرين</h3>
                        <p>مفعل - يرسل يوم الأربعاء</p>
                    </div>
                    <div class="settings-item">
                        <h3>صيغة التقرير المعتمد</h3>
                        <p>تقرير الحلقة المختصر</p>
                    </div>
                </div>
            </section>
        </section>
    </main>

</div>

<script>
window.supervisorDashboardState = <?php echo json_encode($dashboardState, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>
<script src="../assets/js/halqa-supervisor-dashboard.js?v=20260518-2"></script>
</body>
</html>

