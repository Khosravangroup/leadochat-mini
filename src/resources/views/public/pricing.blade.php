<x-layouts.public
    :title="'Pricing | Leadochat'"
    :meta="'Compare Leadochat plans for messaging, automation, shared inbox workflows, analytics, and customer operations.'"
>
    <section class="lc-page-hero">
        <div class="container text-center">
            <span class="lc-channel-pill mb-4">Pricing</span>
            <h1 class="display-4 fw-bold mb-4">Start with one channel. Scale into full customer operations.</h1>
            <p class="lead mx-auto" style="max-width: 760px;">
                Choose a practical starting plan for inbox and automation, then expand as your team adds channels, agents, analytics, and advanced workflows.
            </p>
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
</x-layouts.public>
