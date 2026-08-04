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
    private Comentario $comentarios;
    private Evento $eventos;

    public function __construct()
    {
        $this->comentarios = new Comentario();
        $this->eventos = new Evento();
    }

    /** GET /api/eventos/{id}/comentarios */
    public function index(Request $request): void
    {
        $eventoId = $this->idParam($request);

        if (!$this->eventos->exists($eventoId)) {
            throw HttpException::notFound('El evento solicitado no existe.');
        }

        $limite = (int) ($request->query('limite', 50));
        $offset = (int) ($request->query('offset', 0));

        Response::json([
            'ok'    => true,
            'total' => $this->comentarios->totalPorEvento($eventoId),
            'data'  => $this->comentarios->porEvento($eventoId, $limite, $offset),
        ]);
    }

    /** POST /api/eventos/{id}/comentarios */
    public function store(Request $request): void
    {
        $eventoId = $this->idParam($request);

        if (!$this->eventos->exists($eventoId)) {
            throw HttpException::notFound('El evento sobre el que intenta comentar no existe.');
        }

        $datos = Validator::make($request->body())
            ->required('autor')->string('autor', 3, 120)
            ->required('contenido')->string('contenido', 3, 1000)
            ->validated();

        $this->created($this->comentarios->crear($eventoId, $datos), 'Comentario publicado correctamente.');
    }

    /** DELETE /api/comentarios/{id} */
    public function destroy(Request $request): void
    {
        $id = $this->idParam($request);

        if (!$this->comentarios->delete($id)) {
            throw HttpException::notFound('El comentario que intenta eliminar no existe.');
        }

        $this->ok(['id' => $id], 'Comentario eliminado correctamente.');
    }
}
