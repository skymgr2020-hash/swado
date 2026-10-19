@php
    $appName = App\Models\AppSetting::getValue('app_name', 'Swado');
    $primaryColor = App\Models\AppSetting::getValue('primary_color', '#FC8019');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appName }} Diaries - Food, Tech & Culture Stories</title>
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
        .hero-blog {
            background: #02060c;
            color: #ffffff;
            padding: 70px 0;
            text-align: center;
        }
        .blog-card {
            border: 1px solid #e8e8e8;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.2s ease;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .blog-card:hover {
            border-color: var(--brand-orange);
            box-shadow: 0 8px 24px rgba(40,44,63,0.08);
            transform: translateY(-3px);
            color: inherit;
        }
        .blog-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
    </style>
    @include('partials.public-blade-polish')
</head>
<body>
    @include('partials.google-tag-manager')
    @include('partials.frontend-header')

    <section class="hero-blog">
        <div class="container">
            <span class="badge rounded-pill px-3 py-2 text-uppercase mb-3" style="background: rgba(252,128,25,0.2); color: var(--brand-orange); font-weight: 700;">{{ $appName }} Diaries</span>
            <h1 class="display-5 fw-bolder mb-2">Stories of Food, Tech & Culture</h1>
            <p class="lead text-secondary mx-auto" style="max-width: 580px;">
                Insights into our engineering challenges, culinary explorations across cities, and partner journeys.
            </p>
        </div>
    </section>

    <div class="container py-5">
        <div class="row g-4">
            <!-- Article 1 -->
            <div class="col-lg-4 col-md-6">
                <div class="blog-card">
                    <img src="https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=600&auto=format&fit=crop&q=80" alt="Food tech" class="blog-img">
                    <div class="p-4 d-flex flex-column flex-grow-1">
                        <span class="badge bg-light text-dark align-self-start mb-2 fw-bold">Engineering</span>
                        <h4 class="fw-bold mb-2">How We Optimized Real-Time Food Delivery to Under 30 Mins</h4>
                        <p class="text-muted small mb-4 flex-grow-1">Inside the predictive batching and algorithmic route optimizations that power our on-demand fleet.</p>
                        <div class="text-muted small">Oct 2026 &bull; 5 min read</div>
                    </div>
                </div>
            </div>

            <!-- Article 2 -->
            <div class="col-lg-4 col-md-6">
                <div class="blog-card">
                    <img src="https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?w=600&auto=format&fit=crop&q=80" alt="Biryani trail" class="blog-img">
                    <div class="p-4 d-flex flex-column flex-grow-1">
                        <span class="badge bg-light text-dark align-self-start mb-2 fw-bold">Food & Culture</span>
                        <h4 class="fw-bold mb-2">The Ultimate Biryani Trail: From Lucknow to Hyderabad</h4>
                        <p class="text-muted small mb-4 flex-grow-1">Exploring the authentic spices, cooking vessels, and family heritage behind India’s favorite dish.</p>
                        <div class="text-muted small">Sep 2026 &bull; 7 min read</div>
                    </div>
                </div>
            </div>

            <!-- Article 3 -->
            <div class="col-lg-4 col-md-6">
                <div class="blog-card">
                    <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=600&auto=format&fit=crop&q=80" alt="Restaurant partner" class="blog-img">
                    <div class="p-4 d-flex flex-column flex-grow-1">
                        <span class="badge bg-light text-dark align-self-start mb-2 fw-bold">Partner Stories</span>
                        <h4 class="fw-bold mb-2">How A Local Family Pizzeria Scaled to 4 Outlets</h4>
                        <p class="text-muted small mb-4 flex-grow-1">Meet Chef Mario and learn how digital storefronts helped him 5x his delivery reach.</p>
                        <div class="text-muted small">Aug 2026 &bull; 4 min read</div>
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