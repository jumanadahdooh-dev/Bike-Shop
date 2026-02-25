<?php
session_start();
include '../dashboard/db.php'; // تأكد من المسار الصحيح للملف

// التحقق من أن المستخدم هو الأدمن
if (!isset($_SESSION['user']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// التحقق من وجود معرّف المنتج
if (isset($_GET['id'])) {
    $product_id = $_GET['id'];

    // استعلام لحذف المنتج
    $sql = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    // التوجيه إلى صفحة إدارة المنتجات بعد الحذف
    header("Location: manage-products.php");
    exit();
} else {
    echo "المنتج غير موجود.";
}
?>
