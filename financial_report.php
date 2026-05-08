<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit(); }
require '../includes/db.php';

// حساب إجمالي الدخل من المدفوعات
$stmt1 = $pdo->query("SELECT SUM(amount) as total_collected FROM payments");
$total_collected = $stmt1->fetch()['total_collected'] ?? 0;

// حساب إجمالي المبالغ المتبقية (الديون)
$stmt2 = $pdo->query("SELECT SUM(amount) as total_debt FROM payments");
$total_debt = $stmt2->fetch()['total_debt'] ?? 0;

// عدد المشتركين النشطين حالياً
$stmt3 = $pdo->query("SELECT COUNT(*) as active_count FROM subscribers WHERE subscription_end >= CURDATE()");
$active_count = $stmt3->fetch()['active_count'] ?? 0;
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>التقرير المالي</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <style>
        body { font-family: 'Tajawal', sans-serif; background: #f4f7f6; padding: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); text-align: center; border-bottom: 5px solid #c11325; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4 text-center">التقرير المالي العام</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <h5 class="text-muted">إجمالي المبالغ المحصلة</h5>
                    <h2 class="text-success"><?= number_format($total_collected, 2) ?> $</h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h5 class="text-muted">إجمالي الديون (المتبقي)</h5>
                    <h2 class="text-danger"><?= number_format($total_debt, 2) ?> $</h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h5 class="text-muted">المشتركين النشطين</h5>
                    <h2 class="text-primary"><?= $active_count ?></h2>
                </div>
            </div>
        </div>
        <div class="text-center mt-5">
            <a href="dashboard.php" class="btn btn-secondary">العودة للوحة التحكم</a>
        </div>
    </div>
</body>
</html>