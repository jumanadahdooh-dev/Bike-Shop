<?php
session_start();
include '../dashboard/db.php';  

if (!isset($_SESSION['user'])) {
    header("Location: ../dashboard/login.php");
    exit();
}

$category_sql = "SELECT * FROM categories";
$category_result = $conn->query($category_sql);

if (!$category_result) { die("خطأ في استعلام الفئات: " . $conn->error); }

$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$sql = "SELECT * FROM offers WHERE 1"; 

if ($category_filter != '') {
    $sql .= " AND category_id = ?";
}

$stmt = $conn->prepare($sql);

if ($stmt === false) { die("فشل في تحضير الاستعلام: " . $conn->error); }

if ($category_filter != '') {
    $stmt->bind_param("i", $category_filter);
}

$stmt->execute();
$offers = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $offer_id = $_POST['offer_id']; 
    $user_email = $_SESSION['user'];

    $offer_sql = "SELECT product_id FROM offers WHERE id = ?";
    $stmt_offer = $conn->prepare($offer_sql);
    $stmt_offer->bind_param("i", $offer_id);
    $stmt_offer->execute();
    $offer = $stmt_offer->get_result()->fetch_assoc();
    $product_id = $offer['product_id'];  

    $user_sql = "SELECT id FROM users WHERE email = ?";
    $stmt_user = $conn->prepare($user_sql);
    $stmt_user->bind_param("s", $user_email);
    $stmt_user->execute();
    $user = $stmt_user->get_result()->fetch_assoc();
    $user_id = $user['id'];

    $check_sql = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("ii", $user_id, $product_id);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result();

    if ($check_result->num_rows > 0) {
        $update_sql = "UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?";
        $stmt_update = $conn->prepare($update_sql);
        $stmt_update->bind_param("ii", $user_id, $product_id);
        $stmt_update->execute();
    } else {
        $insert_sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)";
        $stmt_insert = $conn->prepare($insert_sql);
        $stmt_insert->bind_param("ii", $user_id, $product_id);
        $stmt_insert->execute();
    }

    header("Location: cart.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أقوى العروض - متجر الدراجات</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/offers.css">
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
                <li><a href="offers.php" class="active">عروض</a></li>
                <li><a href="contact.php">اتصل بنا</a></li>
            </ul>
        </nav>
        <div class="header-actions">
            <div id="login-register" style="display: flex;">
                <a href="../dashboard/login.php" class="btn-login"><i class="fas fa-sign-in-alt"></i> دخول</a>
            </div>
            <div id="user-actions" style="display: none;">
                <a href="cart.php" class="btn-cart-icon"><i class="fas fa-shopping-cart"></i><span class="cart-count">4</span></a>
                <a href="javascript:void(0);" class="btn-logout" onclick="logout()"><i class="fas fa-sign-out-alt"></i> خروج</a>
            </div>
        </div>
    </header>

    <section class="offers-hero">
        <h1>عروض حصرية لفترة محدودة <span>%</span></h1>
        <p>احصل على دراجة أحلامك بأفضل سعر في السوق</p>
    </section>

    <section class="filters-section">
        <div class="filters-buttons">
            <a href="offers.php" class="btn-filter">كل العروض</a>
            <?php while ($category = $category_result->fetch_assoc()) { ?>
                <a href="offers.php?category=<?php echo $category['id']; ?>" class="btn-filter <?php if ($category['id'] == $category_filter) echo 'active'; ?>">
                    <?php echo $category['name']; ?>
                </a>
            <?php } ?>
        </div>
    </section>

    <section class="offers-section">
        <div class="offer-list">
            <?php
            if ($offers->num_rows > 0) {
                while ($offer = $offers->fetch_assoc()) {
                    echo "<div class='offer-card animate-card'>
                            <div class='offer-badge'>عرض خاص</div>
                            <img src='../uploads/{$offer['image']}' alt='{$offer['name']}' class='offer-image'>
                            <div class='offer-details'>
                                <h3>{$offer['name']}</h3>
                                <p>{$offer['description']}</p>
                                <div class='price-container'>
                                    <span class='price'>{$offer['price']} $</span>
                                </div>
                                <form method='POST'>
                                    <input type='hidden' name='offer_id' value='{$offer['id']}'>
                                    <button type='submit' name='add_to_cart' class='btn-add-offer'>
                                        <i class='fas fa-cart-arrow-down'></i> اقتنص العرض الآن
                                    </button>
                                </form>
                            </div>
                        </div>";
                }
            } else {
                echo "<p class='no-offers'>لا توجد عروض حالياً في هذا القسم.</p>";
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