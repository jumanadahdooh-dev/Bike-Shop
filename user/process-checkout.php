<?php
session_start();
include '../dashboard/db.php';  // تأكد من المسار الصحيح للملف

// التحقق من أن المستخدم قد سجل الدخول
if (!isset($_SESSION['user'])) {
    header("Location: ../dashboard/login.php");
    exit();
}

$user_email = $_SESSION['user'];

// استعلام للحصول على user_id بناءً على البريد الإلكتروني
$sql = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_id = $user['id'];

// استعلام للحصول على محتويات العربة مع صورة المنتج
$sql = "SELECT cart.id, products.name, products.price, cart.quantity, (products.price * cart.quantity) AS total_price, products.image
        FROM cart
        JOIN products ON cart.product_id = products.id
        WHERE cart.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// حساب المجموع الكلي
$total = 0;
while ($row = $result->fetch_assoc()) {
    $total += $row['total_price'];
}

// الحصول على طريقة الدفع من النموذج
$payment_method = $_POST['payment_method'];

// إدخال البيانات في جدول الطلبات
$sql = "INSERT INTO orders (user_id, total, status) VALUES (?, ?, 'pending')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("id", $user_id, $total);
$stmt->execute();
$order_id = $stmt->insert_id;  // الحصول على ID الطلب بعد إضافته

// إدخال بيانات العناصر في جدول order_items
while ($row = $result->fetch_assoc()) {
    $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiid", $order_id, $row['id'], $row['quantity'], $row['price']);
    $stmt->execute();
}

// بناءً على طريقة الدفع، يمكن تحديث حالة الطلب
if ($payment_method == "credit_card") {
    // يمكنك إضافة إجراءات للتحقق من تفاصيل البطاقة
    // تحديث حالة الطلب إلى "تم الدفع"
    $sql = "UPDATE orders SET status = 'paid' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
} else if ($payment_method == "cash_on_delivery") {
    // إذا كان الدفع عند الاستلام، يمكن ترك الحالة كما هي "pending"
    $sql = "UPDATE orders SET status = 'confirmed' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
}

// حذف جميع العناصر من العربة بعد إتمام الطلب
$sql = "DELETE FROM cart WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

// إعادة التوجيه إلى صفحة تأكيد الطلب أو صفحة الرئيسية
header("Location: cart.php?order_id=" . $order_id);
exit();
?>
