<?php

declare(strict_types=1);

namespace App;

final class Router
{
    /** @var array<string, array<string, array{0: class-string, 1: string}>> */
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $path = rtrim(parse_url($path, PHP_URL_PATH) ?: '/', '/');
        if ($path === '') {
            $path = '/';
        }

        [$handlerPath, $params] = $this->matchRoute($method, $path);

        if ($handlerPath === null) {
            http_response_code(404);
            echo '404 – Seite nicht gefunden';
            return;
        }

        [$class, $methodName] = $this->routes[$method][$handlerPath];
        $controller = new $class();
        $controller->$methodName(...$params);
    }

    /**
     * @return array{0: ?string, 1: array<string>}
     */
    private function matchRoute(string $method, string $path): array
    {
        if (!isset($this->routes[$method])) {
            return [null, []];
        }

        if (isset($this->routes[$method][$path])) {
            return [$path, []];
        }

        foreach (array_keys($this->routes[$method]) as $routePath) {
            if (!str_contains($routePath, '{')) {
                continue;
            }

            $pattern = '#^' . preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', $routePath) . '$#';
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches);
                return [$routePath, $matches];
            }
        }

        return [null, []];
    }
}
