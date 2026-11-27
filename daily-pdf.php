<?php
require __DIR__ . '/auth.php';
requirePdfLibrary();

$config = appConfig();
$data = loadData();
$daily_expenses = dailyExpensesWithDetails($data['expenses']);
$cur = h($config['currency']);

// إنشاء كائن TCPDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// إعدادات المستند
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('مطور التطبيق');
$pdf->SetTitle('تقرير المصروفات اليومية المفصل');
$pdf->SetSubject('تقرير يومي');
$pdf->SetKeywords('تقرير, مصروفات, PDF');

// إزالة الهيدر والفوتر الافتراضيين
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// إعداد الخط الافتراضي
$pdf->SetFont('dejavusans', '', 12);

// إضافة صفحة
$pdf->AddPage();

// محتوى الصفحة (جدول مفصل حسب اليوم والأنواع)
$html = '
    <h2 style="text-align: center;">تقرير المصروفات اليومية المفصل</h2>
    <table border="1" cellpadding="6" style="width: 100%; border-collapse: collapse; text-align: right; font-size:13px;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th style="text-align: center;">التاريخ</th>
                <th style="text-align: center;">الإجمالي</th>
                <th style="text-align: center;">تفاصيل الأنواع</th>
            </tr>
        </thead>
        <tbody>';

// إضافة بيانات المصروفات اليومية
foreach ($daily_expenses as $date => $row) {
    // تفصيل الأنواع
    $details = '';
    foreach ($row['details'] as $type => $amount) {
        $details .= '<div><span style="font-weight:bold;">' . h($type) . ':</span> '
                  . number_format($amount, 2) . ' ' . $cur . '</div>';
    }
    $html .= '<tr>
                <td style="text-align: center; vertical-align:top;">' . h($date) . '</td>
                <td style="text-align: center; vertical-align:top;">' . number_format($row['total'], 2) . ' ' . $cur . '</td>
                <td style="vertical-align:top;">' . $details . '</td>
              </tr>';
}

$html .= '
        </tbody>
    </table>';

// كتابة المحتوى إلى ملف PDF
$pdf->writeHTML($html, true, false, true, false, '');

// تصدير ملف PDF
$pdf->Output('تقرير_المصروفات_اليومية_المفصل.pdf', 'D');
