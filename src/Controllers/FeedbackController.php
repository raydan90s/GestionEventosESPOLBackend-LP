<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Services\FeedbackService;

/**
 * RF "Escribir comentario de evento" y "Ver comentarios de evento" (Eimmy Ochoa).
 */
final class FeedbackController extends Controller
{
    private FeedbackService $servicio;

    public function __construct()
    {
        $this->servicio = new FeedbackService();
    }

    /** GET /api/eventos/{id}/comentarios */
    public function index(Request $request): void
    {
        $idDelEvento = $this->idParam($request);
        $cantidad = (int) ($request->query('limite', 50));
        $inicio = (int) ($request->query('offset', 0));

        $resultado = $this->servicio->obtenerParaEvento($idDelEvento, $cantidad, $inicio);

        Response::json([
            'ok'    => true,
            'total' => $resultado['total'],
            'data'  => $resultado['data'],
        ]);
    }

    /** POST /api/eventos/{id}/comentarios */
    public function store(Request $request): void
    {
        $idDelEvento = $this->idParam($request);

        $datosValidados = Validator::make($request->body())
            ->required('autor')->string('autor', 3, 120)
            ->required('contenido')->string('contenido', 3, 1000)
            ->validated();

        $creado = $this->servicio->guardar($idDelEvento, $datosValidados);

        $this->created($creado, 'Comentario publicado correctamente.');
    }

    /** DELETE /api/comentarios/{id} */
    public function destroy(Request $request): void
    {
        $idAborrar = $this->idParam($request);

        $this->servicio->borrar($idAborrar);

        $this->ok(['id' => $idAborrar], 'Comentario eliminado correctamente.');
    }
}
