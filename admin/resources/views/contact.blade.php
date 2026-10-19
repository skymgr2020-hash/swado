@php
    $appName = App\Models\AppSetting::getValue('app_name', config('app.name', 'Swado'));
    $primaryColor = App\Models\AppSetting::getValue('primary_color', '#FC8019');
    $supportEmail = App\Models\AppSetting::getValue('support_email', App\Models\AppSetting::getValue('contact_email', 'support@swado.example'));
    $businessEmail = App\Models\AppSetting::getValue('business_email', $supportEmail);
    $businessPhone = App\Models\AppSetting::getValue('business_phone', App\Models\AppSetting::getValue('contact_phone', ''));
    $businessCity = App\Models\AppSetting::getValue('business_city', 'India');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - {{ $appName }} Customer Care</title>
    @include('partials.seo-meta', [
        'metaTitle' => 'Contact Us - '.$appName,
        'metaDescription' => "Get in touch with {$appName} customer care for order support, partnership enquiries, or general questions.",
        'canonicalUrl' => route('contact'),
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
        body { font-family: 'Nunito Sans', sans-serif; background: #ffffff; color: var(--brand-dark); }
        h1, h2, h3, h4, h5, h6 { font-family: 'Plus Jakarta Sans', sans-serif; }
        .hero-contact {
            background: #02060c;
            color: #ffffff;
            padding: 70px 0;
            text-align: center;
        }
        .contact-box {
            border: 1px solid #e8e8e8;
            border-radius: 16px;
            padding: 30px;
            background: #ffffff;
            height: 100%;
            transition: all 0.2s ease;
        }
        .contact-box:hover {
            border-color: var(--brand-orange);
            box-shadow: 0 8px 24px rgba(40,44,63,0.08);
        }
        .contact-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: #fff5ed;
            color: var(--brand-orange);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 18px;
        }
    </style>
    @include('partials.public-blade-polish')
</head>
<body>
    @include('partials.google-tag-manager')
    @include('partials.frontend-header')

    <section class="hero-contact">
        <div class="container">
            <h1 class="display-5 fw-bolder mb-2">We'd Love to Hear From You</h1>
            <p class="lead text-secondary mx-auto" style="max-width: 540px;">
                Have questions about your order, restaurant partnerships, or fleet onboardings in {{ $businessCity }}?
            </p>
        </div>
    </section>

    <div class="container py-5">
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="contact-box">
                    <div class="contact-icon"><i class="fas fa-headset"></i></div>
                    <h4 class="fw-bold mb-2">Customer Care</h4>
                    <p class="text-muted small mb-3">For order status, payments, refunds, or delivery queries.</p>
                    <a href="mailto:{{ $supportEmail }}" class="fw-bold text-decoration-none" style="color: var(--brand-orange);">{{ $supportEmail }}</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="contact-box">
                    <div class="contact-icon"><i class="fas fa-store"></i></div>
                    <h4 class="fw-bold mb-2">Merchant Partnerships</h4>
                    <p class="text-muted small mb-3">Grow your orders and reach customers across {{ $businessCity }}.</p>
                    <a href="/partner/register" class="fw-bold text-decoration-none" style="color: var(--brand-orange);">Partner With Us &rarr;</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="contact-box">
                    <div class="contact-icon"><i class="fas fa-envelope-open-text"></i></div>
                    <h4 class="fw-bold mb-2">Business Enquiries</h4>
                    <p class="text-muted small mb-3">Corporate tie-ups, fleet management, and regional operations.</p>
                    <a href="mailto:{{ $businessEmail }}" class="fw-bold text-decoration-none" style="color: var(--brand-orange);">{{ $businessEmail }}</a>
                </div>
            </div>
        </div>

        <div class="bg-light p-4 p-md-5 rounded-4 border">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h3 class="fw-bolder mb-2 text-center">Send Us a Direct Message</h3>
                    <p class="text-muted text-center mb-4">Leave your details and our team will get back to you promptly.</p>
                    
                    <form onsubmit="event.preventDefault(); alert('Thank you! Your inquiry has been sent to our customer care team.'); this.reset();">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Your Full Name</label>
                                <input type="text" class="form-control rounded-3 p-3" placeholder="e.g. Rahul Sharma" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Email Address</label>
                                <input type="email" class="form-control rounded-3 p-3" placeholder="name@example.com" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Phone Number</label>
                                <input type="tel" class="form-control rounded-3 p-3" placeholder="+91 9876543210">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Topic</label>
                                <select class="form-select rounded-3 p-3">
                                    <option>Order Issue or Refund</option>
                                    <option>Restaurant Partner Inquiry</option>
                                    <option>Delivery Fleet Support</option>
                                    <option>General Feedback</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold small">Message / Order Details</label>
                                <textarea class="form-control rounded-3 p-3" rows="4" placeholder="How can we assist you today?" required></textarea>
                            </div>
                            <div class="col-12 text-center mt-4">
                                <button type="submit" class="btn text-white px-5 py-3 rounded-pill fw-bold" style="background: var(--brand-orange);">Submit Message</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @include('partials.frontend-footer')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @include('partials.web-visit-tracker', ['panel' => 'frontend'])
</body>
</html>