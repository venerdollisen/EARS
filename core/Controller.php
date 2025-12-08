<?php
require_once BASE_PATH . '/core/AuthorizationTrait.php';

class Controller {
    use AuthorizationTrait;
    
    protected $db;
    protected $auth;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->auth = new Auth();
    }

    protected function render($view, $data = []) {
        // Extract data to variables
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        $viewFile = "views/{$view}.php";
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            throw new Exception("View {$view} not found");
        }
        
        // Get the buffered content
        $content = ob_get_clean();
        // Include layout if it's not an API request and not a standalone page
        if (!$this->isApiRequest() && !$this->isStandalonePage($view)) {
            include 'views/layouts/main.php';
        } else {
            echo $content;
        }
    }

    protected function isStandalonePage($view) {
        // Pages that should not use the main layout
        $standalonePages = ['auth/login', 'auth/forgot_password', 'auth/reset_password'];
        return in_array($view, $standalonePages);
    }

    protected function renderPartial($view, $data = []) {
        extract($data);
        $viewFile = "views/{$view}.php";
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            throw new Exception("View {$view} not found");
        }
    }

    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function isApiRequest() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        // Normalize by stripping the base script directory and common base folder '/EARS'
        $base = dirname($_SERVER['SCRIPT_NAME']);
        if ($base && $base !== '/' && strpos($uri, $base) === 0) {
            $uri = substr($uri, strlen($base));
        }
        $uri = preg_replace('#^/ears#i', '', $uri);
        return strpos($uri, '/api/') === 0;
    }

    protected function requireAuth() {
        if (!$this->auth->isLoggedIn()) {
            if ($this->isApiRequest()) {
                $this->jsonResponse(['error' => 'Unauthorized'], 401);
            } else {
                $basePath = dirname($_SERVER['SCRIPT_NAME']);
                header('Location: ' . $basePath . '/login');
                exit;
            }
        }
    }

    protected function getRequestData() {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?: $_POST;
    }
}
?>