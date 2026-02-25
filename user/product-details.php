<?php
session_start();
include '../dashboard/db.php';

// 1. التأكد من وجود ID المنتج في الرابط
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$product_id = $_GET['id'];

// 2. استعلام جلب بيانات المنتج المحدد فقط
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

// إذا كان المنتج غير موجود
if (!$product) {
    echo "المنتج غير موجود!";
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>متجر الدراجات</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/products.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

  <script>
    function updateUI() {
        const loginRegisterDiv = document.getElementById("login-register");
        const userActionsDiv = document.getElementById("user-actions");
        const isLoggedIn = localStorage.getItem("userLoggedIn") === "true";

        if (isLoggedIn) {
            if (loginRegisterDiv) loginRegisterDiv.style.display = "none";
            if (userActionsDiv) userActionsDiv.style.display = "flex";
        } else {
            if (loginRegisterDiv) loginRegisterDiv.style.display = "flex";
            if (userActionsDiv) userActionsDiv.style.display = "none";
        }
    }

    window.onload = updateUI;

    function logout() {
        localStorage.removeItem("userLoggedIn");
        window.location.href = "index.html";
    }
</script>
</head>
<body>
    <header>
        <div class="logo">
            <img src="../uploads/logo.png" alt="متجر الدراجات">
            <h1><a href="../index.html">متجر الدراجات</a></h1>
        </div>

        <nav class="main-nav">
            <ul>
                <li><a href="../index.html">الرئيسية</a></li>
                <li><a href="products.php">المنتجات</a></li>
                <li><a href="offers.php">عروض</a></li>
                <li><a href="contact.php">اتصل بنا</a></li>
            </ul>
        </nav>

        <div class="header-actions">
            <div id="login-register" style="display: flex;">
                <a href="../dashboard/login.php" class="btn-login"><i class="fas fa-sign-in-alt"></i> دخول</a>
                <a href="../dashboard/register.php" class="btn-register"><i class="fas fa-user-plus"></i> تسجيل</a>
            </div>
            
            <div id="user-actions" style="display: none;">
                <a href="profile.php" class="btn-profile"><i class="fas fa-user"></i> بياناتي</a>
                <a href="cart.php" class="btn-cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count">4</span>
                </a>
                <a href="javascript:void(0);" class="btn-logout" onclick="logout()"><i class="fas fa-sign-out-alt"></i> خروج</a>
            </div>
        </div>
    </header>    
</head>
<body>

    <div class="details-wrapper">
        <div class="product-image-large">
            <?php $image_path = '../uploads/' . basename($product['image']); ?>
            <img src="<?php echo $image_path; ?>" alt="<?php echo $product['name']; ?>">
        </div>

        <div class="product-info-details">
            <span class="category-tag"><?php echo $product['category_name']; ?></span>
            <h1><?php echo $product['name']; ?></h1>
            <p class="description"><?php echo $product['description']; ?></p>
            <span class="price-tag"><?php echo $product['price']; ?> $</span>

            <div class="action-buttons">
                <form method="POST" action="products.php">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <button type="submit" name="add_to_cart" class="btn-add-cart-large">
                        <i class="fas fa-shopping-basket"></i> أضف إلى السلة
                    </button>
                </form>

                <a href="products.php" class="btn-back">العودة للمتجر</a>
            </div>
        </div>
    </div>


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