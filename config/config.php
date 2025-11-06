<?php
// Application configuration
define('APP_NAME', 'Enterprise Analytics and Result Systems');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/EARS');

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