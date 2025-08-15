<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="text-center mt-5">
                <div class="error-page">
                    <h1 class="display-1 text-danger">403</h1>
                    <h2 class="mb-4">Access Forbidden</h2>
                    <p class="lead text-muted mb-4">
                        <?= htmlspecialchars($message ?? 'You do not have permission to access this resource.') ?>
                    </p>
                    <div class="mb-4">
                        <i class="bi bi-shield-exclamation display-4 text-warning"></i>
                    </div>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="<?= APP_URL ?>/dashboard" class="btn btn-primary">
                            <i class="bi bi-house me-2"></i>Go to Dashboard
                        </a>
                        <button onclick="history.back()" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Go Back
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.error-page {
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}

.error-page h1 {
    font-weight: bold;
    margin-bottom: 1rem;
}

.error-page .bi {
    opacity: 0.7;
}
</style>
