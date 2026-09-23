<x-layouts.public :title="'Leadochat Mini - Data Deletion'">
    <section class="lc-page-hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <span class="lc-channel-pill mb-4">Data deletion</span>
                    <h1 class="display-4 fw-bold mb-4">Data Deletion</h1>
                    <p class="lead">
                        How to request removal of connected account records and diagnostic data from this project environment.
                    </p>
                </div>
                <div class="col-lg-5">
                    <div class="lc-hero-panel wow fadeInUp" data-wow-delay=".2s">
                        <x-public.legal-animation variant="deletion" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="ud-about">
        <div class="container">
            <div class="lc-page-card p-4 p-lg-5">
                <div class="space-y-5 text-base leading-8 text-slate-600">
                    <p>
                        To request deletion of your Leadochat account data or data associated with a connected Instagram account,
                        email hello@leadochat.com from the address associated with your Leadochat account.
                    </p>
                    <p>
                        Include your Leadochat account email and the username of the connected Instagram account. Do not send
                        passwords, access tokens, app secrets, or other credentials.
                    </p>
                    <p>
                        After we verify the request, relevant account, connected-account, message, attachment, and diagnostic
                        records under our control will be deleted within 7 days, except records we must retain for security,
                        fraud prevention, or legal obligations. We will confirm completion by email.
                    </p>
                    <p>
                        You may also remove Leadochat access from your Instagram or Meta account settings. Revoking access stops
                        future authorized API calls but does not replace a deletion request for data already stored by Leadochat.
                    </p>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
