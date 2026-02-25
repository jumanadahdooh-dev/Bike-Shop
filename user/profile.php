<?php
session_start();
include_once '../dashboard/db.php'; 

// التحقق من أن المستخدم قد سجل الدخول
if (!isset($_SESSION['user'])) {
    header("Location: ../dashboard/login.php");
    exit();
}

// استرجاع البريد الإلكتروني من الجلسة
$user_email = $_SESSION['user'];

// استعلام للحصول على بيانات المستخدم من جدول users
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc(); 

// إغلاق الاتصال
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>البيانات الشخصية | متجر الدراجات</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/cart.css"> <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        /* ستايلات مخصصة لصفحة البروفايل لتظهر بشكل فخم */
        .profile-container {
            max-width: 650px;
            margin: 0 auto;
        }
        .profile-header {
            text-align: center;
            margin-bottom: 35px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        .user-avatar-circle {
            width: 110px;
            height: 110px;
            background: #fff5f4;
            border: 4px solid var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            box-shadow: 0 10px 20px rgba(204, 51, 31, 0.1);
        }
        .user-avatar-circle i {
            font-size: 55px;
            color: var(--primary-color);
        }
        .profile-header h3 { font-size: 24px; font-weight: 800; margin: 0; color: var(--text-dark); }
        .profile-header p { color: #888; font-size: 14px; margin-top: 5px; }

        .styled-form .form-group { margin-bottom: 25px; }
        .styled-form label { 
            display: block; 
            font-weight: 700; 
            margin-bottom: 10px; 
            font-size: 16px;
            color: var(--text-dark);
        }
        .styled-form label i { color: var(--primary-color); margin-left: 10px; font-size: 18px; }

        .main-input {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #f0f0f0;
            border-radius: 15px;
            font-family: 'Cairo';
            font-size: 16px;
            transition: 0.3s;
            background-color: #fdfdfd;
        }
        .main-input:disabled {
            background-color: #f5f6f7;
            color: #95a5a6;
            cursor: not-allowed;
            border-style: dashed;
        }
        .main-input:focus {
            border-color: var(--primary-color);
            background: #fff;
            outline: none;
        }

        .profile-actions { margin-top: 40px; }
        .edit-buttons-group {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 15px;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="../uploads/logo.png" alt="متجر الدراجات" style="width: 50px;">
            <h1><a href="../index.html">متجر الدراجات</a></h1>
        </div>
        <nav class="main-nav">
            <ul>
                <li><a href="../index.html">الرئيسية</a></li>
                <li><a href="products.php">المنتجات</a></li>
                <li><a href="cart.php">عربة التسوق</a></li>
            </ul>
        </nav>
        <div class="header-actions">
            <a href="../dashboard/logout.php" class="btn-logout-head"><i class="fas fa-sign-out-alt"></i> خروج</a>
        </div>
    </header>

<section class="cart-section">
        <div class="cart-title-area">
            <h2><i class="fas fa-id-badge"></i> حسابي الشخصي</h2>
            <a href="../index.html" class="history-link"><i class="fas fa-home"></i> العودة للرئيسية</a>
        </div>

        <div class="cart-main-box profile-container">
                <div class="profile-header">
                <div class="user-avatar-circle">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3>أهلاً بك، <?php echo htmlspecialchars($user['fullName']); ?></h3>
                <p>تستطيع تحديث بياناتك الشخصية المسجلة لدينا في أي وقت</p>
            </div>

            <form action="update-profile.php" method="POST" class="styled-form">
                <div class="form-group">
                    <label for="fullName"><i class="fas fa-user-edit"></i> الاسم الكامل:</label>
                    <input type="text" id="fullName" name="fullName" value="<?php echo htmlspecialchars($user['fullName']); ?>" disabled class="editable main-input">
                </div>

                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope-open-text"></i> البريد الإلكتروني:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled class="editable main-input">
                </div>

                <div class="form-group">
                    <label for="phoneNumber"><i class="fas fa-mobile-alt"></i> رقم الجوال:</label>
                    <input type="text" id="phoneNumber" name="phoneNumber" value="<?php echo htmlspecialchars($user['phoneNumber']); ?>" disabled class="editable main-input">
                </div>

                <div class="profile-actions">
                    <button type="button" id="update-btn" class="btn-checkout w-100" onclick="toggleEdit()">
                        <i class="fas fa-user-cog"></i> تحديث بيانات الحساب
                    </button>
                    
                    <div id="edit-buttons" class="edit-buttons-group" style="display: none;">
                        <button type="submit" id="save-btn" class="btn-checkout">
                            <i class="fas fa-save"></i> حفظ التعديلات
                        </button>
                        <button type="button" id="cancel-btn" class="btn-continue" onclick="cancelEdit()">
                            <i class="fas fa-times-circle"></i> إلغاء
                        </button>
                    </div>
                </div>
            </form>
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

    <script>
        // دالة تفعيل وضع التعديل
        function toggleEdit() {
            var inputs = document.querySelectorAll('.editable');
            var btnUpdate = document.getElementById('update-btn');
            var editButtons = document.getElementById('edit-buttons');

            // تفعيل المدخلات
            inputs.forEach(input => {
                input.disabled = false;
                input.style.borderStyle = "solid";
            });

            // إظهار وإخفاء الأزرار
            btnUpdate.style.display = "none";
            editButtons.style.display = "grid";
            
            // تركيز على أول حقل تلقائياً
            document.getElementById('fullName').focus();
        }

        // دالة إلغاء التعديل
        function cancelEdit() {
            var inputs = document.querySelectorAll('.editable');
            var btnUpdate = document.getElementById('update-btn');
            var editButtons = document.getElementById('edit-buttons');

            // إغلاق المدخلات
            inputs.forEach(input => {
                input.disabled = true;
                input.style.borderStyle = "dashed";
            });

            // إعادة الأزرار لحالتها الأصلية
            editButtons.style.display = "none";
            btnUpdate.style.display = "flex";
        }
    </script>
</body>
</html>