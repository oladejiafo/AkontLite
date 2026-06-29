<x-app-layout>
    <x-header />

    <div class="container py-5">
        <h1 class="mb-4">Privacy Policy</h1>
        <p><strong>Effective Date:</strong> {{ now()->toDateString() }}</p>

        <p>This Privacy Policy explains how we collect, use, and protect your information when you use AkɔntLite.</p>

        <h2>1. Information We Collect</h2>
        <ul>
            <li>Information you provide: name, email, business details, client data.</li>
            <li>Usage data: IP address, browser type, access times.</li>
        </ul>

        <h2>2. How We Use Your Information</h2>
        <ul>
            <li>To provide and maintain the Service.</li>
            <li>To communicate updates and offers (you can opt out anytime).</li>
            <li>To improve security and prevent fraud.</li>
        </ul>

        <h2>3. Data Sharing</h2>
        <p>We never sell your data.  
        We may share it with trusted partners only to deliver the Service (e.g., payment processors).</p>

        <h2>4. Your Rights</h2>
        <ul>
            <li>Access: Request a copy of your data.</li>
            <li>Correction: Fix inaccurate information.</li>
            <li>Deletion: Delete your account and data on request.</li>
        </ul>

        <h2>5. Cookies</h2>
        <p>We use cookies to remember your preferences and improve the Service.  
        You can disable cookies in your browser settings.</p>

        <h2>6. Data Security</h2>
        <p>We take reasonable measures to protect your data, but no system is 100% secure.</p>

        <h2>7. Changes</h2>
        <p>We may update this policy. Major changes will be communicated by email or on our site.</p>

        <h2>8. Contact Us</h2>
        <p>If you have questions, email us at support@akontlite.com or visit our <a href="{{ route('support') }}">Support Page</a>.</p>
    </div>

    <x-footer />
</x-app-layout>