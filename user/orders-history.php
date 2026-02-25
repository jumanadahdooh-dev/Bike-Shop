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

// استعلام للحصول على جميع الطلبات للمستخدم
$sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل الطلبات | متجر الدراجات</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #cc331f;
            --deep-black: #0a0a0a;   
            --card-black: #161616;   
            --border-color: #252525;
            --text-light: #f0f0f0;
        }

        body { 
            background-color: var(--deep-black); 
            font-family: 'Cairo', sans-serif; 
            color: var(--text-light);
            margin: 0;
        }

        header {
            background-color: #000 !important;
            border-bottom: 1px solid var(--border-color);
            padding: 15px 5%;
        }

        .order-history-section { 
            max-width: 900px; 
            margin: 50px auto; 
            padding: 0 20px; 
            min-height: 75vh;
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 20px;
        }

        .history-header h2 { 
            font-size: 30px; 
            font-weight: 900; 
            color: #fff;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .history-header h2 i { color: var(--primary-color); }

        .btn-back-cart {
            color: #888;
            text-decoration: none;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }

        .btn-back-cart:hover { color: var(--primary-color); }

        /* تنسيق البطاقات بدلاً من الجدول */
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .order-item {
            background: var(--card-black);
            border: 1px solid var(--border-color);
            border-radius: 18px;
            padding: 20px 30px;
            display: grid;
            grid-template-columns: 1fr 2fr 1fr 1fr;
            align-items: center;
            transition: 0.3s ease;
        }

        .order-item:hover {
            border-color: var(--primary-color);
            transform: scale(1.01);
        }

        .order-num { font-weight: 900; color: #fff; }
        .order-num span { color: var(--primary-color); font-size: 12px; display: block; }

        .order-date { color: #777; font-size: 14px; }
        .order-date i { margin-left: 5px; color: #444; }

        .order-price { font-size: 20px; font-weight: 900; color: var(--primary-color); text-align: center; }

        .order-status { text-align: left; }
        .status-pill {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            background: #222;
            color: #aaa;
            border: 1px solid #333;
        }

        footer {
            background-color: #000;
            border-top: 1px solid var(--border-color);
            padding: 50px 0;
            margin-top: 80px;
            text-align: center;
        }

        .footer-bottom p { color: #555; font-size: 14px; }
        .social-media { margin-top: 15px; display: flex; justify-content: center; gap: 20px; }
        .social-media a { color: #444; text-decoration: none; transition: 0.3s; }
        .social-media a:hover { color: var(--primary-color); }

        @media (max-width: 768px) {
            .order-item { grid-template-columns: 1fr 1fr; gap: 20px; text-align: center; }
            .order-status { text-align: center; }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="../uploads/logo.png" alt="متجر الدراجات" style="height: 40px; vertical-align: middle;">
            <h1 style="display:inline; margin-right: 10px;"><a href="index.php" style="color: var(--primary-color); text-decoration: none;">متجر الدراجات</a></h1>
        </div>
        <nav class="main-nav">
            <ul style="list-style: none; display: flex; gap: 20px;">
                <li><a href="user-home.php" style="color: #fff; text-decoration: none;">الرئيسية</a></li>
                <li><a href="cart.php" style="color: #fff; text-decoration: none;">عربة التسوق</a></li>
                <li><a href="logout.php" style="color: var(--primary-color); text-decoration: none; font-weight: bold;">تسجيل الخروج</a></li>
            </ul>
        </nav>
    </header>

    <section class="order-history-section">
        <div class="history-header">
            <h2><i class="fas fa-history"></i> الطلبات السابقة</h2>
            <a href="cart.php" class="btn-back-cart"><i class="fas fa-shopping-cart"></i> العودة للعربة</a>
        </div>

        <div class="orders-list">
            <?php if ($orders->num_rows > 0): ?>
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <div class="order-item">
                        <div class="order-num">
                            <span>رقم الطلب</span>
                            #<?php echo $order['id']; ?>
                        </div>
                        
                        <div class="order-date">
                            <i class="far fa-calendar-alt"></i>
                            <?php echo date('Y-m-d', strtotime($order['created_at'])); ?>
                        </div>
                        
                        <div class="order-price">
                            <?php echo number_format($order['total'], 2); ?> $
                        </div>
                        
                        <div class="order-status">
                            <span class="status-pill">
                                <?php echo $order['status']; ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 60px;">
                    <i class="fas fa-box-open" style="font-size: 50px; color: #222; margin-bottom: 20px; display: block;"></i>
                    <p style="color: #555;">لا توجد طلبات سابقة لعرضها.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <div class="footer-bottom">
            <p>&copy; 2026 متجر الدراجات. جميع الحقوق محفوظة.</p>
        </div>
        <div class="social-media">
            <a href="#"><i class="fab fa-facebook-f"></i> فيسبوك</a>
            <a href="#"><i class="fab fa-twitter"></i> تويتر</a>
            <a href="#"><i class="fab fa-instagram"></i> إنستغرام</a>
        </div>
    </footer>

    <?php
    $stmt->close();
    $conn->close();
    ?>
</body>
</html>