<?php
/**
 * حفظ عمليات صفحة الإدارة (AJAX). يتطلب تسجيل الدخول + POST + توكن CSRF.
 * يرد بنص عربي ورمز HTTP: 200 نجاح، 422 خطأ في المدخلات.
 */
require __DIR__ . '/auth.php';
requirePostWithCsrf();

header('Content-Type: text/plain; charset=UTF-8');

$type = (string) ($_POST['type'] ?? '');
$post = $_POST;

$error = updateData(function (array $data) use ($type, $post) {
    switch ($type) {
        case 'team':
            return addMember($data, (string) ($post['name'] ?? ''));
        case 'expense':
            return addExpense($data, $post);
        case 'contribution':
            return addContribution($data, $post);
        case 'edit_member':
            return renameMember($data, (string) ($post['old_name'] ?? ''), (string) ($post['new_name'] ?? ''));
        case 'delete_member':
            return deleteMember($data, (string) ($post['name'] ?? ''), !empty($post['redistribute']));
        default:
            return [$data, 'طلب غير صحيح.'];
    }
});

if ($error !== null) {
    http_response_code(422);
    echo $error;
    exit();
}

http_response_code(200);
echo 'تم الحفظ بنجاح';
exit();
