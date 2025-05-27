<?php 
session_start(); 
$pageTitle = "Login & Register - AlbumRanker";
include 'includes/header.php';
?>

<!-- ##### Breadcumb Area Start ##### -->
<section class="breadcumb-area bg-img bg-overlay" style="background-image: url('https://images.pexels.com/photos/257904/pexels-photo-257904.jpeg');">
    <div class="bradcumbContent">
        <p>Welcome to AlbumRanker</p>
        <h2>Login or Register</h2>
    </div>
</section>
<!-- ##### Breadcumb Area End ##### -->

<!-- ##### Login Area Start ##### -->
<section class="login-area section-padding-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-6">
                <div class="login-content">
                    <h3>Welcome Back</h3>
                    <!-- Login Form -->
                    <div class="login-form">
                        <form id="loginForm" action="#" method="post">
                            <div class="form-group">
                                <label for="loginEmail">Email address</label>
                                <input type="email" class="form-control" id="loginEmail" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="loginPassword">Password</label>
                                <input type="password" class="form-control" id="loginPassword" name="password" required>
                            </div>
                            <input type="hidden" name="is_login" value="1">
                            <button type="submit" class="btn oneMusic-btn mt-30">Login</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="login-content">
                    <h3>Create Account</h3>
                    <!-- Register Form -->
                    <div class="login-form">
                        <form id="registerForm" action="#" method="post">
                            <div class="form-group">
                                <label for="registerUsername">Username</label>
                                <input type="text" class="form-control" id="registerUsername" name="username" required>
                            </div>
                            <div class="form-group">
                                <label for="registerEmail">Email address</label>
                                <input type="email" class="form-control" id="registerEmail" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="registerPassword">Password</label>
                                <input type="password" class="form-control" id="registerPassword" name="password" required>
                            </div>
                            <button type="submit" class="btn oneMusic-btn mt-30">Register</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- ##### Login Area End ##### -->

<script>
    // Common function for handling form submissions
    function handleFormSubmit(formId, successCallback) {
        const form = document.getElementById(formId);
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('auth.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    successCallback(data);
                } else {
                    alert(data.message || 'Operation failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    }

    // Handle login form
    handleFormSubmit('loginForm', (data) => {
        window.location.href = 'index.php';
    });

    // Handle register form
    handleFormSubmit('registerForm', (data) => {
        alert('Registration successful! Please login.');
        document.getElementById('registerForm').reset();
    });
</script>

<?php include 'includes/footer.php'; ?>