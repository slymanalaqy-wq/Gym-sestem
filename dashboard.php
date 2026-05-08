<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

require '../includes/db.php';

$role = $_SESSION['role'] ?? '';

if (empty($role)) {
    die("ليس لديك صلاحية للوصول لهذه الصفحة.");
}

// --- جلب التنبيهات (الاشتراكات المنتهية أو القريبة من الانتهاء) ---
$today = date('Y-m-d');
$after_3_days = date('Y-m-d', strtotime('+3 days'));

$stmt_alerts = $pdo->prepare("
    SELECT full_name, subscription_end, mobile 
    FROM subscribers 
    WHERE subscription_end <= ? 
    ORDER BY subscription_end ASC 
    LIMIT 5
");
$stmt_alerts->execute([$after_3_days]);
$alerts = $stmt_alerts->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <a href="profile.php" class="btn btn-outline-dark">إعدادات الحساب</a>
    <title>لوحة التحكم - النادي الرياضي</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap');
        
        body { font-family: 'Tajawal', sans-serif; background: #f4f7f6; margin: 0; }
        
        .header { 
            background: linear-gradient(45deg, #c11325, #8e0e1a); 
            color: white; padding: 30px; text-align: center; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;
        }

        .container { padding: 30px 20px; }
        
        .alert-section {
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            border-right: 5px solid #ffc107;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .menu-card {
            background: white; padding: 25px; text-align: center;
            text-decoration: none; color: #333; border-radius: 15px;
            transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            display: flex; flex-direction: column; align-items: center; border: 1px solid #eee;
        }

        .menu-card i { font-size: 40px; color: #c11325; margin-bottom: 15px; }
        
        /* ألوان مخصصة للأزرار الجديدة لتمييزها */
        .card-gate i { color: #e67e22; } /* برتقالي للبوابة */
        .card-logs i { color: #2c3e50; } /* كحلي للسجلات */
        .card-attendance i { color: #27ae60; } /* أخضر للحضور */
        .card-finance i { color: #8e44ad; } /* بنفسجي للمالية */

        .menu-card:hover {
            transform: translateY(-10px); background: #c11325; color: white;
            box-shadow: 0 10px 20px rgba(193, 19, 37, 0.2);
        }
        .menu-card:hover i { color: white; }
        .btn-logout { margin-top: 40px; display: inline-block; padding: 10px 30px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; }
        
        .section-title { margin: 30px 0 15px; font-weight: bold; color: #555; border-right: 4px solid #c11325; padding-right: 10px; }
    </style>
</head>
<body>

    <div class="header animate__animated animate__fadeInDown">
        <i class="fas fa-dumbbell fa-3x mb-3"></i>
        <h2>لوحة تحكم النظام</h2>
    </div>

    <div class="container">
        
        <?php if (!empty($alerts)): ?>
        <div class="alert-section animate__animated animate__headShake">
            <h5 class="text-warning fw-bold mb-3">
                <i class="fas fa-exclamation-triangle"></i> تنبيهات الاشتراكات (قرب الانتهاء/منتهية):
            </h5>
            <div class="row">
                <?php foreach ($alerts as $alert): 
                    $is_expired = ($alert['subscription_end'] < $today);
                ?>
                <div class="col-md-4 mb-2">
                    <div class="p-2 border rounded <?= $is_expired ? 'bg-danger-subtle' : 'bg-light' ?>">
                        <strong><?= htmlspecialchars($alert['full_name']) ?></strong><br>
                        <small class="<?= $is_expired ? 'text-danger' : 'text-muted' ?>">
                            <?= $is_expired ? 'انتهى في: ' : 'ينتهي في: ' ?> <?= $alert['subscription_end'] ?>
                        </small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="welcome-box bg-white p-3 rounded-4 mb-4 shadow-sm animate__animated animate__fadeInRight">
            <span class="fs-5 fw-bold"><i class="fas fa-user-circle"></i> مرحباً، <?= htmlspecialchars($_SESSION['full_name']) ?>!</span>
        </div>
         
        <h5 class="section-title">إدارة الصالة والوصول السريع</h5>
        <div class="menu-grid mb-5">
            <a href="gate_entry.php" class="menu-card card-gate animate__animated animate__zoomIn">
                <i class="fas fa-door-open"></i>
                <span>بوابة الدخول</span>
            </a>
            <a href="attendance.php" class="menu-card card-attendance animate__animated animate__zoomIn" style="animation-delay: 0.1s;">
                <i class="fas fa-user-check"></i>
                <span>الحضور الآن</span>
            </a>
            <a href="logs.php" class="menu-card card-logs animate__animated animate__zoomIn" style="animation-delay: 0.2s;">
                <i class="fas fa-history"></i>
                <span>سجل المراقبة</span>
            </a>
             <a href="financial_report.php" class="menu-card card-finance animate__animated animate__zoomIn" style="animation-delay: 0.3s;">
                <i class="fas fa-coins"></i>
                <span>التقرير المالي</span>
            </a>
        </div>

        <h5 class="section-title">النظام الإداري العام</h5>
        <div class="menu-grid">
            <a href="subscribers.php" class="menu-card animate__animated animate__zoomIn">
                <i class="fas fa-users"></i>
                <span>إدارة المشتركين</span>
            </a>
            <a href="payments.php" class="menu-card animate__animated animate__zoomIn" style="animation-delay: 0.1s;">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>الإدارة المالية</span>
            </a>
            <a href="staff.php" class="menu-card animate__animated animate__zoomIn" style="animation-delay: 0.2s;">
                <i class="fas fa-user-tie"></i>
                <span>إدارة الموظفين</span>
            </a>
            <a href="reports.php" class="menu-card animate__animated animate__zoomIn" style="animation-delay: 0.3s;">
                <i class="fas fa-chart-line"></i>
                <span>التقارير اليومية</span>
            </a>
        </div>

        <?php if ($_SESSION['role'] === 'admin'): ?>
        <div class="text-center mt-5">
            <a href="backup.php" class="btn btn-dark btn-lg shadow-sm px-4" style="border-radius: 10px; background-color: #2c3e50;">
                <i class="fas fa-database me-2"></i> تحميل نسخة احتياطية للقاعدة (.sql)
            </a>
            <p class="text-muted small mt-2">ينصح بتحميل نسخة أسبوعياً للحفاظ على البيانات</p>
        </div>
        <?php endif; ?>

        <div class="text-center">
            <a href="logout.php" class="btn-logout mb-5"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
           <!-- <a href="profile.php" class="btn btn-outline-dark">إعدادات الحساب</a> -->
        </div>
    </div>
</body>
</html>