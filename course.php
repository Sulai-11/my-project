<?php
$conn = new mysqli('localhost', 'root', '', 'test');
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

if (!isset($_GET['id'])) {
    die("الكورس غير موجود");
}

$course_id = (int) $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM course WHERE course_code = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("الكورس غير موجود");
}

$course = $result->fetch_assoc();
$stmt->close();

$stepsArray = [];

if (!empty($course['steps'])) {
    $stepsArray = preg_split("/\r\n|\n|\r/", $course['steps']);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
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
    <title><?php echo htmlspecialchars($course['title']); ?></title>
    <link rel="stylesheet" href="start.css">
</head>
<body>

    <div class="header">
        <a href="second.php" class="logo">
            <img src="html images/white laptop real.png" alt="" width="100px">
            <p>دورات مجانية</p>
        </a>
    </div>
    <div class="home-side" dir="ltr">
        <div class="btns-wrapper">
            <a href="" class="side-p-img">
                <img src="html images/pc.png" alt="" width="100%" >
            </a>
        <button class="open-sidebar-btn">☰</button>
        </div>
  <div class="search-wrapper">
        <input type="text" class="search-input" placeholder="اكتب للبحث...">
        <button class="search-btn"><i class="fi-rr-search"></i></button>
  </div>
  <a href="" class="a-side">
    <div class="btns-wrapper">
        <p class="side-p">التخصصات</p>
<button class="head-btns"><i class="fi fi-sr-graduation-cap"></i></button>
    </div>
    </a>
    <a href="" class="a-side">
<div class="btns-wrapper">
    <p class="side-p">المقررات</p>
<button class="head-btns"><i class="fi fi-ss-book-open-cover"></i></button>
</div>
</a>
<a href="" class="a-side">
<div class="btns-wrapper">
    <p class="side-p">مقرراتي</p>
<button class="head-btns"><i class="fi fi-sr-bookmark"></i></button>
</div>
</a>
<a href="" class="a-side">
<div class="btns-wrapper"> 
    <p class="side-p">اسئلة عامة</p>
<button class="head-btns"><i class="fi fi-sr-info"></i></button>
</div>
</a>
<div class="icon-wrapper">
    <a href="#" class="side-user-p"><p>الحساب</p></a>
<button class="user-iconn"><i class="fi fi-sr-user"></i></button>
</div>
 
    </div>
    <div style="background-color: #2d6fb2; height: 650px;" class="class-title">
        <h1><?php echo htmlspecialchars($course['title']); ?></h1>
        <p><?php echo htmlspecialchars($course['description']); ?></p>
        <a href="preexam.php">ابدأ التدريب</a>
    </div>

    <div class="class">
        <img src="<?php echo htmlspecialchars($course['course_image']); ?>" alt="" class="class-img">
        
        <div dir="rtl" class="class-div">
            <img src="html images/calendar.png"  class="class-logo" >
            <h3>المدة</h3>
            <p><?php echo htmlspecialchars($course['weeks']); ?> اسابيع</p>
        </div>
        <hr>

        <div dir="rtl" class="class-div">
            <img src="html images/clock.png"  class="class-logo" >
            <h3>الساعات في الاسبوع</h3>
            <p><?php echo htmlspecialchars($course['time']); ?> ساعات</p>
        </div>
        <hr>

        <div dir="rtl" class="class-div">
            <img src="html images/graduate.png"  class="class-logo">
            <h3>التخصص</h3>
            <a href="" class="class-grad">علوم الحاسب</a>
        </div>
        <hr>

        <div dir="rtl" class="class-div">
            <img src="html images/internet.png"  class="class-logo" >
            <h3>اللغة</h3>
            <p>انجليزية</p>
        </div>
        <hr>

        <div dir="rtl" class="class-div">
            <img src="html images/television.png"  class="class-logo" >
            <h3>المنصة</h3>
            <p>YouTube</p>
        </div>
        <hr>

        <div dir="rtl" class="class-topic">
            <img src="html images/book.png"  class="class-logo" >
            <h3>مواضيع اخرى</h3>
            <div>
                <a href="#">علم البيانات</a>
                <a href="#">الذكاء الاصطناعي</a>
                <a href="#">البرمجة</a>
            </div>
        </div>
        <div dir="rtl"  class="class-f">
            <img src="html images/white laptop real.png" alt="" >
            <h4>Edutrack</h4>
        </div>
    </div>
    <div class="bar" dir="rtl">
        <a href="">الصفحة الرئيسية</a>
        <a href="">التخصصات</a>
        <a href="">المقررات</a>
        <a href="">مقرراتي</a>
        <a href="">اسئلة عامة</a>
    </div>
    <div dir="rtl" class="content"> 
        <h3>سوف تتعلم الآتي</h3>
        <ul>
            <?php foreach ($stepsArray as $step): ?>
                <li><?php echo htmlspecialchars(trim($step)); ?></li>
            <?php endforeach; ?>
        </ul>

        <a href="preexam.php">ابدأ التدريب</a>

        <h3 class="h3-2">محتوى الكورس</h3>
        <p>
            <?php echo nl2br(htmlspecialchars($course['content'])); ?>
        </p>

        <a href="preexam.php" class="a-2">ابدأ التدريب</a>
    </div>
     <script src="start.js"></script>
</body>
</html>