
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}
$is_admin = ($_SESSION['role'] === 'admin');

require '../includes/db.php';

// ثابت قيمة الاشتراك الشهري
$MONTHLY_FEE = 8000;

// === الحذف (فقط للمشرف) ===
if ($is_admin && isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $stmt = $pdo->prepare("SELECT amount, subscriper_id FROM payments WHERE id = ?");
    $stmt->execute([$id]);
    $payment = $stmt->fetch();
    if ($payment) {
        $pdo->prepare("UPDATE subscribers SET balance = balance - ? WHERE id = ?")->execute([$payment['amount'], $payment['subscriper_id']]);
        $pdo->prepare("DELETE FROM payments WHERE id = ?")->execute([$id]);
    }
    header("Location: payments.php?message=deleted");
    exit();
}

// === عرض البيانات ===
// تعديل لجلب الرصيد الحالي للمشتركين
$subscribers = $pdo->query("SELECT id, full_name, mobile, balance FROM subscribers ORDER BY full_name")->fetchAll();
$payments = $pdo->query("
    SELECT p.*, s.full_name as subscriper_name, u.username as cashier_name
    FROM payments p
    JOIN subscribers s ON p.subscriper_id = s.id
    JOIN users u ON p.cashier_id = u.id
    ORDER BY p.created_at DESC
")->fetchAll();

// === معالجة الإضافة ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subscriber_id = (int)$_POST['subscriper_id'];
    $amount = (float)$_POST['amount'];
    $payment_date = $_POST['payment_date'];
    $notes = trim($_POST['notes'] ?? '');

    if ($subscriber_id <= 0 || $amount <= 0 || empty($payment_date)) {
        $error = "يرجى تعبئة جميع الحقول المطلوبة.";
    } else {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("INSERT INTO payments (subscriper_id, cashier_id, amount, payment_date, notes) VALUES (?, ?, ?, ?, ?)")
                 ->execute([$subscriber_id, $_SESSION['user_id'], $amount, $payment_date, $notes]);
            $pdo->prepare("UPDATE subscribers SET balance = balance + ? WHERE id = ?")
                 ->execute([$amount, $subscriber_id]);
            $pdo->commit();
            header("Location: payments.php?message=added");
            exit();
        } catch (Exception $e) {
            $pdo->rollback();
            $error = "حدث خطأ أثناء التسجيل.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإدارة المالية - النظام الرياضي</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap');
        body { font-family: 'Tajawal', sans-serif; background: #f4f7f6; }
        .header { background: linear-gradient(45deg, #c11325, #8e0e1a); color: white; padding: 25px; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .main-card { border: none; border-radius: 15px; box-shadow: 0 8px 20px rgba(0,0,0,0.06); background: white; margin-bottom: 30px; }
        .form-label { font-weight: bold; color: #2c3e50; }
        .table thead { background-color: #2c3e50; color: white; }
        .btn { border-radius: 8px; transition: 0.3s; }
        .badge-amount { font-size: 1.1em; padding: 8px 12px; }
        .nav-back { text-decoration: none; color: white; float: right; font-size: 0.9em; }
        .remaining-box { background: #fff3cd; border: 1px solid #ffeeba; padding: 10px; border-radius: 8px; font-weight: bold; color: #856404; }
    </style>
</head>
<body>

    <div class="header animate__animated animate__fadeInDown">
        <a href="dashboard.php" class="nav-back"><i class="fas fa-arrow-right"></i> العودة للرئيسية</a>
        <h2><i class="fas fa-cash-register"></i> تسجيل ومعالجة المدفوعات</h2>
    </div>

    <div class="container py-4">
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger animate__animated animate__shakeX"><?= $error ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show animate__animated animate__flipInX">
                <i class="fas fa-check-circle"></i> 
                <?= $_GET['message'] === 'added' ? 'تم تسجيل الدفعة بنجاح وتحديث الرصيد!' : 'تم إلغاء الدفعة وخصم المبلغ من الرصيد!' ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card main-card animate__animated animate__fadeInUp">
            <div class="card-header bg-white fw-bold"><i class="fas fa-plus-circle"></i> إضافة دفعة جديدة</div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">المشترك *</label>
                            <select name="subscriper_id" id="subscriberSelect" class="form-select" required>
                                <option value="">اختر مشتركًا...</option>
                                <?php foreach ($subscribers as $sub): 
                                    $remaining = $MONTHLY_FEE - (float)$sub['balance'];
                                ?>
                                    <option value="<?= $sub['id'] ?>" data-remaining="<?= $remaining ?>">
                                        <?= htmlspecialchars($sub['full_name']) ?> (المتبقي: <?= number_format($remaining) ?> ر.ي)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label text-danger">المبلغ المتبقي حالياً</label>
                            <input type="text" id="remainingDisplay" class="form-control text-danger fw-bold" readonly placeholder="0.00" style="background-color: #fff5f5;">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">المبلغ المدفوع الآن *</label>
                            <input type="number" step="0.01" name="amount" class="form-control fw-bold" placeholder="0.00" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">التاريخ *</label>
                            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">ملاحظات</label>
                            <input type="text" name="notes" class="form-control" placeholder="اختياري...">
                        </div>
                    </div>
                    <div class="text-start border-top pt-3">
                        <button type="submit" class="btn btn-primary px-5 shadow-sm">
                            <i class="fas fa-save"></i> تسجيل الدفعة المالية
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card main-card p-3 animate__animated animate__fadeIn">
            <h5 class="mb-4"><i class="fas fa-history"></i> سجل آخر المدفوعات المستلمة</h5>
            <table id="payTable" class="table table-hover table-striped w-100">
                <thead>
                    <tr>
                        <th>المشترك</th>
                        <th>المبلغ المسدد</th>
                        <th>التاريخ</th>
                        <th>الكاشير</th>
                        <?php if ($is_admin): ?>
                            <th class="text-center">الإجراءات</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['subscriper_name']) ?></strong></td>
                        <td><span class="badge bg-success badge-amount"><?= number_format($p['amount'], 2) ?> ر.ي</span></td>
                        <td><i class="far fa-calendar-alt text-muted"></i> <?= $p['payment_date'] ?></td>
                        <td><i class="fas fa-user-tag text-muted"></i> <?= htmlspecialchars($p['cashier_name']) ?></td>
                        <?php if ($is_admin): ?>
                            <td class="text-center">
                                <a href="payments.php?delete_id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('هل أنت متأكد من إلغاء هذه الدفعة؟ سيتم خصمها من رصيد المشترك.')">
                                    <i class="fas fa-trash-alt"></i> حذف
                                </a>
                            </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // تفعيل نظام الجداول
            $('#payTable').DataTable({
                "language": { "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json" },
                "order": [[ 2, "desc" ]]
            });

            // كود تحديث المبلغ المتبقي تلقائياً عند اختيار المشترك
            $('#subscriberSelect').on('change', function() {
                var remaining = $(this).find(':selected').data('remaining');
                if(remaining !== undefined) {
                    $('#remainingDisplay').val(remaining.toLocaleString() + " ر.ي");
                    // تعبئة خانة المبلغ المدفوع بالمبلغ المتبقي تلقائياً لتسهيل العمل
                    $('input[name="amount"]').val(remaining > 0 ? remaining : 0);
                } else {
                    $('#remainingDisplay').val("0.00");
                    $('input[name="amount"]').val("");
                }
            });
        });
    </script>
</body>
</html>

