<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="mb-3">Reset Password</h4>
                        <form id="resetForm">
                            <input type="hidden" id="token" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">Confirm Password</label>
                                <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Update Password</button>
                            </div>
                        </form>
                        <div id="alertContainer" class="mt-3"></div>
                        <div class="mt-3 text-center">
                            <a href="<?= APP_URL ?>/login">Back to Sign In</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function(){
    $('#resetForm').submit(function(e){
        e.preventDefault();
        const token = $('#token').val().trim();
        const password = $('#password').val();
        const password_confirm = $('#password_confirm').val();
        if (!token) return showAlert('Invalid or missing token', 'danger');
        const btn = $(this).find('button[type=submit]');
        const orig = btn.html();
        btn.prop('disabled', true).html('Updating...');
        $.ajax({
            url: '<?= APP_URL ?>/api/reset-password',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ token: token, password: password, password_confirm: password_confirm }),
            success: function(res){
                showAlert(res.message || 'Password updated', 'success');
                setTimeout(function(){ window.location.href = '<?= APP_URL ?>/login'; }, 1200);
            },
            error: function(xhr){
                const r = xhr.responseJSON || {};
                showAlert(r.message || 'Failed to reset password', 'danger');
            },
            complete: function(){ btn.prop('disabled', false).html(orig); }
        });
    });
    function showAlert(msg, type){
        $('#alertContainer').html(`<div class="alert alert-${type}">${msg}</div>`);
    }
});
</script>
</body>
</html>
