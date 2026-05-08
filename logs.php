<?php
session_start();
// التأكد من أن المشرف فقط هو من يدخل لهذه الصفحة
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require '../includes/db.php';

// جلب آخر 100 حركة تمت في النظام مع اسم الشخص الذي قام بها
$stmt = $pdo->query("
    SELECT l.*, u.full_name as admin_name 
    FROM activity_log l 
    LEFT JOIN users u ON l.user_id = u.id 
    ORDER BY l.created_at DESC 
    LIMIT 100
");
$logs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل النشاطات - النظام الرياضي</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap');
        body { font-family: 'Tajawal', sans-serif; background: #f8f9fa; }
        .header { 
            background: #2c3e50; color: white; padding: 25px; 
            text-align: center; border-bottom: 5px solid #ffc107;
        }
        .log-card { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .badge-action { font-size: 0.85em; padding: 5px 10px; }
        .time-cell { direction: ltr; text-align: right; color: #6c757d; font-size: 0.9em; }
    </style>
</head>
<body>

    <div class="header animate__animated animate__fadeInDown">
        <h2><i class="fas fa-history me-2"></i> سجل مراقبة النشاطات</h2>
        <p class="mb-0 text-white-50">تتبع كافة عمليات الإضافة، التعديل، والحذف في النظام</p>
    </div>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right me-1"></i> العودة للرئيسية
            </a>
            <span class="badge bg-dark">آخر 100 عملية مسجلة</span>
        </div>

        <div class="card log-card animate__animated animate__fadeInUp">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3 px-4">المسؤول</th>
                                <th>نوع العملية</th>
                                <th>التفاصيل</th>
                                <th>الوقت والتاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($logs) > 0): ?>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="px-4">
                                        <i class="fas fa-user-shield text-muted me-2"></i>
                                        <strong><?= htmlspecialchars($log['admin_name'] ?? 'مستخدم محذوف') ?></strong>
                                    </td>
                                    <td>
                                        <?php 
                                        $badge_class = 'bg-primary';
                                        if(strpos($log['action_type'], 'حذف') !== false) $badge_class = 'bg-danger';
                                        if(strpos($log['action_type'], 'تعديل') !== false) $badge_class = 'bg-warning text-dark';
                                        ?>
                                        <span class="badge badge-action <?= $badge_class ?>">
                                            <?= htmlspecialchars($log['action_type']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($log['description']) ?></td>
                                    <td class="time-cell">
                                        <?= date('Y-m-d | h:i A', strtotime($log['created_at'])) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fas fa-info-circle fa-3x mb-3"></i><br>
                                        لا توجد نشاطات مسجلة حتى الآن.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>
</html>