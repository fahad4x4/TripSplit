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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#1f4e79">
    <title>تسجيل الدخول · TripSplit</title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="login">
    <main class="login-card">
        <img class="logo" src="assets/favicon.svg" alt="" width="56" height="56">
        <h1>TripSplit</h1>
        <p class="sub">مصروفات الرحلة، مقسّمة بالعدل</p>
        <?php if (!$configured): ?>
            <div class="alert warn" role="alert">
                <div>
                    <strong>التطبيق غير مهيأ بعد.</strong><br>
                    أنشئ ملف <code>config.local.php</code> يحتوي <code>password_hash</code>
                    (انسخ <code>config.local.example.php</code> ثم ولّد الـ hash بالأمر
                    <code dir="ltr">php tools/hash-password.php</code>).
                </div>
            </div>
        <?php else: ?>
            <form method="post" autocomplete="off">
                <?= csrfField() ?>
                <div class="field">
                    <label for="password">كلمة المرور</label>
                    <input class="input" type="password" id="password" name="password" required autofocus
                           autocomplete="current-password"<?= $error !== null ? ' aria-invalid="true" aria-describedby="login-error"' : '' ?>>
                </div>
                <?php if ($error !== null): ?>
                    <div class="form-msg err" id="login-error" role="alert"><?= h($error) ?></div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary btn-block" style="margin-top:16px">دخول</button>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>
