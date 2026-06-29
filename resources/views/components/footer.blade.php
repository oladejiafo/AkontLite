<footer class="border-top bg-white bg-opacity-80 backdrop-blur-sm py-2 my-2">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center px-5">
        <!-- Left Side -->
        <p class="mb-2 mb-md-0 text-muted small">
            © {{ date('Y') }} AkɔntLite. All rights reserved. Powered By <a href="https://g8brooks.com" target="_blank"><img src="{{ asset('images/G8Brooks_logo.png') }}" height="35px"> </a>
        </p>

        <!-- Right Side Links -->
        <div class="d-flex gap-3">
            <a href="{{ route('terms') }}" class="text-muted text-decoration-none small">Terms</a>
            <a href="{{ route('policy') }}" class="text-muted text-decoration-none small">Privacy</a>
            <a href="{{ route('support') }}" class="text-muted text-decoration-none small">Support</a>
        </div>
    </div>
</footer>

<div class="modal fade" id="authModal" tabindex="-1" aria-labelledby="authModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="authForm">
      <div class="modal-header">
        <h5 class="modal-title" id="authModalLabel">Sign Up</h5>
      </div>

      <div class="modal-body">
        <div class="py-3 flex-column">

          <button type="button" class="btn btn-outline-primary w-100 mt-2 d-flex align-items-center justify-content-center gap-2" id="googleLoginBtn">
            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="20" alt="Google icon">
            Sign up with Google
          </button>

          <div class="text-center pt-3">— or —</div>
        </div>

        <input type="text" name="name" class="form-control mb-2" placeholder="Full Name" required>
        <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
        <input type="password" autocomplete="off" name="password" class="form-control mb-2" placeholder="Password" required>
        <input type="password" autocomplete="off" name="password_confirmation" class="form-control mb-2" placeholder="Confirm Password" required>

        <div class="form-check small mb-3">
          <input class="form-check-input" type="checkbox" id="termsCheck" name="terms" required>
          <label class="form-check-label text-primary" for="termsCheck">
            I agree to the <a href="/terms" target="_blank">Terms & Conditions</a>.
          </label>
        </div>

        <div class="text-center small" id="toggleAuthMode">
        Already have an account?
        <button type="button" class="btn btn-link p-0 text-primary" id="switchToLogin">Login</button>
        <span style="display: none;">
            Don't have an account?
            <button type="button" class="btn btn-link p-0 text-primary" id="switchToSignup">Sign Up</button>
        </span>
        </div>

      </div>

      <div class="modal-footer flex-column">
        <button type="submit" class="btn btn-primary w-100 mb-2">Continue</button>
      </div>
    </form>
  </div>
</div>

<script>
  document.getElementById('googleLoginBtn').addEventListener('click', function () {
      window.location.href = '/auth/google'; 
  });
</script>

<script>
  document.getElementById('switchToLogin').addEventListener('click', function () {
      document.getElementById('authModalLabel').innerText = 'Login';
      document.querySelector('#authForm input[name="name"]').style.display = 'none';
      document.getElementById('googleLoginBtn').innerText = 'Login with Google';
      this.style.display = 'none'; // Hide switch on login view
  });

  const authModalLabel = document.getElementById('authModalLabel');
  const nameInput = document.querySelector('#authForm input[name="name"]');
  const termsCheck = document.getElementById('termsCheck').closest('.form-check');
  const googleBtn = document.getElementById('googleLoginBtn');
  const switchToLogin = document.getElementById('switchToLogin');
  const switchToSignup = document.getElementById('switchToSignup');
  const toggleAuthMode = document.getElementById('toggleAuthMode');
  const confirmPasswordField = document.querySelector('#authForm input[name="password_confirmation"]');
  const termsCheckInput = document.getElementById('termsCheck'); 

  // Login view
  switchToLogin.addEventListener('click', function () {
      authModalLabel.innerText = 'Login';
      nameInput.style.display = 'none';
      nameInput.required = false;
      termsCheck.style.display = 'none';
      termsCheckInput.required = false;
      confirmPasswordField.style.display = 'none'; 
      confirmPasswordField.required = false; 
      googleBtn.innerHTML = `<img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="20"> Login with Google`;
      switchToLogin.style.display = 'none';
      toggleAuthMode.querySelector('span').style.display = 'inline';
  });

  // Sign up view
  switchToSignup.addEventListener('click', function () {
      authModalLabel.innerText = 'Sign Up';
      nameInput.style.display = 'block';
      nameInput.required = true;
      termsCheck.style.display = 'block';
      termsCheckInput.required = true;
      confirmPasswordField.style.display = 'block';
      confirmPasswordField.required = true;
      googleBtn.innerHTML = `<img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="20"> Sign up with Google`;
      switchToLogin.style.display = 'inline';
      toggleAuthMode.querySelector('span').style.display = 'none';
  });

  document.getElementById('authForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    // Get CSRF token safely
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (!csrfMeta) {
        console.error('CSRF token meta tag missing');
        alert('Security error. Please refresh the page.');
        return;
    }

    const url = document.getElementById('authModalLabel').innerText === 'Login' 
        ? '/login' 
        : '/register';

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfMeta.content,
                'Accept': 'application/json',
            },
            body: formData,
            credentials: 'same-origin' 
        });

        const data = await response.json().catch(() => {
            throw new Error('Invalid server response');
        });

        if (!response.ok) {
            const errorMsg = data?.message || 
                            data?.errors?.join('\n') || 
                            'Authentication failed';
            throw new Error(errorMsg);
        }

        // Success handling
        bootstrap.Modal.getInstance(document.getElementById('authModal')).hide();
        document.body.dataset.auth = 'true';

        if (data.redirect) {
          window.location.href = data.redirect;
        } else {
          window.location.reload();
        }

        if (typeof window._postAuthCallback === 'function') {
            window._postAuthCallback();
            window._postAuthCallback = null;
        }

      } catch (err) {
          console.error('Auth error:', err);
          alert(err.message || 'Authentication failed. Please try again.');
      }
  });

</script>
