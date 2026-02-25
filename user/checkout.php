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

// جلب محتويات العربة
$sql = "SELECT cart.id, products.name, products.price, cart.quantity, (products.price * cart.quantity) AS total_price, products.image
        FROM cart
        JOIN products ON cart.product_id = products.id
        WHERE cart.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إتمام الدفع | متجر الدراجات</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        /* --- ستايل الـ Black Mode لصفحة الدفع --- */
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
            max-width: 1000px; 
            margin: 40px auto; 
            padding: 0 20px; 
        }

        .cart-title-area h2 { 
            font-size: 30px; 
            font-weight: 900; 
            color: #fff;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }
        .cart-title-area h2 i { color: var(--primary-color); }

        /* تقسيم الصفحة */
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 30px;
            align-items: start;
        }

        /* تنسيق البطاقات (الصناديق) */
        .cart-main-box {
            background: var(--card-black);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid var(--border-color);
        }

        .box-title {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 25px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
        }

        /* قائمة المنتجات المصغرة */
        .checkout-item-minimal {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #222;
        }

        .checkout-item-minimal img {
            width: 70px; height: 55px;
            object-fit: contain;
            background: #222;
            border-radius: 10px;
            padding: 5px;
        }

        .item-info h4 { font-size: 16px; margin: 0; color: #fff; }
        .item-info p { font-size: 13px; color: #777; margin: 5px 0 0; }
        .item-total { margin-right: auto; font-weight: 800; color: var(--primary-color); font-size: 18px; }

        .checkout-total-line {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 20px;
            font-size: 22px;
            font-weight: 900;
            border-top: 2px dashed var(--border-color);
        }

        /* خيارات الدفع */
        .payment-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }

        .pay-option input { display: none; }

        .pay-card-box {
            padding: 20px;
            border: 2px solid var(--border-color);
            border-radius: 15px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
            background: #1a1a1a;
        }

        .pay-card-box i { font-size: 28px; display: block; margin-bottom: 10px; color: #555; }
        .pay-card-box span { font-size: 14px; font-weight: 700; color: #888; }

        .pay-option input:checked + .pay-card-box {
            border-color: var(--primary-color);
            background: rgba(204, 51, 31, 0.05);
        }

        .pay-option input:checked + .pay-card-box i,
        .pay-option input:checked + .pay-card-box span {
            color: var(--primary-color);
        }

        /* المدخلات (Inputs) */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 10px; font-weight: 700; font-size: 14px; color: #bbb; }
        .form-group label i { color: var(--primary-color); margin-left: 5px; }

        .main-input {
            width: 100%;
            padding: 15px;
            background: #0d0d0d;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: #fff;
            font-family: 'Cairo';
            transition: 0.3s;
        }

        .main-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 10px rgba(204, 51, 31, 0.1);
        }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

        /* الأزرار */
        .btn-checkout {
            background: var(--primary-color);
            color: #fff;
            padding: 18px;
            border-radius: 15px;
            font-size: 18px;
            font-weight: 800;
            border: none;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-checkout:hover { filter: brightness(1.1); transform: translateY(-2px); }

        .btn-continue {
            background: #252525;
            color: #fff;
            padding: 15px;
            border-radius: 15px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .w-100 { width: 100%; }

        @media (max-width: 850px) {
            .checkout-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body class="checkout-page">
    <header>
        <div class="logo">
            <img src="../uploads/logo.png" alt="متجر الدراجات" style="height: 40px;">
            <h1><a href="../index.html" style="color: var(--primary-color); text-decoration: none;">متجر الدراجات</a></h1>
        </div>
        <nav class="main-nav">
            <ul style="list-style: none; display: flex; gap: 20px;">
                <li><a href="../index.html" style="color: #fff; text-decoration: none;">الرئيسية</a></li>
                <li><a href="cart.php" style="color: #fff; text-decoration: none;">عربة التسوق</a></li>
            </ul>
        </nav>
    </header>

    <section class="cart-section">
        <div class="cart-title-area">
            <h2><i class="fas fa-file-invoice-dollar"></i> مراجعة وتأكيد الطلب</h2>
        </div>

        <div class="checkout-grid">
            <div class="cart-main-box shadow-sm">
                <h3 class="box-title"><i class="fas fa-shopping-basket"></i> ملخص المشتريات</h3>
                <div class="checkout-items-list">
                    <?php while ($row = $result->fetch_assoc()): 
                        $total += $row['total_price'];
                        $image_path = '../uploads/' . basename($row['image']);
                    ?>
                    <div class="checkout-item-minimal">
                        <img src="<?php echo $image_path; ?>" alt="">
                        <div class="item-info">
                            <h4><?php echo $row['name']; ?></h4>
                            <p>الكمية: <?php echo $row['quantity']; ?> × <?php echo number_format($row['price'], 2); ?>$</p>
                        </div>
                        <span class="item-total"><?php echo number_format($row['total_price'], 2); ?>$</span>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="checkout-total-line">
                    <span>المجموع الإجمالي:</span>
                    <strong><?php echo number_format($total, 2); ?>$</strong>
                </div>
            </div>

            <div class="cart-main-box">
                <h3 class="box-title"><i class="fas fa-credit-card"></i> معلومات الدفع</h3>
                <form action="process-checkout.php" method="POST" class="styled-form">
                    <div class="form-group">
                        <label>اختر طريقة الدفع</label>
                        <div class="payment-options">
                            <label class="pay-option">
                                <input type="radio" name="payment_method" value="credit_card" checked id="pay_card">
                                <div class="pay-card-box">
                                    <i class="fas fa-credit-card"></i>
                                    <span>بطاقة ائتمان</span>
                                </div>
                            </label>
                            <label class="pay-option">
                                <input type="radio" name="payment_method" value="cash_on_delivery" id="pay_cash">
                                <div class="pay-card-box">
                                    <i class="fas fa-truck"></i>
                                    <span>الدفع عند الاستلام</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div id="credit_card_details" class="card-details-wrapper">
                        <div class="form-group">
                            <label><i class="fas fa-barcode"></i> رقم البطاقة</label>
                            <input type="text" name="card_number" placeholder="XXXX-XXXX-XXXX-XXXX" class="main-input">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt"></i> التاريخ</label>
                                <input type="text" name="expiry_date" placeholder="MM/YY" class="main-input">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-lock"></i> CVV</label>
                                <input type="text" name="cvv" placeholder="123" class="main-input">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-checkout w-100">
                        <i class="fas fa-check-circle"></i> تأكيد وإرسال الطلب
                    </button>
                    
                    <a href="cart.php" class="btn-continue w-100" style="margin-top:15px; text-decoration:none;">
                        <i class="fas fa-arrow-right"></i> العودة للسلة
                    </a>
                </form>
            </div>
        </div>
    </section>

    <script>
        // تبديل ظهور تفاصيل البطاقة بشكل أنيق
        const cardDetails = document.getElementById("credit_card_details");
        document.getElementById("pay_card").addEventListener("change", () => cardDetails.style.display = "block");
        document.getElementById("pay_cash").addEventListener("change", () => cardDetails.style.display = "none");
    </script>

     
        <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-column about">
                <h3>دراجاتك<span>.</span></h3>
                <p>نحن فخورون بتقديم أفضل أنواع الدراجات الهوائية والاحترافية بجودة عالمية لتناسب شغفك ومغامراتك.</p>
            </div>

            <div class="footer-column links">
                <h4>روابط سريعة</h4>
                <ul>
                    <li><a href="index.html">الرئيسية</a></li>
                    <li><a href="products.html">المتجر</a></li>
                    <li><a href="#why-us">لماذا نحن؟</a></li>
                    <li><a href="cart.html">عربة التسوق</a></li>
                </ul>
            </div>

            <div class="footer-column social">
                <h4>تابعنا</h4>
                <p>ابقَ على تواصل معنا عبر منصات التواصل الاجتماعي:</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>جميع الحقوق محفوظة &copy; 2026 | تصميم متجر الدراجات</p>
        </div>
    </footer>

</body>
</html>