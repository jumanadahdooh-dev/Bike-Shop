<?php
session_start();
include '../dashboard/db.php'; 

if (!isset($_SESSION['user'])) {
    header("Location: ../dashboard/login.php");
    exit();
}

$user_email = $_SESSION['user'];

// جلب ID المستخدم
$sql = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$user_id = $stmt->get_result()->fetch_assoc()['id'];

// حذف السلة
if (isset($_POST['clear_cart'])) {
    $sql = "DELETE FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    header("Location: cart.php");
    exit();
}

// جلب محتويات العربة
$sql = "SELECT cart.id, products.name, products.price, cart.quantity, (products.price * cart.quantity) AS total_price, products.image
        FROM cart
        JOIN products ON cart.product_id = products.id
        WHERE cart.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عربة التسوق | متجر الدراجات</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        /* --- ستايل الـ Black Mode الفخم --- */
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

        .cart-section { 
            max-width: 950px; 
            margin: 60px auto; 
            padding: 0 20px; 
            min-height: 70vh;
        }

        /* تنسيق منطقة العنوان المحدثة */
        .cart-title-area {
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 25px;
        }

        .cart-title-area h2 { 
            font-size: 32px; 
            font-weight: 900; 
            color: #fff;
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 0;
        }

        .cart-title-area h2 i { color: var(--primary-color); }

        /* زر سجل الطلبات المطور */
        .btn-history-top {
            background: #1a1a1a;
            color: #fff;
            padding: 12px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #333;
            transition: 0.3s;
        }

        .btn-history-top:hover {
            border-color: var(--primary-color);
            background: #222;
        }

        /* البطاقات العائمة */
        .cart-container { 
            display: flex; 
            flex-direction: column; 
            gap: 20px; 
        }

        .cart-card {
            display: flex;
            align-items: center;
            gap: 25px;
            padding: 25px;
            background: var(--card-black);
            border-radius: 20px;
            border: 1px solid var(--border-color);
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .cart-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(204, 51, 31, 0.2);
        }

        .card-img-box img {
            width: 140px; height: 100px;
            object-fit: contain;
            background: #222; 
            border-radius: 15px;
            padding: 10px;
            border: 1px solid #333;
        }

        .product-details { flex: 2; }
        .product-details h4 { font-size: 24px; font-weight: 800; margin: 0 0 8px; color: #fff; }
        .qty-p { color: #888; font-size: 16px; margin: 0; }
        .qty-p span { color: var(--primary-color); font-weight: 900; margin-right: 5px; }

        .price-info { text-align: left; }
        .total-price-item { font-size: 28px; font-weight: 900; color: var(--primary-color); display: block; }
        .unit-price { color: #555; font-size: 14px; margin-top: 5px; display: block; }

        .cart-footer-summary {
            margin-top: 50px;
            padding: 35px;
            border-top: 1px solid var(--border-color);
            background: rgba(255,255,255,0.02);
            border-radius: 25px;
        }

        .total-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
        }

        .total-summary h3 { font-size: 26px; color: #fff; margin: 0; }
        .total-summary span { font-size: 48px; font-weight: 900; color: var(--primary-color); }

        .cart-action-btns {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 15px;
        }

        .btn-checkout, .btn-continue, .btn-home-link, .btn-clear {
            padding: 22px;
            border-radius: 18px;
            font-size: 18px;
            font-weight: 800;
            cursor: pointer;
            border: none;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-decoration: none;
        }

        .btn-checkout { background: var(--primary-color); color: #fff; box-shadow: 0 8px 20px rgba(204, 51, 31, 0.3); }
        .btn-continue { background: #252525; color: #fff; }
        .btn-home-link { background: #fff; color: #000; }

        .btn-clear {
            background: transparent;
            color: #ff4757;
            border: 1.5px solid #ff4757;
            grid-column: span 3;
            margin-top: 20px;
            font-size: 15px;
        }

        footer {
            background-color: #000 !important;
            border-top: 1px solid var(--border-color);
            padding: 30px 0;
            margin-top: 100px;
        }

        @media (max-width: 768px) {
            .cart-title-area { flex-direction: column; gap: 20px; text-align: center; }
            .cart-action-btns { grid-template-columns: 1fr; }
            .btn-clear { grid-column: span 1; }
            .cart-card { flex-direction: column; text-align: center; }
            .price-info { text-align: center; }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="../uploads/logo.png" alt="متجر الدراجات" style="height: 50px;">
            <h1 style="display:inline; vertical-align: middle; margin-right: 10px;">
                <a href="../index.html" style="color: var(--primary-color); text-decoration: none;">متجر الدراجات</a>
            </h1>
        </div>
        <nav class="main-nav">
            <ul style="list-style: none; display: flex; gap: 20px;">
                <li><a href="../index.html" style="color: #fff; text-decoration: none;">الرئيسية</a></li>
                <li><a href="products.php" style="color: #fff; text-decoration: none;">المنتجات</a></li>
            </ul>
        </nav>
    </header>

    <section class="cart-section">
        <div class="cart-title-area">
            <h2><i class="fas fa-shopping-basket"></i> سلة المشتريات</h2>
            <a href="orders-history.php" class="btn-history-top">
                <i class="fas fa-history" style="color: var(--primary-color);"></i> 
                سجل طلباتي
            </a>
        </div>

        <div class="cart-container">
            <?php
            $total_all = 0;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $total_all += $row['total_price'];
                    $image_path = '../uploads/' . basename($row['image']);
                    ?>
                    <div class="cart-card">
                        <div class="card-img-box">
                            <img src="<?php echo $image_path; ?>" alt="<?php echo $row['name']; ?>">
                        </div>
                        <div class="product-details">
                            <h4><?php echo $row['name']; ?></h4>
                            <p class="qty-p">الكمية: <span><?php echo $row['quantity']; ?></span></p>
                        </div>
                        <div class="price-info">
                            <span class="total-price-item"><?php echo number_format($row['total_price'], 2); ?>$</span>
                            <span class="unit-price">سعر القطعة: <?php echo number_format($row['price'], 2); ?>$</span>
                        </div>
                    </div>
                <?php }
            } else { ?>
                <div style="text-align: center; padding: 80px 0;">
                    <i class="fas fa-shopping-bag" style="font-size: 80px; color: #222; margin-bottom: 20px; display: block;"></i>
                    <h3 style="color: #555;">عربة التسوق فارغة</h3>
                    <a href="products.php" class="btn-checkout" style="display: inline-flex; margin-top: 20px; text-decoration: none;">ابدأ التسوق الآن</a>
                </div>
            <?php } ?>
        </div>

        <?php if ($total_all > 0): ?>
        <div class="cart-footer-summary">
            <div class="total-summary">
                <h3>إجمالي الحساب:</h3>
                <span><?php echo number_format($total_all, 2); ?>$</span>
            </div>
            
            <div class="cart-action-btns">
                <button class="btn-checkout" onclick="window.location.href='checkout.php'">
                    <i class="fas fa-credit-card"></i> الانتقال للدفع
                </button>
                
                <button class="btn-continue" onclick="window.location.href='products.php'">
                    <i class="fas fa-cart-plus"></i> إضافة منتجات
                </button>
                
                <button class="btn-home-link" onclick="window.location.href='../index.html'">
                    <i class="fas fa-home"></i> الرئيسية
                </button>

                <form method="POST" style="grid-column: span 3; width:100%">
                    <button type="submit" name="clear_cart" class="btn-clear">
                        <i class="fas fa-trash-alt"></i> إفراغ سلة المشتريات
                    </button>
                </form>
            </div>  
        </div>
        <?php endif; ?>
    </section>

    <footer class="main-footer">
        <div class="footer-container" style="display: flex; justify-content: space-around; padding: 40px 5%; flex-wrap: wrap; gap: 30px;">
            <div class="footer-column about" style="flex: 1; min-width: 250px;">
                <h3 style="color: #fff;">دراجاتك<span style="color: var(--primary-color);">.</span></h3>
                <p style="color: #888;">نحن فخورون بتقديم أفضل أنواع الدراجات الهوائية والاحترافية بجودة عالمية لتناسب شغفك ومغامراتك.</p>
            </div>

            <div class="footer-column links" style="flex: 1; min-width: 150px;">
                <h4 style="color: #fff;">روابط سريعة</h4>
                <ul style="list-style: none; padding: 0; color: #888;">
                    <li><a href="../index.html" style="color: inherit; text-decoration: none;">الرئيسية</a></li>
                    <li><a href="products.php" style="color: inherit; text-decoration: none;">المتجر</a></li>
                    <li><a href="cart.php" style="color: inherit; text-decoration: none;">عربة التسوق</a></li>
                </ul>
            </div>

            <div class="footer-column social" style="flex: 1; min-width: 200px;">
                <h4 style="color: #fff;">تابعنا</h4>
                <div class="social-icons" style="display: flex; gap: 15px; font-size: 20px;">
                    <a href="#" style="color: #555;"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" style="color: #555;"><i class="fab fa-instagram"></i></a>
                    <a href="#" style="color: #555;"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom" style="text-align: center; padding: 20px; border-top: 1px solid #111; color: #444;">
            <p>جميع الحقوق محفوظة &copy; 2026 | تصميم متجر الدراجات</p>
        </div>
    </footer>
</body>
</html>