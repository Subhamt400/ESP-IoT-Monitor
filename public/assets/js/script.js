document.addEventListener('DOMContentLoaded', function () {
    // Add entrance animation trigger
    const authCard = document.querySelector('.auth-card');
    if (authCard) {
        // Add animate class after a tick so CSS animation runs
        requestAnimationFrame(() => authCard.classList.add('animate'));
    }

    // Show / hide password toggle
    const toggle = document.getElementById('togglePassword');
    if (toggle) {
        toggle.addEventListener('click', function () {
            const pwd = document.getElementById('password');
            if (!pwd) return;
            const isPwd = pwd.getAttribute('type') === 'password';
            pwd.setAttribute('type', isPwd ? 'text' : 'password');
            // toggle active appearance
            toggle.classList.toggle('active', isPwd);
        });
    }

    document.getElementById('loginButton').addEventListener('click', async function () {
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const errorMessage = document.getElementById('error-message');

    errorMessage.textContent = '';
    errorMessage.classList.remove('show');

    if (!username || !password) {
        errorMessage.textContent = 'Please fill in both fields.';
        errorMessage.classList.add('show');
        return;
    }

    try {
        const response = await fetch('../server/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password }),
        });

        const result = await response.json();

        if (result.success) {
            // Redirect to the returned page
            window.location.href = result.redirect_url || 'admin_dashboard.html';
        } else {
            errorMessage.textContent = result.error || 'Invalid username or password.';
            errorMessage.classList.add('show');
        }
    } catch (error) {
        errorMessage.textContent = 'An error occurred while logging in. Please try again.';
        errorMessage.classList.add('show');
        console.error('Login error:', error);
    }
    });
});
