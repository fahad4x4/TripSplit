<?php
/**
 * TripSplit — مكونات الواجهة المشتركة: رأس الصفحة، التنقل، التذييل، تنسيق المبالغ، والأيقونات.
 *
 * ملف دوال فقط (لا يطبع شيئاً عند تضمينه). يُضمَّن بعد auth.php.
 * الواجهة بلا مكتبات خارجية: CSS في assets/app.css وخط Tajawal مستضاف محلياً في assets/fonts/.
 */

const APP_VERSION = '2.1.0';

/** عناصر التنقل الرئيسية: [ملف => [عنوان قصير, أيقونة]] */
function navItems(): array {
    return [
        'index.php'          => ['الرئيسية', 'home'],
        'expenses.php'       => ['المصروفات', 'receipt'],
        'daily_expenses.php' => ['اليومي', 'calendar'],
        'contributions.php'  => ['القطة', 'wallet'],
        'Chart.php'          => ['الإحصائيات', 'chart'],
        'manage.php'         => ['الإدارة', 'settings'],
    ];
}

/** أيقونات SVG مضمّنة (بدون مكتبة أيقونات). */
function icon(string $name, string $class = 'icon'): string {
    static $paths = [
        'home'     => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"/>',
        'receipt'  => '<path d="M6 2h12v20l-3-2-3 2-3-2-3 2z"/><path d="M9 7h6M9 11h6M9 15h4"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'wallet'   => '<path d="M20 7H5a2 2 0 0 1 0-4h13v4"/><path d="M3 5v14a2 2 0 0 0 2 2h15V7"/><circle cx="16" cy="14" r="1.5"/>',
        'chart'    => '<path d="M3 3v18h18"/><path d="M8 17v-5M13 17V8M18 17v-9"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
        'moon'     => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
        'sun'      => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
        'arrow'    => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'pdf'      => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M12 18v-6M9 15l3 3 3-3"/>',
        'users'    => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
        'user'     => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'check'    => '<path d="M20 6 9 17l-5-5"/>',
        'alert'    => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
        'trash'    => '<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>',
        'edit'     => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'lock'     => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'printer'  => '<path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
        'piggy'    => '<path d="M19 9a7 7 0 0 0-7-4c-4 0-7 2.5-7 6 0 2 1 3.6 2.5 4.6V19h3v-2h3v2h3v-3.4c1-.6 1.8-1.5 2.2-2.6H21v-4z"/><circle cx="15.5" cy="10" r=".8"/>',
    ];
    $p = $paths[$name] ?? '';
    return '<svg class="' . h($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $p . '</svg>';
}

/** تنسيق مبلغ: الرقم معزول باتجاه LTR حتى تظهر الإشارة السالبة والفواصل بشكل صحيح داخل نص عربي. */
function fmtNum(float $amount, int $decimals = 2, string $suffix = ''): string {
    return '<bdi class="num" dir="ltr">' . number_format($amount, $decimals) . h($suffix) . '</bdi>';
}

/** مبلغ بعملة الرحلة. */
function money(float $amount, bool $withCurrency = true): string {
    $out = fmtNum($amount);
    if ($withCurrency) {
        $out .= ' <span class="cur">' . h(appConfig()['currency'] ?? '') . '</span>';
    }
    return '<span class="money">' . $out . '</span>';
}

/** المعادل التقريبي بالريال السعودي (نص فقط، بدون صورة خارجية). */
function sar(float $amount): string {
    $rate = (float) (appConfig()['exchange_rate_to_sar'] ?? 0);
    if ($rate <= 0) return '';
    return '<span class="sar" title="تقريباً بالريال السعودي">≈ ' . fmtNum($amount * $rate) . ' ر.س</span>';
}

/** لون شارة الفئة (ألوان بتباين AA مع النص). */
function categoryTone(string $note): string {
    static $map = [
        'سكن' => 'green', 'طعام' => 'amber', 'مواصلات' => 'red', 'تذاكر طيران' => 'blue',
        'طيران' => 'blue', 'تأمين سفر' => 'cyan', 'تأجير سيارات' => 'purple', 'تذاكر دخول' => 'pink',
        'وقود' => 'orange', 'هايبر ماركت' => 'teal', 'بقالة' => 'teal', 'مطاعم' => 'amber',
    ];
    return $map[$note] ?? 'gray';
}

function categoryChip(string $note): string {
    return '<span class="chip chip-' . categoryTone($note) . '">' . h($note !== '' ? $note : 'اخرى') . '</span>';
}

/** رأس HTML + شريط التطبيق + التنقل. $active = اسم الملف الحالي. */
function pageStart(string $title, string $active = '', array $opts = []): void {
    $navItems = navItems();
    $bodyClass = $opts['bodyClass'] ?? '';
    ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#1f4e79">
    <title><?= h($title) ?> · TripSplit</title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="preload" href="assets/fonts/tajawal-arabic-400.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="assets/app.css?v=<?= h(APP_VERSION) ?>">
    <script>
        // تطبيق المظهر قبل الرسم لتفادي الوميض
        (function () {
            try {
                var t = localStorage.getItem('theme');
                if (!t && localStorage.getItem('darkMode') === 'enabled') t = 'dark';
                if (t === 'dark' || t === 'light') document.documentElement.setAttribute('data-theme', t);
            } catch (e) {}
        })();
    </script>
</head>
<body class="<?= h($bodyClass) ?>">
<a class="skip-link" href="#main">تخطَّ إلى المحتوى</a>
<header class="appbar">
    <div class="appbar-inner">
        <a class="brand" href="index.php" aria-label="TripSplit — الرئيسية">
            <img src="assets/favicon.svg" alt="" width="32" height="32">
            <span class="brand-text"><strong>TripSplit</strong><small>مصروفات الرحلة</small></span>
        </a>
        <nav class="topnav" aria-label="التنقل الرئيسي">
            <?php foreach ($navItems as $file => [$label, $ic]): ?>
                <a href="<?= h($file) ?>"<?= $file === $active ? ' aria-current="page"' : '' ?>><?= icon($ic) ?><span><?= h($label) ?></span></a>
            <?php endforeach; ?>
        </nav>
        <div class="appbar-actions">
            <button type="button" class="icon-btn" data-theme-toggle aria-label="تبديل الوضع الليلي" title="الوضع الليلي">
                <?= icon('moon', 'icon i-moon') ?><?= icon('sun', 'icon i-sun') ?>
            </button>
            <form method="post" action="logout.php" class="inline-form">
                <?= csrfField() ?>
                <button type="submit" class="icon-btn" aria-label="تسجيل الخروج" title="تسجيل الخروج"><?= icon('logout') ?></button>
            </form>
        </div>
    </div>
</header>
<nav class="bottomnav" aria-label="التنقل السفلي">
    <?php foreach ($navItems as $file => [$label, $ic]): ?>
        <a href="<?= h($file) ?>"<?= $file === $active ? ' aria-current="page"' : '' ?>><?= icon($ic) ?><span><?= h($label) ?></span></a>
    <?php endforeach; ?>
</nav>
<main id="main" class="container">
<?php
}

function pageEnd(array $opts = []): void {
    ?>
</main>
<footer class="site-footer">
    <div class="container">
        <span>TripSplit <?= h(APP_VERSION) ?> · تطوير أبو فيصل © <?= date('Y') ?></span>
        <a href="https://github.com/fahad4x4/TripSplit" rel="noopener">GitHub</a>
    </div>
</footer>
<?php if (!empty($opts['csrf'])): ?>
<script>window.TRIPSPLIT_CSRF = <?= js(csrfToken()) ?>;</script>
<?php endif; ?>
<script src="assets/app.js?v=<?= h(APP_VERSION) ?>" defer></script>
</body>
</html>
<?php
}

/** عنوان الصفحة مع وصف وأزرار اختيارية. */
function pageTitle(string $title, string $subtitle = '', string $actionsHtml = ''): void {
    echo '<div class="page-head"><div><h1>' . h($title) . '</h1>';
    if ($subtitle !== '') echo '<p class="muted">' . h($subtitle) . '</p>';
    echo '</div>';
    if ($actionsHtml !== '') echo '<div class="page-actions">' . $actionsHtml . '</div>';
    echo '</div>';
}

/** حالة فارغة موحدة. */
function emptyState(string $title, string $text = '', string $ctaHref = '', string $ctaLabel = '', string $ic = 'receipt'): string {
    $out = '<div class="empty">' . icon($ic, 'icon empty-icon') . '<h3>' . h($title) . '</h3>';
    if ($text !== '') $out .= '<p>' . h($text) . '</p>';
    if ($ctaHref !== '') $out .= '<a class="btn btn-primary" href="' . h($ctaHref) . '">' . icon('plus') . h($ctaLabel) . '</a>';
    return $out . '</div>';
}

/** الحرف الأول من الاسم للصورة الرمزية. */
function initial(string $name): string {
    if (preg_match('/^\X/u', trim($name), $m)) return $m[0];
    return '?';
}

/** شارة حالة الرصيد (له / عليه / متوازن). */
function statusBadge(string $status): string {
    $cls = $status === STATUS_CREDIT ? 'badge-pos' : ($status === STATUS_DEBIT ? 'badge-neg' : 'badge-zero');
    return '<span class="badge ' . $cls . '">' . h($status) . '</span>';
}
