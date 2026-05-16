<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'project');
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    die("يجب تسجيل الدخول كطالب لعرض الشهادات");
}

$student_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT 
        cert.certificate_code,
        cert.course_code,
        cert.final_grade,
        cert.date,
        c.title AS skill_title
    FROM certificate cert
    INNER JOIN course c ON cert.course_code = c.course_code
    WHERE cert.student_id = ?
    ORDER BY cert.date DESC, cert.certificate_code DESC
");

$stmt->bind_param("i", $student_id);
$stmt->execute();
$certificates = $stmt->get_result();

function safeText($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>شهاداتي</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Cairo", sans-serif;
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at top right, rgba(46, 119, 192, 0.16), transparent 30%),
                linear-gradient(180deg, #f7fbff 0%, #eef3f8 100%);
            color: #16324f;
        }

        .cert-page {
            width: min(1180px, 92%);
            margin: 0 auto;
            padding: 38px 0 70px;
        }

        .cert-hero {
            position: relative;
            overflow: hidden;
            min-height: 245px;
            border-radius: 34px;
            padding: 42px 46px;
            background:
                linear-gradient(135deg, #2e77c0, #3d8de0),
                radial-gradient(circle at left top, rgba(255, 255, 255, 0.3), transparent 35%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 28px;
            box-shadow: 0 20px 50px rgba(46, 119, 192, 0.25);
        }

        .cert-hero::before {
            content: "";
            position: absolute;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            left: -100px;
            top: -120px;
            background: rgba(255, 255, 255, 0.15);
        }

        .cert-hero::after {
            content: "";
            position: absolute;
            width: 230px;
            height: 230px;
            border-radius: 50%;
            right: 44%;
            bottom: -145px;
            background: rgba(255, 255, 255, 0.11);
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 720px;
        }

        .hero-badge {
            display: inline-flex;
            padding: 8px 18px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.28);
            font-size: 14px;
            font-weight: 900;
            margin-bottom: 16px;
        }

        .cert-hero h1 {
            font-size: 42px;
            font-weight: 900;
            margin-bottom: 14px;
            letter-spacing: -0.5px;
        }

        .cert-hero p {
            max-width: 680px;
            font-size: 18px;
            line-height: 2;
            color: rgba(255, 255, 255, 0.92);
        }

        .hero-icon {
            position: relative;
            z-index: 2;
            width: 128px;
            height: 128px;
            border-radius: 32px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.26);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .hero-icon .material-symbols-outlined {
            font-size: 74px;
            color: white;
        }

        .cert-panel {
            margin-top: 34px;
            padding: 34px;
            border-radius: 34px;
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid #dbe6f1;
            box-shadow: 0 14px 35px rgba(22, 50, 79, 0.09);
            backdrop-filter: blur(14px);
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 28px;
        }

        .section-label {
            color: #2e77c0;
            font-size: 15px;
            font-weight: 900;
            margin-bottom: 4px;
        }

        .panel-header h2 {
            color: #111827;
            font-size: 30px;
            font-weight: 900;
        }

        .cert-count {
            min-height: 46px;
            padding: 0 18px;
            border-radius: 999px;
            background: #eaf3fc;
            color: #2e77c0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 15px;
            font-weight: 900;
            white-space: nowrap;
        }

        .cert-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
        }

        .cert-card {
            position: relative;
            overflow: hidden;
            min-height: 345px;
            border-radius: 28px;
            background: white;
            border: 1px solid #dbe6f1;
            box-shadow: 0 12px 28px rgba(22, 50, 79, 0.08);
            padding: 24px;
            display: flex;
            flex-direction: column;
            transition: 0.28s ease;
        }

        .cert-card::before {
            content: "";
            position: absolute;
            inset: 0;
            height: 8px;
            background: linear-gradient(90deg, #2e77c0, #60a5fa, #93c5fd);
        }

        .cert-card::after {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            left: -85px;
            top: -70px;
            background: rgba(46, 119, 192, 0.08);
        }

        .cert-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 22px 48px rgba(22, 50, 79, 0.15);
            border-color: rgba(46, 119, 192, 0.38);
        }

        .cert-card-top {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 22px;
        }

        .cert-icon {
            width: 58px;
            height: 58px;
            border-radius: 20px;
            background: linear-gradient(135deg, #eaf3fc, #ffffff);
            border: 1px solid #dbeafe;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2e77c0;
        }

        .cert-icon .material-symbols-outlined {
            font-size: 34px;
        }

        .cert-status {
            padding: 7px 14px;
            border-radius: 999px;
            background: #dcfce7;
            color: #15803d;
            font-size: 13px;
            font-weight: 900;
        }

        .cert-body {
            position: relative;
            z-index: 2;
            flex: 1;
        }

        .cert-label {
            color: #6b7c90;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 7px;
        }

        .cert-body h3 {
            color: #111827;
            font-size: 23px;
            font-weight: 900;
            line-height: 1.55;
            min-height: 70px;
            margin-bottom: 22px;

            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .cert-meta {
            display: grid;
            gap: 12px;
        }

        .meta-item {
            min-height: 58px;
            padding: 11px 13px;
            border-radius: 16px;
            background: #f7fbff;
            border: 1px solid #e5eef8;
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .meta-item .material-symbols-outlined {
            width: 34px;
            height: 34px;
            border-radius: 12px;
            background: #eaf3fc;
            color: #2e77c0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            flex-shrink: 0;
        }

        .meta-item small {
            display: block;
            margin-bottom: 1px;
            color: #6b7c90;
            font-size: 12px;
            font-weight: 800;
        }

        .meta-item strong {
            display: block;
            color: #16324f;
            font-size: 15px;
            font-weight: 900;
        }

        .cert-actions {
            position: relative;
            z-index: 2;
            margin-top: 22px;
        }

        .view-btn {
            width: 100%;
            height: 48px;
            border-radius: 16px;
            background: linear-gradient(135deg, #2e77c0, #3d8de0);
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 15px;
            font-weight: 900;
            box-shadow: 0 10px 22px rgba(46, 119, 192, 0.24);
            transition: 0.25s ease;
        }

        .view-btn:hover {
            background: linear-gradient(135deg, #255f9b, #2e77c0);
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(46, 119, 192, 0.3);
        }

        .empty-box {
            min-height: 360px;
            border-radius: 28px;
            background: linear-gradient(180deg, white, #f7fbff);
            border: 1px dashed #bfd3e8;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            text-align: center;
            padding: 40px 24px;
        }

        .empty-icon {
            width: 92px;
            height: 92px;
            border-radius: 28px;
            background: #eaf3fc;
            color: #2e77c0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .empty-icon .material-symbols-outlined {
            font-size: 50px;
        }

        .empty-box h3 {
            color: #111827;
            font-size: 25px;
            font-weight: 900;
            margin-bottom: 10px;
        }

        .empty-box p {
            color: #6b7c90;
            font-size: 16px;
            line-height: 1.9;
            margin-bottom: 24px;
        }

        .back-btn {
            height: 46px;
            padding: 0 22px;
            border-radius: 999px;
            background: #2e77c0;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 15px;
            font-weight: 900;
            transition: 0.25s ease;
        }

        .back-btn:hover {
            background: #255f9b;
            transform: translateY(-2px);
        }

        .top-return {
            margin-bottom: 22px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #2e77c0;
            background: white;
            border: 1px solid #dbe6f1;
            border-radius: 999px;
            padding: 10px 18px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 900;
            box-shadow: 0 8px 20px rgba(22, 50, 79, 0.07);
            transition: 0.25s ease;
        }

        .top-return:hover {
            background: #eaf3fc;
            transform: translateY(-2px);
        }

        @media (max-width: 1150px) {
            .cert-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .cert-page {
                width: 92%;
                padding: 24px 0 60px;
            }

            .cert-hero {
                padding: 32px 24px;
                border-radius: 26px;
                flex-direction: column;
                align-items: flex-start;
            }

            .cert-hero h1 {
                font-size: 32px;
            }

            .cert-hero p {
                font-size: 16px;
            }

            .hero-icon {
                width: 96px;
                height: 96px;
                border-radius: 26px;
            }

            .hero-icon .material-symbols-outlined {
                font-size: 54px;
            }

            .cert-panel {
                padding: 22px;
                border-radius: 26px;
            }

            .panel-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .cert-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<main class="cert-page">

    <a href="second.php" class="top-return">
        <span class="material-symbols-outlined">arrow_forward</span>
        العودة للرئيسية
    </a>

    <section class="cert-hero">
        <div class="hero-content">
            <span class="hero-badge">شهاداتي</span>
            <h1>الشهادات المكتسبة</h1>
            <p>
                هنا يمكنك استعراض جميع الشهادات التي حصلت عليها بعد إكمال المهارات والاختبارات النهائية بنجاح.
            </p>
        </div>

        <div class="hero-icon">
            <span class="material-symbols-outlined">workspace_premium</span>
        </div>
    </section>

    <section class="cert-panel">

        <div class="panel-header">
            <div>
                <p class="section-label">Certificates</p>
                <h2>قائمة الشهادات</h2>
            </div>

            <div class="cert-count">
                <span class="material-symbols-outlined">verified</span>
                <span><?php echo $certificates->num_rows; ?> شهادة</span>
            </div>
        </div>

        <?php if ($certificates->num_rows === 0): ?>

            <div class="empty-box">
                <div class="empty-icon">
                    <span class="material-symbols-outlined">school</span>
                </div>

                <h3>لا توجد شهادات حتى الآن</h3>
                <p>
                    أكمل اختبارًا نهائيًا لإصدار شهادة، وبعدها ستظهر شهادتك هنا.
                </p>

                <a href="second.php" class="back-btn">
                    العودة إلى المهارات
                    <span class="material-symbols-outlined">arrow_back</span>
                </a>
            </div>

        <?php else: ?>

            <div class="cert-grid">

                <?php while ($cert = $certificates->fetch_assoc()): ?>

                    <article class="cert-card">

                        <div class="cert-card-top">
                            <div class="cert-icon">
                                <span class="material-symbols-outlined">workspace_premium</span>
                            </div>

                            <div class="cert-status">
                                مكتملة
                            </div>
                        </div>

                        <div class="cert-body">
                            <p class="cert-label">اسم المهارة</p>

                            <h3><?php echo safeText($cert['skill_title']); ?></h3>

                            <div class="cert-meta">

                                <div class="meta-item">
                                    <span class="material-symbols-outlined">grade</span>
                                    <div>
                                        <small>الدرجة النهائية</small>
                                        <strong><?php echo safeText($cert['final_grade']); ?>%</strong>
                                    </div>
                                </div>

                                <div class="meta-item">
                                    <span class="material-symbols-outlined">calendar_month</span>
                                    <div>
                                        <small>تاريخ الإصدار</small>
                                        <strong><?php echo safeText($cert['date']); ?></strong>
                                    </div>
                                </div>

                                <div class="meta-item">
                                    <span class="material-symbols-outlined">tag</span>
                                    <div>
                                        <small>رقم الشهادة</small>
                                        <strong>CERT-<?php echo str_pad($cert['certificate_code'], 5, "0", STR_PAD_LEFT); ?></strong>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="cert-actions">
                            <a class="view-btn" href="certificate.php?course=<?php echo urlencode($cert['course_code']); ?>">
                                عرض الشهادة
                                <span class="material-symbols-outlined">open_in_new</span>
                            </a>
                        </div>

                    </article>

                <?php endwhile; ?>

            </div>

        <?php endif; ?>

    </section>

</main>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>