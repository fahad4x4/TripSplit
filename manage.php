<?php
require __DIR__ . '/auth.php';

$data = loadData();
$team = $data['team'];
$today = date('Y-m-d');
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
        <form method="post" action="logout.php" class="d-inline">
            <?= csrfField() ?>
            <button type="submit" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-sign-out-alt"></i> خروج
            </button>
        </form>
    </div>
</header>
<main>
<?php
$payerOptions = array_merge([KITTY => KITTY . ' (القطة المشتركة)'], array_combine($team, $team) ?: []);
$forms = [
    'formMember' => [
        'title' => '➕ إضافة عضو',
        'cardHeaderClass' => 'bg-purple',
        'hiddenType' => 'team',
        'inputs' => [
            ['type' => 'text', 'name' => 'name', 'placeholder' => 'اسم العضو', 'maxlength' => 50]
        ],
        'button' => '✔️ إضافة',
        'icon' => 'fa-user-plus'
    ],
    'formEdit' => [
        'title' => '✏️ تعديل اسم عضو',
        'cardHeaderClass' => 'bg-info',
        'hiddenType' => 'edit_member',
        'hint' => 'يتم تحديث الاسم في الفريق والمصروفات والمشاركين والمساهمات.',
        'inputs' => [
            ['type' => 'select', 'name' => 'old_name', 'options' => array_combine($team, $team) ?: [], 'placeholder' => '-- اختر العضو --'],
            ['type' => 'text', 'name' => 'new_name', 'placeholder' => 'الاسم الجديد', 'maxlength' => 50]
        ],
        'button' => '✔️ حفظ الاسم',
        'icon' => 'fa-user-edit'
    ],
    'formExpense' => [
        'title' => '💵 تسجيل مصروف',
        'cardHeaderClass' => 'bg-orange',
        'hiddenType' => 'expense',
        'hint' => 'اختر "' . KITTY . '" إذا دُفع المصروف من القطة، أو اسم العضو إذا دفعه من جيبه.',
        'inputs' => [
            ['type' => 'select', 'name' => 'paid_by', 'options' => $payerOptions, 'placeholder' => '-- اختر الدافع --'],
            ['type' => 'number', 'name' => 'amount', 'placeholder' => 'المبلغ'],
            ['type' => 'select', 'name' => 'note', 'options' => array_combine(
                $cats = ['سكن', 'طعام', 'مواصلات', 'تذاكر طيران', 'تأمين سفر', 'تأجير سيارات', 'تذاكر دخول', 'وقود', 'هايبر ماركت', 'اخرى'], $cats
            ), 'placeholder' => '-- اختر نوع المصروف --'],
            ['type' => 'multiselect', 'name' => 'participants', 'options' => array_combine($team, $team) ?: [], 'placeholder' => 'اختر المشاركين (بدون اختيار = كل الفريق)'],
            ['type' => 'date', 'name' => 'date', 'placeholder' => 'اختر التاريخ', 'value' => $today]
        ],
        'button' => '✔️ تسجيل',
        'icon' => 'fa-money-bill-wave'
    ],
    'formContribution' => [
        'title' => '💰 تسجيل قطة',
        'cardHeaderClass' => 'bg-teal',
        'hiddenType' => 'contribution',
        'inputs' => [
            ['type' => 'select', 'name' => 'name', 'options' => array_combine($team, $team) ?: [], 'placeholder' => '-- اختر المساهم --'],
            ['type' => 'number', 'name' => 'amount', 'placeholder' => 'أدخل مبلغ القطة'],
            ['type' => 'date', 'name' => 'date', 'placeholder' => 'اختر التاريخ', 'value' => $today]
        ],
        'button' => '✔️ تسجيل القطة',
        'icon' => 'fa-coins'
    ],
    'formDelete' => [
        'title' => '🗑️ حذف عضو',
        'cardHeaderClass' => 'bg-danger',
        'hiddenType' => 'delete_member',
        'hint' => 'لا يمكن حذف عضو له مساهمات أو مدفوعات. إذا كان مشاركاً في مصروفات، فعّل خيار إعادة التوزيع.',
        'inputs' => [
            ['type' => 'select', 'name' => 'name', 'options' => array_combine($team, $team) ?: [], 'placeholder' => '-- اختر العضو --'],
            ['type' => 'checkbox', 'name' => 'redistribute', 'placeholder' => 'إعادة توزيع حصته على بقية المشاركين في كل مصروف']
        ],
        'button' => '🗑️ حذف',
        'icon' => 'fa-trash-alt'
    ]
];

foreach ($forms as $formId => $formData) {
    echo "<section class='mb-4'><div class='card'><div class='card-header " . h($formData['cardHeaderClass']) . " text-white'>
            <i class='fas " . h($formData['icon']) . " me-2'></i> " . h($formData['title']) . "
          </div>
          <div class='card-body'>";
    if (!empty($formData['hint'])) {
        echo "<p class='text-muted small mb-3'>" . h($formData['hint']) . "</p>";
    }
    echo "<form id='" . h($formId) . "' autocomplete='off'>
            " . csrfField() . "
            <input type='hidden' name='type' value='" . h($formData['hiddenType']) . "'>";
    foreach ($formData['inputs'] as $input) {
        $name = h($input['name']);
        echo "<div class='mb-3'>";
        if ($input['type'] == 'select') {
            echo "<select name='{$name}' class='form-select' required>
                    <option value='' disabled selected>" . h($input['placeholder']) . "</option>";
            foreach ($input['options'] as $val => $txt) {
                echo "<option value='" . h($val) . "'>" . h($txt) . "</option>";
            }
            echo "</select>";
        } elseif ($input['type'] == 'multiselect') {
            echo "<label class='form-label d-block'>" . h($input['placeholder']) . "</label>";
            echo "<div class='d-flex flex-wrap'>";
            $i = 0;
            foreach ($input['options'] as $val => $txt) {
                $id = $name . '_' . $i++;
                echo "<div class='form-check me-3'>
                        <input class='form-check-input' type='checkbox' name='{$name}[]' value='" . h($val) . "' id='{$id}'>
                        <label class='form-check-label' for='{$id}'>" . h($txt) . "</label>
                      </div>";
            }
            echo "</div>";
        } elseif ($input['type'] == 'checkbox') {
            $id = h($formId . '_' . $input['name']);
            echo "<div class='form-check'>
                    <input class='form-check-input' type='checkbox' name='{$name}' value='1' id='{$id}'>
                    <label class='form-check-label' for='{$id}'>" . h($input['placeholder']) . "</label>
                  </div>";
        } else {
            $extra = '';
            if ($input['type'] === 'number') $extra .= " min='0.01' step='0.01'";
            if (!empty($input['maxlength'])) $extra .= " maxlength='" . (int) $input['maxlength'] . "'";
            if (isset($input['value'])) $extra .= " value='" . h($input['value']) . "'";
            echo "<input type='" . h($input['type']) . "' name='{$name}' class='form-control' placeholder='" . h($input['placeholder']) . "'{$extra} required>";
        }
        echo "</div>";
    }
    echo "<button type='submit' class='btn btn-light w-100'>
            <i class='fas " . h($formData['icon']) . " me-2'></i> " . h($formData['button']) . "
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
const CSRF_TOKEN = <?= js(csrfToken()) ?>;
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
    toast.innerHTML = `<div class='d-flex'><div class='toast-body'></div><button type='button' class='btn-close btn-close-white me-2 m-auto' data-bs-dismiss='toast'></button></div>`;
    toast.querySelector('.toast-body').textContent = message;
    toastContainer.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}

// AJAX form handling
// النماذج التي تغيّر قائمة الأعضاء تعيد تحميل الصفحة لتحديث القوائم
const RELOAD_FORMS = ['formMember', 'formEdit', 'formDelete'];
function handleAjax(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        fetch('save.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: { 'X-CSRF-Token': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.text().then(text => ({ ok: response.ok, text })))
        .then(({ ok, text }) => {
            if (ok) {
                showToast('✔️ ' + text);
                if (RELOAD_FORMS.includes(formId)) {
                    setTimeout(() => window.location.reload(), 1000);
                    return;
                }
                const dateInput = form.querySelector('[name="date"]');
                const dateValue = dateInput ? dateInput.value : null;
                form.reset();
                if (dateInput) dateInput.value = dateValue;
            } else {
                showToast(`❌ ${text}`, false);
            }
        })
        .catch(() => showToast('❌ تعذر الاتصال بالخادم', false));
    });
}
['formMember', 'formEdit', 'formExpense', 'formContribution', 'formDelete'].forEach(handleAjax);

// Reset data with confirmation (POST + CSRF)
function doReset() {
    const fd = new FormData();
    fd.append('csrf_token', CSRF_TOKEN);
    fetch('reset.php', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => {
        if (response.ok) {
            showToast('✔️ تم إعادة ضبط البيانات بنجاح');
            setTimeout(() => { window.location.href = 'index.php'; }, 2000);
        } else {
            response.text().then(text => showToast(`❌ حدث خطأ: ${text}`, false));
        }
    })
    .catch(() => showToast('❌ تعذر الاتصال بالخادم', false));
}
resetButton.addEventListener('click', function() {
    if (typeof Swal === 'undefined') {
        if (confirm('هل أنت متأكد؟ سيتم مسح جميع البيانات ولا يمكن التراجع!')) doReset();
        return;
    }
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
        if (result.isConfirmed) doReset();
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