
<?php

session_start();
setcookie("test_cookie", "work_fine", time() + 3600, "/");
// إذا كان المستخدم مسجل دخوله مسبقًا، أعد توجيهه إلى لوحة التحكم
if (isset($_SESSION['user_id'])) {
    header('Location: admin/dashboard.php');
    exit();
}

$error = '';

// معالجة طلب تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember']); // التحقق إذا تم اختيار "تذكرني"

    if (empty($username) || empty($password)) {
        $error = "يرجى إدخال اسم المستخدم وكلمة المرور.";
    } else {
        try {
            // الاتصال بقاعدة البيانات
            $pdo = new PDO("mysql:host=localhost;dbname=gym_db;charset=utf8mb4", 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // تسجيل بيانات الجلسة (Session)
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                session_regenerate_id(true);

                // --- إضافة الكوكيز هنا ---
                if ($remember) {
                    // حفظ اسم المستخدم لمدة 30 يوم
                    setcookie("remembered_user", $username, time() + (30 * 24 * 60 * 60), "/");
                } else {
                    // مسح الكوكي إذا لم يتم اختيار "تذكرني"
                    if (isset($_COOKIE['remembered_user'])) {
                        setcookie("remembered_user", "", time() - 3600, "/");
                    }
                }
                // -----------------------

                header('Location: admin/dashboard.php');
                exit();
            } else {
                $error = "خطأ في اسم المستخدم أو كلمة المرور.";
            }
        } catch (Exception $e) {
            $error = "عذراً، فشل الاتصال بقاعدة البيانات.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - النظام الرياضي</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap');
        body { font-family: 'Tajawal', sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .login-card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); width: 100%; max-width: 400px; border-top: 6px solid #c11325; }
        .login-header { text-align: center; margin-bottom: 30px; }
        .login-header i { color: #c11325; font-size: 50px; margin-bottom: 15px; }
        .login-header h2 { font-weight: 700; color: #2c3e50; }
        .form-control { border-radius: 10px; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; }
        .btn-login { background: #c11325; border: none; width: 100%; padding: 12px; border-radius: 10px; color: white; font-size: 18px; font-weight: bold; transition: 0.3s; }
        .btn-login:hover { background: #a00f1e; transform: translateY(-2px); }
        .error-msg { background: #fff5f5; color: #c11325; padding: 10px; border-radius: 8px; text-align: center; margin-bottom: 20px; font-size: 14px; border: 1px solid #fed7d7; }
    </style>
</head>
<body>

    <div class="login-card animate__animated animate__fadeIn">
        <div class="login-header">
            <i class="fas fa-dumbbell"></i>
            <h2>نظام النادي الرياضي</h2>
            <p class="text-muted small">يرجى تسجيل الدخول للمتابعة</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-msg animate__animated animate__shakeX">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="اسم المستخدم" required 
                           value="<?= htmlspecialchars($_COOKIE['remembered_user'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="كلمة المرور" required>
                </div>
            </div>

            <div class="mb-3 form-check text-end">
                <input type="checkbox" name="remember" class="form-check-input" id="remember" <?= isset($_COOKIE['remembered_user']) ? 'checked' : '' ?>>
                <label class="form-check-label small text-muted" for="remember">تذكر اسم المستخدم</label>
            </div>

            <button type="submit" class="btn btn-login shadow-sm">
                دخول <i class="fas fa-sign-in-alt ms-2"></i>
            </button>
        </form>

        <div class="mt-4 text-center">
            <small class="text-muted">نظام إدارة العضويات والمدفوعات v2.0</small>
        </div>
    </div>

</body>
</html>