<?php
define('TRIPSPLIT_PUBLIC', true);
require __DIR__ . '/auth.php';

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCK_SECONDS = 300;

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$config = appConfig();
$hash = $config['password_hash'] ?? '';
$configured = is_string($hash) && $hash !== '' && strpos($hash, 'REPLACE_WITH') === false;
$error = null;

$lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);
if ($lockedUntil > time()) {
    $mins = (int) ceil(($lockedUntil - time()) / 60);
    $error = 'محاولات كثيرة غير صحيحة. حاول مجدداً بعد ' . $mins . ' دقيقة.';
} elseif ($configured && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    if (!verifyCsrf()) {
        $error = 'انتهت صلاحية النموذج، حدّث الصفحة وحاول مجدداً.';
    } elseif ($password !== '' && password_verify($password, $hash)) {
        session_regenerate_id(true);
        unset($_SESSION['login_failures'], $_SESSION['login_locked_until'], $_SESSION['csrf_token']);
        $_SESSION['logged_in'] = true;
        header('Location: index.php');
        exit();
    } else {
        $failures = (int) ($_SESSION['login_failures'] ?? 0) + 1;
        $_SESSION['login_failures'] = $failures;
        sleep(min($failures, 3)); // تأخير بسيط يبطئ التخمين
        if ($failures >= LOGIN_MAX_ATTEMPTS) {
            $_SESSION['login_locked_until'] = time() + LOGIN_LOCK_SECONDS;
            $_SESSION['login_failures'] = 0;
            $error = 'محاولات كثيرة غير صحيحة. تم إيقاف الدخول مؤقتاً لمدة 5 دقائق.';
        } else {
            $error = 'كلمة المرور غير صحيحة.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            background: linear-gradient(to bottom right, #00c6ff, #0072ff);
            font-family: 'Tajawal', Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            width: 300px;
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
        input[type="password"], input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
        }
        input[type="submit"] {
            background-color: #0072ff;
            color: white;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #005bb5;
        }
        .setup {
            background: #fff8e1;
            border: 1px solid #f2a950;
            border-radius: 8px;
            padding: 12px;
            font-size: 14px;
            line-height: 1.8;
            color: #5d4300;
            text-align: right;
        }
        .setup code { background: #f5f5f5; padding: 1px 4px; border-radius: 4px; }
        .error {
            color: red;
            margin-bottom: 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>تسجيل الدخول</h2>
        <?php if (!$configured): ?>
            <div class="setup">
                <strong>التطبيق غير مهيأ بعد.</strong><br>
                أنشئ ملف <code>config.local.php</code> يحتوي <code>password_hash</code>
                (انسخ <code>config.local.example.php</code> ثم ولّد الـ hash بالأمر
                <code dir="ltr">php tools/hash-password.php</code>).
            </div>
        <?php else: ?>
            <?php if ($error !== null): ?><div class="error"><?= h($error) ?></div><?php endif; ?>
            <form method="post" autocomplete="off">
                <?= csrfField() ?>
                <input type="password" name="password" placeholder="كلمة المرور" required autofocus>
                <input type="submit" value="دخول">
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
