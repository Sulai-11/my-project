new Swiper('.card-wrapper', {

    
    spaceBetween: 30,

    // If we need pagination
    pagination: {
        el: '.swiper-pagination',
        clickable: true,
        dynamicBullets: true
    },

    // Navigation arrows
    navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
    },

    breakpoints: {
        0: {
            slidesPerView: 1

        },
        768: {
            slidesPerView: 2

        },
        1024: {
            slidesPerView: 3

        }
    }
});
window.addEventListener('beforeunload', () => {
    localStorage.setItem('scrollPosition', window.scrollY);
  });

  // 2. بعد تحميل الصفحة، ارجع لنفس المكان
  window.addEventListener('load', () => {
    // اقرأ المكان المحفوظ
    const scrollPosition = localStorage.getItem('scrollPosition');
    
    // إذا كان فيه رقم محفوظ (يعني مو أول مرة تفتح الصفحة)
    if (scrollPosition) {
      // انزل لهذا المكان
      window.scrollTo(0, parseInt(scrollPosition, 10));
      // (اختياري) احذف الرقم عشان ما يأثر لو فتحت الصفحة في وقت ثاني
      localStorage.removeItem('scrollPosition'); 
    }
  });

// 1. نحدد العناصر التي سنتعامل معها
const sidebar = document.querySelector('.home-side');
const toggleBtn = document.querySelector('.open-sidebar-btn');
const searchBtn = document.querySelector('.search-btn');

// 2. عند الضغط على زر القائمة (☰) - لفتح/إغلاق القائمة الثابتة
toggleBtn.addEventListener('click', (e) => {
    // يمنع انتشار حدث النقر إلى العناصر الأب (مثل document)
    e.stopPropagation(); 
    // يفتح أو يغلق القائمة (يضيف/يزيل كلاس sidebar-open)
    sidebar.classList.toggle('sidebar-open');
});

// 3. عند الضغط على زر البحث - لفتح القائمة والتركيز على حقل البحث
searchBtn.addEventListener('click', () => {
    // يضمن فتح القائمة دائمًا عند النقر على البحث
    sidebar.classList.add('sidebar-open');
    // ينقل التركيز إلى حقل الإدخال داخل القائمة
    sidebar.querySelector('.search-input').focus();
});

// 4. الإغلاق بالنقر خارج الشريط الجانبي (Click Outside)
document.addEventListener('click', (e) => {
    // نتحقق إذا كانت القائمة مفتوحة (تحتوي على الكلاس sidebar-open)
    if (sidebar.classList.contains('sidebar-open')) {
        // نتحقق إذا كان العنصر الذي تم النقر عليه (e.target) ليس داخل الشريط الجانبي (sidebar)
        if (!sidebar.contains(e.target)) {
            // إذا كان النقر خارج الشريط، قم بإغلاقه (إزالة الكلاس)
            sidebar.classList.remove('sidebar-open');
        }
    }
});
function navigateToPage(selectElement) {

        const selectedValue = selectElement.value;

        
        if (selectedValue) {
            
            window.location.href = selectedValue;
        }
    }
    // جلب العناصر
// جلب العناصر والتأكد من وجودها

// زر مستوى المتدرب بعد التسجيل
// let coursesCount = 12;

// let levelText = "";

// if (coursesCount < 3) {
//     levelText = "مبتدئ";
// } else if (coursesCount < 7) {
//     levelText = "متقدم";
// } else if (coursesCount < 12) {
//     levelText = "محترف";
// } else {
//     levelText = "خبير";
// }


// document.getElementById("level").textContent = levelText;
