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
$chapter_number = (int) $_GET['chapter'];

if ($chapter_number < 1) {
    $chapter_number = 1;
}

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

if ($total_chapters < 1) {
    $total_chapters = 1;
}

if ($total_chapters > 7) {
    $total_chapters = 7;
}

if ($chapter_number > $total_chapters) {
    header("Location: endexam.php?course=" . urlencode($course_code));
    exit;
}

/* إذا الشابتر غير موجود أنشئه تلقائيًا */
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

    $stmt = $conn->prepare("SELECT * FROM chapter WHERE course_code = ? AND number = ?");
    $stmt->bind_param("ii", $course_code, $chapter_number);
    $stmt->execute();
    $chapterResult = $stmt->get_result();
}

$chapter = $chapterResult->fetch_assoc();
$stmt->close();

/* تحديث الفيديو */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['role']) && $_SESSION['role'] === 'teacher') {

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

/* إعادة جلب الشابتر بعد التحديث */
$stmt = $conn->prepare("SELECT * FROM chapter WHERE course_code = ? AND number = ?");
$stmt->bind_param("ii", $course_code, $chapter_number);
$stmt->execute();
$chapterResult = $stmt->get_result();
$chapter = $chapterResult->fetch_assoc();
$stmt->close();

/* كل الشابترات لهذا الكورس */
$chapters = [];
$stmt = $conn->prepare("SELECT number, title FROM chapter WHERE course_code = ? ORDER BY number ASC");
$stmt->bind_param("i", $course_code);
$stmt->execute();
$listResult = $stmt->get_result();

while ($row = $listResult->fetch_assoc()) {
    $chapters[] = $row;
}
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

$videoEmbed = convertToEmbed($chapter['video'] ?? '');

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

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher'): ?>
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
            <h3>التنقلات</h3>
            <a class="nav-item" href="second.php">الصفحة الرئيسية</a>
            <a class="nav-item" href="course.php?id=<?php echo urlencode($course_code); ?>">صفحة الكورس</a>

            <div class="nav-divider"></div>

            <?php foreach ($chapters as $item): ?>
    <a class="nav-item chapter-link <?php echo ((int)$item['number'] === $chapter_number) ? 'active' : ''; ?>"
       href="chapter.php?course=<?php echo urlencode($course_code); ?>&chapter=<?php echo urlencode($item['number']); ?>">
        <?php echo htmlspecialchars($item['title']); ?>
    </a>

    <a class="nav-item quiz-link"
       href="exam.php?course=<?php echo urlencode($course_code); ?>&chapter=<?php echo urlencode($item['number']); ?>">
        <?php echo 'كويز ' . htmlspecialchars($item['title']); ?>
    </a>
<?php endforeach; ?>
        </div>
    </aside>

    <main class="lecture-main">

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

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher'): ?>
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
            </div>

            <p style="margin-bottom:15px; color:#5d738d; font-weight:700;">
                الشابتر <?php echo $chapter_number; ?> من <?php echo $total_chapters; ?>
            </p>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher'): ?>
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

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher'): ?>
                <div class="inline-edit-box">
                    <form method="post" class="inline-edit-form">
                        <input type="hidden" name="action" value="save_content">
                        <label>محتوى الشابتر</label>
                        <textarea name="content" rows="8" placeholder="اكتب محتوى الشابتر هنا"><?php echo htmlspecialchars($chapter['content'] ?? ''); ?></textarea>
                        <button type="submit" class="save-btn">تم</button>
                    </form>
                </div>
            <?php endif; ?>

            <<div style="margin-top:20px; display:flex; gap:10px; flex-wrap:wrap;">
    <?php if ($chapter_number > 1): ?>
        <a href="chapter.php?course=<?php echo urlencode($course_code); ?>&chapter=<?php echo urlencode($chapter_number - 1); ?>" class="save-btn" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">
            السابق
        </a>
    <?php endif; ?>

    <?php if ($chapter_number < $total_chapters): ?>
        <a href="exam.php?course=<?php echo urlencode($course_code); ?>&chapter=<?php echo urlencode($chapter_number); ?>" class="save-btn" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">
            اختبار الشابتر
        </a>
    <?php else: ?>
        <a href="endexam.php?course=<?php echo urlencode($course_code); ?>" class="save-btn" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">
            الاختبار النهائي
        </a>
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
      'أنا مساعد هذا الشابتر، اسألني عن الدرس أو المحتوى.'
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