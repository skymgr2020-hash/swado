@php
    $appName = App\Models\AppSetting::getValue('app_name', 'Swado');
    $primaryColor = App\Models\AppSetting::getValue('primary_color', '#FC8019');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner with {{ $appName }} - Grow Your Restaurant & Deliver</title>
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
        .hero-partner {
            background: linear-gradient(135deg, #02060c 0%, #171a29 100%);
            color: #ffffff;
            padding: 80px 0 70px;
            text-align: center;
        }
        .partner-card-choice {
            border: 2px solid #e8e8e8;
            border-radius: 20px;
            padding: 32px 24px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            height: 100%;
        }
        .partner-card-choice:hover, .partner-card-choice.active {
            border-color: var(--brand-orange);
            background: #fff8f5;
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(252, 128, 25, 0.12);
        }
    </style>
    @include('partials.public-blade-polish')
</head>
<body>
    @include('partials.google-tag-manager')
    @include('partials.frontend-header')

    <section class="hero-partner">
        <div class="container">
            <span class="badge rounded-pill px-3 py-2 text-uppercase mb-3" style="background: rgba(252,128,25,0.2); color: var(--brand-orange); font-weight: 700;">Partner Network</span>
            <h1 class="display-4 fw-bolder mb-3">Grow With {{ $appName }}</h1>
            <p class="lead text-secondary mx-auto" style="max-width: 620px;">
                Whether you run an iconic kitchen or want to earn flexibly as a delivery executive, join our thriving ecosystem today.
            </p>
        </div>
    </section>

    <div class="container py-5">
        <div class="row g-4 justify-content-center mb-5">
            <div class="col-md-5">
                <div class="partner-card-choice active" id="restaurantChoiceCard" onclick="selectPartnerType('restaurant')">
                    <i class="fas fa-store fa-3x mb-3" style="color: var(--brand-orange);"></i>
                    <h3 class="fw-bold mb-2">Restaurant Partner</h3>
                    <p class="text-muted mb-0">List your restaurant, tap into thousands of new diners in your city, and scale order volume.</p>
                </div>
            </div>
            <div class="col-md-5">
                <div class="partner-card-choice" id="driverChoiceCard" onclick="selectPartnerType('driver')">
                    <i class="fas fa-motorcycle fa-3x mb-3" style="color: #1ba672;"></i>
                    <h3 class="fw-bold mb-2">Delivery Partner</h3>
                    <p class="text-muted mb-0">Earn competitive pay, flexible shifts, weekly payouts, and comprehensive insurance coverage.</p>
                </div>
            </div>
        </div>

        <!-- Registration Form Box -->
        <div class="bg-light p-4 p-md-5 rounded-4 border mx-auto" style="max-width: 820px;">
            <div id="restaurantForm">
                <h3 class="fw-bold mb-2 text-center">Register Your Restaurant</h3>
                <p class="text-muted text-center mb-4">Step 1: Fill in basic restaurant details to begin onboarding.</p>
                
                <form onsubmit="event.preventDefault(); alert('Restaurant onboarding request received! Our merchant growth manager will call you within 24 hours.'); this.reset();">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Restaurant Name</label>
                            <input type="text" class="form-control p-3 rounded-3" placeholder="e.g. Royal Biryani House" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Owner / Manager Name</label>
                            <input type="text" class="form-control p-3 rounded-3" placeholder="e.g. Amit Patel" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Contact Phone</label>
                            <input type="tel" class="form-control p-3 rounded-3" placeholder="+91 9876543210" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Business Email</label>
                            <input type="email" class="form-control p-3 rounded-3" placeholder="kitchen@example.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">City</label>
                            <input type="text" class="form-control p-3 rounded-3" placeholder="e.g. Bengaluru, Mumbai, Delhi" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Primary Cuisines</label>
                            <input type="text" class="form-control p-3 rounded-3" placeholder="e.g. North Indian, Biryani, Mughlai">
                        </div>
                        <div class="col-12 text-center mt-4">
                            <button type="submit" class="btn text-white px-5 py-3 rounded-pill fw-bold" style="background: var(--brand-orange);">Submit Restaurant Application</button>
                        </div>
                    </div>
                </form>
            </div>

            <div id="driverForm" style="display: none;">
                <h3 class="fw-bold mb-2 text-center">Ride & Deliver with {{ $appName }}</h3>
                <p class="text-muted text-center mb-4">Earn up to ₹35,000/month with complete schedule flexibility.</p>
                
                <form onsubmit="event.preventDefault(); alert('Delivery partner application received! Our onboarding hub will contact you for document verification.'); this.reset();">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Your Full Name</label>
                            <input type="text" class="form-control p-3 rounded-3" placeholder="Full name as per Aadhaar" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Mobile Number</label>
                            <input type="tel" class="form-control p-3 rounded-3" placeholder="+91 9876543210" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Delivery City</label>
                            <input type="text" class="form-control p-3 rounded-3" placeholder="e.g. Bengaluru" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Vehicle Type</label>
                            <select class="form-select p-3 rounded-3">
                                <option>Motorcycle / Scooter</option>
                                <option>Electric Vehicle (EV)</option>
                                <option>Bicycle</option>
                            </select>
                        </div>
                        <div class="col-12 text-center mt-4">
                            <button type="submit" class="btn text-white px-5 py-3 rounded-pill fw-bold" style="background: #1ba672;">Submit Delivery Rider Application</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('partials.frontend-footer')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectPartnerType(type) {
            const restCard = document.getElementById('restaurantChoiceCard');
            const driverCard = document.getElementById('driverChoiceCard');
            const restForm = document.getElementById('restaurantForm');
            const driverForm = document.getElementById('driverForm');

            if (type === 'driver') {
                driverCard.classList.add('active');
                restCard.classList.remove('active');
                driverForm.style.display = 'block';
                restForm.style.display = 'none';
            } else {
                restCard.classList.add('active');
                driverCard.classList.remove('active');
                restForm.style.display = 'block';
                driverForm.style.display = 'none';
            }
        }
    </script>
    @include('partials.web-visit-tracker', ['panel' => 'frontend'])
</body>
</html>