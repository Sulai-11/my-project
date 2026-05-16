<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'project');
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    die("غير مصرح لك");
}

if (!isset($_SESSION['user_id'])) {
    die("بيانات المعلم غير موجودة");
}

if (!isset($_GET['course']) || $_GET['course'] === '') {
    die("رقم الكورس غير موجود");
}

$teacher_id = $_SESSION['user_id'];
$course_code = (int)$_GET['course'];
$message = "";

/* جلب بيانات الكورس الحالي */
$stmt = $conn->prepare("SELECT course_code, title FROM course WHERE course_code = ?");
$stmt->bind_param("i", $course_code);
$stmt->execute();
$courseResult = $stmt->get_result();

if ($courseResult->num_rows !== 1) {
    die("الكورس غير موجود");
}

$course = $courseResult->fetch_assoc();
$stmt->close();

/* جلب شابترات هذا الكورس فقط */
$chapters = [];
$stmt = $conn->prepare("SELECT chapter_code, number, title FROM chapter WHERE course_code = ? ORDER BY number ASC");
$stmt->bind_param("i", $course_code);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $chapters[] = $row;
}

$stmt->close();

/* حفظ السؤال */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_code = (int)($_POST['course_code'] ?? 0);
    $question_scope = $_POST['question_scope'] ?? '';
    $question_type = $_POST['question_type'] ?? '';
    $question_text = trim($_POST['question_text'] ?? '');
    $explanation = trim($_POST['explanation'] ?? '');
    $difficulty = $_POST['difficulty'] ?? 'medium';
    $chapter_code = !empty($_POST['chapter_code']) ? (int)$_POST['chapter_code'] : null;

    if ($question_scope === 'chapter' && empty($chapter_code)) {
        $message = "يجب اختيار الشابتر";
    } elseif ($course_code <= 0 || empty($question_scope) || empty($question_type) || empty($question_text)) {
        $message = "يرجى تعبئة جميع الحقول المطلوبة";
    } else {
        if ($question_type === 'mcq') {
            $option_a = trim($_POST['option_a'] ?? '');
            $option_b = trim($_POST['option_b'] ?? '');
            $option_c = trim($_POST['option_c'] ?? '');
            $option_d = trim($_POST['option_d'] ?? '');
            $correct_option = $_POST['correct_option'] ?? '';

            if (empty($option_a) || empty($option_b) || empty($option_c) || empty($option_d) || empty($correct_option)) {
                $message = "يرجى تعبئة جميع خيارات السؤال وتحديد الإجابة الصحيحة";
            } else {
                $correct_answer = '';
                if ($correct_option === 'A') $correct_answer = $option_a;
                if ($correct_option === 'B') $correct_answer = $option_b;
                if ($correct_option === 'C') $correct_answer = $option_c;
                if ($correct_option === 'D') $correct_answer = $option_d;

                $stmt = $conn->prepare("
                    INSERT INTO question_bank
                    (course_code, chapter_code, question_scope, question_type, question_text, correct_answer, explanation, difficulty, created_by_teacher_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "iissssssi",
                    $course_code,
                    $chapter_code,
                    $question_scope,
                    $question_type,
                    $question_text,
                    $correct_answer,
                    $explanation,
                    $difficulty,
                    $teacher_id
                );

                if ($stmt->execute()) {
                    $question_id = $stmt->insert_id;
                    $stmt->close();

                    $options = [
                        'A' => $option_a,
                        'B' => $option_b,
                        'C' => $option_c,
                        'D' => $option_d
                    ];

                    $stmtOpt = $conn->prepare("
                        INSERT INTO question_option (question_id, option_label, option_text, is_correct)
                        VALUES (?, ?, ?, ?)
                    ");

                    foreach ($options as $label => $text) {
                        $is_correct = ($label === $correct_option) ? 1 : 0;
                        $stmtOpt->bind_param("issi", $question_id, $label, $text, $is_correct);
                        $stmtOpt->execute();
                    }

                    $stmtOpt->close();
                    $message = "تم إضافة السؤال بنجاح";
                } else {
                    $message = "حدث خطأ أثناء حفظ السؤال";
                    $stmt->close();
                }
            }
        }

        elseif ($question_type === 'true_false') {
            $correct_tf = $_POST['correct_tf'] ?? '';

            if ($correct_tf !== 'True' && $correct_tf !== 'False') {
                $message = "يرجى تحديد الإجابة الصحيحة";
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO question_bank
                    (course_code, chapter_code, question_scope, question_type, question_text, correct_answer, explanation, difficulty, created_by_teacher_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "iissssssi",
                    $course_code,
                    $chapter_code,
                    $question_scope,
                    $question_type,
                    $question_text,
                    $correct_tf,
                    $explanation,
                    $difficulty,
                    $teacher_id
                );

                if ($stmt->execute()) {
                    $question_id = $stmt->insert_id;
                    $stmt->close();

                    $stmtOpt = $conn->prepare("
                        INSERT INTO question_option (question_id, option_label, option_text, is_correct)
                        VALUES (?, ?, ?, ?)
                    ");

                    $trueCorrect = ($correct_tf === 'True') ? 1 : 0;
                    $falseCorrect = ($correct_tf === 'False') ? 1 : 0;

                    $label1 = 'A';
                    $text1 = 'True';
                    $stmtOpt->bind_param("issi", $question_id, $label1, $text1, $trueCorrect);
                    $stmtOpt->execute();

                    $label2 = 'B';
                    $text2 = 'False';
                    $stmtOpt->bind_param("issi", $question_id, $label2, $text2, $falseCorrect);
                    $stmtOpt->execute();

                    $stmtOpt->close();
                    $message = "تم إضافة السؤال بنجاح";
                } else {
                    $message = "حدث خطأ أثناء حفظ السؤال";
                    $stmt->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأسئلة</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="header.css">
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:"Cairo",sans-serif;
        }

        body{
            background:#efeeee;
        }

        .page-wrap{
            width:100%;
            min-height:100vh;
            padding:110px 20px 40px;
        }

        .form-box{
            width:75%;
            margin:auto;
            background:white;
            border-radius:24px;
            padding:30px;
            box-shadow:0 10px 24px rgba(0,0,0,0.08);
        }

        .form-box h1{
            color:#2e77c0;
            margin-bottom:25px;
            font-size:30px;
        }

        .msg{
            margin-bottom:18px;
            padding:12px 16px;
            border-radius:12px;
            background:#eef5fb;
            color:#23486b;
            font-weight:700;
        }

        .grid{
            display:grid;
            grid-template-columns:repeat(2, minmax(0,1fr));
            gap:18px 20px;
        }

        .full{
            grid-column:1 / -1;
        }

        .group{
            display:flex;
            flex-direction:column;
            gap:8px;
        }

        .group label{
            font-size:15px;
            font-weight:700;
            color:#36506c;
        }

        .input{
            width:100%;
            border:1px solid #cfd8e3;
            border-radius:12px;
            background:#f9fbfd;
            padding:13px 14px;
            font-size:15px;
            outline:none;
        }

        textarea.input{
            min-height:120px;
            resize:vertical;
        }

        .btn-save{
            margin-top:24px;
            border:none;
            border-radius:14px;
            background:#2e77c0;
            color:white;
            padding:13px 24px;
            font-size:15px;
            font-weight:700;
            cursor:pointer;
        }

        .btn-save:hover{
            background:#255f9b;
        }

        .section-title{
            margin-top:25px;
            margin-bottom:10px;
            font-size:19px;
            color:#2e77c0;
            font-weight:700;
        }

        @media (max-width: 900px){
            .form-box{
                width:95%;
            }

            .grid{
                grid-template-columns:1fr;
            }
        }
        .back-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:10px 18px;
    background:#eef5fb;
    color:#2e77c0;
    text-decoration:none;
    border-radius:10px;
    font-weight:700;
    transition:0.2s;
}
.back-btn:hover{
    background:#dcecff;
}
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="page-wrap">
    <div class="form-box">
        <h1>إدارة الأسئلة</h1>
        <div style="margin-bottom:20px;">
    <a href="course.php?id=<?php echo urlencode($course_code); ?>" class="back-btn">رجوع</a>
</div>
        <?php if (!empty($message)): ?>
            <div class="msg"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="grid">
                <div class="group">
    <label>الكورس الحالي</label>
    <input type="text" class="input" value="<?php echo htmlspecialchars($course['title']); ?>" readonly>
    <input type="hidden" name="course_code" value="<?php echo $course_code; ?>">
</div>

                <div class="group">
                    <label>نوع الاختبار</label>
                    <select name="question_scope" id="question_scope" class="input" required>
                        <option value="">اختر</option>
                        <option value="pre">Pre Exam</option>
                        <option value="chapter">Chapter Exam</option>
                        <option value="end">Final Exam</option>
                    </select>
                </div>

                <div class="group" id="chapterBox" style="display:none;">
                    <label>اختر الشابتر</label>
                    <select name="chapter_code" class="input">
                        <option value="">اختر الشابتر</option>
                        <?php foreach ($chapters as $chapter): ?>
                            <option value="<?php echo $chapter['chapter_code']; ?>">
                                <?php echo 'Chapter ' . $chapter['number'] . ' - ' . htmlspecialchars($chapter['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="group">
                    <label>نوع السؤال</label>
                    <select name="question_type" id="question_type" class="input" required>
                        <option value="">اختر</option>
                        <option value="mcq">اختيارات</option>
                        <option value="true_false">صح / خطأ</option>
                    </select>
                </div>

                <div class="group full">
                    <label>نص السؤال</label>
                    <textarea name="question_text" class="input" required></textarea>
                </div>

                <div class="group">
                    <label>مستوى الصعوبة</label>
                    <select name="difficulty" class="input">
                        <option value="easy">سهل</option>
                        <option value="medium" selected>متوسط</option>
                        <option value="hard">صعب</option>
                    </select>
                </div>

                <div class="group full">
                    <label>شرح أو تفسير الإجابة</label>
                    <textarea name="explanation" class="input"></textarea>
                </div>
            </div>

            <div id="mcqFields" style="display:none;">
                <div class="section-title">خيارات السؤال</div>
                <div class="grid">
                    <div class="group">
                        <label>الخيار A</label>
                        <input type="text" name="option_a" class="input">
                    </div>

                    <div class="group">
                        <label>الخيار B</label>
                        <input type="text" name="option_b" class="input">
                    </div>

                    <div class="group">
                        <label>الخيار C</label>
                        <input type="text" name="option_c" class="input">
                    </div>

                    <div class="group">
                        <label>الخيار D</label>
                        <input type="text" name="option_d" class="input">
                    </div>

                    <div class="group">
                        <label>الإجابة الصحيحة</label>
                        <select name="correct_option" class="input">
                            <option value="">اختر</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="tfFields" style="display:none;">
                <div class="section-title">إجابة صح / خطأ</div>
                <div class="grid">
                    <div class="group">
                        <label>الإجابة الصحيحة</label>
                        <select name="correct_tf" class="input">
                            <option value="">اختر</option>
                            <option value="True">True</option>
                            <option value="False">False</option>
                        </select>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-save">حفظ السؤال</button>
        </form>
    </div>
</div>

<script>
    const questionType = document.getElementById('question_type');
    const mcqFields = document.getElementById('mcqFields');
    const tfFields = document.getElementById('tfFields');
    const questionScope = document.getElementById('question_scope');
    const chapterBox = document.getElementById('chapterBox');

    function toggleQuestionType() {
        if (questionType.value === 'mcq') {
            mcqFields.style.display = 'block';
            tfFields.style.display = 'none';
        } else if (questionType.value === 'true_false') {
            mcqFields.style.display = 'none';
            tfFields.style.display = 'block';
        } else {
            mcqFields.style.display = 'none';
            tfFields.style.display = 'none';
        }
    }

    function toggleScope() {
        if (questionScope.value === 'chapter') {
            chapterBox.style.display = 'block';
        } else {
            chapterBox.style.display = 'none';
        }
    }

    questionType.addEventListener('change', toggleQuestionType);
    questionScope.addEventListener('change', toggleScope);

    toggleQuestionType();
    toggleScope();
</script>

</body>
</html>