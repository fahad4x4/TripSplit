<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/ui.php';

$data = loadData();
$team = $data['team'];
$today = date('Y-m-d');
$hasTeam = !empty($team);
$categories = ['سكن', 'طعام', 'مواصلات', 'تذاكر طيران', 'تأمين سفر', 'تأجير سيارات', 'تذاكر دخول', 'وقود', 'هايبر ماركت', 'اخرى'];

/** قائمة أعضاء كخيارات <option>. */
function memberOptions(array $team, string $placeholder): string {
    $out = '<option value="" disabled selected>' . h($placeholder) . '</option>';
    foreach ($team as $m) $out .= '<option value="' . h($m) . '">' . h($m) . '</option>';
    return $out;
}

pageStart('إدارة الرحلة', 'manage.php');
pageTitle('إدارة الرحلة', 'سجّل المصروفات والقطة، وأدر أعضاء الرحلة.');
?>
<nav class="manage-nav" aria-label="أقسام الإدارة">
    <a href="#expense">تسجيل مصروف</a>
    <a href="#contribution">تسجيل قطة</a>
    <a href="#member">إضافة عضو</a>
    <a href="#edit">تعديل اسم</a>
    <a href="#delete">حذف عضو</a>
    <a href="#reset">إعادة الضبط</a>
</nav>

<?php if (!$hasTeam): ?>
    <div class="alert warn" style="margin-bottom:16px"><?= icon('alert') ?><div>لا يوجد أعضاء بعد. ابدأ بـ<a href="#member">إضافة الأعضاء</a>، ثم سجّل القطة والمصروفات.</div></div>
<?php endif; ?>

<div class="grid grid-2">
    <!-- تسجيل مصروف -->
    <section class="card" id="expense" aria-labelledby="h-expense">
        <div class="card-head"><h2 id="h-expense"><?= icon('receipt') ?>تسجيل مصروف</h2></div>
        <form data-ajax autocomplete="off" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="type" value="expense">
            <div class="form-grid cols-2">
                <div class="field">
                    <label for="exp-amount">المبلغ (<?= h(appConfig()['currency']) ?>)</label>
                    <input class="input" id="exp-amount" type="number" name="amount" inputmode="decimal" min="0.01" step="0.01" placeholder="0.00" required data-msg="أدخل مبلغاً أكبر من صفر.">
                </div>
                <div class="field">
                    <label for="exp-note">نوع المصروف</label>
                    <select class="input" id="exp-note" name="note" required data-msg="اختر نوع المصروف.">
                        <option value="" disabled selected>اختر النوع</option>
                        <?php foreach ($categories as $c): ?><option value="<?= h($c) ?>"><?= h($c) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="exp-paid">من دفع؟</label>
                    <select class="input" id="exp-paid" name="paid_by" required data-msg="اختر من دفع المصروف.">
                        <option value="" disabled selected>اختر الدافع</option>
                        <option value="<?= h(KITTY) ?>"><?= h(KITTY) ?> (القطة المشتركة)</option>
                        <?php foreach ($team as $m): ?><option value="<?= h($m) ?>"><?= h($m) ?> (من جيبه)</option><?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="exp-date">التاريخ</label>
                    <input class="input" id="exp-date" type="date" name="date" value="<?= h($today) ?>" required data-msg="اختر تاريخاً صحيحاً.">
                </div>
                <fieldset class="field full" style="border:0;padding:0;margin:0">
                    <legend class="label">المشاركون في المصروف</legend>
                    <div class="checks">
                        <?php foreach ($team as $i => $m): ?>
                            <label class="check"><input type="checkbox" name="participants[]" value="<?= h($m) ?>"><span><?= h($m) ?></span></label>
                        <?php endforeach; ?>
                    </div>
                    <p class="help">بدون اختيار = يُقسم على كل الأعضاء بالتساوي.</p>
                </fieldset>
                <div class="full">
                    <button type="submit" class="btn btn-primary btn-block"<?= $hasTeam ? '' : ' disabled' ?>><?= icon('check') ?>تسجيل المصروف</button>
                    <div class="form-msg" role="status" aria-live="polite"></div>
                </div>
            </div>
        </form>
    </section>

    <div>
        <!-- تسجيل قطة -->
        <section class="card" id="contribution" aria-labelledby="h-contrib">
            <div class="card-head"><h2 id="h-contrib"><?= icon('wallet') ?>تسجيل قطة</h2></div>
            <form data-ajax autocomplete="off" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="type" value="contribution">
                <div class="form-grid cols-2">
                    <div class="field">
                        <label for="con-name">المساهم</label>
                        <select class="input" id="con-name" name="name" required data-msg="اختر المساهم."><?= memberOptions($team, 'اختر العضو') ?></select>
                    </div>
                    <div class="field">
                        <label for="con-amount">المبلغ</label>
                        <input class="input" id="con-amount" type="number" name="amount" inputmode="decimal" min="0.01" step="0.01" placeholder="0.00" required data-msg="أدخل مبلغاً أكبر من صفر.">
                    </div>
                    <div class="field full">
                        <label for="con-date">التاريخ</label>
                        <input class="input" id="con-date" type="date" name="date" value="<?= h($today) ?>" required data-msg="اختر تاريخاً صحيحاً.">
                    </div>
                    <div class="full">
                        <button type="submit" class="btn btn-primary btn-block"<?= $hasTeam ? '' : ' disabled' ?>><?= icon('check') ?>تسجيل القطة</button>
                        <div class="form-msg" role="status" aria-live="polite"></div>
                    </div>
                </div>
            </form>
        </section>

        <!-- إضافة عضو -->
        <section class="card" id="member" aria-labelledby="h-member">
            <div class="card-head"><h2 id="h-member"><?= icon('user') ?>إضافة عضو</h2></div>
            <form data-ajax data-reload autocomplete="off" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="type" value="team">
                <div class="field">
                    <label for="mem-name">اسم العضو</label>
                    <input class="input" id="mem-name" type="text" name="name" maxlength="50" placeholder="مثال: فهد" required data-msg="اكتب اسم العضو.">
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="margin-top:12px"><?= icon('plus') ?>إضافة</button>
                <div class="form-msg" role="status" aria-live="polite"></div>
            </form>
        </section>
    </div>

    <!-- تعديل اسم -->
    <section class="card" id="edit" aria-labelledby="h-edit">
        <div class="card-head"><h2 id="h-edit"><?= icon('edit') ?>تعديل اسم عضو</h2></div>
        <form data-ajax data-reload autocomplete="off" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="type" value="edit_member">
            <div class="form-grid cols-2">
                <div class="field">
                    <label for="ed-old">العضو</label>
                    <select class="input" id="ed-old" name="old_name" required data-msg="اختر العضو."><?= memberOptions($team, 'اختر العضو') ?></select>
                </div>
                <div class="field">
                    <label for="ed-new">الاسم الجديد</label>
                    <input class="input" id="ed-new" type="text" name="new_name" maxlength="50" required data-msg="اكتب الاسم الجديد.">
                </div>
                <p class="help full muted small">يُحدَّث الاسم في الفريق والمصروفات والمشاركين والمساهمات.</p>
                <div class="full">
                    <button type="submit" class="btn btn-block"<?= $hasTeam ? '' : ' disabled' ?>><?= icon('check') ?>حفظ الاسم</button>
                    <div class="form-msg" role="status" aria-live="polite"></div>
                </div>
            </div>
        </form>
    </section>

    <!-- حذف عضو -->
    <section class="card" id="delete" aria-labelledby="h-delete">
        <div class="card-head"><h2 id="h-delete"><?= icon('trash') ?>حذف عضو</h2></div>
        <form data-ajax data-reload data-confirm="حذف هذا العضو؟" data-confirm-text="لا يمكن التراجع عن الحذف." data-confirm-ok="نعم، احذف" autocomplete="off" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="type" value="delete_member">
            <div class="field">
                <label for="del-name">العضو</label>
                <select class="input" id="del-name" name="name" required data-msg="اختر العضو."><?= memberOptions($team, 'اختر العضو') ?></select>
            </div>
            <label class="switch" style="margin-top:12px">
                <input type="checkbox" name="redistribute" value="1">
                <span>إعادة توزيع حصته على بقية المشاركين في كل مصروف</span>
            </label>
            <p class="help muted small">لا يمكن حذف عضو له مساهمات أو مدفوعات. إن كان مشاركاً في مصروفات ففعّل إعادة التوزيع.</p>
            <button type="submit" class="btn btn-ghost-danger btn-block" style="margin-top:12px"<?= $hasTeam ? '' : ' disabled' ?>><?= icon('trash') ?>حذف العضو</button>
            <div class="form-msg" role="status" aria-live="polite"></div>
        </form>
    </section>
</div>

<!-- إعادة الضبط -->
<section class="card danger-zone section" id="reset" aria-labelledby="h-reset">
    <div class="card-head"><h2 id="h-reset"><?= icon('alert') ?>إعادة تعيين بيانات الرحلة</h2></div>
    <p class="muted" style="margin-bottom:12px">يمسح جميع الأعضاء والمصروفات والمساهمات. تُحفظ نسخة احتياطية على الخادم قبل المسح.</p>
    <button type="button" id="reset-button" class="btn btn-danger"><?= icon('trash') ?>مسح كل البيانات…</button>
</section>

<dialog id="confirm-dialog" aria-labelledby="cd-title">
    <form method="dialog">
        <div class="dialog-body">
            <?= icon('alert') ?>
            <h2 id="cd-title" data-title>هل أنت متأكد؟</h2>
            <p data-text></p>
        </div>
        <div class="dialog-actions">
            <button value="cancel" class="btn" data-cancel>إلغاء</button>
            <button value="ok" class="btn btn-danger" data-ok>تأكيد</button>
        </div>
    </form>
</dialog>
<?php pageEnd(['csrf' => true]); ?>
