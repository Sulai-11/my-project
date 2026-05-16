<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'project');
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

if (
    !isset($_SESSION['role'], $_SESSION['user_id']) ||
    !in_array($_SESSION['role'], ['teacher', 'admin'], true)
) {
    die("غير مصرح لك بإضافة مهارة");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: second.php");
    exit;
}

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
    die("يرجى تعبئة جميع الحقول بشكل صحيح");
}

if ($total_chapters < 1 || $total_chapters > 7) {
    die("عدد الوحدات المهارية يجب أن يكون بين 1 و 7");
}

/*
|--------------------------------------------------------------------------
| التحقق من الصورة
|--------------------------------------------------------------------------
*/

if (!isset($_FILES['course_image']) || $_FILES['course_image']['error'] !== 0) {
    die("حدث خطأ أثناء رفع صورة المهارة");
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$fileType = mime_content_type($_FILES['course_image']['tmp_name']);

if (!in_array($fileType, $allowedTypes, true)) {
    die("نوع الصورة غير مسموح. الرجاء رفع صورة بصيغة JPG أو PNG أو WEBP أو GIF");
}

$maxSize = 5 * 1024 * 1024;

if ($_FILES['course_image']['size'] > $maxSize) {
    die("حجم الصورة كبير جدًا. الحد الأقصى 5MB");
}

$uploadDir = "uploads/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$extension = strtolower(pathinfo($_FILES['course_image']['name'], PATHINFO_EXTENSION));
$imageName = time() . "_" . bin2hex(random_bytes(6)) . "." . $extension;
$targetFile = $uploadDir . $imageName;

if (!move_uploaded_file($_FILES['course_image']['tmp_name'], $targetFile)) {
    die("فشل رفع الصورة");
}

/*
|--------------------------------------------------------------------------
| إذا كان المستخدم Admin: إنشاء المهارة مباشرة بدون طلب
|--------------------------------------------------------------------------
*/

if ($_SESSION['role'] === 'admin') {

    /*
    مهم:
    جدول course عندك يحتاج teacher_id.
    لذلك نربط المهارة بأول معلم موجود في قاعدة البيانات.
    */

    $teacher_id = null;

    $teacherStmt = $conn->prepare("SELECT teacher_id FROM teacher ORDER BY teacher_id ASC LIMIT 1");
    $teacherStmt->execute();
    $teacherResult = $teacherStmt->get_result();

    if ($teacherRow = $teacherResult->fetch_assoc()) {
        $teacher_id = (int)$teacherRow['teacher_id'];
    }

    $teacherStmt->close();

    if ($teacher_id === null) {
        if (file_exists($targetFile)) {
            unlink($targetFile);
        }

        die("لا يوجد معلم في قاعدة البيانات لربط المهارة به");
    }

    $stmt = $conn->prepare("
        INSERT INTO course
        (title, description, time, weeks, content, steps, teacher_id, course_image, total_chapters)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssiissisi",
        $title,
        $description,
        $time,
        $weeks,
        $content,
        $steps,
        $teacher_id,
        $targetFile,
        $total_chapters
    );

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();

        header("Location: second.php?request=admin_created");
        exit;
    } else {
        if (file_exists($targetFile)) {
            unlink($targetFile);
        }

        die("فشل إنشاء المهارة مباشرة بواسطة المسؤول");
    }
}

/*
|--------------------------------------------------------------------------
| إذا كان المستخدم Teacher: إرسال طلب إضافة للـ Admin
|--------------------------------------------------------------------------
*/

$teacher_id = (int)$_SESSION['user_id'];

$checkStmt = $conn->prepare("
    SELECT request_id
    FROM course_request
    WHERE teacher_id = ?
      AND request_type = 'add'
      AND status = 'pending'
      AND JSON_UNQUOTE(JSON_EXTRACT(payload, '$.title')) = ?
    LIMIT 1
");

$checkStmt->bind_param("is", $teacher_id, $title);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    if (file_exists($targetFile)) {
        unlink($targetFile);
    }

    die("يوجد طلب إضافة معلق لنفس المهارة. الرجاء انتظار موافقة المسؤول");
}

$checkStmt->close();

$payload = json_encode([
    "title" => $title,
    "description" => $description,
    "content" => $content,
    "weeks" => $weeks,
    "time" => $time,
    "total_chapters" => $total_chapters,
    "steps" => $steps,
    "course_image" => $targetFile
], JSON_UNESCAPED_UNICODE);

$stmt = $conn->prepare("
    INSERT INTO course_request
    (request_type, course_code, teacher_id, payload, status)
    VALUES ('add', NULL, ?, ?, 'pending')
");

$stmt->bind_param("is", $teacher_id, $payload);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();

    header("Location: second.php?request=add_sent");
    exit;
} else {
    if (file_exists($targetFile)) {
        unlink($targetFile);
    }

    echo "فشل إرسال طلب إضافة المهارة";
}

$stmt->close();
$conn->close();
?>