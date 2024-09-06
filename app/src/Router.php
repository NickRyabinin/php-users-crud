<?php

class Router
{
    private $routes = [];

    // Метод для добавления маршрутов
    public function addRoute($method, $route, $action)
    {
        $this->routes[strtoupper($method)][$route] = $action;
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
/*
// Создание экземпляра роутера
$router = new Router();

// Добавление маршрутов
$router->addRoute('GET', '/users', function() {
    getUsers();
});

$router->addRoute('GET', '/users/{id}', function($id) {
    getUsers($id);
});

$router->addRoute('POST', '/users', function() {
    addUser();
});

$router->addRoute('PUT', '/users/{id}', function($id) {
    updateUser($id);
});

$router->addRoute('DELETE', '/users/{id}', function($id) {
    deleteUser($id);
});

// Обработка маршрутов
$router->route();
*/
