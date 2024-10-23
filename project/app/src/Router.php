<?php

namespace src;

class Router
{
    private $routes = [];

    // Метод для добавления маршрутов
    public function addRoute($method, $route, $controller, $action)
    {
        $this->routes[strtoupper($method)][$route] = [$controller, $action];
    }

    // Метод для загрузки маршрутов из массива
    public function loadRoutes($routes, $controllers)
    {
        foreach ($routes as $method => $routeArray) {
            foreach ($routeArray as $route => $controllerAction) {
                [$controllerName, $action] = $controllerAction;
                $controller = $controllers[$controllerName];
                $this->addRoute($method, $route, $controller, $action);
            }
        }
    }

    // Метод для обработки маршрутов
    public function route()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        // Проверяем наличие скрытого поля для метода (для правильной обработки PUT и DELETE)
        if ($requestMethod === 'POST' && isset($_POST['http_method'])) {
            $requestMethod = strtoupper($_POST['http_method']);
        }

        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (isset($this->routes[$requestMethod])) {
            foreach ($this->routes[$requestMethod] as $route => $controllerAction) {
                // Проверка маршрута с параметрами
                if (preg_match('#^' . str_replace(['{id}'], ['(\d+)'], $route) . '$#', $requestUri, $matches)) {
                    array_shift($matches); // Удаляем первый элемент (полное совпадение)
                    [$controller, $action] = $controllerAction;
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
