<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}
$current_user_id = $_SESSION['user_id'];

require '../includes/db.php';

$action = $_GET['action'] ?? 'list';
$user = null;

// === الحذف (لا يمكن حذف الحساب الحالي) ===
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($id != $current_user_id) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        header("Location: staff.php?message=deleted");
        exit();
    }
}

// === جلب بيانات للتعديل ===
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $user = $stmt->fetch();
    if (!$user || $user['id'] == $current_user_id) {
        header("Location: staff.php");
        exit();
    }
}

// === حفظ (إضافة أو تعديل) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $role = $_POST['role'];
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($full_name) || empty($role)) {
        $error = "يرجى تعبئة جميع الحقول.";
    } else {
        try {
            if ($id) {
                if (!empty($password)) {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET username=?, full_name=?, role=?, password=? WHERE id=?");
                    $stmt->execute([$username, $full_name, $role, $hashed, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username=?, full_name=?, role=? WHERE id=?");
                    $stmt->execute([$username, $full_name, $role, $id]);
                }
            } else {
                if (empty($password)) {
                    $error = "كلمة المرور مطلوبة عند الإنشاء.";
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $hashed, $full_name, $role]);
                }
            }
            if (empty($error)) {
                header("Location: staff.php?message=" . ($id ? 'updated' : 'added'));
                exit();
            }
        } catch (Exception $e) {
            $error = "اسم المستخدم موجود مسبقًا.";
        }
    }
}

if ($action === 'list') {
    $stmt = $pdo->query("SELECT id, username, full_name, role FROM users ORDER BY role, id");
    $users = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $action === 'list' ? 'إدارة الموظفين' : ($action === 'edit' ? 'تعديل موظف' : 'إضافة موظف') ?></title>
    
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
            background: white; margin-bottom: 30px; 
        }

        .form-label { font-weight: bold; color: #34495e; }
        .table thead { background-color: #2c3e50; color: white; }
        .btn { border-radius: 8px; transition: 0.3s; }
        .nav-link-custom { text-decoration: none; color: white; float: right; font-size: 0.9em; }
        
        .role-badge { padding: 6px 12px; border-radius: 20px; font-size: 0.85em; }
    </style>
</head>
<body>

    <div class="header animate__animated animate__fadeInDown">
        <a href="index.php" class="nav-link-custom"><i class="fas fa-arrow-right"></i> العودة للرئيسية</a>
        <h2><i class="fas fa-user-shield"></i> إدارة طاقم العمل</h2>
    </div>

    <div class="container py-4">
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger animate__animated animate__shakeX shadow-sm"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['message'])): ?>
            <?php
            $msgs = ['added' => 'تم إنشاء حساب الموظف بنجاح!', 'updated' => 'تم تحديث البيانات بنجاح!', 'deleted' => 'تم حذف الموظف من النظام!'];
            if (isset($msgs[$_GET['message']])): ?>
                <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeInUp shadow-sm">
                    <i class="fas fa-check-circle"></i> <?= $msgs[$_GET['message']] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="card main-card animate__animated animate__zoomIn">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="fas <?= $action === 'edit' ? 'fa-user-edit' : 'fa-user-plus' ?>"></i>
                    <?= $action === 'edit' ? 'تعديل بيانات الموظف' : 'إضافة موظف جديد للنظام' ?>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="id" value="<?= $user['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">اسم المستخدم (Username) *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">كلمة المرور *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="password" class="form-control" placeholder="<?= $action === 'edit' ? 'اترك فارغاً للإبقاء على الحالية' : 'أدخل كلمة المرور' ?>" <?= $action === 'add' ? 'required' : '' ?>>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الاسم الكامل *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">الصلاحية والمسؤولية *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                    <select name="role" class="form-select" required>
                                        <option value="">اختر...</option>
                                        <option value="cashier" <?= ($user['role'] ?? '') === 'cashier' ? 'selected' : '' ?>>كاشير (صلاحيات محدودة)</option>
                                        <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>مشرف (صلاحيات كاملة)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <button type="submit" class="btn btn-primary px-5 shadow-sm">
                                <i class="fas fa-save"></i> <?= $action === 'edit' ? 'تحديث البيانات' : 'حفظ الموظف' ?>
                            </button>
                            <a href="staff.php" class="btn btn-light px-4 border">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <div class="row mb-3 align-items-center">
                <div class="col-6 text-end h4 mb-0">قائمة الموظفين الحاليين</div>
                <div class="col-6 text-start">
                    <a href="staff.php?action=add" class="btn btn-success shadow-sm">
                        <i class="fas fa-plus"></i> إضافة موظف جديد
                    </a>
                </div>
            </div>

            <div class="card main-card p-3 animate__animated animate__fadeIn">
                <table id="staffTable" class="table table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th>الرقم</th>
                            <th>اسم المستخدم</th>
                            <th>الاسم الكامل</th>
                            <th>الصلاحية</th>
                            <th class="text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($users ?? []) as $u): ?>
                            <?php if ($u['id'] == $current_user_id) continue; ?>
                            <tr>
                                <td><span class="text-muted">#<?= $u['id'] ?></span></td>
                                <td class="fw-bold"><?= htmlspecialchars($u['username']) ?></td>
                                <td><?= htmlspecialchars($u['full_name']) ?></td>
                                <td>
                                    <?php if($u['role'] === 'admin'): ?>
                                        <span class="role-badge bg-danger text-white"><i class="fas fa-user-shield"></i> مشرف</span>
                                    <?php else: ?>
                                        <span class="role-badge bg-info text-white"><i class="fas fa-cash-register"></i> كاشير</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="staff.php?action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary" title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="staff.php?action=delete&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('حذف هذا الموظف نهائياً؟')" title="حذف">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
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
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            if ($('#staffTable').length) {
                $('#staffTable').DataTable({
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