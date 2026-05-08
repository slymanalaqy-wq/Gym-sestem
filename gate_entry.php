<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit(); }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>بوابة الدخول اليدوي</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        body { background: #1a2a3a; color: white; font-family: 'Tajawal', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; overflow: hidden; }
        .gate-card { background: #2c3e50; border-radius: 20px; padding: 40px; width: 100%; max-width: 500px; text-align: center; box-shadow: 0 15px 35px rgba(0,0,0,0.5); border: 2px solid #34495e; }
        .status-display { height: 150px; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold; border-radius: 15px; margin-bottom: 20px; transition: all 0.5s; background: #34495e; }
        .input-group input { background: #ecf0f1; font-size: 1.5rem; text-align: center; border-radius: 10px 0 0 10px !important; }
        .btn-check-gate { background: #e67e22; color: white; border: none; font-weight: bold; padding: 0 25px; }
        .success-bg { background: #27ae60 !important; color: white; }
        .error-bg { background: #c0392b !important; color: white; }
    </style>
</head>
<body>

<div class="gate-card animate__animated animate__zoomIn">
    <h3 class="mb-4">بوابة الدخول الذكية</h3>
    
    <div id="display" class="status-display">
        <span>في انتظار الرقم...</span>
    </div>

    <div class="input-group mb-3 shadow-sm">
        <input type="text" id="fp_id" class="form-control" placeholder="أدخل رقم المعرف أو البصمة" autofocus>
        <button class="btn btn-check-gate" type="button" onclick="checkAccess()">دخول</button>
    </div>

    <div class="mt-4">
        <a href="dashboard.php" class="text-white-50 text-decoration-none small">العودة للوحة التحكم</a>
    </div>
</div>

<script>
    function checkAccess() {
        const fpId = document.getElementById('fp_id').value;
        const display = document.getElementById('display');

        if(!fpId) return;

        display.innerHTML = '<div class="spinner-border text-light"></div>';
        
        // إرسال الطلب لنفس ملف البيومترك
        // استبدل السطر القديم بهذا
fetch('http://localhost:8081/project_website/backend/api/biometric.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'fingerprint_id=' + encodeURIComponent(fpId)
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                display.className = 'status-display animate__animated animate__pulse success-bg';
                display.innerHTML = '✔ تم الدخول<br><small style="font-size:1.2rem">' + data.name + '</small>';
            } else {
                display.className = 'status-display animate__animated animate__shakeX error-bg';
                display.innerHTML = '✘ مرفوض<br><small style="font-size:1rem">' + data.message + '</small>';
            }
            // تصفير الحقل والتركيز عليه مرة أخرى
            document.getElementById('fp_id').value = '';
            document.getElementById('fp_id').focus();
        })
        .catch(error => {
            display.innerHTML = 'خطأ في الاتصال';
        });
    }

    // السماح بالضغط على Enter للارسال
    document.getElementById('fp_id').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') checkAccess();
    });
</script>

</body>
</html>