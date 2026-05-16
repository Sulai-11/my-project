<html dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Rubik:ital,wght@0,300..900;1,300..900&display=swap" >
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css" rel="stylesheet" />
    <title>مشروع تخرج</title>
    <link rel="stylesheet" href="second.css">
    <link rel="stylesheet" href="header.css">
    <?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $action = $_POST['action'] ?? '';

    $conn = new mysqli('localhost', 'root', '', 'project');
    if ($conn->connect_error) {
        die("Connection Failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    if ($action == "delete_course") {

    if (!isset($_SESSION['role'], $_SESSION['user_id'])) {
        die("غير مصرح لك بتنفيذ هذا الإجراء");
    }

    $course_code = (int)($_POST['course_code'] ?? 0);

    if ($course_code <= 0) {
        die("رقم المهارة غير صحيح");
    }

    /*
    |--------------------------------------------------------------------------
    | Admin: حذف مباشر بدون طلب
    |--------------------------------------------------------------------------
    */
    if ($_SESSION['role'] === 'admin') {

        $stmt = $conn->prepare("SELECT course_code FROM course WHERE course_code = ?");
        $stmt->bind_param("i", $course_code);
        $stmt->execute();
        $courseResult = $stmt->get_result();

        if ($courseResult->num_rows !== 1) {
            die("المهارة غير موجودة أو تم حذفها مسبقًا");
        }

        $stmt->close();

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("DELETE pe FROM pre_exam pe INNER JOIN chapter ch ON pe.chapter_code = ch.chapter_code WHERE ch.course_code = ?");
            $stmt->bind_param("i", $course_code);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE cq FROM chapter_quiz cq INNER JOIN chapter ch ON cq.chapter_code = ch.chapter_code WHERE ch.course_code = ?");
            $stmt->bind_param("i", $course_code);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM certificate WHERE course_code = ?");
            $stmt->bind_param("i", $course_code);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM end_exam WHERE course_code = ?");
            $stmt->bind_param("i", $course_code);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM student_course WHERE course_code = ?");
            $stmt->bind_param("i", $course_code);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM course_request WHERE course_code = ?");
            $stmt->bind_param("i", $course_code);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM chapter WHERE course_code = ?");
            $stmt->bind_param("i", $course_code);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM course WHERE course_code = ?");
            $stmt->bind_param("i", $course_code);

            if (!$stmt->execute()) {
                throw new Exception("فشل حذف المهارة");
            }

            $stmt->close();

            $conn->commit();
            $conn->close();

            header("Location: second.php?request=admin_deleted");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            die($e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Teacher: طلب حذف فقط
    |--------------------------------------------------------------------------
    */
    if ($_SESSION['role'] === 'teacher') {

        $teacher_id = (int)$_SESSION['user_id'];

        $stmt = $conn->prepare("
            SELECT course_code
            FROM course
            WHERE course_code = ? AND teacher_id = ?
        ");

        $stmt->bind_param("ii", $course_code, $teacher_id);
        $stmt->execute();
        $courseResult = $stmt->get_result();

        if ($courseResult->num_rows !== 1) {
            die("لا يمكنك طلب حذف مهارة لا تملكها");
        }

        $stmt->close();

        $stmt = $conn->prepare("
            SELECT request_id
            FROM course_request
            WHERE course_code = ?
              AND teacher_id = ?
              AND request_type = 'delete'
              AND status = 'pending'
            LIMIT 1
        ");

        $stmt->bind_param("ii", $course_code, $teacher_id);
        $stmt->execute();
        $pendingResult = $stmt->get_result();

        if ($pendingResult->num_rows > 0) {
            die("يوجد طلب حذف معلق لهذه المهارة بالفعل");
        }

        $stmt->close();

        $payload = json_encode([
            "message" => "طلب حذف مهارة من المعلم",
            "course_code" => $course_code
        ], JSON_UNESCAPED_UNICODE);

        $stmt = $conn->prepare("
            INSERT INTO course_request
            (request_type, course_code, teacher_id, payload, status)
            VALUES ('delete', ?, ?, ?, 'pending')
        ");

        $stmt->bind_param("iis", $course_code, $teacher_id, $payload);

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();

            header("Location: second.php?request=delete_sent");
            exit;
        } else {
            echo "<p style='color:red'>حدث خطأ أثناء إرسال طلب الحذف للمسؤول</p>";
        }

        $stmt->close();
    }

    die("غير مصرح لك بحذف المهارة");
}
    // التسجيل
    if ($action == "signup") {

    $first_name = $_POST['first_name'] ?? '';
    $last_name  = $_POST['last_name'] ?? '';
    $email      = $_POST['email'] ?? '';
    $password   = $_POST['password'] ?? '';
    $emailPattern = "/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.com$/";

if (!preg_match($emailPattern, $email)) {
    die("<p style='color:red'>البريد الإلكتروني غير صحيح. يجب أن يكون مثل example@gmail.com وينتهي بـ .com</p>");
}
    $completed = 0;
    $level = 1;
    $checkStmt = $conn->prepare("
    SELECT email FROM student WHERE email = ?
    UNION
    SELECT email FROM teacher WHERE email = ?
    UNION
    SELECT email FROM admin WHERE email = ?
");
$checkStmt->bind_param("sss", $email, $email, $email);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    die("<p style='color:red'>هذا البريد الإلكتروني مستخدم مسبقًا</p>");
}

$checkStmt->close();
    $stmt = $conn->prepare("INSERT INTO student (first_name, last_name, email, password, completed, level) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssii", $first_name, $last_name, $email, $password, $completed, $level);

    if ($stmt->execute()) {
        echo "<p style='color:green'>SignUp Successful ✔</p>";
    } else {
        echo "<p style='color:red'>Error: Cannot Sign Up</p>";
    }

    $stmt->close();
}

    // تسجيل الدخول
    if ($action == "signin") {

    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $type     = $_POST['login_type'] ?? '';

    // 🔹 إذا اختار الدخول كمعلم
    if ($type === "teacher") {

        $stmt = $conn->prepare("SELECT teacher_id, first_name, last_name, password FROM teacher WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            if ($row['password'] === $password) {
                $_SESSION['user_id'] = $row['teacher_id'];
                $_SESSION['username'] = $row['first_name'] . " " . $row['last_name'];
                $_SESSION['role'] = 'teacher';

                header("Location: second.php");
                exit;
            } else {
                echo "<p style='color:red'>Wrong Password ❌</p>";
            }
        } else {
            echo "<p style='color:red'>Teacher not found ❌</p>";
        }

        $stmt->close();
    }

    // 🔹 إذا اختار الدخول كطالب
    elseif ($type === "student") {

        $stmt = $conn->prepare("SELECT student_id, first_name, last_name, password, level, completed FROM student WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            if ($row['password'] === $password) {
                $_SESSION['user_id'] = $row['student_id'];
                $_SESSION['username'] = $row['first_name'] . " " . $row['last_name'];
                $_SESSION['role'] = 'student';
                $_SESSION['level'] = $row['level'];
                $_SESSION['completed'] = $row['completed'];

                header("Location: second.php");
                exit;
            } else {
                echo "<p style='color:red'>Wrong Password ❌</p>";
            }
        } else {
            echo "<p style='color:red'>Student not found ❌</p>";
        }

        $stmt->close();
    }
    elseif ($type === "admin") {

    $emailPattern = "/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.com$/";

    if (!preg_match($emailPattern, $email)) {
        die("<p style='color:red'>البريد الإلكتروني غير صحيح. يجب أن يكون مثل example@gmail.com وينتهي بـ .com</p>");
    }

    $stmt = $conn->prepare("SELECT admin_id, first_name, last_name, password FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if ($row['password'] === $password) {
            $_SESSION['user_id'] = $row['admin_id'];
            $_SESSION['username'] = $row['first_name'] . " " . $row['last_name'];
            $_SESSION['role'] = 'admin';

            header("Location: admin_requests.php");
            exit;
        } else {
            echo "<p style='color:red'>Wrong Password ❌</p>";
        }
    } else {
        echo "<p style='color:red'>Admin not found ❌</p>";
    }

    $stmt->close();
}
}

    $conn->close();
}
?>
<?php

$conn = new mysqli('localhost', 'root', '', 'project'); if ($conn->connect_error) { die("Connection Failed: " . $conn->connect_error); } $conn->set_charset("utf8mb4");

$courses = [];
$sql = "SELECT course_code, title, description, course_image,teacher_id  FROM course";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
}
$conn->close();
?>
</head>

<body>
    
    <?php if (isset($_GET['request']) && $_GET['request'] === 'add_sent'): ?>
    <div style="
        background:#d1fae5;
        color:#065f46;
        padding:14px 20px;
        margin:15px auto;
        width:90%;
        border-radius:12px;
        text-align:center;
        font-weight:bold;
        font-family:Cairo, Arial;
    ">
        تم إرسال طلب إضافة المهارة إلى المسؤول. ستظهر المهارة بعد الموافقة عليها.
    </div>
<?php endif; ?>

<?php if (isset($_GET['request']) && $_GET['request'] === 'delete_sent'): ?>
    <div style="
        background:#dbeafe;
        color:#1e40af;
        padding:14px 20px;
        margin:15px auto;
        width:90%;
        border-radius:12px;
        text-align:center;
        font-weight:bold;
        font-family:Cairo, Arial;
    ">
        تم إرسال طلب حذف المهارة إلى المسؤول. لن يتم حذفها إلا بعد الموافقة.
    </div>
<?php endif; ?>
<?php if (isset($_GET['request']) && $_GET['request'] === 'update_sent'): ?>
    <div style="
        background:#fef3c7;
        color:#92400e;
        padding:14px 20px;
        margin:15px auto;
        width:90%;
        border-radius:12px;
        text-align:center;
        font-weight:bold;
        font-family:Cairo, Arial;
    ">
        تم إرسال طلب تعديل المهارة إلى المسؤول. لن تظهر التعديلات إلا بعد الموافقة.
    </div>
<?php endif; ?>
<?php if (isset($_GET['request']) && $_GET['request'] === 'admin_deleted'): ?>
    <div style="
        background:#fee2e2;
        color:#991b1b;
        padding:14px 20px;
        margin:15px auto;
        width:90%;
        border-radius:12px;
        text-align:center;
        font-weight:bold;
        font-family:Cairo, Arial;
    ">
        تم حذف المهارة مباشرة بواسطة المسؤول.
    </div>
<?php endif; ?>
<?php if (isset($_GET['request']) && $_GET['request'] === 'admin_created'): ?>
    <div style="
        background:#d1fae5;
        color:#065f46;
        padding:14px 20px;
        margin:15px auto;
        width:90%;
        border-radius:12px;
        text-align:center;
        font-weight:bold;
        font-family:Cairo, Arial;
    ">
        تم إنشاء المهارة مباشرة بواسطة المسؤول.
    </div>
<?php endif; ?>
     <?php include 'header.php'; ?>





    <div class="content">
        
        <div class="right-content">
         <b class="content-b1">تعلّم مجاناً</b>
         <br>
         <br>
         <b class="content-b2">اكتشف مكتبة شاملة من الدورات المجانية <br>عالية الجودة لتعلم أهم المهارات</b>
         <div class="third-line-content">
             <p> توفر المنصة مهارات رقمية وتطبيقية تساعد الطلاب والمتعلمين في المملكة العربية السعودية
        على تطوير قدراتهم بما يتوافق مع احتياجات سوق العمل، التحول الرقمي، ورؤية السعودية 2030.</p>
             
         </div>
        </div>
        <div class="left-content">
            <img src="html images/white laptop real.png"  class="photo-content"/>
        </div>
    </div>
   
    <a href="#" class="course-a" id="showAllCoursesBtn">عرض المزيد</a>
                

    <div class="container" id="coursesSliderSection">

    <div class="store">
        <div class="store-title">
            <p class="store-p">المهارات</p>
            <h2>مهارات علوم الحاسب</h2>
        </div>
    </div>

    <div class="card-wrapper swiper">

        <ul class="card-list swiper-wrapper">

            <?php foreach ($courses as $course): ?>
                <li class="card-item swiper-slide">

                    <div class="slider-course-box course-card-shell">

                        <a href="course.php?id=<?php echo urlencode($course['course_code']); ?>" class="card-link course-main-link">

                            <img 
                                src="<?php echo htmlspecialchars($course['course_image']); ?>" 
                                alt="Card Image" 
                                class="card-image"
                            >

                            <p class="badge ai">
                                <?php echo htmlspecialchars($course['title']); ?>
                            </p>

                            <div class="skill-desc-wrap">
    <p class="card-title skill-desc-text">
        <?php echo htmlspecialchars($course['description']); ?>
    </p>
</div>

                            <div class="course-enter-row">
                                <span class="course-enter-text">ابدأ المهارة</span>
                                <span class="all-course-arrow material-symbols-outlined">arrow_forward</span>
                            </div>

                        </a>

                        <?php if (
                            isset($_SESSION['role'], $_SESSION['user_id']) &&
                            (
                                ($_SESSION['role'] === 'teacher' && (int)$_SESSION['user_id'] === (int)$course['teacher_id']) ||
                                $_SESSION['role'] === 'admin'
                            )
                        ): ?>

                            <div class="course-manage-actions">

                                <?php if ($_SESSION['role'] === 'teacher'): ?>

                                    <a 
                                        href="edit_skill_request.php?course=<?php echo urlencode($course['course_code']); ?>" 
                                        class="course-action-btn edit-action-btn"
                                    >
                                        طلب تعديل
                                    </a>

                                    <form 
                                        method="post" 
                                        class="course-action-form" 
                                        onsubmit="return confirm('سيتم إرسال طلب حذف هذه المهارة إلى المسؤول. هل تريد المتابعة؟');"
                                    >
                                        <input type="hidden" name="action" value="delete_course">
                                        <input 
                                            type="hidden" 
                                            name="course_code" 
                                            value="<?php echo htmlspecialchars($course['course_code']); ?>"
                                        >
                                        <button type="submit" class="course-action-btn delete-action-btn">
                                            طلب حذف
                                        </button>
                                    </form>

                                <?php endif; ?>

                                <?php if ($_SESSION['role'] === 'admin'): ?>

                                    <form 
                                        method="post" 
                                        class="course-action-form" 
                                        onsubmit="return confirm('أنت مسؤول النظام. سيتم حذف هذه المهارة مباشرة بدون طلب. هل أنت متأكد؟');"
                                    >
                                        <input type="hidden" name="action" value="delete_course">
                                        <input 
                                            type="hidden" 
                                            name="course_code" 
                                            value="<?php echo htmlspecialchars($course['course_code']); ?>"
                                        >
                                        <button type="submit" class="course-action-btn delete-action-btn">
                                            حذف
                                        </button>
                                    </form>

                                <?php endif; ?>

                            </div>

                        <?php endif; ?>

                    </div>

                </li>
            <?php endforeach; ?>


            <?php if (!isset($_SESSION['role'])): ?>

                <li class="card-item swiper-slide">
                    <a href="signin.php" class="card-link2">
                        <div class="new-course">
                            <p>+</p>
                        </div>
                        <h3>إضافة مهارة جديدة</h3>
                    </a>
                </li>

            <?php elseif ($_SESSION['role'] === 'teacher' || $_SESSION['role'] === 'admin'): ?>

                <li class="card-item swiper-slide">
                    <a href="#" class="card-link2 open-modal">
                        <div class="new-course">
                            <p>+</p>
                        </div>
                        <h3>إضافة مهارة جديدة</h3>
                    </a>
                </li>

            <?php endif; ?>

        </ul>

        <div class="swiper-pagination"></div>
        

    </div>
    <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>

</div>

        <div id="allCoursesSection" class="all-courses-section">
    <div class="all-courses-header">
        <p class="all-courses-subtitle">المهارات</p>
        <h2 class="all-courses-title">مهارات علوم الحاسب</h2>
    </div>

    <div class="all-courses-grid">

        <?php foreach ($courses as $course): ?>
            <div class="all-course-card course-card-shell">

    <a href="course.php?id=<?php echo urlencode($course['course_code']); ?>" class="all-course-link course-main-link">
        <img src="<?php echo htmlspecialchars($course['course_image']); ?>" alt="Card Image" class="all-course-image">

        <p class="all-course-badge"><?php echo htmlspecialchars($course['title']); ?></p>

        <div class="skill-desc-wrap">
    <p class="card-title skill-desc-text">
        <?php echo htmlspecialchars($course['description']); ?>
    </p>
</div>

        <div class="course-enter-row">
            <span class="course-enter-text">ابدأ المهارة</span>
            <span class="all-course-arrow material-symbols-outlined">arrow_forward</span>
        </div>
    </a>

    <?php if (
        isset($_SESSION['role'], $_SESSION['user_id']) &&
        (
            ($_SESSION['role'] === 'teacher' && (int)$_SESSION['user_id'] === (int)$course['teacher_id']) ||
            $_SESSION['role'] === 'admin'
        )
    ): ?>
        <div class="course-manage-actions">

            <?php if ($_SESSION['role'] === 'teacher'): ?>
                <a 
                    href="edit_skill_request.php?course=<?php echo urlencode($course['course_code']); ?>" 
                    class="course-action-btn edit-action-btn"
                >
                    طلب تعديل
                </a>

                <form method="post" class="course-action-form" onsubmit="return confirm('سيتم إرسال طلب حذف هذه المهارة إلى المسؤول. هل تريد المتابعة؟');">
                    <input type="hidden" name="action" value="delete_course">
                    <input type="hidden" name="course_code" value="<?php echo htmlspecialchars($course['course_code']); ?>">
                    <button type="submit" class="course-action-btn delete-action-btn">طلب حذف</button>
                </form>
            <?php endif; ?>

            <?php if ($_SESSION['role'] === 'admin'): ?>
                <form method="post" class="course-action-form" onsubmit="return confirm('أنت مسؤول النظام. سيتم حذف هذه المهارة مباشرة بدون طلب. هل أنت متأكد؟');">
                    <input type="hidden" name="action" value="delete_course">
                    <input type="hidden" name="course_code" value="<?php echo htmlspecialchars($course['course_code']); ?>">
                    <button type="submit" class="course-action-btn delete-action-btn">حذف</button>
                </form>
            <?php endif; ?>

        </div>
    <?php endif; ?>

</div>
        <?php endforeach; ?>

       

        <div class="all-course-card">
            <a href="#" class="all-course-link">
                <img src="html images/database.avif" alt="Card Image" class="all-course-image">
                <p class="all-course-badge">قواعد البيانات</p>
                <p class="all-course-desc">هذا المهارة يساعد الطالب على فهم تصميم قواعد البيانات والتعامل معها باستخدام لغات مثل SQL لإدارة البيانات بكفاءة.</p>
                <span class="all-course-arrow material-symbols-outlined">arrow_forward</span>
            </a>
        </div>

        <?php if (!isset($_SESSION['role'])): ?>
            <div class="all-course-card">
                <a href="signin.php" class="all-course-add-card">
                    <div class="all-course-add-icon">+</div>
                    <h3>إضافة مهارة جديدة</h3>
                </a>
            </div>
        <?php elseif ($_SESSION['role'] === 'teacher' || $_SESSION['role'] === 'admin'): ?>
            <div class="all-course-card">
                <a href="#" class="all-course-add-card open-modal">
                    <div class="all-course-add-icon">+</div>
                    <h3>إضافة مهارة جديدة</h3>
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>





        <footer>
            <a href="" class="footer-btn">اتصل بنا</a>
            <img src="html images/white laptop real.png" alt="" width="150px">
            <p>Edutrack</p>
        <div>
            <a href="">امكانية الوصول</a>
            <a href="">سياسة الإستخدام</a>
            <a href="">شروط الإستخدام</a>
            <a href="">اسئلة عامة</a>
        </div>
        </footer>
        
    </div>

        <script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>
        <script src="script.js"></script>
        <div id="courseModal" class="modal">
  <form class="modal-box modal-course-form" action="upload_course.php" method="post" enctype="multipart/form-data">
<h2>
    <?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'إنشاء مهارة مباشرة' : 'طلب إضافة مهارة'; ?>
</h2>

    <div class="course-form-grid">
      <div class="form-group">
<label>اسم المهارة</label>
        <input type="text" name="title" placeholder="مثال: أساسيات HTML" class="modal-input" required>
      </div>

      <div class="form-group">
        <label>صورة المهارة</label>
        <input type="file" name="course_image" accept="image/*" class="modal-input" required>
      </div>

      <div class="form-group">
        <label>عدد الأسابيع</label>
        <input type="number" name="weeks" placeholder="مثال: 3" class="modal-input" required>
      </div>

      <div class="form-group">
        <label>عدد الساعات</label>
        <input type="number" name="time" placeholder="مثال: 12" class="modal-input" required>
      </div>

        <div class="form-group">
<label>عدد الوحدات المهارية</label>
             <input type="number" name="total_chapters" min="1" max="7" placeholder="مثال: 5" class="modal-input" required>
        </div>

      <div class="form-group full-width">
<label>وصف مختصر للمهارة</label>
        <textarea name="description" placeholder="اكتب وصفًا مختصرًا وواضحًا عن المهارة" class="modal-input" rows="3" required></textarea>
      </div>

      <div class="form-group full-width">
        <label>محتوى المهارة</label>
        <textarea name="content" placeholder="اكتب محتوى المهارة بالتفصيل" class="modal-input" rows="5" required></textarea>
      </div>

      <div class="form-group full-width">
        <label>سوف يتعلم الطالب</label>
        <textarea name="steps" placeholder="اكتب كل نقطة في سطر مستقل&#10;مثال:&#10;مقدمة عن الإنترنت&#10;أساسيات HTML&#10;التعامل مع الروابط والصور" class="modal-input" rows="5" required></textarea>
      </div>
    </div>

    <div class="modal-buttons">
      <input 
    type="submit" 
    class="submit-course" 
    value="<?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'إنشاء المهارة مباشرة' : 'إرسال الطلب للمسؤول'; ?>"
>
      <button type="button" id="closeCourseBtn">إلغاء</button>
    </div>
  </form>
</div>

<script>
const openBtns = document.querySelectorAll(".open-modal");
const closeBtn = document.getElementById("closeCourseBtn");
const modal = document.getElementById("courseModal");

openBtns.forEach(btn => {
    btn.onclick = function(e) {
        e.preventDefault();
        modal.style.display = "flex";
        setTimeout(() => modal.classList.add("show"), 10);
    }
});

closeBtn.onclick = function() {
    modal.classList.remove("show");
    setTimeout(() => modal.style.display = "none", 300);
}
</script>
<!-- <script>
const specializationsBtn = document.getElementById("specializationsBtn");
const specializationsPanel = document.getElementById("specializationsPanel");

specializationsBtn.addEventListener("click", function(e) {
    e.preventDefault();
    e.stopPropagation();
    specializationsPanel.classList.toggle("show");
});

specializationsPanel.addEventListener("click", function(e) {
    e.stopPropagation();
});

document.addEventListener("click", function() {
    specializationsPanel.classList.remove("show");
});
</script> -->

<script>
const showAllCoursesBtn = document.getElementById("showAllCoursesBtn");
const allCoursesSection = document.getElementById("allCoursesSection");
const coursesSliderSection = document.getElementById("coursesSliderSection");

showAllCoursesBtn.addEventListener("click", function(e) {
    e.preventDefault();

    const isShown = allCoursesSection.classList.contains("show");

    if (!isShown) {
        allCoursesSection.classList.add("show");
        coursesSliderSection.style.display = "none";
        showAllCoursesBtn.textContent = "عرض السلايدر";
        allCoursesSection.scrollIntoView({ behavior: "smooth", block: "start" });
    } else {
        allCoursesSection.classList.remove("show");
        coursesSliderSection.style.display = "block";

setTimeout(function () {
    if (window.coursesSwiper) {
        window.coursesSwiper.update();
    }
}, 100);
        showAllCoursesBtn.textContent = "عرض المزيد";
        coursesSliderSection.scrollIntoView({ behavior: "smooth", block: "start" });
    }
});
</script>
<div id="n8n-chat"></div>

<script type="module">
  import { createChat } from 'https://cdn.jsdelivr.net/npm/@n8n/chat/dist/chat.bundle.es.js';

  createChat({
    webhookUrl: 'https://sulaiman22.app.n8n.cloud/webhook/1cf9b5ad-c416-4aab-9b08-4fcda9cecfff/chat',
    target: '#n8n-chat',
    mode: 'window',
    showWelcomeScreen: true,
    loadPreviousSession: true,
    defaultLanguage: 'en',
    initialMessages: [
      'هلا 👋',
      'أنا مساعد Edutrack، كيف أقدر أخدمك؟'
    ],
    i18n: {
      en: {
        title: 'المساعد الذكي',
       subtitle: 'اسأل عن المهارات، الوحدات المهارية، أو خطوات التعلم',
        footer: '',
        getStarted: 'ابدأ المحادثة',
        inputPlaceholder: 'اكتب سؤالك هنا...'
      }
    }
  });
</script>
<!-- <script>
document.addEventListener("DOMContentLoaded", function () {
    const levelBtn = document.getElementById("levelToggleBtn");
    const levelPopup = document.getElementById("levelPopup");
    const levelBarFill = document.getElementById("levelBarFill");

    if (levelBarFill) {
        const progress = parseFloat(levelBarFill.dataset.progress || 0);
        setTimeout(() => {
            levelBarFill.style.width = progress + "%";
        }, 150);
    }

    if (levelBtn && levelPopup) {
        levelBtn.addEventListener("click", function (e) {
            e.stopPropagation();
            levelPopup.classList.toggle("show");
        });

        levelPopup.addEventListener("click", function (e) {
            e.stopPropagation();
        });

        document.addEventListener("click", function () {
            levelPopup.classList.remove("show");
        });
    }
});
</script> -->
</body>
</html>