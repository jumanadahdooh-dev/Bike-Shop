

 // تحقق من حالة تسجيل الدخول عبر LocalStorage
        window.onload = function() {
            if (localStorage.getItem("userLoggedIn")) {
                // إذا كان المستخدم قد سجل الدخول
                document.getElementById("login-register").style.display = "none"; // إخفاء روابط التسجيل والدخول
                document.getElementById("user-actions").style.display = "block"; // إظهار خيارات المستخدم
            } else {
                // إذا لم يكن المستخدم قد سجل الدخول
                document.getElementById("login-register").style.display = "block"; // إظهار روابط التسجيل والدخول
                document.getElementById("user-actions").style.display = "none"; // إخفاء خيارات المستخدم
            }
        };

        // تسجيل الدخول (تخزين حالة تسجيل الدخول في LocalStorage)
        function login() {
            localStorage.setItem("userLoggedIn", "true");
            window.location.reload(); // إعادة تحميل الصفحة لتحديث المحتوى
        }

        // تسجيل الخروج (إزالة حالة تسجيل الدخول من LocalStorage)
        function logout() {
            localStorage.removeItem("userLoggedIn");
            window.location.reload(); // إعادة تحميل الصفحة لتحديث المحتوى
        }