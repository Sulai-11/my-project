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

    <title>Pre-Exam Site</title>
    <link rel="stylesheet" href="exam.css">
    <style>
        .user-info {
            text-align: right;
            padding: 10px 20px;
            background: #f9f9f9;
            border-bottom: 1px solid #ddd;
            font-family: 'Cairo', sans-serif;
            font-size: 14px;
        }
    </style>
</head>
<body>
    

    <button class="open-sidebar-btn">☰</button>

<div id="mySidebar" class="sidebar">
    <a href="javascript:void(0)" class="close-btn">×</a>
    <a href="second.php" class="a-side">الرئيسية</a>
    <a href="#" class="a-side">مقرراتي</a>
    <a href="#" class="a-side">التخصصات</a>
    <a href="#" class="a-side">اتصل بنا</a>
</div>

    <div class="div-examall">
        <p class="exam-name">تقنيات الإنترنت</p>
        <p class="exam-no" id="ex0">Pre-Exam</p>
        <?php
        $num=0; 
         function number(){
            global $num;
            $num++;
            echo $num;
         }
        ?>
        
        <div class="valid-exam" dir="ltr">
            <a href="#ex0" id="btn-q1"><?php number(); ?></a>
            <a href="#ex1" id="btn-q2"><?php number(); ?></a>
            <a href="#ex2" id="btn-q3"><?php number(); ?></a>
            <a href="#ex3" id="btn-q4"><?php number(); ?></a>
            <a href="#ex4" id="btn-q5"><?php number(); ?></a>
            <a href="#ex5" id="btn-q6"><?php number(); ?></a>
            <a href="#ex6" id="btn-q7"><?php number(); ?></a>
            <a href="#ex7" id="btn-q8"><?php number(); ?></a>
            <a href="#ex8" id="btn-q9"><?php number(); ?></a>
            <a href="#ex9" id="btn-q10"><?php number(); ?></a>
        </div>

    <div class="div-exam" id="ex1">
        <div class="div-quist">
            <p class="p-quist">1</p>
            <p class="p-quist2">Which protocol is used to transfer web pages from the server to the browser?</p>
            <p class="degree-quist">0 / 1</p>
        </div>
        <hr class="exam-hr">
        <form class="form-q" data-question="1">
            <label class="btn-ch"> <input type="radio" name="qu1" value="FTP" class="check"> <span class="Q-text">FTP</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu1" value="HTTP" class="check"> <span class="Q-text">HTTP</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu1" value="SMTP" class="check"> <span class="Q-text">SMTP</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu1" value="IP" class="check"> <span class="Q-text">IP</span> </label>
        </form>
    </div>

    <div class="div-exam" id="ex2">
        <div class="div-quist">
            <p class="p-quist">2</p>
            <p class="p-quist2">Which of the following is used to define the structure of a web page?</p>
            <p class="degree-quist">0 / 1</p>
        </div>
        <hr class="exam-hr">
        <form class="form-q" data-question="2">
            <label class="btn-ch"> <input type="radio" name="qu2" value="CSS" class="check"> <span class="Q-text">CSS</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu2" value="JavaScript" class="check"> <span class="Q-text">JavaScript</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu2" value="HTML" class="check"> <span class="Q-text">HTML</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu2" value="PHP" class="check"> <span class="Q-text">PHP</span> </label>
        </form>
    </div>

    <div class="div-exam" id="ex3">
        <div class="div-quist">
            <p class="p-quist">3</p>
            <p class="p-quist2">What is the main purpose of CSS in web development?</p>
            <p class="degree-quist">0 / 1</p>
        </div>
        <hr class="exam-hr">
        <form class="form-q" data-question="3">
            <label class="btn-ch"> <input type="radio" name="qu3" value="create databases" class="check"> <span class="Q-text">To create databases</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu3" value="design and style" class="check"> <span class="Q-text">To design and style web pages</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu3" value="send emails" class="check"> <span class="Q-text">To send emails</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu3" value="server-side" class="check"> <span class="Q-text">To perform server-side calculations</span> </label>
        </form>
    </div>

    <div class="div-exam" id="ex4">
        <div class="div-quist">
            <p class="p-quist">4</p>
            <p class="p-quist2">Which of the following languages runs on the client side (in the browser)?</p>
            <p class="degree-quist">0 / 1</p>
        </div>
        <hr class="exam-hr">
        <form class="form-q" data-question="4">
            <label class="btn-ch"> <input type="radio" name="qu4" value="PHP" class="check"> <span class="Q-text">PHP</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu4" value="Python" class="check"> <span class="Q-text">Python</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu4" value="JavaScript" class="check"> <span class="Q-text">JavaScript</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu4" value="SQL" class="check"> <span class="Q-text">SQL</span> </label>
        </form>
    </div>

    <div class="div-exam" id="ex5">
        <div class="div-quist">
            <p class="p-quist">5</p>
            <p class="p-quist2">What does the acronym “URL” stand for?</p>
            <p class="degree-quist">0 / 1</p>
        </div>
        <hr class="exam-hr">
        <form class="form-q" data-question="5">
            <label class="btn-ch"> <input type="radio" name="qu5" value="Universal Routing Link" class="check"> <span class="Q-text">Universal Routing Link</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu5" value="Uniform Resource Locator" class="check"> <span class="Q-text">Uniform Resource Locator</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu5" value="Unified Reference Language" class="check"> <span class="Q-text">Unified Reference Language</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu5" value="Universal Reference Link" class="check"> <span class="Q-text">Universal Reference Link</span> </label>
        </form>
    </div>

    <div class="div-exam" id="ex6">
        <div class="div-quist">
            <p class="p-quist">6</p>
            <p class="p-quist2">Which HTML tag is used for the largest heading?</p>
            <p class="degree-quist">0 / 1</p>
        </div>
        <hr class="exam-hr">
        <form class="form-q" data-question="6">
            <label class="btn-ch"> <input type="radio" name="qu6" value="h1" class="check"> <span class="Q-text">&lt;h1&gt;</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu6" value="h6" class="check"> <span class="Q-text">&lt;h6&gt;</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu6" value="head" class="check"> <span class="Q-text">&lt;head&gt;</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu6" value="header" class="check"> <span class="Q-text">&lt;header&gt;</span> </label>
        </form>
    </div>

    <div class="div-exam" id="ex7">
        <div class="div-quist">
            <p class="p-quist">7</p>
            <p class="p-quist2">Which HTML attribute is used to define inline styles?</p>
            <p class="degree-quist">0 / 1</p>
        </div>
        <hr class="exam-hr">
        <form class="form-q" data-question="7">
            <label class="btn-ch"> <input type="radio" name="qu7" value="class" class="check"> <span class="Q-text">class</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu7" value="style" class="check"> <span class="Q-text">style</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu7" value="font" class="check"> <span class="Q-text">font</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu7" value="styles" class="check"> <span class="Q-text">styles</span> </label>
        </form>
    </div>

    <div class="div-exam" id="ex8">
        <div class="div-quist">
            <p class="p-quist">8</p>
            <p class="p-quist2">Which CSS property is used to change the background color?</p>
            <p class="degree-quist">0 / 1</p>
        </div>
        <hr class="exam-hr">
        <form class="form-q" data-question="8">
            <label class="btn-ch"> <input type="radio" name="qu8" value="color" class="check"> <span class="Q-text">color</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu8" value="bgcolor" class="check"> <span class="Q-text">bgcolor</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu8" value="background-color" class="check"> <span class="Q-text">background-color</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu8" value="background" class="check"> <span class="Q-text">background</span> </label>
        </form>
    </div>

    <div class="div-exam" id="ex9">
        <div class="div-quist">
            <p class="p-quist">9</p>
            <p class="p-quist2">How do you create a function in JavaScript?</p>
            <p class="degree-quist">0 / 1</p>
        </div>
        <hr class="exam-hr">
        <form class="form-q" data-question="9">
            <label class="btn-ch"> <input type="radio" name="qu9" value="function:myFunction()" class="check"> <span class="Q-text">function:myFunction()</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu9" value="function myFunction()" class="check"> <span class="Q-text">function myFunction()</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu9" value="create myFunction()" class="check"> <span class="Q-text">create myFunction()</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu9" value="def myFunction()" class="check"> <span class="Q-text">def myFunction()</span> </label>
        </form>
    </div>

    <div class="div-exam" id="ex10">
        <div class="div-quist">
            <p class="p-quist">10</p>
            <p class="p-quist2">Which tag is used to link an external JavaScript file?</p>
            <p class="degree-quist">0 / 1</p>
        </div>
        <hr class="exam-hr">
        <form class="form-q" data-question="10">
            <label class="btn-ch"> <input type="radio" name="qu10" value="script" class="check"> <span class="Q-text">&lt;script&gt;</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu10" value="js" class="check"> <span class="Q-text">&lt;js&gt;</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu10" value="javascript" class="check"> <span class="Q-text">&lt;javascript&gt;</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu10" value="link" class="check"> <span class="Q-text">&lt;link&gt;</span> </label>
        </form>
    </div>

    <div class="exam-done">
        <div class="back-exam">
            <a href="Lecture 5.html">رجوع</a>
        </div>
        <div class="submit-exam">
            <a href="Lecture 1.html">تقديم</a>
        </div>
    </div>
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