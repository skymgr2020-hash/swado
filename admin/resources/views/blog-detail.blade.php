@php
    $appName = App\Models\AppSetting::getValue('app_name', config('app.name', 'Swado'));
    $primaryColor = App\Models\AppSetting::getValue('primary_color', '#FC8019');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $post->title }} - {{ $appName }}</title>
    @include('partials.seo-meta', [
        'metaTitle' => $post->meta_title ?: $post->title,
        'metaDescription' => $post->meta_description ?: $post->excerpt,
        'metaImage' => $post->cover_image ? \App\Services\MediaStorage::url($post->cover_image) : null,
        'canonicalUrl' => route('blog.show', $post->slug),
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
        .blog-detail-hero {
            width: 100%;
            height: 360px;
            object-fit: cover;
            border-radius: 0 0 24px 24px;
        }
        .blog-detail-hero-placeholder {
            width: 100%;
            height: 280px;
            border-radius: 0 0 24px 24px;
            background: linear-gradient(135deg, var(--brand-orange) 0%, color-mix(in srgb, var(--brand-orange) 60%, #111827) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.85);
            font-size: 64px;
        }
        .blog-detail-meta {
            color: var(--app-muted);
            font-size: 14px;
            font-weight: 600;
        }
        .blog-detail-body {
            font-size: 16px;
            line-height: 1.8;
            color: var(--brand-dark);
        }
        .blog-detail-body img {
            max-width: 100%;
            border-radius: 12px;
            height: auto;
        }
        .blog-detail-body h1,
        .blog-detail-body h2,
        .blog-detail-body h3 {
            margin-top: 1.6em;
            margin-bottom: 0.6em;
        }
        .back-to-blog {
            color: var(--brand-orange);
            font-weight: 700;
            text-decoration: none;
        }
    </style>
    @include('partials.public-blade-polish')
</head>
<body>
    @include('partials.google-tag-manager')
    @include('partials.frontend-header')

    @if($post->cover_image)
        <img src="{{ \App\Services\MediaStorage::url($post->cover_image) }}" alt="{{ $post->title }}" class="blog-detail-hero">
    @else
        <div class="blog-detail-hero-placeholder"><i class="fas fa-newspaper"></i></div>
    @endif

    <div class="container py-5" style="max-width: 760px;">
        <a href="{{ route('blog') }}" class="back-to-blog mb-4 d-inline-block">&larr; Back to {{ $appName }} Diaries</a>

        @if($post->category)
            <span class="badge bg-light text-dark align-self-start mb-3 fw-bold">{{ $post->category }}</span>
        @endif

        <h1 class="fw-bolder mb-3">{{ $post->title }}</h1>

        <div class="blog-detail-meta mb-4">
            @if($post->author_name)
                {{ $post->author_name }} &bull;
            @endif
            {{ $post->published_at?->format('F j, Y') }}
            @if($post->read_minutes)
                &bull; {{ $post->read_minutes }} min read
            @endif
        </div>

        <div class="blog-detail-body">
            {!! $post->body !!}
        </div>
    </div>

    @include('partials.frontend-footer')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @include('partials.web-visit-tracker', ['panel' => 'frontend'])
</body>
</html>
