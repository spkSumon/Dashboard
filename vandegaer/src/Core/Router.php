<?php
namespace App\Core;

use App\Helpers\Response;

/**
 * Super simpele router (vanilla).
 *
 * Idee:
 * - Je registreert routes: GET /api/posts -> functie
 * - Bij elke request zoekt dispatch() de juiste handler
 *
 * Pattern ondersteuning:
 * - Exacte routes: /api/health
 * - Routes met {id}: /api/posts/{id}
 */
class Router {
    /** @var array<int, array{method:string, pattern:string, handler:callable}> */
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $path): void {
        $method = strtoupper($method);

        foreach ($this->routes as $r) {
            if ($r['method'] !== $method) continue;

            $regex = $this->toRegex($r['pattern']);
            if (preg_match($regex, $path, $matches)) {
                $params = [];
                foreach ($matches as $k => $v) {
                    if (!is_int($k)) $params[$k] = $v;
                }
                ($r['handler'])($params);
                return;
            }
        }

        Response::error('Route niet gevonden', 404);
    }

    private function toRegex(string $pattern): string {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $regex . '$#';
    }
}
