<footer class="ud-footer wow fadeInUp" data-wow-delay=".1s">
    <div class="ud-footer-widgets">
        <div class="container">
            <div class="row">
                <div class="col-xl-4 col-lg-4 col-md-6">
                    <div class="ud-widget">
                        <a href="{{ route('home') }}" class="d-flex align-items-center gap-2 mb-3 text-decoration-none">
                            <img src="{{ asset('leadochat-site/leadochatmini-logo.png') }}" alt="Leadochat logo" class="lc-footer-logo">
                            <span class="fw-bold text-white fs-4">Leadochat</span>
                        </a>
                        <p class="ud-widget-desc">
                            AI customer operations for messaging, sales, support, voice, and social commerce.
                        </p>
                        <p class="ud-widget-desc">
                            71-75 Shelton Street, Covent Garden, London, United Kingdom, WC2H 9JQ
                        </p>
                    </div>
                </div>

                <div class="col-xl-2 col-lg-2 col-md-6 col-sm-6">
                    <div class="ud-widget">
                        <h5 class="ud-widget-title">Product</h5>
                        <ul class="ud-widget-links">
                            <li><a href="{{ route('public.omnichannel-inbox') }}">Omnichannel Inbox</a></li>
                            <li><a href="{{ route('public.instagram-dm-automation') }}">Instagram Automation</a></li>
                            <li><a href="{{ route('public.whatsapp-business-automation') }}">WhatsApp Automation</a></li>
                            <li><a href="{{ route('public.customer-analytics') }}">Analytics</a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-xl-2 col-lg-2 col-md-6 col-sm-6">
                    <div class="ud-widget">
                        <h5 class="ud-widget-title">Use Cases</h5>
                        <ul class="ud-widget-links">
                            <li><a href="{{ route('public.whatsapp-sales-funnel') }}">WhatsApp Funnel</a></li>
                            <li><a href="{{ route('public.shared-inbox-customer-support') }}">Support Inbox</a></li>
                            <li><a href="{{ route('public.facebook-messenger-automation') }}">Messenger</a></li>
                            <li><a href="{{ route('pricing') }}">Pricing</a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-xl-4 col-lg-4 col-md-6">
                    <div class="ud-widget">
                        <h5 class="ud-widget-title">Company</h5>
                        <ul class="ud-widget-links">
                            <li><a href="{{ route('about') }}">About</a></li>
                            <li><a href="{{ route('contact') }}">Contact</a></li>
                            <li><a href="{{ route('privacy-policy') }}">Privacy Policy</a></li>
                            <li><a href="{{ route('data-deletion') }}">Data Deletion</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="ud-footer-bottom">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <ul class="ud-footer-bottom-left">
                        <li><a href="{{ route('privacy-policy') }}">Privacy Policy</a></li>
                        <li><a href="{{ route('data-deletion') }}">Data Deletion</a></li>
                        <li><a href="{{ route('contact') }}">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <p class="ud-footer-bottom-right">© {{ date('Y') }} Leadochat. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="back-to-top">
        <i class="lni lni-chevron-up"></i>
    </a>
</footer>
