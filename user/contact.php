<?php
session_start();
include '../dashboard/db.php'; // تأكد من المسار الصحيح للملف

// التحقق من أن المستخدم قد سجل الدخول
if (!isset($_SESSION['user'])) {
    header("Location: ../dashboard/login.php");
    exit();
}

// إرسال الرسالة
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $message = $_POST['message'];

    // تحقق من وجود البيانات
    if (empty($name) || empty($email) || empty($message)) {
        $error = "يرجى ملء جميع الحقول.";
    } else {
        // إدخال البيانات في قاعدة البيانات
        $sql = "INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $name, $email, $message);

        if ($stmt->execute()) {
            $success = "تم إرسال رسالتك بنجاح!";
        } else {
            $error = "حدث خطأ أثناء إرسال الرسالة.";
        }

        $stmt->close(); 
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>متجر الدراجات - اتصل بنا</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/contact.css">
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
            window.location.href = "../index.html";
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

    <section class="contact-section">
        <h2>اتصل بنا</h2>

        <form method="POST" action="contact.php">
            <?php if (isset($success)) { echo "<p class='success' style='color: green; font-weight: bold;'>$success</p>"; } ?>
            <?php if (isset($error)) { echo "<p class='error' style='color: red; font-weight: bold;'>$error</p>"; } ?>

            <label for="name">الاسم الكامل:</label>
            <input type="text" id="name" name="name" required>

            <label for="email">البريد الإلكتروني:</label>
            <input type="email" id="email" name="email" required>

            <label for="message">رسالتك:</label>
            <textarea id="message" name="message" rows="4" required></textarea>

            <button type="submit" class="btn-primary">إرسال</button>
        </form>

        <div class="contact-info">
            <p>يمكنك أيضًا الاتصال بنا عبر:</p>
            <ul>
                <li>رقم الهاتف: 123-456-789</li>
                <li>البريد الإلكتروني: contact@bikeshop.com</li>
            </ul>
        </div>

        <div class="map-section">
            <h3>موقعنا</h3>
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3401.5562!2d34.45!3d31.5!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMzHCsDMwJzAwLjAiTiAzNMKwMjcnMDAuMCJF!5e0!3m2!1sar!2s!4v1614123456789!5m2!1sar!2s" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
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