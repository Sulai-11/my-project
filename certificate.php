<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'project');
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    die("يجب تسجيل الدخول كطالب لعرض الشهادة");
}

if (!isset($_GET['course'])) {
    die("رقم المهارة غير موجود");
}

$student_id = (int)$_SESSION['user_id'];
$course_code = (int)$_GET['course'];

$stmt = $conn->prepare("
    SELECT 
        cert.certificate_code,
        cert.final_grade,
        cert.date,
        c.title AS skill_title,
        s.first_name,
        s.last_name
    FROM certificate cert
    INNER JOIN course c ON cert.course_code = c.course_code
    INNER JOIN student s ON cert.student_id = s.student_id
    WHERE cert.student_id = ? AND cert.course_code = ?
    LIMIT 1
");

$stmt->bind_param("ii", $student_id, $course_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("لم يتم إصدار شهادة لهذه المهارة بعد");
}

$certificate = $result->fetch_assoc();
$stmt->close();
$conn->close();

$studentName = $certificate['first_name'] . " " . $certificate['last_name'];
$certificateNumber = "CERT-" . str_pad($certificate['certificate_code'], 5, "0", STR_PAD_LEFT);

function safeText($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>شهادة إتمام مهارة</title>

    <style>
        body {
            margin: 0;
            font-family: "Cairo", Arial, sans-serif;
            background: #eef3f8;
            color: #172033;
        }

        .page {
            width: min(1000px, 92%);
            margin: 40px auto;
        }

        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }

        .actions a,
        .actions button {
            border: none;
            background: #123c69;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 800;
            cursor: pointer;
            font-family: inherit;
        }

        .actions .secondary {
            background: #64748b;
        }

        .certificate {
            background: white;
            border: 10px solid #123c69;
            border-radius: 24px;
            padding: 45px;
            text-align: center;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
            position: relative;
            overflow: hidden;
        }

        .certificate::before {
            content: "";
            position: absolute;
            inset: 20px;
            border: 2px dashed #d4af37;
            border-radius: 16px;
            pointer-events: none;
        }

        .brand {
            color: #123c69;
            font-size: 26px;
            font-weight: 900;
            margin-bottom: 20px;
        }

        .title {
            font-size: 40px;
            color: #123c69;
            margin: 10px 0;
            font-weight: 900;
        }

        .subtitle {
            font-size: 19px;
            color: #475569;
            margin-bottom: 30px;
        }

        .student-name {
            font-size: 34px;
            font-weight: 900;
            color: #111827;
            margin: 22px 0;
        }

        .skill-name {
            font-size: 28px;
            font-weight: 900;
            color: #123c69;
            margin: 18px 0;
        }

        .grade {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            padding: 12px 24px;
            border-radius: 999px;
            font-weight: 900;
            margin: 20px 0;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: 45px;
            font-size: 15px;
            color: #475569;
            flex-wrap: wrap;
        }

        .signature {
            margin-top: 35px;
            color: #123c69;
            font-weight: 900;
        }

        @media print {
            body {
                background: white;
            }

            .actions {
                display: none;
            }

            .page {
                width: 100%;
                margin: 0;
            }

            .certificate {
                box-shadow: none;
                border-radius: 0;
                min-height: 90vh;
            }
        }
    </style>
</head>

<body>

<div class="page">

    <div class="actions">
        <button onclick="window.print()">طباعة / حفظ PDF</button>
        <a href="second.php" class="secondary">العودة للرئيسية</a>
    </div>

    <div class="certificate">
        <div class="brand">EduTrack Skills</div>

        <h1 class="title">شهادة إتمام مهارة</h1>

        <p class="subtitle">
            تمنح هذه الشهادة تأكيدًا على إتمام متطلبات المهارة التعليمية بنجاح
        </p>

        <p>تشهد منصة EduTrack Skills بأن الطالب/ـة</p>

        <div class="student-name">
            <?php echo safeText($studentName); ?>
        </div>

        <p>قد أتم/ـت بنجاح مهارة</p>

        <div class="skill-name">
            <?php echo safeText($certificate['skill_title']); ?>
        </div>

        <div class="grade">
            الدرجة النهائية: <?php echo safeText($certificate['final_grade']); ?>%
        </div>

        <p>
            تم تصميم هذه المهارة لدعم المتعلمين في المملكة العربية السعودية
            بمهارات رقمية وتطبيقية مرتبطة بسوق العمل.
        </p>

        <div class="signature">
            إدارة منصة EduTrack Skills
        </div>

        <div class="footer">
            <div>
                رقم الشهادة:
                <?php echo safeText($certificateNumber); ?>
            </div>

            <div>
                تاريخ الإصدار:
                <?php echo safeText($certificate['date']); ?>
            </div>
        </div>
    </div>

</div>

</body>
</html>