
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

    <title>exam site</title>
    <link rel="stylesheet" href="exam.css">
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

    <div class="div-examall" id="ex1">
        <p class="exam-name">تقنيات الإنترنت</p>
        <p class="exam-no">Exam 1</p>
        <?php
        $num=0; 

         function number(){
            global $num;
            $num++;
            echo $num;
         }
        ?>
        <div class="valid-exam" dir="ltr">
            <a href="#ex1" id="btn-q1"><?php number(); ?></a>
            <a href="#ex2" id="btn-q2"><?php number(); ?></a>
            <a href="#ex3" id="btn-q3"><?php number(); ?></a>
            <a href="#ex4" id="btn-q4"><?php number(); ?></a>
            <a href="#ex5" id="btn-q5"><?php number(); ?></a>
        </div>
    <div class="div-exam">
        <div class="div-quist">
            <p class="p-quist">1</p>
            <p class="p-quist2">Which protocol is used to transfer web pages from the server to the browser ?</p>
            <p class="degree-quist">0 / 1</p>
        </div>
        <hr class="exam-hr">
        <form class="form-q" data-question="1">
            <label class="btn-ch"> <input type="radio" name="qu1" value="FTP" class="check" > <span class="Q-text">FTP</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu1" value="HTTP" class="check"> <span class="Q-text">HTTP</span> </label>
            <label class="btn-ch" id="ex2"> <input type="radio" name="qu1" value="SMTP" class="check" > <span class="Q-text">SMTP</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu1" value="IP" class="check" > <span class="Q-text">IP</span> </label>
        </form>
    </div>

    <div class="div-exam" >
        <div class="div-quist">
            <p class="p-quist">2</p>
            <p class="p-quist2">Which of the following is used to define the structure of a web page?</p>
            <p class="degree-quist">0 / 1</p>
        </div>
        <hr class="exam-hr">
        <form class="form-q" data-question="2">
            <label class="btn-ch"> <input type="radio" name="qu2" value="CSS" class="check" > <span class="Q-text">CSS</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu2" value="JavaScript" class="check"> <span class="Q-text">JavaScript</span> </label>
            <label class="btn-ch" id="ex3"> <input type="radio" name="qu2" value="HTML" class="check" > <span class="Q-text">HTML</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu2" value="PHP" class="check" > <span class="Q-text">PHP</span> </label>
        </form>
    </div>

    <div class="div-exam">
        <div class="div-quist">
            <p class="p-quist">3</p>
            <p class="p-quist2">What is the main purpose of CSS in web development?</p>
            <p class="degree-quist">0 / 1</p>
        </div>
        <hr class="exam-hr">
        <form class="form-q" data-question="3">
            <label class="btn-ch"> <input type="radio" name="qu3" value="create databases" class="check" > <span class="Q-text">To create databases</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu3" value="design and style" class="check"> <span class="Q-text">To design and style web pages</span> </label>
            <label class="btn-ch" id="ex4"> <input type="radio" name="qu3" value="send emails" class="check" > <span class="Q-text">To send emails</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu3" value="server-side" class="check" > <span class="Q-text">To perform server-side calculations</span> </label>
        </form>
    </div>

    <div class="div-exam" >
        <div class="div-quist">
            <p class="p-quist">4</p>
            <p class="p-quist2">Which of the following languages runs on the client side (in the browser)?</p>
            <p class="degree-quist">0 / 1</p>
        </div>
        <hr class="exam-hr">
        <form class="form-q" data-question="4">
            <label class="btn-ch"> <input type="radio" name="qu4" value="PHP" class="check" > <span class="Q-text">PHP</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu4" value="Python" class="check"> <span class="Q-text">Python</span> </label>
            <label class="btn-ch" id="ex5"> <input type="radio" name="qu4" value="JavaScript" class="check" > <span class="Q-text">JavaScript</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu4" value="SQL" class="check" > <span class="Q-text">SQL</span> </label>
        </form>
    </div>
    
    <div class="div-exam">
        <div class="div-quist">
            <p class="p-quist">5</p>
            <p class="p-quist2">What does the acronym “URL” stand for?</p>
            <p class="degree-quist">0 / 1</p>
        </div>
        <hr class="exam-hr">
        <form class="form-q" data-question="5">
            <label class="btn-ch"> <input type="radio" name="qu5" value="Universal Routing Link" class="check" > <span class="Q-text">Universal Routing Link</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu5" value="Uniform Resource Locator" class="check"> <span class="Q-text">Uniform Resource Locator</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu5" value="Unified Reference Language" class="check" > <span class="Q-text">Unified Reference Language</span> </label>
            <label class="btn-ch"> <input type="radio" name="qu5" value="Universal Reference Link" class="check" > <span class="Q-text">Universal Reference Link</span> </label>
        </form>
    </div>
    <div class="exam-done">
        <div class="back-exam">
            <a href="Lecture 5.html">رجوع</a>
        </div>
        
        <div class="submit-exam">
            <button>تقديم</button>
        </div>
    </div>
</div>

<script>
    const sidebar = document.getElementById('mySidebar');
    const openBtn = document.querySelector('.open-sidebar-btn');
    const closeBtn = document.querySelector('.close-btn');

    function openSidebar() {
        sidebar.classList.add('open');
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
    }

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
        btn.classList.add('answered');
    });

});

</script>


</body>
</html>


