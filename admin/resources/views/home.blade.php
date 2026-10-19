<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $appName = App\Models\AppSetting::getValue('app_name', config('app.name', 'Swado'));
        $appLogo = App\Models\AppSetting::getValue('app_logo', null);
        $appFavicon = App\Models\AppSetting::getValue('app_favicon', null);
        $primaryColor = App\Models\AppSetting::getValue('primary_color', '#FC8019');
        $currencySymbol = App\Models\AppSetting::sanitizedCurrencySymbol();
        $bannerDuration = $bannerDurationSeconds ?? (int) App\Models\AppSetting::getValue('banner_duration_seconds', 5);

        // Real Cuisines from Database
        $realCuisines = [];
        try {
            $realCuisines = App\Models\Cuisine::where('is_active', true)
                ->orderBy('popular', 'desc')
                ->orderBy('display_order', 'asc')
                ->orderBy('name', 'asc')
                ->get();
        } catch (\Throwable $e) {}

        // Real Active Banners for Web (Surface: 'web' or 'both')
        $banners = $banners ?? collect();
        if ($banners->isEmpty()) {
            try {
                $banners = App\Models\Banner::where('is_active', true)
                    ->visibleOnSurface('web')
                    ->where(function($q) {
                        $q->whereNull('start_date')->orWhere('start_date', '<=', now());
                    })
                    ->where(function($q) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                    })
                    ->orderBy('display_order')
                    ->get();
            } catch (\Throwable $e) {}
        }

        // Real Top Restaurant Chains from Database
        $topChains = [];
        try {
            $topChains = App\Models\Restaurant::where('is_verified', true)
                ->orderByDesc('rating')
                ->limit(12)
                ->get();
        } catch (\Throwable $e) {}

        // Real Delivery Zones from Database
        $realDeliveryZones = [];
        try {
            $areas = App\Models\DeliveryArea::where('is_active', true)->get();
            foreach ($areas as $area) {
                $restCount = App\Models\Restaurant::where('is_verified', true)
                    ->where(function($q) use ($area) {
                        $q->where('city', 'like', "%{$area->name}%")
                          ->orWhere('address', 'like', "%{$area->name}%");
                    })->count();

                $realDeliveryZones[] = (object) [
                    'id' => $area->id,
                    'name' => $area->name,
                    'radius_km' => $area->radius_km,
                    'latitude' => $area->latitude,
                    'longitude' => $area->longitude,
                    'count' => $restCount ?: 1,
                ];
            }

            if (empty($realDeliveryZones)) {
                $branchZones = App\Models\BranchZone::where('is_active', true)->get();
                foreach ($branchZones as $bz) {
                    $realDeliveryZones[] = (object) [
                        'id' => $bz->id,
                        'name' => $bz->name,
                        'radius_km' => 10,
                        'latitude' => null,
                        'longitude' => null,
                        'count' => 1,
                    ];
                }
            }

            if (empty($realDeliveryZones)) {
                $resCities = App\Models\Restaurant::where('is_verified', true)
                    ->whereNotNull('city')
                    ->where('city', '!=', '')
                    ->select('city as name', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
                    ->groupBy('city')
                    ->orderByDesc('count')
                    ->get();
                foreach ($resCities as $idx => $c) {
                    $realDeliveryZones[] = (object) [
                        'id' => $idx + 1,
                        'name' => $c->name . ' Delivery Zone',
                        'radius_km' => 15,
                        'latitude' => null,
                        'longitude' => null,
                        'count' => (int) $c->count,
                    ];
                }
            }

            if (empty($realDeliveryZones)) {
                $bizCity = App\Models\AppSetting::getValue('business_city', 'Central Zone');
                $realDeliveryZones[] = (object) [
                    'id' => 1,
                    'name' => $bizCity . ' Delivery Zone',
                    'radius_km' => 15,
                    'latitude' => null,
                    'longitude' => null,
                    'count' => 1,
                ];
            }
        } catch (\Throwable $e) {}
    @endphp

    <title>{{ $appName }} - Order Food Online in Your Delivery Zone</title>
    <link rel="icon" href="{{ $appFavicon ? \Illuminate\Support\Facades\Storage::disk('public')->url($appFavicon) : asset('favicon.ico') }}">

    <!-- PWA Manifest & Meta Tags -->
    <link rel="manifest" href="/manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ $appName }}">
    <meta name="theme-color" content="{{ $primaryColor }}">
    @if($appFavicon)
        <link rel="apple-touch-icon" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($appFavicon) }}">
    @endif

    <!-- Preconnect -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Bootstrap 5.3 & FontAwesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Fonts: Inter / Basis Grotesque style -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --swiggy-orange: {{ $primaryColor }};
            --swiggy-orange-dark: #e56d0d;
            --swiggy-orange-light: #fff5ed;
            --swiggy-dark: #02060c;
            --swiggy-muted: #686b78;
            --swiggy-border: #e8e8e8;
            --swiggy-green: #1ba672;
            --swiggy-veg-green: #0f8a65;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #ffffff;
            color: var(--swiggy-dark);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        .swiggy-main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 16px;
        }

        .swiggy-section-heading {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.4px;
            color: var(--swiggy-dark);
            margin: 0;
        }

        .swiggy-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .swiggy-scroll-btn-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .swiggy-scroll-btn {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: none;
            background: #e2e2e7;
            color: #02060c;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .swiggy-scroll-btn:hover {
            background: #d4d5d9;
            color: #000;
        }

        /* ========================================================
           BANNER SLIDER (Swiggy Promo Slider wired to Admin Panel)
           ======================================================== */
        .swiggy-banners-section {
            padding: 24px 0 12px;
            position: relative;
        }

        .swiggy-banners-track {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            scroll-behavior: smooth;
            padding-bottom: 12px;
            scrollbar-width: none;
            -ms-overflow-style: none;
            scroll-snap-type: x mandatory;
        }

        .swiggy-banners-track::-webkit-scrollbar {
            display: none;
        }

        .swiggy-banner-card {
            flex: 0 0 calc(50% - 10px);
            min-width: 320px;
            max-width: 580px;
            height: 220px;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            cursor: pointer;
            text-decoration: none;
            scroll-snap-align: start;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: block;
            background: #fff;
        }

        .swiggy-banner-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.12);
        }

        .swiggy-banner-full-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .swiggy-banner-text-wrap {
            display: flex;
            height: 100%;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }

        .swiggy-banner-content-left {
            flex: 1;
            padding: 28px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            z-index: 2;
        }

        .swiggy-banner-title {
            font-size: 22px;
            font-weight: 800;
            line-height: 1.25;
            margin-bottom: 8px;
            color: #ffffff;
        }

        .swiggy-banner-desc {
            font-size: 13.5px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 16px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .swiggy-banner-cta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--swiggy-orange);
            color: #ffffff;
            font-size: 13px;
            font-weight: 800;
            padding: 8px 18px;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: fit-content;
        }

        .swiggy-banner-img-right {
            width: 45%;
            height: 100%;
            object-fit: cover;
        }

        @media (max-width: 768px) {
            .swiggy-banner-card {
                flex: 0 0 88%;
                height: 180px;
                min-width: 270px;
            }
            .swiggy-banner-content-left {
                padding: 18px;
            }
            .swiggy-banner-title {
                font-size: 18px;
            }
        }

        /* Section 1: "What's on your mind?" Carousel */
        .swiggy-cuisines-section {
            padding: 24px 0 16px;
        }

        .swiggy-cuisines-track {
            display: flex;
            gap: 28px;
            overflow-x: auto;
            scroll-behavior: smooth;
            padding-bottom: 12px;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .swiggy-cuisines-track::-webkit-scrollbar {
            display: none;
        }

        .swiggy-cuisine-item {
            flex-shrink: 0;
            text-align: center;
            cursor: pointer;
            width: 125px;
            user-select: none;
            transition: transform 0.2s ease;
            text-decoration: none;
            color: inherit;
        }

        .swiggy-cuisine-item:hover {
            transform: translateY(-3px);
        }

        .swiggy-cuisine-img-wrap {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            overflow: hidden;
            background: #fff8f5;
            border: 2px solid #fee8da;
            margin: 0 auto 10px;
            box-shadow: 0 4px 14px rgba(40, 44, 63, 0.06);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .swiggy-cuisine-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .swiggy-cuisine-img-wrap i {
            font-size: 36px;
            color: var(--swiggy-orange);
            transition: transform 0.25s ease;
        }

        .swiggy-cuisine-item:hover .swiggy-cuisine-img-wrap img {
            transform: scale(1.08);
        }

        .swiggy-cuisine-title {
            font-size: 14px;
            font-weight: 600;
            color: #3d4152;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .swiggy-cuisine-item.active .swiggy-cuisine-title {
            color: var(--swiggy-orange);
            font-weight: 800;
        }

        .swiggy-cuisine-item.active .swiggy-cuisine-img-wrap {
            border: 3px solid var(--swiggy-orange);
        }

        /* Section 2: Top Restaurant Chains Lane */
        .swiggy-chains-section {
            padding: 24px 0 20px;
        }

        .swiggy-chains-track {
            display: flex;
            gap: 30px;
            overflow-x: auto;
            scroll-behavior: smooth;
            padding-bottom: 16px;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .swiggy-chains-track::-webkit-scrollbar {
            display: none;
        }

        /* Swiggy Restaurant Card */
        .swiggy-card {
            display: block;
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s cubic-bezier(0.2, 0, 0, 1);
            user-select: none;
            position: relative;
        }

        .swiggy-card:hover {
            transform: scale(0.96);
            color: inherit;
        }

        .swiggy-card-img-box {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 10;
            border-radius: 16px;
            overflow: hidden;
            background: #e9e9eb;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .swiggy-card-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .swiggy-card-gradient {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(27, 30, 36, 0) 0%, rgba(27, 30, 36, 0.05) 50%, rgba(27, 30, 36, 0.9) 100%);
            pointer-events: none;
        }

        .swiggy-card-offer {
            position: absolute;
            bottom: 10px;
            left: 12px;
            right: 12px;
            color: #ffffff;
            font-size: 19px;
            font-weight: 900;
            letter-spacing: -0.5px;
            text-transform: uppercase;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.7);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.1;
        }

        .swiggy-card-veg-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 18px;
            height: 18px;
            background: #ffffff;
            border: 2px solid var(--swiggy-veg-green);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .swiggy-card-veg-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--swiggy-veg-green);
        }

        .swiggy-card-body {
            padding: 12px 4px 8px;
        }

        .swiggy-card-name {
            font-size: 17.5px;
            font-weight: 700;
            color: var(--swiggy-dark);
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            letter-spacing: -0.3px;
        }

        .swiggy-card-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 15px;
            font-weight: 700;
            color: var(--swiggy-dark);
            margin-bottom: 3px;
        }

        .swiggy-rating-pill {
            background: var(--swiggy-green);
            color: #ffffff;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 800;
        }

        .swiggy-meta-dot {
            color: #686b78;
            font-size: 12px;
        }

        .swiggy-card-cuisines {
            font-size: 14px;
            color: var(--swiggy-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 1px;
        }

        .swiggy-card-locality {
            font-size: 14px;
            color: var(--swiggy-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .swiggy-divider {
            border: 0;
            border-top: 1px solid #f0f0f5;
            margin: 32px 0;
        }

        /* Filter & Sort Bar */
        .swiggy-filter-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            overflow-x: auto;
            scrollbar-width: none;
            padding-bottom: 12px;
            margin-bottom: 28px;
        }

        .swiggy-filter-bar::-webkit-scrollbar {
            display: none;
        }

        .swiggy-filter-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 9999px;
            border: 1px solid #e2e2e7;
            background: #ffffff;
            font-size: 13.5px;
            font-weight: 600;
            color: #3d4152;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.15s ease;
            box-shadow: 0 1px 3px rgba(40, 44, 63, 0.06);
            user-select: none;
        }

        .swiggy-filter-pill:hover {
            background: #f8f8f9;
            border-color: #d4d5d9;
        }

        .swiggy-filter-pill.active {
            background: #02060c;
            color: #ffffff;
            border-color: #02060c;
        }

        .swiggy-filter-pill.active i {
            color: #ffffff !important;
        }

        /* Search input bar on home */
        .swiggy-search-wrapper {
            position: relative;
            width: 100%;
            max-width: 440px;
        }

        .swiggy-search-wrapper input {
            width: 100%;
            height: 44px;
            padding: 0 38px 0 40px;
            border: 1px solid #d4d5d9;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            background: #f8f8f9;
            outline: none;
            transition: all 0.2s;
        }

        .swiggy-search-wrapper input:focus {
            background: #ffffff;
            border-color: var(--swiggy-orange);
            box-shadow: 0 2px 10px rgba(252, 128, 25, 0.12);
        }

        .swiggy-search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #93959f;
            font-size: 14px;
        }

        .swiggy-search-clear {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #93959f;
            cursor: pointer;
            display: none;
        }

        /* Skeleton Card */
        .swiggy-skeleton-card {
            padding: 8px;
        }

        .skeleton-box {
            background: linear-gradient(90deg, #f0f0f2 25%, #e6e6e8 50%, #f0f0f2 75%);
            background-size: 200% 100%;
            animation: skeletonLoading 1.5s infinite;
            border-radius: 8px;
        }

        .skeleton-img {
            width: 100%;
            aspect-ratio: 16 / 10;
            border-radius: 16px;
            margin-bottom: 12px;
        }

        .skeleton-text {
            height: 14px;
            margin-bottom: 8px;
        }

        @keyframes skeletonLoading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .btn-swiggy-load-more {
            border: 1px solid #d4d5d9;
            border-radius: 12px;
            padding: 12px 36px;
            font-size: 15px;
            font-weight: 700;
            color: #02060c;
            background: #ffffff;
            transition: all 0.2s ease;
        }

        .btn-swiggy-load-more:hover {
            border-color: var(--swiggy-orange);
            color: var(--swiggy-orange);
            background: #fff8f5;
        }

        /* Explore Delivery Zones Grid */
        .swiggy-explore-section {
            padding: 10px 0 40px;
        }

        .swiggy-zone-pill-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
        }

        .swiggy-zone-pill-btn {
            border: 1px solid #e2e2e7;
            border-radius: 12px;
            padding: 16px 20px;
            font-size: 15px;
            font-weight: 600;
            color: #02060c;
            text-align: center;
            text-decoration: none;
            transition: all 0.15s ease;
            background: #ffffff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .swiggy-zone-pill-btn:hover {
            border-color: var(--swiggy-orange);
            color: var(--swiggy-orange);
            box-shadow: 0 4px 14px rgba(40, 44, 63, 0.08);
            transform: translateY(-2px);
        }
    </style>
    @include('partials.public-blade-polish')
</head>
<body>
    @include('partials.google-tag-manager')

    <!-- Unified Header with PWA Meta, Google Maps Places & Delivery Zone Selector -->
    @include('partials.frontend-header')

    <main class="swiggy-main-container">
        <!-- Section 0: Promotional Banner Carousel (Admin Panel Wired) -->
        @if(!empty($banners) && count($banners) > 0)
            <section class="swiggy-banners-section" id="bannersSection">
                <div class="swiggy-section-header mb-3">
                    <h2 class="swiggy-section-heading">Featured Offers & Deals</h2>
                    <div class="swiggy-scroll-btn-group">
                        <button type="button" class="swiggy-scroll-btn" id="bannerPrevBtn" onclick="scrollBannerTrack(-360)" aria-label="Previous">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <button type="button" class="swiggy-scroll-btn" id="bannerNextBtn" onclick="scrollBannerTrack(360)" aria-label="Next">
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <div class="swiggy-banners-track" id="bannerTrack">
                    @foreach($banners as $banner)
                        @php
                            $bannerImg = \App\Services\MediaStorage::url($banner->image);
                            $bannerUrl = 'javascript:void(0)';
                            if ($banner->redirect_type === 'restaurant' && $banner->redirect_restaurant_id) {
                                $bannerUrl = url('/restaurants/' . $banner->redirect_restaurant_id);
                            } elseif ($banner->redirect_type === 'category' && $banner->redirect_category_id) {
                                $bannerUrl = url('/?category=' . $banner->redirect_category_id);
                            } elseif ($banner->redirect_type === 'menu_item' && $banner->redirect_menu_item_id) {
                                $item = $banner->redirectMenuItem;
                                $bannerUrl = $item ? url('/restaurants/' . $item->restaurant_id . '#item-' . $item->id) : url('/restaurants/' . ($banner->redirect_restaurant_id ?: ''));
                            } elseif ($banner->link) {
                                $bannerUrl = $banner->link;
                            }
                        @endphp
                        <a href="{{ $bannerUrl }}" class="swiggy-banner-card" title="{{ $banner->title }}">
                            @if($banner->layout_mode === 'full_image' || empty($banner->description))
                                <img src="{{ $bannerImg }}" alt="{{ $banner->title }}" class="swiggy-banner-full-img" loading="lazy">
                            @else
                                <div class="swiggy-banner-text-wrap">
                                    <div class="swiggy-banner-content-left">
                                        <h3 class="swiggy-banner-title">{{ $banner->title }}</h3>
                                        <p class="swiggy-banner-desc">{{ $banner->description }}</p>
                                        <span class="swiggy-banner-cta">
                                            {{ $banner->cta_label ?: 'Order Now' }} <i class="fas fa-arrow-right" style="font-size: 11px;"></i>
                                        </span>
                                    </div>
                                    @if($bannerImg)
                                        <img src="{{ $bannerImg }}" alt="{{ $banner->title }}" class="swiggy-banner-img-right" loading="lazy">
                                    @endif
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <!-- Section 1: "What's on your mind?" (Real Database Cuisines) -->
        @if(!empty($realCuisines) && count($realCuisines) > 0)
            <section class="swiggy-cuisines-section">
                <div class="swiggy-section-header">
                    <h2 class="swiggy-section-heading">What's on your mind?</h2>
                    <div class="swiggy-scroll-btn-group">
                        <button type="button" class="swiggy-scroll-btn" id="cuisinePrevBtn" onclick="scrollCuisineTrack(-300)" aria-label="Previous">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <button type="button" class="swiggy-scroll-btn" id="cuisineNextBtn" onclick="scrollCuisineTrack(300)" aria-label="Next">
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <div class="swiggy-cuisines-track" id="cuisineTrack">
                    @foreach($realCuisines as $cuisine)
                        @php
                            $cImage = $cuisine->image ? \App\Services\MediaStorage::url($cuisine->image) : null;
                        @endphp
                        <div class="swiggy-cuisine-item" onclick="filterByCuisine('{{ e($cuisine->name) }}', this)" title="{{ $cuisine->name }}">
                            <div class="swiggy-cuisine-img-wrap">
                                @if($cImage)
                                    <img src="{{ $cImage }}" alt="{{ $cuisine->name }}" loading="lazy">
                                @else
                                    <i class="{{ $cuisine->icon ?: 'fas fa-utensils' }}"></i>
                                @endif
                            </div>
                            <div class="swiggy-cuisine-title">{{ $cuisine->name }}</div>
                        </div>
                    @endforeach
                </div>
            </section>

            <hr class="swiggy-divider">
        @endif

        <!-- Section 2: "Top restaurant chains in [Delivery Zone]" (Real Database Restaurants) -->
        @if(!empty($topChains) && count($topChains) > 0)
            <section class="swiggy-chains-section">
                <div class="swiggy-section-header">
                    <h2 class="swiggy-section-heading">Top restaurant chains in <span class="swiggy-inline-zone">your delivery zone</span></h2>
                    <div class="swiggy-scroll-btn-group">
                        <button type="button" class="swiggy-scroll-btn" id="chainsPrevBtn" onclick="scrollChainsTrack(-320)" aria-label="Previous">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <button type="button" class="swiggy-scroll-btn" id="chainsNextBtn" onclick="scrollChainsTrack(320)" aria-label="Next">
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <div class="swiggy-chains-track" id="chainsTrack">
                    @foreach($topChains as $chain)
                        @php
                            $chainImage = \App\Services\MediaStorage::url($chain->logo_image) 
                                ?: \App\Services\MediaStorage::url($chain->cover_image) 
                                ?: \App\Services\MediaStorage::url($chain->banner_image);

                            $realOffer = null;
                            if ((float) $chain->delivery_fee === 0.0) {
                                $realOffer = 'FREE DELIVERY';
                            } elseif ((float) $chain->min_order_amount > 0) {
                                $realOffer = 'MIN ORDER ' . $currencySymbol . (int)$chain->min_order_amount;
                            }
                            $promo = \App\Models\PromoCode::where(function($q) use ($chain) {
                                $q->where('restaurant_id', $chain->id)->orWhereNull('restaurant_id');
                            })->where('is_active', true)->first();
                            if ($promo) {
                                $realOffer = ($promo->discount_type === 'percentage' ? ((int)$promo->discount_value . '% OFF') : ($currencySymbol . (int)$promo->discount_value . ' OFF'))
                                    . ($promo->min_order_amount > 0 ? (' ABOVE ' . $currencySymbol . (int)$promo->min_order_amount) : '');
                            }
                        @endphp
                        <div style="width: 275px; flex-shrink: 0;">
                            <a href="/restaurants/{{ $chain->id }}" class="swiggy-card">
                                <div class="swiggy-card-img-box">
                                    @if($chainImage)
                                        <img src="{{ $chainImage }}" alt="{{ $chain->name }}" class="swiggy-card-img" loading="lazy">
                                    @else
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                                            <i class="fas fa-store fa-2x text-muted"></i>
                                        </div>
                                    @endif
                                    <div class="swiggy-card-gradient"></div>
                                    @if($realOffer)
                                        <div class="swiggy-card-offer">{{ $realOffer }}</div>
                                    @endif
                                    @if($chain->is_pure_veg)
                                        <div class="swiggy-card-veg-indicator" title="Pure Veg">
                                            <div class="swiggy-card-veg-dot"></div>
                                        </div>
                                    @endif
                                </div>
                                <div class="swiggy-card-body">
                                    <div class="swiggy-card-name">{{ $chain->name }}</div>
                                    <div class="swiggy-card-meta">
                                        <span class="swiggy-rating-pill"><i class="fas fa-star"></i></span>
                                        <span>{{ number_format((float)($chain->rating ?? 4.0), 1) }}</span>
                                        <span class="swiggy-meta-dot">•</span>
                                        <span>{{ $chain->delivery_time ?? 30 }}-{{ ($chain->delivery_time ?? 30) + 10 }} mins</span>
                                    </div>
                                    <div class="swiggy-card-cuisines">{{ $chain->cuisine_text ?: 'Variety Cuisine' }}</div>
                                    <div class="swiggy-card-locality">{{ $chain->city ?: $chain->address }}</div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>

            <hr class="swiggy-divider">
        @endif

        <!-- Section 3: "Restaurants with online food delivery in [Delivery Zone]" -->
        <section class="py-2" id="restaurantsSection">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                <h2 class="swiggy-section-heading">Restaurants delivering in <span class="swiggy-inline-zone">your delivery zone</span></h2>
                
                <!-- Quick Search Input -->
                <div class="swiggy-search-wrapper mb-0">
                    <i class="fas fa-search swiggy-search-icon"></i>
                    <input type="text" id="swiggyLiveSearch" placeholder="Search for restaurant or dish..." autocomplete="off" oninput="onSearchInput(this.value)">
                    <button type="button" class="swiggy-search-clear" id="searchClearBtn" onclick="clearLiveSearch()"><i class="fas fa-times"></i></button>
                </div>
            </div>

            <!-- Filter & Sort Pills Bar -->
            <div class="swiggy-filter-bar" id="filterBar">
                <div class="dropdown">
                    <button class="swiggy-filter-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="sortPillBtn">
                        <i class="fas fa-sort-amount-down text-muted"></i>
                        <span id="currentSortLabel">Sort By</span>
                    </button>
                    <ul class="dropdown-menu shadow border-0 rounded-3">
                        <li><a class="dropdown-item py-2 fw-semibold" href="javascript:void(0)" onclick="applySort('rating', 'Rating')"><i class="fas fa-star text-warning me-2"></i>Rating: High to Low</a></li>
                        <li><a class="dropdown-item py-2 fw-semibold" href="javascript:void(0)" onclick="applySort('delivery_time', 'Delivery Time')"><i class="far fa-clock text-primary me-2"></i>Delivery Time: Fastest</a></li>
                        <li><a class="dropdown-item py-2 fw-semibold" href="javascript:void(0)" onclick="applySort('price_low', 'Cost: Low to High')"><i class="fas fa-arrow-down-1-9 text-success me-2"></i>Cost: Low to High</a></li>
                        <li><a class="dropdown-item py-2 fw-semibold" href="javascript:void(0)" onclick="applySort('price_high', 'Cost: High to Low')"><i class="fas fa-arrow-up-9-1 text-danger me-2"></i>Cost: High to Low</a></li>
                    </ul>
                </div>

                <button type="button" class="swiggy-filter-pill" id="fastDeliveryPill" onclick="toggleFilterPill(this, 'fast_delivery')">
                    <i class="fas fa-bolt" style="color: #f59e0b;"></i>
                    <span>Fast Delivery</span>
                </button>

                <button type="button" class="swiggy-filter-pill" id="pureVegPill" onclick="toggleFilterPill(this, 'pure_veg')">
                    <span style="width: 14px; height: 14px; border: 1.5px solid var(--swiggy-veg-green); border-radius: 3px; display: inline-flex; align-items: center; justify-content: center;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: var(--swiggy-veg-green);"></span>
                    </span>
                    <span>Pure Veg</span>
                </button>

                <button type="button" class="swiggy-filter-pill" id="ratingPill" onclick="toggleFilterPill(this, 'rating_4')">
                    <i class="fas fa-star text-warning"></i>
                    <span>Ratings 4.0+</span>
                </button>

                <button type="button" class="swiggy-filter-pill" id="offersPill" onclick="toggleFilterPill(this, 'offers')">
                    <i class="fas fa-percent text-danger"></i>
                    <span>Offers</span>
                </button>

                <button type="button" class="swiggy-filter-pill" id="under199Pill" onclick="toggleFilterPill(this, 'under_199')">
                    <span>Less than {{ $currencySymbol }}199</span>
                </button>
            </div>

            <!-- Restaurant Grid -->
            <div class="row g-4" id="restaurantsContainer">
                @for($i = 0; $i < 6; $i++)
                    <div class="col-xl-3 col-lg-4 col-sm-6 col-12 swiggy-skeleton-card">
                        <div class="skeleton-box skeleton-img"></div>
                        <div class="skeleton-box skeleton-text" style="width: 70%;"></div>
                        <div class="skeleton-box skeleton-text" style="width: 45%;"></div>
                        <div class="skeleton-box skeleton-text" style="width: 85%;"></div>
                    </div>
                @endfor
            </div>

            <!-- Load More -->
            <div class="text-center mt-5 mb-4">
                <button type="button" class="btn btn-swiggy-load-more" id="loadMoreBtn" onclick="loadMoreRestaurants()" style="display: none;">
                    Show More Restaurants <i class="fas fa-chevron-down ms-1"></i>
                </button>
                <div id="gridLoader" class="text-center py-3" style="display: none;">
                    <div class="spinner-border text-warning" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 4: Explore Real Delivery Zones in Database -->
        @if(!empty($realDeliveryZones) && count($realDeliveryZones) > 0)
            <hr class="swiggy-divider">
            <section class="swiggy-explore-section">
                <h2 class="swiggy-section-heading mb-3">Popular Delivery Zones</h2>
                <div class="swiggy-zone-pill-grid">
                    @foreach($realDeliveryZones as $zoneObj)
                        <div class="swiggy-zone-pill-btn" onclick="selectDrawerDeliveryZone('{{ e($zoneObj->name) }}', {{ $zoneObj->latitude ?? 'null' }}, {{ $zoneObj->longitude ?? 'null' }}, {{ $zoneObj->id ?? 'null' }})">
                            <span class="text-truncate">{{ $zoneObj->name }}</span>
                            <span class="badge bg-light text-muted" style="font-size: 11px;">{{ $zoneObj->radius_km ? $zoneObj->radius_km . 'km' : 'Active' }}</span>
                        </div>
                    @endforeach
                    <div class="swiggy-zone-pill-btn text-primary fw-bold" onclick="openSwiggyLocationDrawer()">
                        <span>Change Delivery Zone</span>
                        <i class="fas fa-arrow-right ms-2" style="font-size:12px;"></i>
                    </div>
                </div>

                @if(!empty($realCuisines) && count($realCuisines) > 0)
                    <h2 class="swiggy-section-heading mt-5 mb-3">Best Cuisines in Your Zone</h2>
                    <div class="swiggy-zone-pill-grid">
                        @foreach($realCuisines->take(8) as $rc)
                            <div class="swiggy-zone-pill-btn" onclick="filterByCuisine('{{ e($rc->name) }}')">
                                <span>{{ $rc->name }} Delivery</span>
                                <i class="fas fa-utensils text-muted" style="font-size: 12px;"></i>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    </main>

    <!-- Unified Footer with Delivery Zones Column -->
    @include('partials.frontend-footer')

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const currencySymbol = @json($currencySymbol);
        const bannerAutoPlaySeconds = {{ $bannerDuration }};

        let currentPage = 1;
        let hasMore = true;
        let isLoading = false;
        let currentSearch = '';
        let currentCuisine = '';
        let currentSort = 'rating';
        let currentZone = '';
        let currentZoneId = null;
        let currentLat = null;
        let currentLng = null;

        const activeFilters = {
            fast_delivery: false,
            pure_veg: false,
            rating_4: false,
            offers: false,
            under_199: false
        };

        // Scroll controllers
        function scrollCuisineTrack(offset) {
            const track = document.getElementById('cuisineTrack');
            if (track) track.scrollBy({ left: offset, behavior: 'smooth' });
        }

        function scrollChainsTrack(offset) {
            const track = document.getElementById('chainsTrack');
            if (track) track.scrollBy({ left: offset, behavior: 'smooth' });
        }

        function scrollBannerTrack(offset) {
            const track = document.getElementById('bannerTrack');
            if (track) track.scrollBy({ left: offset, behavior: 'smooth' });
        }

        // Auto-play banner track based on admin banner_duration_seconds setting
        let bannerAutoPlayTimer = null;
        function setupBannerAutoPlay() {
            const track = document.getElementById('bannerTrack');
            if (!track || track.children.length <= 1) return;

            clearInterval(bannerAutoPlayTimer);
            bannerAutoPlayTimer = setInterval(() => {
                if (track.scrollLeft + track.clientWidth >= track.scrollWidth - 10) {
                    track.scrollTo({ left: 0, behavior: 'smooth' });
                } else {
                    track.scrollBy({ left: 360, behavior: 'smooth' });
                }
            }, bannerAutoPlaySeconds * 1000);

            track.addEventListener('mouseenter', () => clearInterval(bannerAutoPlayTimer));
            track.addEventListener('mouseleave', setupBannerAutoPlay);
        }

        // Cuisine filtering
        function filterByCuisine(cuisineName, element = null) {
            document.querySelectorAll('.swiggy-cuisine-item').forEach(el => el.classList.remove('active'));
            if (currentCuisine === cuisineName) {
                currentCuisine = '';
            } else {
                currentCuisine = cuisineName;
                if (element) element.classList.add('active');
            }
            loadRestaurants(true);
            const section = document.getElementById('restaurantsSection');
            if (section) section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function applySort(sortKey, sortLabel) {
            currentSort = sortKey;
            const labelEl = document.getElementById('currentSortLabel');
            if (labelEl) labelEl.textContent = sortLabel;
            loadRestaurants(true);
        }

        function toggleFilterPill(btn, filterKey) {
            activeFilters[filterKey] = !activeFilters[filterKey];
            btn.classList.toggle('active', activeFilters[filterKey]);
            loadRestaurants(true);
        }

        async function loadRestaurants(reset = true) {
            if (isLoading) return;
            if (reset) {
                currentPage = 1;
                hasMore = true;
                const container = document.getElementById('restaurantsContainer');
                if (container) {
                    container.innerHTML = Array(6).fill(0).map(() => `
                        <div class="col-xl-3 col-lg-4 col-sm-6 col-12 swiggy-skeleton-card">
                            <div class="skeleton-box skeleton-img"></div>
                            <div class="skeleton-box skeleton-text" style="width: 70%;"></div>
                            <div class="skeleton-box skeleton-text" style="width: 45%;"></div>
                            <div class="skeleton-box skeleton-text" style="width: 85%;"></div>
                        </div>
                    `).join('');
                }
            }

            if (!hasMore && !reset) return;

            isLoading = true;
            const gridLoader = document.getElementById('gridLoader');
            if (gridLoader && !reset) gridLoader.style.display = 'block';

            try {
                let searchTerm = currentSearch;
                if (currentCuisine) {
                    searchTerm = searchTerm ? `${currentCuisine} ${searchTerm}` : currentCuisine;
                }
                if (activeFilters.under_199) {
                    searchTerm += ' under 199';
                }

                // FIXED: Use /restaurants/search endpoint mapped to HomeController@searchRestaurants
                let url = `/restaurants/search?page=${currentPage}&search=${encodeURIComponent(searchTerm)}&sort=${currentSort}`;

                if (currentZoneId) {
                    url += `&zone_id=${encodeURIComponent(currentZoneId)}`;
                } else if (currentZone) {
                    url += `&zone=${encodeURIComponent(currentZone)}`;
                } else if (currentLat && currentLng) {
                    url += `&lat=${currentLat}&lng=${currentLng}`;
                }

                if (activeFilters.pure_veg) {
                    url += '&pure_veg=true';
                }

                const response = await fetch(url);
                const data = await response.json();

                let list = data.restaurants || [];

                if (activeFilters.fast_delivery) {
                    list = list.filter(r => (parseInt(r.delivery_time) || 30) <= 30);
                }
                if (activeFilters.rating_4) {
                    list = list.filter(r => (parseFloat(r.rating) || 0) >= 4.0);
                }
                if (activeFilters.offers) {
                    list = list.filter(r => Boolean(r.offer));
                }

                const container = document.getElementById('restaurantsContainer');
                if (list.length > 0) {
                    const cardsHtml = list.map(r => `
                        <div class="col-xl-3 col-lg-4 col-sm-6 col-12">
                            <a href="/restaurants/${r.slug || r.id}" class="swiggy-card">
                                <div class="swiggy-card-img-box">
                                    <img src="${r.image || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500'}" alt="${escapeHtml(r.name)}" class="swiggy-card-img" loading="lazy">
                                    <div class="swiggy-card-gradient"></div>
                                    ${r.offer ? `<div class="swiggy-card-offer">${escapeHtml(r.offer)}</div>` : ''}
                                    ${r.is_pure_veg ? `
                                        <div class="swiggy-card-veg-indicator" title="Pure Veg">
                                            <div class="swiggy-card-veg-dot"></div>
                                        </div>
                                    ` : ''}
                                </div>
                                <div class="swiggy-card-body">
                                    <div class="swiggy-card-name">${escapeHtml(r.name)}</div>
                                    <div class="swiggy-card-meta">
                                        <span class="swiggy-rating-pill"><i class="fas fa-star"></i></span>
                                        <span>${(parseFloat(r.rating) || 4.0).toFixed(1)}</span>
                                        <span class="swiggy-meta-dot">•</span>
                                        <span>${r.delivery_time || 30}-${(parseInt(r.delivery_time) || 30) + 10} mins</span>
                                    </div>
                                    <div class="swiggy-card-cuisines">${escapeHtml(r.cuisine || 'Variety Cuisine')}</div>
                                    <div class="swiggy-card-locality">${escapeHtml(r.delivery_zone || r.city || 'Nearby')}</div>
                                </div>
                            </a>
                        </div>
                    `).join('');

                    if (reset) {
                        container.innerHTML = cardsHtml;
                    } else {
                        container.insertAdjacentHTML('beforeend', cardsHtml);
                    }
                } else {
                    if (reset) {
                        container.innerHTML = `
                            <div class="col-12 text-center py-5">
                                <div style="font-size: 48px; color: #d4d5d9; margin-bottom: 12px;"><i class="fas fa-utensils"></i></div>
                                <h4 class="fw-bold mb-2">No restaurants delivering in this zone</h4>
                                <p class="text-muted mb-4">Try choosing another delivery zone or clear your search filters.</p>
                                <button type="button" class="btn btn-outline-dark rounded-pill px-4" onclick="openSwiggyLocationDrawer()">Change Delivery Zone</button>
                            </div>
                        `;
                    }
                }

                hasMore = Boolean(data.has_more);
                const loadBtn = document.getElementById('loadMoreBtn');
                if (loadBtn) {
                    loadBtn.style.display = hasMore ? 'inline-block' : 'none';
                }

            } catch(e) {
                console.error('Error loading restaurants:', e);
            } finally {
                isLoading = false;
                if (gridLoader) gridLoader.style.display = 'none';
            }
        }

        function loadMoreRestaurants() {
            if (!hasMore || isLoading) return;
            currentPage++;
            loadRestaurants(false);
        }

        // Live Search input
        let searchDebounce = null;
        function onSearchInput(val) {
            clearTimeout(searchDebounce);
            currentSearch = val.trim();
            const clearBtn = document.getElementById('searchClearBtn');
            if (clearBtn) clearBtn.style.display = currentSearch ? 'block' : 'none';

            searchDebounce = setTimeout(() => {
                loadRestaurants(true);
            }, 300);
        }

        function clearLiveSearch() {
            const inp = document.getElementById('swiggyLiveSearch');
            if (inp) inp.value = '';
            currentSearch = '';
            const clearBtn = document.getElementById('searchClearBtn');
            if (clearBtn) clearBtn.style.display = 'none';
            loadRestaurants(true);
        }

        // Hook into zone changes from drawer
        window.onSwiggyZoneChanged = function(zoneName, lat, lng, zoneId) {
            currentZone = zoneName;
            currentZoneId = zoneId;
            currentLat = lat;
            currentLng = lng;

            document.querySelectorAll('.swiggy-inline-zone').forEach(el => {
                el.textContent = zoneName.split(',')[0].trim();
            });

            loadRestaurants(true);
        };

        window.onSwiggyLocationChanged = function(locationStr, lat, lng) {
            window.onSwiggyZoneChanged(locationStr, lat, lng, null);
        };

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Initialize on load
        document.addEventListener('DOMContentLoaded', () => {
            try {
                const saved = localStorage.getItem('userDeliveryZone') || localStorage.getItem('userLocation');
                if (saved) {
                    const data = JSON.parse(saved);
                    if (data && (data.zone || data.location)) {
                        currentZone = data.zone || data.location;
                        currentZoneId = data.zone_id || null;
                        currentLat = data.lat;
                        currentLng = data.lng;

                        document.querySelectorAll('.swiggy-inline-zone').forEach(el => {
                            el.textContent = currentZone.split(',')[0].trim();
                        });
                    }
                }
            } catch(e) {}

            loadRestaurants(true);
            setupBannerAutoPlay();
        });
    </script>

    @include('partials.web-visit-tracker', ['panel' => 'frontend'])
</body>
</html>