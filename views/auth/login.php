<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <!-- Logo -->
                        <div class="text-center mb-4">
                            <i class="bi bi-graph-up text-primary" style="font-size: 3rem;"></i>
                            <h2 class="mt-3 mb-0">EARS</h2>
                            <p class="text-muted">Enterprise Analytics and Result Systems</p>
                        </div>
                        
                        <!-- Login Form -->
                        <form id="loginForm">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    Sign In
                                </button>
                            </div>
                        </form>

                        <!-- Forgot password link -->
                        <div class="mt-3 text-center">
                            <a href="<?= APP_URL ?>/forgot-password" class="text-decoration-none">Forgot password?</a>
                        </div>
                        
                        <!-- Alert Messages -->
                        <div id="alertContainer"></div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="text-center mt-4">
                    <small class="text-muted">
                        &copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Toggle password visibility
            $('#togglePassword').click(function() {
                const passwordField = $('#password');
                const icon = $(this).find('i');
                
                if (passwordField.attr('type') === 'password') {
                    passwordField.attr('type', 'text');
                    icon.removeClass('bi-eye').addClass('bi-eye-slash');
                } else {
                    passwordField.attr('type', 'password');
                    icon.removeClass('bi-eye-slash').addClass('bi-eye');
                }
            });
            
            // Handle login form submission
            $('#loginForm').submit(function(e) {
                e.preventDefault();
                
                const username = $('#username').val().trim();
                const password = $('#password').val();
                
                if (!username || !password) {
                    showAlert('Please enter both username and password.', 'danger');
                    return;
                }
                
                // Disable submit button
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<i class="bi bi-arrow-clockwise spin me-2"></i>Signing In...');
                
                // Make API request
                $.ajax({
                    url: '<?= APP_URL ?>/api/login',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        username: username,
                        password: password
                    }),
                    success: function(response) {
                        if (response.success) {
                            showAlert('Login successful! Redirecting...', 'success');
                            setTimeout(function() {
                                window.location.href = response.redirect;
                            }, 1000);
                        } else {
                            showAlert(response.error || 'Login failed.', 'danger');
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        showAlert(response?.error || 'Login failed. Please try again.', 'danger');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
            
            // Use EARS.showAlert instead of local showAlert function
            function showAlert(message, type) {
                if (window.EARS && window.EARS.showAlert) {
                    window.EARS.showAlert(message, type);
                } else {
                    // Fallback for when EARS is not available
                    const alertHtml = `
                        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                            ${message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;
                    $('#alertContainer').html(alertHtml);
                    
                    // Auto-remove after 2.5 seconds to match EARS.showAlert
                    setTimeout(() => {
                        $('#alertContainer .alert').fadeOut(300, function() {
                            $(this).remove();
                        });
                    }, 2500);
                }
            }
        });
    </script>
    
    <style>
        .spin {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</body>
</html>