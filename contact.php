<?php
// تعيين نوع المحتوى لدعم اللغة العربية
header("Content-Type: application/json; charset=utf-8");

// السماح بطلب من الواجهة الأمامية (CORS)
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// بدء الجلسة (اختياري — إذا أردت حفظ الرسالة في حالة تسجيل الدخول)
session_start();

// التحقق من أن الطلب هو POST
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST method is allowed.']);
    exit();
}

    // Method Not Allowed



// التحقق من وجود البيانات
if (
    !isset($_POST['name']) ||
    !isset($_POST['email']) ||
    !isset($_POST['message'])
) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'All fields (name, email, message) are required.']);
    exit();
}

// تنظيف وفلترة المدخلات
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$message = trim($_POST['message']);

// التحقق من أن الحقول غير فارغة
if (empty($name) || empty($email) || empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields must be non-empty.']);
    exit();
}

// التحقق من صحة البريد الإلكتروني
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format.']);
    exit();
}

// ======= الخيار 1: حفظ الرسالة في قاعدة البيانات (موصى به) =======
try {
    // الاتصال بقاعدة البيانات
    $pdo = new PDO("mysql:host=localhost;dbname=gym_db;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // إدراج الرسالة في الجدول
    $stmt = $pdo->prepare("INSERT INTO messages (name, email, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$name, $email, $message]);

    // إرجاع نجاح
    echo json_encode(['success' => true, 'message' => 'Your message has been sent successfully!']);

} catch (PDOException $e) {
    // تسجيل الخطأ (في الإنتاج، لا تُظهر تفاصيل قاعدة البيانات)
    error_log("Contact form error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save your message. Please try again later.']);
}

// ======= الخيار 2: إرسال بريد إلكتروني (بديل) =======
/*
require_once '../config/email.php'; // إذا كان لديك إعدادات بريد

if (mailContactForm($name, $email, $message)) {
    echo json_encode(['success' => true, 'message' => 'Your message has been sent!']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send email.']);
}
*/
?>


