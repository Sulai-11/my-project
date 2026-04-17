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

/* نجيب 30 سؤال من النهائي فقط */
$stmt = $conn->prepare("
    SELECT question_id, question_text, question_type
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
$totalPages = count($questionChunks);
?>
<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final Exam Site</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="exam.css">
    <style>
        .valid-exam {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .valid-exam a {
            padding: 5px 10px;
            text-decoration: none;
            background: #eee;
            color: #333;
            border-radius: 4px;
        }

        .valid-exam a.answered {
            background: #4CAF50;
            color: white;
        }

        .exam-page {
            display: none;
            width: 100%;
        }

        .exam-page.active {
            display: block;
            animation: fadeIn 0.4s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
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
    <p class="exam-no">Final Exam</p>

    <?php if ($totalQuestions === 0): ?>
        <div class="div-exam" style="text-align:center;">
            <div class="div-quist" style="justify-content:center;">
                <p class="p-quist2" style="text-align:center;">لا توجد أسئلة نهائية لهذا الكورس حتى الآن</p>
            </div>

            <div class="exam-done" style="margin-top:30px;">
                <div class="back-exam">
                    <a href="course.php?id=<?php echo urlencode($course_code); ?>">رجوع</a>
                </div>
            </div>
        </div>
    <?php else: ?>

    <form method="POST" action="submit_end_exam.php?course=<?php echo urlencode($course_code); ?>">
        <?php
        $globalQuestionNumber = 1;
        foreach ($questionChunks as $pageIndex => $pageQuestions):
            $pageNumber = $pageIndex + 1;
        ?>
            <div class="exam-page <?php echo $pageNumber === 1 ? 'active' : ''; ?>" id="page<?php echo $pageNumber; ?>">
                <h3 style="text-align: center; color: #555;">الصفحة <?php echo $pageNumber; ?> من <?php echo $totalPages; ?></h3>

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

                        <div class="form-q" data-question="<?php echo $questionNumber; ?>">
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
                        <?php if ($pageNumber > 1): ?>
                            <button type="button" onclick="changePage(<?php echo $pageNumber - 1; ?>)">رجوع</button>
                        <?php else: ?>
                            <a href="course.php?id=<?php echo urlencode($course_code); ?>">رجوع</a>
                        <?php endif; ?>
                    </div>

                    <div class="submit-exam">
                        <?php if ($pageNumber < $totalPages): ?>
                            <button type="button" onclick="changePage(<?php echo $pageNumber + 1; ?>)">التالي</button>
                        <?php else: ?>
                            <button type="submit">تقديم النهائي</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php
            $globalQuestionNumber += count($pageQuestions);
        endforeach;
        ?>
    </form>

    <?php endif; ?>
</div>

<script>
    function changePage(pageNum) {
        document.querySelectorAll('.exam-page').forEach(page => {
            page.classList.remove('active');
        });

        document.getElementById('page' + pageNum).classList.add('active');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

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