<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'project');
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

if (!isset($_GET['course']) || $_GET['course'] === '') {
    die("الكورس غير موجود");
}

$course_code = (int)$_GET['course'];
$isTeacher = isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
$student_id = (!$isTeacher && isset($_SESSION['user_id'])) ? (int)$_SESSION['user_id'] : 0;
$showLockedNotice = isset($_GET['locked']) && $_GET['locked'] == '1';

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

/* عناوين الشابترات */
$chapterTitles = [];
$chapterCodeByNumber = [];
$stmt = $conn->prepare("SELECT chapter_code, number, title FROM chapter WHERE course_code = ? ORDER BY number ASC");
$stmt->bind_param("i", $course_code);
$stmt->execute();
$chapterResult = $stmt->get_result();
while ($row = $chapterResult->fetch_assoc()) {
    $num = (int)$row['number'];
    $chapterTitles[$num] = $row['title'];
    $chapterCodeByNumber[$num] = (int)$row['chapter_code'];
}
$stmt->close();

$chapters = [];
for ($i = 1; $i <= $total_chapters; $i++) {
    $chapters[] = [
        'number' => $i,
        'chapter_code' => $chapterCodeByNumber[$i] ?? 0,
        'title' => $chapterTitles[$i] ?? ('Chapter ' . $i)
    ];
}

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

if (!$isTeacher && $student_id > 0 && !$allChapterQuizzesCompleted) {
    header("Location: chapter.php?course=" . urlencode($course_code) . "&chapter=" . urlencode($maxUnlockedChapter) . "&locked=1");
    exit;
}

/* نجيب 30 سؤال من النهائي فقط */
$stmt = $conn->prepare("
    SELECT question_id, question_text, question_type, chapter_code
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

$questions = [];
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
$conn->close();

$totalQuestions = count($questions);
$questionChunks = array_chunk($questions, 10);
$totalPages = max(1, count($questionChunks));

$flashResult = null;
if (!empty($_SESSION['end_exam_result']) && (int)($_SESSION['end_exam_result']['course_code'] ?? 0) === $course_code) {
    $flashResult = $_SESSION['end_exam_result'];
    unset($_SESSION['end_exam_result']);
}

function canOpenChapter($number, $isTeacher, $student_id, $maxUnlockedChapter) {
    if ($isTeacher || $student_id <= 0) {
        return true;
    }
    return $number <= $maxUnlockedChapter;
}

function isQuizDone($number, $completedQuizMap) {
    return !empty($completedQuizMap[$number]);
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الاختبار النهائي - <?php echo htmlspecialchars($course['title']); ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="exam.css">
    <style>
        * { box-sizing: border-box; font-family: "Cairo", sans-serif; }
        body {
            margin: 0;
            background: linear-gradient(180deg, #f5f8fc 0%, #edf3f9 100%);
            color: #16324f;
        }
        body.modal-open { overflow: hidden; }
        .layout {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 24px;
            padding: 28px;
        }
        .sidebar-box, .exam-shell {
            background: #fff;
            border-radius: 26px;
            border: 1px solid #e7eef6;
            box-shadow: 0 14px 34px rgba(17, 45, 75, 0.08);
        }
        .sidebar-box {
            position: sticky;
            top: 24px;
            height: fit-content;
            padding: 22px 18px;
        }
        .sidebar-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 18px;
        }
        .sidebar-head h3 {
            margin: 0;
            color: #2e77c0;
            font-size: 22px;
        }
        .mini-chip {
            background: #eef5fc;
            color: #2e77c0;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
        }
        .nav-section-label {
            color: #6d8196;
            font-size: 13px;
            font-weight: 800;
            margin: 12px 0 10px;
            padding-right: 4px;
        }
        .nav-divider {
            height: 1px;
            background: #d7e3f1;
            margin: 16px 0;
        }
        .nav-group {
            background: #f8fbff;
            border: 1px solid #ebf1f7;
            border-radius: 18px;
            padding: 10px;
            margin-bottom: 12px;
        }
        .nav-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            text-decoration: none;
            color: #1b2b40;
            background: #f5f8fc;
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 10px;
            font-weight: 700;
            transition: 0.2s ease;
        }
        .nav-item:last-child { margin-bottom: 0; }
        .nav-item:hover {
            background: #e7f1fb;
            color: #2e77c0;
            transform: translateY(-1px);
        }
        .nav-main-text {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }
        .nav-main-text small {
            font-size: 11px;
            color: #7a8ca0;
            font-weight: 800;
        }
        .nav-main-text strong {
            font-size: 14px;
            color: #1d3550;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 160px;
        }
        .nav-status-pill {
            white-space: nowrap;
            padding: 7px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
        }
        .done-pill { background: #ebf9ee; color: #177840; }
        .open-pill { background: #e7f2ff; color: #2e77c0; }
        .current-pill { background: #eef5fc; color: #2e77c0; }
        .locked-pill { background: #f3e9e9; color: #b44e4e; }
        .locked-item {
            opacity: 0.72;
            cursor: not-allowed;
            background: #f2f4f7;
            color: #6f7f90;
        }
        .locked-item:hover {
            transform: none;
            background: #f2f4f7;
            color: #6f7f90;
        }
        .quiz-link {
            background: #eef5fb;
            color: #2e77c0;
            border-right: 4px solid #2e77c0;
        }
        .quiz-link.done {
            background: #ebf9ee;
            border-right-color: #26a257;
            color: #177840;
        }
        .final-link {
            background: linear-gradient(180deg, #f7fbff 0%, #eef5fc 100%);
        }
        .lock-alert {
            display: flex;
            gap: 10px;
            background: #fff4e8;
            border: 1px solid #ffd7a8;
            color: #8a5417;
            border-radius: 16px;
            padding: 14px;
            margin-bottom: 16px;
        }
        .exam-shell {
            padding: 26px;
        }
        .exam-hero {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            padding: 22px;
            border-radius: 22px;
            background: linear-gradient(135deg, #2e77c0, #3f8cdb);
            color: #fff;
            margin-bottom: 22px;
        }
        .exam-hero h1 {
            margin: 0 0 6px;
            font-size: 28px;
        }
        .exam-hero p {
            margin: 0;
            opacity: 0.95;
        }
        .hero-badge {
            min-width: 120px;
            height: 120px;
            border-radius: 24px;
            background: rgba(255,255,255,0.14);
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-weight: 800;
            line-height: 1.8;
            backdrop-filter: blur(6px);
        }
        .progress-strip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            background: #f7fbff;
            border: 1px solid #dbe8f5;
            border-radius: 18px;
            padding: 16px 18px;
            margin-bottom: 22px;
        }
        .progress-strip strong { color: #23486d; }
        .page-stepper {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .page-step {
            min-width: 48px;
            height: 48px;
            border-radius: 14px;
            border: 1px solid #d4e1ef;
            background: #fff;
            color: #64809d;
            font-weight: 800;
            cursor: pointer;
            transition: 0.2s ease;
        }
        .page-step.current {
            background: #2e77c0;
            border-color: #2e77c0;
            color: #fff;
            box-shadow: 0 10px 16px rgba(46, 119, 192, 0.22);
        }
        .page-step.done {
            background: #ebf9ee;
            border-color: #bde4c8;
            color: #177840;
        }
        .page-step.locked {
            background: #f2f4f7;
            color: #94a1af;
            cursor: not-allowed;
        }
        .exam-page { display: none; }
        .exam-page.active {
            display: block;
            animation: fadeIn 0.35s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .page-label {
            margin: 0 0 16px;
            color: #64809d;
            font-weight: 800;
            text-align: center;
        }
        .valid-exam {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
            margin-bottom: 22px;
        }
        .valid-exam a {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            background: #eef5fb;
            color: #476784;
            font-weight: 800;
            border: 1px solid #dce7f3;
        }
        .valid-exam a.answered {
            background: #ebf9ee;
            color: #177840;
            border-color: #ccead6;
        }
        .div-exam {
            background: #ffffff;
            border: 1px solid #e6eef7;
            border-radius: 22px;
            padding: 22px;
            margin-bottom: 18px;
            box-shadow: 0 8px 22px rgba(0,0,0,0.04);
        }
        .div-quist {
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }
        .p-quist {
            width: 48px;
            height: 48px;
            min-width: 48px;
            border-radius: 14px;
            background: #2e77c0;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            margin: 0;
            box-shadow: 0 10px 18px rgba(46, 119, 192, 0.18);
        }
        .p-quist2 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            line-height: 1.9;
            flex: 1;
            color: #21384f;
        }
        .degree-quist {
            margin: 0;
            background: #eef5fc;
            color: #2e77c0;
            padding: 8px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
        }
        .exam-hr {
            border: 0;
            border-top: 1px solid #edf2f7;
            margin: 18px 0;
        }
        .form-q {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .btn-ch {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 18px;
            border-radius: 16px;
            border: 1px solid #dce7f3;
            background: #f9fbfe;
            cursor: pointer;
            transition: 0.2s ease;
        }
        .btn-ch:hover {
            border-color: #bfd8f1;
            background: #f2f8ff;
            transform: translateY(-1px);
        }
        .btn-ch.selected {
            border-color: #2e77c0;
            background: #eef5fc;
            box-shadow: 0 8px 18px rgba(46, 119, 192, 0.10);
        }
        .check {
            accent-color: #2e77c0;
            transform: scale(1.2);
        }
        .Q-text {
            font-size: 15px;
            font-weight: 700;
            color: #29425a;
            line-height: 1.8;
        }
        .exam-done {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
        }
        .exam-done button,
        .exam-done a {
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
        .back-exam button,
        .back-exam a {
            background: #e9f1fa;
            color: #2e77c0;
        }
        .submit-exam button {
            background: #2e77c0;
            color: #fff;
            box-shadow: 0 10px 18px rgba(46, 119, 192, 0.18);
        }
        .submit-exam button.final-submit {
            background: linear-gradient(135deg, #22a35f, #19884e);
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background: #f8fbff;
            border: 1px dashed #bfd4eb;
            border-radius: 22px;
        }
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(10, 25, 45, 0.55);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 9999;
        }
        .result-modal {
            width: min(560px, 100%);
            background: #fff;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0,0,0,0.22);
            animation: popIn 0.25s ease;
        }
        @keyframes popIn {
            from { opacity: 0; transform: scale(0.96) translateY(8px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-top {
            padding: 28px;
            color: #fff;
            background: linear-gradient(135deg, #2e77c0, #3f8cdb);
            text-align: center;
        }
        .modal-top h2 {
            margin: 0 0 8px;
            font-size: 30px;
        }
        .modal-top p {
            margin: 0;
            opacity: 0.96;
        }
        .modal-body {
            padding: 26px;
        }
        .result-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 18px;
        }
        .result-stat {
            background: #f7fbff;
            border: 1px solid #dde9f5;
            border-radius: 18px;
            padding: 16px;
            text-align: center;
        }
        .result-stat strong {
            display: block;
            font-size: 22px;
            color: #21466c;
            margin-bottom: 4px;
        }
        .result-stat span {
            color: #64809d;
            font-size: 13px;
            font-weight: 700;
        }
        .recommend-box {
            background: #eef6ff;
            border: 1px solid #d8e8fb;
            border-radius: 18px;
            padding: 18px;
            color: #284764;
            line-height: 1.9;
        }
        .recommend-box strong {
            display: block;
            margin-bottom: 8px;
        }
        .modal-actions {
            padding: 0 26px 26px;
        }
        .modal-actions button {
            width: 100%;
            height: 52px;
            border: none;
            border-radius: 16px;
            background: #2e77c0;
            color: #fff;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
        }
        @media (max-width: 1100px) {
            .layout { grid-template-columns: 1fr; }
            .sidebar-box { position: static; }
        }
        @media (max-width: 900px) {
            .layout { padding: 16px; }
            .exam-hero { flex-direction: column; align-items: stretch; }
            .hero-badge { width: 100%; min-width: 100%; height: auto; padding: 16px; }
            .form-q { grid-template-columns: 1fr; }
            .result-grid { grid-template-columns: 1fr; }
            .exam-done button, .exam-done a { width: 100%; }
        }
    </style>
</head>
<body class="<?php echo $flashResult ? 'modal-open' : ''; ?>">

<div class="layout">
    <aside class="sidebar-box">
        <div class="sidebar-head">
            <h3>التنقلات</h3>
            <span class="mini-chip">النهائي</span>
        </div>

        <?php if ($showLockedNotice): ?>
            <div class="lock-alert">
                <span class="material-symbols-outlined">lock</span>
                <div>
                    <strong>الوصول مقفول</strong>
                    <div>أكمل كل اختبارات الشابترات أولًا حتى ينفتح لك الاختبار النهائي.</div>
                </div>
            </div>
        <?php endif; ?>

        <div class="nav-section-label">روابط سريعة</div>
        <a class="nav-item" href="second.php"><span>الصفحة الرئيسية</span><span class="nav-status-pill open-pill">فتح</span></a>
        <a class="nav-item" href="course.php?id=<?php echo urlencode($course_code); ?>"><span>صفحة الكورس</span><span class="nav-status-pill open-pill">فتح</span></a>

        <div class="nav-divider"></div>
        <div class="nav-section-label">الشابترات والاختبارات</div>

        <?php foreach ($chapters as $item): ?>
            <?php
                $itemNumber = (int)$item['number'];
                $chapterUnlocked = canOpenChapter($itemNumber, $isTeacher, $student_id, $maxUnlockedChapter);
                $quizDone = isQuizDone($itemNumber, $completedQuizMap);
            ?>
            <div class="nav-group">
                <?php if ($chapterUnlocked): ?>
                    <a class="nav-item" href="chapter.php?course=<?php echo urlencode($course_code); ?>&chapter=<?php echo urlencode($itemNumber); ?>">
                        <span class="nav-main-text">
                            <small>شابتر <?php echo $itemNumber; ?></small>
                            <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                        </span>
                        <span class="nav-status-pill <?php echo $quizDone ? 'done-pill' : 'open-pill'; ?>">
                            <?php echo $quizDone ? 'مكتمل' : 'مفتوح'; ?>
                        </span>
                    </a>
                <?php else: ?>
                    <div class="nav-item locked-item">
                        <span class="nav-main-text">
                            <small>شابتر <?php echo $itemNumber; ?></small>
                            <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                        </span>
                        <span class="nav-status-pill locked-pill">مقفل</span>
                    </div>
                <?php endif; ?>

                <?php if ($chapterUnlocked): ?>
                    <a class="nav-item quiz-link <?php echo $quizDone ? 'done' : ''; ?>" href="exam.php?course=<?php echo urlencode($course_code); ?>&chapter=<?php echo urlencode($itemNumber); ?>">
                        <span>اختبار الشابتر <?php echo $itemNumber; ?></span>
                        <span class="nav-status-pill <?php echo $quizDone ? 'done-pill' : 'open-pill'; ?>"><?php echo $quizDone ? 'تم' : 'متاح'; ?></span>
                    </a>
                <?php else: ?>
                    <div class="nav-item quiz-link locked-item">
                        <span>اختبار الشابتر <?php echo $itemNumber; ?></span>
                        <span class="nav-status-pill locked-pill">مقفل</span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="nav-divider"></div>
        <div class="nav-section-label">الحالة الحالية</div>
        <a class="nav-item final-link" href="endexam.php?course=<?php echo urlencode($course_code); ?>">
            <span>الاختبار النهائي</span>
            <span class="nav-status-pill current-pill">الحالي</span>
        </a>
    </aside>

    <main class="exam-shell">
        <div class="exam-hero">
            <div>
                <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                <p>الاختبار النهائي للكورس — رتّبت لك الواجهة والتنقل بحيث ما يفتح القادم إلا بعد إكمال الحالي.</p>
            </div>
            <div class="hero-badge">
                <?php echo $totalQuestions; ?><br>سؤال
            </div>
        </div>

        <div class="progress-strip">
            <div>
                <strong>التقدم في الاختبار النهائي</strong>
                <div>يمكنك الرجوع للصفحات السابقة دائمًا، لكن الصفحات القادمة لا تُفتح إلا بعد إكمال الصفحة الحالية.</div>
            </div>
            <div class="page-stepper" id="pageStepper">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <button type="button" class="page-step <?php echo $i === 1 ? 'current' : 'locked'; ?>" data-page-step="<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </button>
                <?php endfor; ?>
            </div>
        </div>

        <?php if ($totalQuestions === 0): ?>
            <div class="empty-state">
                <h3>لا توجد أسئلة نهائية لهذا الكورس حتى الآن</h3>
                <p>أضف أسئلة نهائية من لوحة المعلم ثم ارجع لهذه الصفحة.</p>
            </div>
        <?php else: ?>
            <form id="endExamForm" method="POST" action="submit_end_exam.php?course=<?php echo urlencode($course_code); ?>">
                <?php
                $globalQuestionNumber = 1;
                foreach ($questionChunks as $pageIndex => $pageQuestions):
                    $pageNumber = $pageIndex + 1;
                ?>
                    <section class="exam-page <?php echo $pageNumber === 1 ? 'active' : ''; ?>" id="page<?php echo $pageNumber; ?>" data-page="<?php echo $pageNumber; ?>">
                        <p class="page-label">الصفحة <?php echo $pageNumber; ?> من <?php echo $totalPages; ?></p>

                        <div class="valid-exam" dir="ltr">
                            <?php foreach ($pageQuestions as $localIndex => $question): ?>
                                <a href="#ex<?php echo $globalQuestionNumber + $localIndex; ?>" id="btn-q<?php echo $globalQuestionNumber + $localIndex; ?>">
                                    <?php echo $globalQuestionNumber + $localIndex; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <?php foreach ($pageQuestions as $localIndex => $question): ?>
                            <?php $questionNumber = $globalQuestionNumber + $localIndex; ?>
                            <div class="div-exam" id="ex<?php echo $questionNumber; ?>">
                                <div class="div-quist">
                                    <p class="p-quist"><?php echo $questionNumber; ?></p>
                                    <p class="p-quist2"><?php echo htmlspecialchars($question['question_text']); ?></p>
                                    <p class="degree-quist">0 / 1</p>
                                </div>
                                <hr class="exam-hr">

                                <div class="form-q" data-question="<?php echo $questionNumber; ?>" data-page-owner="<?php echo $pageNumber; ?>">
                                    <?php foreach ($question['options'] as $option): ?>
                                        <label class="btn-ch">
                                            <input
                                                type="radio"
                                                name="answers[<?php echo $question['question_id']; ?>]"
                                                value="<?php echo htmlspecialchars($option['option_text']); ?>"
                                                class="check"
                                            >
                                            <span class="Q-text"><?php echo htmlspecialchars($option['option_text']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="exam-done">
                            <div class="back-exam">
                                <?php if ($pageNumber > 1): ?>
                                    <button type="button" onclick="goToPage(<?php echo $pageNumber - 1; ?>)">رجوع</button>
                                <?php else: ?>
                                    <a href="course.php?id=<?php echo urlencode($course_code); ?>">رجوع للكورس</a>
                                <?php endif; ?>
                            </div>

                            <div class="submit-exam">
                                <?php if ($pageNumber < $totalPages): ?>
                                    <button type="button" onclick="goToNextPage(<?php echo $pageNumber; ?>)">التالي</button>
                                <?php else: ?>
                                    <button type="submit" class="final-submit">تقديم النهائي</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                <?php
                    $globalQuestionNumber += count($pageQuestions);
                endforeach;
                ?>
            </form>
        <?php endif; ?>
    </main>
</div>

<?php if ($flashResult): ?>
    <div class="modal-overlay" id="resultModal">
        <div class="result-modal">
            <div class="modal-top">
                <h2>تم تقديم النهائي</h2>
                <p>هذه نتيجتك مباشرة داخل واجهة الاختبار النهائي</p>
            </div>
            <div class="modal-body">
                <div class="result-grid">
                    <div class="result-stat">
                        <strong><?php echo (int)$flashResult['score']; ?> / <?php echo (int)$flashResult['total']; ?></strong>
                        <span>الدرجة</span>
                    </div>
                    <div class="result-stat">
                        <strong><?php echo round((float)$flashResult['percentage']); ?>%</strong>
                        <span>النسبة</span>
                    </div>
                    <div class="result-stat">
                        <strong><?php echo htmlspecialchars($flashResult['levelText']); ?></strong>
                        <span>المستوى</span>
                    </div>
                </div>
                <div class="recommend-box">
                    <strong>التوصية</strong>
                    <div><?php echo htmlspecialchars($flashResult['recommendation']); ?></div>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" onclick="window.location.href='course.php?id=<?php echo urlencode($course_code); ?>'">حسنًا</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    const totalPages = <?php echo (int)$totalPages; ?>;
    const pageStates = {};
    let currentPage = 1;

    for (let i = 1; i <= totalPages; i++) {
        pageStates[i] = (i === 1);
    }

    function evaluatePage(pageNum) {
        const forms = document.querySelectorAll(`.form-q[data-page-owner="${pageNum}"]`);
        if (!forms.length) return false;

        let complete = true;
        forms.forEach(form => {
            const checked = form.querySelector('input[type="radio"]:checked');
            if (!checked) complete = false;
        });
        return complete;
    }

    function refreshPageSteps() {
        document.querySelectorAll('[data-page-step]').forEach(btn => {
            const page = parseInt(btn.getAttribute('data-page-step'), 10);
            btn.classList.remove('current', 'done', 'locked');

            if (page === currentPage) {
                btn.classList.add('current');
            } else if (pageStates[page]) {
                if (evaluatePage(page)) {
                    btn.classList.add('done');
                } else {
                    btn.classList.add('current-pill');
                }
            } else {
                btn.classList.add('locked');
            }
        });
    }

    function goToPage(pageNum) {
        if (!pageStates[pageNum]) return;

        document.querySelectorAll('.exam-page').forEach(page => page.classList.remove('active'));
        const target = document.getElementById('page' + pageNum);
        if (target) {
            target.classList.add('active');
            currentPage = pageNum;
            refreshPageSteps();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    function unlockNextPageIfReady(pageNum) {
        if (evaluatePage(pageNum) && pageNum < totalPages) {
            pageStates[pageNum + 1] = true;
        }
        refreshPageSteps();
    }

    function goToNextPage(pageNum) {
        if (!evaluatePage(pageNum)) {
            alert('أجب على كل أسئلة هذه الصفحة أولًا ثم انتقل للي بعدها.');
            return;
        }

        unlockNextPageIfReady(pageNum);
        goToPage(pageNum + 1);
    }

    document.querySelectorAll('.form-q').forEach(form => {
        form.addEventListener('change', function(event) {
            const qNum = form.getAttribute('data-question');
            const pageOwner = parseInt(form.getAttribute('data-page-owner'), 10);
            const btn = document.getElementById('btn-q' + qNum);
            if (btn) btn.classList.add('answered');

            const labels = form.querySelectorAll('.btn-ch');
            labels.forEach(label => label.classList.remove('selected'));
            const selectedInput = form.querySelector('input[type="radio"]:checked');
            if (selectedInput) {
                selectedInput.closest('.btn-ch').classList.add('selected');
            }

            unlockNextPageIfReady(pageOwner);
        });
    });

    document.querySelectorAll('[data-page-step]').forEach(btn => {
        btn.addEventListener('click', function() {
            const pageNum = parseInt(this.getAttribute('data-page-step'), 10);
            if (pageStates[pageNum]) {
                goToPage(pageNum);
            }
        });
    });

    const form = document.getElementById('endExamForm');
    if (form) {
        form.addEventListener('submit', function(event) {
            for (let i = 1; i <= totalPages; i++) {
                if (!evaluatePage(i)) {
                    event.preventDefault();
                    alert('أكمل جميع أسئلة الاختبار قبل التقديم.');
                    const firstIncomplete = i;
                    if (pageStates[firstIncomplete]) {
                        goToPage(firstIncomplete);
                    }
                    return;
                }
            }
        });
    }

    refreshPageSteps();
</script>
</body>
</html>
