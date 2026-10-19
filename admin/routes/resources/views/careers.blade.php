@php
    $appName = App\Models\AppSetting::getValue('app_name', 'Swado');
    $primaryColor = App\Models\AppSetting::getValue('primary_color', '#FC8019');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Careers at {{ $appName }} - Come Grow with Us</title>
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
        .hero-career {
            background: #02060c;
            color: #ffffff;
            padding: 80px 0;
            text-align: center;
        }
        .career-card {
            border: 1px solid #e8e8e8;
            border-radius: 16px;
            padding: 24px;
            background: #ffffff;
            transition: all 0.2s ease;
        }
        .career-card:hover {
            border-color: var(--brand-orange);
            box-shadow: 0 8px 24px rgba(40,44,63,0.08);
            transform: translateY(-2px);
        }
        .dept-tag {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--brand-orange);
            background: #fff5ed;
            padding: 4px 10px;
            border-radius: 9999px;
            display: inline-block;
        }
    </style>
    @include('partials.public-blade-polish')
</head>
<body>
    @include('partials.google-tag-manager')
    @include('partials.frontend-header')

    <section class="hero-career">
        <div class="container">
            <span class="dept-tag mb-3">Careers at {{ $appName }}</span>
            <h1 class="display-4 fw-bolder mb-3">Join Our Fast-Paced Journey</h1>
            <p class="lead text-secondary mx-auto" style="max-width: 620px;">
                Shape the future of hyperlocal food & grocery logistics alongside passionate innovators.
            </p>
        </div>
    </section>

    <!-- Why Work Here -->
    <section class="container py-5 my-4">
        <div class="text-center mb-5">
            <h2 class="fw-bolder">Why Join {{ $appName }}?</h2>
            <p class="text-muted">High ownership, continuous learning, and impacting millions daily.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="p-4 border rounded-4 text-center h-100">
                    <i class="fas fa-rocket fa-3x mb-3" style="color: var(--brand-orange);"></i>
                    <h5 class="fw-bold">Fast-Paced Innovation</h5>
                    <p class="text-muted">Work with real-time routing engines, machine learning dispatch systems, and large-scale web services.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4 border rounded-4 text-center h-100">
                    <i class="fas fa-smile fa-3x mb-3" style="color: #1ba672;"></i>
                    <h5 class="fw-bold">Well-being & Culture</h5>
                    <p class="text-muted">Comprehensive healthcare, generous parental leave, learning budgets, and a truly inclusive work culture.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4 border rounded-4 text-center h-100">
                    <i class="fas fa-chart-line fa-3x mb-3" style="color: #3b82f6;"></i>
                    <h5 class="fw-bold">Career Acceleration</h5>
                    <p class="text-muted">Solve problems at hyper-scale and accelerate your professional trajectory through hands-on mentorship.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Open Roles -->
    <section class="bg-light py-5">
        <div class="container py-4">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h2 class="fw-bolder mb-1">Open Positions</h2>
                    <p class="text-muted mb-0">Explore our current job openings across engineering, product, and operations.</p>
                </div>
                <a href="mailto:careers@{{ strtolower($appName) }}.com" class="btn text-white rounded-pill px-4 fw-bold" style="background: var(--brand-orange);">Send General Resume</a>
            </div>

            <div class="d-flex flex-column gap-3">
                <div class="career-card d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <span class="dept-tag mb-2">Engineering</span>
                        <h4 class="fw-bold mb-1">Senior Full-Stack Engineer (PHP / Laravel / React)</h4>
                        <div class="text-muted small">Bengaluru &bull; Full-time &bull; Hybrid</div>
                    </div>
                    <a href="mailto:careers@{{ strtolower($appName) }}.com?subject=Application:%20Senior%20Full-Stack%20Engineer" class="btn btn-outline-dark rounded-pill px-4 fw-bold">Apply Now</a>
                </div>

                <div class="career-card d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <span class="dept-tag mb-2">Product & Design</span>
                        <h4 class="fw-bold mb-1">Lead Product Designer (UI/UX)</h4>
                        <div class="text-muted small">Delhi NCR / Bengaluru &bull; Full-time &bull; Remote</div>
                    </div>
                    <a href="mailto:careers@{{ strtolower($appName) }}.com?subject=Application:%20Lead%20Product%20Designer" class="btn btn-outline-dark rounded-pill px-4 fw-bold">Apply Now</a>
                </div>

                <div class="career-card d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <span class="dept-tag mb-2">Operations</span>
                        <h4 class="fw-bold mb-1">City Operations Manager</h4>
                        <div class="text-muted small">Mumbai &bull; Full-time &bull; On-site</div>
                    </div>
                    <a href="mailto:careers@{{ strtolower($appName) }}.com?subject=Application:%20City%20Operations%20Manager" class="btn btn-outline-dark rounded-pill px-4 fw-bold">Apply Now</a>
                </div>

                <div class="career-card d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <span class="dept-tag mb-2">Partnerships</span>
                        <h4 class="fw-bold mb-1">Restaurant Onboarding Executive</h4>
                        <div class="text-muted small">Pune / Hyderabad &bull; Full-time &bull; Hybrid</div>
                    </div>
                    <a href="mailto:careers@{{ strtolower($appName) }}.com?subject=Application:%20Restaurant%20Onboarding%20Executive" class="btn btn-outline-dark rounded-pill px-4 fw-bold">Apply Now</a>
                </div>
            </div>
        </div>
    </section>

    @include('partials.frontend-footer')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @include('partials.web-visit-tracker', ['panel' => 'frontend'])
</body>
</html>