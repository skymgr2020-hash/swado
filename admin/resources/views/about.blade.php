@php
    $appName = App\Models\AppSetting::getValue('app_name', config('app.name', 'Swado'));
    $primaryColor = App\Models\AppSetting::getValue('primary_color', '#FC8019');
    $contactEmail = App\Models\AppSetting::getValue('support_email', App\Models\AppSetting::getValue('contact_email', 'support@swado.example'));
    $contactPhone = App\Models\AppSetting::getValue('business_phone', App\Models\AppSetting::getValue('contact_phone', ''));
    $businessCity = App\Models\AppSetting::getValue('business_city', 'India');

    // Real dynamic counts from database
    $realRestaurantCount = App\Models\Restaurant::where('is_verified', true)->count();
    $realCityCount = App\Models\Restaurant::where('is_verified', true)->whereNotNull('city')->distinct()->count('city') ?: 1;
    $realUserCount = App\Models\User::role('customer')->count();
    $realDriverCount = App\Models\User::role('delivery_partner')->count();

    $statRestaurant = $realRestaurantCount > 0 ? ($realRestaurantCount . '+') : App\Models\AppSetting::getValue('stat_restaurant_count', '100+');
    $statCity = $realCityCount > 0 ? ($realCityCount . '+') : App\Models\AppSetting::getValue('stat_city_count', '10+');
    $statUser = $realUserCount > 0 ? ($realUserCount . '+') : App\Models\AppSetting::getValue('stat_user_count', '1,000+');
    $statDriver = $realDriverCount > 0 ? ($realDriverCount . '+') : App\Models\AppSetting::getValue('stat_driver_count', '50+');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - {{ $appName }} | Food Delivery & Beyond</title>
    @include('partials.seo-meta', [
        'metaTitle' => 'About Us - '.$appName,
        'metaDescription' => "Learn about {$appName}, a food delivery platform connecting you with {$statRestaurant} restaurants across {$statCity} cities.",
        'canonicalUrl' => route('about'),
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
        .hero-banner {
            background: linear-gradient(135deg, #02060c 0%, #171a29 100%);
            color: #ffffff;
            padding: 80px 0 70px;
            position: relative;
            overflow: hidden;
        }
        .stat-card {
            background: #ffffff;
            border: 1px solid #f0f0f5;
            border-radius: 16px;
            padding: 32px 24px;
            text-align: center;
            box-shadow: 0 4px 16px rgba(40,44,63,0.06);
            transition: transform 0.2s ease;
        }
        .stat-card:hover { transform: translateY(-4px); }
        .stat-num { font-size: 38px; font-weight: 900; color: var(--brand-orange); letter-spacing: -1px; }
        .stat-lbl { font-size: 15px; font-weight: 600; color: var(--brand-muted); margin-top: 6px; }
        .value-card {
            border: 1px solid #e8e8e8;
            border-radius: 16px;
            padding: 30px;
            height: 100%;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .value-card:hover { border-color: var(--brand-orange); box-shadow: 0 8px 24px rgba(252, 128, 25, 0.1); }
        .value-icon {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            background: #fff5ed;
            color: var(--brand-orange);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 20px;
        }
    </style>
    @include('partials.public-blade-polish')
</head>
<body>
    @include('partials.google-tag-manager')
    @include('partials.frontend-header')

    <section class="hero-banner">
        <div class="container text-center position-relative" style="z-index: 1;">
            <span class="badge rounded-pill px-3 py-2 text-uppercase mb-3" style="background: rgba(252,128,25,0.2); color: var(--brand-orange); font-weight: 700; letter-spacing: 0.5px;">About {{ $appName }}</span>
            <h1 class="display-4 fw-bolder mb-3">Changing the Way You Eat & Live</h1>
            <p class="lead mx-auto text-secondary" style="max-width: 680px; color: #93959f !important;">
                Connecting customers to quality kitchens, neighborhood restaurants, and reliable doorstep delivery.
            </p>
        </div>
    </section>

    <!-- Real Stats from Database -->
    <div class="container" style="margin-top: -40px; position: relative; z-index: 2;">
        <div class="row g-4">
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-num">{{ $statCity }}</div>
                    <div class="stat-lbl">Cities Covered</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-num">{{ $statRestaurant }}</div>
                    <div class="stat-lbl">Partner Restaurants</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-num">{{ $statDriver }}</div>
                    <div class="stat-lbl">Delivery Partners</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-num">{{ $statUser }}</div>
                    <div class="stat-lbl">Happy Customers</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mission & Story -->
    <section class="container py-5 my-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h2 class="fw-bolder display-6 mb-4">Our Mission</h2>
                <p class="fs-5 text-muted mb-4" style="line-height: 1.7;">
                    Our mission at {{ $appName }} is to elevate everyday living by delivering food quickly, affordably, and safely. We partner closely with culinary establishments across {{ $businessCity }} and beyond to help them reach more hungry diners with seamless ordering technology.
                </p>
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('home') }}" class="btn text-white px-4 py-3 rounded-pill fw-bold" style="background: var(--brand-orange);">Order Food Online</a>
                    <a href="/partner/register" class="btn btn-outline-dark px-4 py-3 rounded-pill fw-bold">Partner with Us</a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="p-4 p-md-5 rounded-4 bg-light border">
                    <h4 class="fw-bold mb-3">Headquarters & Enquiries</h4>
                    <p class="text-muted mb-2"><strong>City:</strong> {{ $businessCity }}</p>
                    <p class="text-muted mb-2"><strong>Support Email:</strong> <a href="mailto:{{ $contactEmail }}" class="text-dark">{{ $contactEmail }}</a></p>
                    @if($contactPhone)
                        <p class="text-muted mb-4"><strong>Phone:</strong> <a href="tel:{{ $contactPhone }}" class="text-dark">{{ $contactPhone }}</a></p>
                    @endif
                    <a href="{{ route('app.download') }}" class="btn btn-dark rounded-pill px-4 fw-bold">Download {{ $appName }} App &rarr;</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Values -->
    <section class="bg-light py-5">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h2 class="fw-bolder">What Drives Us</h2>
                <p class="text-muted">The core principles behind our dispatch routing and merchant community.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="value-card bg-white">
                        <div class="value-icon"><i class="fas fa-heart"></i></div>
                        <h4 class="fw-bold mb-3">Customer First</h4>
                        <p class="text-muted mb-0">Every decision we make starts and ends with making our customers' dining experience seamless and delightful.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="value-card bg-white">
                        <div class="value-icon"><i class="fas fa-bolt"></i></div>
                        <h4 class="fw-bold mb-3">Speed & Reliability</h4>
                        <p class="text-muted mb-0">We focus on hyper-local efficiency to ensure your food is prepared fresh and delivered right on time.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="value-card bg-white">
                        <div class="value-icon"><i class="fas fa-handshake"></i></div>
                        <h4 class="fw-bold mb-3">Partner Growth</h4>
                        <p class="text-muted mb-0">We build transparent relationships so restaurants and delivery executives thrive alongside our business.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('partials.frontend-footer')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @include('partials.web-visit-tracker', ['panel' => 'frontend'])
</body>
</html>