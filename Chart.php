<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/ui.php';

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
$dateTotals = dailyExpenses($expenses);
$maxDay = $dateTotals ? max($dateTotals) : 0;

$categories = [];
foreach ($expenses as $exp) {
    $cat = (string) ($exp['note'] ?? 'اخرى');
    $categories[$cat]['amount'] = ($categories[$cat]['amount'] ?? 0) + (float) ($exp['amount'] ?? 0);
    $categories[$cat]['count'] = ($categories[$cat]['count'] ?? 0) + 1;
}
uasort($categories, function ($a, $b) { return $b['amount'] <=> $a['amount']; });

$actions = '<button type="button" class="btn" data-print>' . icon('printer') . 'طباعة / حفظ PDF</button>'
         . '<a class="btn" href="export.php">' . icon('pdf') . 'التقرير الكامل</a>';
pageStart('الإحصائيات', 'Chart.php');
pageTitle('الإحصائيات المالية', 'تحليل المساهمات والمصروفات والتسويات', $actions);
?>
<section class="stats" aria-label="الأرقام الرئيسية">
    <div class="stat"><div class="label">مجموع القطة</div><div class="value"><?= money($total_contributions) ?></div><div class="hint"><?= sar($total_contributions) ?></div></div>
    <div class="stat"><div class="label">مجموع المصروفات</div><div class="value"><?= money($total_expenses) ?></div><div class="hint"><?= sar($total_expenses) ?></div></div>
    <div class="stat"><div class="label">رصيد <?= h(KITTY) ?></div><div class="value <?= $kitty['balance'] < 0 ? 'neg' : 'pos' ?>"><?= money($kitty['balance']) ?></div><div class="hint"><?= sar($kitty['balance']) ?></div></div>
    <div class="stat"><div class="label">عدد الأعضاء</div><div class="value"><?= (int) count($team) ?></div><div class="hint"><?= (int) count($expenses) ?> مصروف</div></div>
</section>

<section class="card section" aria-labelledby="t-members">
    <div class="card-head"><h2 id="t-members"><?= icon('users') ?>الأعضاء</h2></div>
    <?php if (empty($balances)): ?>
        <?= emptyState('لا يوجد أعضاء', '', 'manage.php#member', 'إضافة الأعضاء', 'users') ?>
    <?php else: ?>
    <div class="table-wrap">
        <table class="data stack">
            <thead><tr><th>العضو</th><th class="n">القطة</th><th class="n">دفع شخصياً</th><th class="n">الحصة</th><th class="n">الرصيد</th><th>الحالة</th></tr></thead>
            <tbody>
            <?php foreach ($balances as $name => $m): ?>
                <tr>
                    <td data-label="العضو"><b><?= h($name) ?></b></td>
                    <td class="n" data-label="القطة"><?= fmtNum($m['contributed']) ?></td>
                    <td class="n" data-label="دفع شخصياً"><?= fmtNum($m['paid']) ?></td>
                    <td class="n" data-label="الحصة"><?= fmtNum($m['share']) ?></td>
                    <td class="n <?= $m['net'] > 0 ? 'pos' : ($m['net'] < 0 ? 'neg' : '') ?>" data-label="الرصيد"><b><?= fmtNum($m['net']) ?></b></td>
                    <td data-label="الحالة"><?= statusBadge($m['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>

<div class="grid grid-2 section">
    <section class="card" aria-labelledby="t-cats">
        <div class="card-head"><h2 id="t-cats"><?= icon('chart') ?>حسب الفئة</h2></div>
        <?php if (empty($categories)): ?>
            <?= emptyState('لا توجد مصروفات') ?>
        <?php else: ?>
        <ul class="bars">
            <?php foreach ($categories as $cat => $c): $pct = $total_expenses > 0 ? $c['amount'] / $total_expenses * 100 : 0; ?>
            <li class="bar-row">
                <div class="bar-top"><span><?= categoryChip((string) $cat) ?> <span class="pct"><?= (int) $c['count'] ?> عملية</span></span><span><?= money($c['amount'], false) ?> <span class="pct"><?= fmtNum($pct, 0, '%') ?></span></span></div>
                <div class="bar"><span style="width: <?= h(number_format($pct, 1, '.', '')) ?>%"></span></div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </section>

    <section class="card" aria-labelledby="t-time">
        <div class="card-head"><h2 id="t-time"><?= icon('calendar') ?>حسب اليوم</h2></div>
        <?php if (empty($dateTotals)): ?>
            <?= emptyState('لا توجد مصروفات', '', '', '', 'calendar') ?>
        <?php else: ?>
        <ul class="bars">
            <?php foreach ($dateTotals as $d => $amt): $pct = $maxDay > 0 ? $amt / $maxDay * 100 : 0; ?>
            <li class="bar-row">
                <div class="bar-top"><bdi dir="ltr"><?= h(safeDate($d, 'd/m/Y')) ?></bdi><span><?= money($amt, false) ?></span></div>
                <div class="bar"><span style="width: <?= h(number_format($pct, 1, '.', '')) ?>%"></span></div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </section>
</div>

<section class="card section" aria-labelledby="t-settle">
    <div class="card-head"><h2 id="t-settle"><?= icon('arrow') ?>التسويات</h2></div>
    <?php if (!empty($transactions)): ?>
        <ul class="settle-list">
            <?php foreach ($transactions as $t): ?>
            <li class="transfer">
                <div class="who"><small>يدفع</small><b><?= h($t['from']) ?></b></div>
                <div class="mid"><span class="amt"><?= fmtNum($t['amount']) ?></span><?= icon('arrow') ?><?= sar($t['amount']) ?></div>
                <div class="who to"><small>إلى</small><b><?= h($t['to']) ?></b></div>
            </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="pos">لا توجد تحويلات مطلوبة بين الأعضاء.</p>
    <?php endif; ?>
    <?php if ($kitty['balance'] > 0 && !empty($settlement['kitty_refunds'])): ?>
        <div class="kitty-note ok">
            <h3><?= icon('piggy') ?>المتبقي في <?= h(KITTY) ?>: <?= money($kitty['balance']) ?> — يُعاد للمساهمين</h3>
            <div class="pill-row"><?php foreach ($settlement['kitty_refunds'] as $n => $amt): ?><span class="pill"><b><?= h($n) ?></b> <?= fmtNum($amt) ?></span><?php endforeach; ?></div>
        </div>
    <?php elseif ($kitty['balance'] < 0 && !empty($settlement['kitty_dues'])): ?>
        <div class="kitty-note bad">
            <h3><?= icon('alert') ?>عجز في <?= h(KITTY) ?>: <?= money(abs($kitty['balance'])) ?></h3>
            <div class="pill-row"><?php foreach ($settlement['kitty_dues'] as $n => $amt): ?><span class="pill"><b><?= h($n) ?></b> <?= fmtNum($amt) ?></span><?php endforeach; ?></div>
        </div>
    <?php endif; ?>
</section>
<?php pageEnd(); ?>
