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

function syncStudentLevel($conn, $student_id) {
    $student_id = (int)$student_id;
    $completed = 0;
    $currentLevel = 1;

    $stmt = $conn->prepare("SELECT completed, level FROM student WHERE student_id = ? LIMIT 1");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $completed = (int)($row['completed'] ?? 0);
        $currentLevel = (int)($row['level'] ?? 1);
    }
    $stmt->close();

    $newLevel = 0;
    $stmt = $conn->prepare("SELECT level FROM level WHERE ? BETWEEN minimum AND maximum ORDER BY level DESC LIMIT 1");
    $stmt->bind_param("i", $completed);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $newLevel = (int)$row['level'];
    }
    $stmt->close();

    if ($newLevel === 0) {
        $stmt = $conn->prepare("SELECT level FROM level WHERE maximum <= ? ORDER BY level DESC LIMIT 1");
        $stmt->bind_param("i", $completed);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $newLevel = (int)$row['level'];
        }
        $stmt->close();
    }

    if ($newLevel === 0) {
        $result = $conn->query("SELECT level FROM level ORDER BY level ASC LIMIT 1");
        if ($result && $row = $result->fetch_assoc()) {
            $newLevel = (int)$row['level'];
        }
    }

    if ($newLevel <= 0) {
        $newLevel = $currentLevel > 0 ? $currentLevel : 1;
    }

    if ($newLevel !== $currentLevel) {
        $stmt = $conn->prepare("UPDATE student SET level = ? WHERE student_id = ?");
        $stmt->bind_param("ii", $newLevel, $student_id);
        $stmt->execute();
        $stmt->close();
    }

    $_SESSION['level'] = $newLevel;
    $_SESSION['completed'] = $completed;
}

$student_id = (int)$_SESSION['user_id'];
$course_code = (int)($_POST['course_code'] ?? 0);
$chapter_code = (int)($_POST['chapter_code'] ?? 0);
$chapter_number = (int)($_POST['chapter_number'] ?? 0);
$answers = $_POST['answers'] ?? [];

if ($course_code <= 0 || $chapter_code <= 0 || $chapter_number <= 0) {
    die("بيانات الاختبار غير صحيحة");
}

$stmt = $conn->prepare("SELECT total_chapters FROM course WHERE course_code = ?");
$stmt->bind_param("i", $course_code);
$stmt->execute();
$courseResult = $stmt->get_result();

if ($courseResult->num_rows !== 1) {
    die("الكورس غير موجود");
}

$course = $courseResult->fetch_assoc();
$total_chapters = (int)$course['total_chapters'];
$stmt->close();

$stmt = $conn->prepare("
    INSERT INTO exam_attempt (student_id, course_code, chapter_code, exam_type, total_questions, correct_count, wrong_count, score, status)
    VALUES (?, ?, ?, 'chapter', 0, 0, 0, 0, 'submitted')
");
$stmt->bind_param("iii", $student_id, $course_code, $chapter_code);
$stmt->execute();
$attempt_id = $stmt->insert_id;
$stmt->close();

$total_questions = 0;
$correct_count = 0;
$wrong_count = 0;
$wrong_topics = [];

foreach ($answers as $question_id => $selected_answer) {
    $question_id = (int)$question_id;
    $selected_answer = trim($selected_answer);

    $stmt = $conn->prepare("SELECT question_text, correct_answer, explanation FROM question_bank WHERE question_id = ?");
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
            if (!empty($row['explanation'])) {
                $wrong_topics[] = $row['explanation'];
            } else {
                $wrong_topics[] = 'راجع السؤال: ' . $row['question_text'];
            }
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
$recommendation = '';
if ($wrong_count === 0) {
    $recommendation = 'ممتاز جدًا، أداؤك قوي في هذا الشابتر.';
} else {
    $recommendation = "ينصح بمراجعة:
- " . implode("
- ", array_unique($wrong_topics));
}

$stmt = $conn->prepare("
    UPDATE exam_attempt
    SET total_questions = ?, correct_count = ?, wrong_count = ?, score = ?, recommendation = ?, submitted_at = NOW()
    WHERE attempt_id = ?
");
$stmt->bind_param("iiiisi", $total_questions, $correct_count, $wrong_count, $score, $recommendation, $attempt_id);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare("UPDATE student SET completed = completed + ? WHERE student_id = ?");
$stmt->bind_param("ii", $correct_count, $student_id);
$stmt->execute();
$stmt->close();

syncStudentLevel($conn, $student_id);

$conn->close();

if ($chapter_number < $total_chapters) {
    header("Location: chapter.php?course=" . urlencode($course_code) . "&chapter=" . urlencode($chapter_number + 1));
} else {
    header("Location: endexam.php?course=" . urlencode($course_code));
}
exit;
?>