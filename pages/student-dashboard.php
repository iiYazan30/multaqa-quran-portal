<?php
session_start();
require_once __DIR__ . "/../php/db_connect.php";
/** @var PDO $pdo */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

$sessionRole = isset($_SESSION["role"]) ? $_SESSION["role"] : (isset($_SESSION["role_name"]) ? $_SESSION["role_name"] : "");
if ($sessionRole !== "student") {
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

function formatPercentValue($value, $fallback)
{
    if ($value === null || $value === "") {
        return $fallback;
    }

    return formatNumberValue($value) . "%";
}

function gradeBadgeClass($grade, $score)
{
    if (strpos((string) $grade, "ممتاز") !== false || (float) $score >= 90) {
        return "grade-excellent";
    }

    if (strpos((string) $grade, "جيد جدا") !== false || strpos((string) $grade, "جيد جداً") !== false || (float) $score >= 85) {
        return "grade-good";
    }

    return "grade-mid";
}

$userId = $_SESSION["user_id"];

$studentStmt = $pdo->prepare("
    SELECT
        students.student_id,
        students.name AS student_name,
        users.username,
        halqas.name AS halqa_name,
        supervisors.name AS supervisor_name
    FROM students
    JOIN users ON students.user_id = users.user_id
    LEFT JOIN halqas ON students.halqa_id = halqas.halqa_id
    LEFT JOIN supervisors ON halqas.supervisor_id = supervisors.supervisor_id
    WHERE students.user_id = ?
    LIMIT 1
");
$studentStmt->execute(array($userId));
$student = $studentStmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header("Location: ../login.php");
    exit;
}

$studentId = $student["student_id"];
$studentName = fallbackValue($student["student_name"], fallbackValue(isset($_SESSION["username"]) ? $_SESSION["username"] : "", "لا يوجد"));
$halqaName = fallbackValue($student["halqa_name"], "لا يوجد");
$supervisorName = fallbackValue($student["supervisor_name"], "لا يوجد");

$recitationStatsStmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(pages_count), 0) AS total_pages,
        COALESCE(SUM(CASE WHEN type + 0 = 1 THEN pages_count ELSE 0 END), 0) AS memorization_pages,
        COALESCE(SUM(CASE WHEN type + 0 = 2 THEN pages_count ELSE 0 END), 0) AS revision_pages
    FROM recitations
    WHERE student_id = ?
");
$recitationStatsStmt->execute(array($studentId));
$recitationStats = $recitationStatsStmt->fetch(PDO::FETCH_ASSOC);

$examStatsStmt = $pdo->prepare("
    SELECT
        COUNT(exam_id) AS exams_count,
        MAX(score) AS highest_score,
        AVG(score) AS average_score
    FROM exams
    WHERE student_id = ?
");
$examStatsStmt->execute(array($studentId));
$examStats = $examStatsStmt->fetch(PDO::FETCH_ASSOC);

$latestExamStmt = $pdo->prepare("
    SELECT score, exam_date
    FROM exams
    WHERE student_id = ?
    ORDER BY exam_date DESC, exam_id DESC
    LIMIT 1
");
$latestExamStmt->execute(array($studentId));
$latestExam = $latestExamStmt->fetch(PDO::FETCH_ASSOC);

$examsStmt = $pdo->prepare("
    SELECT part_name, exam_date, score, grade, notes
    FROM exams
    WHERE student_id = ?
    ORDER BY exam_date DESC, exam_id DESC
");
$examsStmt->execute(array($studentId));
$exams = $examsStmt->fetchAll(PDO::FETCH_ASSOC);

$weeklyStmt = $pdo->prepare("
    SELECT
        weeks.week_number,
        COALESCE(SUM(CASE WHEN recitations.type + 0 = 1 THEN recitations.pages_count ELSE 0 END), 0) AS memorization_pages,
        COALESCE(SUM(CASE WHEN recitations.type + 0 = 2 THEN recitations.pages_count ELSE 0 END), 0) AS revision_pages
    FROM recitations
    JOIN weeks ON recitations.week_id = weeks.week_id
    WHERE recitations.student_id = ?
    GROUP BY weeks.week_id, weeks.week_number
    ORDER BY weeks.week_number ASC
");
$weeklyStmt->execute(array($studentId));
$weeklyRows = $weeklyStmt->fetchAll(PDO::FETCH_ASSOC);

$weeklyMemorizationTotal = 0;
$weeklyRevisionTotal = 0;
foreach ($weeklyRows as $weeklyRow) {
    $weeklyMemorizationTotal += (int) $weeklyRow["memorization_pages"];
    $weeklyRevisionTotal += (int) $weeklyRow["revision_pages"];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة الطالب - ملتقى القرآن</title>

    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/student-dashboard.css">
</head>
<body>

<div class="app-layout">

    <aside class="sidebar">
        <div class="sidebar-top">
            <div class="sidebar-brand">
                <h2>ملتقى القرآن</h2>
                <p>لوحة الطالب</p>
            </div>

            <nav class="sidebar-menu" aria-label="قائمة الطالب">
                <a href="#overview" class="sidebar-link active" data-section-link>الرئيسية</a>
                <a href="#progress" class="sidebar-link" data-section-link>تقدمي</a>
                <a href="#part-exams" class="sidebar-link" data-section-link>امتحانات الأجزاء</a>
                <a href="#profile" class="sidebar-link" data-section-link>الملف الشخصي</a>
                <a href="#settings" class="sidebar-link" data-section-link>الإعدادات</a>
            </nav>
        </div>

        <div class="sidebar-bottom">
            <div class="sidebar-user">
                <h4><?php echo e($studentName); ?></h4>
                <p>طالب - <?php echo e($halqaName); ?></p>
            </div>
        </div>
    </aside>

    <main class="main-area">
        <header class="topbar">
            <div class="topbar-right">
                <h1>لوحة تقدمي الشخصية</h1>
                <p>متابعة إنجازي في الحفظ والمراجعة منذ انضمامي إلى الملتقى</p>
            </div>

            <div class="topbar-left">
                <div class="topbar-context">
                    <span>الطالب: <strong><?php echo e($studentName); ?></strong></span>
                    <span>الحلقة: <strong><?php echo e($halqaName); ?></strong></span>
                </div>
                <div class="topbar-badge">أهلا بك، واصل تقدمك</div>
                <a class="topbar-home-link" href="../index.php">
                    <span aria-hidden="true">↩</span>
                    <span>العودة للصفحة الرئيسية</span>
                </a>
            </div>
        </header>

        <section class="page-content">
            <section id="overview" class="dashboard-section card" data-section>
                <div class="panel-header">
                    <h2>ملخص الإنجاز منذ بداية الملتقى</h2>
                    <span>إجمالي التقدم</span>
                </div>

                <div class="summary-grid">
                    <article class="summary-item">
                        <h3><?php echo e(formatNumberValue($recitationStats["total_pages"])); ?> صفحة</h3>
                        <p>إجمالي ما سمعته منذ بداية الملتقى</p>
                    </article>
                    <article class="summary-item">
                        <h3><?php echo e(formatNumberValue($recitationStats["memorization_pages"])); ?> صفحة</h3>
                        <p>إجمالي صفحات الحفظ</p>
                    </article>
                    <article class="summary-item">
                        <h3><?php echo e(formatNumberValue($recitationStats["revision_pages"])); ?> صفحة</h3>
                        <p>إجمالي صفحات المراجعة</p>
                    </article>
                    <article class="summary-item">
                        <h3><?php echo e(formatNumberValue($examStats["exams_count"])); ?> امتحانات</h3>
                        <p>عدد امتحانات الأجزاء</p>
                    </article>
                </div>
            </section>

            <section id="progress" class="dashboard-section card" data-section>
                <div class="panel-header">
                    <h2>تقدمي الحالي</h2>
                    <span>مؤشرات شخصية</span>
                </div>

                <div class="progress-grid">
                    <article class="progress-item">
                        <h3><?php echo e(formatPercentValue($examStats["average_score"], "0")); ?></h3>
                        <p>متوسط علامات امتحانات الأجزاء</p>
                    </article>
                    <article class="progress-item">
                        <h3><?php echo e($latestExam ? formatPercentValue($latestExam["score"], "لا يوجد") : "لا يوجد"); ?></h3>
                        <p>آخر علامة محققة</p>
                    </article>
                    <article class="progress-item">
                        <h3><?php echo e($latestExam ? $latestExam["exam_date"] : "لم يتم التسجيل بعد"); ?></h3>
                        <p>آخر اختبار</p>
                    </article>
                </div>
            </section>

            <section id="part-exams" class="dashboard-section card" data-section>
                <div class="panel-header">
                    <h2>أداء امتحانات الأجزاء</h2>
                    <span>ملخص ونتائج</span>
                </div>

                <div class="exam-summary-grid">
                    <article class="exam-summary-item">
                        <h3><?php echo e(formatNumberValue($examStats["exams_count"])); ?></h3>
                        <p>عدد امتحانات الأجزاء</p>
                    </article>
                    <article class="exam-summary-item">
                        <h3><?php echo e(formatPercentValue($examStats["highest_score"], "0")); ?></h3>
                        <p>أعلى علامة</p>
                    </article>
                    <article class="exam-summary-item">
                        <h3><?php echo e(formatPercentValue($examStats["average_score"], "0")); ?></h3>
                        <p>متوسط العلامات</p>
                    </article>
                </div>

                <div class="table-wrap section-table-gap">
                    <table class="data-table exams-table">
                        <thead>
                        <tr>
                            <th>اسم الجزء</th>
                            <th>تاريخ الامتحان</th>
                            <th>العلامة</th>
                            <th>التقدير</th>
                            <th>ملاحظات</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (count($exams) === 0): ?>
                        <tr>
                            <td colspan="5">لا يوجد</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($exams as $exam): ?>
                        <tr>
                            <td><?php echo e($exam["part_name"]); ?></td>
                            <td><?php echo e($exam["exam_date"]); ?></td>
                            <td><?php echo e(formatPercentValue($exam["score"], "0")); ?></td>
                            <td><span class="grade-badge <?php echo e(gradeBadgeClass($exam["grade"], $exam["score"])); ?>"><?php echo e(fallbackValue($exam["grade"], "لا يوجد")); ?></span></td>
                            <td><?php echo e(fallbackValue($exam["notes"], "لا يوجد")); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="profile" class="dashboard-section card" data-section>
                <div class="panel-header">
                    <h2>الملف الشخصي</h2>
                    <span>معلوماتي الأساسية</span>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <h3>الاسم الكامل</h3>
                        <p><?php echo e($studentName); ?></p>
                    </div>
                    <div class="info-item">
                        <h3>رقم التسجيل</h3>
                        <p><?php echo e($studentId); ?></p>
                    </div>
                    <div class="info-item">
                        <h3>الحلقة</h3>
                        <p><?php echo e($halqaName); ?></p>
                    </div>
                    <div class="info-item">
                        <h3>المشرف</h3>
                        <p><?php echo e($supervisorName); ?></p>
                    </div>
                </div>
            </section>

            <section id="settings" class="dashboard-section card" data-section>
                <div class="panel-header">
                    <h2>الإعدادات</h2>
                    <span>خيارات سريعة</span>
                </div>

                <div class="settings-grid">
                    <div class="settings-item">
                        <h3>لغة الواجهة</h3>
                        <p>العربية</p>
                    </div>
                    <div class="settings-item">
                        <h3>تنبيه موعد التسميع</h3>
                        <p>مفعل - يوم الثلاثاء 07:00 مساء</p>
                    </div>
                    <div class="settings-item">
                        <h3>تنبيه الامتحانات</h3>
                        <p>قبل موعد الامتحان بـ 48 ساعة</p>
                    </div>
                </div>
            </section>

            <section id="weekly-summary" class="dashboard-section card" data-section>
                <div class="panel-header">
                    <h2>ملخص التسميع الأسبوعي</h2>
                    <span>عرض مختصر</span>
                </div>

                <div class="table-wrap">
                    <table class="data-table weekly-summary-table">
                        <thead>
                        <tr>
                            <th>الأسبوع</th>
                            <th>صفحات الحفظ</th>
                            <th>صفحات المراجعة</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (count($weeklyRows) === 0): ?>
                        <tr>
                            <td>لا يوجد</td>
                            <td>0</td>
                            <td>0</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($weeklyRows as $weeklyRow): ?>
                        <tr>
                            <td>الأسبوع <?php echo e($weeklyRow["week_number"]); ?></td>
                            <td><?php echo e(formatNumberValue($weeklyRow["memorization_pages"])); ?></td>
                            <td><?php echo e(formatNumberValue($weeklyRow["revision_pages"])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <td>الإجمالي</td>
                            <td><?php echo e(formatNumberValue($weeklyMemorizationTotal)); ?></td>
                            <td><?php echo e(formatNumberValue($weeklyRevisionTotal)); ?></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        </section>
    </main>

</div>

<script src="../assets/js/student-dashboard.js"></script>
</body>
</html>

