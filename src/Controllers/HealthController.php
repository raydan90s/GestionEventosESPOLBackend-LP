<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * Diagnostico rapido de la API y de la conexion con Supabase.
 */
final class HealthController extends Controller
{
    /** GET /api/health */
    public function index(Request $request): void
    {
        $dbOk = Database::check();

        Response::json([
            'ok'        => $dbOk,
            'app'       => Config::get('app.name'),
            'env'       => Config::get('app.env'),
            'php'       => PHP_VERSION,
            'database'  => $dbOk ? 'conectada' : 'sin conexion',
            'timestamp' => date('c'),
        ], $dbOk ? 200 : 503);
    }
}
