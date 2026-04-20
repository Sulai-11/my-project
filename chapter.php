<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'project');
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

if (!isset($_GET['course']) || !isset($_GET['chapter'])) {
    die("بيانات الشابتر غير مكتملة");
}

$course_code = (int) $_GET['course'];
$chapter_number = max(1, (int) $_GET['chapter']);
$isTeacher = isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
$student_id = (!$isTeacher && isset($_SESSION['user_id'])) ? (int) $_SESSION['user_id'] : 0;
$showLockedNotice = isset($_GET['locked']) && $_GET['locked'] == '1';

/* جلب بيانات الكورس */
$stmt = $conn->prepare("SELECT * FROM course WHERE course_code = ?");
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

/* كل عناوين الشابترات */
$chapterTitles = [];
$chapterCodeByNumber = [];
$stmt = $conn->prepare("SELECT chapter_code, number, title FROM chapter WHERE course_code = ? ORDER BY number ASC");
$stmt->bind_param("i", $course_code);
$stmt->execute();
$listResult = $stmt->get_result();
while ($row = $listResult->fetch_assoc()) {
    $num = (int)$row['number'];
    $chapterTitles[$num] = $row['title'];
    $chapterCodeByNumber[$num] = (int)$row['chapter_code'];
}
$stmt->close();

/* إنشاء الشابتر تلقائيًا إذا لم يكن موجودًا */
$stmt = $conn->prepare("SELECT * FROM chapter WHERE course_code = ? AND number = ?");
$stmt->bind_param("ii", $course_code, $chapter_number);
$stmt->execute();
$chapterResult = $stmt->get_result();

if ($chapterResult->num_rows === 0 && $chapter_number <= $total_chapters) {
    $defaultTitle = "Chapter " . $chapter_number;
    $emptyContent = "";
    $emptyVideo = "";

    $insertStmt = $conn->prepare("INSERT INTO chapter (course_code, number, title, content, video) VALUES (?, ?, ?, ?, ?)");
    $insertStmt->bind_param("iisss", $course_code, $chapter_number, $defaultTitle, $emptyContent, $emptyVideo);
    $insertStmt->execute();
    $insertStmt->close();

    $chapterTitles[$chapter_number] = $defaultTitle;

    $stmt = $conn->prepare("SELECT * FROM chapter WHERE course_code = ? AND number = ?");
    $stmt->bind_param("ii", $course_code, $chapter_number);
    $stmt->execute();
    $chapterResult = $stmt->get_result();
}

$chapter = $chapterResult->fetch_assoc();
$stmt->close();

if (!$chapter) {
    die("الشابتر غير موجود");
}

$chapterTitles[$chapter_number] = $chapter['title'] ?? ("Chapter " . $chapter_number);
$chapterCodeByNumber[$chapter_number] = (int)($chapter['chapter_code'] ?? 0);

/* الشابترات غير الموجودة في جدول chapter نعطيها عنوان افتراضي */
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

if (!$isTeacher && $student_id > 0 && $chapter_number > $maxUnlockedChapter) {
    header("Location: chapter.php?course=" . urlencode($course_code) . "&chapter=" . urlencode($maxUnlockedChapter) . "&locked=1");
    exit;
}

/* تحديث الفيديو/المحتوى/العنوان للمعلم */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isTeacher) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_video') {
        $video = trim($_POST['video'] ?? '');
        $stmt = $conn->prepare("UPDATE chapter SET video = ? WHERE course_code = ? AND number = ?");
        $stmt->bind_param("sii", $video, $course_code, $chapter_number);
        $stmt->execute();
        $stmt->close();

        header("Location: chapter.php?course=" . urlencode($course_code) . "&chapter=" . urlencode($chapter_number));
        exit;
    }

    if ($action === 'save_content') {
        $content = trim($_POST['content'] ?? '');
        $stmt = $conn->prepare("UPDATE chapter SET content = ? WHERE course_code = ? AND number = ?");
        $stmt->bind_param("sii", $content, $course_code, $chapter_number);
        $stmt->execute();
        $stmt->close();

        header("Location: chapter.php?course=" . urlencode($course_code) . "&chapter=" . urlencode($chapter_number));
        exit;
    }

    if ($action === 'save_title') {
        $title = trim($_POST['title'] ?? '');
        $stmt = $conn->prepare("UPDATE chapter SET title = ? WHERE course_code = ? AND number = ?");
        $stmt->bind_param("sii", $title, $course_code, $chapter_number);
        $stmt->execute();
        $stmt->close();

        header("Location: chapter.php?course=" . urlencode($course_code) . "&chapter=" . urlencode($chapter_number));
        exit;
    }
}

/* إعادة جلب الشابتر بعد أي تحديث */
$stmt = $conn->prepare("SELECT * FROM chapter WHERE course_code = ? AND number = ?");
$stmt->bind_param("ii", $course_code, $chapter_number);
$stmt->execute();
$chapterResult = $stmt->get_result();
$chapter = $chapterResult->fetch_assoc();
$stmt->close();

function convertToEmbed($url) {
    if (empty($url)) return '';

    if (strpos($url, 'youtube.com/watch?v=') !== false) {
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        if (isset($params['v'])) {
            return "https://www.youtube.com/embed/" . $params['v'];
        }
    }

    if (strpos($url, 'youtu.be/') !== false) {
        $path = trim(parse_url($url, PHP_URL_PATH), '/');
        return "https://www.youtube.com/embed/" . $path;
    }

    if (strpos($url, 'youtube.com/embed/') !== false) {
        return $url;
    }

    return $url;
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

$videoEmbed = convertToEmbed($chapter['video'] ?? '');
$nextChapterNumber = $chapter_number + 1;
$currentQuizDone = isQuizDone($chapter_number, $completedQuizMap);
$nextChapterUnlocked = $chapter_number < $total_chapters && canOpenChapter($nextChapterNumber, $isTeacher, $student_id, $maxUnlockedChapter);

$conn->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($chapter['title']); ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Rubik:ital,wght@0,300..900;1,300..900&display=swap">
    <link rel="stylesheet" href="chapter.css">
</head>
<body>

<div class="header">
    <div class="head-div">
        <img class="logo" src="html images/white laptop real.png" alt="">
        <p class="head-logo-p">دورات مجانية</p>
    </div>

    <div class="header-btns">
        <a href="second.php" class="a1"><button class="btn2">الصفحة الرئيسية</button></a>
        <a href="course.php?id=<?php echo urlencode($course_code); ?>" class="a1"><button class="btn2">صفحة الكورس</button></a>
        <a href="#" class="a1"><button class="btn2">مقرراتي</button></a>
        <a href="#" class="a1"><button class="btn2">اتصل بنا</button></a>
    </div>

    <div class="div-signin">
        <?php if (isset($_SESSION['username'])): ?>
            <div class="user-box" dir="rtl">
                <button class="user-iconn">
                    <span class="material-symbols-outlined">person</span>
                </button>

                <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>

                <?php if ($isTeacher): ?>
                    <span class="role-badge">معلم</span>
                <?php else: ?>
                    <span class="role-badge student">طالب</span>
                <?php endif; ?>

                <form action="logout.php" method="post" style="display:inline;">
                    <button class="logout-btn">تسجيل خروج</button>
                </form>
            </div>
        <?php else: ?>
            <a href="signin.php" class="btn5">تسجيل دخول</a>
        <?php endif; ?>
    </div>
</div>

<div class="lecture-page">

    <aside class="chapters-sidebar">
        <div class="sidebar-box">
            <div class="sidebar-heading-wrap">
                <h3>التنقلات</h3>
                <span class="sidebar-chip"><?php echo $total_chapters; ?> شابترات</span>
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
                        <p>أكمل اختبار الشابتر السابق أولًا حتى ينفتح لك الذي بعده.</p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="nav-section-label">محتوى الكورس</div>

            <?php foreach ($chapters as $item): ?>
                <?php
                    $itemNumber = (int)$item['number'];
                    $chapterUnlocked = canOpenChapter($itemNumber, $isTeacher, $student_id, $maxUnlockedChapter);
                    $quizDone = isQuizDone($itemNumber, $completedQuizMap);
                    $chapterActive = $itemNumber === $chapter_number;
                    $chapterHref = "chapter.php?course=" . urlencode($course_code) . "&chapter=" . urlencode($itemNumber);
                    $quizHref = "exam.php?course=" . urlencode($course_code) . "&chapter=" . urlencode($itemNumber);
                ?>
                <div class="nav-group <?php echo $chapterActive ? 'current-group' : ''; ?>">
                    <?php if ($chapterUnlocked): ?>
                        <a class="nav-item chapter-link <?php echo $chapterActive ? 'active' : ''; ?>" href="<?php echo $chapterHref; ?>">
                            <span class="nav-main-text">
                                <small>شابتر <?php echo $itemNumber; ?></small>
                                <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                            </span>
                            <span class="nav-status-pill <?php echo $chapterActive ? 'active-pill' : 'open-pill'; ?>">
                                <?php echo $chapterActive ? 'الحالي' : 'مفتوح'; ?>
                            </span>
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
                        <a class="nav-item quiz-link <?php echo $quizDone ? 'quiz-done' : ''; ?>" href="<?php echo $quizHref; ?>">
                            <span>اختبار الشابتر <?php echo $itemNumber; ?></span>
                            <span class="quiz-state"><?php echo $quizDone ? 'مكتمل' : 'متاح'; ?></span>
                        </a>
                    <?php else: ?>
                        <div class="nav-item quiz-link locked-item quiz-locked">
                            <span>اختبار الشابتر <?php echo $itemNumber; ?></span>
                            <span class="quiz-state">مقفل</span>
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
        </div>
    </aside>

    <main class="lecture-main">

        <?php if (!$isTeacher && $student_id > 0): ?>
            <div class="progress-banner <?php echo $currentQuizDone ? 'success-banner' : ''; ?>">
                <div>
                    <strong>
                        <?php if ($currentQuizDone): ?>
                            ممتاز، اختبار هذا الشابتر مكتمل.
                        <?php else: ?>
                            أنهِ اختبار هذا الشابتر لفتح الذي بعده.
                        <?php endif; ?>
                    </strong>
                    <p>
                        التقدم الحالي: <?php echo $sequentialCompleted; ?> / <?php echo $total_chapters; ?> اختبارات مكتملة
                    </p>
                </div>
                <span class="progress-badge"><?php echo $chapter_number; ?>/<?php echo $total_chapters; ?></span>
            </div>
        <?php endif; ?>

        <div class="lecture-top-grid">
            <section class="video-card">
                <div class="section-head">
                    <h2>المقطع</h2>
                </div>

                <div class="video-box">
                    <?php if (!empty($videoEmbed)): ?>
                        <iframe src="<?php echo htmlspecialchars($videoEmbed); ?>" class="video-frame" frameborder="0" allowfullscreen></iframe>
                    <?php else: ?>
                        <div class="placeholder-box">
                            <span class="material-symbols-outlined">play_circle</span>
                            <p>لم يتم إضافة فيديو بعد</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($isTeacher): ?>
                    <div class="inline-edit-box">
                        <form method="post" class="inline-edit-form">
                            <input type="hidden" name="action" value="save_video">
                            <label>رابط الفيديو</label>
                            <input type="text" name="video" value="<?php echo htmlspecialchars($chapter['video'] ?? ''); ?>" placeholder="ضع رابط يوتيوب هنا">
                            <button type="submit" class="save-btn">تم</button>
                        </form>
                    </div>
                <?php endif; ?>
            </section>

            <section class="chatbot-card">
                <div class="section-head">
                    <h2>المساعد الذكي</h2>
                </div>

                <div id="chapter-chat-wrapper">
                    <div id="chapter-chat"></div>
                </div>
            </section>
        </div>

        <section class="chapter-content-card">
            <div class="section-head">
                <h2><?php echo htmlspecialchars($chapter['title']); ?></h2>
                <span class="chapter-counter">الشابتر <?php echo $chapter_number; ?> من <?php echo $total_chapters; ?></span>
            </div>

            <?php if ($isTeacher): ?>
                <div class="inline-edit-box">
                    <form method="post" class="inline-edit-form">
                        <input type="hidden" name="action" value="save_title">
                        <label>عنوان الشابتر</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($chapter['title']); ?>" placeholder="عنوان الشابتر">
                        <button type="submit" class="save-btn">تم</button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="chapter-content-scroll">
                <?php if (!empty($chapter['content'])): ?>
                    <?php echo nl2br(htmlspecialchars($chapter['content'])); ?>
                <?php else: ?>
                    <div class="placeholder-box">
                        <span class="material-symbols-outlined">description</span>
                        <p>لم يتم إضافة محتوى بعد</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($isTeacher): ?>
                <div class="inline-edit-box">
                    <form method="post" class="inline-edit-form">
                        <input type="hidden" name="action" value="save_content">
                        <label>محتوى الشابتر</label>
                        <textarea name="content" rows="8" placeholder="اكتب محتوى الشابتر هنا"><?php echo htmlspecialchars($chapter['content'] ?? ''); ?></textarea>
                        <button type="submit" class="save-btn">تم</button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="chapter-actions">
                <?php if ($chapter_number > 1): ?>
                    <a href="chapter.php?course=<?php echo urlencode($course_code); ?>&chapter=<?php echo urlencode($chapter_number - 1); ?>" class="action-btn secondary-btn">
                        السابق
                    </a>
                <?php endif; ?>

                <a href="exam.php?course=<?php echo urlencode($course_code); ?>&chapter=<?php echo urlencode($chapter_number); ?>" class="action-btn primary-btn">
                    اختبار الشابتر
                </a>

                <?php if ($chapter_number < $total_chapters): ?>
                    <?php if ($nextChapterUnlocked): ?>
                        <a href="chapter.php?course=<?php echo urlencode($course_code); ?>&chapter=<?php echo urlencode($nextChapterNumber); ?>" class="action-btn primary-btn subtle-btn">
                            الشابتر التالي
                        </a>
                    <?php else: ?>
                        <div class="action-btn disabled-btn">
                            أكمل اختبار هذا الشابتر لفتح التالي
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if ($isTeacher || $student_id <= 0 || $allChapterQuizzesCompleted): ?>
                        <a href="endexam.php?course=<?php echo urlencode($course_code); ?>" class="action-btn final-action-btn">
                            الاختبار النهائي
                        </a>
                    <?php else: ?>
                        <div class="action-btn disabled-btn">
                            أكمل كل اختبارات الشابترات أولًا
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>

    </main>
</div>
<link href="https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css" rel="stylesheet" />

<script type="module">
  import { createChat } from 'https://cdn.jsdelivr.net/npm/@n8n/chat/dist/chat.bundle.es.js';

  createChat({
    webhookUrl: 'https://sulaiman22.app.n8n.cloud/webhook/1cf9b5ad-c416-4aab-9b08-4fcda9cecfff/chat',
    target: '#chapter-chat',
    mode: 'fullscreen',
    loadPreviousSession: true,
    showWelcomeScreen: true,
    metadata: {
      course_code: '<?php echo $course_code; ?>',
      chapter_number: '<?php echo $chapter_number; ?>',
      chapter_title: '<?php echo htmlspecialchars($chapter["title"] ?? "", ENT_QUOTES); ?>'
    },
    initialMessages: [
      'هلا 👋',
      'أنا مساعد هذا الشابتر، اسألني عن الدرس أو عن المطلوب منك فيه.'
    ],
    i18n: {
      en: {
        title: 'مساعد الشابتر',
        subtitle: 'اسأل عن هذا الشابتر أو عن المطلوب منك فيه',
        footer: '',
        getStarted: 'ابدأ',
        inputPlaceholder: 'اكتب سؤالك هنا...'
      }
    }
  });
</script>
</body>
</html>
