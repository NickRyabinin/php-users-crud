<?php
namespace src;

class Router
{
    private $routes = [];

    // Метод для добавления маршрутов
    public function addRoute($method, $route, $action)
    {
        $this->routes[strtoupper($method)][$route] = $action;
    }

    // Метод для загрузки маршрутов из массива
    public function loadRoutes($routes, $userController)
    {
        foreach ($routes as $method => $routeArray) {
            foreach ($routeArray as $route => $action) {
                $this->addRoute($method, $route, function() use ($action, $userController) {
                    return $action($userController, ...func_get_args());
                });
            }
        }
    }

    // Метод для обработки маршрутов
    public function route()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (isset($this->routes[$requestMethod])) {
            foreach ($this->routes[$requestMethod] as $route => $action) {
                // Проверка маршрута с параметрами
                if (preg_match('#^' . str_replace(['{id}'], ['(\d+)'], $route) . '$#', $requestUri, $matches)) {
                    array_shift($matches); // Удаляем первый элемент (полное совпадение)
                    call_user_func_array($action, $matches);
                    return;
                }
            }
        }
        // Если маршрут не найден
        http_response_code(404);
        echo json_encode(['message' => 'Route not found']);
    }
}
