<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'project');
$conn->set_charset("utf8mb4");

$student_id = $_SESSION['user_id'];
$course_code = (int)$_GET['course'];

$score = 0;
$total = 0;

/* جلب كل الأسئلة */
$stmt = $conn->prepare("
    SELECT question_id, correct_answer, chapter_code
    FROM question_bank
    WHERE course_code = ?
");
$stmt->bind_param("i", $course_code);
$stmt->execute();
$result = $stmt->get_result();

$wrongChapters = [];

while ($q = $result->fetch_assoc()) {
    $total++;

    $qid = $q['question_id'];
    $correct = $q['correct_answer'];
    $student_answer = $_POST["q$qid"] ?? '';

    if ($student_answer === $correct) {
        $score++;
    } else {
        if (!empty($q['chapter_code'])) {
            $wrongChapters[] = $q['chapter_code'];
        }
    }
}

$stmt->close();

/* حساب النسبة */
$percentage = ($total > 0) ? ($score / $total) * 100 : 0;

/* حساب المستوى */
$level = ceil($percentage / 10); // من 1 إلى 10

/* تخزين النتيجة */
$stmt = $conn->prepare("
    INSERT INTO exam_attempt
    (student_id, course_code, score, total_questions)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("iiii", $student_id, $course_code, $score, $total);
$stmt->execute();
$stmt->close();

/* استخراج أكثر شابتر فيه أخطاء */
$recommend = "";

if (!empty($wrongChapters)) {
    $counts = array_count_values($wrongChapters);
    arsort($counts);
    $worstChapter = array_key_first($counts);

    $recommend = "نوصي بمراجعة الفصل رقم " . $worstChapter;
} else {
    $recommend = "أداء ممتاز 🔥 استمر";
}

/* تحويل المستوى نص */
$levelText = "";
if ($level <= 3) $levelText = "مبتدئ";
elseif ($level <= 6) $levelText = "متوسط";
else $levelText = "متقدم";

?>
<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>النتيجة</title>
</head>
<body style="text-align:center; font-family:Cairo;">

<h1>🎯 نتيجتك</h1>

<h2><?php echo "$score / $total"; ?></h2>

<h3>📊 النسبة: <?php echo round($percentage); ?>%</h3>

<h3>🏆 مستواك: <?php echo $level; ?> / 10 (<?php echo $levelText; ?>)</h3>

<h3>💡 التوصية:</h3>
<p><?php echo $recommend; ?></p>

<a href="course.php?id=<?php echo $course_code; ?>">الرجوع للكورس</a>

</body>
</html>