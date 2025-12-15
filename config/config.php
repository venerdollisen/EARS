<?php
// Application configuration
define('APP_NAME', 'Enterprise Analytics and Result Systems');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/EARS');
define('APP_ENV', 'development'); // use 'production' in live


// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_SALT', 'ears_salt_2024');

// File upload settings
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Manila');

// ----------------------
// Email Configuration
// ----------------------
define('EMAIL_HOST', 'smtp.gmail.com');      // SMTP server
define('EMAIL_PORT', 587);                        // 587 = TLS, 465 = SSL
define('EMAIL_USERNAME', 'kulotsivener13@gmail.com'); // SMTP username
define('EMAIL_PASSWORD', 'dham uxqi zyio ward');    // SMTP password
define('EMAIL_FROM', 'kulotsivener13@gmail.com');     // From email
define('EMAIL_FROM_NAME', APP_NAME);                // From name
define('EMAIL_ENCRYPTION', 'tls');                  // 'tls' or 'ssl'