<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\HttpException;
use App\Core\Exceptions\ValidationException;
use App\Core\Middleware\Cors;
use Throwable;

/**
 * Nucleo de la aplicacion: arranca la configuracion, registra las rutas
 * y despacha la peticion HTTP entrante.
 */
final class App
{
    private string $basePath;
    private Router $router;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR . '/');
        $this->router = new Router();
    }

    public function boot(): self
    {
        Env::load($this->basePath . '/.env');
        Config::load(require $this->basePath . '/config/config.php');

        $debug = (bool) Config::get('app.debug', false);
        ini_set('display_errors', $debug ? '1' : '0');
        error_reporting($debug ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_NOTICE);
        date_default_timezone_set('America/Guayaquil');

        /** @var callable(Router):void $registerRoutes */
        $registerRoutes = require $this->basePath . '/routes/api.php';
        $registerRoutes($this->router);

        return $this;
    }

    public function run(): void
    {
        $request = Request::capture();

        try {
            Cors::apply($request);
            $this->router->dispatch($request);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), $e->statusCode(), $e->errors());
        } catch (HttpException $e) {
            Response::error($e->getMessage(), $e->statusCode());
        } catch (Throwable $e) {
            $this->handleUnexpected($e);
        }
    }

    private function handleUnexpected(Throwable $e): void
    {
        error_log(sprintf('[%s] %s en %s:%d', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));

        if ((bool) Config::get('app.debug', false)) {
            Response::json([
                'ok'        => false,
                'message'   => 'Error interno del servidor',
                'exception' => get_class($e),
                'detail'    => $e->getMessage(),
                'file'      => $e->getFile() . ':' . $e->getLine(),
            ], 500);

            return;
        }

        Response::error('Error interno del servidor', 500);
    }

    public function router(): Router
    {
        return $this->router;
    }
}
