<x-layouts.public
    :title="'Contact Leadochat | Book a Demo'"
    :meta="'Contact Leadochat to discuss messaging automation, omnichannel inbox workflows, WhatsApp, Instagram, and customer operations.'"
>
    <section class="lc-page-hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="lc-channel-pill mb-4">Contact</span>
                    <h1 class="display-4 fw-bold mb-4">Let’s talk about your customer conversations.</h1>
                    <p class="lead">
                        Share the channels you use today and the workflows you want to improve. We will help map the right starting point.
                    </p>
                </div>
                <div class="col-lg-5">
                    <div class="lc-hero-panel">
                        <img src="{{ asset('leadochat-site/brand-mark.svg') }}" alt="Leadochat logo">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="contact" class="ud-contact">
        <div class="container">
            <div class="row align-items-start g-5">
                <div class="col-xl-7 col-lg-7">
                    <div class="ud-contact-content-wrapper">
                        <div class="ud-contact-title">
                            <span>CONTACT US</span>
                            <h2>Book a demo or send a project question.</h2>
                        </div>

                        <div class="ud-contact-info-wrapper">
                            <div class="ud-single-info">
                                <div class="ud-info-icon"><i class="lni lni-map-marker"></i></div>
                                <div class="ud-info-meta">
                                    <h5>Registered office</h5>
                                    <p>71-75 Shelton Street, Covent Garden, London, United Kingdom, WC2H 9JQ</p>
                                </div>
                            </div>

                            <div class="ud-single-info">
                                <div class="ud-info-icon"><i class="lni lni-envelope"></i></div>
                                <div class="ud-info-meta">
                                    <h5>How can we help?</h5>
                                    <p>Book a demo</p>
                                    <p>Ask about Meta, WhatsApp, Instagram, or inbox workflows</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-5 col-lg-5">
                    <div class="ud-contact-form-wrapper wow fadeInUp" data-wow-delay=".2s">
                        <h3 class="ud-contact-form-title">Send us a message</h3>
                        <form class="ud-contact-form" method="GET" action="mailto:hello@leadochat.com">
                            <div class="ud-form-group">
                                <label for="fullName">Full Name*</label>
                                <input type="text" id="fullName" name="fullName" placeholder="Your name">
                            </div>
                            <div class="ud-form-group">
                                <label for="email">Email*</label>
                                <input type="email" id="email" name="email" placeholder="you@example.com">
                            </div>
                            <div class="ud-form-group">
                                <label for="message">Message*</label>
                                <textarea id="message" name="message" rows="4" placeholder="Tell us about your channels and workflow"></textarea>
                            </div>
                            <div class="ud-form-group mb-0">
                                <button type="submit" class="ud-main-btn">Send message</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
