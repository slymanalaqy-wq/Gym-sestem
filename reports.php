<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require '../includes/db.php';

// التاريخ الافتراضي: اليوم
$search_date = $_GET['date'] ?? date('Y-m-d');

// جلب التقارير ليوم محدد
$stmt = $pdo->prepare("
    SELECT 
        s.full_name,
        s.mobile,
        s.subscription_end,
        p.amount,
        p.payment_date,
        u.username as cashier_name
    FROM payments p
    JOIN subscribers s ON p.subscriper_id = s.id
    JOIN users u ON p.cashier_id = u.id
    WHERE p.payment_date = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$search_date]);
$reports = $stmt->fetchAll();

// حساب إجمالي المبالغ لليوم المعروض
$daily_total = 0;
foreach ($reports as $r) {
    $daily_total += $r['amount'];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقارير اليومية - النادي الرياضي</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap');
        
        body { font-family: 'Tajawal', sans-serif; background: #f4f7f6; }
        
        .header { 
            background: linear-gradient(45deg, #c11325, #8e0e1a); 
            color: white; padding: 25px; text-align: center; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .main-card { 
            border: none; border-radius: 15px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.05); 
            background: white; margin-bottom: 25px; 
        }

        .stats-box {
            background: #fff;
            border-right: 5px solid #28a745;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        }

        .table thead { background-color: #2c3e50; color: white; }
        .btn { border-radius: 8px; transition: 0.3s; }
        .nav-link-custom { text-decoration: none; color: white; float: right; font-size: 0.9em; }
        
        @media print {
            .header, .filter-section, .nav-link-custom { display: none; }
            .main-card { box-shadow: none; border: 1px solid #ddd; }
        }
    </style>
</head>
<body>

    <div class="header animate__animated animate__fadeInDown">
        <a href="index.php" class="nav-link-custom"><i class="fas fa-arrow-right"></i> العودة للرئيسية</a>
        <h2><i class="fas fa-chart-bar"></i> نظام التقارير المالية اليومية</h2>
    </div>

    <div class="container py-4">
        
        <div class="card main-card filter-section animate__animated animate__fadeInUp">
            <div class="card-body">
                <form method="GET" class="row align-items-end">
                    <div class="col-md-4 mb-2">
                        <label class="form-label fw-bold">تحديد التاريخ المراد عرضه:</label>
                        <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($search_date) ?>" required>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-primary w-100 shadow-sm">
                            <i class="fas fa-search"></i> عرض التقرير
                        </button>
                    </div>
                    <div class="col-md-6 mb-2 text-start">
                        <button type="button" onclick="window.print()" class="btn btn-outline-dark shadow-sm">
                            <i class="fas fa-print"></i> طباعة التقرير
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row mb-4 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
            <div class="col-md-6 mb-3">
                <div class="stats-box d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">إجمالي تحصيل اليوم</p>
                        <h3 class="text-success mb-0 fw-bold"><?= number_format($daily_total, 2) ?> <small>ر.ي</small></h3>
                    </div>
                    <i class="fas fa-money-bill-wave fa-3x text-light"></i>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="stats-box d-flex justify-content-between align-items-center" style="border-right-color: #007bff;">
                    <div>
                        <p class="text-muted mb-1">عدد العمليات</p>
                        <h3 class="text-primary mb-0 fw-bold"><?= count($reports) ?> <small>عملية</small></h3>
                    </div>
                    <i class="fas fa-receipt fa-3x text-light"></i>
                </div>
            </div>
        </div>

        <div class="card main-card p-3 animate__animated animate__fadeIn" style="animation-delay: 0.4s;">
            <h5 class="mb-4 text-center fw-bold">كشف المدفوعات ليوم: <span class="text-danger"><?= htmlspecialchars($search_date) ?></span></h5>
            
            <?php if ($reports): ?>
                <table id="reportsTable" class="table table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th>اسم المشترك</th>
                            <th>رقم الجوال</th>
                            <th>المبلغ</th>
                            <th>تاريخ الدفعة</th>
                            <th>نهاية الاشتراك</th>
                            <th>الكاشير</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $r): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($r['full_name']) ?></td>
                            <td><i class="fas fa-mobile-alt text-muted me-1"></i> <?= htmlspecialchars($r['mobile']) ?></td>
                            <td class="text-success fw-bold"><?= number_format($r['amount'], 2) ?></td>
                            <td><?= $r['payment_date'] ?></td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="far fa-clock"></i> <?= $r['subscription_end'] ?>
                                </span>
                            </td>
                            <td><span class="text-muted"><i class="fas fa-user-tag me-1"></i> <?= htmlspecialchars($r['cashier_name']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-folder-open fa-4x text-light mb-3"></i>
                    <p class="h5 text-muted">لا توجد سجلات مدفوعات لهذا اليوم المختار.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            if ($('#reportsTable').length) {
                $('#reportsTable').DataTable({
                    "language": {
                        "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json"
                    },
                    "order": [[ 2, "desc" ]], // الترتيب حسب المبلغ تنازلياً افتراضياً
                    "pageLength": 25
                });
            }
        });
    </script>
</body>
</html>