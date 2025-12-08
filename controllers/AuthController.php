<?php
require_once BASE_PATH . '/models/UserModel.php';
require_once BASE_PATH . '/core/AuditTrailTrait.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


class AuthController extends Controller {
    use AuditTrailTrait;
    
    public function login() {
        if ($this->auth->isLoggedIn()) {
            $basePath = dirname($_SERVER['SCRIPT_NAME']);
            header('Location: ' . $basePath . '/dashboard');
            exit;
        }
        
        $this->render('auth/login');
    }
    
    public function apiLogin() {
        $data = $this->getRequestData();
        
        if (!isset($data['username']) || !isset($data['password'])) {
            $this->jsonResponse(['error' => 'Username and password are required'], 400);
        }
        
        $username = trim($data['username']);
        $password = $data['password'];
        
        $userModel = new UserModel();
        $user = $userModel->authenticate($username, $password);
        
        if ($user) {
            $this->auth->setUserSession($user);
            // Audit trail: login
            try { $this->logLogin('users', (int)$user['id']); } catch (Exception $e) {}
            $basePath = dirname($_SERVER['SCRIPT_NAME']);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Login successful',
                'user' => $user,
                'redirect' => $basePath . '/dashboard'
            ]);
        } else {
            $this->jsonResponse(['error' => 'Invalid username or password'], 401);
        }
    }

    // ------------------------------------------------------------------
    // Forgot / Reset Password UI and API
    // ------------------------------------------------------------------
    public function forgot() {
        if ($this->auth->isLoggedIn()) {
            $basePath = dirname($_SERVER['SCRIPT_NAME']);
            header('Location: ' . $basePath . '/dashboard');
            exit;
        }
        $this->render('auth/forgot_password');
    }

    public function apiForgotPassword() {
        $data = $this->getRequestData();
        $email = trim($data['email'] ?? '');

        if (empty($email)) {
            $this->jsonResponse(['success' => false, 'message' => 'Email is required'], 400);
        }

        $userModel = new UserModel();
        try {
            $result = $userModel->createPasswordReset($email);

            // Always return generic success for security
            if (!$result) {
                $this->jsonResponse([
                    'success' => true, 
                    'message' => 'If the email exists, a password reset link has been sent.'
                ]);
            }

            $token = $result['token'];
            $user = $result['user'];

            // Build reset link
            $resetLink = rtrim(APP_URL, '/') . '/reset-password?token=' . urlencode($token);

            // ----------------------
            // PHPMailer setup
            // ----------------------
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = EMAIL_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = EMAIL_USERNAME;
                $mail->Password   = EMAIL_PASSWORD;
                $mail->SMTPSecure = EMAIL_ENCRYPTION; // tls or ssl
                $mail->Port       = EMAIL_PORT;
                // $mail->SMTPDebug = 2;

                // var_dump(EMAIL_HOST); die;

                $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
                $mail->addAddress($user['email'], $user['full_name']);

                $mail->isHTML(true);
                $mail->Subject = APP_NAME . ' - Password Reset Request';
                $mail->Body    = "
                    <p>Hi " . htmlspecialchars($user['full_name']) . ",</p>
                    <p>We received a request to reset your password. Click the link below to set a new password. This link will expire in 1 hour.</p>
                    <p><a href=\"{$resetLink}\">Reset your password</a></p>
                    <p>If you did not request this, you can safely ignore this email.</p>
                ";

                $mail->send();
            } catch (Exception $e) {
                error_log("Forgot password: failed to send email to {$user['email']}. Error: {$mail->ErrorInfo}");
            }

            // Return generic success message; include token only for dev convenience
            $resp = [
                'success' => true,
                'message' => 'If the email exists, a password reset link has been sent.'
            ];

            if (defined('APP_ENV') && APP_ENV === 'development') {
                // Show debug info for local testing
                $resp['debug'] = [
                    'email_exists' => $result ? true : false,
                    'reset_token' => $result['token'] ?? null,
                    'reset_link' => $result ? $resetLink : null
                ];
            }


            $this->jsonResponse($resp);

        } catch (Exception $e) {
            error_log('Error in apiForgotPassword: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Failed to process request'], 500);
        }
    }

    public function reset() {
        if ($this->auth->isLoggedIn()) {
            $basePath = dirname($_SERVER['SCRIPT_NAME']);
            header('Location: ' . $basePath . '/dashboard');
            exit;
        }

        $token = $_GET['token'] ?? '';
        $this->render('auth/reset_password', ['token' => $token]);
    }

    public function apiResetPassword() {
        $data = $this->getRequestData();
        $token = trim($data['token'] ?? '');
        $password = $data['password'] ?? '';
        $passwordConfirm = $data['password_confirm'] ?? '';

        if (empty($token) || empty($password) || empty($passwordConfirm)) {
            $this->jsonResponse(['success' => false, 'message' => 'All fields are required'], 400);
        }

        if ($password !== $passwordConfirm) {
            $this->jsonResponse(['success' => false, 'message' => 'Passwords do not match'], 400);
        }

        // Basic password rule: min length 8
        if (strlen($password) < 8) {
            $this->jsonResponse(['success' => false, 'message' => 'Password must be at least 8 characters'], 400);
        }

        $userModel = new UserModel();
        try {
            $ok = $userModel->resetPasswordByToken($token, $password);
            if ($ok) {
                $this->jsonResponse(['success' => true, 'message' => 'Password updated successfully']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid or expired token'], 400);
            }
        } catch (Exception $e) {
            error_log('Error in apiResetPassword: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Failed to reset password'], 500);
        }
    }

    public function logout() {
        $current = $this->auth->getCurrentUser();
        if ($current && isset($current['id'])) {
            try { $this->logLogout('users', (int)$current['id']); } catch (Exception $e) {}
        }
        $this->auth->logout();
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        header('Location: ' . $basePath . '/login');
        exit;
    }
    
    public function apiLogout() {
        $current = $this->auth->getCurrentUser();
        if ($current && isset($current['id'])) {
            try { $this->logLogout('users', (int)$current['id']); } catch (Exception $e) {}
        }
        $this->auth->logout();
        $this->jsonResponse(['success' => true, 'message' => 'Logout successful']);
    }
}
?>