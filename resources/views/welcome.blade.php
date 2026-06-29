<x-app-layout>
    <x-header />

    <!-- Hero Section -->
    <section class="container mx-auto px-5 py-1">
        <div class="text-center max-w-4xl mx-auto">
            <div class="badge text-secondary bg-secondary-faded d-inline-flex align-items-center gap-2 my-5 p-3 rounded-pill" styled="background-color: rgba(45, 196, 182, 0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="bi bi-lightning" viewBox="0 0 24 24">
                    <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                No signup required for basic use
            </div>

            <div  class="container" style="max-width: 70%">
                <h1 class="font-bold text-foreground mb-5">
                    Professional Invoices<br>
                    <span class="block text-primary">In Minutes</span>
                </h1>
    
                <p class="w-70 text-xl text-muted-foreground mb-5 max-w-2xl mx-auto px-5" style="font-size: 1.20rem;line-height: 1.5rem;">
                    Create, customize, and send professional invoices instantly. Add payment links, download PDFs, and get paid faster - all with AkɔntLite.
                </p>
            </div>
            
            <div class="d-flex flex-column flex-sm-row justify-content-center gap-3 my-5">
                @auth
                    @php
                        $hasInvoices = Auth::user()->invoices()->exists();
                    @endphp

                    <a href="{{ route('invoices.create') }}"
                    class="btn btn-lg btn-primary text-white fw-semibold rounded-pill shadow px-4 py-2">
                    {{ $hasInvoices ? 'Create New Invoice' : 'Create Your First Invoice' }}
                    </a>
                @else
                    <a href="{{ route('invoices.create') }}"
                    class="btn btn-lg btn-primary text-white fw-semibold rounded-pill shadow px-4 py-2">
                    Create Your First Invoice
                    </a>
                @endauth

                <button class="btn btn-outline-secondary btn-lg fw-semibold rounded-pill px-4 py-2">
                    View Demo
                </button>
            </div>

        </div>
    </section>

    <!-- Features Grid -->
    <section class="container mx-auto px-5 py-3">
        <div class="text-center mb-5">
            <h2 class="text-3xl font-bold text-foreground mb-4">Everything You Need</h2>
            <p class="text-muted foreground text-lg">Powerful features for professional invoicing</p>
        </div>

        <div class="container my-5">
            <div class="row g-4">

                <!-- Card 1 -->
                <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm hover-shadow">
                    <div class="card-body">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3 icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h5 class="fw-semibold">Quick Creation</h5>
                    <p class="text-muted">Create professional invoices in under 2 minutes with our intuitive form builder.</p>
                    </div>
                </div>
                </div>

                <!-- Card 2 -->
                <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm hover-shadow">
                    <div class="card-body">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3 icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </div>
                    <h5 class="fw-semibold">PDF Export</h5>
                    <p class="text-muted">Download professional PDF invoices ready to send to clients instantly.</p>
                    </div>
                </div>
                </div>

                <!-- Card 3 -->
                <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm hover-shadow">
                    <div class="card-body">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3 icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </div>
                    <h5 class="fw-semibold">Payment Links</h5>
                    <p class="text-muted">Add secure payment links via Stripe, PayStack, or Flutterwave for instant payments.</p>
                    </div>
                </div>
                </div>

                <!-- Card 4 -->
                <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm hover-shadow">
                    <div class="card-body">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3 icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h5 class="fw-semibold">No Signup Mode</h5>
                    <p class="text-muted">Create and send one-off invoices without creating an account. Start immediately.</p>
                    </div>
                </div>
                </div>

                <!-- Card 5 -->
                <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm hover-shadow">
                    <div class="card-body">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3 icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h5 class="fw-semibold">Fast & Efficient</h5>
                    <p class="text-muted">Streamlined workflow designed for speed. Get your invoices out in record time.</p>
                    </div>
                </div>
                </div>

                <!-- Card 6 -->
                <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm hover-shadow">
                    <div class="card-body">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3 icon-box">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                    </div>
                    <h5 class="fw-semibold">Pro Features</h5>
                    <p class="text-muted">Upgrade for client management, recurring invoices, and automated follow-ups.</p>
                    </div>
                </div>
                </div>

            </div>
        </div>

    </section>

    <!-- CTA Section -->
    <section class="container mx-auto px-5 py-1">
       
        <div class="card border-0 shadow text-center bg-white-50 rounded-4">
            <div class="card-body p-5" style="background-color: #f8f9fa;">
                <h2 class="fw-bold display-6 mb-3">Ready to Get Started?</h2>
                <p class="fs-5 text-muted mb-4">
                    Join thousands of freelancers and small businesses using AkɔntLite
                </p>

                @auth
                    @php
                        $hasInvoices = Auth::user()->invoices()->exists();
                    @endphp

                    <a href="{{ route('invoices.create') }}"
                    class="btn btn-lg btn-primary px-5 py-3 rounded-pill fw-semibold shadow-sm"
                    style="color: white; transition: all 0.2s ease-in-out;"
                    onmouseover="this.style.transform='scale(1.05)'; this.style.backgroundColor='#26b0a5';"
                    onmouseout="this.style.transform='scale(1)'; this.style.backgroundColor='#2dc4b6';">
                        {{ $hasInvoices ? 'Create New Invoice' : 'Create Your First Invoice' }}
                    </a>

                @else
                    <a href="{{ route('invoices.create') }}"
                    class="btn btn-lg btn-primary px-5 py-3 rounded-pill fw-semibold shadow-sm"
                    style="color: white; transition: all 0.2s ease-in-out;"
                    onmouseover="this.style.transform='scale(1.05)'; this.style.backgroundColor='#26b0a5';"
                    onmouseout="this.style.transform='scale(1)'; this.style.backgroundColor='#2dc4b6';">
                        Create Your First Invoice Now
                    </a>
                @endauth

            </div>
        </div>
     
    </section>

    <x-footer />

</x-app-layout>