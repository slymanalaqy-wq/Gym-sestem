<?php
// includes/db.php
$host = 'localhost';
$db   = 'gym_db'; // نفس اسم قاعدة البيانات التي أنشأتها
$user = 'root';
$pass = ''; // فارغ في XAMPP
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
$pdo = new PDO($dsn, $user, $pass, $options);



 try{
    $pdo= new PDO($dsn , $user , $pass,$options );
     echo "connect";
 }
 catch(PDOException $error){
error_log("database connection failed: " . $error->getMessage());
die("خدث خطا في الاتصال بقاعدة البيانات");

//     echo $error->getMessage();
 }

?>
















