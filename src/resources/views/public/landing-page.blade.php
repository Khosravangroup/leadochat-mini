<x-layouts.public :title="$page['title']" :meta="$page['meta']">
    <section class="lc-page-hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <div class="wow fadeInUp" data-wow-delay=".1s">
                        <span class="lc-channel-pill mb-4">{{ $page['eyebrow'] }}</span>
                        <h1 class="display-4 fw-bold mb-4">{{ $page['h1'] }}</h1>
                        <p class="lead mb-4">{{ $page['intro'] }}</p>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="{{ route('contact') }}" class="ud-main-btn ud-white-btn">{{ $page['primaryCta'] }}</a>
                            <a href="{{ route($page['secondaryRoute']) }}" class="ud-main-btn ud-link-btn">
                                {{ $page['secondaryCta'] }} <i class="lni lni-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="lc-hero-panel wow fadeInUp" data-wow-delay=".2s">
                        <x-public.landing-animation :variant="$slug" :label="$page['eyebrow'] . ' workflow'" />
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-5">
                @foreach ($page['proof'] as $item)
                    <div class="col-lg-3 col-sm-6">
                        <div class="lc-proof-card p-3 text-center fw-bold">{{ $item }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="ud-features">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="ud-section-title mx-auto text-center">
                        <span>{{ $page['eyebrow'] }}</span>
                        <h2>Built for the work behind every customer conversation</h2>
                        <p>{{ $page['intro'] }}</p>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                @foreach ($page['sections'] as $index => $section)
                    <div class="col-lg-4 col-md-6">
                        <div class="lc-feature-card wow fadeInUp" data-wow-delay=".{{ $index + 1 }}s">
                            <div class="ud-feature-icon">
                                <i class="lni {{ ['lni-comments', 'lni-users', 'lni-stats-up'][$index] ?? 'lni-checkmark-circle' }}"></i>
                            </div>
                            <h3>{{ $section['title'] }}</h3>
                            <p>{{ $section['body'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="ud-about lc-section-soft">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="ud-about-image wow fadeInUp" data-wow-delay=".1s">
                        <img src="{{ asset('leadochat-site/assets/images/about/about-image.svg') }}" alt="{{ $page['eyebrow'] }} workflow">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="ud-about-content wow fadeInUp" data-wow-delay=".15s">
                        <span class="tag">Capabilities</span>
                        <h2>Everything the team needs to act, not just reply.</h2>
                        <p>
                            Each customer conversation can carry channel context, customer profile data, team ownership,
                            automation history, and performance insight.
                        </p>
                        <ul class="mt-4">
                            @foreach ($page['features'] as $feature)
                                <li class="mb-2">{{ $feature }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="ud-faq">
        @php
            $sharedFaqs = [
                [
                    'q' => 'How long does setup usually take?',
                    'a' => 'Most teams can start with one channel first, connect the core inbox, and then add automation and routing after the first workflows are confirmed.',
                ],
                [
                    'q' => 'Can my team reply manually when automation is active?',
                    'a' => 'Yes. Leadochat is built for automation plus human handoff, so agents can step in whenever a conversation needs personal attention.',
                ],
                [
                    'q' => 'Does Leadochat keep the customer history in one place?',
                    'a' => 'Yes. The goal is to keep channel context, customer profile details, notes, assignments, and past conversations visible to the team.',
                ],
                [
                    'q' => 'Can I start with Instagram or WhatsApp only?',
                    'a' => 'Yes. You can begin with the channel that matters most now and expand into other messaging, social, and analytics workflows later.',
                ],
            ];
        @endphp
        <div class="shape">
            <img src="{{ asset('leadochat-site/assets/images/faq/shape.svg') }}" alt="shape">
        </div>
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="ud-section-title text-center mx-auto">
                        <span>FAQ</span>
                        <h2>Questions teams ask before switching</h2>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                @foreach (array_merge($page['faq'], $sharedFaqs) as $faq)
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

    <section class="ud-pricing">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="ud-section-title mb-0">
                        <span>Next step</span>
                        <h2>See how {{ $page['eyebrow'] }} works in Leadochat.</h2>
                        <p>Start with a focused demo around your highest-volume channel and expand from there.</p>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="{{ route('contact') }}" class="ud-main-btn">{{ $page['primaryCta'] }}</a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
