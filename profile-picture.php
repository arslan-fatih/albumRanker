<?php
require_once 'config.php';
require_once 'includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Upload Profile Picture</h4>
                </div>
                <div class="card-body">
                    <form id="profilePictureForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="profilePicture" class="form-label">Select Profile Picture</label>
                            <input type="file" class="form-control" id="profilePicture" name="profile" accept="image/*" required>
                            <div class="form-text">Allowed formats: JPG, PNG, GIF. Maximum file size: 5MB</div>
                        </div>
                        <div class="mb-3">
                            <img id="preview" src="#" alt="Profile picture preview" style="max-width: 200px; display: none;" class="img-thumbnail">
                        </div>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </form>
                    <div id="message" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('profilePictureForm');
    const preview = document.getElementById('preview');
    const message = document.getElementById('message');
    const fileInput = document.getElementById('profilePicture');

    // Preview image before upload
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        }
    });

    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('action', 'upload_profile');
        formData.append('profile', fileInput.files[0]);

        message.innerHTML = '<div class="alert alert-info">Uploading...</div>';

        fetch('upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                message.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                // Sayfayı 2 saniye sonra yenile
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                message.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
            }
        })
        .catch(error => {
            message.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
            console.error('Error:', error);
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 