<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once 'config.php';
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$data_file = 'expenses.json';
$data = json_decode(file_get_contents($data_file), true);

if (!$data) {
    $data = [
        'team' => [],
        'expenses' => [],
        'contributions' => [],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';

    if ($type === 'team') {
        $name = trim($_POST['name']);
        if ($name !== '' && !in_array($name, $data['team'])) {
            $data['team'][] = $name;
        }

    } elseif ($type === 'expense') {
        $paidBy = $_POST['paid_by'];
        $amount = (float)$_POST['amount'];
        $note = $_POST['note'];
        $participants = $_POST['participants'] ?? ($data['team'] ?? []);

        if (empty($participants)) {
            $participants = $data['team'] ?? [];
        }

        $expense = [
            'paid_by' => $paidBy,
            'amount' => $amount,
            'note' => $note,
            'participants' => $participants,
            'date' => date('Y-m-d'),
        ];
        $data['expenses'][] = $expense;

    } elseif ($type === 'contribution') {
        $contribution = [
            'name' => $_POST['name'],
            'amount' => (float)$_POST['amount'],
            'date' => $_POST['date'],
        ];
        $data['contributions'][] = $contribution;

    } elseif ($type === 'edit_member') {
        $oldName = trim($_POST['old_name']);
        $newName = trim($_POST['new_name']);
        $editType = $_POST['edit_type'];

        if ($oldName && $newName) {
            if ($editType === 'name') {
                foreach ($data['team'] as &$member) {
                    if ($member === $oldName) {
                        $member = $newName;
                    }
                }
            }
            if ($editType === 'expenses' || $editType === 'all') {
                foreach ($data['expenses'] as &$expense) {
                    if ($expense['paid_by'] === $oldName) {
                        $expense['paid_by'] = $newName;
                    }
                    // نعدل المشاركين أيضاً إذا كان تعديل عام
                    if (!empty($expense['participants'])) {
                        foreach ($expense['participants'] as &$participant) {
                            if ($participant === $oldName) {
                                $participant = $newName;
                            }
                        }
                    }
                }
            }
            if ($editType === 'contributions' || $editType === 'all') {
                foreach ($data['contributions'] as &$contribution) {
                    if ($contribution['name'] === $oldName) {
                        $contribution['name'] = $newName;
                    }
                }
            }
        }

    } elseif ($type === 'delete_member') {
        $name = $_POST['name'];
        $deleteType = $_POST['delete_type'];

        if ($deleteType === 'name' || $deleteType === 'all') {
            if (($key = array_search($name, $data['team'])) !== false) {
                unset($data['team'][$key]);
            }
        }
        if ($deleteType === 'expenses' || $deleteType === 'all') {
            $data['expenses'] = array_filter($data['expenses'], function($expense) use ($name) {
                return $expense['paid_by'] !== $name;
            });
        }
        if ($deleteType === 'contributions' || $deleteType === 'all') {
            $data['contributions'] = array_filter($data['contributions'], function($contribution) use ($name) {
                return $contribution['name'] !== $name;
            });
        }

        $data['team'] = array_values($data['team']);
        $data['expenses'] = array_values($data['expenses']);
        $data['contributions'] = array_values($data['contributions']);
    }

    // نسخ احتياطي قبل الحفظ
    $backup_folder = 'backups';
    if (!is_dir($backup_folder)) {
        mkdir($backup_folder, 0777, true);
    }
    copy($data_file, $backup_folder . '/expenses_backup_' . date('Ymd_His') . '.json');

    // حفظ
    file_put_contents($data_file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    http_response_code(200);
    echo 'تم الحفظ بنجاح';
    exit();
}

http_response_code(400);
echo 'طلب غير صحيح';
?>
