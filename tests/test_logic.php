<?php
/**
 * اختبارات منطق TripSplit بدون أي إطار عمل.
 * التشغيل: php tests/test_logic.php
 */
require __DIR__ . '/../functions.php';

$passed = 0;
$failed = 0;

function check(string $label, bool $cond, string $detail = ''): void {
    global $passed, $failed;
    if ($cond) {
        $passed++;
        echo "  PASS  $label\n";
    } else {
        $failed++;
        echo "  FAIL  $label" . ($detail !== '' ? "  ($detail)" : '') . "\n";
    }
}

function near(float $a, float $b, float $eps = 0.005): bool {
    return abs($a - $b) < $eps;
}

/** يطبق التحويلات واسترداد/عجز الصندوق ويتحقق أن كل الأرصدة صفر. */
function allCleared(array $balances, array $settlement): bool {
    $net = [];
    foreach ($balances as $n => $b) $net[$n] = toCents($b['net']);
    foreach ($settlement['transactions'] as $t) {
        $net[$t['from']] += toCents($t['amount']);
        $net[$t['to']]   -= toCents($t['amount']);
    }
    foreach ($settlement['kitty_refunds'] as $n => $a) $net[$n] -= toCents($a);
    foreach ($settlement['kitty_dues'] as $n => $a)    $net[$n] += toCents($a);
    foreach ($net as $v) if ($v !== 0) return false;
    return true;
}

function noZeroRows(array $settlement): bool {
    foreach ($settlement['transactions'] as $t) if ($t['amount'] < 0.01 || $t['from'] === $t['to']) return false;
    foreach ($settlement['kitty_refunds'] as $a) if ($a < 0.01) return false;
    foreach ($settlement['kitty_dues'] as $a) if ($a < 0.01) return false;
    return true;
}

function sumNets(array $balances): float {
    return fromCents(array_sum(array_map(function ($b) { return toCents($b['net']); }, $balances)));
}

/* ---------------------------------------------------------------- */
echo "\n[1] Kitty-only trip\n";
$team = ['أحمد', 'سالم', 'فهد'];
$contrib = [
    ['name' => 'أحمد', 'amount' => 500, 'date' => '2025-05-01'],
    ['name' => 'سالم', 'amount' => 300, 'date' => '2025-05-01'],
    ['name' => 'فهد',  'amount' => 100, 'date' => '2025-05-01'],
];
$exp = [
    ['paid_by' => KITTY, 'source' => 'kitty', 'amount' => 600, 'note' => 'سكن', 'participants' => $team, 'date' => '2025-05-02'],
];
$b = calculateBalances($team, $exp, $contrib);
$k = kittyBalance($exp, $contrib);
$s = computeSettlement($b);
check('kitty balance = 900 - 600 = 300', near($k['balance'], 300));
check('أحمد net = 500 - 200 = 300', near($b['أحمد']['net'], 300));
check('سالم net = 300 - 200 = 100', near($b['سالم']['net'], 100));
check('فهد net = 100 - 200 = -100', near($b['فهد']['net'], -100));
check('statuses له/له/عليه', $b['أحمد']['status'] === 'له' && $b['سالم']['status'] === 'له' && $b['فهد']['status'] === 'عليه');
check('sum of member nets equals kitty balance', near(sumNets($b), $k['balance']));
check('one transfer فهد -> أحمد 100', count($s['transactions']) === 1 && $s['transactions'][0]['from'] === 'فهد'
    && $s['transactions'][0]['to'] === 'أحمد' && near($s['transactions'][0]['amount'], 100));
check('kitty leftover 300 refunded (outside member transfers)', near(array_sum($s['kitty_refunds']), 300) && empty($s['kitty_dues']));
check('all balances cleared', allCleared($b, $s));

/* ---------------------------------------------------------------- */
echo "\n[2] Personal payment trip (no kitty)\n";
$team = ['A', 'B', 'C'];
$exp = [
    ['paid_by' => 'A', 'source' => 'personal', 'amount' => 300, 'note' => 'سكن', 'participants' => $team, 'date' => '2025-05-01'],
    ['paid_by' => 'B', 'source' => 'personal', 'amount' => 90,  'note' => 'طعام', 'participants' => $team, 'date' => '2025-05-02'],
];
$b = calculateBalances($team, $exp, []);
$k = kittyBalance($exp, []);
$s = computeSettlement($b);
check('kitty balance 0', near($k['balance'], 0));
check('A net = 300 - 130 = 170', near($b['A']['net'], 170));
check('B net = 90 - 130 = -40', near($b['B']['net'], -40));
check('C net = -130', near($b['C']['net'], -130));
check('nets sum to 0', near(sumNets($b), 0));
check('A paid column = 300', near($b['A']['paid'], 300));
check('C pays A 130, B pays A 40', count($s['transactions']) === 2
    && $s['transactions'][0] == ['from' => 'C', 'to' => 'A', 'amount' => 130.0]
    && $s['transactions'][1] == ['from' => 'B', 'to' => 'A', 'amount' => 40.0]);
check('all balances cleared', allCleared($b, $s));

/* ---------------------------------------------------------------- */
echo "\n[3] Expense with 2 of 3 participants\n";
$team = ['A', 'B', 'C'];
$exp = [
    ['paid_by' => 'C', 'source' => 'personal', 'amount' => 100, 'note' => 'تذاكر دخول', 'participants' => ['A', 'B'], 'date' => '2025-05-01'],
];
$b = calculateBalances($team, $exp, []);
$s = computeSettlement($b);
check('A share 50, B share 50, C share 0', near($b['A']['share'], 50) && near($b['B']['share'], 50) && near($b['C']['share'], 0));
check('C net +100 (paid, not participating)', near($b['C']['net'], 100) && $b['C']['status'] === 'له');
check('A, B each owe 50', near($b['A']['net'], -50) && near($b['B']['net'], -50));
check('two transfers of 50 to C', count($s['transactions']) === 2 && near($s['transactions'][0]['amount'], 50));
check('all balances cleared', allCleared($b, $s));

/* ---------------------------------------------------------------- */
echo "\n[4] Rounding: 100 split by 3\n";
$team = ['A', 'B', 'C'];
$exp = [['paid_by' => 'A', 'source' => 'personal', 'amount' => 100, 'note' => 'طعام', 'participants' => $team, 'date' => '2025-05-01']];
$b = calculateBalances($team, $exp, []);
$s = computeSettlement($b);
check('nets sum to exactly 0.00 (rounding cent distributed)', toCents(sumNets($b)) === 0, json_encode(array_column($b, 'net')));
check('A net is 66.66 or 66.67', near($b['A']['net'], 66.67, 0.011));
check('B and C owe 33.33 or 33.34', near($b['B']['net'], -33.33, 0.011) && near($b['C']['net'], -33.33, 0.011));
$tot = 0; foreach ($s['transactions'] as $t) $tot += toCents($t['amount']);
check('transfers total equals A credit', $tot === toCents($b['A']['net']));
check('all amounts have <= 2 decimals', array_reduce($s['transactions'], function ($ok, $t) { return $ok && round($t['amount'], 2) == $t['amount']; }, true));
check('all balances cleared', allCleared($b, $s));

echo "\n[4b] Rounding stress: 7 members, awkward amounts\n";
$team = ['m1', 'm2', 'm3', 'm4', 'm5', 'm6', 'm7'];
$exp = [];
$amounts = [100, 33.33, 0.1, 999.99, 10, 1, 250.5];
foreach ($amounts as $i => $a) {
    $exp[] = ['paid_by' => $team[$i], 'source' => 'personal', 'amount' => $a, 'note' => 'x',
              'participants' => array_slice($team, 0, 3 + ($i % 5)), 'date' => '2025-05-01'];
}
$b = calculateBalances($team, $exp, [['name' => 'm1', 'amount' => 77.77, 'date' => '2025-05-01']]);
$s = computeSettlement($b);
$k = kittyBalance($exp, [['name' => 'm1', 'amount' => 77.77, 'date' => '2025-05-01']]);
check('nets sum exactly to kitty balance', toCents(sumNets($b)) === toCents($k['balance']));
check('no zero settlements', noZeroRows($s));
check('all balances cleared', allCleared($b, $s));

/* ---------------------------------------------------------------- */
echo "\n[5] Settlements: no zero rows, all cleared (mixed trip)\n";
$team = ['A', 'B', 'C', 'D'];
$contrib = [['name' => 'A', 'amount' => 400, 'date' => '2025-05-01'], ['name' => 'B', 'amount' => 400, 'date' => '2025-05-01']];
$exp = [
    ['paid_by' => KITTY, 'source' => 'kitty', 'amount' => 800, 'note' => 'سكن', 'participants' => $team, 'date' => '2025-05-01'],
    ['paid_by' => 'C', 'source' => 'personal', 'amount' => 120, 'note' => 'وقود', 'participants' => ['A', 'B', 'C'], 'date' => '2025-05-02'],
    ['paid_by' => 'D', 'source' => 'personal', 'amount' => 200, 'note' => 'طعام', 'participants' => $team, 'date' => '2025-05-03'],
];
$b = calculateBalances($team, $exp, $contrib);
$s = computeSettlement($b);
check('kitty balance 0', near(kittyBalance($exp, $contrib)['balance'], 0));
check('nets sum to 0', toCents(sumNets($b)) === 0);
check('no zero settlements', noZeroRows($s));
check('all balances cleared', allCleared($b, $s));
check('balanced member has status متوازن', calculateBalances(['X', 'Y'], [], [])['X']['status'] === 'متوازن');
check('empty trip has no settlements', computeSettlement(calculateBalances(['X', 'Y'], [], []))['transactions'] === []);

/* ---------------------------------------------------------------- */
echo "\n[6] Old data without source / participants (backward compatible)\n";
$team = ['عضو 1', 'عضو 2'];
$old = [
    ['paid_by' => 'عضو 1', 'amount' => 1000, 'note' => 'طيران', 'date' => '2025-04-30'],                         // no participants
    ['paid_by' => 'عضو 2', 'amount' => 500,  'note' => 'سكن', 'participants' => [], 'date' => '2025-05-01'],     // empty participants
    ['paid_by' => 'عضو 1', 'amount' => 150,  'note' => 'تذاكر', 'participants' => ['عضو 2'], 'date' => '2025-05-04'],
];
$contrib = [['name' => 'عضو 1', 'amount' => 1000, 'date' => '2025-04-29'], ['name' => 'عضو 2', 'amount' => 800, 'date' => '2025-04-29']];
$b = calculateBalances($team, $old, $contrib);
$k = kittyBalance($old, $contrib);
check('old expenses without source count as kitty', expenseSource($old[0]) === 'kitty' && near($k['spent'], 1650));
check('missing/empty participants = whole team', expenseParticipants($old[0], $team) === $team && expenseParticipants($old[1], $team) === $team);
check('paid_by member NOT credited for old kitty expenses', near($b['عضو 1']['paid'], 0));
check('عضو 1 net = 1000 - 750 = 250', near($b['عضو 1']['net'], 250));
check('عضو 2 net = 800 - 900 = -100', near($b['عضو 2']['net'], -100));
check('kitty balance = 1800 - 1650 = 150', near($k['balance'], 150));
check('payer label shows kitty origin', payerLabel($old[0]) === 'عضو 1 (من ' . KITTY . ')');
$s = computeSettlement($b);
check('all balances cleared', allCleared($b, $s));

// نفس الأرقام القديمة لملف المثال
$sample = json_decode(file_get_contents(__DIR__ . '/../data/expenses.example.json'), true);
$b = calculateBalances($sample['team'], $sample['expenses'], $sample['contributions'] ?? []);
check('sample: عضو 1 net -1450 (unchanged from old version)', near($b['عضو 1']['net'], -1450));
check('sample: عضو 2 net -1450 (unchanged from old version)', near($b['عضو 2']['net'], -1450));

/* ---------------------------------------------------------------- */
echo "\n[7] Data operations: validation, rename, delete\n";
$data = normalizeData(['team' => ['A', 'B', 'C']]);
[$d, $e] = addExpense($data, ['paid_by' => KITTY, 'amount' => '50', 'note' => 'طعام', 'participants' => ['A', 'B'], 'date' => '2025-05-01']);
check('add kitty expense => source kitty', $e === null && $d['expenses'][0]['source'] === 'kitty' && $d['expenses'][0]['paid_by'] === KITTY);
[$d, $e] = addExpense($d, ['paid_by' => 'C', 'amount' => '20', 'note' => 'وقود', 'date' => '']);
check('add member expense => source personal, default today, whole team', $e === null && $d['expenses'][1]['source'] === 'personal'
    && $d['expenses'][1]['date'] === date('Y-m-d') && $d['expenses'][1]['participants'] === ['A', 'B', 'C']);
[, $e] = addExpense($data, ['paid_by' => 'Z', 'amount' => '10', 'note' => 'x']);
check('reject payer not in team', $e !== null);
[, $e] = addExpense($data, ['paid_by' => 'A', 'amount' => '0', 'note' => 'x']);
check('reject amount 0', $e !== null);
[, $e] = addExpense($data, ['paid_by' => 'A', 'amount' => '-5', 'note' => 'x']);
check('reject negative amount', $e !== null);
[, $e] = addExpense($data, ['paid_by' => 'A', 'amount' => '5', 'note' => 'x', 'date' => '2025-02-30']);
check('reject invalid date', $e !== null);
[, $e] = addExpense($data, ['paid_by' => 'A', 'amount' => '5', 'note' => 'x', 'participants' => ['A', 'Q']]);
check('reject participant not in team', $e !== null);
[, $e] = addContribution($data, ['name' => 'A', 'amount' => '5', 'date' => '05/01/2025']);
check('reject contribution bad date', $e !== null);
[, $e] = addContribution($data, ['name' => 'Q', 'amount' => '5', 'date' => '2025-05-01']);
check('reject contribution from non-member', $e !== null);
[, $e] = addMember($data, KITTY);
check('reject member named الصندوق', $e !== null);
[, $e] = addMember($data, 'A');
check('reject duplicate member', $e !== null);

[$d, $e] = addContribution($d, ['name' => 'A', 'amount' => '100', 'date' => '2025-05-01']);
[$r, $e] = renameMember($d, 'C', 'سعد');
check('rename updates team + paid_by + participants', $e === null && in_array('سعد', $r['team'], true)
    && $r['expenses'][1]['paid_by'] === 'سعد' && in_array('سعد', $r['expenses'][1]['participants'], true));
[$r, $e] = renameMember($d, 'A', 'أنس');
check('rename updates contributions', $e === null && $r['contributions'][0]['name'] === 'أنس');
[, $e] = renameMember($d, 'A', 'B');
check('rename refuses existing name', $e !== null);

[, $e] = deleteMember($d, 'A');
check('delete refused: has contributions', $e !== null && strpos($e, 'مساهمات') !== false);
[, $e] = deleteMember($d, 'C');
check('delete refused: has payments', $e !== null && strpos($e, 'دافع') !== false);
[, $e] = deleteMember($d, 'B');
check('delete refused: participates (no redistribute)', $e !== null && strpos($e, 'مشارك') !== false);
[$x, $e] = deleteMember($d, 'B', true);
check('delete with redistribute removes from participants', $e === null && !in_array('B', $x['team'], true)
    && $x['expenses'][0]['participants'] === ['A'] && $x['expenses'][1]['participants'] === ['A', 'C']);
$solo = normalizeData(['team' => ['A', 'B'], 'expenses' => [['paid_by' => KITTY, 'source' => 'kitty', 'amount' => 10, 'note' => 'x', 'participants' => ['B'], 'date' => '2025-05-01']]]);
[$y, $e] = deleteMember($solo, 'B', true);
check('delete refused when expense would have no participants', $e !== null && $y === $solo);
[$z, $e] = deleteMember(normalizeData(['team' => ['A', 'B']]), 'B');
check('delete unused member OK', $e === null && $z['team'] === ['A']);
check('guard: expense with missing participants and empty team', calculateBalances([], [['amount' => 10, 'note' => 'x', 'date' => '2025-01-01']], []) === []);

echo "\n----------------------------------------\n";
echo "Passed: $passed   Failed: $failed\n";
exit($failed === 0 ? 0 : 1);
