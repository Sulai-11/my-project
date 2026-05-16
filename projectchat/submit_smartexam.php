<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    die("يجب تسجيل الدخول كطالب أولاً");
}

if (!isset($_GET['course'])) {
    die("الكورس غير موجود");
}

$course_code = (int)$_GET['course'];
$submittedAnswers = $_POST['answers'] ?? [];

if (empty($_SESSION['smart_exam_questions'][$course_code])) {
    die("انتهت جلسة الاختبار الذكي. ارجع للكورس وابدأ الاختبار من جديد.");
}

$questions = $_SESSION['smart_exam_questions'][$course_code];

$score = 0;
$total = count($questions);
$wrong = 0;
$wrongNotes = [];

foreach ($questions as $index => $question) {
    $correctIndex = (int)($question['correct_answer'] ?? -1);
    $studentAnswer = isset($submittedAnswers[$index]) ? (int)$submittedAnswers[$index] : -1;

    if ($studentAnswer === $correctIndex) {
        $score++;
    } else {
        $wrong++;
        if (!empty($question['explanation'])) {
            $wrongNotes[] = $question['explanation'];
        }
    }
}

$percentage = ($total > 0) ? round(($score / $total) * 100, 2) : 0;

if ($percentage < 50) {
    $levelText = 'مبتدئ';
    $recommendation = "تحتاج مراجعة أساسيات الكورس قبل الانتقال للتطبيقات. ركّز على المفاهيم الأولى ثم أعد الاختبار.";
} elseif ($percentage < 80) {
    $levelText = 'متوسط';
    $recommendation = "مستواك جيد، لكن تحتاج تقوية بعض النقاط التي أخطأت فيها. راجع الملاحظات ثم حاول مرة أخرى.";
} else {
    $levelText = 'متقدم';
    $recommendation = "أداؤك ممتاز. يمكنك الانتقال لتطبيقات أعمق أو حل أسئلة أصعب.";
}

if (!empty($wrongNotes)) {
    $recommendation .= "\n\nملاحظات من أخطائك:\n- " . implode("\n- ", array_slice(array_unique($wrongNotes), 0, 3));
}

$_SESSION['smart_exam_result'] = [
    'course_code' => $course_code,
    'score' => $score,
    'total' => $total,
    'wrong' => $wrong,
    'percentage' => $percentage,
    'levelText' => $levelText,
    'recommendation' => $recommendation
];

/* نحذف الأسئلة بعد التقديم حتى الاختبار القادم يتولد من جديد */
unset($_SESSION['smart_exam_questions'][$course_code]);

header("Location: course.php?id=" . urlencode($course_code));
exit;