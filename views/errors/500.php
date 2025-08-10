<div class="row">
    <div class="col-12">
        <div class="text-center py-5">
            <div class="mb-4">
                <i class="bi bi-exclamation-triangle text-danger" style="font-size: 4rem;"></i>
            </div>
            <h1 class="h3 text-danger mb-3">Internal Server Error</h1>
            <p class="text-muted mb-4">Something went wrong on our end. Please try again later.</p>
            
            <?php if (isset($error) && $error): ?>
                <div class="alert alert-danger text-start">
                    <strong>Error Details:</strong><br>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="<?= APP_URL ?>/dashboard" class="btn btn-primary me-2">
                    <i class="bi bi-house me-2"></i>Go to Dashboard
                </a>
                <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                    <i class="bi bi-arrow-left me-2"></i>Go Back
                </button>
            </div>
        </div>
    </div>
</div> 