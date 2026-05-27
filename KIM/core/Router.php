<?php

namespace Core;

// Clasa simpla de rutare care asociaza rutele HTTP (metoda si cale) cu metodele din controllere
class Router {
    private $routes = [];

    // Inregistreaza o ruta noua in aplicatie (ex: POST, /api/login, AuthController@login)
    public function add($method, $path, $handler) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    // Directioneaza cererea primita catre controllerul si metoda corespunzatoare
    public function dispatch($uri, $reqMethod) {
        $uri = strtok($uri, '?');
        
        foreach ($this->routes as $route) {
            if ($route['path'] === $uri && $route['method'] === $reqMethod) {
                list($controller, $method) = explode('@', $route['handler']);
                $controllerClass = "Controllers\\" . $controller;
                
                if (class_exists($controllerClass)) {
                    $instance = new $controllerClass();
                    if (method_exists($instance, $method)) {
                        $instance->$method();
                        return;
                    }
                }
            }
        }
        
        http_response_code(404);
        echo json_encode(["eroare" => "Ruta nu a fost gasita"]);
    }
}
