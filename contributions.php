<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/ui.php';

$data = loadData();
$contributions = $data['contributions'];
$total = totalContributions($contributions);

// مجموع كل عضو
$perMember = [];
foreach ($data['team'] as $m) $perMember[$m] = 0.0;
foreach ($contributions as $c) {
    $n = (string) ($c['name'] ?? '');
    $perMember[$n] = ($perMember[$n] ?? 0) + (float) ($c['amount'] ?? 0);
}
arsort($perMember);
$max = $perMember ? max($perMember) : 0;

$addBtn = '<a class="btn btn-primary" href="manage.php#contribution">' . icon('plus') . 'تسجيل قطة</a>';
pageStart('سجل القطة', 'contributions.php');
pageTitle('سجل القطة', count($contributions) . ' مساهمة · المجموع ' . number_format($total, 2) . ' ' . appConfig()['currency'], $addBtn);
?>
<?php if (empty($contributions)): ?>
    <div class="card"><?= emptyState('لا توجد مساهمات في القطة', 'سجّل ما دفعه كل عضو في ' . KITTY . ' المشترك.', 'manage.php#contribution', 'تسجيل أول قطة', 'wallet') ?></div>
<?php else: ?>
<div class="grid grid-2">
    <section class="card" aria-labelledby="per-member">
        <div class="card-head"><h2 id="per-member"><?= icon('users') ?>مساهمة كل عضو</h2></div>
        <ul class="bars">
            <?php foreach ($perMember as $name => $amt): $pct = $max > 0 ? $amt / $max * 100 : 0; ?>
            <li class="bar-row">
                <div class="bar-top"><b><?= h($name) ?></b><span><?= money($amt, false) ?></span></div>
                <div class="bar"><span style="width: <?= h(number_format($pct, 1, '.', '')) ?>%"></span></div>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <section aria-labelledby="all-contrib">
        <h2 id="all-contrib" class="visually-hidden">كل الدفعات</h2>
        <ul class="rows">
            <?php foreach (array_reverse($contributions) as $c): ?>
            <li class="row">
                <div class="row-main"><span class="avatar" aria-hidden="true" style="width:32px;height:32px"><?= h(initial((string) ($c['name'] ?? ''))) ?></span><b><?= h($c['name'] ?? '') ?></b></div>
                <div class="row-amt"><?= money((float) $c['amount']) ?><?= sar((float) $c['amount']) ?></div>
                <div class="row-sub"><bdi dir="ltr"><?= h(safeDate($c['date'] ?? '', 'd/m/Y')) ?></bdi></div>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
<?php endif; ?>
<?php pageEnd(); ?>
