export function requireLoginThen(callback) {
    const isAuthenticated = document.body.dataset.auth === 'true';

    if (!isAuthenticated) {
        const authModal = new bootstrap.Modal(document.getElementById('authModal'));
        authModal.show();
        window._postAuthCallback = callback;
        return;
    }

    callback();
}

// Make available globally
window.requireLoginThen = requireLoginThen;
