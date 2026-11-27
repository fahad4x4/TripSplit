<?php
/**
 * TripSplit — منطق الحسابات (المصدر الوحيد)
 *
 * هذا الملف لا يحتوي أي مخرجات أو آثار جانبية؛ كل الصفحات والتقارير والاختبارات تستخدمه.
 *
 * القواعد:
 *  - الدافع (paid_by) إما عضو في الفريق أو القيمة الخاصة "الصندوق" (القطة المشتركة).
 *  - source = 'kitty'    : المصروف دُفع من الصندوق.
 *    source = 'personal' : دفعه العضو من جيبه.
 *    المصروفات القديمة بدون source تُعامل كـ kitty (للحفاظ على أرقام الرحلات القديمة).
 *  - صافي العضو = مساهماته في الصندوق + ما دفعه شخصياً − حصته من المصروفات.
 *  - رصيد الصندوق = مجموع المساهمات − مصروفات الصندوق.
 *  - مجموع صافي الأعضاء = رصيد الصندوق دائماً.
 */

if (!defined('KITTY')) {
    define('KITTY', 'الصندوق');
}

const STATUS_CREDIT   = 'له';
const STATUS_DEBIT    = 'عليه';
const STATUS_BALANCED = 'متوازن';

/* ------------------------------------------------------------------ */
/* أدوات مساعدة                                                        */
/* ------------------------------------------------------------------ */

function toCents($amount): int {
    return (int) round(((float) $amount) * 100);
}

function fromCents(int $cents): float {
    return round($cents / 100, 2);
}

function statusFor(float $net): string {
    if ($net >= 0.005) return STATUS_CREDIT;
    if ($net <= -0.005) return STATUS_DEBIT;
    return STATUS_BALANCED;
}

/** المشاركون في مصروف؛ إذا كانت القائمة مفقودة أو فارغة نعتبر الفريق كاملاً. */
function expenseParticipants(array $expense, array $team): array {
    $p = $expense['participants'] ?? null;
    if (!is_array($p) || count($p) === 0) {
        return array_values($team);
    }
    return array_values(array_unique(array_map('strval', $p)));
}

/** مصدر الدفع: 'kitty' أو 'personal'. القديم بدون source يُعتبر من الصندوق. */
function expenseSource(array $expense): string {
    $src = $expense['source'] ?? null;
    if ($src === 'personal' || $src === 'kitty') {
        return $src;
    }
    return 'kitty';
}

/** نص الدافع للعرض. */
function payerLabel(array $expense): string {
    $paidBy = (string) ($expense['paid_by'] ?? '');
    if (expenseSource($expense) === 'kitty') {
        if ($paidBy === '' || $paidBy === KITTY) {
            return KITTY;
        }
        return $paidBy . ' (من ' . KITTY . ')';
    }
    return $paidBy;
}

function isValidDate($date): bool {
    if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d !== false && $d->format('Y-m-d') === $date;
}

/** تاريخ آمن للعرض حتى لو كانت البيانات القديمة غير صالحة. */
function safeDate($date, string $format = 'Y-m-d'): string {
    $ts = is_string($date) ? strtotime($date) : false;
    return $ts ? date($format, $ts) : '-';
}

/* ------------------------------------------------------------------ */
/* المجاميع                                                            */
/* ------------------------------------------------------------------ */

function totalContributions(array $contributions): float {
    $sum = 0;
    foreach ($contributions as $c) {
        $sum += toCents($c['amount'] ?? 0);
    }
    return fromCents($sum);
}

function totalExpenses(array $expenses): float {
    $sum = 0;
    foreach ($expenses as $e) {
        $sum += toCents($e['amount'] ?? 0);
    }
    return fromCents($sum);
}

/** حالة الصندوق: المساهمات، ما صُرف منه، والرصيد المتبقي. */
function kittyBalance(array $expenses, array $contributions): array {
    $in = 0;
    foreach ($contributions as $c) {
        $in += toCents($c['amount'] ?? 0);
    }
    $out = 0;
    foreach ($expenses as $e) {
        if (expenseSource($e) === 'kitty') {
            $out += toCents($e['amount'] ?? 0);
        }
    }
    $bal = $in - $out;
    return [
        'contributions' => fromCents($in),
        'spent'         => fromCents($out),
        'balance'       => fromCents($bal),
        'status'        => $bal > 0 ? 'فائض' : ($bal < 0 ? 'عجز' : STATUS_BALANCED),
    ];
}

function dailyExpenses(array $expenses): array {
    $daily = [];
    foreach ($expenses as $exp) {
        $date = safeDate($exp['date'] ?? '');
        $daily[$date] = ($daily[$date] ?? 0) + (float) ($exp['amount'] ?? 0);
    }
    ksort($daily);
    return $daily;
}

/** تجميع يومي مع تفصيل حسب نوع المصروف (للصفحة اليومية وتقرير PDF). */
function dailyExpensesWithDetails(array $expenses): array {
    $daily = [];
    foreach ($expenses as $exp) {
        $date = safeDate($exp['date'] ?? '');
        $type = (string) ($exp['note'] ?? 'غير محدد');
        $amount = (float) ($exp['amount'] ?? 0);
        if (!isset($daily[$date])) {
            $daily[$date] = ['total' => 0, 'details' => []];
        }
        $daily[$date]['total'] += $amount;
        $daily[$date]['details'][$type] = ($daily[$date]['details'][$type] ?? 0) + $amount;
    }
    ksort($daily);
    return $daily;
}

/* ------------------------------------------------------------------ */
/* الأرصدة                                                             */
/* ------------------------------------------------------------------ */

/**
 * يحسب رصيد كل عضو.
 * يعيد [name => ['contributed','paid','share','net','status']] بمبالغ مقربة لخانتين،
 * مع توزيع فروقات التقريب بحيث يساوي مجموع الصافي رصيد الصندوق بالضبط.
 */
function calculateBalances(array $team, array $expenses, array $contributions): array {
    $names = array_values(array_map('strval', $team));
    $contrib = [];
    $paid = [];
    $share = [];
    $add = function (string $n) use (&$names, &$contrib, &$paid, &$share) {
        if (!in_array($n, $names, true)) {
            $names[] = $n; // اسم غير موجود في الفريق (بيانات قديمة) حتى لا تضيع المبالغ
        }
        $contrib[$n] = $contrib[$n] ?? 0.0;
        $paid[$n] = $paid[$n] ?? 0.0;
        $share[$n] = $share[$n] ?? 0.0;
    };
    foreach ($names as $n) {
        $add($n);
    }

    foreach ($contributions as $c) {
        $n = (string) ($c['name'] ?? '');
        if ($n === '' || $n === KITTY) continue;
        $add($n);
        $contrib[$n] += toCents($c['amount'] ?? 0);
    }

    foreach ($expenses as $e) {
        $amount = toCents($e['amount'] ?? 0);
        if (expenseSource($e) === 'personal') {
            $payer = (string) ($e['paid_by'] ?? '');
            if ($payer !== '' && $payer !== KITTY) {
                $add($payer);
                $paid[$payer] += $amount;
            }
        }
        $parts = expenseParticipants($e, $team);
        $cnt = count($parts);
        if ($cnt === 0) continue;
        foreach ($parts as $p) {
            if ($p === KITTY) continue;
            $add($p);
            $share[$p] += $amount / $cnt; // بالسنتات، بدون تقريب بعد
        }
    }

    // تقريب لأقرب سنت مع توزيع الفرق (largest remainder)
    $target = toCents(kittyBalance($expenses, $contributions)['balance']);
    $netCents = [];
    $err = [];
    foreach ($names as $n) {
        $exact = $contrib[$n] + $paid[$n] - $share[$n];
        $netCents[$n] = (int) round($exact);
        $err[$n] = $exact - $netCents[$n];
    }
    $diff = $target - array_sum($netCents);
    $guard = count($names) * 2 + 2;
    while ($diff !== 0 && $guard-- > 0 && count($names) > 0) {
        if ($diff > 0) {
            $pick = array_keys($err, max($err))[0];
            $netCents[$pick]++;
            $err[$pick] -= 1;
            $diff--;
        } else {
            $pick = array_keys($err, min($err))[0];
            $netCents[$pick]--;
            $err[$pick] += 1;
            $diff++;
        }
    }

    $balances = [];
    foreach ($names as $n) {
        $net = fromCents($netCents[$n]);
        $balances[$n] = [
            'contributed' => fromCents((int) $contrib[$n]),
            'paid'        => fromCents((int) $paid[$n]),
            'share'       => round($share[$n] / 100, 2),
            'net'         => $net,
            'status'      => statusFor($net),
        ];
    }
    return $balances;
}

/**
 * التسويات بين الأعضاء: أكبر مدين يدفع لأكبر دائن (greedy)، بدون صفوف صفرية.
 * لا يدخل الصندوق في التحويلات بين الأعضاء؛ ما يتبقى بعد التسوية يخص الصندوق:
 *  - kitty_refunds: مبالغ يستردها أعضاء من فائض الصندوق.
 *  - kitty_dues:    مبالغ على أعضاء لتغطية عجز الصندوق.
 */
function computeSettlement(array $balances): array {
    $debtors = [];
    $creditors = [];
    foreach ($balances as $name => $b) {
        $c = toCents($b['net'] ?? 0);
        if ($c > 0) $creditors[$name] = $c;
        elseif ($c < 0) $debtors[$name] = -$c;
    }

    $transactions = [];
    while (!empty($debtors) && !empty($creditors)) {
        arsort($debtors);
        arsort($creditors);
        $d = array_key_first($debtors);
        $c = array_key_first($creditors);
        $pay = min($debtors[$d], $creditors[$c]);
        if ($pay >= 1) {
            $transactions[] = ['from' => (string) $d, 'to' => (string) $c, 'amount' => fromCents($pay)];
        }
        $debtors[$d] -= $pay;
        $creditors[$c] -= $pay;
        if ($debtors[$d] <= 0) unset($debtors[$d]);
        if ($creditors[$c] <= 0) unset($creditors[$c]);
    }

    $refunds = [];
    foreach ($creditors as $n => $c) {
        if ($c >= 1) $refunds[(string) $n] = fromCents($c);
    }
    $dues = [];
    foreach ($debtors as $n => $c) {
        if ($c >= 1) $dues[(string) $n] = fromCents($c);
    }

    return [
        'transactions'  => $transactions,
        'kitty_refunds' => $refunds,
        'kitty_dues'    => $dues,
    ];
}

/** للتوافق مع الاستدعاءات القديمة: قائمة التحويلات بين الأعضاء فقط. */
function recommendSettlements(array $balances): array {
    return computeSettlement($balances)['transactions'];
}

function sumSurpluses(array $balances): float {
    return array_reduce($balances, function ($sum, $b) { return $sum + ($b['net'] > 0 ? $b['net'] : 0); }, 0.0);
}

function sumDeficits(array $balances): float {
    return array_reduce($balances, function ($sum, $b) { return $sum + ($b['net'] < 0 ? abs($b['net']) : 0); }, 0.0);
}

function getSurpluses(array $balances): array {
    $out = [];
    foreach ($balances as $n => $b) if ($b['net'] > 0) $out[$n] = $b['net'];
    return $out;
}

function getDeficits(array $balances): array {
    $out = [];
    foreach ($balances as $n => $b) if ($b['net'] < 0) $out[$n] = $b['net'];
    return $out;
}

function categorizeMembers(array $balances): array {
    $result = [STATUS_CREDIT => [], STATUS_DEBIT => [], STATUS_BALANCED => []];
    foreach ($balances as $name => $b) {
        $result[$b['status'] ?? STATUS_BALANCED][] = $name;
    }
    return $result;
}

/* ------------------------------------------------------------------ */
/* عمليات تعديل البيانات (دوال نقية تعيد [data, error])               */
/* ------------------------------------------------------------------ */

function normalizeData($data): array {
    if (!is_array($data)) $data = [];
    $data['team'] = array_values(array_map('strval', $data['team'] ?? []));
    $data['expenses'] = array_values($data['expenses'] ?? []);
    $data['contributions'] = array_values($data['contributions'] ?? []);
    return $data;
}

function textLength(string $s): int {
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen(utf8_decode_safe($s));
}

function utf8_decode_safe(string $s): string {
    return (string) preg_replace('/[\x{80}-\x{10FFFF}]/u', '?', $s);
}

function validateMemberName(string $name, array $team): ?string {
    if ($name === '') return 'اسم العضو مطلوب.';
    if (textLength($name) > 50) return 'اسم العضو طويل جداً (الحد 50 حرفاً).';
    if ($name === KITTY) return 'لا يمكن استخدام اسم "' . KITTY . '" لعضو.';
    if (in_array($name, $team, true)) return 'العضو موجود مسبقاً.';
    return null;
}

function addMember(array $data, string $name): array {
    $name = trim($name);
    $err = validateMemberName($name, $data['team']);
    if ($err) return [$data, $err];
    $data['team'][] = $name;
    return [$data, null];
}

function parseAmount($raw): ?float {
    if (!is_scalar($raw) || !is_numeric($raw)) return null;
    $v = round((float) $raw, 2);
    return ($v > 0 && is_finite($v)) ? $v : null;
}

/** $input: paid_by, amount, note, participants[], date */
function addExpense(array $data, array $input, ?string $today = null): array {
    $team = $data['team'];
    $paidBy = trim((string) ($input['paid_by'] ?? ''));
    if ($paidBy !== KITTY && !in_array($paidBy, $team, true)) {
        return [$data, 'الدافع يجب أن يكون "' . KITTY . '" أو عضواً في الفريق.'];
    }
    $amount = parseAmount($input['amount'] ?? null);
    if ($amount === null) return [$data, 'المبلغ يجب أن يكون رقماً أكبر من صفر.'];
    $note = trim((string) ($input['note'] ?? ''));
    if ($note === '') return [$data, 'نوع المصروف مطلوب.'];
    if (textLength($note) > 100) return [$data, 'نوع المصروف طويل جداً.'];

    $parts = $input['participants'] ?? [];
    if (!is_array($parts)) $parts = [];
    $parts = array_values(array_unique(array_map(function ($p) { return trim((string) $p); }, $parts)));
    foreach ($parts as $p) {
        if (!in_array($p, $team, true)) return [$data, 'المشارك "' . $p . '" ليس عضواً في الفريق.'];
    }
    if (empty($parts)) $parts = $team;
    if (empty($parts)) return [$data, 'أضف أعضاء للفريق أولاً.'];

    $date = trim((string) ($input['date'] ?? ''));
    if ($date === '') $date = $today ?? date('Y-m-d');
    if (!isValidDate($date)) return [$data, 'التاريخ غير صالح (الصيغة YYYY-MM-DD).'];

    $data['expenses'][] = [
        'paid_by'      => $paidBy,
        'source'       => $paidBy === KITTY ? 'kitty' : 'personal',
        'amount'       => $amount,
        'note'         => $note,
        'participants' => $parts,
        'date'         => $date,
    ];
    return [$data, null];
}

function addContribution(array $data, array $input, ?string $today = null): array {
    $name = trim((string) ($input['name'] ?? ''));
    if (!in_array($name, $data['team'], true)) return [$data, 'المساهم يجب أن يكون عضواً في الفريق.'];
    $amount = parseAmount($input['amount'] ?? null);
    if ($amount === null) return [$data, 'المبلغ يجب أن يكون رقماً أكبر من صفر.'];
    $date = trim((string) ($input['date'] ?? ''));
    if ($date === '') $date = $today ?? date('Y-m-d');
    if (!isValidDate($date)) return [$data, 'التاريخ غير صالح (الصيغة YYYY-MM-DD).'];
    $data['contributions'][] = ['name' => $name, 'amount' => $amount, 'date' => $date];
    return [$data, null];
}

/** إعادة تسمية عضو في كل مكان: الفريق، الدافع، المشاركون، المساهمات. */
function renameMember(array $data, string $old, string $new): array {
    $old = trim($old);
    $new = trim($new);
    if (!in_array($old, $data['team'], true)) return [$data, 'العضو غير موجود.'];
    if ($old === $new) return [$data, 'الاسم الجديد مطابق للاسم الحالي.'];
    $err = validateMemberName($new, $data['team']);
    if ($err) return [$data, $err];

    $data['team'] = array_map(function ($m) use ($old, $new) { return $m === $old ? $new : $m; }, $data['team']);
    foreach ($data['expenses'] as &$e) {
        if (($e['paid_by'] ?? null) === $old) $e['paid_by'] = $new;
        if (!empty($e['participants']) && is_array($e['participants'])) {
            $e['participants'] = array_map(function ($p) use ($old, $new) { return $p === $old ? $new : $p; }, $e['participants']);
        }
    }
    unset($e);
    foreach ($data['contributions'] as &$c) {
        if (($c['name'] ?? null) === $old) $c['name'] = $new;
    }
    unset($c);
    return [$data, null];
}

/**
 * حذف عضو.
 *  - يُرفض إذا كانت له مساهمات أو مدفوعات (paid_by).
 *  - إذا كان مشاركاً في مصروفات: يُرفض، إلا إذا $redistribute = true فيُزال من قوائم المشاركين
 *    ويُقسم المصروف على البقية (ويُرفض إذا أصبح مصروف بلا مشاركين).
 */
function deleteMember(array $data, string $name, bool $redistribute = false): array {
    $name = trim($name);
    $team = $data['team'];
    if (!in_array($name, $team, true)) return [$data, 'العضو غير موجود.'];

    foreach ($data['contributions'] as $c) {
        if (($c['name'] ?? null) === $name) {
            return [$data, 'لا يمكن حذف "' . $name . '" لأن له مساهمات في الصندوق. احذف أو عدّل المساهمات أولاً.'];
        }
    }
    foreach ($data['expenses'] as $e) {
        if (($e['paid_by'] ?? null) === $name) {
            return [$data, 'لا يمكن حذف "' . $name . '" لأنه مسجل كدافع لمصروفات.'];
        }
    }

    $remainingTeam = array_values(array_filter($team, function ($m) use ($name) { return $m !== $name; }));
    $participates = false;
    $expenses = $data['expenses']; // نعمل على نسخة حتى لا تتغير البيانات عند الرفض
    foreach ($expenses as $i => $e) {
        $parts = expenseParticipants($e, $team);
        if (!in_array($name, $parts, true)) continue;
        $participates = true;
        if (!$redistribute) break;
        $newParts = array_values(array_filter($parts, function ($p) use ($name) { return $p !== $name; }));
        if (count($newParts) === 0) {
            return [$data, 'لا يمكن حذف "' . $name . '" لأنه المشارك الوحيد في مصروف (' . ($e['note'] ?? '') . ').'];
        }
        $expenses[$i]['participants'] = $newParts;
    }

    if ($participates && !$redistribute) {
        return [$data, 'لا يمكن حذف "' . $name . '" لأنه مشارك في مصروفات. اختر "إعادة توزيع حصته على بقية المشاركين" للمتابعة.'];
    }

    if (!empty($expenses) && count($remainingTeam) === 0) {
        return [$data, 'لا يمكن حذف آخر عضو في الفريق مع وجود مصروفات.'];
    }

    $data['expenses'] = array_values($expenses);
    $data['team'] = $remainingTeam;
    return [$data, null];
}
