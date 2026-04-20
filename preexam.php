<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'project');
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

if (!isset($_GET['course']) || $_GET['course'] === '') {
    die("رقم الكورس غير موجود");
}

$course_code = (int) $_GET['course'];
$isTeacher = isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
$student_id = (!$isTeacher && isset($_SESSION['user_id'])) ? (int)$_SESSION['user_id'] : 0;

$stmt = $conn->prepare("SELECT course_code, title, total_chapters FROM course WHERE course_code = ?");
$stmt->bind_param("i", $course_code);
$stmt->execute();
$courseResult = $stmt->get_result();

if ($courseResult->num_rows !== 1) {
    die("الكورس غير موجود");
}

$course = $courseResult->fetch_assoc();
$stmt->close();

$total_chapters = (int)($course['total_chapters'] ?? 1);
$total_chapters = max(1, min(7, $total_chapters));

$chapters = [];
$stmt = $conn->prepare("SELECT chapter_code, number, title FROM chapter WHERE course_code = ? ORDER BY number ASC");
$stmt->bind_param("i", $course_code);
$stmt->execute();
$chapterResult = $stmt->get_result();
while ($row = $chapterResult->fetch_assoc()) {
    $chapters[] = [
        'chapter_code' => (int)$row['chapter_code'],
        'number' => (int)$row['number'],
        'title' => $row['title']
    ];
}
$stmt->close();

$questions = [];
$stmt = $conn->prepare("
    SELECT question_id, question_text, question_type
    FROM question_bank
    WHERE course_code = ? AND question_scope = 'pre' AND is_active = 1
    ORDER BY question_id ASC
");
$stmt->bind_param("i", $course_code);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $row['options'] = [];

    $stmtOpt = $conn->prepare("
        SELECT option_label, option_text
        FROM question_option
        WHERE question_id = ?
        ORDER BY option_id ASC
    ");
    $stmtOpt->bind_param("i", $row['question_id']);
    $stmtOpt->execute();
    $optResult = $stmtOpt->get_result();

    while ($opt = $optResult->fetch_assoc()) {
        $row['options'][] = $opt;
    }

    $stmtOpt->close();
    $questions[] = $row;
}
$stmt->close();

$preDone = false;
if ($student_id > 0) {
    $stmt = $conn->prepare("
        SELECT attempt_id
        FROM exam_attempt
        WHERE student_id = ? AND course_code = ? AND exam_type = 'pre' AND status = 'submitted'
        ORDER BY attempt_id DESC
        LIMIT 1
    ");
    $stmt->bind_param("ii", $student_id, $course_code);
    $stmt->execute();
    $preResult = $stmt->get_result();
    $preDone = $preResult->num_rows > 0;
    $stmt->close();
}

$totalQuestions = count($questions);
$canOpenChapters = $isTeacher || $student_id <= 0 || $preDone;

$conn->close();
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الاختبار التمهيدي - <?php echo htmlspecialchars($course['title']); ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="exam.css">
</head>
<body>

<div class="exam-layout">
    <aside class="sidebar-box">
        <div class="sidebar-head">
            <h3>التنقلات</h3>
            <span class="mini-chip">قبل البدء</span>
        </div>

        <div class="nav-section-label">روابط سريعة</div>
        <a class="nav-item top-link" href="second.php">
            <span>الصفحة الرئيسية</span>
            <span class="material-symbols-outlined nav-icon">home</span>
        </a>
        <a class="nav-item top-link" href="course.php?id=<?php echo urlencode($course_code); ?>">
            <span>صفحة الكورس</span>
            <span class="material-symbols-outlined nav-icon">menu_book</span>
        </a>

        <div class="nav-divider"></div>
        <div class="nav-section-label">بداية المسار</div>

        <div class="nav-group current-group">
            <a class="nav-item quiz-link active" href="preexam.php?course=<?php echo urlencode($course_code); ?>">
                <span class="nav-main-text">
                    <small>الاختبار التمهيدي</small>
                    <strong><?php echo htmlspecialchars($course['title']); ?></strong>
                </span>
                <span class="nav-status-pill current-pill">الحالي</span>
            </a>
        </div>

        <div class="nav-divider"></div>
        <div class="nav-section-label">ما بعد الاختبار</div>

        <?php if (empty($chapters)): ?>
            <div class="lock-alert">
                <span class="material-symbols-outlined">info</span>
                <div>
                    <strong>لا توجد شابترات حاليًا</strong>
                    <p>أضف شابترات لهذا الكورس حتى يبدأ الطالب التعلم بعد الاختبار التمهيدي.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($chapters as $item): ?>
                <?php $chapterHref = 'chapter.php?course=' . urlencode($course_code) . '&chapter=' . urlencode($item['number']); ?>
                <div class="nav-group <?php echo ((int)$item['number'] === 1) ? 'current-group' : ''; ?>">
                    <?php if ($canOpenChapters): ?>
                        <a class="nav-item chapter-link" href="<?php echo $chapterHref; ?>">
                            <span class="nav-main-text">
                                <small>شابتر <?php echo (int)$item['number']; ?></small>
                                <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                            </span>
                            <span class="nav-status-pill <?php echo ((int)$item['number'] === 1) ? 'open-pill' : 'locked-pill'; ?>">
                                <?php echo ((int)$item['number'] === 1) ? 'الخطوة التالية' : 'لاحقًا'; ?>
                            </span>
                        </a>
                    <?php else: ?>
                        <div class="nav-item chapter-link locked-item">
                            <span class="nav-main-text">
                                <small>شابتر <?php echo (int)$item['number']; ?></small>
                                <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                            </span>
                            <span class="nav-status-pill locked-pill">أكمل التمهيدي أولًا</span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </aside>

    <main class="exam-shell">
        <section class="exam-hero">
            <div>
                <div class="hero-tag">Pre Exam</div>
                <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                <p>اختبار تمهيدي بسيط يحدد مستوى الطالب قبل الدخول في الشابترات. بعد التقديم يبدأ المسار التعليمي مباشرة.</p>
            </div>
            <div class="hero-badge"><?php echo $totalQuestions; ?><br>أسئلة</div>
        </section>

        <div class="progress-banner <?php echo $preDone ? 'success-state' : ''; ?>">
            <div>
                <strong><?php echo $preDone ? 'تم تسجيل اختبار تمهيدي سابق لهذا الكورس.' : 'أكمل هذا الاختبار ليبدأ مسار الشابترات.'; ?></strong>
                <p><?php echo $preDone ? 'يمكنك إعادة المحاولة عند الحاجة، لكن المسار عندك صار مفتوحًا بالفعل.' : 'هذا التصميم الآن صار متناسقًا مع صفحات الاختبارات الجديدة في المشروع.'; ?></p>
            </div>
            <span class="progress-badge"><?php echo $totalQuestions; ?> / <?php echo $totalQuestions; ?></span>
        </div>

        <?php if ($totalQuestions === 0): ?>
            <div class="empty-card">
                <span class="material-symbols-outlined">quiz</span>
                <h2>لا توجد أسئلة تمهيدية لهذا الكورس حاليًا</h2>
                <p>يمكنك الرجوع لصفحة الكورس أو بدء الشابتر الأول مباشرة إذا كان المسار جاهزًا.</p>
                <div class="action-row">
                    <a class="action-btn secondary-btn" href="course.php?id=<?php echo urlencode($course_code); ?>">رجوع للكورس</a>
                    <a class="action-btn primary-btn" href="chapter.php?course=<?php echo urlencode($course_code); ?>&chapter=1">بدء الشابتر الأول</a>
                </div>
            </div>
        <?php else: ?>

            <div class="question-jump" dir="ltr">
                <?php for ($i = 1; $i <= $totalQuestions; $i++): ?>
                    <a href="#ex<?php echo $i; ?>" id="btn-q<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>

            <form action="submit_preexam.php" method="post">
                <input type="hidden" name="course_code" value="<?php echo $course_code; ?>">

                <?php foreach ($questions as $index => $question): ?>
                    <section class="question-card" id="ex<?php echo $index + 1; ?>">
                        <div class="question-head">
                            <div class="question-no"><?php echo $index + 1; ?></div>
                            <div class="question-text-wrap">
                                <h3><?php echo htmlspecialchars($question['question_text']); ?></h3>
                                <span>درجة السؤال: 1</span>
                            </div>
                        </div>

                        <div class="options-grid form-q" data-question="<?php echo $index + 1; ?>">
                            <?php foreach ($question['options'] as $option): ?>
                                <label class="option-card btn-ch">
                                    <input 
                                        type="radio"
                                        name="answers[<?php echo $question['question_id']; ?>]"
                                        value="<?php echo htmlspecialchars($option['option_text']); ?>"
                                        class="check"
                                        required
                                    >
                                    <span class="Q-text"><?php echo htmlspecialchars($option['option_text']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>

                <div class="submit-panel">
                    <div class="submit-copy">
                        <strong>بعد التقديم سيتم حفظ النتيجة ونقلك مباشرة إلى الشابتر الأول.</strong>
                        <p>تقدر ترجع للكورس أو ترسل الاختبار الآن.</p>
                    </div>
                    <div class="action-row">
                        <a class="action-btn secondary-btn" href="course.php?id=<?php echo urlencode($course_code); ?>">رجوع للكورس</a>
                        <button type="submit" class="action-btn primary-btn">تقديم الاختبار التمهيدي</button>
                    </div>
                </div>
            </form>

        <?php endif; ?>
    </main>
</div>

<script>
    document.querySelectorAll('.form-q').forEach(form => {
        form.addEventListener('change', function(event) {
            const qNum = form.getAttribute('data-question');
            const btn = document.getElementById('btn-q' + qNum);
            if (btn) btn.classList.add('answered');

            form.querySelectorAll('.option-card').forEach(card => card.classList.remove('selected'));
            const label = event.target.closest('.option-card');
            if (label) label.classList.add('selected');
        });
    });
</script>

</body>
</html>
