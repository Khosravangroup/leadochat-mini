<x-layouts.public
    :title="'AI Customer Operations Platform for Messaging, Sales, Support & Voice | Leadochat'"
    :meta="'Automate WhatsApp, Instagram, Telegram, Facebook, and live chat. Manage every message in one inbox, convert leads faster, and run sales and support from one platform.'"
>
    <section class="ud-hero" id="home">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <div class="ud-hero-content wow fadeInUp" data-wow-delay=".1s">
                        <span class="lc-channel-pill mb-4">AI customer operations platform</span>
                        <h1 class="ud-hero-title">Turn Conversations Into Customers</h1>
                        <p class="ud-hero-desc">
                            Automate WhatsApp, Instagram, Telegram, Facebook, and live chat. Manage every message in one inbox,
                            convert leads faster, and run sales and support from one platform.
                        </p>
                        <ul class="ud-hero-buttons">
                            <li>
                                <a href="{{ route('register') }}" class="ud-main-btn ud-white-btn">Start free</a>
                            </li>
                            <li>
                                <a href="{{ route('contact') }}" class="ud-main-btn ud-link-btn">
                                    Book a demo <i class="lni lni-arrow-right"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="lc-hero-panel wow fadeInUp" data-wow-delay=".2s">
                        <x-public.conversation-animation label="Messages become customers" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="ud-features">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="ud-section-title mx-auto text-center">
                        <span>Why teams switch</span>
                        <h2>Too many channels. Too many missed opportunities.</h2>
                        <p>
                            Leadochat brings customer messaging, automation, sales, support, social commerce, and analytics into one operating layer.
                        </p>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="lc-feature-card wow fadeInUp" data-wow-delay=".1s">
                        <div class="ud-feature-icon"><i class="lni lni-inbox"></i></div>
                        <h3>One unified inbox</h3>
                        <p>Manage WhatsApp, Instagram, Telegram, Facebook, and live chat from one shared team workspace.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="lc-feature-card wow fadeInUp" data-wow-delay=".15s">
                        <div class="ud-feature-icon"><i class="lni lni-bolt"></i></div>
                        <h3>Automation that converts</h3>
                        <p>Capture, qualify, nurture, route, and follow up with leads before they disappear into another app.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="lc-feature-card wow fadeInUp" data-wow-delay=".2s">
                        <div class="ud-feature-icon"><i class="lni lni-bar-chart"></i></div>
                        <h3>Revenue intelligence</h3>
                        <p>Understand which channels, campaigns, customers, and agents are driving sales and support outcomes.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="ud-about lc-section-soft">
        <div class="container">
            <div class="ud-about-wrapper wow fadeInUp" data-wow-delay=".1s">
                <div class="ud-about-content-wrapper">
                    <div class="ud-about-content">
                        <span class="tag">Platform pillars</span>
                        <h2>Messaging, sales, support, voice, and social commerce in one customer workspace.</h2>
                        <p>
                            Leadochat is built for teams that need more than a chatbot and more than a shared inbox.
                            It connects customer conversations to workflow, context, and measurable outcomes.
                        </p>
                        <div class="lc-link-list mt-4">
                            <a href="{{ route('public.omnichannel-inbox') }}">Unified inbox</a>
                            <a href="{{ route('public.instagram-dm-automation') }}">Instagram automation</a>
                            <a href="{{ route('public.whatsapp-business-automation') }}">WhatsApp automation</a>
                            <a href="{{ route('public.customer-analytics') }}">Customer analytics</a>
                        </div>
                    </div>
                </div>
                <div class="ud-about-image">
                    <img src="{{ asset('leadochat-site/assets/images/about/about-image.svg') }}" alt="Leadochat automation workflow">
                </div>
            </div>
        </div>
    </section>

    <section class="ud-pricing">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="ud-section-title mb-0">
                        <span>Launch path</span>
                        <h2>Start with the channel that drives your revenue.</h2>
                        <p>
                            Use Leadochat for one high-intent channel first, then expand into inbox, automation, analytics, and team workflows.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="{{ route('pricing') }}" class="ud-main-btn">Compare plans</a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
