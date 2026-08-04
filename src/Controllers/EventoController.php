<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Categoria;
use App\Models\Evento;

/**
 * RF "Crear evento" y "Ver catalogo de eventos" (Juliana Burgos).
 */
final class EventoController extends Controller
{
    private const ESTADOS = ['activo', 'cancelado', 'finalizado'];

    private Evento $eventos;
    private Categoria $categorias;

    public function __construct()
    {
        $this->eventos = new Evento();
        $this->categorias = new Categoria();
    }

    /**
     * GET /api/eventos
     * Filtros: categoria_id, fecha_desde, fecha_hasta, q, estado,
     *          solo_disponibles, solo_proximos, limite, offset
     */
    public function index(Request $request): void
    {
        $filtros = [
            'categoria_id'     => $request->query('categoria_id'),
            'q'                => $request->query('q'),
            'estado'           => $request->query('estado'),
            'solo_disponibles' => filter_var($request->query('solo_disponibles', false), FILTER_VALIDATE_BOOLEAN),
            'solo_proximos'    => filter_var($request->query('solo_proximos', false), FILTER_VALIDATE_BOOLEAN),
            'limite'           => $request->query('limite'),
            'offset'           => $request->query('offset'),
        ];

        foreach (['fecha_desde', 'fecha_hasta'] as $campo) {
            $valor = $request->query($campo);

            if ($valor === null) {
                continue;
            }

            $fecha = Validator::parseDate((string) $valor);

            if ($fecha === null) {
                throw HttpException::badRequest(sprintf('El filtro "%s" no tiene un formato de fecha valido.', $campo));
            }

            $filtros[$campo] = $fecha->format('Y-m-d H:i:sP');
        }

        if ($filtros['categoria_id'] !== null && filter_var($filtros['categoria_id'], FILTER_VALIDATE_INT) === false) {
            throw HttpException::badRequest('El filtro "categoria_id" debe ser numerico.');
        }

        if ($filtros['estado'] !== null && !in_array($filtros['estado'], self::ESTADOS, true)) {
            throw HttpException::badRequest('El filtro "estado" debe ser: ' . implode(', ', self::ESTADOS) . '.');
        }

        $eventos = $this->eventos->catalogo($filtros);

        Response::json([
            'ok'    => true,
            'total' => count($eventos),
            'data'  => $eventos,
        ]);
    }

    /** GET /api/eventos/{id} */
    public function show(Request $request): void
    {
        $evento = $this->eventos->detalle($this->idParam($request));

        if ($evento === null) {
            throw HttpException::notFound('El evento solicitado no existe.');
        }

        $this->ok($evento);
    }

    /** POST /api/eventos */
    public function store(Request $request): void
    {
        $datos = Validator::make($request->body())
            ->required('titulo')->string('titulo', 5, 150)
            ->required('categoria_id')->integer('categoria_id', 1)
            ->required('ubicacion')->string('ubicacion', 3, 150)
            ->required('fecha_evento')->datetime('fecha_evento', true)
            ->required('cupos_maximos')->integer('cupos_maximos', 1, 10000)
            ->optionalString('descripcion', 2000)
            ->optionalString('organizador', 120)
            ->optionalString('imagen_url', 500)
            ->in('estado', self::ESTADOS)
            ->validated();

        if (!$this->categorias->exists((int) $datos['categoria_id'])) {
            throw HttpException::badRequest('La categoria indicada no existe.');
        }

        $this->created($this->eventos->crear($datos), 'Evento creado correctamente.');
    }

    /** PUT /api/eventos/{id} */
    public function update(Request $request): void
    {
        $id = $this->idParam($request);

        if (!$this->eventos->exists($id)) {
            throw HttpException::notFound('El evento que intenta actualizar no existe.');
        }

        $datos = Validator::make($request->body())
            ->string('titulo', 5, 150)
            ->integer('categoria_id', 1)
            ->string('ubicacion', 3, 150)
            ->datetime('fecha_evento')
            ->integer('cupos_maximos', 1, 10000)
            ->optionalString('descripcion', 2000)
            ->optionalString('organizador', 120)
            ->optionalString('imagen_url', 500)
            ->in('estado', self::ESTADOS)
            ->validated();

        if (isset($datos['categoria_id']) && !$this->categorias->exists((int) $datos['categoria_id'])) {
            throw HttpException::badRequest('La categoria indicada no existe.');
        }

        if (isset($datos['cupos_maximos'])) {
            $inscritos = $this->eventos->totalInscritos($id);

            if ((int) $datos['cupos_maximos'] < $inscritos) {
                throw HttpException::conflict(
                    sprintf('El aforo no puede ser menor que las %d inscripciones ya registradas.', $inscritos)
                );
            }
        }

        $this->ok($this->eventos->actualizar($id, $datos), 'Evento actualizado correctamente.');
    }

    /** DELETE /api/eventos/{id} */
    public function destroy(Request $request): void
    {
        $id = $this->idParam($request);

        if (!$this->eventos->exists($id)) {
            throw HttpException::notFound('El evento que intenta eliminar no existe.');
        }

        $this->eventos->delete($id);

        $this->ok(['id' => $id], 'Evento eliminado correctamente.');
    }
}
