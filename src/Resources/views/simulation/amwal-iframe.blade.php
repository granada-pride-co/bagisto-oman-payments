<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AmwalPay SmartBox Simulator</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Cairo', sans-serif; }
        body { background: #f0fdfa; padding: 24px 16px; color: #1e293b; }
        .box { max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #99f6e4; }
        .logo { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #0d9488; padding-bottom: 14px; }
        .logo-text { font-size: 1.25rem; font-weight: 800; color: #0f766e; display: flex; align-items: center; gap: 8px; }
        .badge { background: #ccfbf1; color: #115e59; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .omannet-banner { display: flex; align-items: center; justify-content: space-between; background: #e0f2fe; border: 1px solid #bae6fd; padding: 10px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 0.85rem; font-weight: 600; color: #0369a1; }
        .amount-row { display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; }
        .amount-val { font-size: 1.25rem; font-weight: 700; color: #0f766e; }
        .field { margin-bottom: 14px; }
        .field label { display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 6px; color: #475569; }
        .field input { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; }
        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .btn-pay { width: 100%; background: #0d9488; color: #ffffff; border: none; padding: 12px; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: background 0.2s; margin-top: 10px; }
        .btn-pay:hover { background: #0f766e; }
        .btn-fail { width: 100%; background: #f8fafc; color: #64748b; border: 1px solid #cbd5e1; padding: 10px; border-radius: 10px; font-size: 0.85rem; font-weight: 600; cursor: pointer; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="logo">
            <div class="logo-text">
                <span>💠</span>
                <span>أموال باي (Amwal SmartBox)</span>
            </div>
            <span class="badge">عمان نت مباشر</span>
        </div>

        <div class="omannet-banner">
            <span>🇴🇲 بوابة بطاقات الخصم المباشر الوطنية (عمان نت)</span>
            <span>CBO</span>
        </div>

        <div class="amount-row">
            <span>المبلغ المستحق:</span>
            <span class="amount-val">{{ number_format($amount, 3) }} {{ $currency }}</span>
        </div>

        <form id="sim-form">
            <div class="field">
                <label>رقم بطاقة الخصم (OmanNet Debit):</label>
                <input type="text" value="4591 •••• •••• 1045" readonly>
            </div>

            <div class="field-row">
                <div class="field">
                    <label>تاريخ الانتهاء:</label>
                    <input type="text" value="08/27" readonly>
                </div>
                <div class="field">
                    <label>رمز الأمان CVV:</label>
                    <input type="text" value="119" readonly>
                </div>
            </div>

            <button type="button" class="btn-pay" onclick="submitSuccess()">
                ✓ تأكيد الدفع عبر أموال باي (محاكاة ناجحة)
            </button>

            <button type="button" class="btn-fail" onclick="submitCancel()">
                ✕ إلغاء المعاملة
            </button>
        </form>
    </div>

    <script>
        function submitSuccess() {
            const data = {
                type: 'OMAN_PAYMENT_SUCCESS',
                status: 'success',
                session_id: '{{ $sessionId }}',
                transaction_id: 'amwal_tx_' + Date.now(),
                card_type: 'OmanNet Debit Card'
            };

            if (window.parent && window.parent !== window) {
                window.parent.postMessage(data, '*');
            } else {
                window.location.href = "{{ $callbackUrl }}?session_id={{ $sessionId }}&card_type=OmanNet";
            }
        }

        function submitCancel() {
            const data = {
                type: 'OMAN_PAYMENT_CANCEL',
                status: 'cancel',
                session_id: '{{ $sessionId }}'
            };

            if (window.parent && window.parent !== window) {
                window.parent.postMessage(data, '*');
            } else {
                window.location.href = "{{ $cancelUrl }}";
            }
        }
    </script>
</body>
</html>
