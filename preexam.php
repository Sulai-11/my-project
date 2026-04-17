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

/* جلب بيانات الكورس */
$stmt = $conn->prepare("SELECT course_code, title FROM course WHERE course_code = ?");
$stmt->bind_param("i", $course_code);
$stmt->execute();
$courseResult = $stmt->get_result();

if ($courseResult->num_rows !== 1) {
    die("الكورس غير موجود");
}

$course = $courseResult->fetch_assoc();
$stmt->close();

/* جلب أسئلة الـ pre exam */
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
$conn->close();

$totalQuestions = count($questions);
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre Exam</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="exam.css">
</head>
<body>

<button class="open-sidebar-btn">☰</button>

<div id="mySidebar" class="sidebar">
    <a href="javascript:void(0)" class="close-btn">×</a>
    <a href="second.php" class="a-side">الرئيسية</a>
    <a href="course.php?id=<?php echo urlencode($course_code); ?>" class="a-side">صفحة الكورس</a>
    <a href="#" class="a-side">مقرراتي</a>
    <a href="#" class="a-side">اتصل بنا</a>
</div>

<div class="div-examall">
    <p class="exam-name"><?php echo htmlspecialchars($course['title']); ?></p>
    <p class="exam-no" id="ex0">Pre-Exam</p>

    <?php if ($totalQuestions === 0): ?>
        <div class="div-exam" style="text-align:center;">
            <div class="div-quist" style="justify-content:center;">
                <p class="p-quist2" style="text-align:center;">لا توجد أسئلة تمهيدية لهذا الكورس حتى الآن</p>
            </div>
            <div class="exam-done" style="margin-top:30px;">
                <div class="back-exam">
                    <a href="course.php?id=<?php echo urlencode($course_code); ?>">رجوع</a>
                </div>
                <div class="submit-exam">
                    <a href="chapter.php?course=<?php echo urlencode($course_code); ?>&chapter=1">تخطي إلى الشابتر الأول</a>
                </div>
            </div>
        </div>
    <?php else: ?>

    <div class="valid-exam" dir="ltr">
        <?php for ($i = 1; $i <= $totalQuestions; $i++): ?>
            <a href="#ex<?php echo $i; ?>" id="btn-q<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>

    <form action="submit_preexam.php" method="post">
        <input type="hidden" name="course_code" value="<?php echo $course_code; ?>">

        <?php foreach ($questions as $index => $question): ?>
            <div class="div-exam" id="ex<?php echo $index + 1; ?>">
                <div class="div-quist">
                    <p class="p-quist"><?php echo $index + 1; ?></p>
                    <p class="p-quist2"><?php echo htmlspecialchars($question['question_text']); ?></p>
                    <p class="degree-quist">0 / 1</p>
                </div>
                <hr class="exam-hr">

                <div class="form-q" data-question="<?php echo $index + 1; ?>">
                    <?php foreach ($question['options'] as $option): ?>
                        <label class="btn-ch">
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
            </div>
        <?php endforeach; ?>

        <div class="exam-done">
            <div class="back-exam">
                <a href="course.php?id=<?php echo urlencode($course_code); ?>">رجوع</a>
            </div>
            <div class="submit-exam">
                <button type="submit">تقديم</button>
            </div>
        </div>
    </form>

    <?php endif; ?>
</div>

<script>
    const sidebar = document.getElementById('mySidebar');
    const openBtn = document.querySelector('.open-sidebar-btn');
    const closeBtn = document.querySelector('.close-btn');

    function openSidebar() { sidebar.classList.add('open'); }
    function closeSidebar() { sidebar.classList.remove('open'); }

    openBtn.addEventListener('click', openSidebar);
    closeBtn.addEventListener('click', closeSidebar);

    window.addEventListener('click', function(event) {
        if (sidebar.classList.contains('open')) {
            if (!sidebar.contains(event.target) && !openBtn.contains(event.target)) {
                closeSidebar();
            }
        }
    });

    document.querySelectorAll('.form-q').forEach(form => {
        form.addEventListener('change', function() {
            let qNum = form.getAttribute('data-question');
            let btn = document.getElementById('btn-q' + qNum);
            if(btn) btn.classList.add('answered');
        });
    });
</script>

</body>
</html>