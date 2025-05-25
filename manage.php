<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$config = require 'config.php';
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$data = [];
if (file_exists($config['data_file'])) {
    $json = file_get_contents($config['data_file']);
    $data = json_decode($json, true) ?? [];
}

$team = $data['team'] ?? [];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة بيانات الرحلة</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link href="styles.css" rel="stylesheet">
    <style>
        main { max-width: 720px; margin: auto; }
        @media (max-width: 600px) {
            main { padding: 0 4px; }
        }
    </style>
</head>
<body>
<header class="header">
    <h2 class="mb-2">🛠️ إدارة بيانات الرحلة</h2>
    <div class="mb-1">
        <a href="index.php" class="btn btn-light btn-sm">
            <i class="fas fa-home"></i> العودة للرئيسية
        </a>
        <button id="dark-mode-toggle" class="btn btn-dark btn-sm">
            <i class="fas fa-moon"></i> الوضع الليلي
        </button>
    </div>
</header>
<main>
<?php
$forms = [
    'formMember' => [
        'title' => '➕ إضافة عضو',
        'cardHeaderClass' => 'bg-purple',
        'hiddenType' => 'team',
        'inputs' => [
            ['type' => 'text', 'name' => 'name', 'placeholder' => 'اسم العضو']
        ],
        'button' => '✔️ إضافة',
        'icon' => 'fa-user-plus'
    ],
    'formExpense' => [
        'title' => '💵 تسجيل مصروف',
        'cardHeaderClass' => 'bg-orange',
        'hiddenType' => 'expense',
        'inputs' => [
            ['type' => 'select', 'name' => 'paid_by', 'options' => $team, 'placeholder' => '-- اختر الدافع --'],
            ['type' => 'number', 'name' => 'amount', 'placeholder' => 'المبلغ'],
             ['type' => 'select', 'name' => 'note', 'options' => ['سكن', 'طعام', 'مواصلات', 'تذاكر طيران', 'تأمين سفر', 'تأجير سيارات', 'تذاكر دخول', 'وقود', 'هايبر ماركت', 'اخرى'], 'placeholder' => '-- اختر نوع المصروف --'],
            ['type' => 'multiselect', 'name' => 'participants', 'options' => $team, 'placeholder' => 'اختر المشاركين'],
            ['type' => 'date', 'name' => 'date', 'placeholder' => 'اختر التاريخ']
        ],
        'button' => '✔️ تسجيل',
        'icon' => 'fa-money-bill-wave'
    ],
    'formContribution' => [
        'title' => '💰 تسجيل قطة',
        'cardHeaderClass' => 'bg-teal',
        'hiddenType' => 'contribution',
        'inputs' => [
            ['type' => 'select', 'name' => 'name', 'options' => $team, 'placeholder' => '-- اختر المساهم --'],
            ['type' => 'number', 'name' => 'amount', 'placeholder' => 'أدخل مبلغ القطة'],
             ['type' => 'date', 'name' => 'date', 'placeholder' => 'اختر التاريخ']
        ],
        'button' => '✔️ تسجيل القطة',
        'icon' => 'fa-coins'
    ],
     'formDelete' => [
        'title' => '🗑️ حذف بيانات',
        'cardHeaderClass' => 'bg-danger',
        'hiddenType' => 'delete_member',
        'inputs' => [
            ['type' => 'select', 'name' => 'name', 'options' => $team, 'placeholder' => '-- اختر العضو --'],
            ['type' => 'select', 'name' => 'delete_type', 'options' => ['name'=>'الاسم فقط','expenses'=>'المصروفات فقط','contributions'=>'المساهمات فقط','all'=>'الكل'], 'placeholder' => '-- ماذا تريد حذف؟ --']
        ],
        'button' => '🗑️ حذف',
        'icon' => 'fa-trash-alt'
    ]
];

foreach ($forms as $formId => $formData) {
    echo "<section class='mb-4'><div class='card'><div class='card-header {$formData['cardHeaderClass']} text-white'>
            <i class='fas {$formData['icon']} me-2'></i> {$formData['title']}
          </div>
          <div class='card-body'>
            <form id='$formId' autocomplete='off'>
            <input type='hidden' name='type' value='{$formData['hiddenType']}'>";
    foreach ($formData['inputs'] as $input) {
        echo "<div class='mb-3'>";
        if ($input['type'] == 'select') {
            echo "<select name='{$input['name']}' class='form-select' required>
                    <option disabled selected>{$input['placeholder']}</option>";
            foreach ($input['options'] as $k => $v) {
                $val = is_numeric($k) ? $v : $k;
                $txt = is_numeric($k) ? $v : $v;
                echo "<option value='" . htmlspecialchars($val) . "'>" . htmlspecialchars($txt) . "</option>";
            }
            echo "</select>";
        } elseif ($input['type'] == 'multiselect') {
            echo "<label class='form-label d-block'>{$input['placeholder']}</label>";
            echo "<div class='d-flex flex-wrap'>";
            foreach ($input['options'] as $k => $v) {
                $val = is_numeric($k) ? $v : $k;
                $txt = is_numeric($k) ? $v : $v;
                echo "<div class='form-check me-3'>
                        <input class='form-check-input' type='checkbox' name='{$input['name']}[]' value='" . htmlspecialchars($val) . "' id='{$input['name']}_".htmlspecialchars($val)."'>
                        <label class='form-check-label' for='{$input['name']}_".htmlspecialchars($val)."'>" . htmlspecialchars($txt) . "</label>
                      </div>";
            }
            echo "</div>";
        } else {
            echo "<input type='{$input['type']}' name='{$input['name']}' class='form-control' placeholder='{$input['placeholder']}' required>";
        }
        echo "</div>";
    }
    echo "<button type='submit' class='btn btn-light w-100'>
            <i class='fas {$formData['icon']} me-2'></i> {$formData['button']}
          </button>
          </form>
          </div>
          </div></section>";
}
?>
<section>
<div class="card mt-4">
    <div class="card-header bg-danger text-white">
        <i class="fas fa-exclamation-triangle me-2"></i>
        🗑️ إعادة تعيين جميع بيانات الرحلة
    </div>
    <div class="card-body text-center">
        <p class="text-muted mb-3">تنبيه: هذا الإجراء سيؤدي إلى مسح جميع المشاركين والمصاريف والمساهمات نهائياً!</p>
        <button id="reset-button" class="btn btn-danger px-5 py-2">
            <i class="fas fa-fire me-2"></i> نعم، أعد ضبط كل شيء
        </button>
    </div>
</div>
</section>
<div class="toast-container"></div>
</main>
<footer class="py-3 mt-5">
    <small>© <?= date('Y') ?> تطوير أبو فيصل</small>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const body = document.body;
const header = document.querySelector('.header');
const cards = document.querySelectorAll('.card');
const cardHeaders = document.querySelectorAll('.card-header');
const resetButton = document.getElementById('reset-button');
const toastContainer = document.querySelector('.toast-container');
const formControls = document.querySelectorAll('.form-control, .form-select');

// Toast function
function showToast(message, success = true) {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${success ? 'success' : 'danger'} border-0 show mb-2`;
    toast.role = 'alert';
    toast.innerHTML = `<div class='d-flex'><div class='toast-body'>${message}</div><button type='button' class='btn-close btn-close-white me-2 m-auto' data-bs-dismiss='toast'></button></div>`;
    toastContainer.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}

// AJAX form handling
function handleAjax(formId) {
    const form = document.getElementById(formId);
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        fetch('save.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                showToast('✔️ تم الحفظ بنجاح');
                form.reset();
                 if (formId === 'formDelete') {
                      form.querySelector('[name="name"]').selectedIndex = 0;
                      form.querySelector('[name="delete_type"]').selectedIndex = 0;
                 }
            } else {
                response.text().then(text => {
                    showToast(`❌ حدث خطأ: ${text}`, false);
                });
            }
        });
    });
}
['formMember', 'formExpense', 'formContribution', 'formDelete'].forEach(handleAjax);

// Reset data with confirmation
resetButton.addEventListener('click', function() {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: 'هذا الإجراء سيؤدي إلى مسح جميع البيانات ولا يمكن التراجع عنه!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: '🔥 نعم، أعد ضبط كل شيء',
        cancelButtonText: '🚫 إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('reset.php', {
                method: 'POST'
            })
            .then(response => {
                if (response.ok) {
                    showToast('✔️ تم إعادة ضبط البيانات بنجاح');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 2000);
                } else {
                    response.text().then(text => {
                       showToast(`❌ حدث خطأ: ${text}`, false);
                    });
                }
            });
        }
    });
});

// Dark mode toggle
const darkModeToggle = document.getElementById('dark-mode-toggle');
const savedTheme = localStorage.getItem('theme');
function applyDarkMode(on) {
    if (on) {
        body.classList.add('dark-mode');
        header.classList.add('dark-mode');
        cards.forEach(card => card.classList.add('dark-mode'));
        cardHeaders.forEach(cardHeader => cardHeader.classList.add('dark-mode'));
        formControls.forEach(control => control.classList.add('dark-mode'));
        darkModeToggle.innerHTML = '<i class="fas fa-sun"></i> الوضع النهاري';
    } else {
        body.classList.remove('dark-mode');
        header.classList.remove('dark-mode');
        cards.forEach(card => card.classList.remove('dark-mode'));
        cardHeaders.forEach(cardHeader => cardHeader.classList.remove('dark-mode'));
        formControls.forEach(control => control.classList.remove('dark-mode'));
        darkModeToggle.innerHTML = '<i class="fas fa-moon"></i> الوضع الليلي';
    }
}
if (savedTheme === 'dark') applyDarkMode(true);
darkModeToggle.addEventListener('click', () => {
    const isDark = !body.classList.contains('dark-mode');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    applyDarkMode(isDark);
});
</script>
</body>
</html>