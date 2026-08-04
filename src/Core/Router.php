<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\HttpException;

/**
 * Enrutador: asocia metodo + patron de ruta con un metodo de un controlador.
 *
 * Los parametros dinamicos se declaran entre llaves: /api/eventos/{id}
 */
final class Router
{
    /** @var array<int, array{method:string, regex:string, params:string[], handler:array{0:class-string,1:string}}> */
    private array $routes = [];

    /** @param array{0:class-string,1:string} $handler */
    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /** @param array{0:class-string,1:string} $handler */
    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /** @param array{0:class-string,1:string} $handler */
    public function put(string $path, array $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    /** @param array{0:class-string,1:string} $handler */
    public function patch(string $path, array $handler): void
    {
        $this->add('PATCH', $path, $handler);
    }

    /** @param array{0:class-string,1:string} $handler */
    public function delete(string $path, array $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    /** @param array{0:class-string,1:string} $handler */
    private function add(string $method, string $path, array $handler): void
    {
        $params = [];
        $path = '/' . trim($path, '/');

        $regex = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            static function (array $matches) use (&$params): string {
                $params[] = $matches[1];
                return '([^/]+)';
            },
            $path
        );

        $this->routes[] = [
            'method'  => $method,
            'regex'   => '#^' . $regex . '$#',
            'params'  => $params,
            'handler' => $handler,
        ];
    }

    /**
     * Resuelve la peticion y ejecuta el controlador correspondiente.
     */
    public function dispatch(Request $request): void
    {
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $request->path(), $matches) !== 1) {
                continue;
            }

            if ($route['method'] !== $request->method()) {
                $allowedMethods[] = $route['method'];
                continue;
            }

            array_shift($matches);
            $request->setParams(array_combine($route['params'], $matches) ?: []);

            [$class, $action] = $route['handler'];
            $controller = new $class();
            $controller->{$action}($request);

            return;
        }

        if ($allowedMethods !== []) {
            throw new HttpException(
                405,
                sprintf('Metodo %s no permitido en esta ruta. Permitidos: %s',
                    $request->method(),
                    implode(', ', array_unique($allowedMethods))
                )
            );
        }

        throw HttpException::notFound(sprintf('La ruta %s %s no existe', $request->method(), $request->path()));
    }
}
