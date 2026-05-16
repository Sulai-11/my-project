<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />

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
        
        <a href="#" class="a1"><button class="btn2">مهاراتي</button></a>  
           
    </div>

    <div class="div-signin">
    <?php if (isset($_SESSION['username'])): ?>

        <?php
            $roleLabel = "مستخدم";

            if (isset($_SESSION['role'])) {
                if ($_SESSION['role'] === 'admin') {
                    $roleLabel = "مسؤول النظام";
                } elseif ($_SESSION['role'] === 'teacher') {
                    $roleLabel = "معلم";
                } elseif ($_SESSION['role'] === 'student') {
                    $roleLabel = "طالب";
                }
            }
        ?>

        <div class="user-menu-wrapper" dir="rtl">
            <button type="button" class="user-menu-toggle" id="userMenuToggle">
                <span class="user-menu-avatar">
                    <i class="fi fi-sr-user"></i>
                </span>

                <span class="user-menu-info">
                    <span class="user-menu-name">
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </span>
                    <span class="user-menu-role">
                        <?php echo htmlspecialchars($roleLabel); ?>
                    </span>
                </span>

                <span class="material-symbols-outlined user-menu-arrow">
                    keyboard_arrow_down
                </span>
            </button>

            <div class="user-menu-dropdown" id="userMenuDropdown">

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>

                    <div class="student-level-card">
                        <div class="student-level-head">
                            <span>المستوى الحالي</span>
                            <strong><?php echo htmlspecialchars($levelText); ?></strong>
                        </div>

                        <div class="student-points">
                            <?php echo (int)$completedPoints; ?> نقطة
                        </div>

                        <div class="level-range">
                            <span><?php echo (int)$levelMin; ?></span>
                            <span><?php echo (int)$levelMax; ?></span>
                        </div>

                        <div class="level-bar">
                            <div 
                                class="level-bar-fill" 
                                id="levelBarFill" 
                                data-progress="<?php echo round($progressPercent, 2); ?>">
                            </div>
                        </div>

                        <div class="student-next-level">
                            التالي: <?php echo htmlspecialchars($nextLevelText); ?>
                        </div>
                    </div>

                    <a href="my_certificates.php" class="user-menu-item">
                        <span class="material-symbols-outlined">workspace_premium</span>
                        شهاداتي
                    </a>

                <?php endif; ?>


                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'teacher'): ?>

                    <button type="button" class="user-menu-item open-modal">
                        <span class="material-symbols-outlined">add_circle</span>
                        طلب إضافة مهارة
                    </button>

                <?php endif; ?>


                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>

                    <button type="button" class="user-menu-item open-modal">
                        <span class="material-symbols-outlined">add_circle</span>
                        إنشاء مهارة مباشرة
                    </button>

                    <a href="admin_requests.php" class="user-menu-item">
                        <span class="material-symbols-outlined">rule</span>
                        لوحة طلبات المعلمين
                    </a>

                <?php endif; ?>


                <div class="user-menu-divider"></div>

                <form action="logout.php" method="post">
                    <button type="submit" class="user-menu-item logout-menu-item">
                        <span class="material-symbols-outlined">logout</span>
                        تسجيل خروج
                    </button>
                </form>
            </div>
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

            const userMenuDropdown = document.getElementById("userMenuDropdown");
            if (userMenuDropdown) {
                userMenuDropdown.classList.remove("show");
            }
        });

        specializationsPanel.addEventListener("click", function(e) {
            e.stopPropagation();
        });
    }

    const userMenuToggle = document.getElementById("userMenuToggle");
    const userMenuDropdown = document.getElementById("userMenuDropdown");
    const levelBarFill = document.getElementById("levelBarFill");

    if (levelBarFill) {
        const progress = parseFloat(levelBarFill.dataset.progress || 0);
        setTimeout(() => {
            levelBarFill.style.width = progress + "%";
        }, 150);
    }

    if (userMenuToggle && userMenuDropdown) {
        userMenuToggle.addEventListener("click", function(e) {
            e.preventDefault();
            e.stopPropagation();

            userMenuDropdown.classList.toggle("show");

            if (specializationsPanel) {
                specializationsPanel.classList.remove("show");
            }
        });

        userMenuDropdown.addEventListener("click", function(e) {
            e.stopPropagation();
        });
    }

    document.addEventListener("click", function() {
        if (specializationsPanel) {
            specializationsPanel.classList.remove("show");
        }

        if (userMenuDropdown) {
            userMenuDropdown.classList.remove("show");
        }
    });
});
</script>

<?php
$conn->close();
?>