<?php
class Router {
    private $routes = [];

    public function addRoute($path, $handler) {
        $this->routes[$path] = $handler;
    }

    public function dispatch() {
        $requestUri = $_SERVER['REQUEST_URI'];
        $path = parse_url($requestUri, PHP_URL_PATH);
        
        // Remove base path if exists
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/') {
            $path = str_replace($basePath, '', $path);
        }
        
        // Remove trailing slash
        $path = rtrim($path, '/');
        // Normalize base folder (supports /EARS or /ears)
        $path = preg_replace('#^/ears#i', '', $path);
        if (empty($path)) {
            $path = '/';
        }

        // Debug: Log the processed path
        error_log("Router: Request URI: $requestUri, Processed Path: $path");

        if (isset($this->routes[$path])) {
            $handler = $this->routes[$path];
            error_log("Router: Found route '$path' -> '$handler'");
            $this->executeHandler($handler);
        } else {
            // Check for dynamic routes
            $matched = false;
            foreach ($this->routes as $route => $handler) {
                $params = $this->extractParams($route, $path);
                if ($params !== false) {
                    $this->executeHandler($handler, $params);
                    $matched = true;
                    break;
                }
            }
            
            if (!$matched) {
                error_log("Router: No route found for path: $path");
                http_response_code(404);
                include 'views/errors/404.php';
            }
        }
    }

    private function matchRoute($route, $path) {
        $routePattern = preg_replace('/\/{([^\/]+)}/', '/([^/]+)', $route);
        $routePattern = str_replace('/', '\/', $routePattern);
        return preg_match('/^' . $routePattern . '$/', $path);
    }
    
    private function extractParams($route, $path) {
        // Convert route pattern to regex
        $routePattern = preg_replace('/\/{([^\/]+)}/', '/([^/]+)', $route);
        $routePattern = str_replace('/', '\/', $routePattern);
        $routePattern = '/^' . $routePattern . '$/';
        
        if (preg_match($routePattern, $path, $matches)) {
            // Remove the first match (full string)
            array_shift($matches);
            return $matches;
        }
        
        return false;
    }

    private function executeHandler($handler, $params = []) {
        list($controllerName, $method) = explode('@', $handler);
        
        error_log("Router: Executing handler '$handler' (Controller: $controllerName, Method: $method)");
        if (!empty($params)) {
            error_log("Router: Parameters: " . json_encode($params));
        }
        
        $controllerFile = "controllers/{$controllerName}.php";
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            $controller = new $controllerName();
            
            if (method_exists($controller, $method)) {
                error_log("Router: Calling $controllerName->$method()");
                
                // Call method with parameters if they exist
                if (!empty($params)) {
                    call_user_func_array([$controller, $method], $params);
                } else {
                    $controller->$method();
                }
            } else {
                error_log("Router: Method {$method} not found in {$controllerName}");
                throw new Exception("Method {$method} not found in {$controllerName}");
            }
        } else {
            error_log("Router: Controller file not found: $controllerFile");
            throw new Exception("Controller {$controllerName} not found");
        }
    }
}
?> 