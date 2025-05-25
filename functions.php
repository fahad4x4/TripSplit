<?php
function totalContributions($contributions) {
    $sum = 0;
    foreach ($contributions as $c) {
        $sum += $c['amount'];
    }
    return $sum;
}


function sumSurpluses(array $balances): float {
    return array_reduce($balances, fn($sum, $b) => $sum + ($b['net'] > 0 ? $b['net'] : 0), 0);
}

function sumDeficits(array $balances): float {
    return array_reduce($balances, fn($sum, $b) => $sum + ($b['net'] < 0 ? abs($b['net']) : 0), 0);
}
function dailyExpenses(array $expenses): array {
    $daily = [];

    foreach ($expenses as $exp) {
        $date = date('Y-m-d', strtotime($exp['date']));
        if (!isset($daily[$date])) {
            $daily[$date] = 0;
        }
        $daily[$date] += $exp['amount'];
    }

    ksort($daily); // ترتيب حسب التاريخ
    return $daily;
}
function calculateBalances($participants, $expenses, $contributions) {
    $individualExpenses = [];
    $individualContributions = [];
    $balances = [];

    foreach ($participants as $p) {
        $individualExpenses[$p] = 0;
        $individualContributions[$p] = 0;
    }

    foreach ($expenses as $exp) {
        $amount = $exp['amount'];
        $expenseParticipants = $exp['participants'] ?? $participants; // إذا المصروف قديم مافيه مشاركين نعتبر الكل

        $share = count($expenseParticipants) > 0 ? ($amount / count($expenseParticipants)) : 0;

        foreach ($expenseParticipants as $p) {
            if (!isset($individualExpenses[$p])) {
                $individualExpenses[$p] = 0;
            }
            $individualExpenses[$p] += $share;
        }
    }
    
    foreach ($contributions as $c) {
        $name = $c['name'];
        $amount = $c['amount'];
        if (!isset($individualContributions[$name])) {
            $individualContributions[$name] = 0;
        }
        $individualContributions[$name] += $amount;
    }

    foreach ($participants as $p) {
        $paid = $individualContributions[$p];
        $due = $individualExpenses[$p];
        $net = round($paid - $due, 2);
        $status = $net > 0 ? 'فائض' : ($net < 0 ? 'عليه دفع' : 'متساوي');
        $balances[$p] = [
            'contributed' => $paid,
            'share' => $due,
            'net' => $net,
            'status' => $status
        ];
    }

    return $balances;
}

function totalExpenses(array $expenses): float {
    return array_reduce($expenses, function($carry, $item) {
        return $carry + ($item['amount'] ?? 0);
    }, 0);
}

function recommendSettlements($balances) {
    $settlements = [];
    $debtors = [];
    $creditors = [];

    foreach ($balances as $name => $data) {
        if ($data['net'] < 0) {
            $debtors[$name] = $data;
        } elseif ($data['net'] > 0) {
            $creditors[$name] = $data;
        }
    }

    foreach ($debtors as $debtor => $dData) {
        $amountOwed = -$dData['net'];
        foreach ($creditors as $creditor => &$cData) {
            if ($amountOwed == 0) break;
            $creditAvailable = $cData['net'];
            $payAmount = min($amountOwed, $creditAvailable);
            $settlements[] = [
                'from' => $debtor,
                'to' => $creditor,
                'amount' => round($payAmount, 2)
            ];
            $cData['net'] -= $payAmount;
            $amountOwed -= $payAmount;
        }
    }

    return $settlements;
}
function getSurpluses($balances): array {
    $surpluses = [];
    foreach ($balances as $name => $data) {
        if ($data['net'] > 0) {
            $surpluses[$name] = $data['net'];
        }
    }
    return $surpluses;
}

function getDeficits($balances): array {
    $deficits = [];
    foreach ($balances as $name => $data) {
        if ($data['net'] < 0) {
            $deficits[$name] = $data['net'];
        }
    }
    return $deficits;
}
function categorizeMembers(array $balances): array {
    $result = [
        'فائض' => [],
        'عليه دفع' => [],
        'متساوي' => []
    ];

    foreach ($balances as $name => $balance) {
        $status = $balance['status'] ?? 'متساوي';
        $result[$status][] = $name;
    }

    return $result;
}

?>