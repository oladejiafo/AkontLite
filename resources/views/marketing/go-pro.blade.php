<x-app-layout>
    <x-header />

    <div class="container py-5">

        <!-- Hero -->
        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold">Go Pro with AkɔntForge</h1>
            <p class="lead">Unlock the full power of professional accounting — move beyond basic invoicing and payments.</p>
            <a href="https://www.akontforge.com/upgrade?source=akontlite" target="_blank" class="btn btn-primary btn-lg">
                Upgrade Now — Keep All My Data
            </a>
        </div>

        <!-- Optional snapshot -->
        {{-- Uncomment if you want to show your main site live --}}
        {{-- 
        <div class="text-center mb-5">
            <iframe src="https://www.akontforge.com" style="width:100%; height:600px; border:1px solid #ddd;"></iframe>
        </div>
        --}}

        <!-- Comparison Table -->
        <div class="mb-5">
            <h2 class="text-center mb-4">Compare Plans</h2>
            <div class="table-responsive">
                <table class="table table-bordered text-center">
                    <thead>
                        <tr>
                            <th></th>
                            <th>AkɔntLite (Free)</th>
                            <th>AkɔntForge (Pro)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Unlimited Invoices</td>
                            <td>✅</td>
                            <td>✅</td>
                        </tr>
                        <tr>
                            <td>Advanced Payments</td>
                            <td>❌</td>
                            <td>✅</td>
                        </tr>
                        <tr>
                            <td>Tax Reports</td>
                            <td>❌</td>
                            <td>✅</td>
                        </tr>
                        <tr>
                            <td>Payroll & Expenses</td>
                            <td>❌</td>
                            <td>✅</td>
                        </tr>
                        <tr>
                            <td>White-label Branding</td>
                            <td>❌</td>
                            <td>✅</td>
                        </tr>
                        <tr>
                            <td>Multi-User & Roles</td>
                            <td>❌</td>
                            <td>✅</td>
                        </tr>
                        <tr>
                            <td>Automatic Payment Reminders</td>
                            <td>❌</td>
                            <td>✅</td>
                        </tr>
                        <tr>
                            <td>Priority Support</td>
                            <td>❌</td>
                            <td>✅</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- How it works -->
        <div class="mb-5">
            <h2 class="text-center mb-4">Upgrade in 3 Clicks</h2>
            <div class="row text-center">
                <div class="col-md-4">
                    <h4>1. Pick Your Plan</h4>
                    <p>Choose the best plan for your business size.</p>
                </div>
                <div class="col-md-4">
                    <h4>2. Pay Securely</h4>
                    <p>Use your preferred payment method.</p>
                </div>
                <div class="col-md-4">
                    <h4>3. Migrate Instantly</h4>
                    <p>Your invoices, payments & clients move with you — securely.</p>
                </div>
            </div>
        </div>

        <!-- Testimonials / Trust -->
        <div class="text-center mb-5">
            <h2>Trusted by Growing Businesses</h2>
            <p class="fst-italic">“Upgrading to AkɔntForge saved us time, money, and gave us peace of mind.”</p>
            <p>— Happy User</p>
        </div>

        <!-- Final CTA -->
        <div class="text-center">
            <a href="https://www.akontforge.com/upgrade?source=akontlite" target="_blank" class="btn btn-success btn-lg">
                Upgrade Now — Start in Minutes
            </a>
            <p class="mt-3 text-muted">Your data stays safe. No downtime. Cancel anytime.</p>
        </div>

    </div>
    <x-footer />
</x-app-layout>