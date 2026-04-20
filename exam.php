<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'project');
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

if (!isset($_GET['course']) || !isset($_GET['chapter'])) {
    die("بيانات الاختبار غير مكتملة");
}

$course_code = (int)$_GET['course'];
$chapter_number = max(1, (int)$_GET['chapter']);
$isTeacher = isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
$student_id = (!$isTeacher && isset($_SESSION['user_id'])) ? (int)$_SESSION['user_id'] : 0;
$showLockedNotice = isset($_GET['locked']) && $_GET['locked'] == '1';
$autoDoneNotice = isset($_GET['autodone']) && $_GET['autodone'] == '1';

/* جلب بيانات الكورس */
$stmt = $conn->prepare("SELECT course_code, title, total_chapters FROM course WHERE course_code = ?");
$stmt->bind_param("i", $course_code);
$stmt->execute();
$courseResult = $stmt->get_result();

if ($courseResult->num_rows !== 1) {
    die("الكورس غير موجود");
}

$course = $courseResult->fetch_assoc();
$stmt->close();

$total_chapters = (int)($course['total_chapters'] ?? 5);
$total_chapters = max(1, min(7, $total_chapters));

/* جلب الشابترات كلها */
$chapters = [];
$chapterCodeByNumber = [];
$chapterTitles = [];

$stmt = $conn->prepare("SELECT chapter_code, number, title FROM chapter WHERE course_code = ? ORDER BY number ASC");
$stmt->bind_param("i", $course_code);
$stmt->execute();
$chapterListResult = $stmt->get_result();

while ($row = $chapterListResult->fetch_assoc()) {
    $num = (int)$row['number'];
    $chapterCodeByNumber[$num] = (int)$row['chapter_code'];
    $chapterTitles[$num] = $row['title'];
    $chapters[] = [
        'chapter_code' => (int)$row['chapter_code'],
        'number' => $num,
        'title' => $row['title']
    ];
}
$stmt->close();

if (!isset($chapterCodeByNumber[$chapter_number])) {
    die("الشابتر غير موجود");
}

$chapter_code = (int)$chapterCodeByNumber[$chapter_number];
$chapter_title = $chapterTitles[$chapter_number] ?? ('Chapter ' . $chapter_number);

/* تقدم الطالب في كويزات الشابترات */
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

$sequentialCompleted = 0;
for ($i = 1; $i <= $total_chapters; $i++) {
    if (!empty($completedQuizMap[$i])) {
        $sequentialCompleted++;
    } else {
        break;
    }
}

$maxUnlockedChapter = min($total_chapters, $sequentialCompleted + 1);
$allChapterQuizzesCompleted = ($sequentialCompleted >= $total_chapters);

if (!$isTeacher && $student_id > 0 && $chapter_number > $maxUnlockedChapter) {
    header("Location: exam.php?course=" . urlencode($course_code) . "&chapter=" . urlencode($maxUnlockedChapter) . "&locked=1");
    exit;
}

/* جلب أسئلة اختبار الشابتر */
$questions = [];

$stmt = $conn->prepare("
    SELECT question_id, question_text, question_type
    FROM question_bank
    WHERE course_code = ? 
      AND chapter_code = ?
      AND question_scope = 'chapter'
      AND is_active = 1
    ORDER BY question_id ASC
    LIMIT 5
");
$stmt->bind_param("ii", $course_code, $chapter_code);
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
$currentQuizDone = !empty($completedQuizMap[$chapter_number]);

/* إذا ما فيه أسئلة، نعتبر الكويز مكتمل حتى لا يوقف التسلسل */
if (!$isTeacher && $student_id > 0 && $totalQuestions === 0 && !$currentQuizDone && $chapter_number <= $maxUnlockedChapter) {
    $stmt = $conn->prepare("
        INSERT INTO chapter_quiz (student_id, chapter_code, grade)
        VALUES (?, ?, 0)
        ON DUPLICATE KEY UPDATE grade = grade
    ");
    $stmt->bind_param("ii", $student_id, $chapter_code);
    $stmt->execute();
    $stmt->close();

    header("Location: exam.php?course=" . urlencode($course_code) . "&chapter=" . urlencode($chapter_number) . "&autodone=1");
    exit;
}

$currentQuizDone = !empty($completedQuizMap[$chapter_number]) || $autoDoneNotice;

function canOpenChapter($number, $isTeacher, $student_id, $maxUnlockedChapter) {
    if ($isTeacher || $student_id <= 0) {
        return true;
    }
    return $number <= $maxUnlockedChapter;
}

function isQuizDone($number, $completedQuizMap, $autoDoneNotice, $chapter_number) {
    if ($autoDoneNotice && $number === $chapter_number) {
        return true;
    }
    return !empty($completedQuizMap[$number]);
}

$nextChapterNumber = $chapter_number + 1;
$nextChapterUnlocked = $chapter_number < $total_chapters && canOpenChapter($nextChapterNumber, $isTeacher, $student_id, $maxUnlockedChapter);

$conn->close();
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار الشابتر - <?php echo htmlspecialchars($chapter_title); ?></title>
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
            <span class="mini-chip"><?php echo $total_chapters; ?> شابترات</span>
        </div>

        <?php if ($showLockedNotice): ?>
            <div class="lock-alert">
                <span class="material-symbols-outlined">lock</span>
                <div>
                    <strong>الوصول مقفول</strong>
                    <p>أكمل اختبار الشابتر السابق أولًا حتى ينفتح لك هذا الاختبار.</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($autoDoneNotice): ?>
            <div class="success-alert">
                <span class="material-symbols-outlined">check_circle</span>
                <div>
                    <strong>تم تجاوز هذا الكويز تلقائيًا</strong>
                    <p>هذا الشابتر لا يحتوي على أسئلة حاليًا، لذلك اعتبره النظام مكتملًا حتى لا يتوقف التقدم.</p>
                </div>
            </div>
        <?php endif; ?>

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
        <div class="nav-section-label">محتوى الكورس</div>

        <?php foreach ($chapters as $item): ?>
            <?php
                $itemNumber = (int)$item['number'];
                $chapterUnlocked = canOpenChapter($itemNumber, $isTeacher, $student_id, $maxUnlockedChapter);
                $quizDone = isQuizDone($itemNumber, $completedQuizMap, $autoDoneNotice, $chapter_number);
                $isCurrentExam = $itemNumber === $chapter_number;
                $chapterHref = "chapter.php?course=" . urlencode($course_code) . "&chapter=" . urlencode($itemNumber);
                $quizHref = "exam.php?course=" . urlencode($course_code) . "&chapter=" . urlencode($itemNumber);
            ?>
            <div class="nav-group <?php echo $isCurrentExam ? 'current-group' : ''; ?>">
                <?php if ($chapterUnlocked): ?>
                    <a class="nav-item chapter-link" href="<?php echo $chapterHref; ?>">
                        <span class="nav-main-text">
                            <small>شابتر <?php echo $itemNumber; ?></small>
                            <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                        </span>
                        <span class="nav-status-pill open-pill">مفتوح</span>
                    </a>
                <?php else: ?>
                    <div class="nav-item chapter-link locked-item">
                        <span class="nav-main-text">
                            <small>شابتر <?php echo $itemNumber; ?></small>
                            <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                        </span>
                        <span class="nav-status-pill locked-pill">مقفل</span>
                    </div>
                <?php endif; ?>

                <?php if ($chapterUnlocked): ?>
                    <a class="nav-item quiz-link <?php echo $quizDone ? 'done' : ''; ?> <?php echo $isCurrentExam ? 'active' : ''; ?>" href="<?php echo $quizHref; ?>">
                        <span class="nav-main-text">
                            <small>اختبار الشابتر</small>
                            <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                        </span>
                        <span class="nav-status-pill <?php echo $isCurrentExam ? 'current-pill' : ($quizDone ? 'done-pill' : 'open-pill'); ?>">
                            <?php echo $isCurrentExam ? 'الحالي' : ($quizDone ? 'مكتمل' : 'متاح'); ?>
                        </span>
                    </a>
                <?php else: ?>
                    <div class="nav-item quiz-link locked-item">
                        <span class="nav-main-text">
                            <small>اختبار الشابتر</small>
                            <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                        </span>
                        <span class="nav-status-pill locked-pill">مقفل</span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="nav-divider"></div>
        <div class="nav-section-label">الاختبار النهائي</div>

        <?php if ($isTeacher || $student_id <= 0 || $allChapterQuizzesCompleted): ?>
            <a class="nav-item final-link" href="endexam.php?course=<?php echo urlencode($course_code); ?>">
                <span>الاختبار النهائي</span>
                <span class="nav-status-pill final-pill">جاهز</span>
            </a>
        <?php else: ?>
            <div class="nav-item final-link locked-item">
                <span>الاختبار النهائي</span>
                <span class="nav-status-pill locked-pill">أكمل الشابترات أولًا</span>
            </div>
        <?php endif; ?>
    </aside>

    <main class="exam-shell">
        <section class="exam-hero">
            <div>
                <div class="hero-tag">Chapter Exam</div>
                <h1><?php echo htmlspecialchars($chapter_title); ?></h1>
                <p><?php echo htmlspecialchars($course['title']); ?> — الشابتر <?php echo $chapter_number; ?> من <?php echo $total_chapters; ?></p>
            </div>
            <div class="hero-badge">
                <?php if ($currentQuizDone): ?>
                    مكتمل
                <?php else: ?>
                    <?php echo $totalQuestions; ?><br>أسئلة
                <?php endif; ?>
            </div>
        </section>

        <?php if (!$isTeacher && $student_id > 0): ?>
            <div class="progress-banner <?php echo $currentQuizDone ? 'success-state' : ''; ?>">
                <div>
                    <strong>
                        <?php if ($currentQuizDone): ?>
                            تم إنهاء اختبار هذا الشابتر.
                        <?php else: ?>
                            أنهِ هذا الاختبار حتى ينفتح لك ما بعده.
                        <?php endif; ?>
                    </strong>
                    <p>التقدم الحالي: <?php echo $sequentialCompleted + ($autoDoneNotice && empty($completedQuizMap[$chapter_number]) ? 1 : 0); ?> / <?php echo $total_chapters; ?> اختبارات مكتملة</p>
                </div>
                <span class="progress-badge"><?php echo $chapter_number; ?>/<?php echo $total_chapters; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($totalQuestions === 0): ?>
            <div class="empty-card">
                <span class="material-symbols-outlined">quiz</span>
                <h2>لا توجد أسئلة لهذا الشابتر حاليًا</h2>
                <p>تم التعامل مع هذا الشابتر بحيث لا يوقف مسار الطالب، ويمكنك الرجوع أو متابعة التقدم.</p>

                <div class="action-row">
                    <a class="action-btn secondary-btn" href="chapter.php?course=<?php echo urlencode($course_code); ?>&chapter=<?php echo urlencode($chapter_number); ?>">رجوع للشابتر</a>

                    <?php if ($chapter_number < $total_chapters): ?>
                        <a class="action-btn primary-btn" href="chapter.php?course=<?php echo urlencode($course_code); ?>&chapter=<?php echo urlencode($chapter_number + 1); ?>">الشابتر التالي</a>
                    <?php else: ?>
                        <a class="action-btn final-btn" href="endexam.php?course=<?php echo urlencode($course_code); ?>">الاختبار النهائي</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>

            <div class="question-jump" dir="ltr">
                <?php for ($i = 1; $i <= $totalQuestions; $i++): ?>
                    <a href="#ex<?php echo $i; ?>" id="btn-q<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>

            <form action="submit_chapter_exam.php" method="post">
                <input type="hidden" name="course_code" value="<?php echo $course_code; ?>">
                <input type="hidden" name="chapter_code" value="<?php echo $chapter_code; ?>">
                <input type="hidden" name="chapter_number" value="<?php echo $chapter_number; ?>">

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
                        <strong>بعد التقديم سيتم حفظ النتيجة ويفتح لك التقدم التالي تلقائيًا.</strong>
                        <p>تقدر ترجع للشابتر أو ترسل الاختبار الآن.</p>
                    </div>
                    <div class="action-row">
                        <a class="action-btn secondary-btn" href="chapter.php?course=<?php echo urlencode($course_code); ?>&chapter=<?php echo urlencode($chapter_number); ?>">رجوع للشابتر</a>
                        <button type="submit" class="action-btn primary-btn">تقديم الاختبار</button>
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
