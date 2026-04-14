<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'test');
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    die("غير مصرح لك");
}

if (!isset($_SESSION['user_id'])) {
    die("لم يتم العثور على بيانات المعلم");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $content = $_POST['content'] ?? '';
    $weeks = $_POST['weeks'] ?? 0;
    $time = $_POST['time'] ?? 0;
    $steps = $_POST['steps'] ?? '';
    $teacher_id = $_SESSION['user_id'];

    if (
        empty($title) || empty($description) || empty($content) ||
        empty($weeks) || empty($time) || empty($steps)
    ) {
        die("يرجى تعبئة جميع الحقول");
    }

    if (!isset($_FILES['course_image']) || $_FILES['course_image']['error'] !== 0) {
        die("حدث خطأ أثناء رفع الصورة");
    }

    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $imageName = time() . "_" . basename($_FILES['course_image']['name']);
    $targetFile = $uploadDir . $imageName;

    if (move_uploaded_file($_FILES['course_image']['tmp_name'], $targetFile)) {
        $stmt = $conn->prepare("
            INSERT INTO course (title, description, teacher_id, course_image, steps, content, weeks, time)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssisssii", $title, $description, $teacher_id, $targetFile, $steps, $content, $weeks, $time);

        if ($stmt->execute()) {
            header("Location: second.php");
            exit;
        } else {
            echo "فشل حفظ الكورس";
        }

        $stmt->close();
    } else {
        echo "فشل رفع الصورة";
    }
}

$conn->close();
?>