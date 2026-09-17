<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $gatewayTitle }} - @lang('oman_payments::app.title')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0f766e;
            --primary-dark: #115e59;
            --bg-page: #f8fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #64748b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: {{ app()->getLocale() == 'ar' ? "'Cairo', sans-serif" : "'Inter', sans-serif" }};
        }

        body {
            background: var(--bg-page);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 24px 16px;
        }

        .checkout-container {
            max-width: 820px;
            width: 100%;
            margin: 0 auto;
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.08), 0 0 1px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--border-color);
        }

        .header-bar {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
        }

        .gateway-branding {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .gateway-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, #0d9488 0%, #065f46 100%);
            box-shadow: 0 4px 10px rgba(13, 148, 136, 0.3);
        }

        .gateway-details h1 {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .gateway-details p {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .amount-badge {
            text-align: {{ app()->getLocale() == 'ar' ? 'left' : 'right' }};
        }

        .amount-badge .label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .amount-badge .value {
            font-size: 1.35rem;
            font-weight: 800;
            color: #047857;
        }

        .simulation-banner {
            background: #fef3c7;
            color: #92400e;
            padding: 10px 20px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #fde68a;
            font-weight: 600;
        }

        .simulation-banner svg {
            flex-shrink: 0;
            width: 20px;
            height: 20px;
        }

        .iframe-wrapper {
            position: relative;
            width: 100%;
            min-height: 640px;
            background: #fdfdfd;
        }

        .loader-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
            transition: opacity 0.3s ease;
        }

        .spinner {
            width: 48px;
            height: 48px;
            border: 4px solid #e2e8f0;
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loader-text {
            margin-top: 16px;
            font-size: 0.95rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        iframe#payment-iframe {
            width: 100%;
            height: 660px;
            border: none;
            display: block;
        }

        .footer-bar {
            padding: 16px 24px;
            border-top: 1px solid var(--border-color);
            background: #fafafa;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .security-badges {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .badge-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .badge-item svg {
            width: 18px;
            height: 18px;
            color: #10b981;
        }

        .btn-cancel {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 0.85rem;
            color: #ef4444;
            background: #fef2f2;
            text-decoration: none;
            font-weight: 600;
            border: 1px solid #fee2e2;
            transition: all 0.2s ease;
        }

        .btn-cancel:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        @media (max-width: 640px) {
            body {
                padding: 8px;
            }
            .header-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            .amount-badge {
                text-align: inherit;
            }
            .iframe-wrapper {
                min-height: 560px;
            }
            iframe#payment-iframe {
                height: 580px;
            }
        }
    </style>
</head>
<body>

    <div class="checkout-container">
        <!-- Header -->
        <header class="header-bar">
            <div class="gateway-branding">
                <div class="gateway-icon">
                    <span>🇴🇲</span>
                </div>
                <div class="gateway-details">
                    <h1>{{ $gatewayTitle }}</h1>
                    <p>@lang('oman_payments::app.security_badge')</p>
                </div>
            </div>

            <div class="amount-badge">
                <div class="label">{{ __('Total Payable') }}</div>
                <div class="value">{{ number_format($amount, 3) }} {{ $currency }}</div>
            </div>
        </header>

        @if($isSimulation)
        <div class="simulation-banner">
            <svg fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
            </svg>
            <span>@lang('oman_payments::app.simulation_notice')</span>
        </div>
        @endif

        <!-- iFrame Container -->
        <div class="iframe-wrapper">
            <div id="loader" class="loader-overlay">
                <div class="spinner"></div>
                <div class="loader-text">@lang('oman_payments::app.processing')</div>
            </div>

            <iframe
                id="payment-iframe"
                src="{{ $iframeUrl }}"
                title="{{ $gatewayTitle }}"
                allow="payment"
                sandbox="allow-scripts allow-same-origin allow-forms allow-top-navigation-by-user-activation allow-popups"
            ></iframe>
        </div>

        <!-- Footer -->
        <footer class="footer-bar">
            <div class="security-badges">
                <div class="badge-item">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <span>CBO / OmanNet Verified</span>
                </div>
                <div class="badge-item">
                    <span>🔒 SSL 256-bit</span>
                </div>
            </div>

            <a href="{{ route('oman_payments.cancel', ['gateway' => $gateway, 'session_id' => $sessionId]) }}" class="btn-cancel">
                <span>✕</span>
                <span>@lang('oman_payments::app.cancel_payment')</span>
            </a>
        </footer>
    </div>

    <!-- Script for iFrame loading and postMessage listener -->
    <script>
        const iframe = document.getElementById('payment-iframe');
        const loader = document.getElementById('loader');

        // Hide loader when iframe finishes loading
        iframe.addEventListener('load', function() {
            setTimeout(() => {
                if (loader) {
                    loader.style.opacity = '0';
                    setTimeout(() => loader.style.display = 'none', 300);
                }
            }, 300);
        });

        // Listen for postMessage from payment iframe
        window.addEventListener('message', function(event) {
            // Check message payload
            if (!event.data) return;

            const data = event.data;

            if (data.type === 'OMAN_PAYMENT_SUCCESS' || data.status === 'success') {
                if (loader) {
                    loader.style.display = 'flex';
                    loader.style.opacity = '1';
                    document.querySelector('.loader-text').innerText = 'تم تأكيد الدفع بنجاح، جاري إنشاء الطلب...';
                }

                const params = new URLSearchParams({
                    session_id: data.session_id || '{{ $sessionId }}',
                    transaction_id: data.transaction_id || '{{ $sessionId }}',
                    card_type: data.card_type || 'OmanNet',
                    amount: '{{ $amount }}'
                });

                window.location.href = "{{ route('oman_payments.callback', ['gateway' => $gateway]) }}?" + params.toString();
            } else if (data.type === 'OMAN_PAYMENT_CANCEL' || data.status === 'cancel') {
                window.location.href = "{{ route('oman_payments.cancel', ['gateway' => $gateway, 'session_id' => $sessionId]) }}";
            }
        });
    </script>
</body>
</html>
