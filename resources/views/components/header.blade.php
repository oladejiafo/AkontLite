<header class="bg-white border-bottom sticky-top py-3">
<meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="container d-flex justify-content-between align-items-center">

        <!-- Left Side: Logo + Text -->
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('home') }}">
            <img src="{{ asset('images/akont_logo.png') }}" alt="Logo" style="height: 50px;" class="me-1">
            </a>
            <div>
                <a href="{{ route('home') }}" style="text-decoration:none ;">
                  <h1 class="h5 mb-0 fw-bold text-primary">AkɔntLite</h1>
                </a>
                <p class="mb-0 small text-muted">Professional Invoicing Made Simple</p>
            </div>
        </div>
        <!-- Bootstrap Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        <!-- Right Side: Buttons -->
        <div class="d-flex align-items-center gap-2 justify-content-between" style="min-width: 250px;">
            @if(auth()->check())
                <!-- Mini Menu -->
                <nav class="me-3">
                    <a href="{{ route('invoices.index') }}" class="text-decoration-none me-3 small text-muted text-menu">
                        Invoices
                    </a>
                    {{-- Optional: Payments link --}}
                    <a href="{{ route('payments.index') }}" class="text-decoration-none small text-muted text-menu">
                        Payments
                    </a>
                </nav>

                <!-- User Info + Logout -->
                <div class="d-flex flex-column flex-sm-row align-items-center gap-2">
                    <span class="text-muted small text-truncate me-sm-3" style="max-width: 120px;">
                        <!-- SVG icon -->
                        Hi, {{ Str::limit(auth()->user()->name, 12) }}
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm w-100 w-sm-auto">
                            Logout
                        </button>
                    </form>
                </div>
            @else
                <div class="flex-grow-1 me-2">
                    <button class="btn btn-outline-secondary btn-sm w-100" onclick="openAuthModal()">
                        Login / Sign Up
                    </button>
                </div>
            @endif

            <div>
                <a href="{{ route('go.pro') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-lightning" viewBox="0 0 16 16">
                        <path d="M11.3 1L2 9h6l-1 6L14 7h-6l1-6z"/>
                    </svg>
                    Go Pro
                </a>
            </div>
        </div>

    </div>
</header>

<script>
    window.openAuthModal = function () {
        const authModalEl = document.getElementById('authModal');
        if (!authModalEl) {
            console.error('Auth modal element not found!');
            return;
        }
        const authModal = new bootstrap.Modal(authModalEl);
        authModal.show();
    };
</script>