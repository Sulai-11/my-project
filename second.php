<html dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Rubik:ital,wght@0,300..900;1,300..900&display=swap" >
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <title>مشروع تخرج</title>
    <link rel="stylesheet" href="second.css">
    <?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $action = $_POST['action'] ?? '';

    $conn = new mysqli('localhost', 'root', '', 'test');
    if ($conn->connect_error) {
        die("Connection Failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    if ($action == "delete_course") {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
        die("غير مصرح لك بحذف الكورس");
    }

    $course_code = $_POST['course_code'] ?? '';

    $stmt = $conn->prepare("DELETE FROM course WHERE course_code = ?");
    $stmt->bind_param("s", $course_code);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: second.php");
        exit;
    } else {
        echo "<p style='color:red'>حدث خطأ أثناء حذف الكورس</p>";
    }

    $stmt->close();
}
    // التسجيل
    if ($action == "signup") {

    $first_name = $_POST['first_name'] ?? '';
    $last_name  = $_POST['last_name'] ?? '';
    $email      = $_POST['email'] ?? '';
    $password   = $_POST['password'] ?? '';

    $completed = 0;
    $level = 1;

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
}

    $conn->close();
}
?>
<?php
$levelText = "";

if (isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
    if ($_SESSION['level'] == 1) {
        $levelText = "مبتدئ";
    } elseif ($_SESSION['level'] == 2) {
        $levelText = "متوسط";
    } elseif ($_SESSION['level'] == 3) {
        $levelText = "متقدم";
    } else {
        $levelText = "غير معروف";
    }
}
?>
    <?php
$conn = new mysqli('localhost', 'root', '', 'test');
if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

$courses = [];
$sql = "SELECT course_code, title, description, course_image FROM course";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
}
?>
</head>

<body>  
     
    <div class="header">  
        
            <div class="head-div">
            <img class ="logo" src="html images/white laptop real.png" >
            <p class="head-logo-p">دورات مجانية</p>
            </div>
            <div class="header-btns">
                <a href="second.php" class="a1"><button class="btn2">الصفحة الرئيسية</button></a>
<div class="a1 specializations-menu-wrapper">
    <button class="btn2" id="specializationsBtn">التخصصات</button>

    <div class="specializations-panel" id="specializationsPanel">
        <div class="specializations-inner">
            <h2>التخصصات</h2>
            <div class="specializations-line"></div>

            <div class="specializations-grid">
                <a href="#" class="specialization-item">علوم الحاسب</a>
                <a href="#" class="specialization-item">الفنون والتصميم</a>
                <a href="#" class="specialization-item">إدارة الأعمال</a>
                <a href="#" class="specialization-item">علم البيانات</a>
                <a href="#" class="specialization-item">التعليم والتدريس</a>
                <a href="#" class="specialization-item">الصحة والطب</a>
                <a href="#" class="specialization-item">الشريعة واصول والدين</a>
                <a href="#" class="specialization-item">الرياضيات</a>
                <a href="#" class="specialization-item">الكيمياء</a>
                <a href="#" class="specialization-item">العلوم</a>
                <a href="#" class="specialization-item">العلوم الاجتماعية</a>
                <a href="#" class="specialization-item">الفيزياء</a>
            </div>
        </div>
    </div>
</div>                <a href="#" class="a1"><button class="btn2">مقرراتي</button></a>  
                <a href="#" class="a1"><button class="btn2">اتصل بنا</button></a>   
            </div>
            
        
        <div class="div-signin">
        <?php if (isset($_SESSION['username'])): ?>

<div class="user-box" dir="rtl">
    <button class="user-iconn"><i class="fi fi-sr-user"></i></button>

    <span class="user-name"><?php echo $_SESSION['username']; ?></span>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
    <span class="user-level">المستوى: <?php echo $levelText; ?></span>
<?php endif; ?>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher'): ?>
        <button id="openCourseBtn" class="createCourse open-modal">إنشاء كورس</button>
    <?php endif; ?>

    <form action="logout.php" method="post" style="display:inline;">
        <button class="logout-btn">تسجيل خروج</button>
    </form>
</div>

<?php else: ?>

<a href="signin.php" class="btn5">تسجيل دخول</a>

<?php endif; ?>

        </div>
        
         <!-- <button class="user-status">
  <span class="material-symbols-outlined user-icon">account_circle</span>
  <span id="level">مبتدئ</span>
         </button> -->

    </div> 
    
    
    <div class="content">
        
        <div class="right-content">
         <b class="content-b1">تعلّم مجاناً</b>
         <br>
         <br>
         <b class="content-b2">اكتشف مكتبة شاملة من الدورات المجانية <br>عالية الجودة لتعلم أهم المقررات الدراسية</b>
         <div class="third-line-content">
             <p>البرمجة و قواعد البيانات والذكاء الاصطناعي و تعلم الخوارزميات</p>
             <p class="second-p">وأكثر</p>
         </div>
        </div>
        <div class="left-content">
            <img src="html images/white laptop real.png"  class="photo-content"/>
        </div>
    </div>
   
    <a href="#" class="course-a" id="showAllCoursesBtn">عرض المزيد</a>
                
           
            <div class="container swiper" id="coursesSliderSection">
                <div class="store">
            
        <div class="store-title">
            <p class="store-p">المقررات</p>
                <h2>مقررات علوم الحاسب</h2>
            </div>
        </div>
                <div class="card-wrapper">
                <ul class="card-list swiper-wrapper">
                    <?php foreach ($courses as $course): ?>
    <li class="card-item swiper-slide">
        <div class="slider-course-box">
            <a href="course.php?id=<?php echo urlencode($course['course_code']); ?>" class="card-link">
                <img src="<?php echo htmlspecialchars($course['course_image']); ?>" alt="Card Image" class="card-image">
                <p class="badge ai"><?php echo htmlspecialchars($course['title']); ?></p>
                <p class="card-title"><?php echo htmlspecialchars($course['description']); ?></p>
                <div class="Ar_Del">
                        <span class="all-course-arrow material-symbols-outlined">arrow_forward</span>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher'): ?>
                            <form method="post" class="all-course-delete-form" onsubmit="return confirm('هل أنت متأكد من حذف هذا الكورس؟');">
                                <input type="hidden" name="action" value="delete_course">
                                <input type="hidden" name="course_code" value="<?php echo htmlspecialchars($course['course_code']); ?>">
                                <button type="submit" class="all-course-delete-btn">×</button>
                            </form>
                        <?php endif; ?>
                    </div>
            </a>

            
        </div>
    </li>
<?php endforeach; ?>

                    
                   
                    
                    <li class="card-item swiper-slide">
                        <a href="#" class="card-link">
                            <img src="html images/الامن السيبراني.jpg" alt="Card Image" class="card-image">
                            <p class="badge cs">الأمن السيبراني</p>
                            <h2 class="card-title">هذا المقرر في جامعة نجران يساعد الطالب على التعرف على طرق حماية الأنظمة والمعلومات من الهجمات والاختراقات الإلكترونية.</h2>
                            <button class="card-button material-symbols-outlined">arrow_forward</button>  
                        </a>
                    </li>
                    <li class="card-item swiper-slide">
                        <a href="#" class="card-link">
                            <img src="html images/database.avif" alt="Card Image" class="card-image">
                            <p class="badge db">قواعد البيانات</p>
                            <h2 class="card-title">هذا المقرر يساعد الطالب على فهم تصميم قواعد البيانات والتعامل معها باستخدام لغات مثل SQL لإدارة البيانات بكفاءة</h2>
                            <button class="card-button material-symbols-outlined">arrow_forward</button>
                        </a>
                    </li>
                    <?php if (!isset($_SESSION['role'])): ?>
<li class="card-item swiper-slide">
    <a href="signin.php" class="card-link2">
        <div class="new-course">
            <p>+</p>
        </div>
        <h3>إضافة كورس جديد</h3>
    </a>
</li>
<?php elseif ($_SESSION['role'] === 'teacher'): ?>
<li class="card-item swiper-slide">
    <a href="#" class="card-link2 open-modal">
        <div class="new-course">
            <p>+</p>
        </div>
        <h3>إضافة كورس جديد</h3>
    </a>
</li>
<?php endif; ?>
                </ul>
                <div class="swiper-pagination"></div>
                <div class="swiper-slide-button swiper-button-prev"></div>
                <div class="swiper-slide-button swiper-button-next"></div>
            </div>
        </div>




        <div id="allCoursesSection" class="all-courses-section">
    <div class="all-courses-header">
        <p class="all-courses-subtitle">كل المقررات</p>
        <h2 class="all-courses-title">جميع الكورسات</h2>
    </div>

    <div class="all-courses-grid">

        <?php foreach ($courses as $course): ?>
            <div class="all-course-card">

                

                <a href="course.php?id=<?php echo urlencode($course['course_code']); ?>" class="all-course-link">
                    <img src="<?php echo htmlspecialchars($course['course_image']); ?>" alt="Card Image" class="all-course-image">
                    <p class="all-course-badge"><?php echo htmlspecialchars($course['title']); ?></p>
                    <p class="all-course-desc"><?php echo htmlspecialchars($course['description']); ?></p>
                    <div class="Ar_Del">
                        <span class="all-course-arrow material-symbols-outlined">arrow_forward</span>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher'): ?>
                            <form method="post" class="all-course-delete-form" onsubmit="return confirm('هل أنت متأكد من حذف هذا الكورس؟');">
                                <input type="hidden" name="action" value="delete_course">
                                <input type="hidden" name="course_code" value="<?php echo htmlspecialchars($course['course_code']); ?>">
                                <button type="submit" class="all-course-delete-btn">×</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>

        <div class="all-course-card">
            <a href="#" class="all-course-link">
                <img src="html images/الامن السيبراني.jpg" alt="Card Image" class="all-course-image">
                <p class="all-course-badge">الأمن السيبراني</p>
                <p class="all-course-desc">هذا المقرر في جامعة نجران يساعد الطالب على التعرف على طرق حماية الأنظمة والمعلومات من الهجمات والاختراقات الإلكترونية.</p>
                <span class="all-course-arrow material-symbols-outlined">arrow_forward</span>
            </a>
        </div>

        <div class="all-course-card">
            <a href="#" class="all-course-link">
                <img src="html images/database.avif" alt="Card Image" class="all-course-image">
                <p class="all-course-badge">قواعد البيانات</p>
                <p class="all-course-desc">هذا المقرر يساعد الطالب على فهم تصميم قواعد البيانات والتعامل معها باستخدام لغات مثل SQL لإدارة البيانات بكفاءة.</p>
                <span class="all-course-arrow material-symbols-outlined">arrow_forward</span>
            </a>
        </div>

        <?php if (!isset($_SESSION['role'])): ?>
            <div class="all-course-card">
                <a href="signin.php" class="all-course-add-card">
                    <div class="all-course-add-icon">+</div>
                    <h3>إضافة كورس جديد</h3>
                </a>
            </div>
        <?php elseif ($_SESSION['role'] === 'teacher'): ?>
            <div class="all-course-card">
                <a href="#" class="all-course-add-card open-modal">
                    <div class="all-course-add-icon">+</div>
                    <h3>إضافة كورس جديد</h3>
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>





        <footer>
            <a href="" class="footer-btn">اتصل بنا</a>
            <img src="html images/white laptop real.png" alt="" width="150px">
            <p>دورات مجانية</p>
        <div>
            <a href="">امكانية الوصول</a>
            <a href="">سياسة الإستخدام</a>
            <a href="">شروط الإستخدام</a>
            <a href="">اسئلة عامة</a>
        </div>
        </footer>
        
    </div>
    <!-- <div id="courseModal" class="modal-overlay">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        
        <form action="upload_course.php" method="post" enctype="multipart/form-data" class="course-modal-form">
            <h2>إنشاء كورس جديد</h2>
            
            <label>اسم الكورس</label>
            <input type="text" name="courseName" required>
            
            <label>وصف الكورس</label>
            <textarea name="courseDescription" rows="4" required></textarea>
            
            <label>صورة الكورس</label>
            <input type="file" name="courseImage" accept="image/*" required>
            
            <button type="submit" class="submit-course">إنشاء الآن</button>
        </form>
    </div>
</div> -->





        <script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>
        <script src="script.js"></script>
        <div id="courseModal" class="modal">
  <form class="modal-box modal-course-form" action="upload_course.php" method="post" enctype="multipart/form-data">
    <h2>إنشاء كورس</h2>

    <div class="course-form-grid">
      <div class="form-group">
        <label>اسم الكورس</label>
        <input type="text" name="title" placeholder="مثال: أساسيات HTML" class="modal-input" required>
      </div>

      <div class="form-group">
        <label>صورة الكورس</label>
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

      <div class="form-group full-width">
        <label>وصف مختصر للكورس</label>
        <textarea name="description" placeholder="اكتب وصفًا مختصرًا وواضحًا عن الكورس" class="modal-input" rows="3" required></textarea>
      </div>

      <div class="form-group full-width">
        <label>محتوى الكورس</label>
        <textarea name="content" placeholder="اكتب محتوى الكورس بالتفصيل" class="modal-input" rows="5" required></textarea>
      </div>

      <div class="form-group full-width">
        <label>سوف يتعلم الطالب</label>
        <textarea name="steps" placeholder="اكتب كل نقطة في سطر مستقل&#10;مثال:&#10;مقدمة عن الإنترنت&#10;أساسيات HTML&#10;التعامل مع الروابط والصور" class="modal-input" rows="5" required></textarea>
      </div>
    </div>

    <div class="modal-buttons">
      <input type="submit" class="submit-course" value="إنشاء">
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
<script>
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
</script>

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
        showAllCoursesBtn.textContent = "عرض المزيد";
        coursesSliderSection.scrollIntoView({ behavior: "smooth", block: "start" });
    }
});
</script>
</body>
</html>