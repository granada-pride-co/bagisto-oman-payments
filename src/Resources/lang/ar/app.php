<?php

return [
    'title' => 'بوابات الدفع العمانية',
    'security_badge' => 'مدفوعات آمنة ومحمية بتشفير 256-bit بمعايير البنك المركزي العُماني (CBO)',
    'powered_by' => 'نظام الدفع الوطني العُماني',
    'omannet_protected' => 'متوافق مع بطاقات عمان نت (OmanNet) وفيزا وماستركارد',
    'simulation_notice' => 'وضع المحاكاة التجريبي مفعل - يمكنك تجربة الدفع بالضغط على أحد أزرار الاختبار داخل الـ iFrame دون خصم حقيقي.',
    'cancel_payment' => 'إلغاء والعودة للمتجر',
    'processing' => 'جاري تجهيز بوابة الدفع...',

    'fields' => [
        'simulation_mode' => 'وضع المحاكاة الذكي (Simulation Mode)',
        'simulation_mode_info' => 'عند التفعيل، يتم عرض شاشة دفع وهمية تفاعلية داخل الـ iFrame لتجربة دورة الدفع كاملة بدون الحاجة لمفاتيح بنكية حقيقية.',
    ],

    'thawani' => [
        'title' => 'ثواني باي (Thawani Pay)',
        'description' => 'ادفع بأمان عبر محفظة ثواني الإلكترونية أو بالبطاقات البنكية العمانية مباشرة داخل المتجر.',
        'system_title' => 'بوابة ثواني (Thawani Pay)',
        'system_info' => 'بوابة دفع عمانية مرخصة من البنك المركزي العُماني CBO.',
        'public_key' => 'المفتاح العام (Publishable Key)',
        'secret_key' => 'المفتاح السري (Secret Key)',
    ],

    'bankmuscat' => [
        'title' => 'بنك مسقط - سمارت باي (Bank Muscat SmartPay)',
        'description' => 'ادفع مباشرة عبر بوابة الدفع الإلكتروني لبنك مسقط المشغلة بنظام MPGS الآمن.',
        'system_title' => 'بنك مسقط SmartPay (MPGS)',
        'system_info' => 'بوابة الدفع الآمنة لأكبر بنك في سلطنة عمان بنظام Hosted Checkout iFrame.',
        'merchant_id' => 'معرف التاجر (Merchant ID)',
        'api_password' => 'كلمة مرور الـ API (API Password)',
        'gateway_host' => 'رابط خادم البوابة (Gateway Host)',
        'gateway_host_info' => 'الافتراضي: bankmuscat.gateway.mastercard.com',
        'api_version' => 'إصدار الـ API (API Version)',
    ],

    'amwal' => [
        'title' => 'أموال باي - بطاقات عمان نت (AmwalPay - OmanNet)',
        'description' => 'ادفع ببطاقات الخصم المباشر (عمان نت) أو البطاقات الائتمانية عبر نافذة أموال باي الذكية.',
        'system_title' => 'أموال باي (AmwalPay)',
        'system_info' => 'بوابة دفع إلكترونية عمانية مرخصة تدعم شبكة بطاقات عمان نت مباشرة.',
        'merchant_id' => 'معرف التاجر (Merchant ID)',
        'terminal_id' => 'معرف نقطة البيع (Terminal ID)',
        'secret_key' => 'المفتاح السري لإنشاء Secure Hash',
    ],

    'paymob' => [
        'title' => 'باي موب عمان (Paymob Oman)',
        'description' => 'الدفع السريع لبطاقات عمان نت والبطاقات الدولية عبر بوابة باي موب المضمنة.',
        'system_title' => 'باي موب عمان (Paymob Oman)',
        'system_info' => 'بوابة دفع تدعم البطاقات المحلية والدولية داخل إطار iFrame مدمج.',
        'api_key' => 'مفتاح الـ API (API Key)',
        'integration_id' => 'معرف التكامل (Integration ID)',
        'iframe_id' => 'معرف الـ iFrame (iFrame ID)',
        'hmac_secret' => 'المفتاح السري لـ HMAC',
    ],

    'messages' => [
        'invalid_gateway' => 'بوابة الدفع المحددة غير صالحة أو غير مفعلة.',
        'cart_empty' => 'سلة التسوق فارغة، يرجى إضافة منتجات للمتابعة.',
        'session_error' => 'تعذر إنشاء جلسة دفع مع البوابة. يرجى مراجعة إعدادات البوابة.',
        'generic_error' => 'حدث خطأ أثناء معالجة عملية الدفع. يرجى المحاولة لاحقاً.',
        'invalid_transaction' => 'معرف المعاملة غير صالح أو منتهي الصلاحية.',
        'payment_failed' => 'فشلت عملية الدفع أو تم رفض البطاقة من قبل البنك.',
        'cart_expired' => 'انتهت صلاحية السلة، يرجى إعادة المحاولة.',
        'payment_cancelled' => 'تم إلغاء عملية الدفع من قِبل العميل.',
    ],
];
