<?php

declare(strict_types=1);

namespace App\core;

use Exception;
use App\middleware\CSRFMiddleware;

class Router
{
    private array $routes;
    private array $excludedRoutes = [
        '/api/login',
        '/api/register'
    ];


    public function __construct()
    {
        $this->routes = RouteResolver::getRoutes();
    }

    private function shouldCheckCSRF(string $uri, string $method): bool
    {
        // On vérifie CSRF uniquement pour les méthodes modifiant des données
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return false;
        }

        // On exclut certaines routes de la vérification CSRF
        return !in_array($uri, $this->excludedRoutes);
    }

    private function verifyCSRF(): void
    {
        if (!CSRFMiddleware::verifyToken()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Token CSRF invalide'
            ]);
            exit;
        }
    }

    public function dispatch(string $uri, string $method): void
    {
        // Vérification CSRF si nécessaire
        if ($this->shouldCheckCSRF($uri, $method)) {
            $this->verifyCSRF();
        }


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
