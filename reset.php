<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$config = require 'config.php';

$data_file = $config['data_file'] ?? 'expenses.json'; // مسار الملف الأساسي

// إنشاء مجلد النسخ الاحتياطي إذا لم يكن موجود
$backup_folder = 'backups';
if (!is_dir($backup_folder)) {
    mkdir($backup_folder, 0777, true);
}

// أخذ نسخة احتياطية قبل الحذف
if (file_exists($data_file)) {
    $backup_file = $backup_folder . '/expenses_backup_' . date('Ymd_His') . '.json';
    copy($data_file, $backup_file);
}

$empty_data = [
    'team' => [],
    'expenses' => [],
    'contributions' => []
];

// مسح البيانات بعد أخذ النسخة
file_put_contents($data_file, json_encode($empty_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

// إعادة التوجيه
header('Location: manage.php');
exit();
?>
