@php
    $appName = App\Models\AppSetting::getValue('app_name', 'Swado');
    $primaryColor = App\Models\AppSetting::getValue('primary_color', '#FC8019');
    $type = $type ?? 'terms';
    $settings = $settings ?? [];

    $defaultTerms = "1. Introduction\n"
        . "Welcome to {$appName}. By downloading our application or accessing our website, you agree to comply with and be bound by the following terms of service.\n\n"
        . "2. Marketplace Platform\n"
        . "{$appName} operates as a hyperlocal technology platform connecting customers to independent merchant kitchens and delivery contractors.\n\n"
        . "3. Orders and Pricing\n"
        . "Prices displayed are set by our restaurant partners. While we strive to ensure accurate pricing, taxes and delivery surcharges are clearly specified before checkout.";

    $defaultPrivacy = "1. Information We Collect\n"
        . "We collect information you provide directly to us when creating an account, placing food delivery orders, setting delivery addresses, or communicating with support.\n\n"
        . "2. How We Use Location Data\n"
        . "With your explicit permission, we access your device's GPS location to recommend nearby partner restaurants, calculate accurate delivery times, and route delivery executives efficiently.\n\n"
        . "3. Data Security\n"
        . "We maintain industry-standard security protocols to protect your sensitive personal data and payment transactions against unauthorized access.";

    $defaultRefund = "Refund eligibility depends on payment status, restaurant acceptance, delivery progress and support review. Orders can typically be cancelled shortly after placement for a full refund; later-stage cancellations may receive a partial refund based on preparation progress.";

    $defaultMerchant = "Partner restaurants must maintain valid food safety licenses, accurate menu and pricing information, and timely order acceptance. Non-compliance may result in listing suspension.";

    $termsText = $settings['legal_terms'] ?? $defaultTerms;
    $privacyText = $settings['legal_privacy'] ?? $defaultPrivacy;
    $refundText = $settings['legal_refund'] ?? $defaultRefund;
    $merchantText = $settings['legal_merchant'] ?? $defaultMerchant;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legal & Policies - {{ $appName }}</title>
    @include('partials.seo-meta', [
        'metaTitle' => 'Legal & Policies - '.$appName,
        'metaDescription' => "Terms of service, privacy policy, and legal information for {$appName}.",
    ])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Nunito+Sans:wght@400;500;600;700;800;900&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Nunito+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"></noscript>
    <style>
        :root {
            --brand-orange: var(--primary, {{ $primaryColor }});
            --brand-dark: #02060c;
            --brand-muted: #686b78;
            --app-ink: #111827;
            --app-muted: #6B7280;
            --app-border: #E5E7EB;
            --app-bg: #FAFAFA;
        }
        body { font-family: 'Nunito Sans', sans-serif; background: #f8f8f9; color: var(--brand-dark); }
        h1, h2, h3, h4, h5, h6 { font-family: 'Plus Jakarta Sans', sans-serif; }
        .legal-header {
            background: #02060c;
            color: #ffffff;
            padding: 60px 0 40px;
        }
        .legal-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(40,44,63,0.06);
            padding: 40px;
            margin-top: -20px;
            margin-bottom: 60px;
        }
        .legal-nav-pill {
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            color: #3d4152;
            text-decoration: none;
            display: block;
            margin-bottom: 8px;
            transition: all 0.2s ease;
        }
        .legal-nav-pill:hover, .legal-nav-pill.active {
            background: #fff5ed;
            color: var(--brand-orange);
        }
    </style>
    @include('partials.public-blade-polish')
</head>
<body>
    @include('partials.google-tag-manager')
    @include('partials.frontend-header')

    <section class="legal-header text-center">
        <div class="container">
            <h1 class="display-6 fw-bold mb-2">Legal & Privacy Center</h1>
            <p class="text-secondary mb-0">Learn about our terms of service, customer privacy, and merchant compliance.</p>
        </div>
    </section>

    <div class="container">
        <div class="legal-card">
            <div class="row g-5">
                <div class="col-lg-3">
                    <div class="p-2 border rounded-4 bg-light">
                        <a href="{{ route('legal.terms') }}" class="legal-nav-pill @if($type === 'terms') active @endif">Terms of Service</a>
                        <a href="{{ route('legal.privacy') }}" class="legal-nav-pill @if($type === 'privacy') active @endif">Privacy Policy</a>
                        <a href="{{ route('legal.index') }}#cancellation" class="legal-nav-pill">Cancellation & Refunds</a>
                        <a href="{{ route('legal.index') }}#merchant" class="legal-nav-pill">Merchant Compliance</a>
                    </div>
                </div>
                <div class="col-lg-9">
                    @if($type === 'privacy')
                        <h2 class="fw-bold mb-4" id="privacy">Privacy Policy</h2>
                        <p class="text-muted">Last updated: {{ date('F Y') }}</p>
                        <hr class="my-4">
                        <p class="text-muted" style="white-space: pre-line;">{{ $privacyText }}</p>
                    @elseif($type === 'terms')
                        <h2 class="fw-bold mb-4" id="terms">Terms & Conditions of Service</h2>
                        <p class="text-muted">Last updated: {{ date('F Y') }}</p>
                        <hr class="my-4">
                        <p class="text-muted" style="white-space: pre-line;">{{ $termsText }}</p>
                    @else
                        <h2 class="fw-bold mb-3" id="terms">Terms & Conditions of Service</h2>
                        <p class="text-muted mb-4" style="white-space: pre-line;">{{ $termsText }}</p>

                        <h2 class="fw-bold mb-3 mt-5" id="privacy">Privacy Policy</h2>
                        <p class="text-muted mb-4" style="white-space: pre-line;">{{ $privacyText }}</p>

                        <h2 class="fw-bold mb-3 mt-5" id="cancellation">Cancellation & Refunds</h2>
                        <p class="text-muted mb-4" style="white-space: pre-line;">{{ $refundText }}</p>

                        <h2 class="fw-bold mb-3 mt-5" id="merchant">Merchant Compliance</h2>
                        <p class="text-muted" style="white-space: pre-line;">{{ $merchantText }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('partials.frontend-footer')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @include('partials.web-visit-tracker', ['panel' => 'frontend'])
</body>
</html>