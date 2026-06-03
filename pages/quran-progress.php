<?php
session_start();

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

$studentName = fallbackValue(isset($_SESSION["username"]) ? $_SESSION["username"] : "", "طالب ملتقى القرآن");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خريطة حفظ القرآن - ملتقى القرآن</title>

    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/student-dashboard.css">
    <link rel="stylesheet" href="../assets/css/quran-progress.css">
</head>
<body class="quran-progress-body">

<div class="app-layout">

    <aside class="sidebar">
        <div class="sidebar-top">
            <div class="sidebar-brand">
                <h2>ملتقى القرآن</h2>
                <p>لوحة الطالب</p>
            </div>

            <nav class="sidebar-menu" aria-label="قائمة الطالب">
                <a href="student-dashboard.php#overview" class="sidebar-link">الرئيسية</a>
                <a href="student-dashboard.php#progress" class="sidebar-link">تقدمي</a>
                <a href="student-dashboard.php#exam-request-section" class="sidebar-link sidebar-link-highlight">طلب امتحان</a>
                <a href="student-dashboard.php#part-exams" class="sidebar-link">امتحانات الأجزاء</a>
                <a href="quran-progress.php" class="sidebar-link sidebar-link-map active" aria-current="page">خريطة الحفظ</a>
                <a href="student-dashboard.php#profile" class="sidebar-link">الملف الشخصي</a>
                <a href="student-dashboard.php#settings" class="sidebar-link">الإعدادات</a>
            </nav>
        </div>

        <div class="sidebar-bottom">
            <div class="sidebar-user">
                <h4><?php echo e($studentName); ?></h4>
                <p>طالب - خريطة الحفظ</p>
            </div>
        </div>
    </aside>

    <main class="main-area">
        <header class="topbar quran-topbar">
            <div class="topbar-right">
                <h1>خريطة حفظ القرآن</h1>
                <p>تابع السور التي أتممت حفظها بطريقة بصرية جميلة</p>
            </div>

            <div class="topbar-left">
                <div class="topbar-context">
                    <span>الطالب: <strong><?php echo e($studentName); ?></strong></span>
                    <span>الوضع: <strong>تجريبي</strong></span>
                </div>
                <div class="topbar-badge">رحلة حفظ مباركة</div>
                <a class="topbar-home-link" href="../index.php">
                    <span aria-hidden="true">↩</span>
                    <span>العودة للصفحة الرئيسية</span>
                </a>
            </div>
        </header>

        <section class="page-content quran-progress-content">
            <section class="quran-summary-grid" aria-label="ملخص خريطة الحفظ">
                <article class="quran-summary-card">
                    <span>السور المحفوظة</span>
                    <strong id="completed-surahs-count">19</strong>
                </article>
                <article class="quran-summary-card">
                    <span>إجمالي السور</span>
                    <strong id="total-surahs-count">114</strong>
                </article>
                <article class="quran-summary-card">
                    <span>نسبة الإنجاز</span>
                    <strong id="completion-percentage">17%</strong>
                </article>
                <article class="quran-summary-card">
                    <span>آخر سورة مكتملة</span>
                    <strong id="latest-completed-surah">مريم</strong>
                </article>
            </section>

            <section class="quran-map-shell card" aria-labelledby="quran-map-title">
                <div class="quran-map-header">
                    <div>
                        <span class="quran-map-kicker">المصحف الشريف</span>
                        <h2 id="quran-map-title">خريطة السور</h2>
                    </div>
                    <div class="quran-map-legend" aria-label="حالات السور">
                        <span class="legend-item legend-completed">مكتملة</span>
                        <span class="legend-item legend-pending">بانتظار الحفظ</span>
                    </div>
                </div>

                <div class="quran-progress-track" aria-hidden="true">
                    <span id="quran-progress-fill"></span>
                </div>

                <div id="surah-map" class="surah-map-grid" aria-live="polite"></div>

                <p class="quran-demo-note">هذه الخريطة تجريبية وسيتم ربطها ببيانات الحفظ لاحقًا</p>
            </section>
        </section>
    </main>

</div>

<script src="../assets/js/quran-progress.js"></script>
</body>
</html>
