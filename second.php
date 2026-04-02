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

    if ($action == "signup") {
        
        $username = $_POST['username'] ?? '';
        $email    = $_POST['email'] ?? '';
        $pass     = $_POST['pass'] ?? '';

        $stmt = $conn->prepare("INSERT INTO signin (username, email, pass) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $pass);

        if ($stmt->execute()) {
            echo "<p style='color:green'>SignUp Successful ✔</p>";
        } else {
            echo "<p style='color:red'>Error: Cannot Sign Up</p>";
        }

        $stmt->close();
    }

    if ($action == "signin") {

    $email = $_POST['email'] ?? '';
    $pass  = $_POST['pass'] ?? '';

    $stmt = $conn->prepare("SELECT username, pass FROM signin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if ($row['pass'] === $pass) {

            // حفظ اسم المستخدم في السيشن
            $_SESSION['username'] = $row['username'];

            header("Location: second.php");
            exit;

        } else {
            echo "<p style='color:red'>Wrong Password ❌</p>";
        }

    } else {
        echo "<p style='color:red'>Email Not Found ❌</p>";
    }

    $stmt->close();
}

    $conn->close();
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
                <a href="#" class="a1"><button class="btn2">التخصصات</button></a>
                <a href="#" class="a1"><button class="btn2">مقرراتي</button></a>  
                <a href="#" class="a1"><button class="btn2">اتصل بنا</button></a>   
                <a href="#" class="a1"><button class="btn2">الدعم الفني</button></a>  
            </div>
            
        
        <div class="div-signin">
        <?php if (isset($_SESSION['username'])): ?>

<div class="user-box" dir="rtl">
<button class="user-iconn"><i class="fi fi-sr-user"></i></button>
    <span class="user-name"><?php echo $_SESSION['username']; ?></span>
    <span class="user-level">مبتدئ</span>
    <button id="openCourseBtn" class="createCourse open-modal">إنشاء كورس</button>
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
    <div class="store">
        
        <div class="store-title">
            <a href="" class="course-a">عرض المزيد</a>
            <div>
                <p class="store-p">المقررات</p>
                <h2>مقررات علوم الحاسب</h2>
            </div>
        </div>
        <div class="container swiper">
            <div class="card-wrapper">
                <ul class="card-list swiper-wrapper">
                    <li class="card-item swiper-slide">
                        <a href="start.html" class="card-link">
                            <img src="html images/iot.jpeg" alt="Card Image" class="card-image">
                            <p class="badge iot">تقنيات الإنترنت</p>
                            <h2 class="card-title">تقنيات الإنترنت هي الأدوات والأنظمة التي تُستخدم لإنشاء وتبادل المعلومات عبر الشبكة العالمية، وتشمل أشياء مثل البروتوكولات (مثل HTTP وTCP/IP)</h2>
                            <button class="card-button material-symbols-outlined">arrow_forward</button>    
                        </a>
                    </li>
                    <li class="card-item swiper-slide">
                        <a href="#" class="card-link">
                            <img src="html images/هندسة البرمجيات.jpg" alt="Card Image" class="card-image">
                            <p class="badge se">هندسة البرمجيات</p>
                            <h2 class="card-title">علم يهتم بتصميم، تطوير البرمجيات بطريقة منظمة وفعّالة لضمان جودتها وتقليل الأخطاء يساعد الطالب على تعلم تطوير وإدارة المشاريع البرمجية </h2>
                            <button class="card-button material-symbols-outlined">arrow_forward</button>  
                        </a>
                    </li>
                    <li class="card-item swiper-slide">
                        <a href="#" class="card-link">
                            <img src="html images/الذكاء الاصطناعي.jpeg" alt="Card Image" class="card-image">
                            <p class="badge ai">الذكاء الاصطناعي</p>
                            <h2 class="card-title">هذا المقرر يهتم بجعل الحاسوب والأنظمة قادرة على التفكير والتعلم واتخاذ القرارات مثل البشر باستخدام الخوارزميات والبيانات</h2>
                            <button class="card-button material-symbols-outlined">arrow_forward</button>
                        </a>
                    </li>
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
                    <li class="card-item swiper-slide">
<?php if (isset($_SESSION['username'])): ?>
    <a href="#" class="card-link2 open-modal">
<?php else: ?>
    <a href="signin.php" class="card-link2">
<?php endif; ?>
        <div class="new-course">
            <p>+</p>
        </div>
        <h3>إضافة كورس جديد</h3>
    </a>
</li>
                </ul>
                <div class="swiper-pagination"></div>
                <div class="swiper-slide-button swiper-button-prev"></div>
                <div class="swiper-slide-button swiper-button-next"></div>
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
  <form class="modal-box" action="upload_course.php" method="post" enctype="multipart/form-data">
  <h2>إنشاء كورس</h2>

    <input type="text" placeholder="اسم الكورس" class="modal-input">
    <textarea placeholder="وصف الكورس" class="modal-input"></textarea>
    <input type="file" accept="image/*" class="modal-input">
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
</body>
</html>