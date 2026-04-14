 <!DOCTYPE html>
 <html dir="ltr">
 <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&family=Quicksand:wght@300..700&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="signin.css">
 </head>

<body>
    <form action="second.php" class="sign-form" method="post">
    <input type="hidden" name="action" value="signup">

    <p class="login-p">التسجيل</p>

    <input name="first_name" type="text" class="email" placeholder="الاسم الأول" required>
    <input name="last_name" type="text" class="email" placeholder="اسم العائلة" required>
    <input name="email" type="email" class="email" placeholder="البريد الالكتروني" required>
    <input name="password" type="password" class="email" placeholder="كلمة السر" required>

    <input type="submit" class="login-btn" value="تسجيل">
</form>

    <form action="second.php" class="sign-form" method="post">
    <input type="hidden" name="action" value="signin">

    <p class="login-p">تسجيل الدخول</p>

    <input name="email" type="email" class="email" placeholder="البريد الالكتروني" required>
    <input name="password" type="password" class="email" placeholder="كلمة السر" required>

    <!-- زر دخول كطالب -->
    <button type="submit" name="login_type" value="student" class="login-btn">
        تسجيل الدخول كطالب
    </button>

    <!-- زر دخول كمعلم -->
    <button type="submit" name="login_type" value="teacher" class="login-btn teacher" style="margin-top:10px;">
        تسجيل الدخول كمعلم
    </button>

</form>

  <div class="blue-div" id="myBlueDiv">
    <a href="second.php" target="_self"><button class="blue-out"><img src="html images/exit.png"></button></a>
    <p class="blue-p" id="moveP">ليس لديك حساب؟</p>
    <button class="blue-btn" id="moveButton">تسجيل</button>
  </div>
  <script>
const moveButton = document.getElementById('moveButton');
const moveP = document.getElementById('moveP');
const divToMove = document.getElementById('myBlueDiv');

// --- الإضافة الجديدة: نجد زر الخروج ---
const exitButton = document.querySelector('.blue-out');

moveButton.addEventListener('click', () => {
 
  // 1. تبديل الكلاس (يضيفه أو يزيله) للـ div
  divToMove.classList.toggle('is-moved');
  
  // --- الإضافة الجديدة: تبديل الكلاس لزر الخروج أيضاً ---
  exitButton.classList.toggle('is-moved');
 
  // 2. التحقق من الحالة لتغيير النص
  if (divToMove.classList.contains('is-moved')) {
    moveP.textContent = 'لديك حساب بالفعل؟';
    moveButton.textContent = 'تسجيل دخول';
  } else {
    moveP.textContent = 'ليس لديك حساب؟';
    moveButton.textContent = 'تسجيل';
  }
 
});
  </script>
</body>

 <!-- <body background="https://wallpapercave.com/wp/wp7951246.png">
    <div class="signin">
        <p class="first-p">تسجيل الدخول</p>
        <br>
        <div class="first-in">
            <label for="email">البريد الالكتروني</label>
            <input type="text" name="email" id="email">
        </div>
        <br><br>
        <div class="sec-in">
            <label for="password">كلمة السر</label>
            <input type="password">
        </div>
        <div class="first-btn">
            <a onclick="openSmallWindow()"><button>تسجيل الدخول</button></a>
        </div>
        <div class="sec-p">
            <p>نسيت كلمة السر؟</p>
            <a href="#">اضغط هنا</a>
        </div>
        <hr>
        <div class="sec-btn">
           <a href="signup.html"> <button>تسجيل</button></a>
         </div>
    </div>
    <script>
      function openSmallWindow() {
          window.open('http://127.0.0.1:5500/enter.html', '_blank', 'width=600,height=400');
      }
  </script>
  
 </body> -->
 </html>



