<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Evento;

/**
 * RF "Crear evento" y "Ver catalogo de eventos" (Juliana Burgos).
 */
final class EventoController extends Controller
{
    private const ESTADOS = ['activo', 'cancelado', 'finalizado'];

    private Evento $eventos;

    public function __construct()
    {
        $this->eventos = new Evento();
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

        // El total es el de la consulta completa, no el de esta pagina: con
        // 60 eventos y limite 50, count($eventos) diria 50 y el frontend no
        // podria saber que faltan 10 mas.
        Response::json([
            'ok'    => true,
            'total' => $this->eventos->contarCatalogo($filtros),
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
        // Las etiquetas son las que ve el organizador en el formulario: sin ellas
        // el mensaje de error mostraria el nombre de la columna ("cupos_maximos").
        $datos = Validator::make($request->body())
            ->required('titulo', 'titulo')->string('titulo', 5, 150, 'titulo')
            ->required('categoria_id', 'categoria')->integer('categoria_id', 1, null, 'categoria')
            ->required('ubicacion', 'lugar')->string('ubicacion', 3, 150, 'lugar')
            ->required('fecha_evento', 'fecha')->datetime('fecha_evento', true, 'fecha')
            ->required('cupos_maximos', 'aforo')->integer('cupos_maximos', 1, 10000, 'aforo')
            ->optionalString('descripcion', 2000, 'descripcion')
            ->optionalString('organizador', 120, 'organizador')
            ->optionalString('imagen_url', 500, 'imagen')
            ->in('estado', self::ESTADOS, 'estado')
            ->validated();

        // La categoria se valida dentro del modelo, igual que Inscripcion
        // valida sus reglas de negocio antes de escribir en la base de datos.
        $this->created($this->eventos->crear($datos), 'Evento creado correctamente.');
    }

    /** PUT /api/eventos/{id} */
    public function update(Request $request): void
    {
        $id = $this->idParam($request);

        $datos = Validator::make($request->body())
            ->string('titulo', 5, 150, 'titulo')
            ->integer('categoria_id', 1, null, 'categoria')
            ->string('ubicacion', 3, 150, 'lugar')
            ->datetime('fecha_evento', false, 'fecha')
            ->integer('cupos_maximos', 1, 10000, 'aforo')
            ->optionalString('descripcion', 2000, 'descripcion')
            ->optionalString('organizador', 120, 'organizador')
            ->optionalString('imagen_url', 500, 'imagen')
            ->in('estado', self::ESTADOS, 'estado')
            ->validated();

        // Existencia del evento, existencia de la categoria y el chequeo de
        // aforo vs. inscritos ocurren dentro del modelo, bajo la misma
        // transaccion que ajusta los cupos.
        $this->ok($this->eventos->actualizar($id, $datos), 'Evento actualizado correctamente.');
    }

    /** DELETE /api/eventos/{id} */
    public function destroy(Request $request): void
    {
        $id = $this->idParam($request);

        if (!$this->eventos->delete($id)) {
            throw HttpException::notFound('El evento que intenta eliminar no existe.');
        }

        $this->ok(['id' => $id], 'Evento eliminado correctamente.');
    }
}
