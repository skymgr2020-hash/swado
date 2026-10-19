@php
    $appName = App\Models\AppSetting::getValue('app_name', 'Swado');
    $primaryColor = App\Models\AppSetting::getValue('primary_color', '#FC8019');
    $contactEmail = App\Models\AppSetting::getValue('contact_email', 'support@swado.com');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help & Support - {{ $appName }}</title>
    @include('partials.seo-meta', [
        'metaTitle' => 'Help & Support - '.$appName,
        'metaDescription' => "Find answers and get support for your {$appName} orders, payments, refunds, and account.",
        'canonicalUrl' => route('help'),
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
        body { font-family: 'Nunito Sans', sans-serif; background: #f0f0f5; color: var(--brand-dark); }
        h1, h2, h3, h4, h5, h6 { font-family: 'Plus Jakarta Sans', sans-serif; }
        .help-header {
            background: #02060c;
            color: #ffffff;
            padding: 50px 0 40px;
        }
        .help-container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(40,44,63,0.06);
            overflow: hidden;
            margin-top: -30px;
            margin-bottom: 60px;
        }
        .help-nav-item {
            padding: 18px 24px;
            border-bottom: 1px solid #f0f0f5;
            cursor: pointer;
            font-weight: 600;
            color: #3d4152;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .help-nav-item:hover, .help-nav-item.active {
            background: #fff5ed;
            color: var(--brand-orange);
            border-left: 4px solid var(--brand-orange);
        }
        .help-accordion-item {
            border-bottom: 1px solid #f0f0f5;
            padding: 20px 0;
        }
    </style>
    @include('partials.public-blade-polish')
</head>
<body>
    @include('partials.google-tag-manager')
    @include('partials.frontend-header')

    <section class="help-header text-center">
        <div class="container">
            <h1 class="fw-bold mb-2">Help & Support</h1>
            <p class="text-secondary mb-0">Let's take a step ahead and help you solve any issues quickly.</p>
        </div>
    </section>

    <div class="container">
        <div class="help-container">
            <div class="row g-0">
                <!-- Left Nav Tabs -->
                <div class="col-lg-4 border-end bg-light">
                    <div class="p-3 fw-bold text-uppercase small text-muted">Help Topics</div>
                    <div class="help-nav-item active" onclick="switchHelpTab('partner', this)">
                        <span>Partner Onboarding</span>
                        <i class="fas fa-chevron-right small text-muted"></i>
                    </div>
                    <div class="help-nav-item" onclick="switchHelpTab('legal', this)">
                        <span>Legal, Terms & Conditions</span>
                        <i class="fas fa-chevron-right small text-muted"></i>
                    </div>
                    <div class="help-nav-item" onclick="switchHelpTab('faqs', this)">
                        <span>FAQs</span>
                        <i class="fas fa-chevron-right small text-muted"></i>
                    </div>
                    <div class="help-nav-item" onclick="switchHelpTab('orders', this)">
                        <span>Orders & Delivery</span>
                        <i class="fas fa-chevron-right small text-muted"></i>
                    </div>
                    <div class="help-nav-item" onclick="switchHelpTab('payments', this)">
                        <span>Payments & Refunds</span>
                        <i class="fas fa-chevron-right small text-muted"></i>
                    </div>
                </div>

                <!-- Right Content Pane -->
                <div class="col-lg-8 p-4 p-md-5 bg-white">
                    <div id="helpTabContent">
                        <h3 class="fw-bold mb-4" id="helpTabTitle">Partner Onboarding</h3>
                        
                        <div class="accordion accordion-flush" id="helpAccordion">
                            <div class="accordion-item help-accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-bold px-0 bg-transparent text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#q1">
                                        What are the mandatory documents needed to list my restaurant?
                                    </button>
                                </h2>
                                <div id="q1" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                    <div class="accordion-body px-0 text-muted">
                                        To register your restaurant on {{ $appName }}, you need: (1) FSSAI Registration or License, (2) PAN Card of owner or company, (3) GSTIN Certificate, (4) Cancelled cheque or bank statement, and (5) Menu with pricing.
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item help-accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-bold px-0 bg-transparent text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#q2">
                                        How long does it take for my restaurant to go live?
                                    </button>
                                </h2>
                                <div id="q2" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                    <div class="accordion-body px-0 text-muted">
                                        Once your documents and menu are submitted, our onboarding team verifies the details within 24 to 48 hours. Once verified, your kitchen goes live on our search feed immediately!
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item help-accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-bold px-0 bg-transparent text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#q3">
                                        What is the commission rate per order?
                                    </button>
                                </h2>
                                <div id="q3" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                    <div class="accordion-body px-0 text-muted">
                                        Commission fees depend on your city and service model. Please complete the registration form and our regional merchant manager will explain customized commercial plans suited for your volume.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 p-4 rounded-4 bg-light d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div>
                                <h5 class="fw-bold mb-1">Still need help?</h5>
                                <p class="text-muted small mb-0">Our support champions are here for you 24/7.</p>
                            </div>
                            <a href="{{ Route::has('contact') ? route('contact') : url('/contact') }}" class="btn text-white rounded-pill px-4 fw-bold" style="background: var(--brand-orange);">Contact Us</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('partials.frontend-footer')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function switchHelpTab(tab, el) {
            document.querySelectorAll('.help-nav-item').forEach(item => item.classList.remove('active'));
            el.classList.add('active');
            const titleEl = document.getElementById('helpTabTitle');
            if (tab === 'partner') titleEl.textContent = 'Partner Onboarding';
            else if (tab === 'legal') titleEl.textContent = 'Legal, Terms & Conditions';
            else if (tab === 'faqs') titleEl.textContent = 'Frequently Asked Questions';
            else if (tab === 'orders') titleEl.textContent = 'Orders & Delivery Help';
            else if (tab === 'payments') titleEl.textContent = 'Payments & Refunds';
        }
    </script>
    @include('partials.web-visit-tracker', ['panel' => 'frontend'])
</body>
</html>