<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paymob Oman Simulator</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Cairo', sans-serif; }
        body { background: #eff6ff; padding: 24px 16px; color: #1e293b; }
        .box { max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #bfdbfe; }
        .logo { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #2563eb; padding-bottom: 14px; }
        .logo-text { font-size: 1.25rem; font-weight: 800; color: #1d4ed8; display: flex; align-items: center; gap: 8px; }
        .badge { background: #dbeafe; color: #1e40af; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .cards-row { display: flex; gap: 8px; margin-bottom: 16px; }
        .card-pill { background: #f1f5f9; padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; color: #334155; }
        .amount-row { display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; border: 1px solid #e2e8f0; }
        .amount-val { font-size: 1.25rem; font-weight: 700; color: #1d4ed8; }
        .field { margin-bottom: 14px; }
        .field label { display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 6px; color: #475569; }
        .field input { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; }
        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .btn-pay { width: 100%; background: #2563eb; color: #ffffff; border: none; padding: 12px; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: background 0.2s; margin-top: 10px; }
        .btn-pay:hover { background: #1d4ed8; }
        .btn-fail { width: 100%; background: #f8fafc; color: #64748b; border: 1px solid #cbd5e1; padding: 10px; border-radius: 10px; font-size: 0.85rem; font-weight: 600; cursor: pointer; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="logo">
            <div class="logo-text">
                <span>💳</span>
                <span>باي موب عمان (Paymob Oman)</span>
            </div>
            <span class="badge">iFrame Hosted</span>
        </div>

        <div class="cards-row">
            <span class="card-pill">🇴🇲 عمان نت</span>
            <span class="card-pill">VISA</span>
            <span class="card-pill">Mastercard</span>
        </div>

        <div class="amount-row">
            <span>المبلغ المستحق:</span>
            <span class="amount-val">{{ number_format($amount, 3) }} {{ $currency }}</span>
        </div>

        <form id="sim-form">
            <div class="field">
                <label>رقم البطاقة:</label>
                <input type="text" value="4000 •••• •••• 1122" readonly>
            </div>

            <div class="field-row">
                <div class="field">
                    <label>تاريخ الانتهاء:</label>
                    <input type="text" value="11/30" readonly>
                </div>
                <div class="field">
                    <label>رمز الأمان CVV:</label>
                    <input type="text" value="902" readonly>
                </div>
            </div>

            <button type="button" class="btn-pay" onclick="submitSuccess()">
                ✓ تأكيد الدفع عبر باي موب (محاكاة ناجحة)
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
                transaction_id: 'paymob_tx_' + Date.now(),
                card_type: 'Paymob OmanNet / Card'
            };

            if (window.parent && window.parent !== window) {
                window.parent.postMessage(data, '*');
            } else {
                window.location.href = "{{ $callbackUrl }}?session_id={{ $sessionId }}&card_type=Paymob";
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
