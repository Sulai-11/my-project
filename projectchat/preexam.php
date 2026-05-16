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
$showLockedNotice = isset($_GET['locked']) && $_GET['locked'] == '1';
$isTeacher = isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
$student_id = (!$isTeacher && isset($_SESSION['user_id'])) ? (int) $_SESSION['user_id'] : 0;

$stmt = $conn->prepare("SELECT course_code, title, total_chapters FROM course WHERE course_code = ?");
$stmt->bind_param("i", $course_code);
$stmt->execute();
$courseResult = $stmt->get_result();

if ($courseResult->num_rows !== 1) {
    die("الكورس غير موجود");
}

$course = $courseResult->fetch_assoc();
$stmt->close();

$total_chapters = max(1, (int)($course['total_chapters'] ?? 1));

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

$totalQuestions = count($questions);

$completedPre = false;
if ($student_id > 0) {
    $stmt = $conn->prepare("
        SELECT attempt_id
        FROM exam_attempt
        WHERE student_id = ?
          AND course_code = ?
          AND exam_type = 'pre'
          AND status = 'submitted'
        LIMIT 1
    ");
    $stmt->bind_param("ii", $student_id, $course_code);
    $stmt->execute();
    $preResult = $stmt->get_result();
    $completedPre = $preResult->num_rows > 0;
    $stmt->close();
}

$completedQuizMap = [];
if ($student_id > 0) {
    $stmt = $conn->prepare("
        SELECT DISTINCT c.number
        FROM chapter c
        LEFT JOIN exam_attempt ea
            ON ea.chapter_code = c.chapter_code
           AND ea.student_id = ?
           AND ea.course_code = ?
           AND ea.exam_type = 'chapter'
           AND ea.status = 'submitted'
        LEFT JOIN chapter_quiz cq
            ON cq.chapter_code = c.chapter_code
           AND cq.student_id = ?
        WHERE c.course_code = ?
          AND (ea.attempt_id IS NOT NULL OR cq.student_id IS NOT NULL)
        ORDER BY c.number ASC
    ");
    $stmt->bind_param("iiii", $student_id, $course_code, $student_id, $course_code);
    $stmt->execute();
    $progressResult = $stmt->get_result();
    while ($row = $progressResult->fetch_assoc()) {
        $completedQuizMap[(int)$row['number']] = true;
    }
    $stmt->close();
}

$chapters = [];
$stmt = $conn->prepare("SELECT chapter_code, number, title FROM chapter WHERE course_code = ? ORDER BY number ASC");
$stmt->bind_param("i", $course_code);
$stmt->execute();
$chapterResult = $stmt->get_result();
while ($row = $chapterResult->fetch_assoc()) {
    $chapters[] = $row;
}
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre Exam</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="stylesheet" href="exam.css">
</head>
<body>

<div class="exam-layout">
    <aside class="sidebar-box">
        <div class="sidebar-head">
            <h3>التنقلات</h3>
            <span class="mini-chip">تمهيدي</span>
        </div>

        <a class="nav-item top-link" href="second.php">
            <span>الصفحة الرئيسية</span>
            <span class="material-symbols-outlined nav-icon">home</span>
        </a>

        <a class="nav-item top-link" href="course.php?id=<?php echo urlencode($course_code); ?>">
            <span>صفحة الكورس</span>
            <span class="material-symbols-outlined nav-icon">menu_book</span>
        </a>

        <div class="nav-divider"></div>

        <?php if ($showLockedNotice): ?>
            <div class="lock-alert">
                <span class="material-symbols-outlined">lock</span>
                <div>
                    <strong>الوصول مقفول</strong>
                    <p>يجب إنهاء أو تخطي البري إكزام أولًا.</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="nav-section-label">مسار الكورس</div>

        <a class="nav-item <?php echo $completedPre ? 'quiz-link done' : 'quiz-link active'; ?>" href="preexam.php?course=<?php echo urlencode($course_code); ?>">
            <span>البري إكزام</span>
            <span class="nav-status-pill <?php echo $completedPre ? 'done-pill' : 'current-pill'; ?>">
                <?php echo $completedPre ? 'مكتمل' : 'الحالي'; ?>
            </span>
        </a>

        <?php foreach ($chapters as $item): ?>
            <?php
                $n = (int)$item['number'];
                $quizDone = !empty($completedQuizMap[$n]);
                $chapterOpen = $completedPre && ($n === 1 || !empty($completedQuizMap[$n - 1]));
            ?>
            <div class="nav-group">
                <?php if ($chapterOpen || $isTeacher): ?>
                    <a class="nav-item" href="chapter.php?course=<?php echo urlencode($course_code); ?>&chapter=<?php echo urlencode($n); ?>">
                        <span class="nav-main-text">
                            <small>شابتر <?php echo $n; ?></small>
                            <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                        </span>
                        <span class="nav-status-pill open-pill">مفتوح</span>
                    </a>
                <?php else: ?>
                    <div class="nav-item locked-item">
                        <span class="nav-main-text">
                            <small>شابتر <?php echo $n; ?></small>
                            <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                        </span>
                        <span class="nav-status-pill locked-pill">مقفل</span>
                    </div>
                <?php endif; ?>

                <?php if ($quizDone): ?>
                    <a class="nav-item quiz-link done" href="exam.php?course=<?php echo urlencode($course_code); ?>&chapter=<?php echo urlencode($n); ?>">
                        <span>اختبار الشابتر <?php echo $n; ?></span>
                        <span class="nav-status-pill done-pill">مكتمل</span>
                    </a>
                <?php else: ?>
                    <div class="nav-item locked-item">
                        <span>اختبار الشابتر <?php echo $n; ?></span>
                        <span class="nav-status-pill locked-pill">مقفل</span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="nav-divider"></div>

        <div class="nav-section-label">الاختبار النهائي</div>
        <div class="nav-item locked-item final-link">
            <span>الاختبار النهائي</span>
            <span class="nav-status-pill locked-pill">لاحقًا</span>
        </div>
    </aside>

    <main class="exam-shell">
        <section class="exam-hero">
            <div>
                <span class="hero-tag">تمهيد قبل البداية</span>
                <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                <p>هذا الاختبار يقيس مستواك قبل البدء. يمكنك الحل ثم الضغط على التالي، أو استخدام زر التخطي للانتقال مباشرة إلى الشابتر الأول.</p>
            </div>
            <div class="hero-badge">
                Pre<br>Exam
            </div>
        </section>

        <section class="progress-banner">
            <div>
                <strong>عدد الأسئلة</strong>
                <p><?php echo $totalQuestions; ?> سؤال تمهيدي لهذا الكورس</p>
            </div>
            <span class="progress-badge">0 / <?php echo $total_chapters; ?></span>
        </section>

        <?php if ($totalQuestions === 0): ?>
            <section class="empty-card">
                <span class="material-symbols-outlined">quiz</span>
                <h2>لا توجد أسئلة تمهيدية</h2>
                <p>لم يتم إضافة أسئلة للبري إكزام في هذا الكورس حتى الآن.</p>
                <div class="action-row">
                    <a href="course.php?id=<?php echo urlencode($course_code); ?>" class="action-btn secondary-btn">رجوع</a>
                    <a href="submit_preexam.php?course=<?php echo urlencode($course_code); ?>&skip=1" class="action-btn primary-btn">تخطي البري إكزام</a>
                </div>
            </section>
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
                                <span>اختر الإجابة الصحيحة</span>
                            </div>
                        </div>

                        <div class="options-grid form-q" data-question="<?php echo $index + 1; ?>">
                            <?php foreach ($question['options'] as $optIndex => $option): ?>
                                <label class="option-card">
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

                <section class="submit-panel">
                    <div class="submit-copy">
                        <strong>بعد الإرسال ستنتقل مباشرة إلى الشابتر الأول</strong>
                        <p>يمكنك أيضًا تخطي البري إكزام بدون حل الأسئلة.</p>
                    </div>

                    <div class="action-row">
                        <a href="course.php?id=<?php echo urlencode($course_code); ?>" class="action-btn secondary-btn">السابق</a>
                        <a href="submit_preexam.php?course=<?php echo urlencode($course_code); ?>&skip=1" class="action-btn secondary-btn">تخطي البري إكزام</a>
                        <button type="submit" class="action-btn primary-btn">التالي</button>
                    </div>
                </section>
            </form>

        <?php endif; ?>
    </main>
</div>

<script>
document.querySelectorAll('.form-q').forEach(form => {
    form.addEventListener('change', function(e) {
        const qNum = form.getAttribute('data-question');
        const btn = document.getElementById('btn-q' + qNum);
        if (btn) btn.classList.add('answered');

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
