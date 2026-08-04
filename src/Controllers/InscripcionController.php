<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Evento;
use App\Models\Inscripcion;

/**
 * RF "Registrar inscripcion" y "Ver asistentes" (Diego Parrales).
 */
final class InscripcionController extends Controller
{
    private Inscripcion $inscripciones;
    private Evento $eventos;

    public function __construct()
    {
        $this->inscripciones = new Inscripcion();
        $this->eventos = new Evento();
    }

    /** POST /api/eventos/{id}/inscripciones */
    public function store(Request $request): void
    {
        $eventoId = $this->idParam($request);

        $datos = Validator::make($request->body())
            ->required('nombre_estudiante')->string('nombre_estudiante', 3, 120)
            ->required('correo')->email('correo')
            ->optionalString('matricula', 20)
            ->optionalString('telefono', 20)
            ->validated();

        // La validacion de cupos ocurre dentro de una transaccion en el modelo.
        $inscripcion = $this->inscripciones->registrar($eventoId, $datos);

        $this->created($inscripcion, 'Inscripcion registrada correctamente.');
    }

    /** GET /api/eventos/{id}/asistentes */
    public function asistentes(Request $request): void
    {
        $eventoId = $this->idParam($request);
        $evento = $this->eventos->detalle($eventoId);

        if ($evento === null) {
            throw HttpException::notFound('El evento solicitado no existe.');
        }

        $busqueda = $request->query('q');
        $asistentes = $this->inscripciones->asistentesPorEvento($eventoId, $busqueda === null ? null : (string) $busqueda);

        Response::json([
            'ok'      => true,
            'evento'  => [
                'id'                => (int) $evento['id'],
                'titulo'            => $evento['titulo'],
                'fecha_evento'      => $evento['fecha_evento'],
                'ubicacion'         => $evento['ubicacion'],
                'cupos_maximos'     => (int) $evento['cupos_maximos'],
                'cupos_disponibles' => (int) $evento['cupos_disponibles'],
                'inscritos'         => (int) $evento['inscritos'],
            ],
            'total'   => count($asistentes),
            'data'    => $asistentes,
        ]);
    }

    /** DELETE /api/inscripciones/{id} */
    public function destroy(Request $request): void
    {
        $id = $this->idParam($request);

        if (!$this->inscripciones->cancelar($id)) {
            throw HttpException::notFound('La inscripcion que intenta cancelar no existe.');
        }

        $this->ok(['id' => $id], 'Inscripcion cancelada; el cupo fue devuelto al evento.');
    }
}
