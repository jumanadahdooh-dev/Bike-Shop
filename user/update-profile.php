<?php
session_start();
// بدلاً من include
include_once '../dashboard/db.php'; // يستخدم مرة واحدة فقط


// التحقق من أن المستخدم قد سجل الدخول
if (!isset($_SESSION['user'])) {
    header("Location: ../dashboard/login.php");
    exit();
}

// تحديث البيانات في قاعدة البيانات
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullName = $_POST['fullName'];
    $phoneNumber = $_POST['phoneNumber'];
    $email = $_SESSION['user'];  // من المفترض أن البريد الإلكتروني مخزن في الجلسة

    // الاتصال بقاعدة البيانات وتحديث البيانات
    include '../dashboard/db.php';  // تأكد من المسار الصحيح للملف

    $sql = "UPDATE users SET fullName = ?, phoneNumber = ? WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $fullName, $phoneNumber, $email);

    if ($stmt->execute()) {
        echo "تم تحديث البيانات بنجاح!";
        header("Location: profile.php"); // إعادة التوجيه إلى صفحة البيانات الشخصية بعد التحديث
    } else {
        echo "حدث خطأ أثناء تحديث البيانات.";
    }

    $stmt->close();
    $conn->close();
}
?>
