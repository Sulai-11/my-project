<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'project');
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    die("غير مصرح لك بإرسال طلب تعديل مهارة");
}

if (!isset($_SESSION['user_id'])) {
    die("لم يتم العثور على بيانات المعلم");
}

$teacher_id = (int)$_SESSION['user_id'];
$course_code = (int)($_GET['course'] ?? $_POST['course_code'] ?? 0);

if ($course_code <= 0) {
    die("رقم المهارة غير صحيح");
}

function safeText($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$stmt = $conn->prepare("
    SELECT *
    FROM course
    WHERE course_code = ? AND teacher_id = ?
");

$stmt->bind_param("ii", $course_code, $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("لا يمكنك تعديل مهارة لا تملكها");
}

$course = $result->fetch_assoc();
$stmt->close();

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $weeks = (int)($_POST['weeks'] ?? 0);
    $time = (int)($_POST['time'] ?? 0);
    $total_chapters = (int)($_POST['total_chapters'] ?? 0);
    $steps = trim($_POST['steps'] ?? '');

    if (
        $title === '' ||
        $description === '' ||
        $content === '' ||
        $weeks <= 0 ||
        $time <= 0 ||
        $steps === '' ||
        $total_chapters <= 0
    ) {
        $error = "يرجى تعبئة جميع الحقول بشكل صحيح";
    } elseif ($total_chapters < 1 || $total_chapters > 7) {
        $error = "عدد الوحدات المهارية يجب أن يكون بين 1 و 7";
    } else {

        $stmt = $conn->prepare("
            SELECT request_id
            FROM course_request
            WHERE teacher_id = ?
              AND course_code = ?
              AND request_type = 'update'
              AND status = 'pending'
            LIMIT 1
        ");

        $stmt->bind_param("ii", $teacher_id, $course_code);
        $stmt->execute();
        $pendingResult = $stmt->get_result();

        if ($pendingResult->num_rows > 0) {
            $error = "يوجد طلب تعديل معلق لهذه المهارة بالفعل. الرجاء انتظار موافقة المسؤول";
        }

        $stmt->close();

        if ($error === "") {
            $newImagePath = "";

            if (isset($_FILES['course_image']) && $_FILES['course_image']['error'] === 0) {

                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                $fileType = mime_content_type($_FILES['course_image']['tmp_name']);

                if (!in_array($fileType, $allowedTypes, true)) {
                    $error = "نوع الصورة غير مسموح. الرجاء رفع صورة بصيغة JPG أو PNG أو WEBP أو GIF";
                } elseif ($_FILES['course_image']['size'] > 5 * 1024 * 1024) {
                    $error = "حجم الصورة كبير جدًا. الحد الأقصى 5MB";
                } else {
                    $uploadDir = "uploads/";

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $extension = pathinfo($_FILES['course_image']['name'], PATHINFO_EXTENSION);
                    $imageName = time() . "_" . bin2hex(random_bytes(6)) . "." . $extension;
                    $targetFile = $uploadDir . $imageName;

                    if (!move_uploaded_file($_FILES['course_image']['tmp_name'], $targetFile)) {
                        $error = "فشل رفع الصورة الجديدة";
                    } else {
                        $newImagePath = $targetFile;
                    }
                }
            }
        }

        if ($error === "") {
            $payload = json_encode([
                "title" => $title,
                "description" => $description,
                "content" => $content,
                "weeks" => $weeks,
                "time" => $time,
                "total_chapters" => $total_chapters,
                "steps" => $steps,
                "course_image" => $newImagePath
            ], JSON_UNESCAPED_UNICODE);

            $stmt = $conn->prepare("
                INSERT INTO course_request
                (request_type, course_code, teacher_id, payload, status)
                VALUES ('update', ?, ?, ?, 'pending')
            ");

            $stmt->bind_param("iis", $course_code, $teacher_id, $payload);

            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();

                header("Location: second.php?request=update_sent");
                exit;
            } else {
                if ($newImagePath !== "" && file_exists($newImagePath)) {
                    unlink($newImagePath);
                }

                $error = "فشل إرسال طلب تعديل المهارة";
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طلب تعديل مهارة</title>

    <style>
        body {
            margin: 0;
            font-family: "Cairo", Arial, sans-serif;
            background: #f3f6fb;
            color: #172033;
        }

        .topbar {
            background: #123c69;
            color: white;
            padding: 18px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar h1 {
            margin: 0;
            font-size: 24px;
        }

        .topbar a {
            color: white;
            text-decoration: none;
            background: rgba(255,255,255,0.15);
            padding: 9px 16px;
            border-radius: 10px;
        }

        .page {
            width: min(950px, 92%);
            margin: 35px auto;
            background: white;
            border-radius: 22px;
            padding: 28px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
        }

        .notice {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .info {
            background: #dbeafe;
            color: #1e40af;
        }

        .current-image {
            width: 220px;
            height: 130px;
            object-fit: cover;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            margin-bottom: 15px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(240px, 1fr));
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        label {
            font-weight: 800;
            margin-bottom: 8px;
            color: #334155;
        }

        input,
        textarea {
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 12px;
            font-family: inherit;
            font-size: 15px;
            outline: none;
        }

        input:focus,
        textarea:focus {
            border-color: #123c69;
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            flex-wrap: wrap;
        }

        .submit-btn {
            background: #123c69;
            color: white;
            border: none;
            padding: 12px 22px;
            border-radius: 12px;
            font-weight: 800;
            cursor: pointer;
            font-family: inherit;
        }

        .cancel-btn {
            background: #e5e7eb;
            color: #111827;
            text-decoration: none;
            padding: 12px 22px;
            border-radius: 12px;
            font-weight: 800;
        }

        @media (max-width: 700px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .topbar {
                flex-direction: column;
                gap: 12px;
            }
        }
    </style>
</head>

<body>

<div class="topbar">
    <h1>طلب تعديل مهارة</h1>
    <a href="second.php">العودة للرئيسية</a>
</div>

<div class="page">

    <?php if ($error !== ""): ?>
        <div class="notice error"><?php echo safeText($error); ?></div>
    <?php endif; ?>

    <div class="notice info">
        ملاحظة: التعديلات لن تظهر للطلاب إلا بعد موافقة المسؤول.
    </div>

    <h2><?php echo safeText($course['title']); ?></h2>

    <?php if (!empty($course['course_image'])): ?>
        <p><strong>الصورة الحالية:</strong></p>
        <img src="<?php echo safeText($course['course_image']); ?>" class="current-image" alt="صورة المهارة الحالية">
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="course_code" value="<?php echo (int)$course['course_code']; ?>">

        <div class="form-grid">

            <div class="form-group">
                <label>اسم المهارة</label>
                <input type="text" name="title" value="<?php echo safeText($course['title']); ?>" required>
            </div>

            <div class="form-group">
                <label>صورة جديدة للمهارة اختياري</label>
                <input type="file" name="course_image" accept="image/*">
            </div>

            <div class="form-group">
                <label>عدد الأسابيع</label>
                <input type="number" name="weeks" value="<?php echo safeText($course['weeks']); ?>" min="1" required>
            </div>

            <div class="form-group">
                <label>عدد الساعات</label>
                <input type="number" name="time" value="<?php echo safeText($course['time']); ?>" min="1" required>
            </div>

            <div class="form-group">
                <label>عدد الوحدات المهارية</label>
                <input type="number" name="total_chapters" value="<?php echo safeText($course['total_chapters']); ?>" min="1" max="7" required>
            </div>

            <div class="form-group full-width">
                <label>وصف مختصر للمهارة</label>
                <textarea name="description" rows="4" required><?php echo safeText($course['description']); ?></textarea>
            </div>

            <div class="form-group full-width">
                <label>محتوى المهارة</label>
                <textarea name="content" rows="6" required><?php echo safeText($course['content']); ?></textarea>
            </div>

            <div class="form-group full-width">
                <label>ماذا سيتعلم الطالب؟</label>
                <textarea name="steps" rows="5" required><?php echo safeText($course['steps']); ?></textarea>
            </div>

        </div>

        <div class="actions">
            <button type="submit" class="submit-btn">إرسال طلب التعديل للمسؤول</button>
            <a href="second.php" class="cancel-btn">إلغاء</a>
        </div>
    </form>

</div>

</body>
</html>

<?php
$conn->close();
?>