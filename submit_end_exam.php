<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'project');
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

if (!isset($_GET['course'])) {
    die("الكورس غير موجود");
}

$student_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$course_code = (int)$_GET['course'];
$submittedAnswers = $_POST['answers'] ?? [];

$score = 0;
$total = 0;
$wrong = 0;
$wrongChapters = [];
$answerRows = [];

/* جلب أسئلة النهائي فقط */
$stmt = $conn->prepare("
    SELECT question_id, correct_answer, chapter_code
    FROM question_bank
    WHERE course_code = ?
      AND question_scope = 'end'
      AND is_active = 1
    ORDER BY question_id ASC
    LIMIT 30
");
$stmt->bind_param("i", $course_code);
$stmt->execute();
$result = $stmt->get_result();

while ($q = $result->fetch_assoc()) {
    $total++;

    $qid = (int)$q['question_id'];
    $correct = trim((string)$q['correct_answer']);
    $student_answer = trim((string)($submittedAnswers[$qid] ?? ''));
    $isCorrect = ($student_answer !== '' && $student_answer === $correct) ? 1 : 0;

    if ($isCorrect) {
        $score++;
    } else {
        $wrong++;
        if (!empty($q['chapter_code'])) {
            $wrongChapters[] = (int)$q['chapter_code'];
        }
    }

    $answerRows[] = [
        'question_id' => $qid,
        'selected_answer' => $student_answer,
        'is_correct' => $isCorrect,
    ];
}
$stmt->close();

$percentage = ($total > 0) ? (($score / $total) * 100) : 0;
$level = max(1, (int)ceil($percentage / 10));

if ($percentage < 50) {
    $levelText = 'مبتدئ';
} elseif ($percentage < 80) {
    $levelText = 'متوسط';
} else {
    $levelText = 'متقدم';
}

if (!empty($wrongChapters)) {
    $counts = array_count_values($wrongChapters);
    arsort($counts);
    $worstChapter = (int)array_key_first($counts);
    $recommend = "ننصحك تراجع الشابتر رقم {$worstChapter} لأنه الأكثر أخطاء في النهائي.";
} elseif ($total > 0) {
    $recommend = "أداء ممتاز جدًا، كمل على هذا المستوى.";
} else {
    $recommend = "لا توجد أسئلة نهائية مفعلة لهذا الكورس حاليًا.";
}

/* حفظ المحاولة */
$stmt = $conn->prepare("
    INSERT INTO exam_attempt
    (student_id, course_code, chapter_code, exam_type, total_questions, correct_count, wrong_count, score, recommendation, status, submitted_at)
    VALUES (?, ?, NULL, 'end', ?, ?, ?, ?, ?, 'submitted', NOW())
");
$stmt->bind_param("iiiiiis", $student_id, $course_code, $total, $score, $wrong, $score, $recommend);
$stmt->execute();
$attempt_id = $stmt->insert_id;
$stmt->close();

if ($attempt_id > 0 && !empty($answerRows)) {
    $stmtAns = $conn->prepare("
        INSERT INTO exam_attempt_answer (attempt_id, question_id, selected_answer, is_correct)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($answerRows as $row) {
        $question_id = (int)$row['question_id'];
        $selected_answer = $row['selected_answer'];
        $is_correct = (int)$row['is_correct'];
        $stmtAns->bind_param("iisi", $attempt_id, $question_id, $selected_answer, $is_correct);
        $stmtAns->execute();
    }

    $stmtAns->close();
}

$conn->close();

$_SESSION['end_exam_result'] = [
    'course_code' => $course_code,
    'score' => $score,
    'total' => $total,
    'percentage' => $percentage,
    'level' => $level,
    'levelText' => $levelText,
    'recommendation' => $recommend,
];

header("Location: endexam.php?course=" . urlencode($course_code));
exit;