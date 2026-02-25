<?php
session_start();
include '../dashboard/db.php'; // تأكدي من صحة المسار

$error = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    // استخدام الاستعلامات المحضرة (Prepared Statements) للأمان
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        if (password_verify($password, $row['password'])) {
            $_SESSION["user"] = $email;
            $_SESSION["role"] = $row['role'];
            $_SESSION["user_id"] = $row['id'];

            if ($row['role'] == 'admin') {
                header("Location: ../admin/admin-dashboard.php");
                exit();
            } else {
                echo "<script>
                        localStorage.setItem('userLoggedIn', 'true');
                        window.location.href = '../index.html';
                      </script>";
                exit();
            }
        } else {
            $error = "عذراً، كلمة المرور التي أدخلتها غير صحيحة.";
        }
    } else {
        $error = "هذا البريد الإلكتروني غير مسجل لدينا.";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | متجر الدراجات</title>
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
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* --- الهيدر (نفس تصميم صفحة الـ Register) --- */
        header {
            background-color: #000;
            border-bottom: 1px solid var(--border-color);
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo h1 a { color: var(--primary-color); text-decoration: none; font-weight: 900; font-size: 24px; }
        
        .main-nav ul { list-style: none; display: flex; gap: 25px; margin: 0; padding: 0; }
        .main-nav a { color: #fff; text-decoration: none; font-weight: 700; transition: 0.3s; }
        .main-nav a:hover { color: var(--primary-color); }

        /* --- حاوية تسجيل الدخول (نفس تصميم الـ Register) --- */
        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .login-card {
            background: var(--card-black);
            border: 1px solid var(--border-color);
            padding: 40px;
            border-radius: 25px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        }

        .login-card h2 { color: #fff; font-weight: 900; margin-bottom: 10px; text-align: center; }
        .login-card p.subtitle { color: #666; text-align: center; margin-bottom: 30px; font-size: 15px; }

        /* --- التنبيهات --- */
        .alert-error {
            background: rgba(255, 71, 87, 0.1);
            color: #ff4757;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid #ff4757;
            margin-bottom: 25px;
            font-size: 14px;
            text-align: center;
        }

        /* --- مدخلات البيانات --- */
        .form-group { margin-bottom: 20px; text-align: right; }
        .form-group label { display: block; color: #aaa; margin-bottom: 8px; font-weight: 700; font-size: 14px; }
        .form-group input {
            width: 100%;
            padding: 15px;
            background: #000;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: #fff;
            box-sizing: border-box;
            font-family: 'Cairo';
            transition: 0.3s;
        }
        .form-group input:focus { border-color: var(--primary-color); outline: none; box-shadow: 0 0 10px rgba(204, 51, 31, 0.1); }

        /* --- الأزرار --- */
        .btn-login-submit {
            width: 100%;
            padding: 16px;
            background: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }
        .btn-login-submit:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(204, 51, 31, 0.3); }

        .footer-text { text-align: center; margin-top: 25px; color: #666; font-size: 15px; }
        .footer-text a { color: var(--primary-color); text-decoration: none; font-weight: 700; }

        footer {
            padding: 30px;
            text-align: center;
            border-top: 1px solid var(--border-color);
            color: #444;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <header>
        <div class="logo">
            <h1><a href="../index.html">متجر الدراجات</a></h1>
        </div>
        <nav class="main-nav">
            <ul>
                <li><a href="../index.html">الرئيسية</a></li>
            </ul>
        </nav>
        <div>
            <a href="register.php" style="color:var(--primary-color); text-decoration:none; font-weight:700;">إنشاء حساب</a>
        </div>
    </header>

    <main class="login-container">
        <div class="login-card">
            <h2>تسجيل الدخول</h2>
            <p class="subtitle">مرحباً بك مجدداً! أدخل بياناتك للمتابعة</p>

            <?php if ($error != ""): ?>
                <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label>البريد الإلكتروني</label>
                    <input type="email" name="email" placeholder="example@mail.com" required>
                </div>
                <div class="form-group">
                    <label>كلمة المرور</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-login-submit">دخول</button>
            </form>

            <div class="footer-text">
                ليس لديك حساب؟ <a href="register.php">أنشئ حساباً جديداً</a>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 متجر الدراجات. جميع الحقوق محفوظة.</p>
    </footer>

</body>
</html>