<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Comentario;
use App\Models\Evento;

/**
 * RF "Escribir comentario de evento" y "Ver comentarios de evento" (Eimmy Ochoa).
 */
final class ComentarioController extends Controller
{
    private Comentario $modeloComentario;
    private Evento $modeloEvento;

    public function __construct()
    {
        $this->modeloComentario = new Comentario();
        $this->modeloEvento = new Evento();
    }

    /** GET /api/eventos/{id}/comentarios */
    public function index(Request $request): void
    {
        $idDelEvento = $this->idParam($request);

        if (!$this->modeloEvento->exists($idDelEvento)) {
            throw HttpException::notFound('El evento solicitado no existe.');
        }

        $cantidad = (int) ($request->query('limite', 50));
        $inicio = (int) ($request->query('offset', 0));

        Response::json([
            'ok'    => true,
            'total' => $this->modeloComentario->contarComentariosDeEvento($idDelEvento),
            'data'  => $this->modeloComentario->obtenerComentariosDeEvento($idDelEvento, $cantidad, $inicio),
        ]);
    }

    /** POST /api/eventos/{id}/comentarios */
    public function store(Request $request): void
    {
        $idDelEvento = $this->idParam($request);

        if (!$this->modeloEvento->exists($idDelEvento)) {
            throw HttpException::notFound('El evento sobre el que intenta comentar no existe.');
        }

        $datosValidados = Validator::make($request->body())
            ->required('autor')->string('autor', 3, 120)
            ->required('contenido')->string('contenido', 3, 1000)
            ->validated();

        $this->created($this->modeloComentario->registrarComentario($idDelEvento, $datosValidados), 'Comentario publicado correctamente.');
    }

    /** DELETE /api/comentarios/{id} */
    public function destroy(Request $request): void
    {
        $idDeComentario = $this->idParam($request);

        if (!$this->modeloComentario->delete($idDeComentario)) {
            throw HttpException::notFound('El comentario que intenta eliminar no existe.');
        }

        $this->ok(['id' => $idDeComentario], 'Comentario eliminado correctamente.');
    }
}
