<x-app-layout>
    <x-header />
    <div class="container py-5">
        <h1 class="mb-4">Terms of Use for AkɔntLite</h1>
        <p><strong>Effective Date:</strong> {{ now()->toDateString() }}</p>

        <p>By using AkɔntLite ("the Service"), you agree to these Terms of Use.  
        If you do not agree, please do not use the Service.</p>

        <h2>1. Who We Are</h2>
        <p>AkɔntLite is a free, light invoicing and payments solution operated by <b>G8 Brooks</b> ("Company", "We", "Us").</p>

        <h2>2. Your Responsibilities</h2>
        <ul>
            <li>You must provide accurate information when creating an account.</li>
            <li>You are responsible for keeping your login details safe.</li>
            <li>You must not misuse our Service (spam, abuse, illegal activities).</li>
        </ul>

        <h2>3. Use of Content</h2>
        <p>All content you upload remains yours. You grant us permission to use it only as needed to run the Service.</p>

        <h2>4. Email Communication & Marketing</h2>
        <p>By signing up, you agree that we may contact you occasionally about AkɔntLite updates,  
        new features, and special offers, including upgrades to AkɔntForge.</p>
        <p>You can opt out anytime by clicking "unsubscribe" in our emails or by contacting support.</p>

        <h2>5. Data Protection</h2>
        <p>We handle your data in line with our <a href="{{ route('policy') }}">Privacy Policy</a>.  
        We do not sell your data to third parties.</p>

        <h2>6. Account Termination</h2>
        <p>We reserve the right to suspend or terminate accounts that breach these Terms.</p>

        <h2>7. Disclaimer</h2>
        <p>AkɔntLite is provided "as is" with no warranties. We do our best to keep it secure and reliable,  
        but cannot guarantee uninterrupted access.</p>

        <h2>8. Governing Law</h2>
        <p>These Terms are governed by the laws of UAE.</p>

        <h2>9. Changes</h2>
        <p>We may update these Terms from time to time.  
        We’ll notify users of major changes via email or on the site.</p>

        <h2>10. Contact & Support</h2>
        <p>Questions? Reach out anytime:</p>
        <ul>
            <li>Email: support@akontlite.com</li>
            <li>Support: <a href="{{ route('support') }}">{{ route('support') }}</a></li>
        </ul>
    </div>
    <x-footer />
</x-app-layout>
