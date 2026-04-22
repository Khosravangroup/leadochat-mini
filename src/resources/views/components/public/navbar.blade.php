<header class="ud-header">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <nav class="navbar navbar-expand-lg">
                    <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}">
                        <img src="{{ asset('leadochat-site/brand-mark.svg') }}" alt="Leadochat logo" class="lc-logo-mark">
                        <span class="lc-brand-text">Leadochat</span>
                    </a>

                    <button class="navbar-toggler" type="button" aria-label="Toggle navigation">
                        <span class="toggler-icon"></span>
                        <span class="toggler-icon"></span>
                        <span class="toggler-icon"></span>
                    </button>

                    <div class="navbar-collapse">
                        <ul id="nav" class="navbar-nav mx-auto">
                            <li class="nav-item">
                                <a class="ud-menu-scroll" href="{{ route('home') }}">Home</a>
                            </li>
                            <li class="nav-item">
                                <a class="ud-menu-scroll" href="{{ route('features') }}">Features</a>
                            </li>
                            <li class="nav-item">
                                <a class="ud-menu-scroll" href="{{ route('public.omnichannel-inbox') }}">Inbox</a>
                            </li>
                            <li class="nav-item">
                                <a class="ud-menu-scroll" href="{{ route('public.instagram-dm-automation') }}">Instagram</a>
                            </li>
                            <li class="nav-item">
                                <a class="ud-menu-scroll" href="{{ route('public.whatsapp-business-automation') }}">WhatsApp</a>
                            </li>
                            <li class="nav-item">
                                <a class="ud-menu-scroll" href="{{ route('pricing') }}">Pricing</a>
                            </li>
                            <li class="nav-item">
                                <a class="ud-menu-scroll" href="{{ route('contact') }}">Contact</a>
                            </li>
                        </ul>
                    </div>

                    <div class="navbar-btn d-none d-sm-inline-block">
                        <a href="{{ route('login') }}" class="ud-main-btn ud-login-btn">Sign In</a>
                        <a href="{{ route('register') }}" class="ud-main-btn ud-white-btn">Start Free</a>
                    </div>
                </nav>
            </div>
        </div>
    </div>
</header>
