<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/ui.php';

$data = loadData();
$team = $data['team'];
$expenses = $data['expenses'];
$contributions = $data['contributions'];

$total = totalExpenses($expenses);
$totalContrib = totalContributions($contributions);
$kitty = kittyBalance($expenses, $contributions);
$balances = calculateBalances($team, $expenses, $contributions);
$settlement = computeSettlement($balances);
$transactions = $settlement['transactions'];

// مجموع المصروفات لكل فئة (تنازلياً)
$expensesByCategory = [];
foreach ($expenses as $expense) {
    $category = (string) ($expense['note'] ?? 'اخرى');
    $expensesByCategory[$category] = ($expensesByCategory[$category] ?? 0) + (float) ($expense['amount'] ?? 0);
}
arsort($expensesByCategory);

// مصروفات مشتركة (مشاركان أو أكثر) أو فردية
$total_shared = 0.0;
$shared_count = 0;
$individual_expenses = [];
foreach ($expenses as $exp) {
    $parts = expenseParticipants($exp, $team);
    if (count($parts) >= 2) {
        $total_shared += (float) ($exp['amount'] ?? 0);
        $shared_count++;
    } elseif (count($parts) === 1) {
        $member = $parts[0];
        $individual_expenses[$member] = $individual_expenses[$member] ?? ['total' => 0, 'count' => 0];
        $individual_expenses[$member]['total'] += (float) $exp['amount'];
        $individual_expenses[$member]['count']++;
    }
}
$days = count(dailyExpenses($expenses));
$perPerson = count($team) > 0 ? $total / count($team) : 0;

pageStart('لوحة الرحلة', 'index.php');
?>
<?php if (empty($team)): ?>
    <div class="card">
        <?= emptyState('ابدأ رحلتك', 'أضف أعضاء الرحلة أولاً، ثم سجّل القطة والمصروفات وسيحسب التطبيق من يدفع لمن تلقائياً.', 'manage.php#member', 'إضافة الأعضاء', 'users') ?>
    </div>
<?php else: ?>

<h1 class="visually-hidden">لوحة الرحلة</h1>

<!-- الأرقام الرئيسية -->
<section class="stats" aria-label="ملخص الرحلة">
    <a class="stat hero" href="expenses.php">
        <div class="label">مجموع المصروفات</div>
        <div class="value"><?= money($total) ?></div>
        <div class="hint"><?= sar($total) ?></div>
        <div class="hint"><?= (int) count($expenses) ?> عملية · <?= (int) $days ?> أيام · متوسط الفرد <?= fmtNum($perPerson) ?></div>
    </a>
    <a class="stat" href="contributions.php">
        <div class="label">مجموع القطة</div>
        <div class="value"><?= money($totalContrib) ?></div>
        <div class="hint"><?= (int) count($contributions) ?> مساهمة</div>
    </a>
    <div class="stat">
        <div class="label">رصيد <?= h(KITTY) ?></div>
        <div class="value <?= $kitty['balance'] < 0 ? 'neg' : 'pos' ?>"><?= money($kitty['balance']) ?></div>
        <div class="hint">صُرف منه <?= fmtNum($kitty['spent']) ?></div>
    </div>
</section>

<div class="grid grid-2 section">
    <!-- من يدفع لمن -->
    <section class="card" aria-labelledby="settle-title">
        <div class="card-head">
            <h2 id="settle-title"><?= icon('arrow') ?>من يدفع لمن؟</h2>
            <a class="btn" href="export.php"><?= icon('pdf') ?>تقرير PDF</a>
        </div>
        <?php if (!empty($transactions)): ?>
            <ul class="settle-list">
                <?php foreach ($transactions as $t): ?>
                <li class="transfer">
                    <div class="who"><small>يدفع</small><b><?= h($t['from']) ?></b></div>
                    <div class="mid">
                        <span class="amt"><?= fmtNum($t['amount']) ?></span>
                        <?= icon('arrow') ?>
                        <?= sar($t['amount']) ?>
                    </div>
                    <div class="who to"><small>إلى</small><b><?= h($t['to']) ?></b></div>
                </li>
                <?php endforeach; ?>
            </ul>
            <p class="muted small" style="margin-top:8px">المبالغ بـ<?= h(appConfig()['currency']) ?>.</p>
        <?php elseif (empty($settlement['kitty_refunds']) && empty($settlement['kitty_dues'])): ?>
            <div class="alert ok"><?= icon('check') ?><div>كل الحسابات متوازنة، لا توجد تحويلات مطلوبة.</div></div>
        <?php else: ?>
            <p class="muted">لا توجد تحويلات بين الأعضاء؛ التسوية كلها عبر <?= h(KITTY) ?>.</p>
        <?php endif; ?>

        <?php if ($kitty['balance'] > 0 && !empty($settlement['kitty_refunds'])): ?>
            <div class="kitty-note ok">
                <h3><?= icon('piggy') ?>المتبقي في <?= h(KITTY) ?>: <?= money($kitty['balance']) ?></h3>
                <p>يُعاد لكل عضو من <?= h(KITTY) ?> (منفصل عن التحويلات أعلاه):</p>
                <div class="pill-row">
                    <?php foreach ($settlement['kitty_refunds'] as $name => $amt): ?>
                        <span class="pill"><b><?= h($name) ?></b> <?= fmtNum($amt) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php elseif ($kitty['balance'] < 0 && !empty($settlement['kitty_dues'])): ?>
            <div class="kitty-note bad">
                <h3><?= icon('alert') ?>عجز في <?= h(KITTY) ?>: <?= money(abs($kitty['balance'])) ?></h3>
                <p>يغطيه الأعضاء بإضافة قطة (منفصل عن التحويلات أعلاه):</p>
                <div class="pill-row">
                    <?php foreach ($settlement['kitty_dues'] as $name => $amt): ?>
                        <span class="pill"><b><?= h($name) ?></b> <?= fmtNum($amt) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- أرصدة الأعضاء -->
    <section class="card" aria-labelledby="members-title">
        <div class="card-head">
            <h2 id="members-title"><?= icon('users') ?>أرصدة الأعضاء</h2>
            <span class="muted small"><?= (int) count($team) ?> أعضاء</span>
        </div>
        <ul class="members">
            <?php foreach ($balances as $name => $b): ?>
            <li class="member">
                <span class="avatar" aria-hidden="true"><?= h(initial((string) $name)) ?></span>
                <div>
                    <b><?= h($name) ?></b> <?= statusBadge($b['status']) ?>
                    <div class="meta">قطة <?= fmtNum($b['contributed']) ?> · دفع <?= fmtNum($b['paid']) ?> · حصته <?= fmtNum($b['share']) ?></div>
                </div>
                <div class="bal <?= $b['net'] > 0 ? 'pos' : ($b['net'] < 0 ? 'neg' : 'muted') ?>"><?= fmtNum($b['net']) ?></div>
            </li>
            <?php endforeach; ?>
        </ul>
        <p class="muted small" style="margin-top:8px">الرصيد = القطة + ما دفعه شخصياً − حصته من المصروفات.</p>
    </section>
</div>

<div class="grid grid-2 section">
    <!-- حسب الفئة -->
    <section class="card" aria-labelledby="cat-title">
        <div class="card-head">
            <h2 id="cat-title"><?= icon('chart') ?>المصروفات حسب الفئة</h2>
            <a class="small link" href="Chart.php">التفاصيل</a>
        </div>
        <?php if (!empty($expensesByCategory)): ?>
            <ul class="bars">
                <?php foreach ($expensesByCategory as $category => $amount): $pct = $total > 0 ? $amount / $total * 100 : 0; ?>
                <li class="bar-row">
                    <div class="bar-top">
                        <span><?= categoryChip((string) $category) ?></span>
                        <span><?= money($amount, false) ?> <span class="pct"><?= fmtNum($pct, 0, '%') ?></span></span>
                    </div>
                    <div class="bar" role="img" aria-label="<?= h($category) ?> <?= h(number_format($pct, 0)) ?>%"><span style="width: <?= h(number_format($pct, 1, '.', '')) ?>%"></span></div>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <?= emptyState('لا توجد مصروفات بعد', 'سجّل أول مصروف من صفحة الإدارة.', 'manage.php#expense', 'تسجيل مصروف') ?>
        <?php endif; ?>
    </section>

    <!-- مشتركة / فردية -->
    <section class="card" aria-labelledby="split-title">
        <div class="card-head"><h2 id="split-title"><?= icon('users') ?>مشتركة وفردية</h2></div>
        <ul class="members">
            <li class="member">
                <span class="avatar" aria-hidden="true"><?= icon('users') ?></span>
                <div><b>مصروفات مشتركة</b><div class="meta"><?= (int) $shared_count ?> عملية بين عضوين أو أكثر</div></div>
                <div class="bal"><?= fmtNum($total_shared) ?></div>
            </li>
            <?php foreach ($individual_expenses as $member => $info): ?>
            <li class="member">
                <span class="avatar" aria-hidden="true"><?= h(initial((string) $member)) ?></span>
                <div><b>خاصة بـ<?= h($member) ?></b><div class="meta"><?= (int) $info['count'] ?> عملية</div></div>
                <div class="bal"><?= fmtNum($info['total']) ?></div>
            </li>
            <?php endforeach; ?>
        </ul>
        <div class="page-actions" style="margin-top:16px">
            <a class="btn btn-primary" href="manage.php#expense"><?= icon('plus') ?>تسجيل مصروف</a>
            <a class="btn" href="daily_expenses.php"><?= icon('calendar') ?>المصروف اليومي</a>
        </div>
    </section>
</div>
<?php endif; ?>
<?php pageEnd(); ?>
