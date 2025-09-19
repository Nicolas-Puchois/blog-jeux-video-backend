<?php

declare(strict_types=1);

namespace App\core;

use Exception;

class Router
{
    private array $routes;


    public function __construct()
    {
        $this->routes = RouteResolver::getRoutes();
    }

    public function dispatch(string $uri, string $method): void
    {


        // Chercher une route correspondante avec des paramètres dynamiques
        foreach ($this->routes[$method] as $pattern => $routeInfo) {
            $pattern = '#^' . $pattern . '$#';
            if (preg_match($pattern, $uri, $matches)) {
                $controllerClass = $routeInfo['className'];
                $action = $routeInfo['methodName'];


                // Extraire les paramètres de l'URL
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                unset($params[0]); // Supprimer la correspondance complète

                // Convertir les types de paramètres si nécessaire
                $params = array_map(function ($param) {
                    if (is_numeric($param)) {
                        return is_float($param * 1) ? (float)$param : (int)$param;
                    }
                    return $param;
                }, $params);

                $controller = new $controllerClass();
                $controller->$action(...array_values($params));
                return;
            }
        }

        throw new Exception("Route not found for {$method} {$uri}");
    }
}
