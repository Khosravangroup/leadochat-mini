<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Leadochat') }}</title>
        <link rel="shortcut icon" href="{{ asset('leadochat-site/brand-mark.svg') }}" type="image/svg+xml">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body {
                margin: 0;
                color: #111827;
                background:
                    radial-gradient(circle at 12% 12%, rgba(255, 216, 77, .18), transparent 25%),
                    radial-gradient(circle at 88% 18%, rgba(249, 115, 22, .16), transparent 28%),
                    linear-gradient(135deg, #f7fbfa 0%, #eef8f5 100%);
            }

            .lc-auth-shell {
                min-height: 100vh;
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(360px, 500px);
            }

            .lc-auth-story {
                display: flex;
                flex-direction: column;
                justify-content: center;
                gap: 34px;
                padding: 48px clamp(28px, 6vw, 86px);
                background:
                    radial-gradient(circle at 26% 20%, rgba(255, 216, 77, .22), transparent 24%),
                    linear-gradient(180deg, #0f766e 0%, #134e4a 100%);
                color: #fff;
            }

            .lc-auth-brand {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                color: #fff;
                text-decoration: none;
                font-size: 22px;
                font-weight: 900;
            }

            .lc-auth-brand img {
                width: 46px;
                height: 46px;
            }

            .lc-auth-copy h1 {
                max-width: 620px;
                margin: 0 0 16px;
                color: #fff;
                font-size: clamp(34px, 5vw, 62px);
                line-height: 1.02;
                font-weight: 900;
                letter-spacing: 0;
            }

            .lc-auth-copy p {
                max-width: 560px;
                margin: 0;
                color: rgba(255, 255, 255, .84);
                font-size: 18px;
                line-height: 1.75;
            }

            .lc-auth-panel {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 36px 24px;
            }

            .lc-auth-card {
                width: 100%;
                max-width: 430px;
                border: 1px solid rgba(15, 118, 110, .14);
                border-radius: 8px;
                background: rgba(255, 255, 255, .94);
                box-shadow: 0 26px 70px rgba(15, 23, 42, .12);
                padding: 32px;
            }

            .lc-auth-card h2 {
                margin: 0 0 8px;
                color: #134e4a;
                font-size: 28px;
                font-weight: 900;
            }

            .lc-auth-card p {
                margin: 0 0 28px;
                color: #64748b;
                line-height: 1.65;
            }

            .lc-auth-link {
                color: #0f766e;
                font-weight: 800;
                text-decoration: none;
            }

            .lc-auth-link:hover {
                color: #f97316;
            }

            @media (max-width: 980px) {
                .lc-auth-shell {
                    grid-template-columns: 1fr;
                }

                .lc-auth-story {
                    min-height: auto;
                    padding: 28px 22px 36px;
                }

                .lc-auth-copy h1 {
                    font-size: 36px;
                }
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="lc-auth-shell">
            <section class="lc-auth-story">
                <a href="{{ route('home') }}" class="lc-auth-brand">
                    <img src="{{ asset('leadochat-site/brand-mark.svg') }}" alt="Leadochat logo">
                    <span>Leadochat</span>
                </a>

                <div class="lc-auth-copy">
                    <h1>Keep every customer conversation moving.</h1>
                    <p>
                        Manage Instagram, WhatsApp, live chat, automation, and team handoffs from one focused workspace.
                    </p>
                </div>

                <x-public.conversation-animation label="Your channels, one team inbox" />
            </section>

            <main class="lc-auth-panel">
                <div class="lc-auth-card">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
