@props([
    'title' => 'Leadochat',
    'meta' => 'Automate messaging, sales, support, and social customer operations from one shared platform.',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $meta }}">
    <title>{{ $title }}</title>
    <link rel="shortcut icon" href="{{ asset('leadochat-site/assets/images/favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('leadochat-site/assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('leadochat-site/assets/css/animate.css') }}">
    <link rel="stylesheet" href="{{ asset('leadochat-site/assets/css/lineicons.css') }}">
    <link rel="stylesheet" href="{{ asset('leadochat-site/assets/css/ud-styles.css') }}">
    <style>
        :root {
            --leadochat-blue: #3056d3;
            --leadochat-ink: #111827;
            --leadochat-soft: #f7f9ff;
        }

        body {
            color: var(--leadochat-ink);
        }

        .ud-header {
            background: var(--leadochat-blue);
        }

        .ud-header.sticky {
            background: #fff;
            box-shadow: 0 8px 30px rgba(15, 23, 42, .08);
        }

        .lc-logo-mark {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            object-fit: contain;
            background: #fff;
            padding: 4px;
        }

        .lc-logo-word {
            height: 34px;
            width: auto;
            object-fit: contain;
        }

        .ud-header.sticky .lc-brand-text,
        .ud-header.sticky .navbar-nav .nav-item a,
        .ud-header.sticky .ud-login-btn {
            color: #111827;
        }

        .lc-brand-text {
            color: #fff;
            font-weight: 800;
            font-size: 20px;
            letter-spacing: 0;
        }

        .lc-section-soft {
            background: var(--leadochat-soft);
        }

        .lc-channel-pill {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            border-radius: 8px;
            padding: 0 12px;
            background: rgba(48, 86, 211, .1);
            color: #2446b8;
            font-weight: 700;
            font-size: 13px;
        }

        .lc-proof-card,
        .lc-feature-card,
        .lc-page-card {
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: #fff;
            box-shadow: 0 14px 40px rgba(15, 23, 42, .06);
        }

        .lc-feature-card {
            padding: 28px;
            height: 100%;
        }

        .lc-feature-card h3 {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 12px;
            color: #111827;
        }

        .lc-feature-card p,
        .lc-feature-card li {
            color: #5b6575;
            line-height: 1.75;
        }

        .lc-page-hero {
            padding: 150px 0 90px;
            background: linear-gradient(180deg, #3056d3 0%, #2545ad 100%);
            color: #fff;
        }

        .lc-page-hero h1,
        .lc-page-hero p {
            color: #fff;
        }

        .lc-hero-panel {
            border-radius: 8px;
            background: #fff;
            padding: 20px;
            box-shadow: 0 25px 70px rgba(15, 23, 42, .18);
        }

        .lc-hero-panel img {
            width: 100%;
            border-radius: 8px;
        }

        .lc-link-list a {
            display: inline-flex;
            margin: 0 10px 10px 0;
            border-radius: 8px;
            border: 1px solid #dbe3ff;
            padding: 9px 12px;
            color: #2446b8;
            font-weight: 700;
            text-decoration: none;
            background: #fff;
        }

        .ud-hero .lc-channel-pill,
        .lc-page-hero .lc-channel-pill {
            background: rgba(255, 255, 255, .16);
            color: #fff;
        }

        .lc-footer-logo {
            width: 44px;
            height: 44px;
            object-fit: contain;
            border-radius: 8px;
            background: #fff;
            padding: 4px;
        }

        .navbar-toggler .toggler-icon {
            background: #fff;
        }

        .ud-header.sticky .navbar-toggler .toggler-icon {
            background: #111827;
        }

        @media (max-width: 991px) {
            .navbar-collapse {
                background: #fff;
                border-radius: 8px;
                padding: 18px;
                margin-top: 14px;
            }

            .navbar-collapse .navbar-nav .nav-item a {
                color: #111827;
            }

            .lc-page-hero {
                padding-top: 130px;
            }
        }
    </style>
</head>
<body>
    <x-public.navbar />

    <main>
        {{ $slot }}
    </main>

    <x-public.footer />

    <script src="{{ asset('leadochat-site/assets/js/wow.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const header = document.querySelector('.ud-header');
            const toggler = document.querySelector('.navbar-toggler');
            const collapse = document.querySelector('.navbar-collapse');
            const backToTop = document.querySelector('.back-to-top');

            const updateHeader = function () {
                if (!header) return;
                header.classList.toggle('sticky', window.scrollY > 10);
                if (backToTop) {
                    backToTop.style.display = window.scrollY > 80 ? 'flex' : 'none';
                }
            };

            toggler?.addEventListener('click', function () {
                toggler.classList.toggle('active');
                collapse?.classList.toggle('show');
            });

            document.querySelectorAll('.ud-menu-scroll').forEach(function (link) {
                link.addEventListener('click', function () {
                    toggler?.classList.remove('active');
                    collapse?.classList.remove('show');
                });
            });

            backToTop?.addEventListener('click', function (event) {
                event.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });

            if (window.WOW) {
                new WOW().init();
            }

            updateHeader();
            window.addEventListener('scroll', updateHeader, { passive: true });
        });
    </script>
</body>
</html>
