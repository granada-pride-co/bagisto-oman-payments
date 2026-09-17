<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thawani Pay Simulator</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Cairo', sans-serif; }
        body { background: #f0fdf4; padding: 24px 16px; color: #1e293b; }
        .box { max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #bbf7d0; }
        .logo { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
        .logo-text { font-size: 1.3rem; font-weight: 800; color: #15803d; display: flex; align-items: center; gap: 8px; }
        .badge { background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .amount-row { display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; }
        .amount-val { font-size: 1.25rem; font-weight: 700; color: #0f172a; }
        .field { margin-bottom: 14px; }
        .field label { display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 6px; color: #475569; }
        .field input { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; }
        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .btn-pay { width: 100%; background: #16a34a; color: #ffffff; border: none; padding: 12px; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: background 0.2s; margin-top: 10px; }
        .btn-pay:hover { background: #15803d; }
        .btn-fail { width: 100%; background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; padding: 10px; border-radius: 10px; font-size: 0.85rem; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .thawani-qr { text-align: center; padding: 16px; background: #fafafa; border-radius: 10px; margin-bottom: 16px; border: 1px dashed #cbd5e1; }
        .qr-placeholder { font-size: 3rem; margin-bottom: 6px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="logo">
            <div class="logo-text">
                <span>🟢</span>
                <span>ثواني باي (Thawani Pay)</span>
            </div>
            <span class="badge">محاكاة معتمدة</span>
        </div>

        <div class="amount-row">
            <span>المبلغ المستحق:</span>
            <span class="amount-val">{{ number_format($amount, 3) }} {{ $currency }}</span>
        </div>

        <div class="thawani-qr">
            <div class="qr-placeholder">📱</div>
            <p style="font-size: 0.8rem; color: #64748b;">امسح بالباركود عبر تطبيق ثواني أو ادفع بالبطاقة أدناه</p>
        </div>

        <form id="sim-form">
            <div class="field">
                <label>رقم البطاقة (عمان نت / فيزا / ماستركارد):</label>
                <input type="text" value="5241 •••• •••• 8892" readonly>
            </div>

            <div class="field-row">
                <div class="field">
                    <label>تاريخ الانتهاء:</label>
                    <input type="text" value="12/28" readonly>
                </div>
                <div class="field">
                    <label>رمز الأمان CVV:</label>
                    <input type="text" value="382" readonly>
                </div>
            </div>

            <div class="field">
                <label>اسم حامل البطاقة:</label>
                <input type="text" value="Oman Customer" readonly>
            </div>

            <button type="button" class="btn-pay" onclick="submitSuccess()">
                ✓ تأكيد الدفع عبر ثواني (محاكاة ناجحة)
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
                transaction_id: 'thw_tx_' + Date.now(),
                card_type: 'Thawani Wallet / OmanNet'
            };

            // Notify parent window via postMessage
            if (window.parent && window.parent !== window) {
                window.parent.postMessage(data, '*');
            } else {
                window.location.href = "{{ $callbackUrl }}?session_id={{ $sessionId }}&card_type=Thawani";
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
