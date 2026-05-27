<?php
// إعدادات الاتصال بقاعدة البيانات
$host = "localhost";
$dbname = "trading_platform"; // اسم قاعدة البيانات
$username = "root"; // اسم المستخدم لقاعدة البيانات
$password = ""; // كلمة المرور لقاعدة البيانات

// الاتصال بقاعدة البيانات باستخدام PDO (الطريقة الأكثر أماناً)
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// التأكد من أن البيانات قادمة عبر زر الإرسال (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. استقبال البيانات وتنقيتها (Sanitization) للحماية من الاختراق
    $full_name = htmlspecialchars(strip_tags($_POST['full_name']));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $raw_password = $_POST['password'];
    $course_name = htmlspecialchars($_POST['course_name']);
    $payment_method = htmlspecialchars($_POST['payment_method']);

    // 2. تشفير كلمة المرور (مهم جداً ألا تُحفظ كلمة المرور كما هي)
    $password_hash = password_hash($raw_password, PASSWORD_BCRYPT);

    // 3. التحقق من أن الإيميل غير مسجل مسبقاً
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        die("عذراً، هذا البريد الإلكتروني مسجل مسبقاً!");
    }

    // 4. حفظ البيانات في قاعدة البيانات
    try {
        $insert_query = "INSERT INTO users (full_name, email, password_hash, course_name, payment_method) 
                         VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($insert_query);
        $stmt->execute([$full_name, $email, $password_hash, $course_name, $payment_method]);
        
        $user_id = $pdo->lastInsertId(); // الحصول على رقم العميل الجديد

        // 5. التوجيه إلى بوابة الدفع بناءً على اختيار العميل
        // (هنا يتم دمج API الخاص ببوابة مثل Moyasar أو PayTabs)
        
        if ($payment_method == 'mada' || $payment_method == 'credit_card') {
            // كود التوجيه لبوابة مدى / فيزا
            echo "تم حفظ حسابك بنجاح! جاري تحويلك لبوابة الدفع الآمنة (مدى/فيزا)...";
            // header("Location: https://api.moyasar.com/v1/payments/..."); 
            // exit();
        } elseif ($payment_method == 'apple_pay') {
            echo "تم حفظ حسابك بنجاح! جاري فتح Apple Pay...";
            // كود آبل باي
        }

    } catch (PDOException $e) {
        die("حدث خطأ أثناء إنشاء الحساب: " . $e->getMessage());
    }
} else {
    echo "طلب غير صالح.";
}
?>