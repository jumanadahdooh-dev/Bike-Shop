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

// استعلام لاسترجاع التصنيفات من قاعدة البيانات
$category_sql = "SELECT * FROM categories";
$category_result = $conn->query($category_sql);

// إعداد الفلتر إذا تم تحديد فئة
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// بناء استعلام SQL بناءً على الفئة المحددة
$sql = "SELECT * FROM products WHERE 1"; 

// إضافة فلتر الفئة
if ($category_filter != '') {
    $sql .= " AND category_id = ?";
}

$stmt = $conn->prepare($sql);

if ($category_filter != '') {
    $stmt->bind_param("i", $category_filter);
}

$stmt->execute();
$products = $stmt->get_result();

// معالجة إضافة المنتج إلى السلة
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    
    $sql_check = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $user_id, $product_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $sql_update = "UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ii", $user_id, $product_id);
        $stmt_update->execute();
    } else {
        $sql_insert = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("ii", $user_id, $product_id);
        $stmt_insert->execute();
    }
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
    
    <section class="categories-section">
        <h2>التصنيفات</h2>
        <div class="categories-buttons">
            <a href="products.php" class="btn-category">عرض الكل</a>
            <?php
            while ($category = $category_result->fetch_assoc()) {
                echo "<a href='products.php?category={$category['id']}' class='btn-category'>{$category['name']}</a>";
            }
            ?>
        </div>
    </section>

    <section class="products-section">
        <h2>المنتجات</h2>
        <div class="product-list">
            <?php
            if ($products->num_rows > 0) {
                while ($product = $products->fetch_assoc()) {
                    $image_path = '../uploads/' . basename($product['image']);
                    echo "<div class='product-card'>
                            <img src='{$image_path}' alt='{$product['name']}' class='product-image'>
                            <h3>{$product['name']}</h3>
                            <p>{$product['description']}</p>
                            <p class='price'>{$product['price']} $</p>
                            
                            <div class='product-buttons'>
                                <a href='product-details.php?id={$product['id']}' class='btn-view-details'>رؤية التفاصيل</a>
                                
                                <form method='POST' action='products.php'>
                                    <input type='hidden' name='product_id' value='{$product['id']}'>
                                    <button type='submit' name='add_to_cart' class='btn-add-to-cart'>أضف إلى العربة</button>
                                </form>
                            </div>
                        </div>";
                }
            } else {
                echo "<p>لا توجد منتجات حسب الفلاتر المحددة.</p>";
            }
            ?>
        </div>
    </section>

    
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

<?php
$stmt->close();
$conn->close();
?>