<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Muscat SmartPay Simulator</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Cairo', sans-serif; }
        body { background: #fff5f5; padding: 24px 16px; color: #1e293b; }
        .box { max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #fecaca; }
        .logo { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #881b22; padding-bottom: 14px; }
        .logo-text { font-size: 1.25rem; font-weight: 800; color: #881b22; display: flex; align-items: center; gap: 8px; }
        .badge { background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .amount-row { display: flex; justify-content: space-between; align-items: center; background: #faf5f5; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; border: 1px solid #fee2e2; }
        .amount-val { font-size: 1.25rem; font-weight: 700; color: #881b22; }
        .card-preview { background: linear-gradient(135deg, #881b22 0%, #4c0d12 100%); color: #fff; padding: 18px 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(136, 27, 34, 0.35); position: relative; }
        .card-chip { width: 34px; height: 26px; background: #fbbf24; border-radius: 4px; margin-bottom: 12px; }
        .card-num { font-size: 1.15rem; letter-spacing: 2px; font-family: monospace; margin-bottom: 10px; }
        .card-info { display: flex; justify-content: space-between; font-size: 0.8rem; opacity: 0.9; }
        .field { margin-bottom: 14px; }
        .field label { display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 6px; color: #475569; }
        .field input { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; }
        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .btn-pay { width: 100%; background: #881b22; color: #ffffff; border: none; padding: 12px; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: background 0.2s; margin-top: 10px; }
        .btn-pay:hover { background: #6f151b; }
        .btn-fail { width: 100%; background: #f8fafc; color: #64748b; border: 1px solid #cbd5e1; padding: 10px; border-radius: 10px; font-size: 0.85rem; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .security-badge { text-align: center; font-size: 0.75rem; color: #64748b; margin-top: 14px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="logo">
            <div class="logo-text">
                <span>🏛️</span>
                <span>بنك مسقط SmartPay (MPGS)</span>
            </div>
            <span class="badge">MPGS Hosted</span>
        </div>

        <div class="card-preview">
            <div class="card-chip"></div>
            <div class="card-num">4111 •••• •••• 9210</div>
            <div class="card-info">
                <span>OmanNet / Visa</span>
                <span>EXP: 09/29</span>
            </div>
        </div>

        <div class="amount-row">
            <span>المبلغ المستحق:</span>
            <span class="amount-val">{{ number_format($amount, 3) }} {{ $currency }}</span>
        </div>

        <form id="sim-form">
            <div class="field-row">
                <div class="field">
                    <label>رمز الأمان 3D Secure OTP:</label>
                    <input type="text" value="739102" readonly>
                </div>
                <div class="field">
                    <label>نوع الحساب:</label>
                    <input type="text" value="بطاقة خصم عمان نت" readonly>
                </div>
            </div>

            <button type="button" class="btn-pay" onclick="submitSuccess()">
                ✓ تأكيد الدفع عبر بنك مسقط (محاكاة ناجحة)
            </button>

            <button type="button" class="btn-fail" onclick="submitCancel()">
                ✕ إلغاء المعاملة
            </button>
        </form>

        <div class="security-badge">
            Mastercard Payment Gateway Services (MPGS) • تشفير متوافق مع بنك مسقط
        </div>
    </div>

    <script>
        function submitSuccess() {
            const data = {
                type: 'OMAN_PAYMENT_SUCCESS',
                status: 'success',
                session_id: '{{ $sessionId }}',
                transaction_id: 'BM_TXN_' + Date.now(),
                card_type: 'Bank Muscat OmanNet / Visa'
            };

            if (window.parent && window.parent !== window) {
                window.parent.postMessage(data, '*');
            } else {
                window.location.href = "{{ $callbackUrl }}?session_id={{ $sessionId }}&card_type=BankMuscat";
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
