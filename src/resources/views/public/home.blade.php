<x-layouts.public
    :title="'AI Customer Operations Platform for Messaging, Sales, Support & Voice | Leadochat'"
    :meta="'Automate WhatsApp, Instagram, Telegram, Facebook, and live chat. Manage every message in one inbox, convert leads faster, and run sales and support from one platform.'"
>
    @once
        <style>
            .lc-home-hero {
                padding: 152px 0 78px;
                background: linear-gradient(180deg, #0f766e 0%, #134e4a 100%);
                color: #fff;
            }

            .lc-home-hero .ud-hero-title {
                max-width: 680px;
                color: #fff;
                font-size: 58px;
                line-height: 1.05;
            }

            .lc-home-hero .ud-hero-desc {
                max-width: 650px;
                color: rgba(255, 255, 255, .84);
                font-size: 18px;
                line-height: 1.75;
            }

            .lc-home-proof {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin: 26px 0 0;
                padding: 0;
                list-style: none;
            }

            .lc-home-proof li {
                display: inline-flex;
                align-items: center;
                min-height: 34px;
                border-radius: 8px;
                padding: 0 12px;
                color: #fff8d7;
                background: rgba(255, 255, 255, .12);
                font-size: 13px;
                font-weight: 800;
            }

            .lc-home-trust {
                margin-top: -38px;
                position: relative;
                z-index: 4;
            }

            .lc-trust-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 16px;
            }

            .lc-trust-item {
                min-height: 128px;
                border-radius: 8px;
                background: #fff;
                border: 1px solid #d9e7e3;
                box-shadow: 0 18px 48px rgba(15, 23, 42, .08);
                padding: 22px;
            }

            .lc-trust-item strong {
                display: block;
                color: #134e4a;
                font-size: 28px;
                line-height: 1;
                margin-bottom: 10px;
            }

            .lc-trust-item span {
                color: #5b6575;
                line-height: 1.65;
                font-weight: 600;
            }

            .lc-home-band {
                padding: 92px 0;
            }

            .lc-home-band-soft {
                background: #f7fbfa;
            }

            .lc-pillar-card {
                height: 100%;
                border-radius: 8px;
                border: 1px solid #d9e7e3;
                background: #fff;
                padding: 26px;
                box-shadow: 0 14px 40px rgba(15, 23, 42, .06);
            }

            .lc-pillar-icon {
                width: 46px;
                height: 46px;
                border-radius: 8px;
                display: grid;
                place-items: center;
                margin-bottom: 20px;
                color: #134e4a;
                background: linear-gradient(135deg, #ffd84d, #f97316);
                font-size: 22px;
            }

            .lc-pillar-card h3,
            .lc-feature-mini h3,
            .lc-pricing-preview h3 {
                color: #111827;
                font-size: 21px;
                font-weight: 900;
                margin-bottom: 12px;
            }

            .lc-pillar-card p,
            .lc-feature-mini p,
            .lc-pricing-preview p,
            .lc-faq-item p {
                color: #5b6575;
                line-height: 1.75;
                margin-bottom: 0;
            }

            .lc-home-visual {
                width: 100%;
                border-radius: 8px;
                border: 1px solid #d9e7e3;
                box-shadow: 0 18px 48px rgba(15, 23, 42, .08);
                background: #fff;
            }

            .lc-feature-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 16px;
            }

            .lc-feature-mini {
                border-radius: 8px;
                border: 1px solid #d9e7e3;
                background: #fff;
                padding: 22px;
                min-height: 182px;
            }

            .lc-feature-mini i {
                display: inline-grid;
                place-items: center;
                width: 38px;
                height: 38px;
                border-radius: 8px;
                margin-bottom: 16px;
                color: #134e4a;
                background: #fff8d7;
                font-size: 19px;
            }

            .lc-integration-list {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-top: 22px;
            }

            .lc-integration-list span {
                display: inline-flex;
                min-height: 36px;
                align-items: center;
                border-radius: 8px;
                padding: 0 13px;
                color: #134e4a;
                background: #fff;
                border: 1px solid #d9e7e3;
                font-weight: 800;
            }

            .lc-pricing-preview {
                border-radius: 8px;
                border: 1px solid #d9e7e3;
                background: #fff;
                padding: 30px;
                box-shadow: 0 18px 48px rgba(15, 23, 42, .08);
            }

            .lc-price {
                display: flex;
                align-items: flex-end;
                gap: 8px;
                color: #134e4a;
                margin: 16px 0 20px;
            }

            .lc-price strong {
                font-size: 42px;
                line-height: 1;
                font-weight: 900;
            }

            .lc-price span {
                color: #5b6575;
                font-weight: 800;
                padding-bottom: 5px;
            }

            .lc-faq-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 16px;
            }

            .lc-faq-item {
                border-radius: 8px;
                border: 1px solid #d9e7e3;
                background: #fff;
                padding: 24px;
            }

            .lc-faq-item h3 {
                color: #111827;
                font-size: 18px;
                font-weight: 900;
                margin-bottom: 10px;
            }

            .lc-home-cta {
                border-radius: 8px;
                padding: 42px;
                background: linear-gradient(135deg, #0f766e, #134e4a);
                color: #fff;
            }

            .lc-home-cta h2,
            .lc-home-cta p {
                color: #fff;
            }

            @media (max-width: 991px) {
                .lc-home-hero {
                    padding-top: 132px;
                }

                .lc-home-hero .ud-hero-title {
                    font-size: 44px;
                }

                .lc-trust-grid,
                .lc-feature-grid,
                .lc-faq-grid {
                    grid-template-columns: 1fr;
                }

                .lc-home-band {
                    padding: 72px 0;
                }
            }

            @media (max-width: 575px) {
                .lc-home-hero .ud-hero-title {
                    font-size: 38px;
                }

                .lc-home-proof {
                    display: grid;
                    grid-template-columns: 1fr;
                }

                .lc-home-cta {
                    padding: 28px;
                }
            }
        </style>
    @endonce

    <section class="ud-hero lc-home-hero" id="home">
        <div class="container">
            <div class="row align-items-center g-5">
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
                            <li>
                                <a href="{{ route('pricing') }}" class="ud-main-btn ud-link-btn">
                                    Compare platforms
                                </a>
                            </li>
                        </ul>
                        <ul class="lc-home-proof">
                            <li>Automation that converts</li>
                            <li>One unified inbox</li>
                            <li>Sales, support, voice, and intelligence</li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="wow fadeInUp" data-wow-delay=".2s">
                        <x-public.conversation-animation label="Messages become customers" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="lc-home-trust">
        <div class="container">
            <div class="lc-trust-grid">
                <div class="lc-trust-item wow fadeInUp" data-wow-delay=".05s">
                    <strong>5+</strong>
                    <span>messaging channels handled from one customer workspace.</span>
                </div>
                <div class="lc-trust-item wow fadeInUp" data-wow-delay=".1s">
                    <strong>1</strong>
                    <span>shared inbox for leads, support requests, social replies, and follow-up.</span>
                </div>
                <div class="lc-trust-item wow fadeInUp" data-wow-delay=".15s">
                    <strong>AI</strong>
                    <span>automation for capture, qualification, routing, nurture, and conversion.</span>
                </div>
            </div>
        </div>
    </section>

    <section class="lc-home-band">
        <div class="container">
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <div class="ud-section-title mx-auto text-center">
                        <span>Why teams switch</span>
                        <h2>Too many channels. Too many missed opportunities.</h2>
                        <p>
                            Leadochat brings customer messaging, automation, sales, support, social commerce, voice, and analytics
                            into one operating layer, so every conversation can move toward a measurable outcome.
                        </p>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="lc-pillar-card wow fadeInUp" data-wow-delay=".05s">
                        <div class="lc-pillar-icon"><i class="lni lni-bolt"></i></div>
                        <h3>Capture and convert leads</h3>
                        <p>
                            Build flows that capture intent, qualify customers, trigger follow-ups, and route hot conversations to the right agent.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="lc-pillar-card wow fadeInUp" data-wow-delay=".1s">
                        <div class="lc-pillar-icon"><i class="lni lni-inbox"></i></div>
                        <h3>Reply from one inbox</h3>
                        <p>
                            Manage WhatsApp, Instagram, Telegram, Facebook, and live chat with shared ownership and customer context.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="lc-pillar-card wow fadeInUp" data-wow-delay=".15s">
                        <div class="lc-pillar-icon"><i class="lni lni-bar-chart"></i></div>
                        <h3>Run the whole operation</h3>
                        <p>
                            Connect sales, support, voice, social commerce, customer profiles, and reporting in the same workspace.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="lc-home-band lc-home-band-soft">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <img
                        class="lc-home-visual wow fadeInUp"
                        data-wow-delay=".05s"
                        src="{{ asset('leadochat-site/assets/images/home/customer-ops-dashboard.svg') }}"
                        alt="Leadochat customer operations dashboard"
                    >
                </div>
                <div class="col-lg-6">
                    <div class="ud-section-title mb-4">
                        <span>Unified customer workspace</span>
                        <h2>More than a chatbot. More than a shared inbox.</h2>
                        <p>
                            Leadochat connects every customer conversation to the workflow around it:
                            profile context, automation, products, follow-up, operators, and revenue insight.
                        </p>
                    </div>
                    <div class="lc-link-list">
                        <a href="{{ route('public.omnichannel-inbox') }}">Unified inbox</a>
                        <a href="{{ route('public.instagram-dm-automation') }}">Instagram DM automation</a>
                        <a href="{{ route('public.whatsapp-business-automation') }}">WhatsApp automation</a>
                        <a href="{{ route('public.customer-analytics') }}">Customer analytics</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="lc-home-band" id="automation-builder">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 order-lg-2">
                    <img
                        class="lc-home-visual wow fadeInUp"
                        data-wow-delay=".05s"
                        src="{{ asset('leadochat-site/assets/images/home/automation-pipeline.svg') }}"
                        alt="Lead automation workflow from capture to conversion"
                    >
                </div>
                <div class="col-lg-6 order-lg-1">
                    <div class="ud-section-title mb-0">
                        <span>Automation builder</span>
                        <h2>Capture, qualify, nurture, and convert before the lead goes cold.</h2>
                        <p>
                            Use automation for the repetitive moments: first replies, qualification, routing,
                            reminders, product recommendations, and follow-up. Keep human agents ready for the conversations that need them.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="lc-home-band lc-home-band-soft" id="conversational-sales-crm">
        <div class="container">
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <div class="ud-section-title mx-auto text-center">
                        <span>Feature cluster</span>
                        <h2>Everything around the conversation, connected.</h2>
                        <p>
                            Give teams the context and tools they need to move from message to sale, support outcome, or next best action.
                        </p>
                    </div>
                </div>
            </div>

            <div class="lc-feature-grid">
                <div class="lc-feature-mini wow fadeInUp" data-wow-delay=".05s">
                    <i class="lni lni-user"></i>
                    <h3>360° profile</h3>
                    <p>See customer history, tags, channel activity, orders, notes, and ownership beside every chat.</p>
                </div>
                <div class="lc-feature-mini wow fadeInUp" data-wow-delay=".1s">
                    <i class="lni lni-comments-alt"></i>
                    <h3>AI chatbot</h3>
                    <p>Answer common questions, collect details, recommend products, and hand off when a human should step in.</p>
                </div>
                <div class="lc-feature-mini wow fadeInUp" data-wow-delay=".15s">
                    <i class="lni lni-gallery"></i>
                    <h3>Media management</h3>
                    <p>Handle images, videos, voice, files, product cards, comment replies, and social context from the same inbox.</p>
                </div>
                <div class="lc-feature-mini wow fadeInUp" data-wow-delay=".2s">
                    <i class="lni lni-cart"></i>
                    <h3>Social commerce</h3>
                    <p>Use Instagram posts, DMs, catalogs, product tags, offers, collections, and commerce evidence in one flow.</p>
                </div>
                <div class="lc-feature-mini wow fadeInUp" data-wow-delay=".25s">
                    <i class="lni lni-mic"></i>
                    <h3>Call transcription</h3>
                    <p>Connect voice conversations to the same customer timeline, follow-up workflow, and reporting layer.</p>
                </div>
                <div class="lc-feature-mini wow fadeInUp" data-wow-delay=".3s">
                    <i class="lni lni-dashboard"></i>
                    <h3>Operator analytics</h3>
                    <p>Track response quality, channel performance, customer behavior, sales outcomes, and team workload.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="lc-home-band">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="ud-section-title mb-0">
                        <span>Integrations</span>
                        <h2>Connect the tools that already run your business.</h2>
                        <p>
                            Bring commerce, automation, websites, and internal systems into customer conversations without rebuilding the stack.
                        </p>
                        <div class="lc-integration-list">
                            <span>Shopify</span>
                            <span>WooCommerce</span>
                            <span>WordPress</span>
                            <span>Zapier</span>
                            <span>Make</span>
                            <span>n8n</span>
                            <span>API</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img
                        class="lc-home-visual wow fadeInUp"
                        data-wow-delay=".05s"
                        src="{{ asset('leadochat-site/assets/images/home/integrations-network.svg') }}"
                        alt="Leadochat integrations network"
                    >
                </div>
            </div>
        </div>
    </section>

    <section class="lc-home-band lc-home-band-soft">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <div class="ud-section-title mb-0">
                        <span>Pricing preview</span>
                        <h2>Start small, then expand into the full customer operation.</h2>
                        <p>
                            Launch with one high-intent channel, then add automation, inbox workflows, social commerce, analytics, and team operations as you grow.
                        </p>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="lc-pricing-preview wow fadeInUp" data-wow-delay=".05s">
                        <h3>Plans from</h3>
                        <div class="lc-price"><strong>$20</strong><span>to custom enterprise</span></div>
                        <p>Choose the plan that fits your channel volume, agents, automation depth, and commerce needs.</p>
                        <div class="mt-4 d-flex flex-wrap gap-2">
                            <a href="{{ route('pricing') }}" class="ud-main-btn">Compare plans</a>
                            <a href="{{ route('contact') }}" class="ud-main-btn ud-border-btn">Book a demo</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="lc-home-band">
        <div class="container">
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <div class="ud-section-title mx-auto text-center">
                        <span>FAQ</span>
                        <h2>Clear answers before the first demo.</h2>
                    </div>
                </div>
            </div>

            <div class="lc-faq-grid">
                <div class="lc-faq-item">
                    <h3>What is an AI customer operations platform?</h3>
                    <p>
                        It is a system that connects messaging, automation, sales workflows, support workflows, voice, analytics, and customer context in one place.
                    </p>
                </div>
                <div class="lc-faq-item">
                    <h3>How is Leadochat different from a chatbot tool?</h3>
                    <p>
                        Chatbots handle replies. Leadochat also gives teams an inbox, customer profiles, social commerce, routing, human handoff, and reporting.
                    </p>
                </div>
                <div class="lc-faq-item">
                    <h3>Which channels can teams manage?</h3>
                    <p>
                        Teams can work across WhatsApp, Instagram, Telegram, Facebook, and live chat, with room for automation and integrations around those channels.
                    </p>
                </div>
                <div class="lc-faq-item">
                    <h3>Can sales and support use the same workspace?</h3>
                    <p>
                        Yes. Tags, assignments, notes, customer history, and analytics help sales and support work together without losing context.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-5">
        <div class="container">
            <div class="lc-home-cta">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <h2 class="mb-3">Turn your busiest messaging channel into a customer operation.</h2>
                        <p class="mb-0">
                            Start with inbox clarity, add automation where it helps, and keep every customer action visible.
                        </p>
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
