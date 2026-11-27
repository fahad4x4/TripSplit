<?php
/**
 * انسخ هذا الملف إلى config.local.php (غير مرفوع إلى Git) وضع فيه hash كلمة المرور.
 * Copy this file to config.local.php (git-ignored) and put your password hash in it.
 *
 * لتوليد الـ hash / To generate the hash:
 *   php tools/hash-password.php
 * أو / or:
 *   php -r 'echo password_hash("yourpass", PASSWORD_DEFAULT), PHP_EOL;'
 *
 * يمكنك أيضاً تجاوز أي إعداد من config.php هنا (مثل currency أو exchange_rate_to_sar).
 */
return [
    'password_hash' => 'REPLACE_WITH_OUTPUT_OF_password_hash',
];
