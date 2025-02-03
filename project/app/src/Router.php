<?php

/**
 * Роутер.
 * Загружает существующие маршруты и контроллеры в общий массив, а затем вызывает
 * нужный метод определённого контроллера, сопоставляя загруженные данные с введённым
 * посетителем запросом.
 */

namespace src;

class Router
{
    private array $routes = [];
    private Request $request;
    private AuthMiddleware $authMiddleware;

    public function __construct(Request $request, AuthMiddleware $authMiddleware)
    {
        $this->request = $request;
        $this->authMiddleware = $authMiddleware;
    }

    public function addRoute(string $method, string $route, array $routeData): void
    {
        $this->routes[strtoupper($method)][$route] = $routeData;
    }

    public function loadRoutes(array $routes, array $controllers): void
    {
        foreach ($routes as $method => $routeArray) {
            foreach ($routeArray as $route => $routeData) {
                $controllerName = $routeData[0];
                $action = $routeData[1];
                $meta = isset($routeData[2]) ? $routeData[2] : [];

                $controller = $controllers[$controllerName];

                $this->addRoute($method, $route, [$controller, $action, $meta]);
            }
        }
    }

    public function route(): void
    {
        $requestMethod = $this->getRequestMethod();
        $requestPath = $this->request->getParsedUrl()['path'];

        if (isset($this->routes[$requestMethod])) {
            foreach ($this->routes[$requestMethod] as $route => $routeData) {
                if ($this->matchRoute($route, $requestPath, $routeData)) {
                    return;
                }
            }
        }

        $this->handleRouteNotFound();
    }

    private function getRequestMethod(): string
    {
        $requestMethod = $this->request->getHttpMethod();
        $hiddenRequestMethod = $this->request->getFormData('http_method');
        return ($requestMethod === 'POST' && $hiddenRequestMethod) ? strtoupper($hiddenRequestMethod) : $requestMethod;
    }

    private function matchRoute(string $route, string $requestPath, array $routeData): bool
    {
        if (preg_match('#^' . str_replace(['{id}'], ['(\d+)'], $route) . '$#', $requestPath, $matches)) {
            array_shift($matches);
            [$controller, $action, $meta] = $routeData;
            $this->checkVisitorPermissions($meta);
            call_user_func_array([$controller, $action], $matches);
            return true;
        }
        return false;
    }

    private function checkVisitorPermissions(array $meta): void
    {
        if (isset($meta['auth']) && $meta['auth'] === true) {
            $this->authMiddleware->checkAuth();
        }
        if (isset($meta['admin']) && $meta['admin'] === true) {
            $this->authMiddleware->checkAdmin();
        }
    }

    private function handleRouteNotFound(): void
    {
        http_response_code(404);
        echo json_encode(['message' => 'Route not found']);
    }
}
