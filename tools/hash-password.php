<?php
/**
 * يولّد hash لكلمة المرور ويطبع سطر config.local.php.
 * Usage: php tools/hash-password.php            (prompts)
 *        php tools/hash-password.php 'secret'   (argument; ends up in shell history)
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$pass = $argv[1] ?? null;
if ($pass === null) {
    fwrite(STDERR, 'Password: ');
    if (DIRECTORY_SEPARATOR === '/') {
        @system('stty -echo');
    }
    $pass = rtrim((string) fgets(STDIN), "\r\n");
    if (DIRECTORY_SEPARATOR === '/') {
        @system('stty echo');
    }
    fwrite(STDERR, PHP_EOL);
}
if ($pass === '') {
    fwrite(STDERR, "Empty password.\n");
    exit(1);
}
$hash = password_hash($pass, PASSWORD_DEFAULT);
echo "<?php\nreturn [\n    'password_hash' => " . var_export($hash, true) . ",\n];\n";
