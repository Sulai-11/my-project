<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli('localhost', 'root', '', 'project');
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

$completedPoints = 0;
$levelText = "غير معروف";
$levelMin = 0;
$levelMax = 30;
$nextLevelText = "متوسط";
$progressPercent = 0;

if (isset($_SESSION['role']) && $_SESSION['role'] === 'student' && isset($_SESSION['user_id'])) {
    $student_id = (int)$_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT completed FROM student WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $studentResult = $stmt->get_result();

    if ($studentRow = $studentResult->fetch_assoc()) {
        $completedPoints = (int)$studentRow['completed'];
    }
    $stmt->close();

    if ($completedPoints <= 30) {
        $levelText = "مبتدئ";
        $levelMin = 0;
        $levelMax = 30;
        $nextLevelText = "متوسط";
        $progressPercent = ($completedPoints / 30) * 100;
    } elseif ($completedPoints <= 70) {
        $levelText = "متوسط";
        $levelMin = 31;
        $levelMax = 70;
        $nextLevelText = "متقدم";
        $progressPercent = (($completedPoints - 31) / (70 - 31)) * 100;
    } else {
        $levelText = "متقدم";
        $levelMin = 71;
        $levelMax = $completedPoints;
        $nextLevelText = "تم الوصول لأعلى مستوى";
        $progressPercent = 100;
    }

    if ($progressPercent < 0) $progressPercent = 0;
    if ($progressPercent > 100) $progressPercent = 100;
}
?>

<div class="header">  
    <div class="head-div">
        <img class="logo" src="html images/white laptop real.png" alt="">
        <p class="head-logo-p">Edutrack</p>
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
        </div>

        <a href="#" class="a1"><button class="btn2">مقرراتي</button></a>  
        <a href="#" class="a1"><button class="btn2">اتصل بنا</button></a>   
    </div>

    <div class="div-signin">
        <?php if (isset($_SESSION['username'])): ?>
            <div class="user-box" dir="rtl">
                <button class="user-iconn"><i class="fi fi-sr-user"></i></button>

                <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
                    <div class="level-dropdown">
                        <button type="button" class="user-level level-toggle-btn" id="levelToggleBtn">
                            المستوى: <?php echo htmlspecialchars($levelText); ?>
                        </button>

                        <div class="level-popup" id="levelPopup">
                            <div class="level-popup-head">
                                <strong><?php echo htmlspecialchars($levelText); ?></strong>
                                <span><?php echo $completedPoints; ?> نقطة</span>
                            </div>

                            <div class="level-range">
                                <span><?php echo $levelMin; ?></span>
                                <span><?php echo $levelMax; ?></span>
                            </div>

                            <div class="level-bar">
                                <div class="level-bar-fill" id="levelBarFill" data-progress="<?php echo round($progressPercent, 2); ?>"></div>
                            </div>

                            <div class="level-popup-info">
                                <p>تقدمك الحالي: <strong><?php echo $completedPoints; ?></strong></p>
                                <p>المستوى الحالي: <strong><?php echo htmlspecialchars($levelText); ?></strong></p>
                                <p>التالي: <strong><?php echo htmlspecialchars($nextLevelText); ?></strong></p>
                            </div>
                        </div>
                    </div>
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
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const specializationsBtn = document.getElementById("specializationsBtn");
    const specializationsPanel = document.getElementById("specializationsPanel");

    if (specializationsBtn && specializationsPanel) {
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
    }

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
</script>

<?php
$conn->close();
?>