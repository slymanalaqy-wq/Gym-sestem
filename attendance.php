<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit(); }
require '../includes/db.php';

// جلب آخر عمليات الدخول الناجحة التي تمت اليوم
$stmt = $pdo->query("SELECT * FROM activity_log WHERE action_type = 'تسجيل دخول' AND DATE(created_at) = CURDATE() ORDER BY created_at DESC");
$logs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الحضور اليومي</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
</head>
<body style="background:#f4f7f6; padding:20px;">
    <div class="container">
        <h2 class="mb-4">المتواجدون في النادي (اليوم)</h2>
        <table class="table table-white table-striped shadow-sm rounded">
            <thead class="table-dark">
                <tr>
                    <th>الوقت</th>
                    <th>التفاصيل</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($logs as $log): ?>
                <tr>
                    <td><?= date('H:i:s', strtotime($log['created_at'])) ?></td>
                    <td><?= htmlspecialchars($log['description']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="dashboard.php" class="btn btn-secondary">رجوع</a>
    </div>
</body>
</html>