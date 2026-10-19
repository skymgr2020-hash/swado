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
        .blog-img-placeholder {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, var(--brand-orange) 0%, color-mix(in srgb, var(--brand-orange) 60%, #111827) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.85);
            font-size: 42px;
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
            @forelse($posts as $post)
                <div class="col-lg-4 col-md-6">
                    <a href="{{ route('blog.show', $post->slug) }}" class="blog-card">
                        @if($post->cover_image)
                            <img src="{{ \App\Services\MediaStorage::url($post->cover_image) }}" alt="{{ $post->title }}" class="blog-img">
                        @else
                            <div class="blog-img-placeholder"><i class="fas fa-newspaper"></i></div>
                        @endif
                        <div class="p-4 d-flex flex-column flex-grow-1">
                            @if($post->category)
                                <span class="badge bg-light text-dark align-self-start mb-2 fw-bold">{{ $post->category }}</span>
                            @endif
                            <h4 class="fw-bold mb-2">{{ $post->title }}</h4>
                            @if($post->excerpt)
                                <p class="text-muted small mb-4 flex-grow-1">{{ $post->excerpt }}</p>
                            @endif
                            <div class="text-muted small">
                                {{ $post->published_at?->format('M Y') }}
                                @if($post->read_minutes)
                                    &bull; {{ $post->read_minutes }} min read
                                @endif
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12 text-center text-muted py-5">
                    No stories published yet. Check back soon.
                </div>
            @endforelse
        </div>

        @if($posts->hasPages())
            <div class="mt-5">
                {{ $posts->links() }}
            </div>
        @endif
    </div>

    @include('partials.frontend-footer')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @include('partials.web-visit-tracker', ['panel' => 'frontend'])
</body>
</html>