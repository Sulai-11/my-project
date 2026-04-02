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