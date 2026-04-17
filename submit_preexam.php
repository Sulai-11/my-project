<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'project');
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    die("يجب تسجيل الدخول كطالب أولاً");
}

$student_id = (int)$_SESSION['user_id'];
$course_code = (int)($_POST['course_code'] ?? 0);
$answers = $_POST['answers'] ?? [];

if ($course_code <= 0) {
    die("بيانات الاختبار غير صحيحة");
}

/* إنشاء محاولة */
$stmt = $conn->prepare("
    INSERT INTO exam_attempt (student_id, course_code, chapter_code, exam_type, total_questions, correct_count, wrong_count, score, status)
    VALUES (?, ?, NULL, 'pre', 0, 0, 0, 0, 'submitted')
");
$stmt->bind_param("ii", $student_id, $course_code);
$stmt->execute();
$attempt_id = $stmt->insert_id;
$stmt->close();

$total_questions = 0;
$correct_count = 0;
$wrong_count = 0;

/* تصحيح الإجابات */
foreach ($answers as $question_id => $selected_answer) {
    $question_id = (int)$question_id;
    $selected_answer = trim($selected_answer);

    $stmt = $conn->prepare("SELECT correct_answer FROM question_bank WHERE question_id = ?");
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        $total_questions++;
        $is_correct = ($selected_answer === $row['correct_answer']) ? 1 : 0;

        if ($is_correct) {
            $correct_count++;
        } else {
            $wrong_count++;
        }

        $stmtAns = $conn->prepare("
            INSERT INTO exam_attempt_answer (attempt_id, question_id, selected_answer, is_correct)
            VALUES (?, ?, ?, ?)
        ");
        $stmtAns->bind_param("iisi", $attempt_id, $question_id, $selected_answer, $is_correct);
        $stmtAns->execute();
        $stmtAns->close();
    }

    $stmt->close();
}

$score = $correct_count;

/* تحديث المحاولة */
$stmt = $conn->prepare("
    UPDATE exam_attempt
    SET total_questions = ?, correct_count = ?, wrong_count = ?, score = ?, submitted_at = NOW()
    WHERE attempt_id = ?
");
$stmt->bind_param("iiiii", $total_questions, $correct_count, $wrong_count, $score, $attempt_id);
$stmt->execute();
$stmt->close();

/* زيادة نقاط الطالب */
$stmt = $conn->prepare("UPDATE student SET completed = completed + ? WHERE student_id = ?");
$stmt->bind_param("ii", $correct_count, $student_id);
$stmt->execute();
$stmt->close();

$conn->close();

/* بعد الـ pre exam ندخله الشابتر الأول */
header("Location: chapter.php?course=" . urlencode($course_code) . "&chapter=1");
exit;
?>