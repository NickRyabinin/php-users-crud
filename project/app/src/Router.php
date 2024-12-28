<?php

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

    // Метод для добавления маршрутов
    public function addRoute(string $method, string $route, array $routeData): void
    {
        $this->routes[strtoupper($method)][$route] = $routeData;
    }

    // Метод для загрузки маршрутов из массива
    public function loadRoutes(array $routes, array $controllers): void
    {
        foreach ($routes as $method => $routeArray) {
            foreach ($routeArray as $route => $routeData) {
                // Извлекаем контроллер, действие и метаданные (если есть)
                $controllerName = $routeData[0];
                $action = $routeData[1];
                $meta = isset($routeData[2]) ? $routeData[2] : [];

                $controller = $controllers[$controllerName];

                $this->addRoute($method, $route, [$controller, $action, $meta]);
            }
        }
    }

    // Метод для обработки маршрутов
    public function route(): void
    {
        $requestMethod = $this->request->getHttpMethod();

        // Проверяем наличие скрытого поля для метода (для правильной обработки PUT и DELETE)
        $hiddenRequestMethod = $this->request->getFormData('http_method');
        if ($requestMethod === 'POST' && $hiddenRequestMethod) {
            $requestMethod = strtoupper($hiddenRequestMethod);
        }

        $requestPath = $this->request->getParsedUrl()['path'];
        if (isset($this->routes[$requestMethod])) {
            foreach ($this->routes[$requestMethod] as $route => $routeData) {
                // Проверка маршрута с параметрами
                if (preg_match('#^' . str_replace(['{id}'], ['(\d+)'], $route) . '$#', $requestPath, $matches)) {
                    array_shift($matches); // Удаляем первый элемент (полное совпадение)
                    [$controller, $action, $meta] = $routeData;

                    if (isset($meta['auth']) && $meta['auth'] === true) {
                        $this->authMiddleware->checkAuth();
                    }

                    if (isset($meta['admin']) && $meta['admin'] === true) {
                        $this->authMiddleware->checkAdmin();
                    }

                    call_user_func_array([$controller, $action], $matches);
                    return;
                }
            }
        }
        // Если маршрут не найден
        http_response_code(404);
        echo json_encode(['message' => 'Route not found']);
    }
}
