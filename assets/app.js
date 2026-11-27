/* TripSplit — سكربت خفيف بدون مكتبات: الوضع الليلي، نماذج الإدارة (AJAX + CSRF)، وتأكيد الإجراءات الخطرة. */
(function () {
    'use strict';
    var root = document.documentElement;

    /* ---------- الوضع الليلي ---------- */
    function isDark() {
        var t = root.getAttribute('data-theme');
        if (t) return t === 'dark';
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
        btn.setAttribute('aria-pressed', String(isDark()));
        btn.addEventListener('click', function () {
            var next = isDark() ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            try { localStorage.setItem('theme', next); localStorage.removeItem('darkMode'); } catch (e) {}
            btn.setAttribute('aria-pressed', String(next === 'dark'));
        });
    });

    /* ---------- تنبيه عائم ---------- */
    var toastTimer;
    function toast(msg, ok) {
        var el = document.querySelector('.toast');
        if (!el) {
            el = document.createElement('div');
            el.className = 'toast';
            el.setAttribute('role', 'status');
            el.setAttribute('aria-live', 'polite');
            document.body.appendChild(el);
        }
        el.textContent = msg;
        el.classList.toggle('err', !ok);
        el.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { el.hidden = true; }, 4000);
    }

    /* ---------- نافذة التأكيد (dialog أصلي) ---------- */
    function confirmDialog(opts) {
        return new Promise(function (resolve) {
            var d = document.getElementById('confirm-dialog');
            if (!d || typeof d.showModal !== 'function') {
                resolve(window.confirm(opts.title + '\n' + opts.text));
                return;
            }
            d.querySelector('[data-title]').textContent = opts.title;
            d.querySelector('[data-text]').textContent = opts.text;
            var ok = d.querySelector('[data-ok]');
            ok.textContent = opts.ok || 'تأكيد';
            d.returnValue = '';
            d.addEventListener('close', function onClose() {
                d.removeEventListener('close', onClose);
                resolve(d.returnValue === 'ok');
            });
            d.showModal();
            d.querySelector('[data-cancel]').focus();
        });
    }

    var CSRF = window.TRIPSPLIT_CSRF || '';

    function post(url, formData) {
        return fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: { 'X-CSRF-Token': CSRF, 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.text().then(function (t) { return { ok: r.ok, text: t }; }); });
    }

    /* ---------- نماذج الإدارة ---------- */
    document.querySelectorAll('form[data-ajax]').forEach(function (form) {
        var msg = form.querySelector('.form-msg');
        var btn = form.querySelector('button[type="submit"]');
        function show(text, ok) {
            if (msg) { msg.textContent = text; msg.className = 'form-msg ' + (ok ? 'ok' : 'err'); }
            toast((ok ? '✓ ' : '✗ ') + text, ok);
        }
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            form.querySelectorAll('[aria-invalid]').forEach(function (el) { el.removeAttribute('aria-invalid'); });
            if (!form.checkValidity()) {
                var bad = form.querySelector(':invalid');
                if (bad) { bad.setAttribute('aria-invalid', 'true'); bad.focus(); }
                show(bad && bad.dataset.msg ? bad.dataset.msg : 'أكمل الحقول المطلوبة.', false);
                return;
            }
            var go = Promise.resolve(true);
            if (form.dataset.confirm) {
                var sel = form.querySelector('select[name="name"]');
                go = confirmDialog({
                    title: form.dataset.confirm,
                    text: (sel && sel.value ? 'العضو: ' + sel.value + '. ' : '') + (form.dataset.confirmText || ''),
                    ok: form.dataset.confirmOk
                });
            }
            go.then(function (yes) {
                if (!yes) return;
                if (btn) btn.disabled = true;
                post('save.php', new FormData(form)).then(function (res) {
                    show(res.text, res.ok);
                    if (!res.ok) return;
                    if (form.hasAttribute('data-reload')) {
                        setTimeout(function () { window.location.reload(); }, 900);
                        return;
                    }
                    var date = form.querySelector('[name="date"]');
                    var keep = date ? date.value : null;
                    var payer = form.querySelector('[name="paid_by"]');
                    var keepPayer = payer ? payer.value : null;
                    form.reset();
                    if (date) date.value = keep;
                    if (payer) payer.value = keepPayer;
                }).catch(function () {
                    show('تعذر الاتصال بالخادم، تحقق من الاتصال وحاول مجدداً.', false);
                }).then(function () { if (btn) btn.disabled = false; });
            });
        });
    });

    /* ---------- إعادة الضبط ---------- */
    var reset = document.getElementById('reset-button');
    if (reset) {
        reset.addEventListener('click', function () {
            confirmDialog({
                title: 'مسح كل بيانات الرحلة؟',
                text: 'سيُحذف جميع الأعضاء والمصروفات والمساهمات. تُحفظ نسخة احتياطية على الخادم، لكن لا يمكن التراجع من هنا.',
                ok: 'امسح الكل'
            }).then(function (yes) {
                if (!yes) return;
                var fd = new FormData();
                fd.append('csrf_token', CSRF);
                reset.disabled = true;
                post('reset.php', fd).then(function (res) {
                    if (res.ok) {
                        toast('✓ تمت إعادة ضبط البيانات', true);
                        setTimeout(function () { window.location.href = 'index.php'; }, 1200);
                    } else {
                        toast('✗ ' + res.text, false);
                        reset.disabled = false;
                    }
                }).catch(function () { toast('✗ تعذر الاتصال بالخادم', false); reset.disabled = false; });
            });
        });
    }

    /* ---------- طباعة ---------- */
    document.querySelectorAll('[data-print]').forEach(function (b) {
        b.addEventListener('click', function () { window.print(); });
    });
})();
