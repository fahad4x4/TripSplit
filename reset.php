<?php
/**
 * إعادة تعيين بيانات الرحلة. POST فقط مع توكن CSRF، ويؤخذ نسخة احتياطية قبل المسح.
 */
require __DIR__ . '/auth.php';
requirePostWithCsrf();

header('Content-Type: text/plain; charset=UTF-8');

$error = updateData(function (array $data) {
    return [emptyData(), null]; // saveData يأخذ نسخة احتياطية قبل الكتابة
});

if ($error !== null) {
    http_response_code(500);
    echo $error;
    exit();
}

echo 'تمت إعادة الضبط';
exit();
