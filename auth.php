<?php
/**
 * TripSplit — الأمان المشترك: الجلسة، تسجيل الدخول، CSRF، والهروب من HTML.
 *
 * يُضمَّن في بداية كل صفحة. يفرض تسجيل الدخول تلقائياً
 * إلا إذا عُرّف الثابت TRIPSPLIT_PUBLIC قبل التضمين (صفحة login.php فقط).
 */

// لا نعرض الأخطاء للمستخدم؛ نسجلها في سجل الخادم.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/storage.php';

/** هروب آمن لأي قيمة تُطبع في HTML. */
function h($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** JSON آمن داخل وسم <script>. */
function js($value): string {
    return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/** الإعدادات: config.php العام + config.local.php الخاص (غير مرفوع إلى Git). */
function appConfig(): array {
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
        $local = __DIR__ . '/config.local.php';
        if (is_file($local)) {
            $extra = require $local;
            if (is_array($extra)) {
                $config = array_merge($config, $extra);
            }
        }
    }
    return $config;
}

function isHttps(): bool {
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') return true;
    if (($_SERVER['SERVER_PORT'] ?? null) == 443) return true;
    if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') return true;
    return false;
}

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('TRIPSPLITSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function isAjaxOrPost(): bool {
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET'
        || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
}

function requireLogin(): void {
    if (isLoggedIn()) return;
    if (isAjaxOrPost()) {
        http_response_code(401);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'انتهت الجلسة، يرجى تسجيل الدخول من جديد.';
        exit();
    }
    header('Location: login.php');
    exit();
}

/* ---------------------------- CSRF ---------------------------- */

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">';
}

function verifyCsrf(): bool {
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($sent) && $sent !== '' && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $sent);
}

/** يرفض الطلب إن لم يكن POST بتوكن CSRF صحيح. */
function requirePostWithCsrf(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'الطريقة غير مسموحة.';
        exit();
    }
    if (!verifyCsrf()) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'رمز الحماية (CSRF) غير صالح، حدّث الصفحة وحاول مجدداً.';
        exit();
    }
}

// ترويسات أمان أساسية
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
}

startSecureSession();
if (!defined('TRIPSPLIT_PUBLIC')) {
    requireLogin();
}

/**
 * يحمّل vendor/autoload.php (TCPDF عبر Composer)، أو يعرض رسالة عربية واضحة إن لم تُثبّت المكتبات.
 */
function requirePdfLibrary(): void {
    $autoload = __DIR__ . '/vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
        if (class_exists('TCPDF')) return;
    }
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>تصدير PDF غير متاح</title>'
       . '<style>body{font-family:Tajawal,Arial,sans-serif;background:#f5f7fa;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}'
       . '.box{background:#fff;padding:2rem;border-radius:15px;box-shadow:0 4px 15px rgba(0,0,0,.1);max-width:460px;line-height:1.9}'
       . 'code{background:#f0f0f0;padding:2px 6px;border-radius:4px}</style></head><body><div class="box">'
       . '<h3>تصدير PDF غير متاح حالياً</h3>'
       . '<p>مكتبة TCPDF غير مثبتة على الخادم. نفّذ الأمر التالي في مجلد المشروع:</p>'
       . '<p dir="ltr"><code>composer install</code></p>'
       . '<p><a href="index.php">العودة للصفحة الرئيسية</a></p>'
       . '</div></body></html>';
    exit();
}
