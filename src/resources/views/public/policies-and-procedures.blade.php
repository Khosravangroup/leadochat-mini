<x-layouts.public
    :title="'Policies and Procedures | Leadochat'"
    :meta="'Read Leadochat policies and procedures, including terms of service, account rules, payments, refunds, integrations, intellectual property, termination, liability, and payment terms.'"
>
    @php
        $sections = [
            [
                'id' => 'terms',
                'title' => '1. Terms of Service',
                'body' => [
                    'These Terms of Service are a contract between you and Leadochat, the provider of the Leadochat website and services accessible from Leadochat websites, domains, and subdomains.',
                    'By using the Leadochat service, you agree to be bound by these Terms of Service. If you do not agree, do not use the service. If you violate these terms, we reserve the right to cancel or block access to your account without notice.',
                ],
            ],
            [
                'id' => 'account',
                'title' => '2. Your account',
                'body' => [
                    'Accounts must be registered by a human. Accounts registered by bots or automated methods are not permitted. You must be 16 years of age or older.',
                    'You must provide a valid permanent email address and any other required information during registration. One person or legal entity may not maintain more than one free account.',
                    'You are responsible for maintaining the security of your account and password. Personally identifiable information submitted by you is subject to our Privacy Policy.',
                    'You may not use the service for illegal or unauthorized purposes, distribute harmful code, obscure notices, bypass security, infringe rights, publish abusive or unlawful content, impersonate others, or mislead anyone about your identity.',
                ],
            ],
            [
                'id' => 'updates-support',
                'title' => '3. App updates and support',
                'body' => [
                    'Leadochat apps purchased from CodeCanyon are subject to Envato terms, including 6 months of support and lifetime updates where applicable.',
                    'Leadochat apps purchased outside CodeCanyon may include 1 year of free updates and 6 months of support unless otherwise stated.',
                ],
            ],
            [
                'id' => 'payment-refunds',
                'title' => '4. Payment and refunds',
                'body' => [
                    'A valid credit card, debit card, PayPal account, or supported payment method may be required for purchases. Software, apps, and services are billed in advance according to the applicable pricing schedule.',
                    'Fees are exclusive of taxes, levies, or duties imposed by taxing authorities unless otherwise stated. You are responsible for taxes applicable to your use of the service or payments made in connection with it.',
                    'Refund eligibility may require that the software or app has confirmed issues, support has verified and failed to resolve the issue within the stated support window, and the issue is related to Leadochat rather than hosting, server, or third-party configuration.',
                    'Support may request access details needed to verify and solve an issue. Refusal to provide required access may affect refund eligibility.',
                ],
            ],
            [
                'id' => 'violations',
                'title' => '5. Violation of these Terms of Service',
                'body' => [
                    'We reserve the right to investigate and prosecute violations of these Terms of Service to the fullest extent of the law.',
                    'We may remove data, accounts, or content that violates these terms or is otherwise objectionable. We may suspend or terminate accounts that breach these terms.',
                    'Termination may result in deactivation or deletion of the account, denial of account access, and removal of content in the account.',
                ],
            ],
            [
                'id' => 'slack-messenger',
                'title' => '6. Slack and Messenger apps',
                'body' => [
                    'Slack and Messenger integrations may have no recurring cost, but we reserve the right to terminate service where necessary, including when message volume creates excessive processing costs or operational risk.',
                    'If an integration service must be stopped, we may contact you before stopping it to discuss possible solutions and may provide technical guidance for continuing without using our servers where available.',
                ],
            ],
            [
                'id' => 'intellectual-property',
                'title' => '7. Intellectual property and content ownership',
                'body' => [
                    'The contents of Leadochat software are copyrighted. Leadochat and Leadochat logos are trademarks and may not be used without express written permission except where allowed for approved promotion.',
                    'You may not duplicate, copy, sell, share, distribute, or reuse any portion of the HTML, CSS, PHP, JavaScript, visual design elements, apps, addons, or website without express written permission.',
                    'You do not acquire ownership rights by using the service or software. License rights depend on the license purchased and the applicable marketplace or commercial terms.',
                    'Unless expressly permitted by the relevant license, you may not resell, redistribute, or provide Leadochat as downloadable software, source code, or an installable product for customers.',
                ],
            ],
            [
                'id' => 'termination',
                'title' => '8. Termination by user',
                'body' => [
                    'You may terminate your subscription under the conditions applicable to your monthly or annual subscription.',
                    'For monthly subscriptions, termination generally becomes effective from the first day following the monthly period during which termination was notified. For annual subscriptions, termination generally becomes effective from the first day following the relevant annual period.',
                    'Access to Leadochat and the license remains in effect until the effective termination date. No refund is granted for fees already paid except where required by applicable terms or law.',
                    'You may terminate subscriptions or automatic credit recharge from the membership or account section where those settings are available.',
                ],
            ],
            [
                'id' => 'liability',
                'title' => '9. Limited liability',
                'body' => [
                    'You acknowledge and agree that you assume full responsibility for your use of Leadochat and for the security of systems, programs, and data used to access the service.',
                    'You agree not to use Leadochat in an offensive, abusive, unlawful, infringing, automated, disruptive, or malicious manner, and not to attempt to reverse engineer, decompile, disassemble, or discover source code except where expressly allowed by law.',
                    'To the greatest extent permitted by law, Leadochat and its suppliers or licensors shall not be liable for indirect, incidental, consequential, or similar damages arising from use of, inability to use, unauthorized access to, or technical issues related to the service.',
                    'If Leadochat is found liable by a court, aggregate liability is limited to the fees charged during the 12 months before the date of the proceeding, unless applicable law requires otherwise.',
                ],
            ],
            [
                'id' => 'fees',
                'title' => '10. Fees and payment terms',
                'body' => [
                    'Unless explicitly specified otherwise in a written quotation, price quotations are non-binding and may be adjusted if other or additional information is provided.',
                    'Payment obligations are non-cancelable and paid fees, taxes, and purchases are non-refundable to the greatest extent permitted by applicable law.',
                    'If paying by card, automatic debit, or another supported payment method, you represent that you are authorized to use that method and authorize the applicable fees to be charged through it.',
                    'By purchasing a subscription, enabling automatic credit recharge, or purchasing a reseller-related product, you agree to the applicable recurring, invoice, or usage-based payment terms.',
                ],
            ],
            [
                'id' => 'contact',
                'title' => '11. Contact',
                'body' => [
                    'If you have questions or comments about these Policies and Procedures, contact us by email at info@leadochat.com.',
                    'Postal mail or courier: Global Education LTD, 115419, Moscow, 2nd Roshchinsky proezd, building 8, building 6.',
                ],
            ],
        ];
    @endphp

    <section class="lc-page-hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="lc-channel-pill mb-4">Policies</span>
                    <h1 class="display-4 fw-bold mb-4">Policies and Procedures</h1>
                    <p class="lead">
                        Terms, account rules, payments, refunds, integrations, intellectual property, termination, liability, and contact details.
                    </p>
                </div>
                <div class="col-lg-5">
                    <div class="lc-hero-panel wow fadeInUp" data-wow-delay=".2s">
                        <x-public.legal-animation variant="policies" />
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
