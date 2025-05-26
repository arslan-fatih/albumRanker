// Global variables
let currentUser = null;

// Initialize the application
function init() {
    checkAuth();
    initEventListeners();
}

// Check authentication status
function checkAuth() {
    fetch('auth.php?action=check')
        .then(response => response.json())
        .then(data => {
            currentUser = data.success ? data.user : null;
            updateUIForUser();
        })
        .catch(error => {
            console.error('Auth check failed:', error);
            updateUIForUser();
        });
}

// Initialize event listeners
function initEventListeners() {
    const forms = {
        loginForm: handleLogin,
        registerForm: handleRegister,
        searchForm: handleSearch
    };

    Object.entries(forms).forEach(([id, handler]) => {
        const form = document.getElementById(id);
        if (form) {
            form.addEventListener('submit', handler);
        }
    });

    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }

    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', handleFileSelect);
    });
}

// Update UI based on user status
function updateUIForUser() {
    const isLoggedIn = !!currentUser;
    document.querySelectorAll('.auth-required').forEach(el => el.style.display = isLoggedIn ? '' : 'none');
    document.querySelectorAll('.auth-not-required').forEach(el => el.style.display = isLoggedIn ? 'none' : '');
    
    const userMenu = document.getElementById('userMenu');
    if (userMenu) {
        userMenu.innerHTML = isLoggedIn ? `
            <a href="profile.php" class="btn btn-primary">My Profile</a>
            <a href="#" id="logoutBtn" class="btn btn-outline-primary ml-2">Logout</a>
        ` : `
            <a href="login.php" class="btn btn-primary">Login / Register</a>
        `;
        
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', handleLogout);
        }
    }
}

// Handle login
function handleLogin(event) {
    event.preventDefault();
    submitForm(event.target, 'auth.php')
        .then(data => {
            if (data.success) {
                currentUser = data.user;
                updateUIForUser();
                window.location.href = 'index.php';
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Login failed:', error);
            showAlert('Login failed. Please try again.', 'danger');
        });
}

// Handle register
function handleRegister(event) {
    event.preventDefault();
    submitForm(event.target, 'auth.php')
        .then(data => {
            if (data.success) {
                showAlert('Registration successful. Please login.', 'success');
                event.target.reset();
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Registration failed:', error);
            showAlert('Registration failed. Please try again.', 'danger');
        });
}

// Handle logout
window.handleLogout = function(event) {
    event.preventDefault();
    fetch('auth.php?action=logout')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentUser = null;
                updateUIForUser();
                window.location.href = 'index.php';
            }
        })
        .catch(error => {
            console.error('Logout failed:', error);
            showAlert('Logout failed. Please try again.', 'danger');
        });
}

// Handle search
function handleSearch(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const params = new URLSearchParams({
        q: formData.get('q'),
        genre: formData.get('genre'),
        sort: formData.get('sort')
    });
    window.location.href = `albums-store.php?${params.toString()}`;
}

// Handle file selection
function handleFileSelect(event) {
    const file = event.target.files[0];
    if (!file || !file.type.startsWith('image/')) return;
    
    const reader = new FileReader();
    reader.onload = e => {
        const preview = event.target.parentElement.querySelector('.file-preview');
        if (preview) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
    };
    reader.readAsDataURL(file);
}

// Show alert message
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    `;
    
    const container = document.querySelector('.alert-container') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => alertDiv.remove(), 5000);
}

// Helper function to submit forms
function submitForm(form, url) {
    return fetch(url, {
        method: 'POST',
        body: new FormData(form)
    }).then(response => response.json());
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', init); 