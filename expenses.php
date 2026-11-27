<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/ui.php';

$data = loadData();
$team = $data['team'];
$expenses = $data['expenses'];

// تجميع حسب اليوم، الأحدث أولاً (مع الحفاظ على ترتيب الإدخال داخل اليوم)
$byDay = [];
foreach (array_reverse($expenses) as $exp) {
    $day = safeDate($exp['date'] ?? '');
    $byDay[$day][] = $exp;
}
krsort($byDay);

$addBtn = '<a class="btn btn-primary" href="manage.php#expense">' . icon('plus') . 'إضافة مصروف</a>';
pageStart('سجل المصروفات', 'expenses.php');
pageTitle('سجل المصروفات', count($expenses) . ' عملية · المجموع ' . number_format(totalExpenses($expenses), 2) . ' ' . appConfig()['currency'], $addBtn);
?>
<?php if (empty($expenses)): ?>
    <div class="card"><?= emptyState('لا توجد مصروفات مسجلة', 'كل مصروف تسجله يظهر هنا مع من دفعه ومن شارك فيه.', 'manage.php#expense', 'تسجيل أول مصروف') ?></div>
<?php else: ?>
    <?php foreach ($byDay as $day => $items): $dayTotal = totalExpenses($items); ?>
    <section class="day-group" aria-label="<?= h($day) ?>">
        <div class="day-head">
            <h2><bdi dir="ltr"><?= h(safeDate($day, 'd/m/Y')) ?></bdi></h2>
            <span class="total"><?= money($dayTotal) ?></span>
        </div>
        <ul class="rows">
            <?php foreach ($items as $exp):
                $parts = expenseParticipants($exp, $team);
                $payer = (string) ($exp['paid_by'] ?? '');
                $personal = expenseSource($exp) === 'personal';
                $everyone = count($team) > 0 && count($parts) === count($team) && !array_diff($team, $parts);
            ?>
            <li class="row">
                <div class="row-main">
                    <?= categoryChip((string) ($exp['note'] ?? '')) ?>
                    <span>دفعها <b><?= h(payerLabel($exp)) ?></b></span>
                </div>
                <div class="row-amt"><?= money((float) $exp['amount']) ?><?= sar((float) $exp['amount']) ?></div>
                <div class="row-sub">
                    <span>المشاركون:</span>
                    <?php if ($everyone): ?>
                        <span class="person">الجميع (<?= (int) count($parts) ?>)</span>
                    <?php else: ?>
                        <?php foreach ($parts as $p): ?>
                            <span class="person<?= $personal && $p === $payer ? ' payer' : '' ?>"><?= h($p) ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <span>· نصيب الفرد <?= fmtNum(count($parts) ? (float) $exp['amount'] / count($parts) : 0) ?></span>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endforeach; ?>
<?php endif; ?>
<?php pageEnd(); ?>
