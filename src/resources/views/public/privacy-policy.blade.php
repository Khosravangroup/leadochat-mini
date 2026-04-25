<x-layouts.public
    :title="'Privacy Policy | Leadochat'"
    :meta="'Read the Leadochat Privacy Policy, including what personal information is covered, how service data is handled, retention, cookies, logs, analytics, and contact details.'"
>
    @php
        $sections = [
            [
                'id' => 'scope',
                'title' => 'What this policy covers',
                'body' => [
                    'This Privacy Policy explains what information we collect, why we collect it, how we use it, how it may be shared, and how we handle personal information created, inputted, submitted, posted, transmitted, stored, or displayed when you, your agents, or end-users access and use our services.',
                    'For this policy, our services include the main Leadochat product and related apps or integrations. Our website includes leadochat.com and its subdomains.',
                    'Your continued use of our services or websites means you agree to this policy. If you do not accept the policy, your remedy is to discontinue use of the services and website.',
                ],
            ],
            [
                'id' => 'personal-information',
                'title' => 'What we mean by personal information',
                'body' => [
                    'Personal information means information relating to an identified or identifiable natural person, business, or legal entity. This may include information that can identify a person directly or indirectly by reference to identifiers or factors specific to that person.',
                    'Except as described in this policy, Leadochat does not currently sell, rent, or loan personal information to third parties.',
                ],
            ],
            [
                'id' => 'service-data',
                'title' => 'Personal information you provide or that we collect',
                'body' => [
                    'For self-hosted PHP and WordPress versions, Leadochat does not access or collect your conversations, messages, attachments, users, or generated service information. That information is stored on your own server and is not transmitted to Leadochat.',
                    'For cloud services, service information may be stored in secure databases for product functionality. We do not process or share your conversations except as needed to provide the service you use.',
                    'If you purchase through a third-party marketplace or payment provider, information you provide to that provider is handled according to that provider\'s policies.',
                ],
            ],
            [
                'id' => 'integrations',
                'title' => 'Apps and integrations',
                'body' => [
                    'When you connect external services such as Slack, Google/Dialogflow, Facebook Messenger, Meta, Instagram, WhatsApp, or other integrations, the information shared depends on the integration and the external service terms.',
                    'Integration data may include identifiers, access tokens, page or channel identifiers, website URL, account details, profile information, and routing metadata required to connect the service.',
                    'Messages or attachments sent through an integration may be routed through the systems required to provide that integration. Where possible, Leadochat avoids storing routed content unless storage is part of the selected service functionality.',
                ],
            ],
            [
                'id' => 'retention-storage',
                'title' => 'Data retention, removal, and storage',
                'body' => [
                    'The types of information covered by this policy include account, contact, integration, and service metadata such as email addresses and connected-service identifiers.',
                    'If you contact us and request permanent deletion of information from our system and database, we will process the request within 7 days after receiving and verifying the request.',
                    'We store information in secure databases and do not share database access with third-party entities except where required to provide infrastructure, security, or service operations.',
                ],
            ],
            [
                'id' => 'cookies-logs-analytics',
                'title' => 'Cookies, log files, and analytics',
                'body' => [
                    'We do not currently use tracking cookies for advertising. The website may use cookies or local storage in the future for functionality, preferences, analytics, or service improvement.',
                    'Like most websites and services, we may gather certain information automatically in log files, including IP address, browser, internet service provider, referring and exit pages, operating system, date and time stamp, clickstream data, and information entered into open text fields.',
                    'Analytics information may be used to improve services, website functionality, marketing, support, security, and product performance. In some cases, analytics data may include personal information or sensitive business information if that information is part of the service activity being measured.',
                ],
            ],
            [
                'id' => 'use',
                'title' => 'How we use personal information',
                'body' => [
                    'We may use personal information to enable access to services, process transactions, send confirmations and invoices, respond to questions and requests, provide support, send service updates, monitor service usage, improve functionality, prevent fraud or unauthorized access, and notify you about relevant service matters.',
                    'Where email is collected for subscription or service notifications, it is used to communicate important account, billing, or product information.',
                ],
            ],
            [
                'id' => 'contact',
                'title' => 'Privacy contact',
                'body' => [
                    'If you have questions or comments about this Privacy Policy, contact us by email at info@leadochat.com.',
                    'Postal mail or courier: Global Education LTD, 115419, Moscow, 2nd Roshchinsky proezd, building 8, building 6.',
                ],
            ],
        ];
    @endphp

    <section class="lc-page-hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="lc-channel-pill mb-4">Privacy</span>
                    <h1 class="display-4 fw-bold mb-4">Privacy Policy</h1>
                    <p class="lead">
                        How Leadochat handles personal information, service data, integrations, logs, analytics, retention, and deletion requests.
                    </p>
                </div>
                <div class="col-lg-5">
                    <div class="lc-hero-panel wow fadeInUp" data-wow-delay=".2s">
                        <x-public.legal-animation variant="privacy" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="ud-about">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="lc-page-card p-4 sticky-top" style="top: 110px;">
                        <h2 class="h5 fw-bold mb-3">On this page</h2>
                        <div class="lc-link-list">
                            @foreach ($sections as $section)
                                <a href="#{{ $section['id'] }}">{{ $section['title'] }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="d-grid gap-4">
                        @foreach ($sections as $section)
                            <article id="{{ $section['id'] }}" class="lc-page-card p-4 p-lg-5">
                                <h2 class="h4 fw-bold mb-4">{{ $section['title'] }}</h2>
                                @foreach ($section['body'] as $paragraph)
                                    <p class="text-muted mb-3">{{ $paragraph }}</p>
                                @endforeach
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
