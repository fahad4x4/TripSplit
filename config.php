<?php
/**
 * TripSplit — الإعدادات العامة (مرفوعة إلى Git).
 *
 * - data_file            : مسار ملف البيانات نسبةً لمجلد المشروع (محمي داخل data/).
 * - currency             : اسم عملة الرحلة كما يظهر في الواجهة والتقارير.
 * - exchange_rate_to_sar : سعر تحويل وحدة واحدة من عملة الرحلة إلى الريال السعودي
 *                          (يُستخدم لعرض المبلغ التقريبي بالريال فقط، لا يؤثر على الحسابات).
 * - admin_name           : الاسم الذي يظهر في ختام تقرير PDF.
 *
 * كلمة المرور لا توضع هنا: ضعها كـ hash في config.local.php (انظر config.local.example.php).
 */
return [
    'data_file'            => 'data/expenses.json',
    'currency'             => 'درهم مغربي',
    'exchange_rate_to_sar' => 0.41,
    'admin_name'           => 'أبو فيصل',
];
