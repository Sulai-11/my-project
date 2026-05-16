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

if (!isset($_GET['course'])) {
    die("الكورس غير موجود");
}

$student_id = (int)$_SESSION['user_id'];
$course_code = (int)$_GET['course'];
$submittedAnswers = $_POST['answers'] ?? [];

/*
|--------------------------------------------------------------------------
| إعدادات AI
|--------------------------------------------------------------------------
| ضع رابط ويبهوك خاص بالتوصيات من n8n هنا
| إذا تركتيه فارغًا، سيعمل النظام المحلي فقط
*/
$AI_WEBHOOK_URL = 'https://sulaiman22.app.n8n.cloud/webhook/exam-recommendation'; 

$score = 0;
$total = 0;
$wrong = 0;

$wrongChapters = [];
$chapterStats = [];
$wrongExplanations = [];
$answerRows = [];

/* جلب بيانات الكورس */
$stmt = $conn->prepare("
    SELECT course_code, title, description
    FROM course
    WHERE course_code = ?
    LIMIT 1
");
$stmt->bind_param("i", $course_code);
$stmt->execute();
$courseResult = $stmt->get_result();
$course = $courseResult->fetch_assoc();
$stmt->close();

if (!$course) {
    die("الكورس غير موجود");
}

/*
|--------------------------------------------------------------------------
| جلب أسئلة النهائي فقط + اسم الشابتر + التفسير
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT 
        qb.question_id,
        qb.question_text,
        qb.correct_answer,
        qb.chapter_code,
        qb.explanation,
        c.number AS chapter_number,
        c.title AS chapter_title
    FROM question_bank qb
    LEFT JOIN chapter c ON c.chapter_code = qb.chapter_code
    WHERE qb.course_code = ?
      AND qb.question_scope = 'end'
      AND qb.is_active = 1
    ORDER BY qb.question_id ASC
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

    $chapter_code = !empty($q['chapter_code']) ? (int)$q['chapter_code'] : 0;
    $chapter_number = !empty($q['chapter_number']) ? (int)$q['chapter_number'] : 0;
    $chapter_title = trim((string)($q['chapter_title'] ?? ''));

    if ($chapter_code > 0 && !isset($chapterStats[$chapter_code])) {
        $chapterStats[$chapter_code] = [
            'chapter_code' => $chapter_code,
            'chapter_number' => $chapter_number,
            'chapter_title' => $chapter_title !== '' ? $chapter_title : ('Chapter ' . $chapter_number),
            'total' => 0,
            'correct' => 0,
            'wrong' => 0,
        ];
    }

    if ($chapter_code > 0) {
        $chapterStats[$chapter_code]['total']++;
    }

    if ($isCorrect) {
        $score++;
        if ($chapter_code > 0) {
            $chapterStats[$chapter_code]['correct']++;
        }
    } else {
        $wrong++;

        if ($chapter_code > 0) {
            $wrongChapters[] = $chapter_code;
            $chapterStats[$chapter_code]['wrong']++;
        }

        if (!empty($q['explanation'])) {
            $wrongExplanations[] = trim($q['explanation']);
        }
    }

    $answerRows[] = [
        'question_id' => $qid,
        'selected_answer' => $student_answer,
        'is_correct' => $isCorrect,
    ];
}
$stmt->close();

$percentage = ($total > 0) ? round(($score / $total) * 100, 2) : 0;

/*
|--------------------------------------------------------------------------
| تحديد المستوى
|--------------------------------------------------------------------------
*/
if ($percentage < 50) {
    $levelText = 'مبتدئ';
} elseif ($percentage < 80) {
    $levelText = 'متوسط';
} else {
    $levelText = 'متقدم';
}

/*
|--------------------------------------------------------------------------
| تحديث student.completed
|--------------------------------------------------------------------------
| نزيدها بعدد الصحيح في النهائي
*/
if ($score > 0) {
    $stmt = $conn->prepare("
        UPDATE student
        SET completed = completed + ?
        WHERE student_id = ?
    ");
    $stmt->bind_param("ii", $score, $student_id);
    $stmt->execute();
    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| تحديث مستوى الطالب من جدول level إذا وجد
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT level
    FROM level
    WHERE minimum <= ? AND maximum >= ?
    ORDER BY level ASC
    LIMIT 1
");
$stmt->bind_param("ii", $score, $score);
$stmt->execute();
$levelResult = $stmt->get_result();
if ($row = $levelResult->fetch_assoc()) {
    $dbLevel = (int)$row['level'];

    $stmt2 = $conn->prepare("
        UPDATE student
        SET level = ?
        WHERE student_id = ?
    ");
    $stmt2->bind_param("ii", $dbLevel, $student_id);
    $stmt2->execute();
    $stmt2->close();

    $_SESSION['level'] = $dbLevel;
}
$stmt->close();

/*
|--------------------------------------------------------------------------
| بناء توصيات محلية احترافية
|--------------------------------------------------------------------------
*/
$chapterRecommendations = [];
$topWeakChapters = [];

if (!empty($chapterStats)) {
    usort($chapterStats, function ($a, $b) {
        if ($a['wrong'] === $b['wrong']) {
            return $a['chapter_number'] <=> $b['chapter_number'];
        }
        return $b['wrong'] <=> $a['wrong'];
    });

    foreach ($chapterStats as $ch) {
        if ($ch['wrong'] > 0) {
            $topWeakChapters[] = $ch;
        }
    }

    $topWeakChapters = array_slice($topWeakChapters, 0, 3);
}

foreach ($topWeakChapters as $ch) {
    $chapterRecommendations[] = "راجع {$ch['chapter_title']} لأن لديك {$ch['wrong']} خطأ من {$ch['total']} أسئلة مرتبطة به.";
}

$studyPlan = [];
if ($percentage < 50) {
    $studyPlan[] = "ابدأ بمراجعة الأساسيات أولًا ثم أعد الاختبار بعد إنهاء الشابترات الضعيفة.";
    $studyPlan[] = "ركز على الفهم وليس الحفظ، وحاول حل أمثلة قصيرة بعد كل شابتر.";
} elseif ($percentage < 80) {
    $studyPlan[] = "مستواك جيد، لكن تحتاج تقوية النقاط التي أخطأت فيها أكثر من مرة.";
    $studyPlan[] = "أعد مشاهدة الفيديوهات الخاصة بالشابترات الأضعف ثم حل اختباراتها مرة أخرى.";
} else {
    $studyPlan[] = "أداؤك ممتاز، ومستواك يسمح بالانتقال إلى محتوى أكثر تقدمًا.";
    $studyPlan[] = "حافظ على مستواك بمراجعة سريعة للشابترات التي ظهرت فيها أخطاء بسيطة.";
}

$skillHints = [];
foreach (array_slice(array_unique($wrongExplanations), 0, 3) as $exp) {
    $skillHints[] = $exp;
}

/*
|--------------------------------------------------------------------------
| اقتراح كورسات إضافية
|--------------------------------------------------------------------------
*/
$courseSuggestions = [];
$stmt = $conn->prepare("
    SELECT course_code, title
    FROM course
    WHERE course_code <> ?
    ORDER BY RAND()
    LIMIT 2
");
$stmt->bind_param("i", $course_code);
$stmt->execute();
$extraCourses = $stmt->get_result();

while ($row = $extraCourses->fetch_assoc()) {
    $courseSuggestions[] = [
        'course_code' => (int)$row['course_code'],
        'title' => $row['title']
    ];
}
$stmt->close();

/*
|--------------------------------------------------------------------------
| Recommendation محلي نصي
|--------------------------------------------------------------------------
*/
$localRecommendationParts = [];

if (!empty($chapterRecommendations)) {
    $localRecommendationParts[] = "الشابترات الأضعف: " . implode(' | ', $chapterRecommendations);
}

if (!empty($studyPlan)) {
    $localRecommendationParts[] = "الخطة: " . implode(' | ', $studyPlan);
}

if (!empty($skillHints)) {
    $localRecommendationParts[] = "ملاحظات تعليمية: " . implode(' | ', $skillHints);
}

if (!empty($courseSuggestions)) {
    $titles = array_map(fn($c) => $c['title'], $courseSuggestions);
    $localRecommendationParts[] = "كورسات مقترحة: " . implode(' | ', $titles);
}

if (empty($localRecommendationParts)) {
    $localRecommendation = "لا توجد بيانات كافية لبناء توصية مفصلة حاليًا.";
} else {
    $localRecommendation = implode("\n\n", $localRecommendationParts);
}

/*
|--------------------------------------------------------------------------
| AI عبر n8n (اختياري)
|--------------------------------------------------------------------------
*/
$aiRecommendation = null;

if (!empty($AI_WEBHOOK_URL)) {
    $aiPayload = [
        'student_id' => $student_id,
        'course_code' => $course_code,
        'course_title' => $course['title'] ?? '',
        'score' => $score,
        'total' => $total,
        'percentage' => $percentage,
        'level_text' => $levelText,
        'weak_chapters' => $topWeakChapters,
        'study_plan' => $studyPlan,
        'skill_hints' => $skillHints,
        'course_suggestions' => $courseSuggestions,
        'fallback_recommendation' => $localRecommendation,
    ];

    $ch = curl_init($AI_WEBHOOK_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($aiPayload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json; charset=utf-8'
    ]);

    $aiRaw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($aiRaw && $httpCode >= 200 && $httpCode < 300) {
        $decoded = json_decode($aiRaw, true);

        if (is_array($decoded) && !empty($decoded['recommendation'])) {
            $aiRecommendation = trim((string)$decoded['recommendation']);
        } elseif (is_string($aiRaw) && trim($aiRaw) !== '') {
            $aiRecommendation = trim($aiRaw);
        }
    }
}

$finalRecommendation = $aiRecommendation ?: $localRecommendation;

/*
|--------------------------------------------------------------------------
| حفظ المحاولة
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    INSERT INTO exam_attempt
    (student_id, course_code, chapter_code, exam_type, total_questions, correct_count, wrong_count, score, recommendation, status, submitted_at)
    VALUES (?, ?, NULL, 'end', ?, ?, ?, ?, ?, 'submitted', NOW())
");
$stmt->bind_param("iiiiiis", $student_id, $course_code, $total, $score, $wrong, $score, $finalRecommendation);
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

/*
|--------------------------------------------------------------------------
| نخزن نتيجة منظمة لاستخدامها في endexam.php
|--------------------------------------------------------------------------
*/
$_SESSION['end_exam_result'] = [
    'course_code' => $course_code,
    'score' => $score,
    'total' => $total,
    'percentage' => $percentage,
    'levelText' => $levelText,
    'recommendation' => $finalRecommendation,
    'local_recommendation' => $localRecommendation,
    'ai_recommendation' => $aiRecommendation,
    'weak_chapters' => $topWeakChapters,
    'study_plan' => $studyPlan,
    'skill_hints' => $skillHints,
    'course_suggestions' => $courseSuggestions,
];

header("Location: endexam.php?course=" . urlencode($course_code));
exit;
?>