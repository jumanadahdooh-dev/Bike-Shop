<?php
session_start();
include '../dashboard/db.php'; // تأكد من المسار الصحيح للملف

// التحقق من أن المستخدم هو الأدمن
if (!isset($_SESSION['user']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// التحقق من وجود ID العرض في الرابط
if (isset($_GET['id'])) {
    $offer_id = $_GET['id'];

    // استعلام لاسترجاع بيانات العرض بناءً على ID
    $offer_sql = "SELECT * FROM offers WHERE id = ?";
    $stmt = $conn->prepare($offer_sql);
    $stmt->bind_param("i", $offer_id);
    $stmt->execute();
    $offer_result = $stmt->get_result();
    $offer = $offer_result->fetch_assoc();

    // التحقق من أن العرض موجود
    if (!$offer) {
        die("العرض غير موجود.");
    }

    // حذف العرض من قاعدة البيانات
    $delete_sql = "DELETE FROM offers WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $offer_id);

    if ($stmt->execute()) {
        // إذا تم الحذف بنجاح
        header("Location: manage-offers.php"); // التوجيه إلى صفحة العروض
        exit();
    } else {
        $error_message = "حدث خطأ أثناء حذف العرض.";
    }
} else {
    die("ID العرض غير موجود.");
}
?>
