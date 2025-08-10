<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | <?= APP_NAME ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 text-center">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <!-- 404 Icon -->
                        <div class="mb-4">
                            <i class="bi bi-exclamation-triangle text-warning" style="font-size: 5rem;"></i>
                        </div>
                        
                        <h1 class="display-1 text-muted">404</h1>
                        <h2 class="h4 mb-3">Page Not Found</h2>
                        <p class="text-muted mb-4">
                            The page you're looking for doesn't exist or has been moved.
                        </p>
                        
                        <div class="d-grid gap-2 d-md-block">
                            <a href="/dashboard" class="btn btn-primary">
                                <i class="bi bi-house me-2"></i>Go to Dashboard
                            </a>
                            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Go Back
                            </a>
                        </div>
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
</body>
</html> 