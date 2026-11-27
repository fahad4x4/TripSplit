<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/ui.php';

$data = loadData();
$daily_expenses = dailyExpensesWithDetails($data['expenses']);
$grand = array_sum(array_map(function ($d) { return (float) $d['total']; }, $daily_expenses));
$maxDay = $daily_expenses ? max(array_map(function ($d) { return (float) $d['total']; }, $daily_expenses)) : 0;

$actions = '<a class="btn" href="daily-pdf.php" target="_blank" rel="noopener">' . icon('pdf') . 'تصدير PDF</a>';
pageStart('المصروف اليومي', 'daily_expenses.php');
pageTitle('المصروف اليومي', count($daily_expenses) . ' أيام · المجموع ' . number_format($grand, 2) . ' ' . appConfig()['currency'], $actions);
?>
<?php if (empty($daily_expenses)): ?>
    <div class="card"><?= emptyState('لا توجد مصروفات بعد', 'يظهر هنا مجموع كل يوم مقسماً حسب نوع المصروف.', 'manage.php#expense', 'تسجيل مصروف', 'calendar') ?></div>
<?php else: ?>
    <section class="card" aria-labelledby="days-overview">
        <div class="card-head"><h2 id="days-overview"><?= icon('chart') ?>نظرة على الأيام</h2></div>
        <ul class="bars">
            <?php foreach ($daily_expenses as $date => $day): $pct = $maxDay > 0 ? $day['total'] / $maxDay * 100 : 0; ?>
            <li class="bar-row">
                <div class="bar-top"><a href="#d-<?= h($date) ?>"><bdi dir="ltr"><?= h(safeDate($date, 'd/m/Y')) ?></bdi></a><span><?= money($day['total'], false) ?></span></div>
                <div class="bar"><span style="width: <?= h(number_format($pct, 1, '.', '')) ?>%"></span></div>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <?php foreach ($daily_expenses as $date => $day): ?>
    <section class="day-group section" id="d-<?= h($date) ?>">
        <div class="day-head">
            <h2><?= icon('calendar') ?> <bdi dir="ltr"><?= h(safeDate($date, 'd/m/Y')) ?></bdi></h2>
            <span class="total"><?= money($day['total']) ?></span>
        </div>
        <ul class="rows">
            <?php foreach ($day['details'] as $type => $amount): ?>
            <li class="row">
                <div class="row-main"><?= categoryChip((string) $type) ?></div>
                <div class="row-amt"><?= money((float) $amount) ?><?= sar((float) $amount) ?></div>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endforeach; ?>
<?php endif; ?>
<?php pageEnd(); ?>
