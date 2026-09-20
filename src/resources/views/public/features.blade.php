<x-layouts.public
    :title="'Messaging, Automation, Inbox, Analytics & Social Commerce Features | Leadochat'"
    :meta="'Explore Leadochat features for omnichannel messaging, Instagram and WhatsApp automation, shared inbox workflows, analytics, social commerce, and customer operations.'"
>
    @once
        <style>
            .lc-features-hero {
                padding: 152px 0 86px;
                background: linear-gradient(180deg, #0f766e 0%, #134e4a 100%);
                color: #fff;
            }

            .lc-features-hero h1 {
                max-width: 760px;
                color: #fff;
                font-size: 56px;
                line-height: 1.08;
            }

            .lc-features-hero p {
                max-width: 690px;
                color: rgba(255, 255, 255, .84);
                line-height: 1.75;
            }

            .lc-feature-proof-row {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 14px;
                margin-top: 46px;
            }

            .lc-feature-proof {
                min-height: 92px;
                border-radius: 8px;
                padding: 18px;
                background: rgba(255, 255, 255, .1);
                border: 1px solid rgba(255, 255, 255, .18);
                color: #fff;
            }

            .lc-feature-proof strong {
                display: block;
                color: #fff8d7;
                font-size: 24px;
                line-height: 1;
                margin-bottom: 9px;
            }

            .lc-feature-proof span {
                display: block;
                color: rgba(255, 255, 255, .82);
                font-size: 13px;
                font-weight: 800;
                line-height: 1.45;
            }

            .lc-features-band {
                padding: 92px 0;
            }

            .lc-features-band-soft {
                background: #f7fbfa;
            }

            .lc-capability-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 16px;
            }

            .lc-capability-card,
            .lc-workflow-card,
            .lc-channel-card,
            .lc-feature-faq {
                border-radius: 8px;
                border: 1px solid #d9e7e3;
                background: #fff;
                box-shadow: 0 14px 40px rgba(15, 23, 42, .06);
            }

            .lc-capability-card {
                min-height: 238px;
                padding: 26px;
            }

            .lc-capability-card i,
            .lc-workflow-card i {
                display: grid;
                place-items: center;
                width: 44px;
                height: 44px;
                border-radius: 8px;
                color: #134e4a;
                background: linear-gradient(135deg, #ffd84d, #f97316);
                font-size: 21px;
                margin-bottom: 18px;
            }

            .lc-capability-card h3,
            .lc-workflow-card h3,
            .lc-channel-card h3,
            .lc-feature-faq h3 {
                color: #111827;
                font-size: 20px;
                font-weight: 900;
                margin-bottom: 10px;
            }

            .lc-capability-card p,
            .lc-workflow-card p,
            .lc-channel-card p,
            .lc-feature-faq p {
                color: #5b6575;
                line-height: 1.72;
                margin-bottom: 0;
            }

            .lc-workflow-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 18px;
            }

            .lc-workflow-card {
                min-height: 220px;
                padding: 28px;
            }

            .lc-channel-list {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 16px;
            }

            .lc-channel-card {
                min-height: 202px;
                padding: 24px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }

            .lc-channel-card a {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                color: #0f766e;
                font-weight: 900;
                text-decoration: none;
                margin-top: 18px;
            }

            .lc-feature-faq-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 16px;
            }

            .lc-feature-faq {
                padding: 24px;
            }

            .lc-features-cta {
                border-radius: 8px;
                padding: 42px;
                background: linear-gradient(135deg, #0f766e, #134e4a);
                color: #fff;
            }

            .lc-features-cta h2,
            .lc-features-cta p {
                color: #fff;
            }

            @media (max-width: 991px) {
                .lc-features-hero {
                    padding-top: 132px;
                }

                .lc-features-hero h1 {
                    font-size: 42px;
                }

                .lc-feature-proof-row,
                .lc-capability-grid,
                .lc-workflow-grid,
                .lc-channel-list,
                .lc-feature-faq-grid {
                    grid-template-columns: 1fr;
                }

                .lc-features-band {
                    padding: 72px 0;
                }
            }

            @media (max-width: 575px) {
                .lc-features-hero h1 {
                    font-size: 36px;
                }

                .lc-features-cta {
                    padding: 28px;
                }
            }
        </style>
    @endonce

    <section class="lc-features-hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <div class="wow fadeInUp" data-wow-delay=".1s">
                        <span class="lc-channel-pill mb-4">Feature overview</span>
                        <h1 class="fw-bold mb-4">Every customer workflow connected to the conversation.</h1>
                        <p class="lead mb-4">
                            Manage messages, automate follow-up, publish and moderate social content, send catalog products,
                            assign agents, and measure what drives revenue from one customer operations workspace.
                        </p>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="{{ route('register') }}" class="ud-main-btn ud-white-btn">Start free</a>
                            <a href="{{ route('contact') }}" class="ud-main-btn ud-link-btn">
                                Book a demo <i class="lni lni-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="wow fadeInUp" data-wow-delay=".2s">
                        <x-public.features-animation label="Connected feature map" />
                    </div>
                </div>
            </div>

            <div class="lc-feature-proof-row">
                <div class="lc-feature-proof"><strong>Inbox</strong><span>One history per customer across channels.</span></div>
                <div class="lc-feature-proof"><strong>AI</strong><span>Capture, qualify, route, and follow up.</span></div>
                <div class="lc-feature-proof"><strong>Shop</strong><span>Catalogs, product cards, and social commerce.</span></div>
                <div class="lc-feature-proof"><strong>Data</strong><span>Analytics for channels, agents, and outcomes.</span></div>
            </div>
        </div>
    </section>

    <section class="lc-features-band">
        <div class="container">
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <div class="ud-section-title mx-auto text-center">
                        <span>Core capabilities</span>
                        <h2>Build the operation around every message.</h2>
                        <p>
                            Leadochat combines the clarity of a shared inbox, the conversion power of automation,
                            and the context teams need to sell, support, and grow.
                        </p>
                    </div>
                </div>
            </div>

            @php
                $capabilities = [
                    ['icon' => 'lni-inbox', 'title' => 'Omnichannel inbox', 'body' => 'Unify WhatsApp, Instagram, Telegram, Facebook, and live chat with one customer history and clear ownership.'],
                    ['icon' => 'lni-bolt', 'title' => 'Automation builder', 'body' => 'Capture intent, qualify leads, route conversations, trigger reminders, and keep follow-up moving.'],
                    ['icon' => 'lni-instagram', 'title' => 'Instagram operations', 'body' => 'Manage DMs, comments, story replies, post publishing, product tags, and social selling workflows.'],
                    ['icon' => 'lni-whatsapp', 'title' => 'WhatsApp workflows', 'body' => 'Turn WhatsApp into a structured sales and support channel with handoff, customer context, and nurture flows.'],
                    ['icon' => 'lni-cart', 'title' => 'Social commerce', 'body' => 'Create catalogs, send product cards, prepare collections, model offers, and connect products to conversations.'],
                    ['icon' => 'lni-bar-chart', 'title' => 'Customer analytics', 'body' => 'See channel performance, campaign signals, customer value, agent activity, and revenue-focused outcomes.'],
                ];
            @endphp

            <div class="lc-capability-grid">
                @foreach ($capabilities as $index => $feature)
                    <div class="lc-capability-card wow fadeInUp" data-wow-delay=".{{ $index + 1 }}s">
                        <i class="lni {{ $feature['icon'] }}"></i>
                        <h3>{{ $feature['title'] }}</h3>
                        <p>{{ $feature['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="lc-features-band lc-features-band-soft">
        <div class="container">
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <div class="ud-section-title mx-auto text-center">
                        <span>Workflow groups</span>
                        <h2>Designed for repeated daily work, not one-off demos.</h2>
                        <p>
                            Sales, support, social, and operations teams can work from the same source of customer truth.
                        </p>
                    </div>
                </div>
            </div>

            <div class="lc-workflow-grid">
                <div class="lc-workflow-card wow fadeInUp" data-wow-delay=".05s">
                    <i class="lni lni-users"></i>
                    <h3>Team ownership</h3>
                    <p>Assign conversations, add private notes, organize tags, route departments, and keep agent handoff visible.</p>
                </div>
                <div class="lc-workflow-card wow fadeInUp" data-wow-delay=".1s">
                    <i class="lni lni-user"></i>
                    <h3>360° customer profile</h3>
                    <p>Show identity, channel history, conversation context, product shares, notes, and activity beside every chat.</p>
                </div>
                <div class="lc-workflow-card wow fadeInUp" data-wow-delay=".15s">
                    <i class="lni lni-gallery"></i>
                    <h3>Media and message handling</h3>
                    <p>Handle text, images, video, voice, files, product cards, comment replies, story replies, and reactions.</p>
                </div>
                <div class="lc-workflow-card wow fadeInUp" data-wow-delay=".2s">
                    <i class="lni lni-plug"></i>
                    <h3>Integrations and API</h3>
                    <p>Connect Shopify, WooCommerce, WordPress, Zapier, Make, n8n, and internal systems through API-ready workflows.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="lc-features-band">
        <div class="container">
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <div class="ud-section-title mx-auto text-center">
                        <span>Explore by workflow</span>
                        <h2>Start with the problem your team feels most.</h2>
                    </div>
                </div>
            </div>

            @php
                $pages = [
                    ['title' => 'Omnichannel inbox', 'body' => 'Stop missed replies and duplicated follow-ups with one team workspace.', 'route' => 'public.omnichannel-inbox'],
                    ['title' => 'Instagram DM automation', 'body' => 'Convert comments, story replies, and DMs into qualified conversations.', 'route' => 'public.instagram-dm-automation'],
                    ['title' => 'WhatsApp automation', 'body' => 'Capture, qualify, nurture, and support customers through WhatsApp.', 'route' => 'public.whatsapp-business-automation'],
                    ['title' => 'Customer analytics', 'body' => 'Understand channels, campaigns, customers, and team performance.', 'route' => 'public.customer-analytics'],
                    ['title' => 'WhatsApp sales funnel', 'body' => 'Move inbound WhatsApp leads through qualification and follow-up.', 'route' => 'public.whatsapp-sales-funnel'],
                    ['title' => 'Shared support inbox', 'body' => 'Give support teams ownership, history, escalation, and reporting.', 'route' => 'public.shared-inbox-customer-support'],
                ];
            @endphp

            <div class="lc-channel-list">
                @foreach ($pages as $page)
                    <div class="lc-channel-card">
                        <div>
                            <h3>{{ $page['title'] }}</h3>
                            <p>{{ $page['body'] }}</p>
                        </div>
                        <a href="{{ route($page['route']) }}">
                            View workflow <i class="lni lni-arrow-right"></i>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="lc-features-band lc-features-band-soft">
        <div class="container">
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <div class="ud-section-title mx-auto text-center">
                        <span>FAQ</span>
                        <h2>What teams usually ask before choosing features.</h2>
                    </div>
                </div>
            </div>

            <div class="lc-feature-faq-grid">
                <div class="lc-feature-faq">
                    <h3>Can we start with only one channel?</h3>
                    <p>Yes. Most teams start with the channel that creates the most revenue or support pressure, then expand into automation and analytics.</p>
                </div>
                <div class="lc-feature-faq">
                    <h3>Does automation replace agents?</h3>
                    <p>No. Automation handles repetitive capture, routing, and follow-up while agents stay close to high-value conversations.</p>
                </div>
                <div class="lc-feature-faq">
                    <h3>Can sales and support work together?</h3>
                    <p>Yes. Tags, assignments, notes, departments, profiles, and histories help both teams work from the same customer context.</p>
                </div>
                <div class="lc-feature-faq">
                    <h3>Is social commerce part of the platform?</h3>
                    <p>Yes. Leadochat supports catalog modeling, product cards in inbox conversations, Instagram product tags, offers, sets, and collections.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-5">
        <div class="container">
            <div class="lc-features-cta">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <h2 class="mb-3">Choose the first workflow, then build the full customer operation.</h2>
                        <p class="mb-0">Start with the highest-volume channel, add automation where it helps, and keep every action tied to customer context.</p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <a href="{{ route('register') }}" class="ud-main-btn ud-white-btn">Start free</a>
                        <a href="{{ route('contact') }}" class="ud-main-btn ud-link-btn mt-2">Book a demo</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
