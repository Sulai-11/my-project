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

if (!isset($_GET['course']) || $_GET['course'] === '') {
    die("الكورس غير موجود");
}

$course_code = (int)$_GET['course'];
$student_id = (int)$_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| n8n Webhook
|--------------------------------------------------------------------------
| ضع رابط Webhook الإنتاج من n8n هنا
*/
$N8N_WEBHOOK_URL = 'https://sulaiman22.app.n8n.cloud/webhook/smart-exam-generator';

/* جلب بيانات الكورس */
$stmt = $conn->prepare("
    SELECT course_code, title, description, content, steps
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

/* جلب الشابترات */
$chapters = [];
$stmt = $conn->prepare("
    SELECT number, title, content
    FROM chapter
    WHERE course_code = ?
    ORDER BY number ASC
");
$stmt->bind_param("i", $course_code);
$stmt->execute();
$chapterResult = $stmt->get_result();

while ($row = $chapterResult->fetch_assoc()) {
    $chapters[] = $row;
}
$stmt->close();
$conn->close();

/*
|--------------------------------------------------------------------------
| إنشاء أسئلة جديدة كل مرة يدخل الطالب صفحة الاختبار الذكي
|--------------------------------------------------------------------------
*/

$payload = [
    'course_code' => $course_code,
    'course_title' => $course['title'],
    'course_description' => $course['description'],
    'course_content' => $course['content'],
    'course_steps' => $course['steps'],
    'chapters' => $chapters,
    'student_id' => $student_id,
    'required_questions' => 10,
    'language' => 'Arabic',
    'specialization' => 'Internet Technologies',
    'force_new_questions' => true,
    'random_seed' => time() . '-' . rand(1000, 9999)
];

$ch = curl_init($N8N_WEBHOOK_URL);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 25);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json; charset=utf-8'
]);

$aiRaw = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$decoded = json_decode($aiRaw, true);

if (!$aiRaw || $httpCode < 200 || $httpCode >= 300) {
    echo "<pre>";
    echo "HTTP CODE: " . $httpCode . "\n\n";
    echo "RAW RESPONSE:\n";
    echo htmlspecialchars($aiRaw);
    echo "</pre>";
    exit;
}

if (!is_array($decoded)) {
    echo "<pre>";
    echo "JSON decode failed\n\n";
    echo "RAW RESPONSE:\n";
    echo htmlspecialchars($aiRaw);
    echo "</pre>";
    exit;
}

if (isset($decoded['questions']) && is_array($decoded['questions'])) {
    $questions = $decoded['questions'];
} elseif (isset($decoded['output']['questions']) && is_array($decoded['output']['questions'])) {
    $questions = $decoded['output']['questions'];
} else {
    echo "<pre>";
    echo "n8n response does not contain questions\n\n";
    echo "HTTP CODE: " . $httpCode . "\n\n";
    echo "RAW RESPONSE:\n";
    echo htmlspecialchars($aiRaw);
    echo "\n\nDECODED:\n";
    print_r($decoded);
    echo "</pre>";
    exit;
}

/*
|--------------------------------------------------------------------------
| نحفظ نسخة الأسئلة الحالية فقط عشان التصحيح بعد التقديم
|--------------------------------------------------------------------------
*/
$_SESSION['smart_exam_questions'][$course_code] = $questions;

$totalQuestions = count($questions);
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الاختبار الذكي - <?php echo htmlspecialchars($course['title']); ?></title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="stylesheet" href="exam.css">

    <style>
        .smart-layout {
            min-height: 100vh;
            padding: 28px;
            background: linear-gradient(180deg, #f5f8fc 0%, #edf3f9 100%);
        }

        .smart-shell {
            max-width: 1050px;
            margin: 0 auto;
            background: #fff;
            border-radius: 26px;
            border: 1px solid #e7eef6;
            box-shadow: 0 14px 34px rgba(17, 45, 75, 0.08);
            padding: 26px;
        }

        .smart-note {
            background: #f7fbff;
            border: 1px solid #dbe8f5;
            border-radius: 18px;
            padding: 16px 18px;
            margin-bottom: 24px;
            color: #284764;
            line-height: 1.9;
        }

        .smart-actions {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
            background: #fff;
            border: 1px solid #e6eef7;
            border-radius: 22px;
            padding: 24px;
            box-shadow: 0 8px 22px rgba(0,0,0,0.04);
        }

        .smart-actions a,
        .smart-actions button {
            min-width: 180px;
            min-height: 50px;
            border-radius: 16px;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.2s ease;
            padding: 0 18px;
        }

        .cancel-smart {
            background: #e9f1fa;
            color: #2e77c0;
        }

        .submit-smart {
            background: linear-gradient(135deg, #22a35f, #19884e);
            color: #fff;
            box-shadow: 0 10px 18px rgba(34, 163, 95, 0.18);
        }

        .smart-actions a:hover,
        .smart-actions button:hover {
            transform: translateY(-1px);
        }

        @media (max-width: 820px) {
            .smart-layout {
                padding: 16px;
            }

            .smart-shell {
                padding: 18px;
                border-radius: 22px;
            }

            .smart-actions a,
            .smart-actions button {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<div class="smart-layout">
    <main class="smart-shell">

        <section class="exam-hero">
            <div>
                <span class="hero-tag">AI Quiz</span>
                <h1>الاختبار الذكي</h1>
                <p>
                    اختبار مكوّن من 10 أسئلة تم إنشاؤها بالذكاء الاصطناعي بناءً على محتوى كورس:
                    <?php echo htmlspecialchars($course['title']); ?>
                </p>
            </div>

            <div class="hero-badge">
                Smart<br>Exam
            </div>
        </section>

        <div class="smart-note">
            أجب عن جميع الأسئلة، ثم اضغط تقديم. هذا الاختبار تدريبي ولا يؤثر على فتح الشابترات أو النهائي.
        </div>

        <form method="POST" action="submit_smartexam.php?course=<?php echo urlencode($course_code); ?>">

            <?php foreach ($questions as $index => $question): ?>
                <?php
                    $qText = $question['question'] ?? '';
                    $options = $question['options'] ?? [];
                ?>

                <section class="question-card" id="ex<?php echo $index + 1; ?>">
                    <div class="question-head">
                        <div class="question-no"><?php echo $index + 1; ?></div>
                        <div class="question-text-wrap">
                            <h3><?php echo htmlspecialchars($qText); ?></h3>
                            <span>اختر الإجابة الصحيحة</span>
                        </div>
                    </div>

                    <div class="options-grid form-q" data-question="<?php echo $index + 1; ?>">
                        <?php foreach ($options as $optIndex => $option): ?>
                            <label class="option-card">
                                <input
                                    type="radio"
                                    name="answers[<?php echo $index; ?>]"
                                    value="<?php echo htmlspecialchars($optIndex); ?>"
                                    class="check"
                                    required
                                >
                                <span class="Q-text"><?php echo htmlspecialchars($option); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>

            <div class="smart-actions">
                <a class="cancel-smart" href="course.php?id=<?php echo urlencode($course_code); ?>">
                    إلغاء
                </a>

                <button class="submit-smart" type="submit">
                    تقديم
                </button>
            </div>

        </form>
    </main>
</div>

<script>
document.querySelectorAll('.form-q').forEach(form => {
    form.addEventListener('change', function(e) {
        const selectedCard = e.target.closest('.option-card');

        if (selectedCard) {
            form.querySelectorAll('.option-card').forEach(card => card.classList.remove('selected'));
            selectedCard.classList.add('selected');
        }
    });
});
</script>

</body>
</html>