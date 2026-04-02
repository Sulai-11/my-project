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

    <title>Final Exam Site</title>
    <link rel="stylesheet" href="exam.css">
    <style>
        
        .valid-exam {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            justify-content: center;
            margin-bottom: 20px;
        }
        .valid-exam a {
            padding: 5px 10px;
            text-decoration: none;
            background: #eee;
            color: #333;
            border-radius: 4px;
        }
        .valid-exam a.answered {
            background: #4CAF50;
            color: white;
        }
        
        /* التعديل هنا لضمان عدم تخريب تصميم الأسئلة الأصلي */
        .exam-page {
            display: none; 
            width: 100%;
        }
        .exam-page.active {
            display: block; /* العودة للوضع الطبيعي ليأخذ نفس تصميمك */
            animation: fadeIn 0.4s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .pagination-btns {
            display: flex;
            justify-content: space-between;
            width: 100%;
            margin-top: 20px;
            padding: 10px;
        }
        .pagination-btns button {
            padding: 10px 20px;
            font-family: 'Cairo', sans-serif;
            cursor: pointer;
            background-color: #0056b3;
            color: white;
            border: none;
            border-radius: 5px;
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
        <p class="exam-no">Final Exam</p>

        <div class="exam-page active" id="page1">
            <h3 style="text-align: center; color: #555;">الصفحة 1 من 3</h3>
            
            <div class="valid-exam" dir="ltr">
                <a href="#ex1" id="btn-q1">1</a><a href="#ex2" id="btn-q2">2</a><a href="#ex3" id="btn-q3">3</a>
                <a href="#ex4" id="btn-q4">4</a><a href="#ex5" id="btn-q5">5</a><a href="#ex6" id="btn-q6">6</a>
                <a href="#ex7" id="btn-q7">7</a><a href="#ex8" id="btn-q8">8</a><a href="#ex9" id="btn-q9">9</a><a href="#ex10" id="btn-q10">10</a>
            </div>

            <div class="div-exam" id="ex1">
                <div class="div-quist"><p class="p-quist">1</p><p class="p-quist2">Which protocol is used to transfer web pages?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="1">
                    <label class="btn-ch"><input type="radio" name="qu1" value="FTP" class="check"><span class="Q-text">FTP</span></label>
                    <label class="btn-ch"><input type="radio" name="qu1" value="HTTP" class="check"><span class="Q-text">HTTP</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex2">
                <div class="div-quist"><p class="p-quist">2</p><p class="p-quist2">What does HTML stand for?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="2">
                    <label class="btn-ch"><input type="radio" name="qu2" value="HTML" class="check"><span class="Q-text">Hyper Text Markup Language</span></label>
                    <label class="btn-ch"><input type="radio" name="qu2" value="Home" class="check"><span class="Q-text">Home Tool Markup Language</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex3">
                <div class="div-quist"><p class="p-quist">3</p><p class="p-quist2">What is the main purpose of CSS?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="3">
                    <label class="btn-ch"><input type="radio" name="qu3" value="Database" class="check"><span class="Q-text">Database</span></label>
                    <label class="btn-ch"><input type="radio" name="qu3" value="Styling" class="check"><span class="Q-text">Styling Web Pages</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex4">
                <div class="div-quist"><p class="p-quist">4</p><p class="p-quist2">Which language runs on the browser?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="4">
                    <label class="btn-ch"><input type="radio" name="qu4" value="PHP" class="check"><span class="Q-text">PHP</span></label>
                    <label class="btn-ch"><input type="radio" name="qu4" value="JavaScript" class="check"><span class="Q-text">JavaScript</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex5">
                <div class="div-quist"><p class="p-quist">5</p><p class="p-quist2">What does URL stand for?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="5">
                    <label class="btn-ch"><input type="radio" name="qu5" value="URL" class="check"><span class="Q-text">Uniform Resource Locator</span></label>
                    <label class="btn-ch"><input type="radio" name="qu5" value="Link" class="check"><span class="Q-text">Unified Reference Link</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex6">
                <div class="div-quist"><p class="p-quist">6</p><p class="p-quist2">Largest HTML heading tag?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="6">
                    <label class="btn-ch"><input type="radio" name="qu6" value="h6" class="check"><span class="Q-text">&lt;h6&gt;</span></label>
                    <label class="btn-ch"><input type="radio" name="qu6" value="h1" class="check"><span class="Q-text">&lt;h1&gt;</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex7">
                <div class="div-quist"><p class="p-quist">7</p><p class="p-quist2">Tag for hyperlink?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="7">
                    <label class="btn-ch"><input type="radio" name="qu7" value="a" class="check"><span class="Q-text">&lt;a&gt;</span></label>
                    <label class="btn-ch"><input type="radio" name="qu7" value="link" class="check"><span class="Q-text">&lt;link&gt;</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex8">
                <div class="div-quist"><p class="p-quist">8</p><p class="p-quist2">CSS property for text size?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="8">
                    <label class="btn-ch"><input type="radio" name="qu8" value="font-size" class="check"><span class="Q-text">font-size</span></label>
                    <label class="btn-ch"><input type="radio" name="qu8" value="text-size" class="check"><span class="Q-text">text-size</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex9">
                <div class="div-quist"><p class="p-quist">9</p><p class="p-quist2">Alert box in JS?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="9">
                    <label class="btn-ch"><input type="radio" name="qu9" value="msgBox" class="check"><span class="Q-text">msgBox()</span></label>
                    <label class="btn-ch"><input type="radio" name="qu9" value="alert" class="check"><span class="Q-text">alert()</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex10">
                <div class="div-quist"><p class="p-quist">10</p><p class="p-quist2">What does PHP stand for?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="10">
                    <label class="btn-ch"><input type="radio" name="qu10" value="PHP_Hypertext" class="check"><span class="Q-text">PHP: Hypertext Preprocessor</span></label>
                    <label class="btn-ch"><input type="radio" name="qu10" value="Private_Home" class="check"><span class="Q-text">Private Home Page</span></label>
                </form>
            </div>

            
            <div class="exam-done">
                <div class="back-exam">
                    <a href="Lecture 5.html">رجوع</a>
                </div>
                <div class="submit-exam">
                    <button type="button" onclick="changePage(2)">التالي</button>
                </div>
            </div>
        </div>

        <div class="exam-page" id="page2">
            <h3 style="text-align: center; color: #555;">الصفحة 2 من 3</h3>
            
            <div class="valid-exam" dir="ltr">
                <a href="#ex11" id="btn-q11">11</a><a href="#ex12" id="btn-q12">12</a><a href="#ex13" id="btn-q13">13</a>
                <a href="#ex14" id="btn-q14">14</a><a href="#ex15" id="btn-q15">15</a><a href="#ex16" id="btn-q16">16</a>
                <a href="#ex17" id="btn-q17">17</a><a href="#ex18" id="btn-q18">18</a><a href="#ex19" id="btn-q19">19</a><a href="#ex20" id="btn-q20">20</a>
            </div>

            <div class="div-exam" id="ex11">
                <div class="div-quist"><p class="p-quist">11</p><p class="p-quist2">Which symbol is used for comments in JS?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="11">
                    <label class="btn-ch"><input type="radio" name="qu11" value="slash" class="check"><span class="Q-text">//</span></label>
                    <label class="btn-ch"><input type="radio" name="qu11" value="comment" class="check"><span class="Q-text">&lt;!--</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex12">
                <div class="div-quist"><p class="p-quist">12</p><p class="p-quist2">CSS property for background color?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="12">
                    <label class="btn-ch"><input type="radio" name="qu12" value="color" class="check"><span class="Q-text">color</span></label>
                    <label class="btn-ch"><input type="radio" name="qu12" value="background-color" class="check"><span class="Q-text">background-color</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex13">
                <div class="div-quist"><p class="p-quist">13</p><p class="p-quist2">How to declare a variable in PHP?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="13">
                    <label class="btn-ch"><input type="radio" name="qu13" value="dollar" class="check"><span class="Q-text">$varName</span></label>
                    <label class="btn-ch"><input type="radio" name="qu13" value="var" class="check"><span class="Q-text">var varName</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex14">
                <div class="div-quist"><p class="p-quist">14</p><p class="p-quist2">SQL statement to extract data?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="14">
                    <label class="btn-ch"><input type="radio" name="qu14" value="SELECT" class="check"><span class="Q-text">SELECT</span></label>
                    <label class="btn-ch"><input type="radio" name="qu14" value="GET" class="check"><span class="Q-text">GET</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex15">
                <div class="div-quist"><p class="p-quist">15</p><p class="p-quist2">HTML tag for unordered list?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="15">
                    <label class="btn-ch"><input type="radio" name="qu15" value="ul" class="check"><span class="Q-text">&lt;ul&gt;</span></label>
                    <label class="btn-ch"><input type="radio" name="qu15" value="ol" class="check"><span class="Q-text">&lt;ol&gt;</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex16">
                <div class="div-quist"><p class="p-quist">16</p><p class="p-quist2">Is JS case-sensitive?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="16">
                    <label class="btn-ch"><input type="radio" name="qu16" value="Yes" class="check"><span class="Q-text">Yes</span></label>
                    <label class="btn-ch"><input type="radio" name="qu16" value="No" class="check"><span class="Q-text">No</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex17">
                <div class="div-quist"><p class="p-quist">17</p><p class="p-quist2">Where do we put external CSS?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="17">
                    <label class="btn-ch"><input type="radio" name="qu17" value="head" class="check"><span class="Q-text">In &lt;head&gt;</span></label>
                    <label class="btn-ch"><input type="radio" name="qu17" value="body" class="check"><span class="Q-text">In &lt;body&gt;</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex18">
                <div class="div-quist"><p class="p-quist">18</p><p class="p-quist2">HTML tag for line break?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="18">
                    <label class="btn-ch"><input type="radio" name="qu18" value="br" class="check"><span class="Q-text">&lt;br&gt;</span></label>
                    <label class="btn-ch"><input type="radio" name="qu18" value="lb" class="check"><span class="Q-text">&lt;lb&gt;</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex19">
                <div class="div-quist"><p class="p-quist">19</p><p class="p-quist2">What does SQL stand for?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="19">
                    <label class="btn-ch"><input type="radio" name="qu19" value="SQL_1" class="check"><span class="Q-text">Structured Query Language</span></label>
                    <label class="btn-ch"><input type="radio" name="qu19" value="SQL_2" class="check"><span class="Q-text">Strong Question Language</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex20">
                <div class="div-quist"><p class="p-quist">20</p><p class="p-quist2">How to select an element by id in CSS?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="20">
                    <label class="btn-ch"><input type="radio" name="qu20" value="hash" class="check"><span class="Q-text">#idName</span></label>
                    <label class="btn-ch"><input type="radio" name="qu20" value="dot" class="check"><span class="Q-text">.idName</span></label>
                </form>
            </div>

            
            <div class="exam-done">
                <div class="back-exam">
                    <button type="button" onclick="changePage(1)">رجوع</button>
                </div>
                <div class="submit-exam">
                    <button type="button" onclick="changePage(3)">التالي</button>
                </div>
            </div>
        </div>

        <div class="exam-page" id="page3">
            <h3 style="text-align: center; color: #555;">الصفحة 3 من 3</h3>
            
            <div class="valid-exam" dir="ltr">
                <a href="#ex21" id="btn-q21">21</a><a href="#ex22" id="btn-q22">22</a><a href="#ex23" id="btn-q23">23</a>
                <a href="#ex24" id="btn-q24">24</a><a href="#ex25" id="btn-q25">25</a><a href="#ex26" id="btn-q26">26</a>
                <a href="#ex27" id="btn-q27">27</a><a href="#ex28" id="btn-q28">28</a><a href="#ex29" id="btn-q29">29</a><a href="#ex30" id="btn-q30">30</a>
            </div>

            <div class="div-exam" id="ex21">
                <div class="div-quist"><p class="p-quist">21</p><p class="p-quist2">Bootstrap is a framework for?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="21">
                    <label class="btn-ch"><input type="radio" name="qu21" value="CSS" class="check"><span class="Q-text">CSS</span></label>
                    <label class="btn-ch"><input type="radio" name="qu21" value="Python" class="check"><span class="Q-text">Python</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex22">
                <div class="div-quist"><p class="p-quist">22</p><p class="p-quist2">Which HTML attribute defines inline styles?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="22">
                    <label class="btn-ch"><input type="radio" name="qu22" value="style" class="check"><span class="Q-text">style</span></label>
                    <label class="btn-ch"><input type="radio" name="qu22" value="class" class="check"><span class="Q-text">class</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex23">
                <div class="div-quist"><p class="p-quist">23</p><p class="p-quist2">Tag for inserting an image?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="23">
                    <label class="btn-ch"><input type="radio" name="qu23" value="img" class="check"><span class="Q-text">&lt;img&gt;</span></label>
                    <label class="btn-ch"><input type="radio" name="qu23" value="image" class="check"><span class="Q-text">&lt;image&gt;</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex24">
                <div class="div-quist"><p class="p-quist">24</p><p class="p-quist2">PHP server scripts are surrounded by delimiters, which?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="24">
                    <label class="btn-ch"><input type="radio" name="qu24" value="php-tag" class="check"><span class="Q-text">&lt;?php ... ?&gt;</span></label>
                    <label class="btn-ch"><input type="radio" name="qu24" value="script-tag" class="check"><span class="Q-text">&lt;script&gt;...&lt;/script&gt;</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex25">
                <div class="div-quist"><p class="p-quist">25</p><p class="p-quist2">How do you create a function in JS?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="25">
                    <label class="btn-ch"><input type="radio" name="qu25" value="function" class="check"><span class="Q-text">function myFunction()</span></label>
                    <label class="btn-ch"><input type="radio" name="qu25" value="def" class="check"><span class="Q-text">def myFunction()</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex26">
                <div class="div-quist"><p class="p-quist">26</p><p class="p-quist2">SQL statement to update data?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="26">
                    <label class="btn-ch"><input type="radio" name="qu26" value="UPDATE" class="check"><span class="Q-text">UPDATE</span></label>
                    <label class="btn-ch"><input type="radio" name="qu26" value="MODIFY" class="check"><span class="Q-text">MODIFY</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex27">
                <div class="div-quist"><p class="p-quist">27</p><p class="p-quist2">What is the default port for HTTP?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="27">
                    <label class="btn-ch"><input type="radio" name="qu27" value="80" class="check"><span class="Q-text">80</span></label>
                    <label class="btn-ch"><input type="radio" name="qu27" value="443" class="check"><span class="Q-text">443</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex28">
                <div class="div-quist"><p class="p-quist">28</p><p class="p-quist2">HTML comment syntax?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="28">
                    <label class="btn-ch"><input type="radio" name="qu28" value="html-comment" class="check"><span class="Q-text">&lt;!-- --&gt;</span></label>
                    <label class="btn-ch"><input type="radio" name="qu28" value="js-comment" class="check"><span class="Q-text">//</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex29">
                <div class="div-quist"><p class="p-quist">29</p><p class="p-quist2">Which CSS property controls text boldness?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="29">
                    <label class="btn-ch"><input type="radio" name="qu29" value="font-weight" class="check"><span class="Q-text">font-weight</span></label>
                    <label class="btn-ch"><input type="radio" name="qu29" value="text-style" class="check"><span class="Q-text">text-style</span></label>
                </form>
            </div>
            <div class="div-exam" id="ex30">
                <div class="div-quist"><p class="p-quist">30</p><p class="p-quist2">How to define an array in PHP?</p><p class="degree-quist">0 / 1</p></div><hr class="exam-hr">
                <form class="form-q" data-question="30">
                    <label class="btn-ch"><input type="radio" name="qu30" value="array" class="check"><span class="Q-text">$arr = array();</span></label>
                    <label class="btn-ch"><input type="radio" name="qu30" value="bracket" class="check"><span class="Q-text">$arr = new Array();</span></label>
                </form>
            </div>

            
            
            <div class="exam-done">
                <div class="back-exam">
                    <button type="button" onclick="changePage(2)">رجوع</button>
                </div>
                <div class="submit-exam">
                    <button>تقديم النهائي</button>
                </div>
            </div>
        </div>

    </div>

<script>
    // دالة الانتقال بين الصفحات
    function changePage(pageNum) {
        // إخفاء جميع الصفحات
        document.querySelectorAll('.exam-page').forEach(page => {
            page.classList.remove('active');
        });
        // إظهار الصفحة المطلوبة
        document.getElementById('page' + pageNum).classList.add('active');
        // رفع الشاشة للأعلى بسلاسة
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // إعداد الشريط الجانبي (Sidebar)
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

    // تلوين زر السؤال في الشريط العلوي عند الإجابة عليه
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