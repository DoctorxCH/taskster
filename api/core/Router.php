<?php

class Router {
    private array $routes = [];
    private array $middlewares = [];

    public function get(string $path, callable|array $handler): void {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable|array $handler): void {
        $this->addRoute('PUT', $path, $handler);
    }

    public function patch(string $path, callable|array $handler): void {
        $this->addRoute('PATCH', $path, $handler);
    }

    public function delete(string $path, callable|array $handler): void {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable|array $handler): void {
        // Convert path parameters like :id or {id} into regex
        $pattern = preg_replace('/:[a-zA-Z0-9_]+|\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_-]+)', $path);
        $this->routes[] = [
            'method' => $method,
            'pattern' => '#^' . $pattern . '$#',
            'handler' => $handler
        ];
    }

    public function addMiddleware(callable $middleware): void {
        $this->middlewares[] = $middleware;
    }

    public function dispatch(string $method, string $path, array $body = []): bool {
        // Remove trailing slash
        $path = rtrim($path, '/');
        if (empty($path)) {
            $path = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $path, $matches)) {
                array_shift($matches); // Remove full match

                // Execute middlewares
                foreach ($this->middlewares as $middleware) {
                    $middlewareResult = call_user_func($middleware, $path, $method);
                    if ($middlewareResult === false) {
                        return true; // Middleware handled the request (e.g. sent 401/403)
                    }
                }

                try {
                    $handler = $route['handler'];
                    // Pass body as first parameter if POST/PUT/PATCH, followed by path variables
                    $args = [];
                    if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
                        $args[] = $body;
                    }
                    $args = array_merge($args, $matches);
                    
                    if (is_array($handler) && is_string($handler[0])) {
                        $controller = new $handler[0]();
                        $result = call_user_func_array([$controller, $handler[1]], $args);
                    } else {
                        $result = call_user_func_array($handler, $args);
                    }

                    if ($result !== null) {
                        $this->jsonResponse($result);
                    }
                } catch (Exception $e) {
                    $this->errorResponse($e->getMessage(), $e->getCode() ?: 500);
                }
                return true; // Route handled
            }
        }
        
        return false; // Route not found by new router
    }

    public function jsonResponse($data, int $statusCode = 200): void {
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    public function errorResponse(string $message, int $statusCode = 400): void {
        http_response_code($statusCode === 0 ? 500 : $statusCode);
        echo json_encode(['error' => $message]);
        exit;
    }
}
