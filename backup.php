<?php
session_start();
// التحقق من أن المشرف فقط هو من يقوم بالنسخ الاحتياطي
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("صلاحية غير كافية.");
}

require '../includes/db.php';

try {
    // إعدادات قاعدة البيانات
    $host = "localhost";
    $user = "root";
    $pass = "";
    $name = "gym_db";

    // جلب جميع الجداول
    $tables = array();
    $result = $pdo->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $return = "";
    foreach ($tables as $table) {
        $result = $pdo->query("SELECT * FROM $table");
        $num_fields = $result->columnCount();

        $return .= "DROP TABLE IF EXISTS $table;";
        $row2 = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_NUM);
        $return .= "\n\n" . $row2[1] . ";\n\n";

        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $return .= "INSERT INTO $table VALUES(";
            for ($j = 0; $j < $num_fields; $j++) {
                $row[$j] = addslashes($row[$j]);
                if (isset($row[$j])) { $return .= '"' . $row[$j] . '"'; } else { $return .= '""'; }
                if ($j < ($num_fields - 1)) { $return .= ','; }
            }
            $return .= ");\n";
        }
        $return .= "\n\n\n";
    }

    // اسم الملف مع التاريخ والوقت
    $filename = 'backup-' . $name . '-' . date('Y-m-d-H-i-s') . '.sql';

    // إرسال الملف للمتصفح للتحميل
    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: Binary");
    header("Content-disposition: attachment; filename=\"" . $filename . "\"");
    echo $return;
    exit;

} catch (Exception $e) {
    echo "حدث خطأ أثناء النسخ الاحتياطي: " . $e->getMessage();
}
?>