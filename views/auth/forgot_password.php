<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="mb-3">Forgot Password</h4>
                        <p class="text-muted">Enter the email address associated with your account. We'll send a link to reset your password.</p>
                        <form id="forgotForm">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Send Reset Link</button>
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
    $('#forgotForm').submit(function(e){
        e.preventDefault();
        const email = $('#email').val().trim();
        if (!email) return showAlert('Please enter your email', 'danger');
        const btn = $(this).find('button[type=submit]');
        const orig = btn.html();
        btn.prop('disabled', true).html('Sending...');
        $.ajax({
            url: '<?= APP_URL ?>/api/forgot-password',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({email: email}),
            success: function(res){
                showAlert(res.message || 'If the email exists, a reset link has been sent', 'success');
            },
            error: function(xhr){
                const r = xhr.responseJSON || {};
                showAlert(r.message || 'Failed to send reset link', 'danger');
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
