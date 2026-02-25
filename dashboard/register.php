<?php
include "../dashboard/db.php";
session_start();

$error_msg = "";
$success_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = $_POST["fullName"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm-password"];
    $phoneNumber = $_POST["phoneNumber"];
    $role = $_POST["role"];

    // 1. التحقق من تطابق كلمة المرور
    if ($password !== $confirm_password) {
        $error_msg = "عذراً، كلمة المرور وتأكيدها غير متطابقين.";
    } else {
        // 2. التحقق من وجود البريد الإلكتروني مسبقاً
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error_msg = "هذا البريد الإلكتروني مسجل بالفعل، حاول تسجيل الدخول.";
        } else {
            // 3. التحقق من صلاحية البريد للأدمن
            if ($role == 'admin' && strpos($email, '@admin.com') === false) {
                $error_msg = "هذا البريد غير مصرح له بامتيازات المدير.";
            } else {
                // 4. تشفير كلمة المرور وإدخال البيانات
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (fullName, email, password, phoneNumber, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $fullName, $email, $hashed_password, $phoneNumber, $role);

                if ($stmt->execute()) {
                    $success_msg = "تم إنشاء الحساب بنجاح! سيتم توجيهك للدخول...";
                    header("refresh:2;url=login.php");
                } else {
                    $error_msg = "حدث خطأ أثناء التسجيل: " . $conn->error;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب | متجر الدراجات</title>
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
            background-color: #000;
            border-bottom: 1px solid var(--border-color);
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo h1 a { color: var(--primary-color); text-decoration: none; font-weight: 900; }

        .register-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 40px;
            background: var(--card-black);
            border-radius: 25px;
            border: 1px solid var(--border-color);
            box-shadow: 0 15px 40px rgba(0,0,0,0.4);
        }

        .register-container h2 { text-align: center; font-weight: 900; margin-bottom: 30px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #aaa; font-size: 14px; }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            background: #000;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: #fff;
            font-family: 'Cairo';
            box-sizing: border-box;
        }

        .form-group input:focus { border-color: var(--primary-color); outline: none; }

        .btn-register-submit {
            width: 100%;
            padding: 15px;
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

        .btn-register-submit:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(204, 51, 31, 0.3); }

        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .alert-error { background: rgba(255, 71, 87, 0.1); color: #ff4757; border: 1px solid #ff4757; }
        .alert-success { background: rgba(46, 213, 115, 0.1); color: #2ed573; border: 1px solid #2ed573; }

        .footer-text { text-align: center; margin-top: 20px; color: #666; }
        .footer-text a { color: var(--primary-color); text-decoration: none; font-weight: 700; }

        footer { text-align: center; padding: 40px; color: #444; border-top: 1px solid var(--border-color); margin-top: 50px; }
    </style>
</head>
<body>

<header>
    <div class="logo">
        <h1><a href="../index.html">متجر الدراجات</a></h1>
    </div>
    <nav class="main-nav">
        <ul style="list-style:none; display:flex; gap:20px;">
            <li><a href="../index.html" style="color:#fff; text-decoration:none;">الرئيسية</a></li>
        </ul>
    </nav>
    <div>
        <a href="login.php" style="color:var(--primary-color); text-decoration:none; font-weight:700;">تسجيل الدخول</a>
    </div>
</header>

<main class="register-container">
    <h2>إنشاء حساب جديد</h2>

    <?php if ($error_msg): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error_msg; ?></div>
    <?php endif; ?>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>الاسم الكامل</label>
            <input type="text" name="fullName" placeholder="أدخل اسمك الثلاثي" required>
        </div>
        <div class="form-group">
            <label>البريد الإلكتروني</label>
            <input type="email" name="email" placeholder="example@mail.com" required>
        </div>
        <div class="form-group">
            <label>رقم الجوال</label>
            <input type="text" name="phoneNumber" placeholder="05xxxxxxxx" required>
        </div>
        <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div>
                <label>كلمة المرور</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <div>
                <label>تأكيد الكلمة</label>
                <input type="password" name="confirm-password" placeholder="••••••••" required>
            </div>
        </div>
        <div class="form-group">
            <label>نوع الحساب</label>
            <select name="role">
                <option value="user">مستخدم (عميل)</option>
                <option value="admin">مدير (مسؤول نظام)</option>
            </select>
        </div>
        <button type="submit" class="btn-register-submit">إنشاء الحساب</button>
    </form>

    <div class="footer-text">
        لديك حساب بالفعل؟ <a href="login.php">سجل دخولك هنا</a>
    </div>
</main>

<footer>
    <p>&copy; 2026 متجر الدراجات. جميع الحقوق محفوظة.</p>
</footer>

</body>
</html>