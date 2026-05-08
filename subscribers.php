<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}
require '../includes/db.php';
$action = $_GET['action'] ?? 'list';
$subscriber = null;
$error = "";

// === الحذف ===
if ($action === 'delete' && isset($_GET['id'])) {
    if ($_SESSION['role'] !== 'admin') { die("خطأ: لا تملك صلاحية الحذف."); }
    $id = (int)$_GET['id'];
    $log_stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action_type, description) VALUES (?, 'حذف مشترك', ?)");
    $log_stmt->execute([$_SESSION['user_id'], "قام بحذف المشترك رقم: " . $id]);
    $pdo->prepare("DELETE FROM payments WHERE subscriper_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM subscribers WHERE id = ?")->execute([$id]);
    header("Location: subscribers.php?message=deleted");
    exit();
}

// === تحميل المشترك للتعديل ===
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM subscribers WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $subscriber = $stmt->fetch();
}

// === معالجة الحفظ ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $full_name = trim($_POST['full_name']);
    $mobile = trim($_POST['mobile']);
    $fingerprint_id = trim($_POST['fingerprint_id']);
    $start = $_POST['subscription_start'];
    $end = $_POST['subscription_end'];
    $balance = !empty($_POST['balance']) ? (float)$_POST['balance'] : 0;
    $notes = trim($_POST['notes'] ?? '');
    if (empty($full_name) || empty($mobile) || empty($start) || empty($end)) {
        $error = "جميع الحقول الأساسية مطلوبة.";
    } else {
        try {
            if ($id) {
                if ($_SESSION['role'] !== 'admin') { die("خطأ: لا تملك صلاحية التعديل."); }
                $stmt = $pdo->prepare("UPDATE subscribers SET full_name=?, mobile=?, fingerprint_id=?, subscription_start=?, subscription_end=?, balance=?, notes=? WHERE id=?");
                $stmt->execute([$full_name, $mobile, $fingerprint_id, $start, $end, $balance, $notes, $id]);
                $log_stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action_type, description) VALUES (?, 'تعديل بيانات', ?)");
                $log_stmt->execute([$_SESSION['user_id'], "قام بتعديل بيانات المشترك: " . $full_name . " (رقم: " . $id . ")"]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO subscribers (full_name, mobile, fingerprint_id, subscription_start, subscription_end, balance, notes, fingerprint_enabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$full_name, $mobile, $fingerprint_id, $start, $end, $balance, $notes, 1]);
                $last_sub_id = $pdo->lastInsertId();
                $log_stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action_type, description) VALUES (?, 'إضافة مشترك', ?)");
                $log_stmt->execute([$_SESSION['user_id'], "أضاف مشترك جديد: " . $full_name . " برقم: " . $last_sub_id]);
            }
            header("Location: subscribers.php?message=" . ($id ? 'updated' : 'added'));
            exit();
        } catch (Exception $e) { $error = "خطأ: " . $e->getMessage(); }
    }
}

// === جلب البيانات مع حساب المدفوعات والمتبقي ===
$subscribers = [];
if ($action === 'list') {
    $stmt = $pdo->query("
        SELECT 
            s.*,
            COALESCE(SUM(p.amount), 0) AS total_paid,
            (8000 - COALESCE(SUM(p.amount), 0)) AS remaining_amount
        FROM subscribers s
        LEFT JOIN payments p ON s.id = p.subscriper_id
        GROUP BY s.id
        ORDER BY s.id DESC
    ");
    $subscribers = $stmt->fetchAll();
}

$total_balance = 0;
foreach($subscribers as $sub) { $total_balance += (float)$sub['balance']; }
$total_monthly = count($subscribers) * 8000;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>إدارة المشتركين</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
<link rel="stylesheet" href="css/jquery.dataTables.min.css">
<link rel="stylesheet" href="css/dataTables.bootstrap.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap');
body { background-color: #f4f7f6; font-family: 'Tajawal', sans-serif; }
.main-card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); background: #fff; margin-bottom: 30px; }
.stats-card { background: linear-gradient(45deg, #2c3e50, #4ca1af); color: white; border-radius: 10px; padding: 20px; text-align: center; margin-bottom: 20px; }
.table thead { background-color: #2c3e50; color: white; }
.btn { border-radius: 8px; transition: 0.3s; }
.btn:hover { transform: translateY(-2px); }
</style>
</head>
<body>
<div class="container py-4">
<?php if (isset($_GET['message'])): ?>
<div class="alert alert-success alert-dismissible fade show animate__animated animate__bounceIn">
تمت العملية بنجاح!
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-danger animate__animated animate__shakeX"><?= $error ?></div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
<div class="card main-card animate__animated animate__fadeInUp">
<div class="card-header bg-primary text-white text-center h5">
<?= $action === 'edit' ? 'تعديل بيانات المشترك' : 'إضافة مشترك جديد' ?>
</div>
<div class="card-body">
<form method="POST">
<?php if ($action === 'edit'): ?><input type="hidden" name="id" value="<?= $subscriber['id'] ?>"><?php endif; ?>
<div class="row">
<div class="col-md-6 mb-3">
<label class="form-label">الاسم الكامل</label>
<input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($subscriber['full_name'] ?? '') ?>" required>
</div>
<div class="col-md-6 mb-3">
<label class="form-label">رقم الجوال</label>
<input type="text" name="mobile" class="form-control" value="<?= htmlspecialchars($subscriber['mobile'] ?? '') ?>" required>
</div>
<div class="col-md-6 mb-3">
<label class="form-label">معرف البصمة</label>
<input type="text" name="fingerprint_id" class="form-control" value="<?= htmlspecialchars($subscriber['fingerprint_id'] ?? '') ?>" required>
</div>
<div class="col-md-3 mb-3">
<label class="form-label">بداية الاشتراك</label>
<input type="date" name="subscription_start" class="form-control" value="<?= $subscriber['subscription_start'] ?? date('Y-m-d') ?>">
</div>
<div class="col-md-3 mb-3">
<label class="form-label">نهاية الاشتراك</label>
<input type="date" name="subscription_end" class="form-control" value="<?= $subscriber['subscription_end'] ?? date('Y-m-d', strtotime('+1 month')) ?>">
</div>
<div class="col-md-6 mb-3">
<label class="form-label">الرصيد</label>
<input type="number" step="0.01" name="balance" class="form-control" value="<?= $subscriber['balance'] ?? '0.00' ?>">
</div>
<div class="col-md-12 mb-3">
<label class="form-label">ملاحظات</label>
<textarea name="notes" class="form-control"><?= htmlspecialchars($subscriber['notes'] ?? '') ?></textarea>
</div>
</div>
<div class="text-center">
<button type="submit" class="btn btn-success px-5">حفظ البيانات</button>
<a href="subscribers.php" class="btn btn-secondary px-5">إلغاء</a>
</div>
</form>
</div>
</div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
<div class="row mb-4 align-items-center">
<div class="col-md-6">
<h2 class="animate__animated animate__fadeInRight">نظام إدارة المشتركين</h2>
</div>
<div class="col-md-6 text-start">
<a href="subscribers.php?action=add" class="btn btn-primary shadow">+ إضافة مشترك جديد</a>
<a href="dashboard.php" class="btn btn-outline-dark shadow">الرئيسية</a>
</div>
</div>

<div class="row">
<div class="col-md-6">
<div class="stats-card">
<h5>إجمالي الاشتراك الشهري المتوقع</h5>
<h3><?= number_format($total_monthly) ?> <small>ر.ي</small></h3>
</div>
</div>
<div class="col-md-6">
<div class="stats-card" style="background: linear-gradient(45deg, #e67e22, #f39c12);">
<h5>إجمالي الرصيد المتبقي</h5>
<h3><?= number_format($total_balance, 2) ?> <small>ر.ي</small></h3>
</div>
</div>
</div>

<div class="card main-card p-3 animate__animated animate__fadeIn">
<table id="subTable" class="table table-hover table-striped w-100">
<thead>
<tr>
<th>الرقم</th>
<th>الاسم</th>
<th>الجوال</th>
<th>البصمة</th>
<th>الفترة</th>
<th>الرصيد</th>
<th>المدفوع</th>
<th>المتبقي</th>
<th>الإجراءات</th>
</tr>
</thead>
<tbody>
<?php foreach ($subscribers as $sub): ?>
<tr>
<td><?= $sub['id'] ?></td>
<td><strong><?= htmlspecialchars($sub['full_name']) ?></strong></td>
<td><?= htmlspecialchars($sub['mobile']) ?></td>
<td><span class="badge bg-secondary"><?= htmlspecialchars($sub['fingerprint_id'] ?? '—') ?></span></td>
<td><small><?= $sub['subscription_start'] ?> → <?= $sub['subscription_end'] ?></small></td>
<td class="text-danger fw-bold"><?= number_format($sub['balance'], 2) ?></td>
<td class="text-success fw-bold"><?= number_format($sub['total_paid'], 2) ?> ر.ي</td>
<td>
<?php 
$remaining = max(0, $sub['remaining_amount']);
if ($remaining <= 0): ?>
<span class="badge bg-success">مسدّد بالكامل</span>
<?php else: ?>
<span class="badge bg-warning text-dark"><?= number_format($remaining, 2) ?> ر.ي</span>
<?php endif; ?>
</td>
<td>
<?php if ($_SESSION['role'] === 'admin'): ?>
<a href="subscribers.php?action=edit&id=<?= $sub['id'] ?>" class="btn btn-sm btn-info text-white">تعديل</a>
<a href="subscribers.php?action=delete&id=<?= $sub['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('حذف؟')">حذف</a>
<?php else: ?>
<span class="text-muted small">عرض فقط</span>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
if ($('#subTable').length > 0) {
$('#subTable').DataTable({
"language": {
"url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json"
},
"responsive": true
});
}
});
</script>
</body>
</html>








