<?php
require __DIR__ . '/auth.php';

$config = appConfig();
$currency = $config['currency'];
$exchange_rate = (float) $config['exchange_rate_to_sar'];
$cur = h($currency);

$data = loadData();
$contributions = $data['contributions'];
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل المساهمات - إدارة مصروفات الرحلة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
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
                    <i class="fas fa-hand-holding-usd me-2"></i>
                    سجل المساهمات
                </h1>
                <a href="index.php" class="text-decoration-none" style="color: var(--accent-color);">
                    <i class="fas fa-home me-2"></i> العودة للصفحة الرئيسية
                </a>
            </div>
        </div>
    </header>

    <main class="container mt-4 mb-5">
        <div class="card">
            <div class="card-header bg-teal contributions text-white">
                <i class="fas fa-hand-holding-usd me-2"></i>سجل دفعات القطة
                <span class="badge bg-light text-dark float-start"><?= (int) count($contributions) ?> مساهمات</span>
            </div>
            <div class="card-body">
                <?php if (!empty($contributions)): ?>
                    <div class="row g-3">
                        <?php foreach ($contributions as $contrib): ?>
                        <div class="col-12 col-md-6">
                            <div class="p-3 border rounded-3 shadow-sm d-flex flex-column gap-2 bg-light" style="background-color: #fef6fd;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="fw-bold"><?= h($contrib['name'] ?? '') ?></div>
                                    <div class="text-muted small"><?= h(safeDate($contrib['date'] ?? '')) ?></div>
                                </div>
                                <div class="d-flex justify-content-between align-items-end">
                                    <div>
                                        <span class="fw-bold"><?= number_format((float) $contrib['amount'], 2) ?> <?= $cur ?></span>
                                        <div class="text-muted small">
                                            ≈ <?= number_format((float) $contrib['amount'] * $exchange_rate, 2) ?>
                                            <img src="https://alghathbar.com/trip/icons/Saudi_Riyal_Symbol.svg" alt="ريال سعودي" width="14">
                                        </div>
                                    </div>
                                    <i class="fas fa-hand-holding-usd text-primary fa-lg"></i>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">لا توجد مساهمات مسجلة</div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="text-center py-4 mt-5">
        <div class="container">
            <p class="mb-0">
                تم تطوير النظام بواسطة 
                <i class="fas fa-code"></i> أبو فيصل
                © <?= date('Y') ?>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>