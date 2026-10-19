@php
    $appName = App\Models\AppSetting::getValue('app_name', 'Swado');
    $primaryColor = App\Models\AppSetting::getValue('primary_color', '#FC8019');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frequently Asked Questions - {{ $appName }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-orange: {{ $primaryColor }};
            --brand-dark: #02060c;
            --brand-muted: #686b78;
        }
        body { font-family: 'Inter', sans-serif; background: #ffffff; color: var(--brand-dark); }
        .hero-faqs {
            background: #02060c;
            color: #ffffff;
            padding: 70px 0;
            text-align: center;
        }
        .faq-pill {
            border: 1px solid #e2e2e7;
            padding: 8px 20px;
            border-radius: 9999px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            background: #fff;
            transition: all 0.2s ease;
        }
        .faq-pill:hover, .faq-pill.active {
            background: var(--brand-orange);
            color: #fff;
            border-color: var(--brand-orange);
        }
    </style>
    @include('partials.public-blade-polish')
</head>
<body>
    @include('partials.google-tag-manager')
    @include('partials.frontend-header')

    <section class="hero-faqs">
        <div class="container">
            <h1 class="display-5 fw-bolder mb-2">Frequently Asked Questions</h1>
            <p class="lead text-secondary mx-auto" style="max-width: 540px;">
                Find quick answers to common queries regarding ordering, delivery times, payments, and account settings.
            </p>
        </div>
    </section>

    <div class="container py-5">
        <!-- FAQ Accordion -->
        <div class="accordion accordion-flush mx-auto" id="mainFaqAccordion" style="max-width: 820px;">
            <div class="accordion-item border rounded-3 p-2 mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-bold bg-transparent text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                        How does {{ $appName }} calculate delivery charges?
                    </button>
                </h2>
                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#mainFaqAccordion">
                    <div class="accordion-body text-muted">
                        Delivery charges are determined primarily by the distance between the chosen restaurant and your delivery location, as well as prevailing weather or peak demand hours.
                    </div>
                </div>
            </div>

            <div class="accordion-item border rounded-3 p-2 mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-bold bg-transparent text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                        Can I cancel or edit my order after placing it?
                    </button>
                </h2>
                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#mainFaqAccordion">
                    <div class="accordion-body text-muted">
                        Once an order is accepted and the kitchen begins preparation, cancellations may incur a fee. If you need immediate assistance or made a mistake, please reach out to customer care promptly.
                    </div>
                </div>
            </div>

            <div class="accordion-item border rounded-3 p-2 mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-bold bg-transparent text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                        What payment methods are supported?
                    </button>
                </h2>
                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#mainFaqAccordion">
                    <div class="accordion-body text-muted">
                        We support all popular Indian payment options including UPI (Google Pay, PhonePe, Paytm), Credit & Debit cards (Visa, Mastercard, RuPay), Net Banking, and Cash on Delivery (COD) at eligible pin codes.
                    </div>
                </div>
            </div>

            <div class="accordion-item border rounded-3 p-2 mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-bold bg-transparent text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                        How can I track my food order in real-time?
                    </button>
                </h2>
                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#mainFaqAccordion">
                    <div class="accordion-body text-muted">
                        After placing your order, head over to "My Orders" in your profile. You'll see live status indicators: Order Placed &rarr; Food Being Prepared &rarr; Out for Delivery &rarr; Arrived.
                    </div>
                </div>
            </div>

            <div class="accordion-item border rounded-3 p-2 mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-bold bg-transparent text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                        Is there a minimum order amount?
                    </button>
                </h2>
                <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#mainFaqAccordion">
                    <div class="accordion-body text-muted">
                        Most restaurants do not have a mandatory minimum order amount; however, a nominal delivery surcharge may apply for orders below ₹99 depending on the merchant.
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('partials.frontend-footer')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @include('partials.web-visit-tracker', ['panel' => 'frontend'])
</body>
</html>