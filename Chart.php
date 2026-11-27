<?php
require __DIR__ . '/auth.php';

$config = appConfig();
$currency = $config['currency'];
$exchange_rate = (float) $config['exchange_rate_to_sar'];
$cur = h($currency);

$data = loadData();
$team = $data['team'];
$expenses = $data['expenses'];
$contributions = $data['contributions'];

// الحسابات من functions.php (المصدر الوحيد)
$balances = calculateBalances($team, $expenses, $contributions);
$settlement = computeSettlement($balances);
$transactions = $settlement['transactions'];
$kitty = kittyBalance($expenses, $contributions);

$total_contributions = totalContributions($contributions);
$total_expenses = totalExpenses($expenses);

// تحويل القيمة إلى ريال سعودي
function toSAR(float $amount, float $rate): string {
    return number_format($amount * $rate, 2);
}

// رمز الريال السعودي
function sarIcon(): string {
    return '<img src="https://alghathbar.com/trip/icons/Saudi_Riyal_Symbol.svg" width="14" alt="SAR">';
}

// إجمالي المصروفات اليومية للتايملاين
$dateTotals = dailyExpenses($expenses);

// توزيع المصروفات حسب الفئة للفطيرة
$categoryCounts = [];
foreach ($expenses as $exp) {
    $cat = (string) ($exp['note'] ?? 'اخرى');
    $categoryCounts[$cat] = ($categoryCounts[$cat] ?? 0) + 1;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإحصائيات المالية</title>

    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- HTML2PDF for Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>
    <link href="styles.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="icons/icon-192x192.png">
</head>
<body>
<header class="header">
    <div class="container text-center">
        <div class="d-flex flex-column align-items-center gap-3">
            <div class="logo-container">
                <a href="index.php" class="text-decoration-none">
                    <img src="icons/icon-192x192.png" alt="شعار الرحلة" class="img-fluid">
                </a>
            </div>
            <h1 class="display-6 fw-bold text-gradient">
                <i class="bi bi-currency-exchange me-2"></i>
                إدارة مصروفات الرحلة
            </h1>
            <p class="text-muted mb-0">مشاركة عادلة، إدارة ذكية</p>
            <a href="manage.php" class="text-decoration-none" style="color: var(--accent-color);">
                <i class="bi bi-gear"></i> صفحة الإدارة
            </a>
        </div>
        <div class="form-check form-switch mt-3">
            <input class="form-check-input" type="checkbox" id="darkModeSwitch">
            <label class="form-check-label" for="darkModeSwitch">الوضع الليلي</label>
        </div>
    </div>
</header>
<div class="container py-5" id="content-to-export">
    <div class="text-center mb-5">
        <h1 class="fw-bold"><i class="bi bi-graph-up-arrow"></i> لوحة الإحصائيات المالية</h1>
        <p class="text-muted">عرض وتحليل المساهمات والمصروفات</p>
    </div>
    <!-- إحصائيات سريعة -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-cash-stack fs-2"></i>
                </div>
                <div class="card-body">
                    <div class="text-muted small">مجموع القطة</div>
                    <h3 class="mb-0"><?= number_format($total_contributions, 2) ?> <?= $cur ?></h3>
                    <div class="text-muted"><?= toSAR($total_contributions, $exchange_rate) ?> <?= sarIcon() ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-header bg-danger text-white">
                    <i class="bi bi-receipt fs-2"></i>
                </div>
                <div class="card-body">
                    <div class="text-muted small">مجموع المصروفات</div>
                    <h3 class="mb-0"><?= number_format($total_expenses, 2) ?> <?= $cur ?></h3>
                    <div class="text-muted"><?= toSAR($total_expenses, $exchange_rate) ?> <?= sarIcon() ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-calculator fs-2"></i>
                </div>
                <div class="card-body">
                    <div class="text-muted small">رصيد <?= h(KITTY) ?></div>
                    <h3 class="mb-0"><?= number_format($kitty['balance'], 2) ?> <?= $cur ?></h3>
                    <div class="text-muted"><?= toSAR($kitty['balance'], $exchange_rate) ?> <?= sarIcon() ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-people-fill fs-2"></i>
                </div>
                <div class="card-body">
                    <h3 class="mb-0"><?= (int) count($team) ?></h3>
                    <div class="text-muted">عدد الأعضاء</div>
                </div>
            </div>
        </div>
    </div>
    <!-- التبويبات -->
    <ul class="nav nav-tabs mb-3" id="statTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="members-tab" data-bs-toggle="tab" data-bs-target="#members" type="button" role="tab">الأفراد</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab">الفئات</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="timeline-tab" data-bs-toggle="tab" data-bs-target="#timeline" type="button" role="tab">الزمن</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="settlements-tab" data-bs-toggle="tab" data-bs-target="#settlements" type="button" role="tab">التسويات</button>
        </li>
    </ul>
    <!-- محتوى التبويبات -->
    <div class="tab-content" id="statTabsContent">
        <!-- الأفراد -->
        <div class="tab-pane fade show active" id="members" role="tabpanel">
            <div class="card p-4">
                <table class="table table-bordered">
                    <thead><tr><th>العضو</th><th>المساهمة في القطة</th><th>دفع شخصياً</th><th>الحصة</th><th>الرصيد</th><th>الحالة</th></tr></thead>
                    <tbody>
                    <?php foreach ($team as $name):
                        $member = $balances[$name] ?? ['contributed' => 0, 'paid' => 0, 'share' => 0, 'net' => 0, 'status' => STATUS_BALANCED];
                        $color = $member['net'] >= 0 ? 'text-success' : 'text-danger';
                        $sar = toSAR($member['net'], $exchange_rate);
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($name) ?></td>
                            <td><?= number_format($member['contributed'], 2) ?></td>
                            <td><?= number_format($member['paid'], 2) ?></td>
                            <td><?= number_format($member['share'], 2) ?></td>
                            <td class="fw-bold <?= $color ?>">
                                <?= number_format($member['net'], 2) ?>
                                <br><small class="sar-equivalent"><?= $sar ?> <?= sarIcon() ?></small>
                            </td>
                            <td><?= h($member['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- الفئات -->
        <div class="tab-pane fade" id="categories" role="tabpanel">
            <div class="card p-4">
                <canvas id="categoryChart" height="100"></canvas>
            </div>
        </div>
        <!-- الزمن -->
        <div class="tab-pane fade" id="timeline" role="tabpanel">
            <div class="card p-4">
                <canvas id="timelineChart" height="100"></canvas>
            </div>
        </div>
        <!-- التسويات -->
        <div class="tab-pane fade" id="settlements" role="tabpanel">
            <div class="card p-4">

                <?php if (!empty($transactions)): ?>
                    <ul class="list-group">
                        <?php foreach ($transactions as $t):
                            $sar_amount = toSAR($t['amount'], $exchange_rate);
                        ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span><?= htmlspecialchars($t['from']) ?> <i class="bi bi-arrow-left-right"></i> <?= htmlspecialchars($t['to']) ?></span>
                            <span class="badge rounded-pill bg-primary">
                                <?= number_format($t['amount'], 2) ?> <?= $cur ?> <br>
                                <small><?= $sar_amount ?> <?= sarIcon() ?></small>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-success">لا توجد تحويلات مطلوبة بين الأعضاء.</p>
                <?php endif; ?>
                <?php if ($kitty['balance'] > 0): ?>
                    <p class="mt-3 mb-0 text-success">متبقٍ في <?= h(KITTY) ?>: <?= number_format($kitty['balance'], 2) ?> <?= $cur ?> يُعاد للمساهمين
                        <?php foreach ($settlement['kitty_refunds'] as $n => $amt): ?>(<?= h($n) ?>: <?= number_format($amt, 2) ?>) <?php endforeach; ?></p>
                <?php elseif ($kitty['balance'] < 0): ?>
                    <p class="mt-3 mb-0 text-danger">عجز في <?= h(KITTY) ?>: <?= number_format(abs($kitty['balance']), 2) ?> <?= $cur ?>
                        <?php foreach ($settlement['kitty_dues'] as $n => $amt): ?>(<?= h($n) ?>: <?= number_format($amt, 2) ?>) <?php endforeach; ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- زر تصدير إلى PDF -->
    <div class="text-center mt-4">
        <button onclick="exportToPDF()" class="btn btn-success">
            <i class="bi bi-download"></i> تصدير إلى PDF
        </button>
    </div>
</div>
<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- رسم بياني للفئات -->
<script>
    const categoryCtx = document.getElementById('categoryChart');
    new Chart(categoryCtx, {
        type: 'pie',
        data: {
            labels: <?= js(array_keys($categoryCounts)) ?>,
            datasets: [{
                label: 'المصروفات حسب الفئة',
                data: <?= js(array_values($categoryCounts)) ?>,
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF','#F2A950','#20c997','#E74C3C','#6f42c1','#fd7e14','#d63384','#0dcaf0']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'right' },
                title: { display: true, text: 'توزيع المصروفات حسب الفئة' }
            }
        }
    });
</script>
<!-- رسم بياني زمني -->
<script>
document.getElementById('timeline-tab').addEventListener('shown.bs.tab', function () {
    const timelineCtx = document.getElementById('timelineChart');
    new Chart(timelineCtx, {
        type: 'line',
        data: {
            labels: <?= js(array_keys($dateTotals)) ?>,
            datasets: [{
                label: 'المصروفات اليومية',
                data: <?= js(array_values($dateTotals)) ?>,
                borderColor: '#36A2EB',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: { display: true, text: 'المصروفات بمرور الوقت' }
            }
        }
    });
});
</script>
<!-- تصدير إلى PDF -->
<script>
    function exportToPDF() {
        const element = document.getElementById('content-to-export');
        const opt = {
            margin:       0.5,
            filename:     <?= js('تقرير_الاحصائيات_' . date('Y-m-d') . '.pdf') ?>,
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    }
</script>
<script>
    // التحكم في الوضع الليلي
    const darkModeSwitch = document.getElementById('darkModeSwitch');
    if (localStorage.getItem('darkMode') === 'enabled') {
        document.documentElement.setAttribute('data-theme', 'dark');
        darkModeSwitch.checked = true;
    }
    darkModeSwitch.addEventListener('change', function() {
        if (this.checked) {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('darkMode', 'enabled');
        } else {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('darkMode', 'disabled');
        }
    });
    darkModeSwitch.addEventListener('change', function() {
        setTimeout(() => {
            const event = new Event('themeChanged');
            window.dispatchEvent(event);
        }, 300);
    });
</script>
</body>
</html>