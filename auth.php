<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}
function requireAdmin() {
    if ($_SESSION['role'] !== 'admin') {
        die("<h2 style='color:red;text-align:center;'>ليس لديك صلاحية!</h2>");
    }
}
?>

