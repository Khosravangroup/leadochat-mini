<x-layouts.public
    :title="'Messaging, Automation, Inbox, Analytics & Social Commerce Features | Leadochat'"
    :meta="'Explore Leadochat features for omnichannel messaging, Instagram and WhatsApp automation, shared inbox workflows, analytics, and customer operations.'"
>
    <section class="lc-page-hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="lc-channel-pill mb-4">Feature overview</span>
                    <h1 class="display-4 fw-bold mb-4">One platform for the customer work that happens after the first message.</h1>
                    <p class="lead">Manage conversations, automate follow-up, assign agents, publish and moderate social content, and measure what drives revenue.</p>
                </div>
                <div class="col-lg-5">
                    <div class="lc-hero-panel">
                        <img src="{{ asset('leadochat-site/leadochat-mini.png') }}" alt="Leadochat feature overview">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="ud-features">
        <div class="container">
            <div class="row g-4">
                @php
                    $features = [
                        ['icon' => 'lni-inbox', 'title' => 'Omnichannel inbox', 'body' => 'One shared inbox for WhatsApp, Instagram, Telegram, Facebook, and live chat with customer history.'],
                        ['icon' => 'lni-instagram', 'title' => 'Instagram automation', 'body' => 'Manage DMs, comments, story replies, post publishing, and social follow-up from one workspace.'],
                        ['icon' => 'lni-whatsapp', 'title' => 'WhatsApp automation', 'body' => 'Capture leads, qualify prospects, run reminders, and hand off conversations to agents.'],
                        ['icon' => 'lni-facebook', 'title' => 'Messenger workflows', 'body' => 'Bring Facebook conversations into the same sales and support operating layer.'],
                        ['icon' => 'lni-bar-chart', 'title' => 'Customer analytics', 'body' => 'Measure channel performance, campaigns, conversations, and revenue signals.'],
                        ['icon' => 'lni-users', 'title' => 'Team operations', 'body' => 'Assign conversations, add notes, route ownership, and keep every agent aligned.'],
                    ];
                @endphp

                @foreach ($features as $feature)
                    <div class="col-lg-4 col-md-6">
                        <div class="lc-feature-card h-100 wow fadeInUp" data-wow-delay=".1s">
                            <div class="ud-feature-icon"><i class="lni {{ $feature['icon'] }}"></i></div>
                            <h3>{{ $feature['title'] }}</h3>
                            <p>{{ $feature['body'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="ud-about lc-section-soft">
        <div class="container">
            <div class="ud-section-title mx-auto text-center">
                <span>Explore pages</span>
                <h2>Choose the workflow you want to improve first.</h2>
            </div>
            <div class="lc-link-list text-center">
                <a href="{{ route('public.omnichannel-inbox') }}">Omnichannel Inbox</a>
                <a href="{{ route('public.instagram-dm-automation') }}">Instagram DM Automation</a>
                <a href="{{ route('public.whatsapp-business-automation') }}">WhatsApp Business Automation</a>
                <a href="{{ route('public.customer-analytics') }}">Customer Analytics</a>
                <a href="{{ route('public.whatsapp-sales-funnel') }}">WhatsApp Sales Funnel</a>
                <a href="{{ route('public.shared-inbox-customer-support') }}">Shared Support Inbox</a>
            </div>
        </div>
    </section>
</x-layouts.public>
