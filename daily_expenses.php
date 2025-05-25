<?php
// تحميل الإعدادات والبيانات
$config = require 'config.php';
$data = json_decode(file_get_contents($config['data_file']), true);

$expenses = $data['expenses'] ?? [];

// دالة حساب المصروفات اليومية مع التفصيل
function dailyExpensesWithDetails(array $expenses): array {
    $daily = [];
    foreach ($expenses as $exp) {
        $date = date('Y-m-d', strtotime($exp['date']));
        $type = $exp['note'];
        $amount = $exp['amount'];
        if (!isset($daily[$date])) {
            $daily[$date] = [
                'total' => 0,
                'details' => []
            ];
        }
        // تحديث المجموع الكلي
        $daily[$date]['total'] += $amount;
        // تحديث التفاصيل حسب النوع
        if (!isset($daily[$date]['details'][$type])) {
            $daily[$date]['details'][$type] = 0;
        }
        $daily[$date]['details'][$type] += $amount;
    }
    ksort($daily); // ترتيب حسب التاريخ
    return $daily;
}

$daily_expenses = dailyExpensesWithDetails($expenses);

// تحويل القيمة إلى ريال سعودي
function toSAR(float $amount, float $rate): string {
    return number_format($amount * $rate, 2);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقرير المالي اليومي - إدارة مصروفات الرحلة</title>
    <!-- Bootstrap 5 RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Google Fonts - Tajawal -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <!-- Line Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css">
    <!-- Animate CSS (اختياري) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- ملف التنسيقات الرئيسي -->
    <link href="styles.css" rel="stylesheet">
    <style>
        /* لمسة خفيفة للكارت */
        .expense-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.07);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .expense-card .card-header {
            background: var(--teal-color, #20c997);
            color: #fff;
            border-radius: 0;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: .7rem;
            border-bottom: 2px solid var(--accent-color, #F2A950);
        }
        .day-section .bg-light {
            background: var(--light-bg, #f8f9fa) !important;
        }
        .btn-sar {
            background: var(--accent-color, #F2A950);
            color: #2C3E50;
            border: none;
            padding: .7em 2.3em;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: bold;
            box-shadow: 0 2px 10px #0001;
            transition: background 0.2s;
        }
        .btn-sar:hover {
            background: #e7bb57;
        }
        /* تكامل مع الوضع الليلي */
        body.dark-mode .expense-card { background: #232323; }
        body.dark-mode .expense-card .card-header { background: #18806d; }
        body.dark-mode .day-section .bg-light { background: #2a2a2a !important; }
        body.dark-mode .btn-sar { background: #bb9a3e; color: #fff; }
        /* Responsive */
        @media (max-width: 600px) {
            .expense-card .card-header { flex-direction: column; gap: 0.4rem; }
        }
    </style>
</head>
<body>
    <!-- الهيدر -->
    <header class="main-header bg-teal text-white py-4 mb-4 animate__animated animate__fadeInDown">
        <div class="container text-center">
            <div class="logo-container d-inline-block mb-2">
                <img src="https://cdn-icons-png.flaticon.com/512/2332/2332689.png" alt="الشعار" width="70">
            </div>
            <h1 class="mb-2 display-6 fw-bold text-gradient">
                <i class="las la-coins"></i> إدارة مصروفات الرحلة
            </h1>
            <!-- زر الوضع الليلي -->
            <button class="btn btn-outline-light btn-sm toggle-dark-mode">
                <i class="las la-moon"></i> تبديل المظهر
            </button>
        </div>
    </header>

    <!-- المحتوى الرئيسي -->
    <main class="container py-3">
        <!-- بطاقة التقارير -->
        <div class="expense-card">
            <div class="card-header">
                <i class="las la-file-invoice"></i>
                <h3 class="mb-0 fs-5 fw-bold">التقرير المالي التفصيلي اليومي</h3>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($daily_expenses)): ?>
                    <?php foreach ($daily_expenses as $date => $data): ?>
                        <div class="day-section mb-4">
                            <!-- تاريخ اليوم -->
                            <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded-2">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="las la-calendar-day text-primary"></i>
                                    <span class="fw-bold"><?= $date ?></span>
                                </div>
                                <div class="total-amount">
                                    <span class="badge bg-primary fs-6">
                                        <?= number_format($data['total'], 2) ?> <?= $config['currency'] ?>
                                    </span>
                                    <small class="text-muted ms-2">
                                        ≈ <?= toSAR($data['total'], $config['exchange_rate_to_sar']) ?> SAR
                                    </small>
                                </div>
                            </div>
                            <!-- جدول التفاصيل -->
                            <div class="table-responsive">
                                <table class="table expense-table">
                                    <thead>
                                        <tr>
                                            <th><i class="las la-tag"></i> النوع</th>
                                            <th><i class="las la-coins"></i> المبلغ</th>
                                            <th><i class="las la-exchange-alt"></i> التحويل</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data['details'] as $type => $amount): ?>
                                            <tr>
                                                <td>
                                                    <i class="las la-caret-left text-muted"></i>
                                                    <?= htmlspecialchars($type) ?>
                                                </td>
                                                <td>
                                                    <?= number_format($amount, 2) ?> 
                                                    <?= $config['currency'] ?>
                                                </td>
                                                <td class="text-success">
                                                    <?= toSAR($amount, $config['exchange_rate_to_sar']) ?> SAR
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="las la-wallet fs-1 text-muted mb-3"></i>
                        <p class="text-muted">لا توجد مصروفات مسجلة بعد</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
       <!-- أزرار التحكم -->
<div class="text-center mt-4">
    <a href="daily-pdf.php" class="btn btn-sar" target="_blank" rel="noopener">
        <i class="las la-file-pdf"></i>
        تصدير PDF
    </a>
</div>
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
    <!-- الوضع الليلي -->
    <script>
        document.querySelector('.toggle-dark-mode').addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            this.innerHTML = document.body.classList.contains('dark-mode') ? 
                '<i class="las la-sun"></i> الوضع النهاري' : 
                '<i class="las la-moon"></i> الوضع الليلي';
        });
    </script>
</body>
</html>