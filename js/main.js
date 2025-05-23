// Global variables
let currentUser = null;
let unreadMessageCount = 0;

// Initialize the application
function init() {
    // Check authentication status
    checkAuth();
    
    // Initialize event listeners
    initEventListeners();
    
    // Initialize message polling if logged in
    if (currentUser) {
        startMessagePolling();
    }
}

// Check authentication status
function checkAuth() {
    fetch('auth.php?action=check')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentUser = data.user;
                updateUIForLoggedInUser();
            } else {
                updateUIForLoggedOutUser();
            }
        })
        .catch(error => {
            console.error('Auth check failed:', error);
            updateUIForLoggedOutUser();
        });
}

// Initialize event listeners
function initEventListeners() {
    // Login form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    // Register form
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }
    
    // Logout button
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }
    
    // Search form
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', handleSearch);
    }
    
    // File uploads
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', handleFileSelect);
    });
}

// Update UI for logged in user
function updateUIForLoggedInUser() {
    document.querySelectorAll('.auth-required').forEach(el => el.style.display = '');
    document.querySelectorAll('.auth-not-required').forEach(el => el.style.display = 'none');
    const userMenu = document.getElementById('userMenu');
    if (userMenu) {
        userMenu.innerHTML = `
            <a href="profile.php" class="btn btn-primary">My Profile</a>
            <a href="#" id="logoutBtn" class="btn btn-outline-primary ml-2">Logout</a>
        `;
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', handleLogout);
        }
    }
}

// Update UI for logged out user
function updateUIForLoggedOutUser() {
    document.querySelectorAll('.auth-required').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.auth-not-required').forEach(el => el.style.display = '');
    const userMenu = document.getElementById('userMenu');
    if (userMenu) {
        userMenu.innerHTML = `
            <a href="login.php" class="btn btn-primary">Login / Register</a>
        `;
    }
}

// Handle login
function handleLogin(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    fetch('auth.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentUser = data.user;
            updateUIForLoggedInUser();
            forceUserMenuUpdate();
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
    
    const formData = new FormData(event.target);
    
    fetch('auth.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
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
function handleLogout(event) {
    event.preventDefault();
    
    fetch('auth.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'action=logout'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentUser = null;
            updateUIForLoggedOutUser();
            forceUserMenuUpdate();
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
    const query = formData.get('q');
    const genre = formData.get('genre');
    const sort = formData.get('sort');
    
    window.location.href = `albums-store.php?q=${encodeURIComponent(query)}&genre=${genre}&sort=${sort}`;
}

// Handle file selection
function handleFileSelect(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // Show preview for images
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = event.target.parentElement.querySelector('.file-preview');
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(file);
    }
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
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Start message polling
function startMessagePolling() {
    // Check for new messages every 30 seconds
    setInterval(checkNewMessages, 30000);
}

// Check for new messages
function checkNewMessages() {
    fetch('messages.php?action=get_unread_count')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.count !== unreadMessageCount) {
                unreadMessageCount = data.count;
                updateMessageBadge();
            }
        })
        .catch(error => console.error('Message check failed:', error));
}

// Update message badge
function updateMessageBadge() {
    const badge = document.querySelector('#userMenu .badge');
    if (badge) {
        badge.textContent = unreadMessageCount;
        badge.style.display = unreadMessageCount > 0 ? 'inline' : 'none';
    }
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Format duration
function formatDuration(seconds) {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
}

// Force update user menu on every page load
function forceUserMenuUpdate() {
    fetch('auth.php?action=check')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentUser = data.user;
                updateUIForLoggedInUser();
            } else {
                updateUIForLoggedOutUser();
            }
        })
        .catch(() => updateUIForLoggedOutUser());
}

document.addEventListener('DOMContentLoaded', function() {
    init();
    forceUserMenuUpdate();
}); 