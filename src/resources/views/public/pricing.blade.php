<x-layouts.public
    :title="'Pricing | Leadochat'"
    :meta="'Compare Leadochat plans for messaging, automation, shared inbox workflows, analytics, and customer operations.'"
>
    <section class="lc-page-hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="lc-channel-pill mb-4">Pricing</span>
                    <h1 class="display-4 fw-bold mb-4">Start with one channel. Scale into full customer operations.</h1>
                    <p class="lead">
                        Choose a practical starting plan for inbox and automation, then expand as your team adds channels, agents, analytics, and advanced workflows.
                    </p>
                </div>
                <div class="col-lg-5">
                    <div class="lc-hero-panel wow fadeInUp" data-wow-delay=".2s">
                        <x-public.pricing-animation />
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="ud-pricing">
        <div class="container">
            <div class="row g-0 align-items-center justify-content-center">
                <div class="col-lg-4 col-md-6 col-sm-10">
                    <div class="ud-single-pricing first-item wow fadeInUp" data-wow-delay=".1s">
                        <div class="ud-pricing-header">
                            <h3>STARTER</h3>
                            <h4>$20/mo</h4>
                        </div>
                        <div class="ud-pricing-body">
                            <ul>
                                <li>Shared inbox foundation</li>
                                <li>One primary messaging channel</li>
                                <li>Basic automation flows</li>
                                <li>Customer profiles</li>
                                <li>Email support</li>
                            </ul>
                        </div>
                        <div class="ud-pricing-footer">
                            <a href="{{ route('register') }}" class="ud-main-btn ud-border-btn">Start free</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 col-sm-10">
                    <div class="ud-single-pricing active wow fadeInUp" data-wow-delay=".15s">
                        <span class="ud-popular-tag">POPULAR</span>
                        <div class="ud-pricing-header">
                            <h3>GROWTH</h3>
                            <h4>$79/mo</h4>
                        </div>
                        <div class="ud-pricing-body">
                            <ul>
                                <li>WhatsApp and Instagram workflows</li>
                                <li>Multi-agent inbox</li>
                                <li>Comment and DM operations</li>
                                <li>Automation and routing</li>
                                <li>Analytics dashboard</li>
                            </ul>
                        </div>
                        <div class="ud-pricing-footer">
                            <a href="{{ route('contact') }}" class="ud-main-btn ud-white-btn">Book a demo</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 col-sm-10">
                    <div class="ud-single-pricing last-item wow fadeInUp" data-wow-delay=".2s">
                        <div class="ud-pricing-header">
                            <h3>ENTERPRISE</h3>
                            <h4>Custom</h4>
                        </div>
                        <div class="ud-pricing-body">
                            <ul>
                                <li>Advanced channel operations</li>
                                <li>Custom workflows and integrations</li>
                                <li>Team roles and governance</li>
                                <li>Priority implementation support</li>
                                <li>Dedicated success planning</li>
                            </ul>
                        </div>
                        <div class="ud-pricing-footer">
                            <a href="{{ route('contact') }}" class="ud-main-btn ud-border-btn">Talk to sales</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="ud-faq lc-section-soft">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="ud-section-title text-center mx-auto">
                        <span>FAQ</span>
                        <h2>Pricing questions before you start</h2>
                        <p>Start practical, prove the workflow, then scale into the channels and automation your team needs.</p>
                    </div>
                </div>
            </div>

            @php
                $pricingFaqs = [
                    ['q' => 'Can I start with only one channel?', 'a' => 'Yes. Most teams start with the highest-volume channel, then add more inboxes, automation, and analytics when the workflow is ready.'],
                    ['q' => 'Do I need a large team to use Leadochat?', 'a' => 'No. A small team can use Leadochat as a shared inbox first, then add team routing and advanced automation as volume grows.'],
                    ['q' => 'Is Instagram automation included in the Growth plan?', 'a' => 'The Growth plan is designed for Instagram and WhatsApp workflows, including shared inbox operations and comment-to-DM style customer journeys.'],
                    ['q' => 'Can pricing change for custom integrations?', 'a' => 'Yes. Enterprise pricing depends on channels, integrations, implementation scope, and the level of support your operation needs.'],
                    ['q' => 'Can I book a setup call before choosing?', 'a' => 'Yes. The best next step is a short workflow review so the plan matches the channels and team process you actually use.'],
                    ['q' => 'Can I upgrade later?', 'a' => 'Yes. You can start small and move into more channels, agents, automation, and analytics when the business needs it.'],
                ];
            @endphp

            <div class="row g-4">
                @foreach ($pricingFaqs as $faq)
                    <div class="col-lg-6">
                        <div class="lc-page-card p-4 h-100">
                            <h3 class="h5 fw-bold mb-3">{{ $faq['q'] }}</h3>
                            <p class="mb-0 text-muted">{{ $faq['a'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-layouts.public>
