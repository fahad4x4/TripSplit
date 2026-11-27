<?php
require __DIR__ . '/auth.php';

$config = appConfig();
$currency = $config['currency'];
$exchange_rate = (float) $config['exchange_rate_to_sar'];
$cur = h($currency);

$data = loadData();
$team = $data['team'];
$expenses = $data['expenses'];

/**
 * دالة لإرجاع كلاس اللون المناسب للفئة
 */
function expenseBadgeClass($note) {
    switch ($note) {
        case 'سكن': return 'bg-success';
        case 'طعام': return 'bg-warning';
        case 'مواصلات': return 'bg-danger';
        case 'تذاكر طيران': return 'bg-primary';
        case 'تأمين سفر': return 'bg-info';
        case 'تأجير سيارات': return 'bg-purple';
        case 'تذاكر دخول': return 'bg-pink';
        case 'وقود': return 'bg-orange';
        case 'هايبر ماركت': return 'bg-teal';
        case 'اخرى': return 'bg-secondary';
        default: return 'bg-secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل المصروفات - إدارة مصروفات الرحلة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="styles.css" rel="stylesheet"> 
    <style>
        /* تحسين ابراز اسم الدافع */
        .payer-badge {
            border: 2px solid var(--accent-color, #F2A950);
            font-weight: bold;
            font-size: 0.95em;
            background: #fffbe5;
            color: #b26d00;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container text-center">
            <div class="d-flex flex-column align-items-center gap-3">
                <div class="logo-container">
                    <img src="icons/icon-192x192.png" alt="شعار الرحلة" class="img-fluid">
                </div>
                <h1 class="display-6 fw-bold text-gradient">
                    <i class="fas fa-receipt me-2"></i>
                    سجل المصروفات
                </h1>
                <a href="index.php" class="text-decoration-none" style="color: var(--accent-color);">
                    <i class="fas fa-home me-2"></i> العودة للصفحة الرئيسية
                </a>
            </div>
            <div class="form-check form-switch mt-3">
                <input class="form-check-input" type="checkbox" id="darkModeSwitch" onchange="toggleDarkMode()">
                <label class="form-check-label" for="darkModeSwitch">الوضع الليلي</label>
            </div>
        </div>
    </header>

    <main class="container mt-4 mb-5">
        <section>
            <div class="card">
                <div class="card-header expenses d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <i class="fas fa-receipt me-2"></i>سجل المصروفات
                        <span class="badge bg-light text-dark"><?= (int) count($expenses) ?> مصروفات</span>
                    </div>
                    <a href="manage.php" class="btn btn-outline-primary btn-sm ms-2">
                        <i class="fas fa-plus"></i> إضافة مصروف جديد
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($expenses)): ?>
                        <div class="row g-3">
                            <?php foreach (array_reverse($expenses) as $exp): ?>
                            <div class="col-12 col-md-6">
                                <div class="p-3 border rounded-3 shadow-sm d-flex flex-column gap-2" style="background-color: #fdfaf5;">
                                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                        <span class="badge <?= expenseBadgeClass($exp['note'] ?? '') ?>"><?= h($exp['note'] ?? '') ?></span>
                                        <div class="text-muted small"><?= h(safeDate($exp['date'] ?? '', 'd/m/Y')) ?></div>
                                    </div>
                                    <!-- عرض المشاركين -->
                                    <?php $parts = expenseParticipants($exp, $team); $payer = (string) ($exp['paid_by'] ?? ''); $personal = expenseSource($exp) === 'personal'; ?>
                                    <?php if (!empty($parts)): ?>
                                        <div class="d-flex flex-wrap gap-2 small">
                                            <?php foreach ($parts as $participant): ?>
                                                <?php $isPayer = $personal && $participant === $payer; ?>
                                                <span class="badge <?= $isPayer ? 'payer-badge' : 'bg-secondary' ?>">
                                                    <?= h($participant) ?>
                                                    <?php if ($isPayer): ?>
                                                        <i class="fas fa-crown ms-1" title="الدافع"></i>
                                                    <?php endif; ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between align-items-end mt-2 flex-wrap gap-2">
                                        <div>
                                            <div class="fw-bold"><?= number_format((float) $exp['amount'], 2) ?> <?= $cur ?></div>
                                            <div class="text-muted small">
                                                ≈ <?= number_format((float) $exp['amount'] * $exchange_rate, 2) ?>
                                                <img src="https://alghathbar.com/trip/icons/Saudi_Riyal_Symbol.svg" alt="ريال سعودي" width="14">
                                            </div>
                                        </div>
                                        <div class="text-muted small">
                                            دفعها: <span class="fw-bold"><?= h(payerLabel($exp)) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">لا توجد مصروفات مسجلة حالياً</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <footer class="text-center py-4 mt-5" style="background: var(--main-color); color: white;">
        <div class="container">
            <p class="mb-0">
                تم تطوير النظام بواسطة 
                <i class="fas fa-code"></i> أبو فيصل
                © <?= date('Y') ?>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // الوضع الليلي
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            if (document.body.classList.contains('dark-mode')) {
                localStorage.setItem('darkMode', 'enabled');
            } else {
                localStorage.setItem('darkMode', 'disabled');
            }
        }
        window.onload = function () {
            if (localStorage.getItem('darkMode') === 'enabled') {
                document.body.classList.add('dark-mode');
                var sw = document.getElementById('darkModeSwitch');
                if (sw) sw.checked = true;
            }
        }
    </script>
</body>
</html>