<?php
require __DIR__ . '/auth.php';

$config = appConfig();
$currency = $config['currency'];
$exchange_rate = (float) $config['exchange_rate_to_sar'];

$data = loadData();
$team = $data['team'];
$expenses = $data['expenses'];
$contributions = $data['contributions'];

$total = totalExpenses($expenses);
$kitty = kittyBalance($expenses, $contributions);
$balances = calculateBalances($team, $expenses, $contributions);
$settlement = computeSettlement($balances);
$transactions = $settlement['transactions'];

// حساب مجموع المصروفات لكل فئة
$expensesByCategory = [];
foreach ($expenses as $expense) {
    $category = (string) ($expense['note'] ?? 'اخرى');
    $expensesByCategory[$category] = ($expensesByCategory[$category] ?? 0) + (float) ($expense['amount'] ?? 0);
}

// تصنيف المصروفات: مشتركة (مشاركان أو أكثر) أو فردية
$shared_expenses = [];
$individual_expenses = [];
foreach ($expenses as $exp) {
    $parts = expenseParticipants($exp, $team);
    $participants_count = count($parts);
    if ($participants_count >= 2) {
        $shared_expenses[] = $exp;
    } elseif ($participants_count == 1) {
        $member = $parts[0];
        if (!isset($individual_expenses[$member])) {
            $individual_expenses[$member] = ['total' => 0, 'count' => 0];
        }
        $individual_expenses[$member]['total'] += (float) $exp['amount'];
        $individual_expenses[$member]['count']++;
    }
}

$total_shared = array_sum(array_map(function ($e) { return (float) ($e['amount'] ?? 0); }, $shared_expenses));
$shared_count = count($shared_expenses);
$cur = h($currency);
$sarIcon = '<img src="https://alghathbar.com/trip/icons/Saudi_Riyal_Symbol.svg" width="14" alt="ريال سعودي">';
$statusBadge = [STATUS_CREDIT => 'bg-success', STATUS_DEBIT => 'bg-danger', STATUS_BALANCED => 'bg-secondary'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة مصروفات الرحلة 🇲🇦</title>

    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    <!-- أيقونات -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- خط Google Tajawal -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- التنسيقات المخصصة -->
    <link href="styles.css" rel="stylesheet"> 
</head>

<body>
    <header class="header">
        <div class="container text-center">
            <div class="d-flex flex-column align-items-center gap-3">
                <div class="logo-container">
                    <img src="icons/icon-192x192.png" alt="شعار الرحلة" class="img-fluid">
                </div>
                <h1 class="display-6 fw-bold text-gradient">
                    <i class="fas fa-coins me-2"></i>
                    إدارة مصروفات الرحلة <span style="font-size: 0.7em;"></span>
                </h1>
                <p class="text-muted mb-0">مشاركة عادلة، إدارة ذكية</p>
                <a href="manage.php" class="text-decoration-none" style="color: var(--accent-color);">
                    <i class="fas fa-code"></i> صفحة الادارة
                </a> 
            </div>
            <div class="form-check form-switch mt-3">
                <input class="form-check-input" type="checkbox" id="darkModeSwitch" onchange="toggleDarkMode()">
                <label class="form-check-label" for="darkModeSwitch">الوضع الليلي</label>
            </div>
        </div>
    </header>

    <main class="container">
        <!-- نظرة سريعة -->
        <section class="dashboard-summary mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-chart-bar me-2"></i> نظرة سريعة
                </div>
                <div class="card-body">
                    <div class="row text-center g-3">
                        <!-- مجموع القطة -->
                        <div class="col-md-3">
                            <div class="p-3 border rounded-3 shadow-sm bg-light">
                                <div class="text-muted small">مجموع القطة</div>
                                <div class="h5 fw-bold">
                                    <?= number_format(totalContributions($contributions), 2) ?> <?= $cur ?>
                                </div>
                                <a href="contributions.php" class="btn btn-sm btn-primary mt-2">عرض التفاصيل</a>
                            </div>
                        </div>
                        <!-- مجموع المصروفات -->
                        <div class="col-md-3">
                            <div class="p-3 border rounded-3 shadow-sm bg-light">
                                <div class="text-muted small">مجموع المصروفات</div>
                                <div class="h5 fw-bold">
                                    <?= number_format($total, 2) ?> <?= $cur ?>
                                </div>
                                <a href="expenses.php" class="btn btn-sm btn-primary mt-2">عرض التفاصيل</a>
                            </div>
                        </div>
                        <!-- رصيد الصندوق -->
                        <div class="col-md-3">
                            <div class="p-3 border rounded-3 shadow-sm bg-light">
                                <div class="text-muted small">رصيد الصندوق (القطة − مصروفات الصندوق)</div>
                                <div class="h5 fw-bold <?= $kitty['balance'] < 0 ? 'text-danger' : 'text-success' ?>">
                                    <?= number_format($kitty['balance'], 2) ?> <?= $cur ?>
                                </div>
                                <div class="text-muted small">صُرف من الصندوق: <?= number_format($kitty['spent'], 2) ?> <?= $cur ?></div>
                            </div>
                        </div>
                        <!-- مجموع العجز -->
                        <div class="col-md-3">
                            <div class="p-3 border rounded-3 shadow-sm bg-light">
                                <div class="text-muted small">مجموع العجز</div>
                                <div class="h5 fw-bold text-danger">
                                    <?= number_format(sumDeficits($balances), 2) ?> <?= $cur ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- المصروفات حسب الفئة -->
        <section class="dashboard-charts mb-4">
            <div class="row g-3">
                <div class="col-md-8">
                    <div class="card h-100">
                        <div class="card-header expenses">
                            <i class="fas fa-tags me-2"></i>المصروفات حسب الفئة
                        </div>
                        <div class="card-body">
                            <?php if (!empty($expensesByCategory)): ?>
                                <div class="row g-3">
                                    <?php foreach ($expensesByCategory as $category => $amount): ?>
                                        <div class="col-md-6 col-lg-4 col-6">
                                            <div class="p-3 border rounded-3 shadow-sm text-center bg-light">
                                                <div class="text-muted small"><?= htmlspecialchars($category) ?></div>
                                                <div class="h5 fw-bold">
                                                    <?= number_format($amount, 2) ?> <?= $cur ?>
                                                </div>
                                                <div class="text-muted small">
                                                    ≈ <?= number_format($amount * $exchange_rate, 2) ?>
                                                    <img src="https://alghathbar.com/trip/icons/Saudi_Riyal_Symbol.svg" width="12" alt="ريال سعودي">
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">لا توجد مصروفات مسجلة بعد</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!-- الرسم البياني الدائري -->
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header bg-teal text-white">
                            <i class="fas fa-chart-pie me-2"></i>التوزيع النسبي
                        </div>
                        <div class="card-body">
                            <canvas id="expensesChart" style="max-height: 300px"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- سجل المشاركين -->
        <section class="dashboard-participants mb-4">
            <div class="card">
                <div class="card-header participants">
                    <i class="fas fa-users me-2"></i>سجل المشاركين
                </div>
                <div class="card-body">
                    <?php if (!empty($team)): ?>
                        <div class="grid-participants">
                            <?php foreach ($team as $index => $participant): ?>
                                <div class="participant-card">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($participant) ?></div>
                                        <div class="text-muted small">#<?= $index + 1 ?> – <?= number_format($balances[$participant]['net'] ?? 0, 2) ?> <?= $cur ?></div>
                                    </div>
                                    <?php $st = $balances[$participant]['status'] ?? STATUS_BALANCED; ?>
                                    <span class="badge <?= $statusBadge[$st] ?? 'bg-secondary' ?>"><?= h($st) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">لا يوجد مشاركين مسجلين</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- تصنيف المصروفات -->
        <section class="dashboard-categories mb-4">
            <div class="card">
                <div class="card-header bg-purple text-white">
                    <i class="fas fa-sitemap me-2"></i>تصنيف المصروفات
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- المصروفات المشتركة -->
                        <div class="col-md-4">
                            <div class="p-3 border rounded-3 shadow-sm text-center bg-light">
                                <div class="text-muted small">المصروفات المشتركة</div>
                                <div class="h4 fw-bold text-primary">
                                    <?= number_format($total_shared, 2) ?> <?= $cur ?>
                                </div>
                                <div class="small text-muted">
                                    ≈ <?= number_format($total_shared * $exchange_rate, 2) ?> 
                                    <img src="https://alghathbar.com/trip/icons/Saudi_Riyal_Symbol.svg" width="14">
                                </div>
                                <div class="mt-2 small">
                                    <i class="fas fa-users me-1"></i>
                                    <?= (int) $shared_count ?> عملية
                                </div>
                            </div>
                        </div>
                        <!-- المصروفات الفردية -->
                        <?php foreach ($individual_expenses as $member => $info): ?>
                        <div class="col-md-4">
                            <div class="p-3 border rounded-3 shadow-sm text-center bg-light">
                                <div class="text-muted small">مصروفات <?= htmlspecialchars($member) ?> المنفردة</div>
                                <div class="h4 fw-bold text-success">
                                    <?= number_format($info['total'], 2) ?> <?= $cur ?>
                                </div>
                                <div class="small text-muted">
                                    ≈ <?= number_format($info['total'] * $exchange_rate, 2) ?> 
                                    <img src="https://alghathbar.com/trip/icons/Saudi_Riyal_Symbol.svg" width="14">
                                </div>
                                <div class="mt-2 small">
                                    <i class="fas fa-user-tag me-1"></i>
                                    <?= (int) $info['count'] ?> عملية
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- التحويلات المطلوبة -->
        <section class="dashboard-settlements mb-4">
            <div class="card border-0 shadow">
                <div class="card-header bg-gradient bg-primary text-white fw-bold d-flex align-items-center">
                    <i class="fas fa-exchange-alt me-2"></i>
                    التحويلات المطلوبة
                </div>
                <div class="card-body">
                    <?php if (!empty($transactions)): ?>
                        <div class="row g-4">
                            <?php foreach ($transactions as $t): ?>
                            <div class="col-md-6">
                                <div class="border rounded-4 p-3 position-relative bg-light">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="badge bg-danger text-white">
                                            <i class="fas fa-arrow-up me-1"></i> من: <?= htmlspecialchars($t['from']) ?>
                                        </span>
                                        <span class="badge bg-success text-white">
                                            <i class="fas fa-arrow-down me-1"></i> إلى: <?= htmlspecialchars($t['to']) ?>
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fs-5 fw-bold text-dark"><?= number_format($t['amount'], 2) ?> <?= $cur ?></div>
                                            <div class="text-muted small">
                                                ≈ <?= number_format($t['amount'] * $exchange_rate, 2) ?>
                                                <img src="https://alghathbar.com/trip/icons/Saudi_Riyal_Symbol.svg" width="14" alt="ريال سعودي">
                                            </div>
                                        </div>
                                        <div class="text-primary">
                                            <i class="fas fa-hand-holding-usd fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif (empty($settlement['kitty_refunds']) && empty($settlement['kitty_dues'])): ?>
                        <div class="alert alert-info d-flex align-items-center">
                            <i class="fas fa-balance-scale me-2"></i>
                            جميع الحسابات متوازنة
                        </div>
                    <?php endif; ?>

                    <?php if ($kitty['balance'] > 0): ?>
                        <div class="alert alert-success mt-4 mb-0">
                            <i class="fas fa-piggy-bank me-2"></i>
                            متبقٍ في <?= h(KITTY) ?>: <strong><?= number_format($kitty['balance'], 2) ?> <?= $cur ?></strong>
                            — يُعاد للمساهمين (خارج التحويلات بين الأعضاء):
                            <?php foreach ($settlement['kitty_refunds'] as $name => $amt): ?>
                                <span class="badge bg-success ms-1"><?= h($name) ?>: <?= number_format($amt, 2) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($kitty['balance'] < 0): ?>
                        <div class="alert alert-warning mt-4 mb-0">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            عجز في <?= h(KITTY) ?>: <strong><?= number_format(abs($kitty['balance']), 2) ?> <?= $cur ?></strong>
                            — يغطيه الأعضاء بإضافة قطة (خارج التحويلات بين الأعضاء):
                            <?php foreach ($settlement['kitty_dues'] as $name => $amt): ?>
                                <span class="badge bg-danger ms-1"><?= h($name) ?>: <?= number_format($amt, 2) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- الخيارات الإضافية -->
        <section class="dashboard-actions mb-5">
            <div class="row g-4">
                <!-- تصدير PDF -->
                <div class="col-md-4">
                    <div class="card shadow-sm h-100 border-0" style="transition: 0.3s;">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div class="text-center mb-3">
                                <i class="fas fa-file-export fa-3x text-primary"></i>
                            </div>
                            <h5 class="card-title text-center">تصدير PDF</h5>
                            <p class="card-text text-center text-muted small">حفظ تقرير الرحلة كملف PDF مرتب ومنسق</p>
                            <a href="export.php" class="btn btn-primary w-100 mt-3">تصدير التقرير 📄</a>
                        </div>
                    </div>
                </div>
                <!-- بطاقة الرسم البياني -->
                <div class="col-md-4">
                    <div class="card shadow-sm h-100 border-0" style="transition: 0.3s;">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div class="text-center mb-3">
                                <i class="fas fa-chart-pie fa-3x text-warning"></i>
                            </div>
                            <h5 class="card-title text-center">الرسم البياني</h5>
                            <p class="card-text text-center text-muted small">مقارنة بين القطة والمصروفات لكل عضو في الرحلة</p>
                            <a href="Chart.php" class="btn btn-warning w-100 mt-3 text-white">عرض الرسم 📊</a>
                        </div>
                    </div>
                </div>
                <!-- المصروف اليومي -->
                <div class="col-md-4">
                    <div class="card shadow-sm h-100 border-0" style="transition: 0.3s;">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div class="text-center mb-3">
                                <i class="fas fa-calendar-day fa-3x text-success"></i>
                            </div>
                            <h5 class="card-title text-center">المصروف اليومي</h5>
                            <p class="card-text text-center text-muted small">عرض تفصيلي للمصروفات حسب التاريخ واليوم</p>
                            <a href="daily_expenses.php" class="btn btn-success w-100 mt-3">عرض التفاصيل 📅</a>
                        </div>
                    </div>
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
            <small class="text-muted" style="color: #ddd !important;">
                الإصدار 2.0.2 - آخر تحديث: 27 ذو القعدة ١٤٤٦ هـ
            </small>
        </div>
    </footer>

    <!-- المكتبات البرمجية -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
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
                document.getElementById('darkModeSwitch').checked = true;
            }
        }

        // رسم مخطط المصروفات حسب الفئة
        const ctx = document.getElementById('expensesChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: <?= js(array_keys($expensesByCategory)) ?>,
                    datasets: [{
                        data: <?= js(array_values($expensesByCategory)) ?>,
                        backgroundColor: [
                            '#2A5C8D', '#F2A950', '#4CAF50', 
                            '#FFC107', '#E74C3C', '#6f42c1'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${context.formattedValue} ` + <?= js($currency) ?>;
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>