<?php
session_start();

// التحقق من أن المستخدم مسجل دخوله
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || strlen($new_password) < 6) {
        $error = "كلمة المرور يجب أن لا تقل عن 6 أحرف.";
    } elseif ($new_password !== $confirm_password) {
        $error = "كلمة المرور وتأكيدها غير متطابقين.";
    } else {
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=gym_db;charset=utf8mb4", 'root', '');
            
            // تشفير كلمة المرور الجديدة
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // تحديث كلمة المرور للمستخدم الحالي
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $_SESSION['user_id']]);

            $message = "تم تغيير كلمة المرور بنجاح!";
        } catch (Exception $e) {
            $error = "حدث خطأ أثناء التحديث.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الملف الشخصي - تغيير كلمة المرور</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Tajawal', sans-serif; }
        .profile-card { max-width: 500px; margin: 50px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .btn-update { background-color: #c11325; color: white; border: none; }
        .btn-update:hover { background-color: #a00f1e; color: white; }
    </style>
</head>
<body>

<div class="container">
    <div class="profile-card">
        <h3 class="text-center mb-4">تحديث الملف الشخصي</h3>
        <p class="text-center text-muted">أهلاً بك، <?= htmlspecialchars($_SESSION['full_name']) ?></p>
        <hr>

        <?php if ($message): ?>
            <div class="alert alert-success text-center"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger text-center"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">كلمة المرور الجديدة</label>
                <input type="password" name="new_password" class="form-control" placeholder="أدخل كلمة مرور قوية" required>
            </div>
            <div class="mb-3">
                <label class="form-label">تأكيد كلمة المرور</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="أعد كتابة كلمة المرور" required>
            </div>
            <button type="submit" class="btn btn-update w-100">حفظ التغييرات</button>
            <a href="dashboard.php" class="btn btn-light w-100 mt-2">العودة للوحة التحكم</a>
        </form>
    </div>
</div>

</body>
</html>