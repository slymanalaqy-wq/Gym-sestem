<?php
// إعدادات الاستجابة
header("Content-Type: application/json; charset=utf8mb4");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// السماح فقط بطلب POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST method is allowed.']);
    exit();
}

// قراءة معرف البصمة من الطلب
$fingerprint_id = trim($_POST['fingerprint_id'] ?? '');

if (empty($fingerprint_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Fingerprint ID is required.']);
    exit();
}

try {
    // الاتصال بقاعدة البيانات
    $pdo = new PDO("mysql:host=localhost;dbname=gym_db;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. استعلام التحقق من المشترك
    $stmt = $pdo->prepare("
        SELECT id, full_name, subscription_end, fingerprint_enabled 
        FROM subscribers 
        WHERE fingerprint_id = :fp_id
    ");
    $stmt->bindValue(':fp_id', $fingerprint_id, PDO::PARAM_STR);
    $stmt->execute();
    $subscriber = $stmt->fetch(PDO::FETCH_ASSOC);

    $success = false;
    $message = "";

    // 2. التحقق من الشروط (التفعيل، الصلاحية)
    if (!$subscriber) {
        $message = "البصمة غير مسجلة بالنظام.";
        $action_log = "محاولة دخول مجهولة";
        $desc_log = "تم استخدام بصمة غير مسجلة رقم: " . $fingerprint_id;
    } elseif ($subscriber['fingerprint_enabled'] == 0) {
        $message = "تم إيقاف هذه البصمة من قبل الإدارة.";
        $action_log = "دخول مرفوض";
        $desc_log = "حاول المشترك " . $subscriber['full_name'] . " الدخول ولكن بصمته معطلة.";
    } elseif (strtotime($subscriber['subscription_end']) < time()) {
        $message = "اشتراكك منتهٍ، يرجى التجديد.";
        $action_log = "دخول مرفوض";
        $desc_log = "حاول المشترك " . $subscriber['full_name'] . " الدخول واشتراكه منتهٍ.";
    } else {
        $success = true;
        $message = "أهلاً بك يا " . $subscriber['full_name'];
        $action_log = "تسجيل دخول";
        $desc_log = "دخل المشترك " . $subscriber['full_name'] . " النادي عبر البوابة.";
    }

    // 3. التسجيل في جدول activity_log (الجدول الذي أنشأناه سابقاً)
    // نضع user_id كـ NULL لأن العملية تمت آلياً من البوابة وليس من مدير
    $log = $pdo->prepare("INSERT INTO activity_log (user_id, action_type, description) VALUES (NULL, :action, :desc)");
    $log->bindValue(':action', $action_log);
    $log->bindValue(':desc', $desc_log);
    $log->execute();

    // 4. إرجاع النتيجة لكود بايثون
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'name' => $subscriber ? $subscriber['full_name'] : 'Unknown',
        'action' => $success ? 'OPEN_GATE' : 'DENY_GATE'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'System error']);
}
?>