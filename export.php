<?php
require __DIR__ . '/auth.php';
requirePdfLibrary();

function formatAmount($amount) {
    return number_format((float) $amount, 2, '.', ',');
}

$config = appConfig();
$data = loadData();
$team = $data['team'];
$expenses = $data['expenses'];
$contributions = $data['contributions'];
$total = totalExpenses($expenses);
$kitty = kittyBalance($expenses, $contributions);
$balances = calculateBalances($team, $expenses, $contributions);
$settlement = computeSettlement($balances);
$transactions = $settlement['transactions'];

ob_start();

// إنشاء كائن TCPDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 15);
$pdf->setRTL(true);
$pdf->SetFont('aealarabiya', '', 13);

// دالة لإنشاء الجداول
function createTable($pdf, $headers, $rows, $fillColor = [224, 224, 224]) {
    $pdf->SetFillColor(...$fillColor);
    $html = '<table border="0.5" cellpadding="6">';
    $html .= '<thead><tr style="background-color:#f5f5f5;">';
    foreach ($headers as $header) {
        $html .= '<th>' . h($header) . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td>' . h($cell) . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    $pdf->writeHTML($html, true, false, true, false, '');
}

// الغلاف
$pdf->AddPage();
$pdf->SetTextColor(42, 92, 141);
$pdf->SetFont('aealarabiya', 'B', 20);
$pdf->Cell(0, 15, 'تقرير مصروفات الرحلة', 0, 1, 'C');
$pdf->SetFont('aealarabiya', '', 12);
$pdf->SetTextColor(0);
$pdf->Ln(5);
$logo = 'https://alghathbar.com/url/Fahad-logo-Black-1080-200.png';
$pdf->Image($logo, 180, 18, 22);

$pdf->MultiCell(0, 10, 'التقرير المفصل لجميع مصروفات ومساهمات الرحلة والأرصدة النهائية.', 0, 'C');
$pdf->Ln(5);
$pdf->Cell(0, 10, 'التاريخ: ' . date('Y/m/d'), 0, 1, 'C');
$pdf->Ln(10);

// سجل المصروفات
$pdf->SetFont('aealarabiya', 'B', 12);
$pdf->SetFillColor(42, 92, 141); // تغيير لون الخلفية إلى الأزرق الغامق
$pdf->Cell(0, 10, 'سجل المصروفات', 0, 1, 'C', true);
$pdf->Ln(0);

$expenseRows = [];
foreach ($expenses as $e) {
    $expenseRows[] = [
        payerLabel($e),
        formatAmount($e['amount']) . ' ' . $config['currency'],
        (string) ($e['note'] ?? ''),
        safeDate($e['date'] ?? '', 'd/m/Y')
    ];
}
createTable($pdf, ['الدافع', 'المبلغ', 'نوع المصروف', 'التاريخ'], $expenseRows);
$pdf->Ln(0);

// سجل المساهمات
$pdf->SetFont('aealarabiya', 'B', 12);
$pdf->SetFillColor(42, 92, 141); // تغيير لون الخلفية إلى الأزرق الغامق
$pdf->Cell(0, 10, 'سجل المساهمات', 0, 1, 'C', true);
$pdf->Ln(0);

$contributionRows = [];
foreach ($contributions as $c) {
    $contributionRows[] = [
        (string) ($c['name'] ?? ''),
        formatAmount($c['amount']) . ' ' . $config['currency'],
        safeDate($c['date'] ?? '', 'd/m/Y')
    ];
}
createTable($pdf, ['الاسم', 'المبلغ', 'التاريخ'], $contributionRows);
$pdf->Ln(0);

// الرصيد النهائي
$pdf->SetFont('aealarabiya', 'B', 12);
$pdf->SetFillColor(42, 92, 141); // تغيير لون الخلفية إلى الأزرق الغامق
$pdf->Cell(0, 10, 'الرصيد النهائي لكل مشارك', 0, 1, 'C', true);
$pdf->Ln(0);

$balanceRows = [];
foreach ($balances as $name => $b) {
    $sar = $b['net'] * $config['exchange_rate_to_sar'];
    $balanceRows[] = [
        $name . ' - ' . $b['status'],
        formatAmount($b['net']),
        formatAmount($sar)
    ];
}
createTable($pdf, ['الاسم', 'الرصيد (' . $config['currency'] . ')', 'الرصيد بالريال'], $balanceRows);
createTable($pdf, ['رصيد ' . KITTY . ' (' . $config['currency'] . ')', 'المساهمات', 'المصروف منه'], [[
    formatAmount($kitty['balance']),
    formatAmount($kitty['contributions']),
    formatAmount($kitty['spent']),
]]);
$pdf->Ln(0);

// التحويلات المطلوبة
$pdf->SetFont('aealarabiya', 'B', 12);
$pdf->SetFillColor(42, 92, 141); // تغيير لون الخلفية إلى الأزرق الغامق
$pdf->Cell(0, 10, 'التحويلات المطلوبة بين الأعضاء', 0, 1, 'C', true);
$pdf->Ln(0);

$transactionRows = [];
if (!empty($transactions)) {
    foreach ($transactions as $t) {
        $sar = $t['amount'] * $config['exchange_rate_to_sar'];
        $transactionRows[] = [
            $t['from'],
            $t['to'],
            formatAmount($t['amount']) . ' ' . $config['currency'] . ' (' . formatAmount($sar) . ' ريال)'
        ];
    }
} else {
    $transactionRows[] = ['لا توجد تحويلات مطلوبة', '', ''];
}
foreach ($settlement['kitty_refunds'] as $n => $amt) {
    $transactionRows[] = [KITTY . ' (فائض)', $n, formatAmount($amt) . ' ' . $config['currency']];
}
foreach ($settlement['kitty_dues'] as $n => $amt) {
    $transactionRows[] = [$n, KITTY . ' (عجز)', formatAmount($amt) . ' ' . $config['currency']];
}
createTable($pdf, ['من', 'إلى', 'المبلغ'], $transactionRows);

// ختام التقرير
$pdf->Ln(0);
$pdf->SetFont('aealarabiya', '', 13);
$pdf->MultiCell(0, 10, "تم إعداد هذا التقرير بكل دقة لضمان الشفافية بين المشاركين.\nنتمنى لكم رحلات موفقة وسعيدة دائمًا.", 0, 'C');
$pdf->Ln(0);
$pdf->SetFont('aealarabiya', 'B', 15);
$pdf->Cell(0, 10, 'مع تحيات - ' . $config['admin_name'], 0, 1, 'C');

ob_end_clean();
$pdf->Output('trip_report_optimized.pdf', 'I');
exit;
