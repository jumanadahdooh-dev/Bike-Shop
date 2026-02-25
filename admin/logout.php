<?php
// بدء الجلسة
session_start();

// إنهاء الجلسة
session_unset(); // إزالة جميع المتغيرات الجلسة
session_destroy(); // تدمير الجلسة

// إعادة التوجيه إلى صفحة تسجيل الدخول
header("Location: ../index.html");
exit();
?>
